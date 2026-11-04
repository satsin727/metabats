<?php
/*
 * BATS Open Tasks - email & Google Chat notifications (2026-11)
 *
 *  tk_settings  one row: channels on/off, SMTP (shared with Email Distribution or a separate account),
 *               default Google Chat groups, and per-event rules + templates (rules_json)
 *  tk_notify    outgoing queue: one row per email recipient / chat group, sent in the background
 *
 * Task actions only QUEUE notifications (fast, never fail the action). They are sent by
 * tk_notify_run(): the board calls it right after each action, and the email cron
 * (email_worker.php) also runs it, so nothing is lost if a browser closes.
 * Google Chat webhooks and the Gmail password are the ones stored by Email Distribution (encrypted).
 */
require_once __DIR__ . '/tasks_lib.php';
require_once __DIR__ . '/email_lib.php';

const TK_EVENTS = [
    'task_created'   => 'Task created',
    'task_assigned'  => 'Task assigned / reassigned',
    'task_status'    => 'Stage changed (other moves)',
    'task_review'    => 'Submitted for review',
    'task_rework'    => 'Sent back for rework',
    'task_on_hold'   => 'Put on hold',
    'task_completed' => 'Completed',
    'task_cancelled' => 'Cancelled',
    'task_reopened'  => 'Reopened',
    'task_comment'   => 'New comment',
];
const TK_PLACEHOLDERS = [
    '{recipient}' => 'Person receiving the email (group name in Google Chat)', '{actor}' => 'Who did the action',
    '{task_key}' => 'TSK-0001', '{title}' => 'Task title', '{description}' => 'Task description', '{priority}' => 'Priority',
    '{category}' => 'Category', '{stage}' => 'Current stage', '{from_stage}' => 'Previous stage', '{due_date}' => 'Due date',
    '{assignee}' => 'Assignee', '{manager}' => 'Manager', '{creator}' => 'Created by', '{reason}' => 'Reason / resolution note',
    '{comment}' => 'Comment text', '{link}' => 'Link to the task', '{date}' => 'Date & time of the action',
];

function tk_default_rules(): array {
    $mk = function (string $subj, string $line, array $to, bool $chat = true, string $extra = '') {
        return ['email' => 1, 'chat_on' => $chat ? 1 : 0, 'to' => $to, 'cc' => '', 'groups' => [],
            'subject' => '[BATS] ' . $subj . ' {task_key}: {title}',
            'body' => "Hi {recipient},\n\n" . $line . "\n\n**Task:** {task_key} - {title}\n**Stage:** {stage}\n**Priority:** {priority}\n**Due:** {due_date}\n**Assignee:** {assignee}\n**Manager:** {manager}"
                . ($extra !== '' ? "\n\n" . $extra : '') . "\n\nOpen the task: {link}\n\n- BATS Open Tasks",
            'chat' => '*' . $subj . ' {task_key}* - {title}' . "\n" . strip_tags(str_replace('**', '*', $line)) . "\nStage: {stage} | Priority: {priority} | Due: {due_date}\nAssignee: {assignee} | Manager: {manager}"
                . ($extra !== '' ? "\n" . str_replace('**', '*', $extra) : '') . "\n{link}"];
    };
    return [
        'task_created'   => $mk('New task', '**{actor}** created a new task.', ['assignee', 'manager'], true, '{description}'),
        'task_assigned'  => $mk('Task assigned', '**{actor}** assigned this task to **{assignee}**.', ['assignee', 'manager']),
        'task_status'    => $mk('Task update', '**{actor}** moved the task from {from_stage} to **{stage}**.', ['assignee', 'manager', 'creator'], false),
        'task_review'    => $mk('Review needed', '**{actor}** finished the work and submitted it for your review.', ['manager']),
        'task_rework'    => $mk('Rework needed', '**{actor}** sent the task back for rework.', ['assignee'], true, '**Reason:** {reason}'),
        'task_on_hold'   => $mk('Task on hold', '**{actor}** put the task on hold.', ['assignee', 'manager', 'creator'], true, '**Reason:** {reason}'),
        'task_completed' => $mk('Task completed', '**{actor}** marked the task as completed.', ['assignee', 'manager', 'creator'], true, '**Resolution:** {reason}'),
        'task_cancelled' => $mk('Task cancelled', '**{actor}** cancelled the task.', ['assignee', 'manager', 'creator'], true, '**Reason:** {reason}'),
        'task_reopened'  => $mk('Task reopened', '**{actor}** reopened the task.', ['assignee', 'manager', 'creator'], true, '**Reason:** {reason}'),
        'task_comment'   => $mk('New comment on', '**{actor}** commented:', ['assignee', 'manager', 'creator'], false, '{comment}'),
    ];
}

