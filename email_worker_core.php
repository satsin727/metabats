<?php
/** One queue-processing pass, shared by CLI cron and admin-only Run Now. */
require_once __DIR__ . '/email_lib.php';
require_once __DIR__ . '/vendor/autoload.php';

function em_process_one_due_message(): string
{
    $db = em_db();
    $lock = (int)$db->query("SELECT GET_LOCK('bats_em_worker_global',0)")->fetchColumn();
    if ($lock !== 1) {
        return "Another worker is active";
    }
    try {
        $settings = em_settings();
        if (!$settings || (int)$settings['queue_paused'] === 1) {
            return "Queue paused or not configured";
        }
        if (!filter_var($settings['smtp_email'], FILTER_VALIDATE_EMAIL) || empty($settings['smtp_password_enc'])) {
            throw new RuntimeException('Configure Gmail SMTP first');
        }
        $password = em_decrypt($settings['smtp_password_enc']);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $tz = new DateTimeZone($settings['timezone_name']);
        $clock = $now->setTimezone($tz)->format('H:i:s');
        if ($settings['send_start'] !== null && $settings['send_end'] !== null) {
            $start = $settings['send_start'];
            $end = $settings['send_end'];
            $allowed = $start <= $end ? ($clock >= $start && $clock <= $end): ($clock >= $start || $clock <= $end);
            if (!$allowed) {
                echo "Outside sending window\n";
                exit;
            }
        }
        // Stale processing messages require manual reconciliation: SMTP may have accepted them
        // immediately before a PHP crash. Never automatically resend such messages.
        $stale = $db->query("SELECT id,campaign_id FROM em_messages WHERE status='processing' AND locked_at < UTC_TIMESTAMP()-INTERVAL 30 MINUTE LIMIT 100")->fetchAll();
        foreach ($stale as $r) {
            $db->prepare("UPDATE em_messages SET status='failed',last_error='Interrupted send; check SMTP logs before manually resending' WHERE id=? AND status='processing'")->execute([$r['id']]);
            em_log('processing_interrupted', 'Manual reconciliation required', (int)$r['id'], (int)$r['campaign_id']);
        }
        $last = $db->query("SELECT sent_at FROM em_messages WHERE status='sent' ORDER BY sent_at DESC LIMIT 1")->fetchColumn();
        if ($last && time() - strtotime($last) < (int)$settings['min_gap_seconds']) {
            return "Waiting for minimum gap";
        }
        $hourly = (int)$db->query("SELECT COUNT(*) FROM em_messages WHERE status='sent' AND sent_at >= UTC_TIMESTAMP()-INTERVAL 1 HOUR")->fetchColumn();
        if ($hourly >= (int)$settings['hourly_limit']) {
            return "Hourly limit reached";
        }
        $daily = (int)$db->query("SELECT COALESCE(SUM(recipient_count),0) FROM em_messages WHERE status='sent' AND sent_at >= UTC_TIMESTAMP()-INTERVAL 24 HOUR")->fetchColumn();
        $item = $db->query("SELECT * FROM em_messages WHERE status='scheduled' AND available_at<=UTC_TIMESTAMP() ORDER BY available_at,id LIMIT 1")->fetch();
        if (!$item) {
            return "No due messages";
        }
        if ($daily + (int)$item['recipient_count'] > (int)$settings['daily_recipient_limit']) {
            return "Daily recipient limit reached";
        }
        $q = $db->prepare("UPDATE em_messages SET status='processing',locked_at=UTC_TIMESTAMP(),attempts=attempts+1 WHERE id=? AND status='scheduled'");
        $q->execute([$item['id']]);
        if (!$q->rowCount()) return "Message already claimed by another worker";
        try {
            $rc = json_decode($item['recipients_json'], true, 512, JSON_THROW_ON_ERROR);
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_email'];
            $mail->Password = $password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->Timeout = 25;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($settings['smtp_email'], $settings['smtp_from_name']);
            foreach ($rc['to'] ?? [] as $a)$mail->addAddress($a);
            foreach ($rc['cc'] ?? [] as $a)$mail->addCC($a);
            foreach ($rc['bcc'] ?? [] as $a)$mail->addBCC($a);
            $mail->isHTML(false);
            $mail->Subject = $item['subject'];
            $mail->Body = $item['body'];
            $mail->send();
            $db->prepare("UPDATE em_messages SET status='sent',sent_at=UTC_TIMESTAMP(),locked_at=NULL,last_error=NULL,smtp_message_id=? WHERE id=?")->execute([$mail->getLastMessageID(), $item['id']]);
            em_log('smtp_accepted', 'SMTP accepted message', (int)$item['id'], (int)$item['campaign_id']);
            return "SMTP accepted message #" . $item['id'];
        }
        catch (Throwable $e) {
            $msg = em_mbcut($e->getMessage(), 800);
            $attempt = (int)$item['attempts'] + 1;
            $temporary = preg_match('/(?:^|\D)4[0-9]{2}(?:\D|$)|4\.7\.|timed? out|connection|temporar|try again/i', $msg) === 1;
            if ($temporary && $attempt < 5) {
                $minutes = min(240, 5 * (2**($attempt - 1)));
                $db->prepare("UPDATE em_messages SET status='scheduled',locked_at=NULL,available_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL $minutes MINUTE),last_error=? WHERE id=?")->execute([$msg, $item['id']]);
                em_log('retry_scheduled', 'Retry in '.$minutes.' minutes: '.$msg, (int)$item['id'], (int)$item['campaign_id']);
            }
            else {
                $db->prepare("UPDATE em_messages SET status='failed',locked_at=NULL,last_error=? WHERE id=?")->execute([$msg, $item['id']]);
                em_log('send_failed', $msg, (int)$item['id'], (int)$item['campaign_id']);
            }
            return "Send failed for message #" . $item['id'] . ": " . $msg;
        }
    }
    catch (Throwable $e) {
        return 'Worker error: ' . $e->getMessage();
    }
    finally {
        $db->query("SELECT RELEASE_LOCK('bats_em_worker_global')");
    }

}
