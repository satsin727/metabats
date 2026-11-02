<?php
/*
 * AJAX endpoint for the Email Distribution pages (no page reloads).
 * Every call needs a logged-in BATS user; every POST needs the page's CSRF token.
 */
require_once __DIR__ . '/email_lib.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function ea_out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
set_exception_handler(function ($e) { ea_out(['ok' => false, 'error' => $e->getMessage()], 400); });

$u = em_user();
$db = em_db();
em_schema();
$op = (string)($_REQUEST['op'] ?? '');
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($isPost) em_verify();

switch ($op) {

case 'preview':
    if (!$isPost) ea_out(['ok' => false, 'error' => 'POST required'], 405);
    $r = em_build_campaign($u, $_POST, false);
    ea_out(['ok' => true, 'messages' => $r['messages'], 'scheduled' => $r['scheduled_local']]);

case 'queue':
    if (!$isPost) ea_out(['ok' => false, 'error' => 'POST required'], 405);
    $token = (string)($_POST['campaign_token'] ?? '');
    if (!$token || !hash_equals((string)($_SESSION['em_campaign_token'] ?? ''), $token)) {
        throw new RuntimeException('This campaign was already submitted or the page expired. Refresh and try again.');
    }
    $r = em_build_campaign($u, $_POST, true);
    $_SESSION['em_campaign_token'] = bin2hex(random_bytes(20));   // ready for the next campaign
    ea_out(['ok' => true, 'campaign_id' => $r['campaign_id'], 'count' => count($r['messages']), 'scheduled' => $r['scheduled_local'],
            'token' => $_SESSION['em_campaign_token'], 'status' => em_share_status((array)($_POST['reqids'] ?? []))]);

case 'status':
    ea_out(['ok' => true, 'status' => em_share_status((array)($_REQUEST['reqids'] ?? []))]);

case 'today_queue':
    [$start, $end, $label] = em_period('today');
    $s = $db->prepare("SELECT m.id,m.campaign_id,m.reqid,r.job_title,m.list_name,m.recipient_count,m.status,m.attempts,m.available_at,m.sent_at,m.last_error,m.chat_status,c.created_by,u.name AS created_name
        FROM em_messages m JOIN em_campaigns c ON c.id=m.campaign_id LEFT JOIN users u ON u.uid=c.created_by LEFT JOIN req r ON r.reqid=m.reqid
        WHERE (m.created_at >= ? AND m.created_at < ?) OR (m.available_at >= ? AND m.available_at < ?) OR m.status IN ('scheduled','processing')
        ORDER BY FIELD(m.status,'processing','scheduled','failed','sent','cancelled'), m.available_at, m.id LIMIT 1000");
    $s->execute([$start, $end, $start, $end]);
    $rows = [];
    $stats = ['scheduled' => 0, 'processing' => 0, 'sent' => 0, 'failed' => 0, 'cancelled' => 0];
    foreach ($s as $m) {
        $stats[$m['status']] = ($stats[$m['status']] ?? 0) + 1;
        $rows[] = ['id' => (int)$m['id'], 'campaign_id' => (int)$m['campaign_id'], 'reqid' => (int)$m['reqid'], 'job_title' => (string)$m['job_title'],
            'list_name' => (string)$m['list_name'], 'recipients' => (int)$m['recipient_count'], 'status' => $m['status'], 'attempts' => (int)$m['attempts'],
            'scheduled' => em_local($m['available_at']), 'sent' => em_local($m['sent_at']), 'error' => (string)$m['last_error'], 'chat' => (string)$m['chat_status'],
            'by' => (string)$m['created_name'],
            'can_cancel' => $m['status'] === 'scheduled' && ((int)$u['level'] === 1 || (int)$m['created_by'] === (int)$u['uid'])];
    }
    $settings = em_settings();
    ea_out(['ok' => true, 'label' => $label, 'now' => (new DateTimeImmutable('now', em_tz()))->format('m/d/Y g:i:s A'), 'rows' => $rows, 'stats' => $stats,
            'paused' => (int)$settings['queue_paused'] === 1, 'hourly' => (int)$settings['hourly_limit'], 'daily' => (int)$settings['daily_recipient_limit']]);

case 'message':
    $q = $db->prepare('SELECT m.*, c.created_by, c.post_to_chat, r.job_title, r.rlocation FROM em_messages m JOIN em_campaigns c ON c.id=m.campaign_id LEFT JOIN req r ON r.reqid=m.reqid WHERE m.id=?');
    $q->execute([(int)($_REQUEST['id'] ?? 0)]);
    $m = $q->fetch();
    if (!$m) ea_out(['ok' => false, 'error' => 'Message not found'], 404);
    $rc = json_decode((string)$m['recipients_json'], true) ?: [];
    ea_out(['ok' => true, 'message' => ['id' => (int)$m['id'], 'campaign_id' => (int)$m['campaign_id'], 'reqid' => (int)$m['reqid'], 'job_title' => (string)$m['job_title'],
        'list_name' => (string)$m['list_name'], 'subject' => (string)$m['subject'], 'body' => (string)$m['body'],
        'to' => $rc['to'] ?? [], 'cc' => $rc['cc'] ?? [], 'bcc' => $rc['bcc'] ?? [], 'status' => $m['status'], 'sent' => em_local($m['sent_at']),
        'scheduled' => em_local($m['available_at']), 'error' => (string)$m['last_error'], 'chat_status' => (string)$m['chat_status'],
        'resend_of' => $m['resend_of'] !== null ? (int)$m['resend_of'] : null, 'post_to_chat' => (int)$m['post_to_chat'] === 1,
        'can_resend' => !in_array($m['status'], ['scheduled', 'processing'], true) && ((int)$u['level'] === 1 || (int)$m['created_by'] === (int)$u['uid'])]]);

case 'resend':
    if (!$isPost) ea_out(['ok' => false, 'error' => 'POST required'], 405);
    $id = em_resend($u, (int)($_POST['id'] ?? 0), (string)($_POST['subject'] ?? ''), (string)($_POST['body'] ?? ''),
        (string)($_POST['to'] ?? ''), (string)($_POST['cc'] ?? ''), (string)($_POST['bcc'] ?? ''), !empty($_POST['post_to_chat']));
    ea_out(['ok' => true, 'id' => $id]);

case 'cancel':
    if (!$isPost) ea_out(['ok' => false, 'error' => 'POST required'], 405);
    $mid = (int)($_POST['message_id'] ?? 0);
    $q = $db->prepare('SELECT m.campaign_id, c.created_by FROM em_messages m JOIN em_campaigns c ON c.id=m.campaign_id WHERE m.id=?');
    $q->execute([$mid]);
    $c = $q->fetch();
    if (!$c || ((int)$u['level'] !== 1 && (int)$c['created_by'] !== (int)$u['uid'])) throw new RuntimeException('Not authorized');
    $s = $db->prepare("UPDATE em_messages SET status='cancelled', chat_status=IF(chat_status='pending',NULL,chat_status) WHERE id=? AND status='scheduled'");
    $s->execute([$mid]);
    em_log('message_cancelled', 'Message cancelled from Today\'s Queue', $mid, (int)$c['campaign_id'], (int)$u['uid']);
    ea_out(['ok' => true, 'cancelled' => $s->rowCount()]);

case 'run_worker':
    if (!$isPost) ea_out(['ok' => false, 'error' => 'POST required'], 405);
    em_admin($u);
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    require_once __DIR__ . '/email_worker_core.php';
    ea_out(['ok' => true, 'result' => em_process_one_due_message()]);

case 'gchat_test':
    if (!$isPost) ea_out(['ok' => false, 'error' => 'POST required'], 405);
    em_admin($u);
    $s = em_settings();
    if (empty($s['gchat_webhook_enc'])) throw new RuntimeException('Save a Google Chat webhook URL first');
    $sample = $db->query("SELECT reqid FROM req WHERE status=1 AND emp_type='C2C' ORDER BY reqid DESC LIMIT 1")->fetchColumn();
    $req = $sample ? em_req((int)$sample) : null;
    $req = $req ?: ['reqid' => 0, 'job_title' => 'Sample Job Title', 'rlocation' => 'Dallas, TX', 'emp_type' => 'C2C', 'companyname' => 'Sample Client', 'rend_client' => 'Sample End Client', 'domain' => 'example.com', 'remail' => 'recruiter@example.com', 'rdesc' => 'Sample job description'];
    em_gchat_post(em_decrypt($s['gchat_webhook_enc']), "[TEST from BATS]\n" . em_gchat_text((string)$s['gchat_template'], $req, 'Test group'));
    em_log('gchat_test', 'Test message posted to Google Chat', null, null, (int)$u['uid']);
    ea_out(['ok' => true, 'result' => 'Test message posted to Google Chat']);

default:
    ea_out(['ok' => false, 'error' => 'Unknown action'], 400);
}
