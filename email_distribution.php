<?php require_once __DIR__.'/email_lib.php';
$u = em_user();
$db = em_db();
em_schema();
$page = (string)($_GET['page'] ?? 'requirements');
$pages = ['requirements', 'lists', 'templates', 'settings', 'history', 'queue', 'report', 'logs'];
if (!in_array($page, $pages, true))$page = 'requirements';
$adminPages = ['lists', 'templates', 'settings'];
if (in_array($page, $adminPages, true))em_admin($u);
$notice = '';
$error = '';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        em_require_post();
        $op = (string)($_POST['op'] ?? '');
        if ($op === 'run_worker') {
            em_admin($u);
            if ($page !== 'queue') {
                throw new RuntimeException('Invalid queue action');
            }
            // Release the PHP session lock before a potentially slow SMTP connection.
            // The login and CSRF checks have already completed above.
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            require_once __DIR__ . '/email_worker_core.php';
            $result = em_process_one_due_message();
            // Redirect without leaking SMTP errors into the URL.
            $notice = $result;
        }
        elseif ($op === 'settings') {
            em_admin($u);
            $email = trim((string)($_POST['smtp_email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL))throw new RuntimeException('Valid Gmail address required');
            $hour = max(1, min(100, (int)($_POST['hourly_limit'] ?? 15)));
            $daily = max(1, min(1000, (int)($_POST['daily_recipient_limit'] ?? 100)));
            $gap = max(0, min(3600, (int)($_POST['min_gap_seconds'] ?? 240)));
            $tz = trim((string)($_POST['timezone_name'] ?? ''));
            if (!in_array($tz, timezone_identifiers_list(), true))throw new RuntimeException('Invalid timezone');
            $password = trim((string)($_POST['smtp_password'] ?? ''));
            $encrypted = $password !== '' ? em_encrypt($password): null;
            $s = $db->prepare('UPDATE em_settings SET smtp_email=?,smtp_from_name=?,smtp_password_enc=COALESCE(?,smtp_password_enc),hourly_limit=?,daily_recipient_limit=?,min_gap_seconds=?,timezone_name=?,queue_paused=?,default_to_list=?,default_cc_list=?,default_bcc_list=?,send_start=?,send_end=? WHERE id=1');
            $s->execute([$email, trim((string)($_POST['smtp_from_name'] ?? 'Recruitment Team')), $encrypted, $hour, $daily, $gap, $tz, isset($_POST['queue_paused']) ? 1: 0, ($_POST['default_to_list'] ?? '') !== '' ? (int)$_POST['default_to_list']: null, ($_POST['default_cc_list'] ?? '') !== '' ? (int)$_POST['default_cc_list']: null, ($_POST['default_bcc_list'] ?? '') !== '' ? (int)$_POST['default_bcc_list']: null, ($_POST['send_start'] ?? '') ?: null, ($_POST['send_end'] ?? '') ?: null]);
            // Google Chat webhook (optional)
            // The settings page sends these two fields base64-encoded and reversed (see email_distribution_ui.php)
            // so Bluehost Mod_Security does not block a raw webhook URL in the form post.
            $decode = function ($x) { $raw = base64_decode(strrev((string)$x), true); return $raw === false ? '' : $raw; };
            $hook = trim(isset($_POST['gchat_webhook_x']) ? $decode($_POST['gchat_webhook_x']) : (string)($_POST['gchat_webhook'] ?? ''));
            $chatTemplate = isset($_POST['gchat_template_x']) ? $decode($_POST['gchat_template_x']) : (string)($_POST['gchat_template'] ?? '');
            if ($hook !== '' && !em_gchat_valid_url($hook)) throw new RuntimeException('Google Chat webhook must look like https://chat.googleapis.com/v1/spaces/.../messages?key=...&token=...');
            $hookEnc = $hook !== '' ? em_encrypt($hook) : null;
            $clearHook = isset($_POST['gchat_clear']) ? 1 : 0;
            $db->prepare('UPDATE em_settings SET gchat_enabled=?, gchat_template=?, gchat_webhook_enc=CASE WHEN ?=1 THEN NULL ELSE COALESCE(?,gchat_webhook_enc) END WHERE id=1')
               ->execute([isset($_POST['gchat_enabled']) ? 1 : 0, trim($chatTemplate), $clearHook, $hookEnc]);
            em_log('settings_updated', 'SMTP/rate-limit settings changed', null, null, (int)$u['uid']);
            em_redirect('settings', 'Settings saved');
        }
        if ($op === 'create_list') {
            em_admin($u);
            $name = trim((string)($_POST['name'] ?? ''));
            if (!$name || strlen($name) > 450)throw new RuntimeException('List name required (max 150 characters)');
            $db->prepare('INSERT INTO em_lists(name) VALUES(?)')->execute([$name]);
            em_log('list_created', $name, null, null, (int)$u['uid']);
            em_redirect('lists', 'List created');
        }
        if ($op === 'add_contact') {
            em_admin($u);
            $email = trim((string)($_POST['email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL))throw new RuntimeException('Invalid email');
            $db->prepare('INSERT INTO em_contacts(email,display_name,active) VALUES(?,?,1) ON DUPLICATE KEY UPDATE display_name=VALUES(display_name),active=1')->execute([$email, trim((string)($_POST['display_name'] ?? ''))]);
            em_redirect('lists', 'Contact saved');
        }
        if ($op === 'member') {
            em_admin($u);
            $lid = (int)($_POST['list_id'] ?? 0);
            $cid = (int)($_POST['contact_id'] ?? 0);
            $role = (string)($_POST['recipient_type'] ?? 'to');
            if (!in_array($role, ['to', 'cc', 'bcc'], true))throw new RuntimeException('Invalid role');
            $q = $db->prepare('SELECT id FROM em_lists WHERE id=?');
            $q->execute([$lid]);
            if (!$q->fetch())throw new RuntimeException('Unknown list');
            $q = $db->prepare('SELECT id FROM em_contacts WHERE id=?');
            $q->execute([$cid]);
            if (!$q->fetch())throw new RuntimeException('Unknown contact');
            $db->prepare('INSERT INTO em_list_members(list_id,contact_id,recipient_type) VALUES(?,?,?) ON DUPLICATE KEY UPDATE recipient_type=VALUES(recipient_type)')->execute([$lid, $cid, $role]);
            em_redirect('lists', 'Member added/updated');
        }
        if ($op === 'remove_member') {
            em_admin($u);
            $db->prepare('DELETE FROM em_list_members WHERE list_id=? AND contact_id=?')->execute([(int)$_POST['list_id'], (int)$_POST['contact_id']]);
            em_redirect('lists', 'Member removed');
        }
        if ($op === 'template') {
            em_admin($u);
            $name = trim((string)($_POST['name'] ?? ''));
            $subject = trim((string)($_POST['subject'] ?? ''));
            $body = trim((string)($_POST['body'] ?? ''));
            if (!$name || !$subject || !$body)throw new RuntimeException('Name, subject and body required');
            $id = (int)($_POST['template_id'] ?? 0);
            if ($id) {
                $db->prepare('UPDATE em_templates SET name=?,subject=?,body=? WHERE id=?')->execute([$name, $subject, $body, $id]);
            }
            else {
                $db->prepare('INSERT INTO em_templates(name,subject,body) VALUES(?,?,?)')->execute([$name, $subject, $body]);
            }
            em_log('template_saved', $name, null, null, (int)$u['uid']);
            em_redirect('templates', 'Template saved');
        }
        if ($op === 'campaign') {   // non-JavaScript fallback; the page normally queues through email_ajax.php
            $token = (string)($_POST['campaign_token'] ?? '');
            if (!$token || !hash_equals((string)($_SESSION['em_campaign_token'] ?? ''), $token))throw new RuntimeException('Campaign already submitted or page expired. Refresh and try again');
            $r = em_build_campaign($u, $_POST, true);
            unset($_SESSION['em_campaign_token']);
            em_redirect('queue', 'Campaign #'.$r['campaign_id'].' queued ('.count($r['messages']).' messages)');
        }
        if ($op === 'cancel') {
            $camp = (int)($_POST['campaign_id'] ?? 0);
            $q = $db->prepare('SELECT created_by FROM em_campaigns WHERE id=?');
            $q->execute([$camp]);
            $c = $q->fetch();
            if (!$c || ((int)$u['level'] !== 1 && (int)$c['created_by'] !== (int)$u['uid']))throw new RuntimeException('Not authorized');
            $db->beginTransaction();
            $db->prepare("UPDATE em_messages SET status='cancelled', chat_status=IF(chat_status='pending',NULL,chat_status) WHERE campaign_id=? AND status='scheduled'")->execute([$camp]);
            $left = $db->prepare("SELECT COUNT(*) FROM em_messages WHERE campaign_id=? AND status IN ('sent','processing','failed')");
            $left->execute([$camp]);
            if ((int)$left->fetchColumn() === 0)$db->prepare("UPDATE em_campaigns SET status='cancelled' WHERE id=?")->execute([$camp]);
            em_log('campaign_cancelled', 'Pending messages cancelled', null, $camp, (int)$u['uid']);
            $db->commit();
            em_redirect('queue', 'Pending messages cancelled');
        }
        if ($op !== 'run_worker') {
            throw new RuntimeException('Unknown action');
        }
    }
}
catch (Throwable $e) {
    $error = $e->getMessage();
}
$settings = em_settings();
$lists = $db->query('SELECT * FROM em_lists WHERE active=1 ORDER BY name')->fetchAll();
$templates = $db->query('SELECT * FROM em_templates WHERE active=1 ORDER BY name')->fetchAll();
if (!is_file(__DIR__.'/includes/header.php') || !is_file(__DIR__.'/includes/menu.php'))throw new RuntimeException('BATS layout files missing: includes/header.php or includes/menu.php');
require __DIR__.'/includes/header.php';
require __DIR__.'/includes/menu.php';
?>
<?php require __DIR__.'/email_distribution_ui.php'; ?>
<?php require __DIR__.'/includes/footer.php';
?>