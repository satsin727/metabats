<?php
/** One queue-processing pass, shared by CLI cron and admin-only Run Now. */
require_once __DIR__ . '/email_lib.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends ONE due message. $only = 'email' or 'chat' limits it to that queue; null = whichever is due first.
 * Email and Google Chat have their own limits (email: min gap / hourly / daily recipients;
 * chat: chat min gap / chat hourly), so a waiting email never blocks Google Chat and vice versa.
 */
function em_process_one_due_message(?string $only = null): string
{
    $db = em_db();
    $lock = (int)$db->query("SELECT GET_LOCK('bats_em_worker_global',0)")->fetchColumn();
    if ($lock !== 1) {
        return "Another worker is active";
    }
    try {
        em_schema();
        $settings = em_settings();
        if (!$settings || (int)$settings['queue_paused'] === 1) {
            return "Queue paused or not configured";
        }
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $tz = new DateTimeZone($settings['timezone_name']);
        $clock = $now->setTimezone($tz)->format('H:i:s');
        if ($settings['send_start'] !== null && $settings['send_end'] !== null) {
            $start = $settings['send_start'];
            $end = $settings['send_end'];
            $allowed = $start <= $end ? ($clock >= $start && $clock <= $end): ($clock >= $start || $clock <= $end);
            if (!$allowed) {
                return "Outside sending window";
            }
        }
        // Stale processing messages require manual reconciliation: SMTP may have accepted them
        // immediately before a PHP crash. Never automatically resend such messages.
        $stale = $db->query("SELECT id,campaign_id FROM em_messages WHERE status='processing' AND locked_at < UTC_TIMESTAMP()-INTERVAL 30 MINUTE LIMIT 100")->fetchAll();
        foreach ($stale as $r) {
            $db->prepare("UPDATE em_messages SET status='failed',last_error='Interrupted send; check SMTP logs before manually resending' WHERE id=? AND status='processing'")->execute([$r['id']]);
            em_log('processing_interrupted', 'Manual reconciliation required', (int)$r['id'], (int)$r['campaign_id']);
        }
        $channels = $only === 'chat' ? ['chat'] : ($only === 'email' ? ['email'] : ['email', 'chat']);
        $item = null; $reasons = []; $password = null;
        foreach ($channels as $ch) {
            $due = $db->prepare("SELECT * FROM em_messages WHERE status='scheduled' AND channel=? AND available_at<=UTC_TIMESTAMP() ORDER BY available_at,id LIMIT 1");
            $due->execute([$ch]);
            $cand = $due->fetch();
            if (!$cand) continue;
            $gapLimit = $ch === 'chat' ? (int)($settings['chat_min_gap_seconds'] ?? 2) : (int)$settings['min_gap_seconds'];
            $hourLimit = $ch === 'chat' ? (int)($settings['chat_hourly_limit'] ?? 60) : (int)$settings['hourly_limit'];
            $label = $ch === 'chat' ? 'Google Chat' : 'Email';
            // Compare UTC timestamps in MySQL rather than interpreting UTC as PHP's local timezone.
            $st = $db->prepare("SELECT TIMESTAMPDIFF(SECOND, MAX(sent_at), UTC_TIMESTAMP()) FROM em_messages WHERE status='sent' AND channel=?");
            $st->execute([$ch]);
            $elapsed = $st->fetchColumn();
            if ($elapsed !== null && (int)$elapsed < $gapLimit) {
                $reasons[] = "$label: waiting for minimum gap (" . max(1, $gapLimit - (int)$elapsed) . " seconds remaining)";
                continue;
            }
            $st = $db->prepare("SELECT COUNT(*) FROM em_messages WHERE status='sent' AND channel=? AND sent_at >= UTC_TIMESTAMP()-INTERVAL 1 HOUR");
            $st->execute([$ch]);
            if ((int)$st->fetchColumn() >= $hourLimit) { $reasons[] = "$label: hourly limit reached"; continue; }
            if ($ch === 'chat') {
                if (empty($settings['gchat_enabled']) || empty($settings['gchat_webhook_enc'])) {
                    $reasons[] = 'Google Chat is disabled or has no webhook (SMTP & Google Chat settings)';
                    continue;
                }
            } else {
                if (!filter_var($settings['smtp_email'], FILTER_VALIDATE_EMAIL) || empty($settings['smtp_password_enc'])) {
                    $reasons[] = 'Email: configure Gmail SMTP first';
                    continue;
                }
                $daily = (int)$db->query("SELECT COALESCE(SUM(recipient_count),0) FROM em_messages WHERE status='sent' AND channel='email' AND sent_at >= UTC_TIMESTAMP()-INTERVAL 24 HOUR")->fetchColumn();
                if ($daily + (int)$cand['recipient_count'] > (int)$settings['daily_recipient_limit']) { $reasons[] = 'Email: daily recipient limit reached'; continue; }
                $password = em_decrypt($settings['smtp_password_enc']);
            }
            $item = $cand;
            break;
        }
        if (!$item) {
            return $reasons ? implode('; ', $reasons) : 'No due messages';
        }
        $isChat = (($item['channel'] ?? 'email') === 'chat');
        $q = $db->prepare("UPDATE em_messages SET status='processing',locked_at=UTC_TIMESTAMP(),attempts=attempts+1 WHERE id=? AND status='scheduled'");
        $q->execute([$item['id']]);
        if (!$q->rowCount()) return "Message already claimed by another worker";
        try {
            if ($isChat) {
                em_gchat_post(em_decrypt($settings['gchat_webhook_enc']), (string)$item['body']);
                $db->prepare("UPDATE em_messages SET status='sent',sent_at=UTC_TIMESTAMP(),locked_at=NULL,last_error=NULL,chat_status='posted',chat_error=NULL WHERE id=?")->execute([$item['id']]);
                em_log('gchat_posted', 'Google Chat only message posted for requirement ' . $item['reqid'], (int)$item['id'], (int)$item['campaign_id']);
                return "Google Chat message #" . $item['id'] . " posted";
            }
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
            em_gchat_after_send($item); // optional Google Chat post (never affects the email status)
            return "SMTP accepted message #" . $item['id'];
        }
        catch (Throwable $e) {
            $msg = em_mbcut($e->getMessage(), 800);
            $attempt = (int)$item['attempts'] + 1;
            $temporary = preg_match('/(?:^|\D)4[0-9]{2}(?:\D|$)|4\.7\.|timed? out|connection|temporar|try again/i', $msg) === 1
                || ($isChat && preg_match('/HTTP (?:0|429|5\d\d)\b/', $msg) === 1);
            if ($temporary && $attempt < 5) {
                $minutes = min(240, 5 * (2**($attempt - 1)));
                $db->prepare("UPDATE em_messages SET status='scheduled',locked_at=NULL,available_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL $minutes MINUTE),last_error=? WHERE id=?")->execute([$msg, $item['id']]);
                em_log('retry_scheduled', 'Retry in '.$minutes.' minutes: '.$msg, (int)$item['id'], (int)$item['campaign_id']);
            }
            else {
                $db->prepare("UPDATE em_messages SET status='failed',locked_at=NULL,last_error=?" . ($isChat ? ",chat_status='failed',chat_error=?" : "") . " WHERE id=?")->execute($isChat ? [$msg, $msg, $item['id']] : [$msg, $item['id']]);
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

/**
 * "Send Google Chat Now" and cron: post due Google Chat messages one after another,
 * respecting the chat minimum gap (short waits are slept) and the chat hourly limit.
 */
function em_process_chat_burst(int $max = 20, int $maxSeconds = 25): string
{
    $t0 = time(); $posted = 0; $last = '';
    while ($posted < $max && time() - $t0 < $maxSeconds) {
        $last = em_process_one_due_message('chat');
        if (strpos($last, 'Google Chat message #') === 0) { $posted++; continue; }
        if (preg_match('/Google Chat: waiting for minimum gap \((\d+) seconds/', $last, $m) && (int)$m[1] <= 10 && time() - $t0 + (int)$m[1] < $maxSeconds) {
            sleep((int)$m[1]);
            continue;
        }
        break;
    }
    return $posted ? "Posted $posted Google Chat message(s)" . ($last && strpos($last, 'Google Chat message #') !== 0 ? " — then: $last" : '') : ($last ?: 'No due Google Chat messages');
}
