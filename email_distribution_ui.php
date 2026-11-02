<?php
if (!isset($u, $db, $page)) { http_response_code(404); exit; } // include only (via email_distribution.php)
/*
 * Email Distribution pages (included by email_distribution.php after the POST handling).
 * Available: $u, $db, $page, $settings, $lists, $templates, $error, $notice
 */
$isAdmin = (int)$u['level'] === 1;
$csrf = em_csrf();
$campaignToken = $_SESSION['em_campaign_token'] ?? ($_SESSION['em_campaign_token'] = bin2hex(random_bytes(20)));
$todayLocal = new DateTimeImmutable('now', em_tz());
$gchatReady = !empty($settings['gchat_enabled']) && !empty($settings['gchat_webhook_enc']);
$navItems = ['requirements' => 'Share C2C Requirements', 'queue' => "Today's Queue", 'history' => 'History', 'report' => 'Reports', 'logs' => 'Logs'];
if ($isAdmin) $navItems += ['lists' => 'Recipient Lists', 'templates' => 'Templates', 'settings' => 'SMTP & Google Chat'];
?>
<style>
.em-main { min-width: 0; float: right; }
.em-wrap { width: 100%; max-width: 1500px; margin: 24px auto; padding: 14px 20px 32px; box-sizing: border-box; min-width: 0; }
.em-wrap *, .em-wrap *::before, .em-wrap *::after { box-sizing: border-box; }
.em-wrap h2 { margin: 0 0 6px; font-size: 25px; line-height: 1.3; }
.em-today { color: #475467; margin-bottom: 14px; }
.em-nav { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 18px; }
.em-nav .btn { margin: 0; white-space: normal; }
.em-nav .btn.active { background: #1f4e8c; color: #fff; border-color: #1f4e8c; }
.em-wrap .panel { min-width: 0; width: 100%; border: 1px solid #dce2e8; border-radius: 8px; padding: 18px; margin: 14px 0; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.03); }
.em-wrap .panel h4 { margin-top: 0; margin-bottom: 14px; }
.em-wrap input:not([type=checkbox]):not([type=radio]), .em-wrap select, .em-wrap textarea { max-width: 100%; min-width: 0; padding: 7px 9px; border: 1px solid #cbd3db; border-radius: 4px; }
.em-wrap input[type=checkbox] { margin-right: 5px; }
.em-wrap label { display: inline-flex; flex-direction: column; align-items: flex-start; gap: 5px; margin: 0 12px 8px 0; min-width: 0; font-weight: 500; }
.em-wrap label:has(input[type=checkbox]) { flex-direction: row; align-items: center; }
.em-wrap .btn { margin: 2px; }
.em-wrap .em-filters { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px 16px; }
.em-wrap .em-filters label { flex: 0 1 auto; margin: 0; }
.em-wrap .em-filters input[name=q] { width: 240px; }
.em-wrap .em-filters input[name=client] { width: 200px; }
.em-wrap .em-filters select[name=skill] { width: 190px; }
.em-wrap .em-filters input[type=date] { width: 160px; }
.em-wrap .scroll { width: 100%; max-width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.em-wrap table { width: 100%; border-collapse: collapse; }
.em-wrap th, .em-wrap td { padding: 9px 8px; border-bottom: 1px solid #e3e8ed; text-align: left; vertical-align: top; }
.em-wrap th { white-space: nowrap; background: #f7f9fb; }
.em-wrap .scroll table { min-width: 860px; }
.em-wrap .muted { color: #667085; font-size: 13px; }
.em-wrap .tag { display: inline-block; background: #eef1f5; padding: 3px 8px; border-radius: 5px; margin: 2px; }
.em-wrap .domain { font-weight: 600; color: #1f4e8c; }
.em-wrap textarea { width: 100%; }
.em-wrap .label { display: inline-block; margin: 1px 2px 1px 0; }
.em-wrap tr.em-updated td { background: #fffbe6; transition: background 1s; }
.em-wrap .em-pre { white-space: pre-wrap; font-family: Consolas, Menlo, monospace; font-size: 13px; background: #f8fafc; border: 1px solid #e3e8ed; border-radius: 6px; padding: 12px; max-height: 340px; overflow: auto; }
.em-toast { position: fixed; right: 20px; bottom: 20px; z-index: 10050; max-width: 420px; padding: 12px 16px; border-radius: 6px; color: #fff; background: #1e7e34; box-shadow: 0 4px 14px rgba(0,0,0,.2); display: none; }
.em-toast.err { background: #b42318; }
.em-modal-bg { position: fixed; inset: 0; background: rgba(15,23,42,.55); z-index: 10040; display: none; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 40px 12px; }
.em-modal { background: #fff; border-radius: 10px; width: 100%; max-width: 900px; box-shadow: 0 20px 50px rgba(0,0,0,.3); }
.em-modal-h { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-bottom: 1px solid #e3e8ed; }
.em-modal-h h4 { margin: 0; }
.em-modal-b { padding: 16px 18px; max-height: 70vh; overflow-y: auto; }
.em-modal-f { padding: 12px 18px; border-top: 1px solid #e3e8ed; text-align: right; }
.em-mail { border: 1px solid #dce2e8; border-radius: 8px; margin-bottom: 14px; }
.em-mail-h { background: #f7f9fb; padding: 10px 12px; border-bottom: 1px solid #e3e8ed; font-size: 13px; line-height: 1.6; }
.em-mail-h b { display: inline-block; min-width: 62px; }
.em-mail-b { white-space: pre-wrap; padding: 12px; font-family: Arial, sans-serif; font-size: 14px; }
.em-x { background: none; border: 0; font-size: 26px; line-height: 1; cursor: pointer; color: #667085; }
.em-stat { display: inline-block; min-width: 110px; padding: 10px 14px; margin: 0 8px 8px 0; border-radius: 8px; background: #f2f5f8; }
.em-stat b { display: block; font-size: 22px; }
@media (max-width: 767px) {
  .em-main { float: none; width: 100%; margin-left: 0; }
  .em-wrap { margin: 12px auto; padding: 12px; }
  .em-wrap .em-filters label, .em-wrap .em-filters input, .em-wrap .em-filters select { width: 100% !important; }
}
</style>
<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 em-main">
<div class="em-wrap">
<h2>Requirement Email Distribution</h2>
<div class="em-today">Today: <b><?= em_h($todayLocal->format('l, m/d/Y')) ?></b> <span class="muted">(<?= em_h(em_tz()->getName()) ?>)</span></div>
<div class="em-nav"><?php foreach ($navItems as $k => $label): ?><a class="btn btn-default <?= $page === $k ? 'active' : '' ?>" href="admin.php?action=emaildistribution&amp;page=<?= $k ?>"><?= em_h($label) ?></a><?php endforeach; ?></div>
<?php if (isset($_GET['notice'])): ?><div class="alert alert-success"><?= em_h($_GET['notice']) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= em_h($error) ?></div><?php endif; ?>

<?php if ($page === 'requirements'):
/* ===================== REQUIREMENTS (open C2C only) ===================== */
$clientQ = trim((string)($_GET['client'] ?? ''));
$skill = (int)($_GET['skill'] ?? 0);
$from = (string)($_GET['from'] ?? '');
$to = (string)($_GET['to'] ?? '');
$share = (string)($_GET['share'] ?? '');
$term = trim((string)($_GET['q'] ?? ''));
$pg = max(1, (int)($_GET['p'] ?? 1));
$per = 40;
$where = ['r.status=1', "r.emp_type='C2C'"];
$params = [];
if ($clientQ !== '') {
    $where[] = '(c.domain LIKE ? OR c.remail LIKE ? OR c.companyname LIKE ? OR r.rend_client LIKE ?)';
    foreach (range(1, 4) as $i) $params[] = '%' . addcslashes($clientQ, '%_\\') . '%';
}
if ($skill) { $where[] = 'r.skillid=?'; $params[] = $skill; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where[] = 'r.datetime>=?'; $params[] = $from . ' 00:00:00'; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $where[] = 'r.datetime<?'; $params[] = date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'; }
if ($term !== '') {
    $where[] = '(r.job_title LIKE ? OR r.rlocation LIKE ? OR c.companyname LIKE ? OR r.rend_client LIKE ? OR r.reqid = ?)';
    foreach (range(1, 4) as $i) $params[] = '%' . addcslashes($term, '%_\\') . '%';
    $params[] = ctype_digit($term) ? (int)$term : -1;
}
if ($share === 'not_shared') $where[] = "NOT EXISTS(SELECT 1 FROM em_messages x WHERE x.reqid=r.reqid AND x.status IN ('scheduled','processing','sent'))";
if ($share === 'sent') $where[] = "EXISTS(SELECT 1 FROM em_messages x WHERE x.reqid=r.reqid AND x.status='sent')";
if ($share === 'scheduled') $where[] = "EXISTS(SELECT 1 FROM em_messages x WHERE x.reqid=r.reqid AND x.status IN ('scheduled','processing'))";
$cond = implode(' AND ', $where);
$count = $db->prepare("SELECT COUNT(*) FROM req r LEFT JOIN clients c ON c.cid=r.cid WHERE $cond");
$count->execute($params);
$total = (int)$count->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$pg = min($pg, $pages);
$offset = ($pg - 1) * $per;
$q = $db->prepare("SELECT r.reqid,r.job_title,r.rlocation,r.emp_type,r.datetime,r.rend_client,c.companyname,c.remail,
        COALESCE(NULLIF(c.domain,''), SUBSTRING_INDEX(c.remail,'@',-1)) AS domain, s.skillname
    FROM req r LEFT JOIN clients c ON c.cid=r.cid LEFT JOIN skill s ON s.sid=r.skillid
    WHERE $cond ORDER BY r.datetime DESC, r.reqid DESC LIMIT $per OFFSET $offset");
$q->execute($params);
$rows = $q->fetchAll();
$status = em_share_status(array_column($rows, 'reqid'));
$skills = $db->query('SELECT sid,skillname FROM skill ORDER BY skillname')->fetchAll();
$defaultLists = array_filter([(int)$settings['default_to_list'], (int)$settings['default_cc_list'], (int)$settings['default_bcc_list']]);
?>
<div class="panel"><h4>Filter open C2C requirements</h4>
<form method="get" class="em-filters"><input type="hidden" name="action" value="emaildistribution"><input type="hidden" name="page" value="requirements">
 <label>Search <input name="q" value="<?= em_h($term) ?>" placeholder="Req ID, job, location, end client"></label>
 <label>Client / domain <input name="client" value="<?= em_h($clientQ) ?>" placeholder="e.g. acme.com"></label>
 <label>Skill <select name="skill"><option value="0">All skills</option><?php foreach ($skills as $sk): ?><option value="<?= (int)$sk['sid'] ?>" <?= $skill === (int)$sk['sid'] ? 'selected' : '' ?>><?= em_h($sk['skillname']) ?></option><?php endforeach; ?></select></label>
 <label>Posted from <input type="date" name="from" value="<?= em_h($from) ?>"></label><label>Posted to <input type="date" name="to" value="<?= em_h($to) ?>"></label>
 <label>Sharing <select name="share"><option value="">All</option><?php foreach (['not_shared' => 'Not shared', 'sent' => 'Sent', 'scheduled' => 'Queued'] as $k => $v): ?><option value="<?= $k ?>" <?= $share === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
 <button class="btn btn-primary">Apply Filters</button> <a class="btn btn-default" href="admin.php?action=emaildistribution&amp;page=requirements">Reset</a>
</form></div>

<form id="em-compose" onsubmit="return false">
<input type="hidden" name="csrf" value="<?= em_h($csrf) ?>"><input type="hidden" name="campaign_token" id="em-token" value="<?= em_h($campaignToken) ?>">
<div class="panel scroll"><h4>Open C2C Requirements <small>(<?= number_format($total) ?> found · page <?= $pg ?> of <?= $pages ?>)</small></h4>
<table><thead><tr><th><input type="checkbox" id="em-all" title="Select all on this page"></th><th>ID</th><th>Job Title</th><th>Location</th><th>Client / Skill</th><th>Posted</th><th>Sharing</th><th></th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="8" class="muted">No open C2C requirements match these filters.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): $rid = (int)$r['reqid']; ?>
<tr id="em-row-<?= $rid ?>">
 <td><input class="em-check" type="checkbox" name="reqids[]" value="<?= $rid ?>"></td>
 <td><?= $rid ?></td>
 <td><a href="leads/view.php?id=<?= $rid ?>" target="_blank"><?= em_h($r['job_title']) ?></a> <span class="label label-info">C2C</span></td>
 <td><?= em_h($r['rlocation']) ?></td>
 <td><span class="domain"><?= em_h($r['domain'] ?: '—') ?></span><?php if ($r['companyname']): ?> <span class="muted">(<?= em_h($r['companyname']) ?>)</span><?php endif; ?>
     <div>End client: <b><?= em_h($r['rend_client'] ?: '—') ?></b></div><div class="muted"><?= em_h($r['skillname']) ?></div></td>
 <td><?= em_h($r['datetime'] ? date('m/d/Y g:i A', strtotime($r['datetime'])) : '') ?></td>
 <td class="em-share" data-reqid="<?= $rid ?>"><?= $status[$rid]['html'] ?? '' ?></td>
 <td><button type="button" class="btn btn-default btn-xs em-preview-one" data-reqid="<?= $rid ?>">Preview email</button></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<div style="margin-top:10px"><?php $base = $_GET; unset($base['p']);
for ($n = max(1, $pg - 3); $n <= min($pages, $pg + 3); $n++): $url = 'admin.php?' . http_build_query(array_merge($base, ['action' => 'emaildistribution', 'page' => 'requirements', 'p' => $n])); ?>
<a class="btn btn-<?= $n === $pg ? 'primary' : 'default' ?> btn-sm" href="<?= em_h($url) ?>"><?= $n ?></a><?php endfor; ?></div>
<p class="muted">Selections apply to the current page. Up to 100 requirements per campaign. "Sharing" refreshes automatically while emails are queued.</p>
</div>

<div class="panel"><h4>Compose and queue</h4>
<p><b>One email per requirement × selected list.</b> Use <i>Preview &amp; Queue</i> to see exactly what will be sent before queueing.</p>
<div style="margin-bottom:12px"><b>Send via:</b>
 <label><input type="radio" name="channel" value="email" checked> Email to recipient lists</label>
 <label><input type="radio" name="channel" value="chat" <?= $gchatReady ? '' : 'disabled' ?>> Google Chat only (no email)<?= $gchatReady ? '' : ' <span class="muted">— not set up</span>' ?></label>
</div>
<div id="em-email-only">
<label>Template <select name="template_id" required><?php foreach ($templates as $t): ?><option value="<?= (int)$t['id'] ?>"><?= em_h($t['name']) ?></option><?php endforeach; ?></select></label>
<h5>Recipient lists</h5>
<?php foreach ($lists as $l): ?><label><input type="checkbox" name="listids[]" value="<?= (int)$l['id'] ?>" <?= in_array((int)$l['id'], $defaultLists, true) ? 'checked' : '' ?>> <?= em_h($l['name']) ?></label><?php endforeach; ?>
<details><summary>Optional per-list recipient exceptions</summary><?php foreach ($lists as $l): ?><div style="border-bottom:1px solid #ddd;padding:8px"><b><?= em_h($l['name']) ?></b><p><label>Extra To <input name="list_extra[<?= (int)$l['id'] ?>][to]" placeholder="to@example.com"></label><label>Extra CC <input name="list_extra[<?= (int)$l['id'] ?>][cc]" placeholder="cc@example.com"></label><label>Extra BCC <input name="list_extra[<?= (int)$l['id'] ?>][bcc]" placeholder="bcc@example.com"></label><label>Exclude <input name="list_exclude[<?= (int)$l['id'] ?>]" placeholder="exclude@example.com"></label></p></div><?php endforeach; ?></details>
<details><summary>Additional recipients / exclusions for this campaign</summary>
<div><label>Additional To<textarea name="extra_to" rows="2" cols="40" placeholder="extra@example.com"></textarea></label><label>Additional CC<textarea name="extra_cc" rows="2" cols="40"></textarea></label><label>Additional BCC<textarea name="extra_bcc" rows="2" cols="40"></textarea></label><label>Exclude addresses<textarea name="exclude" rows="2" cols="40" placeholder="excluded@example.com"></textarea></label></div></details>
</div>
<p class="muted" id="em-chat-note" style="display:none">One Google Chat message per selected requirement, using the chat template from SMTP &amp; Google Chat. It goes through the same queue, pause switch, sending window, minimum gap and hourly limit as emails.</p>
<label>Schedule (<?= em_h($settings['timezone_name']) ?>; blank = now) <input type="datetime-local" name="scheduled_at"></label>
<p style="margin-top:10px" id="em-also-chat"><?php if ($gchatReady): ?><label><input type="checkbox" name="post_to_chat" value="1" checked> Also post to Google Chat when sent</label><?php else: ?><span class="muted">Google Chat posting is off<?= $isAdmin ? ' (set it up under SMTP &amp; Google Chat)' : '' ?>.</span><?php endif; ?></p>
<button type="button" class="btn btn-success" id="em-preview-all">Preview &amp; Queue selected</button>
<span class="muted" id="em-selected-count">0 selected</span>
</div>
</form>

<?php elseif ($page === 'queue'):
/* ===================== TODAY'S QUEUE (live) ===================== */ ?>
<div class="panel">
 <h4>Today's Queue <small id="em-q-label"></small></h4>
 <div id="em-q-stats"></div>
 <p class="muted">Shows everything queued or sent today, plus anything still waiting. Refreshes every 15 seconds without reloading the page. Last refresh: <span id="em-q-now">—</span></p>
 <?php if ($isAdmin): ?><button class="btn btn-primary" id="em-run">Run Email Queue Now</button> <span id="em-run-result" class="muted"></span><?php endif; ?>
 <button class="btn btn-default" id="em-q-refresh">Refresh now</button>
</div>
<div class="panel scroll"><table><thead><tr><th>#</th><th>Scheduled</th><th>Req</th><th>Job Title</th><th>Sent To Group</th><th>Recipients</th><th>Status</th><th>Sent</th><th>Chat</th><th>Queued by</th><th></th></tr></thead><tbody id="em-q-body"><tr><td colspan="11" class="muted">Loading…</td></tr></tbody></table></div>

<?php elseif ($page === 'history'):
/* ===================== HISTORY ===================== */
$todayStr = $todayLocal->format('Y-m-d');
$histStatus = (string)($_GET['status'] ?? '');
$histList = (int)($_GET['list_id'] ?? 0);
$histReq = (int)($_GET['reqid'] ?? 0);
$histFrom = (string)($_GET['from'] ?? $todayStr);
$histTo = (string)($_GET['to'] ?? $todayStr);
$w = ['1=1']; $p = [];
if (in_array($histStatus, ['scheduled', 'processing', 'sent', 'failed', 'cancelled'], true)) { $w[] = 'm.status=?'; $p[] = $histStatus; }
if ($histList) { $w[] = 'm.list_id=?'; $p[] = $histList; }
if ($histReq) { $w[] = 'm.reqid=?'; $p[] = $histReq; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $histFrom) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $histTo)) {
    [$hs, $he] = em_period('custom', preg_match('/^\d{4}-\d{2}-\d{2}$/', $histFrom) ? $histFrom : '2000-01-01', preg_match('/^\d{4}-\d{2}-\d{2}$/', $histTo) ? $histTo : $todayStr);
    $w[] = 'm.created_at>=? AND m.created_at<?'; $p[] = $hs; $p[] = $he;
}
$condition = implode(' AND ', $w);
$histPage = max(1, (int)($_GET['p'] ?? 1));
$lim = 100; $offset = ($histPage - 1) * $lim;
$s = $db->prepare("SELECT m.id,m.campaign_id,m.reqid,r.job_title,r.rlocation,m.list_name,m.channel,m.subject,m.recipients_json,m.recipient_count,m.status,m.attempts,m.available_at,m.sent_at,m.last_error,m.created_at,m.chat_status,m.resend_of,c.created_by
    FROM em_messages m JOIN em_campaigns c ON c.id=m.campaign_id LEFT JOIN req r ON r.reqid=m.reqid WHERE $condition ORDER BY m.id DESC LIMIT $lim OFFSET $offset");
$s->execute($p);
$history = $s->fetchAll();
?>
<div class="panel"><h4>Sharing History</h4>
<form method="get" class="em-filters"><input type="hidden" name="action" value="emaildistribution"><input type="hidden" name="page" value="history">
<label>Requirement <input type="number" name="reqid" value="<?= $histReq ?: '' ?>" style="width:110px"></label>
<label>Status <select name="status"><option value="">All</option><?php foreach (['scheduled', 'processing', 'sent', 'failed', 'cancelled'] as $v): ?><option <?= $histStatus === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></label>
<label>List <select name="list_id"><option value="">All</option><?php foreach ($lists as $l): ?><option value="<?= (int)$l['id'] ?>" <?= $histList === (int)$l['id'] ? 'selected' : '' ?>><?= em_h($l['name']) ?></option><?php endforeach; ?></select></label>
<label>From <input type="date" name="from" value="<?= em_h($histFrom) ?>"></label><label>To <input type="date" name="to" value="<?= em_h($histTo) ?>"></label>
<button class="btn btn-primary">Filter</button> <button class="btn btn-success" type="submit" formaction="email_export.php">Export CSV</button>
<a class="btn btn-default" href="admin.php?action=emaildistribution&amp;page=history&amp;from=&amp;to=">All dates</a>
</form></div>
<div class="panel scroll"><table><thead><tr><th>Message</th><th>Queued</th><th>Requirement</th><th>Subject</th><th>Sent To Group</th><th>Recipients</th><th>Status</th><th>Sent</th><th>Chat</th><th></th></tr></thead><tbody>
<?php if (!$history): ?><tr><td colspan="10" class="muted">No emails for these filters.</td></tr><?php endif; ?>
<?php foreach ($history as $h): $rr = json_decode((string)$h['recipients_json'], true) ?: []; ?>
<tr><td>#<?= (int)$h['id'] ?><div class="muted">Campaign <?= (int)$h['campaign_id'] ?><?= $h['resend_of'] ? ' · resend of #' . (int)$h['resend_of'] : '' ?></div></td>
<td><?= em_h(em_local($h['created_at'])) ?></td>
<td><?= (int)$h['reqid'] ?> <?= em_h($h['job_title']) ?><?php if ($h['rlocation']): ?><div class="muted"><?= em_h($h['rlocation']) ?></div><?php endif; ?></td>
<td><?= em_h($h['subject']) ?></td>
<td><?= ($h['channel'] ?? 'email') === 'chat' ? '<span class="label label-primary">Google Chat</span>' : em_h($h['list_name']) ?></td>
<td><?php foreach (['to', 'cc', 'bcc'] as $kind) if (!empty($rr[$kind])) echo '<div class="muted"><b>' . strtoupper($kind) . ':</b> ' . em_h(implode(', ', $rr[$kind])) . '</div>'; ?></td>
<td><span class="label label-<?= ['sent' => 'success', 'failed' => 'danger', 'cancelled' => 'default', 'scheduled' => 'warning', 'processing' => 'info'][$h['status']] ?? 'default' ?>"><?= em_h($h['status']) ?></span><?php if ($h['last_error']): ?><div class="muted"><?= em_h(em_mbcut((string)$h['last_error'], 140)) ?></div><?php endif; ?></td>
<td><?= em_h(em_local($h['sent_at'])) ?></td>
<td><?= em_h((string)$h['chat_status']) ?></td>
<td style="white-space:nowrap"><button type="button" class="btn btn-default btn-xs em-view" data-id="<?= (int)$h['id'] ?>">View <?= ($h['channel'] ?? 'email') === 'chat' ? 'message' : 'email' ?></button>
<?php if (!in_array($h['status'], ['scheduled', 'processing'], true) && ($isAdmin || (int)$h['created_by'] === (int)$u['uid'])): ?><button type="button" class="btn btn-warning btn-xs em-resend" data-id="<?= (int)$h['id'] ?>">Edit &amp; Resend</button><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<p><?php if ($histPage > 1): $prm = $_GET; $prm['p'] = $histPage - 1; ?><a class="btn btn-default" href="admin.php?<?= em_h(http_build_query($prm)) ?>">Previous</a><?php endif; ?>
<?php if (count($history) === $lim): $prm = $_GET; $prm['p'] = $histPage + 1; ?><a class="btn btn-default" href="admin.php?<?= em_h(http_build_query($prm)) ?>">Next</a><?php endif; ?></p></div>

<?php elseif ($page === 'report'):
/* ===================== REPORTS ===================== */
$period = (string)($_GET['period'] ?? 'today');
[$rs, $re, $rlabel, $rfrom, $rto, $period] = em_period($period, (string)($_GET['from'] ?? ''), (string)($_GET['to'] ?? ''));
$rep = em_report_rows($u, $rs, $re, true);
$byClient = [];
$uniqueReqs = [];
foreach ($rep as $r) {
    $k = strtolower((string)$r['domain']) ?: '(no client)';
    if (!isset($byClient[$k])) $byClient[$k] = ['domain' => $r['domain'] ?: '(no client)', 'company' => $r['companyname'], 'reqs' => [], 'emails' => 0];
    $byClient[$k]['reqs'][$r['reqid']] = true;
    $byClient[$k]['emails']++;
    $uniqueReqs[$r['reqid']] = true;
}
uasort($byClient, fn($a, $b) => count($b['reqs']) <=> count($a['reqs']));
$dl = 'email_report_export.php?' . http_build_query(['period' => $period, 'from' => $rfrom, 'to' => $rto]);
?>
<div class="panel"><h4>Shared Requirements Report</h4>
<form method="get" class="em-filters"><input type="hidden" name="action" value="emaildistribution"><input type="hidden" name="page" value="report">
<?php foreach (['today' => 'Today', 'week' => 'Last 7 days', 'month' => 'Last 30 days', 'thismonth' => 'This month'] as $k => $v): ?><a class="btn btn-<?= $period === $k ? 'primary' : 'default' ?>" href="admin.php?action=emaildistribution&amp;page=report&amp;period=<?= $k ?>"><?= $v ?></a><?php endforeach; ?>
<input type="hidden" name="period" value="custom">
<label>From <input type="date" name="from" value="<?= em_h($rfrom) ?>"></label><label>To <input type="date" name="to" value="<?= em_h($rto) ?>"></label>
<button class="btn btn-<?= $period === 'custom' ? 'primary' : 'default' ?>">Custom range</button>
<a class="btn btn-success" href="<?= em_h($dl) ?>">Download <?= ['today' => "today's", 'week' => 'weekly', 'month' => 'monthly', 'thismonth' => 'this month\'s'][$period] ?? 'selected range' ?> report (CSV)</a>
</form>
<p style="margin-top:12px"><b><?= em_h($rlabel) ?></b></p>
<div class="em-stat">Emails sent<b><?= number_format(count($rep)) ?></b></div><div class="em-stat">Requirements shared<b><?= number_format(count($uniqueReqs)) ?></b></div><div class="em-stat">Clients<b><?= number_format(count($byClient)) ?></b></div>
</div>
<div class="panel scroll"><h4>By client</h4><table><thead><tr><th>Client domain</th><th>Company</th><th>Requirements shared</th><th>Emails sent</th></tr></thead><tbody>
<?php if (!$byClient): ?><tr><td colspan="4" class="muted">Nothing was shared in this period.</td></tr><?php endif; ?>
<?php foreach ($byClient as $c): ?><tr><td class="domain"><?= em_h($c['domain']) ?></td><td><?= em_h($c['company']) ?></td><td><?= count($c['reqs']) ?></td><td><?= (int)$c['emails'] ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<div class="panel scroll"><h4>Details</h4><table><thead><tr><th>Date shared</th><th>Job Title</th><th>Location</th><th>Client</th><th>Recruiter email</th><th>End client</th><th>Sent to group</th><th>Job description</th></tr></thead><tbody>
<?php foreach (array_slice($rep, 0, 1000) as $r): $jd = em_job_description_text((string)$r['rdesc']); ?>
<tr><td style="white-space:nowrap"><?= em_h(em_local($r['sent_at'])) ?></td><td><?= (int)$r['reqid'] ?> · <?= em_h($r['job_title']) ?></td><td><?= em_h($r['rlocation']) ?></td><td class="domain"><?= em_h($r['domain']) ?></td><td><?= em_h($r['remail']) ?></td><td><?= em_h($r['rend_client']) ?></td><td><?= em_h($r['list_name']) ?></td>
<td><details><summary><?= em_h(em_mbcut($jd, 70)) ?><?= strlen($jd) > 70 ? '…' : '' ?></summary><div class="em-pre"><?= em_h($jd) ?></div></details></td></tr>
<?php endforeach; ?>
</tbody></table><?php if (count($rep) > 1000): ?><p class="muted">Showing the first 1,000 rows. The download contains all <?= number_format(count($rep)) ?>.</p><?php endif; ?></div>

<?php elseif ($page === 'logs'):
/* ===================== LOGS ===================== */
$lp = max(1, (int)($_GET['p'] ?? 1));
$logs = $db->query('SELECT l.*,u.name FROM em_logs l LEFT JOIN users u ON u.uid=l.actor_uid ORDER BY l.id DESC LIMIT 101 OFFSET ' . (($lp - 1) * 100))->fetchAll();
$more = count($logs) > 100; $logs = array_slice($logs, 0, 100);
?>
<div class="panel scroll"><h4>Audit and Delivery Logs</h4><table><thead><tr><th>Time</th><th>Event</th><th>Campaign</th><th>Message</th><th>User</th><th>Detail</th></tr></thead><tbody>
<?php foreach ($logs as $l): ?><tr><td style="white-space:nowrap"><?= em_h(em_local($l['created_at'])) ?></td><td><?= em_h($l['event']) ?></td><td><?= em_h($l['campaign_id']) ?></td><td><?= em_h($l['message_id']) ?></td><td><?= em_h($l['name']) ?></td><td><?= em_h($l['detail']) ?></td></tr><?php endforeach; ?>
</tbody></table>
<p><?php if ($lp > 1): ?><a class="btn btn-default" href="admin.php?action=emaildistribution&amp;page=logs&amp;p=<?= $lp - 1 ?>">Newer</a><?php endif; ?><?php if ($more): ?><a class="btn btn-default" href="admin.php?action=emaildistribution&amp;page=logs&amp;p=<?= $lp + 1 ?>">Older</a><?php endif; ?></p></div>

<?php elseif ($page === 'settings'):
/* ===================== SETTINGS (SMTP + Google Chat) ===================== */ ?>
<form method="post" id="em-settings-form"><input type="hidden" name="csrf" value="<?= em_h($csrf) ?>"><input type="hidden" name="op" value="settings"><input type="hidden" name="gchat_webhook_x" id="em-gchat-webhook-x"><input type="hidden" name="gchat_template_x" id="em-gchat-template-x">
<div class="panel"><h4>Gmail SMTP and Rate Limits</h4>
<p><label>Gmail address <input type="email" name="smtp_email" required value="<?= em_h($settings['smtp_email']) ?>"></label><label>From name <input name="smtp_from_name" value="<?= em_h($settings['smtp_from_name']) ?>"></label></p>
<p><label>Gmail App Password <input type="password" name="smtp_password" autocomplete="new-password" placeholder="Leave blank to keep current"></label></p>
<p><label>Messages / rolling hour <input type="number" name="hourly_limit" min="1" max="100" value="<?= (int)$settings['hourly_limit'] ?>"></label><label>Recipients / rolling 24h <input type="number" name="daily_recipient_limit" min="1" max="1000" value="<?= (int)$settings['daily_recipient_limit'] ?>"></label><label>Minimum gap (seconds) <input type="number" name="min_gap_seconds" min="0" max="3600" value="<?= (int)$settings['min_gap_seconds'] ?>"></label></p>
<p><label>Timezone <input name="timezone_name" value="<?= em_h($settings['timezone_name']) ?>"></label><label><input type="checkbox" name="queue_paused" <?= $settings['queue_paused'] ? 'checked' : '' ?>> Pause queue</label></p>
<p><label>Sending window start (optional) <input type="time" name="send_start" value="<?= em_h($settings['send_start']) ?>"></label><label>Sending window end (optional) <input type="time" name="send_end" value="<?= em_h($settings['send_end']) ?>"></label></p>
<p class="muted">Select default lists to preselect on the compose screen. Each selected list receives its own email. List members retain their To/CC/BCC roles.</p>
<?php foreach (['default_to_list' => 'Default To List', 'default_cc_list' => 'Default CC List', 'default_bcc_list' => 'Default BCC List'] as $key => $label): ?><label><?= $label ?> <select name="<?= $key ?>"><option value="">None</option><?php foreach ($lists as $l): ?><option value="<?= (int)$l['id'] ?>" <?= (int)$settings[$key] === (int)$l['id'] ? 'selected' : '' ?>><?= em_h($l['name']) ?></option><?php endforeach; ?></select></label><?php endforeach; ?>
<p class="muted">Gmail STARTTLS port 587. SMTP passwords are encrypted using EMAIL_SECRET_KEY.</p>
</div>
<div class="panel"><h4>Google Chat (webhook)</h4>
<p class="muted">In Google Chat: open the space → <i>Apps &amp; integrations</i> → <i>Webhooks</i> → add a webhook and paste its URL here. When enabled, a message is posted to the space once per requirement when its email is sent (if "Also post to Google Chat" was ticked when queueing).</p>
<p><label><input type="checkbox" name="gchat_enabled" <?= !empty($settings['gchat_enabled']) ? 'checked' : '' ?>> Enable Google Chat posting</label></p>
<p><label style="width:100%">Webhook URL <input type="password" id="em-gchat-webhook" style="width:100%" autocomplete="off" placeholder="<?= !empty($settings['gchat_webhook_enc']) ? 'Saved (encrypted). Leave blank to keep it.' : 'https://chat.googleapis.com/v1/spaces/…/messages?key=…&token=…' ?>"></label>
<?php if (!empty($settings['gchat_webhook_enc'])): ?><label><input type="checkbox" name="gchat_clear"> Remove saved webhook</label><?php endif; ?></p>
<p><label style="width:100%">Chat message template<textarea id="em-gchat-template" rows="7"><?= em_h(($settings['gchat_template'] ?? '') !== '' ? $settings['gchat_template'] : EM_GCHAT_DEFAULT_TEMPLATE) ?></textarea></label></p>
<p class="muted">Placeholders: {{req_id}}, {{job_title}}, {{location}}, {{employment_type}}, {{client}}, {{domain}}, {{end_client}}, {{recruiter_email}}, {{list_name}}, {{date}}, {{job_description}}. Google Chat formatting: *bold*, _italic_.</p>
<?php if (!empty($settings['gchat_webhook_enc'])): ?><button type="button" class="btn btn-default" id="em-gchat-test">Send test message</button> <span id="em-gchat-result" class="muted"></span><?php endif; ?>
</div>
<p><button class="btn btn-primary">Save Settings</button></p>
</form>

<?php elseif ($page === 'lists' || $page === 'templates'):
/* ===================== LISTS / TEMPLATES (unchanged) ===================== */
require __DIR__ . '/email_distribution_admin.php';
endif; ?>
</div></div><!-- /.em-main -->

<!-- Shared modal + toast -->
<div class="em-modal-bg" id="em-modal-bg"><div class="em-modal" role="dialog" aria-modal="true" aria-labelledby="em-modal-title">
 <div class="em-modal-h"><h4 id="em-modal-title">Preview</h4><button type="button" class="em-x" id="em-modal-x" aria-label="Close">&times;</button></div>
 <div class="em-modal-b" id="em-modal-body"></div>
 <div class="em-modal-f" id="em-modal-foot"></div>
</div></div>
<div class="em-toast" id="em-toast"></div>

<script>
(function () {
  var CSRF = <?= json_encode($csrf) ?>, PAGE = <?= json_encode($page) ?>;
  var $ = function (s, r) { return (r || document).querySelector(s); }, $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }
  function toast(msg, err) { var t = $('#em-toast'); t.textContent = msg; t.className = 'em-toast' + (err ? ' err' : ''); t.style.display = 'block'; clearTimeout(t._h); t._h = setTimeout(function () { t.style.display = 'none'; }, err ? 8000 : 5000); }
  function api(op, data, method) {
    method = method || 'POST';
    var url = 'email_ajax.php?op=' + encodeURIComponent(op), opts = { method: method, credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
    if (method === 'POST') { var fd = data instanceof FormData ? data : new FormData(); if (!(data instanceof FormData) && data) Object.keys(data).forEach(function (k) { [].concat(data[k]).forEach(function (v) { fd.append(k, v); }); }); fd.append('csrf', CSRF); fd.set('op', op); opts.body = fd; }
    else if (data) { var sp = new URLSearchParams(); Object.keys(data).forEach(function (k) { [].concat(data[k]).forEach(function (v) { sp.append(k, v); }); }); url += '&' + sp.toString(); }
    return fetch(url, opts).then(function (r) { return r.json().catch(function () { throw new Error('Server error (' + r.status + ')'); }); })
      .then(function (j) { if (!j.ok) throw new Error(j.error || 'Request failed'); return j; });
  }
  var bg = $('#em-modal-bg');
  function openModal(title, html, foot) { $('#em-modal-title').textContent = title; $('#em-modal-body').innerHTML = html; $('#em-modal-foot').innerHTML = foot || ''; bg.style.display = 'flex'; }
  function closeModal() { bg.style.display = 'none'; }
  $('#em-modal-x').addEventListener('click', closeModal);
  bg.addEventListener('click', function (e) { if (e.target === bg) closeModal(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
  function mailHtml(m) {
    if (m.channel === 'chat') return '<div class="em-mail"><div class="em-mail-h"><div><b>Google Chat</b> message (no email)</div><div class="muted">Req #' + esc(m.reqid) + '</div></div><div class="em-mail-b">' + esc(m.body) + '</div></div>';
    var rc = m.recipients || { to: m.to, cc: m.cc, bcc: m.bcc };
    return '<div class="em-mail"><div class="em-mail-h"><div><b>To:</b> ' + esc((rc.to || []).join(', ')) + '</div>' +
      ((rc.cc || []).length ? '<div><b>CC:</b> ' + esc(rc.cc.join(', ')) + '</div>' : '') + ((rc.bcc || []).length ? '<div><b>BCC:</b> ' + esc(rc.bcc.join(', ')) + '</div>' : '') +
      '<div><b>Subject:</b> ' + esc(m.subject) + '</div><div class="muted">Req #' + esc(m.reqid) + ' · group: ' + esc(m.list_name) + '</div></div>' +
      '<div class="em-mail-b">' + esc(m.body) + '</div></div>';
  }

  /* ---------- Requirements page ---------- */
  if (PAGE === 'requirements' && $('#em-compose')) {
    var form = $('#em-compose');
    var updateCount = function () { $('#em-selected-count').textContent = $$('.em-check:checked').length + ' selected'; };
    $('#em-all').addEventListener('change', function () { var c = this.checked; $$('.em-check').forEach(function (x) { x.checked = c; }); updateCount(); });
    $$('.em-check').forEach(function (x) { x.addEventListener('change', updateCount); });
    var watch = {}, timer = null;
    function applyStatus(st) {
      Object.keys(st).forEach(function (id) {
        var cell = $('.em-share[data-reqid="' + id + '"]'); if (!cell) return;
        if (cell.innerHTML !== st[id].html) { cell.innerHTML = st[id].html; var tr = cell.parentNode; tr.classList.add('em-updated'); setTimeout(function () { tr.classList.remove('em-updated'); }, 1500); }
        if (st[id].queued_count > 0) watch[id] = true; else delete watch[id];
      });
    }
    function poll() {
      var ids = Object.keys(watch); if (!ids.length) { timer = null; return; }
      api('status', { 'reqids[]': ids }, 'GET').then(function (j) { applyStatus(j.status); }).catch(function () {}).then(function () { timer = setTimeout(poll, 10000); });
    }
    function startPoll() { if (!timer) timer = setTimeout(poll, 8000); }
    $$('.em-share').forEach(function (c) { if (/Queued:/.test(c.textContent)) watch[c.getAttribute('data-reqid')] = true; }); startPoll();

    function composeData(reqids) {
      var fd = new FormData(form); fd.delete('reqids[]'); reqids.forEach(function (id) { fd.append('reqids[]', id); }); return fd;
    }
    var channel = function () { var c = $('[name="channel"]:checked', form); return c ? c.value : 'email'; };
    var syncChannel = function () { var chat = channel() === 'chat'; $('#em-email-only').style.display = chat ? 'none' : ''; $('#em-chat-note').style.display = chat ? '' : 'none'; $('#em-also-chat').style.display = chat ? 'none' : ''; };
    $$('[name="channel"]', form).forEach(function (r) { r.addEventListener('change', syncChannel); }); syncChannel();
    function preview(reqids, allowQueue) {
      if (!reqids.length) { toast('Select at least one requirement', true); return; }
      if (channel() === 'email' && !$$('[name="listids[]"]:checked', form).length) { toast('Select at least one recipient list', true); return; }
      openModal('Email preview', '<p class="muted">Building preview…</p>');
      api('preview', composeData(reqids)).then(function (j) {
        var n = j.messages.length;
        var isChat = j.channel === 'chat', noun = isChat ? 'Google Chat message' : 'email';
        var head = isChat ? '<p><b>' + n + ' ' + noun + (n === 1 ? '' : 's') + '</b> will be posted to the Google Chat space (no email). Scheduled: <b>' + esc(j.scheduled) + '</b>.</p>'
                          : '<p><b>' + n + ' email' + (n === 1 ? '' : 's') + '</b> will be sent (' + reqids.length + ' requirement' + (reqids.length === 1 ? '' : 's') + ' × selected lists). Scheduled: <b>' + esc(j.scheduled) + '</b>.</p>';
        var body = head + j.messages.slice(0, 50).map(mailHtml).join('') + (n > 50 ? '<p class="muted">… and ' + (n - 50) + ' more with the same template.</p>' : '');
        var foot = '<button type="button" class="btn btn-default" id="em-m-cancel">Close</button>' + (allowQueue ? ' <button type="button" class="btn btn-success" id="em-m-queue">Queue ' + n + ' ' + noun + (n === 1 ? '' : 's') + '</button>' : '');
        openModal(isChat ? 'Google Chat preview' : 'Email preview', body, foot);
        $('#em-m-cancel').onclick = closeModal;
        if (allowQueue) $('#em-m-queue').onclick = function () {
          var b = this; b.disabled = true; b.textContent = 'Queueing…';
          api('queue', composeData(reqids)).then(function (q) {
            $('#em-token').value = q.token; closeModal();
            toast('Campaign #' + q.campaign_id + ' queued: ' + q.count + (q.channel === 'chat' ? ' Google Chat message(s)' : ' email(s)') + ', scheduled ' + q.scheduled + '.');
            applyStatus(q.status); $$('.em-check').forEach(function (x) { x.checked = false; }); $('#em-all').checked = false; updateCount(); startPoll();
          }).catch(function (e) { b.disabled = false; b.textContent = 'Queue'; toast(e.message, true); });
        };
      }).catch(function (e) { openModal('Email preview', '<div class="alert alert-danger">' + esc(e.message) + '</div>', '<button type="button" class="btn btn-default" onclick="document.getElementById(\'em-modal-bg\').style.display=\'none\'">Close</button>'); });
    }
    $('#em-preview-all').addEventListener('click', function () { preview($$('.em-check:checked').map(function (x) { return x.value; }), true); });
    $$('.em-preview-one').forEach(function (b) { b.addEventListener('click', function () { preview([b.getAttribute('data-reqid')], true); }); });
  }

  /* ---------- Today's queue ---------- */
  if (PAGE === 'queue') {
    var badge = { sent: 'success', failed: 'danger', cancelled: 'default', scheduled: 'warning', processing: 'info' };
    var load = function () {
      api('today_queue', null, 'GET').then(function (j) {
        $('#em-q-label').textContent = '— ' + j.label; $('#em-q-now').textContent = j.now;
        $('#em-q-stats').innerHTML = Object.keys(j.stats).map(function (k) { return '<span class="em-stat">' + esc(k) + '<b>' + j.stats[k] + '</b></span>'; }).join('') +
          '<p>Queue: <b>' + (j.paused ? 'PAUSED' : 'ACTIVE') + '</b> · limit ' + j.hourly + ' emails/hour, ' + j.daily + ' recipients/day.</p>';
        $('#em-q-body').innerHTML = j.rows.length ? j.rows.map(function (m) {
          return '<tr><td>' + m.id + '</td><td style="white-space:nowrap">' + esc(m.scheduled) + '</td><td>' + m.reqid + '</td><td>' + esc(m.job_title) + '</td><td>' + (m.channel === 'chat' ? '<span class="label label-primary">Google Chat</span>' : esc(m.list_name)) + '</td><td>' + (m.channel === 'chat' ? '—' : m.recipients) +
            '</td><td><span class="label label-' + (badge[m.status] || 'default') + '">' + esc(m.status) + '</span>' + (m.error ? '<div class="muted">' + esc(m.error.slice(0, 140)) + '</div>' : '') +
            '</td><td style="white-space:nowrap">' + esc(m.sent) + '</td><td>' + esc(m.chat) + '</td><td>' + esc(m.by) + '</td><td>' +
            (m.can_cancel ? '<button class="btn btn-danger btn-xs em-q-cancel" data-id="' + m.id + '">Cancel</button>' : '') + '</td></tr>';
        }).join('') : '<tr><td colspan="11" class="muted">Nothing queued or sent today.</td></tr>';
        $$('.em-q-cancel').forEach(function (b) { b.onclick = function () { if (!confirm('Cancel this email?')) return; api('cancel', { message_id: b.getAttribute('data-id') }).then(function () { toast('Cancelled'); load(); }).catch(function (e) { toast(e.message, true); }); }; });
      }).catch(function (e) { toast(e.message, true); });
    };
    load(); setInterval(load, 15000);
    $('#em-q-refresh').onclick = load;
    if ($('#em-run')) $('#em-run').onclick = function () { var b = this; b.disabled = true; $('#em-run-result').textContent = 'Running…'; api('run_worker', {}).then(function (j) { $('#em-run-result').textContent = j.result; load(); }).catch(function (e) { $('#em-run-result').textContent = e.message; }).then(function () { b.disabled = false; }); };
  }

  /* ---------- History: view + edit & resend ---------- */
  if (PAGE === 'history') {
    $$('.em-view').forEach(function (b) { b.onclick = function () {
      openModal('Email #' + b.getAttribute('data-id'), '<p class="muted">Loading…</p>');
      api('message', { id: b.getAttribute('data-id') }, 'GET').then(function (j) { var m = j.message;
        openModal('Email #' + m.id + ' — ' + m.status, '<p class="muted">Queued for ' + esc(m.scheduled) + (m.sent ? ' · sent ' + esc(m.sent) : '') + (m.resend_of ? ' · resend of #' + m.resend_of : '') + (m.chat_status ? ' · Google Chat: ' + esc(m.chat_status) : '') + '</p>' + (m.error ? '<div class="alert alert-danger">' + esc(m.error) + '</div>' : '') + mailHtml(m),
          '<button type="button" class="btn btn-default" id="em-m-close">Close</button>');
        $('#em-m-close').onclick = closeModal;
      }).catch(function (e) { toast(e.message, true); closeModal(); });
    }; });
    $$('.em-resend').forEach(function (b) { b.onclick = function () {
      api('message', { id: b.getAttribute('data-id') }, 'GET').then(function (j) { var m = j.message;
        var hide = m.channel === 'chat' ? ' style="display:none"' : '';
        var html = '<p class="muted">Correct anything below, check the preview, then queue a new copy. The original stays in history.</p>' +
          '<div' + hide + '><label style="width:100%">To<input id="rs-to" style="width:100%" value="' + esc(m.to.join(', ')) + '"></label>' +
          '<label style="width:100%">CC<input id="rs-cc" style="width:100%" value="' + esc(m.cc.join(', ')) + '"></label>' +
          '<label style="width:100%">BCC<input id="rs-bcc" style="width:100%" value="' + esc(m.bcc.join(', ')) + '"></label>' +
          '<label style="width:100%">Subject<input id="rs-subject" style="width:100%" value="' + esc(m.subject) + '"></label></div>' +
          '<label style="width:100%">' + (m.channel === 'chat' ? 'Google Chat message' : 'Body') + '<textarea id="rs-body" rows="12" style="width:100%">' + esc(m.body) + '</textarea></label>' +
          (m.channel === 'chat' ? '' : <?= json_encode($gchatReady ? '<label><input type="checkbox" id="rs-chat"> Also post to Google Chat when sent</label>' : '') ?>) +
          '<h5>Preview</h5><div id="rs-preview"></div>';
        openModal('Edit & resend email #' + m.id, html, '<button type="button" class="btn btn-default" id="em-m-close">Cancel</button> <button type="button" class="btn btn-warning" id="rs-go">Queue corrected email</button>');
        var pv = function () { $('#rs-preview').innerHTML = mailHtml({ to: $('#rs-to').value.split(/[,;]+/).map(function (s) { return s.trim(); }).filter(Boolean), cc: $('#rs-cc').value.split(/[,;]+/).map(function (s) { return s.trim(); }).filter(Boolean), bcc: $('#rs-bcc').value.split(/[,;]+/).map(function (s) { return s.trim(); }).filter(Boolean), subject: $('#rs-subject').value, body: $('#rs-body').value, reqid: m.reqid, list_name: m.list_name, channel: m.channel }); };
        ['#rs-to', '#rs-cc', '#rs-bcc', '#rs-subject', '#rs-body'].forEach(function (s) { $(s).addEventListener('input', pv); }); pv();
        $('#em-m-close').onclick = closeModal;
        $('#rs-go').onclick = function () { var g = this; g.disabled = true;
          api('resend', { id: m.id, to: $('#rs-to').value, cc: $('#rs-cc').value, bcc: $('#rs-bcc').value, subject: $('#rs-subject').value, body: $('#rs-body').value, post_to_chat: ($('#rs-chat') && $('#rs-chat').checked) ? 1 : '' })
            .then(function (r) { closeModal(); toast('Corrected ' + (m.channel === 'chat' ? 'Google Chat message' : 'email') + ' queued as #' + r.id + '. See Today\'s Queue.'); }).catch(function (e) { g.disabled = false; toast(e.message, true); });
        };
      }).catch(function (e) { toast(e.message, true); });
    }; });
  }

  /* ---------- Settings: send webhook + template encoded ----------
     Bluehost's Mod_Security rejects form posts that contain a raw https:// URL with ?key=&token=.
     The values are sent base64-encoded (and reversed) and decoded on the server. */
  if (PAGE === 'settings' && $('#em-settings-form')) {
    var enc = function (v) { var b = btoa(unescape(encodeURIComponent(v || ''))); return b.split('').reverse().join(''); };
    $('#em-settings-form').addEventListener('submit', function () {
      $('#em-gchat-webhook-x').value = enc($('#em-gchat-webhook').value.trim());
      $('#em-gchat-template-x').value = enc($('#em-gchat-template').value);
    });
  }
  /* ---------- Settings: Google Chat test ---------- */
  if (PAGE === 'settings' && $('#em-gchat-test')) {
    $('#em-gchat-test').onclick = function () { var b = this; b.disabled = true; $('#em-gchat-result').textContent = 'Sending…';
      api('gchat_test', {}).then(function (j) { $('#em-gchat-result').textContent = j.result; }).catch(function (e) { $('#em-gchat-result').textContent = e.message; }).then(function () { b.disabled = false; }); };
  }
})();
</script>
