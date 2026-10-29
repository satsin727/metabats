<?php
// Run every minute from Bluehost cron using PHP CLI. Never invoke over HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require_once __DIR__.'/email_lib.php';
require_once __DIR__.'/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
$db = em_db();
$lock = (int)$db->query("SELECT GET_LOCK('bats_em_worker_global',0)")->fetchColumn();
if ($lock !== 1) {
    echo "Another worker is active\n";
    exit;
}
try {
    $settings = em_settings();
    if (!$settings || (int)$settings['queue_paused'] === 1) {
        echo "Queue paused or not configured\n";
        exit;
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
        echo "Waiting for minimum gap\n";
        exit;
    }
    $hourly = (int)$db->query("SELECT COUNT(*) FROM em_messages WHERE status='sent' AND sent_at >= UTC_TIMESTAMP()-INTERVAL 1 HOUR")->fetchColumn();
    if ($hourly >= (int)$settings['hourly_limit']) {
        echo "Hourly limit reached\n";
        exit;
    }
    $daily = (int)$db->query("SELECT COALESCE(SUM(recipient_count),0) FROM em_messages WHERE status='sent' AND sent_at >= UTC_TIMESTAMP()-INTERVAL 24 HOUR")->fetchColumn();
    $item = $db->query("SELECT * FROM em_messages WHERE status='scheduled' AND available_at<=UTC_TIMESTAMP() ORDER BY available_at,id LIMIT 1")->fetch();
    if (!$item) {
        echo "No due messages\n";
        exit;
    }
    if ($daily + (int)$item['recipient_count'] > (int)$settings['daily_recipient_limit']) {
        echo "Daily recipient limit reached\n";
        exit;
    }
    $q = $db->prepare("UPDATE em_messages SET status='processing',locked_at=UTC_TIMESTAMP(),attempts=attempts+1 WHERE id=? AND status='scheduled'");
    $q->execute([$item['id']]);
    if (!$q->rowCount())exit;
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
        echo "Sent message #" . $item['id'] . "\n";
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
        fwrite(STDERR, "Send failed: $msg\n");
    }
}
catch (Throwable $e) {
    fwrite(STDERR, 'Worker error: '.$e->getMessage()."\n");
    exit(1);
}
finally {
    $db->query("SELECT RELEASE_LOCK('bats_em_worker_global')");
}