function tk_notify_schema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    tk_schema();
    $db = tk_db();
    $db->exec("CREATE TABLE IF NOT EXISTS tk_settings (
        id INT NOT NULL PRIMARY KEY,
        email_enabled TINYINT(1) NOT NULL DEFAULT 0,
        chat_enabled TINYINT(1) NOT NULL DEFAULT 0,
        smtp_mode VARCHAR(10) NOT NULL DEFAULT 'shared',
        smtp_host VARCHAR(150) NOT NULL DEFAULT 'smtp.gmail.com',
        smtp_port INT NOT NULL DEFAULT 587,
        smtp_secure VARCHAR(10) NOT NULL DEFAULT 'tls',
        smtp_email VARCHAR(254) NULL,
        smtp_from_name VARCHAR(150) NOT NULL DEFAULT 'BATS Tasks',
        smtp_password_enc TEXT NULL,
        email_hourly_limit INT NOT NULL DEFAULT 40,
        skip_actor TINYINT(1) NOT NULL DEFAULT 1,
        chat_groups VARCHAR(500) NOT NULL DEFAULT '',
        app_url VARCHAR(300) NULL,
        rules_json MEDIUMTEXT NULL,
        updated_at DATETIME NULL,
        updated_by INT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("INSERT IGNORE INTO tk_settings(id) VALUES (1)");
    $db->exec("CREATE TABLE IF NOT EXISTS tk_notify (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        event VARCHAR(30) NOT NULL,
        channel VARCHAR(10) NOT NULL,
        target VARCHAR(254) NOT NULL,
        target_name VARCHAR(150) NULL,
        subject VARCHAR(300) NULL,
        body TEXT NOT NULL,
        status VARCHAR(12) NOT NULL DEFAULT 'pending',
        attempts INT NOT NULL DEFAULT 0,
        last_error VARCHAR(500) NULL,
        actor_uid INT NULL,
        created_at DATETIME NOT NULL,
        available_at DATETIME NOT NULL,
        locked_at DATETIME NULL,
        sent_at DATETIME NULL,
        KEY idx_tkn_due (status, available_at),
        KEY idx_tkn_task (task_id),
        KEY idx_tkn_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function tk_nsettings(): array {
    tk_notify_schema();
    $s = tk_db()->query('SELECT * FROM tk_settings WHERE id = 1')->fetch();
    $rules = json_decode((string)$s['rules_json'], true) ?: [];
    $def = tk_default_rules();
    foreach ($def as $ev => $r) $rules[$ev] = array_merge($r, is_array($rules[$ev] ?? null) ? $rules[$ev] : []);
    $s['rules'] = array_intersect_key($rules, $def);
    $s['group_ids'] = array_values(array_filter(array_map('intval', explode(',', (string)$s['chat_groups']))));
    return $s;
}

/** Base URL of BATS, e.g. https://bats.tektrekkers.com/appxbats/ (remembered from the last web request). */
function tk_app_url(array $s): string {
    if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
        $url = ($https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
        if ($url !== (string)$s['app_url']) { try { tk_db()->prepare('UPDATE tk_settings SET app_url = ? WHERE id = 1')->execute([$url]); } catch (Throwable $e) {} }
        return $url;
    }
    return (string)($s['app_url'] ?: '');
}
function tk_user_email(?int $uid): string {
    if (!$uid) return '';
    static $c = [];
    if (!array_key_exists($uid, $c)) {
        $q = tk_db()->prepare('SELECT email, username, status FROM users WHERE uid = ?');
        $q->execute([$uid]);
        $r = $q->fetch();
        $e = '';
        if ($r && (int)$r['status'] === 1) foreach ([$r['email'] ?? '', $r['username'] ?? ''] as $x) if (filter_var(trim((string)$x), FILTER_VALIDATE_EMAIL)) { $e = trim((string)$x); break; }
        $c[$uid] = $e;
    }
    return $c[$uid];
}
function tk_vars(array $t, int $actor, array $extra, string $url): array {
    return [
        '{actor}' => tk_uname($actor), '{task_key}' => tk_key((int)$t['id']), '{title}' => (string)$t['title'],
        '{description}' => trim((string)$t['description']) !== '' ? (string)$t['description'] : '(no description)',
        '{priority}' => TK_PRIORITIES[$t['priority']] ?? (string)$t['priority'], '{category}' => (string)$t['category'],
        '{stage}' => tk_stage_label($t['status']), '{from_stage}' => tk_stage_label($extra['from'] ?? ''),
        '{due_date}' => $t['due_date'] ? date('m/d/Y', strtotime($t['due_date'])) : 'Not set',
        '{assignee}' => tk_uname($t['assignee_uid']) ?: 'Unassigned', '{manager}' => tk_uname($t['manager_uid']) ?: 'None',
        '{creator}' => tk_uname($t['created_by']), '{reason}' => (string)($extra['reason'] ?? '') ?: '-', '{comment}' => (string)($extra['comment'] ?? ''),
        '{link}' => $url !== '' ? $url . 'admin.php?action=opentasks&task=' . (int)$t['id'] : '', '{date}' => date('m/d/Y g:i A'),
    ];
}
/** Google Chat formatting: **bold** -> *bold*, __underline__ -> _italic_ (Chat has no underline). */
function tk_chat_format(string $s): string {
    $s = preg_replace('/\*\*(?=\S)([^\n]*?\S)\*\*/', '*$1*', $s);
    return preg_replace('/__(?=\S)([^\n]*?\S)__/', '_$1_', $s);
}

/** Map a stage move to its notification event. */
function tk_move_event(string $from, string $to): string {
    if (in_array($from, ['completed', 'cancelled'], true)) return 'task_reopened';
    if ($to === 'in_review') return 'task_review';
    if ($from === 'in_review' && $to === 'in_progress') return 'task_rework';
    if ($to === 'on_hold') return 'task_on_hold';
    if ($to === 'completed') return 'task_completed';
    if ($to === 'cancelled') return 'task_cancelled';
    return 'task_status';
}

/** Queue the notifications for one task event. Never throws (a notification problem must not break the task action). */
function tk_notify(string $event, int $taskId, int $actor, array $extra = []): int {
    try {
        $s = tk_nsettings();
        $rule = $s['rules'][$event] ?? null;
        if (!$rule) return 0;
        $doEmail = (int)$s['email_enabled'] === 1 && !empty($rule['email']);
        $doChat = (int)$s['chat_enabled'] === 1 && !empty($rule['chat_on']);
        if (!$doEmail && !$doChat) return 0;
        $t = tk_get($taskId);
        if (!$t) return 0;
        $vars = tk_vars($t, $actor, $extra, tk_app_url($s));
        $now = tk_now();
        $ins = tk_db()->prepare('INSERT INTO tk_notify(task_id, event, channel, target, target_name, subject, body, actor_uid, created_at, available_at) VALUES(?,?,?,?,?,?,?,?,?,?)');
        $n = 0;
        if ($doEmail) {
            $uids = [];
            foreach ((array)$rule['to'] as $who) {
                $uid = ['assignee' => $t['assignee_uid'], 'manager' => $t['manager_uid'], 'creator' => $t['created_by']][$who] ?? null;
                if ($uid) $uids[(int)$uid] = true;
            }
            if ((int)$s['skip_actor'] === 1) unset($uids[$actor]);
            $sent = [];
            foreach (array_keys($uids) as $uid) {
                $em = tk_user_email($uid);
                if ($em === '' || isset($sent[strtolower($em)])) continue;
                $sent[strtolower($em)] = true;
                $v = $vars + ['{recipient}' => tk_uname($uid)];
                $ins->execute([$taskId, $event, 'email', $em, tk_uname($uid), mb_substr(strtr((string)$rule['subject'], $v), 0, 300), strtr((string)$rule['body'], $v), $actor, $now, $now]);
                $n++;
            }
            foreach (preg_split('/[,;\s]+/', (string)($rule['cc'] ?? '')) as $em) {
                $em = trim($em);
                if (!filter_var($em, FILTER_VALIDATE_EMAIL) || isset($sent[strtolower($em)])) continue;
                $sent[strtolower($em)] = true;
                $v = $vars + ['{recipient}' => 'Team'];
                $ins->execute([$taskId, $event, 'email', $em, $em, mb_substr(strtr((string)$rule['subject'], $v), 0, 300), strtr((string)$rule['body'], $v), $actor, $now, $now]);
                $n++;
            }
        }
        if ($doChat) {
            $gids = array_values(array_filter(array_map('intval', (array)($rule['groups'] ?? []))));
            if (!$gids) $gids = $s['group_ids'];
            foreach ($gids ? em_pick_groups($gids, true) : [] as $gid => $g) {
                $v = $vars + ['{recipient}' => (string)$g['name']];
                $ins->execute([$taskId, $event, 'chat', (string)$gid, (string)$g['name'], null, tk_chat_format(strtr((string)$rule['chat'], $v)), $actor, $now, $now]);
                $n++;
            }
        }
        return $n;
    } catch (Throwable $e) {
        error_log('tk_notify: ' . $e->getMessage());
        return 0;
    }
}

/** SMTP account for task emails: [host, port, secure, email, password, fromName]. */
function tk_smtp(array $s): array {
    if ($s['smtp_mode'] === 'custom') {
        if (!filter_var((string)$s['smtp_email'], FILTER_VALIDATE_EMAIL) || empty($s['smtp_password_enc'])) throw new RuntimeException('Task SMTP is set to "separate account" but the email or password is missing (Open Tasks > Notifications).');
        return [(string)$s['smtp_host'], (int)$s['smtp_port'], (string)$s['smtp_secure'], (string)$s['smtp_email'], em_decrypt((string)$s['smtp_password_enc']), (string)$s['smtp_from_name']];
    }
    em_schema();
    $e = em_settings();
    if (!filter_var((string)($e['smtp_email'] ?? ''), FILTER_VALIDATE_EMAIL) || empty($e['smtp_password_enc'])) throw new RuntimeException('Gmail SMTP is not configured in Email Distribution > SMTP & Google Chat.');
    return ['smtp.gmail.com', 587, 'tls', (string)$e['smtp_email'], em_decrypt((string)$e['smtp_password_enc']), (string)($s['smtp_from_name'] ?: $e['smtp_from_name'])];
}
function tk_mailer(array $smtp) {
    require_once __DIR__ . '/vendor/autoload.php';
    $m = new \PHPMailer\PHPMailer\PHPMailer(true);
    $m->isSMTP();
    [$m->Host, $port, $sec, $m->Username, $m->Password, $from] = $smtp;
    $m->Port = $port;
    $m->SMTPAuth = true;
    $m->SMTPSecure = $sec === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : ($sec === 'tls' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : '');
    if ($sec === 'none') $m->SMTPAutoTLS = false;
    $m->Timeout = 20;
    $m->CharSet = 'UTF-8';
    $m->SMTPKeepAlive = true;
    $m->setFrom($m->Username, $from);
    return $m;
}
function tk_send_email($m, string $to, string $subject, string $body): void {
    $m->clearAllRecipients();
    $m->addAddress($to);
    $m->isHTML(true);
    $m->Subject = em_text_plain($subject);
    $m->Body = em_text_to_html($body);
    $m->AltBody = em_text_plain($body);
    $m->send();
}

/** Send due notifications (up to $max, within $seconds). Returns a short summary. */
function tk_notify_run(int $max = 15, int $seconds = 20): string {
    tk_notify_schema();
    $db = tk_db();
    $start = microtime(true);
    $s = tk_nsettings();
    // rows stuck in "processing" for 15+ minutes (request killed): try once more
    $db->prepare("UPDATE tk_notify SET status = 'pending', locked_at = NULL WHERE status = 'processing' AND locked_at < ?")->execute([date('Y-m-d H:i:s', strtotime('-15 minutes'))]);
    $sentHour = (int)$db->query("SELECT COUNT(*) FROM tk_notify WHERE channel = 'email' AND status = 'sent' AND sent_at >= '" . date('Y-m-d H:i:s', strtotime('-1 hour')) . "'")->fetchColumn();
    $due = $db->prepare("SELECT * FROM tk_notify WHERE status = 'pending' AND available_at <= ? ORDER BY id LIMIT " . (int)$max);
    $due->execute([tk_now()]);
    $ok = $fail = $wait = 0;
    $mailer = null; $smtpErr = null;
    foreach ($due->fetchAll() as $n) {
        if (microtime(true) - $start > $seconds) break;
        if ($n['channel'] === 'email' && (int)$s['email_enabled'] !== 1) { $db->prepare("UPDATE tk_notify SET status = 'skipped', last_error = 'Email notifications are switched off' WHERE id = ?")->execute([$n['id']]); continue; }
        if ($n['channel'] === 'chat' && (int)$s['chat_enabled'] !== 1) { $db->prepare("UPDATE tk_notify SET status = 'skipped', last_error = 'Google Chat notifications are switched off' WHERE id = ?")->execute([$n['id']]); continue; }
        if ($n['channel'] === 'email' && $sentHour >= max(1, (int)$s['email_hourly_limit'])) { $wait++; continue; }   // hourly limit: stays pending
        $claim = $db->prepare("UPDATE tk_notify SET status = 'processing', locked_at = ?, attempts = attempts + 1 WHERE id = ? AND status = 'pending'");
        $claim->execute([tk_now(), $n['id']]);
        if ($claim->rowCount() !== 1) continue;   // another request took it
        try {
            if ($n['channel'] === 'email') {
                if ($smtpErr) throw new RuntimeException($smtpErr);
                if (!$mailer) { try { $mailer = tk_mailer(tk_smtp($s)); } catch (Throwable $e) { $smtpErr = $e->getMessage(); throw $e; } }
                tk_send_email($mailer, (string)$n['target'], (string)$n['subject'], (string)$n['body']);
                $sentHour++;
            } else {
                [$hook] = em_group_webhook((int)$n['target']);
                em_gchat_post($hook, (string)$n['body']);
            }
            $db->prepare("UPDATE tk_notify SET status = 'sent', sent_at = ?, locked_at = NULL, last_error = NULL WHERE id = ?")->execute([tk_now(), $n['id']]);
            $ok++;
        } catch (Throwable $e) {
            $msg = mb_substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 480);
            $final = (int)$n['attempts'] + 1 >= 3;
            $db->prepare("UPDATE tk_notify SET status = ?, last_error = ?, locked_at = NULL, available_at = ? WHERE id = ?")
               ->execute([$final ? 'failed' : 'pending', $msg, date('Y-m-d H:i:s', strtotime('+' . (5 * ((int)$n['attempts'] + 1)) . ' minutes')), $n['id']]);
            $fail++;
            if ($mailer && $n['channel'] === 'email') { try { $mailer->smtpClose(); } catch (Throwable $x) {} $mailer = null; }
        }
    }
    if ($mailer) { try { $mailer->smtpClose(); } catch (Throwable $x) {} }
    return "Task notifications: $ok sent, $fail failed" . ($wait ? ", $wait waiting for the hourly email limit" : '');
}
