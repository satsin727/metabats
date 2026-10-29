<?php require_once __DIR__.'/email_lib.php';
$u = em_user();
$db = em_db();
$page = (string)($_GET['page'] ?? 'requirements');
$pages = ['requirements', 'lists', 'templates', 'settings', 'history', 'queue'];
if (!in_array($page, $pages, true))$page = 'requirements';
$adminPages = ['lists', 'templates', 'settings'];
if (in_array($page, $adminPages, true))em_admin($u);
$notice = '';
$error = '';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        em_require_post();
        $op = (string)($_POST['op'] ?? '');
        if ($op === 'settings') {
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
        if ($op === 'campaign') {
            $token = (string)($_POST['campaign_token'] ?? '');
            if (!$token || !hash_equals((string)($_SESSION['em_campaign_token'] ?? ''), $token))throw new RuntimeException('Campaign already submitted or page expired. Refresh and try again');
            $reqids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['reqids'] ?? [])))));
            $listids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['listids'] ?? [])))));
            if (!$reqids || !$listids || count($reqids) > 100 || count($listids) > 20)throw new RuntimeException('Select 1–100 requirements and 1–20 lists');
            $templateId = (int)($_POST['template_id'] ?? 0);
            $q = $db->prepare('SELECT * FROM em_templates WHERE id=? AND active=1');
            $q->execute([$templateId]);
            $tpl = $q->fetch();
            if (!$tpl)throw new RuntimeException('Select a valid template');
            $extra = ['to' => em_addresses((string)($_POST['extra_to'] ?? '')), 'cc' => em_addresses((string)($_POST['extra_cc'] ?? '')), 'bcc' => em_addresses((string)($_POST['extra_bcc'] ?? ''))];
            $excluded = array_fill_keys(array_map('strtolower', em_addresses((string)($_POST['exclude'] ?? ''))), true);
            $when = trim((string)($_POST['scheduled_at'] ?? ''));
            $settings = em_settings();
            $tz = new DateTimeZone($settings['timezone_name']);
            $scheduled = $when !== '' ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $when, $tz): new DateTimeImmutable('now', $tz);
            $minimumSchedule = (new DateTimeImmutable('now', $tz))->modify('-2 minutes');
            if (!$scheduled || $scheduled < $minimumSchedule)throw new RuntimeException('Invalid or past schedule');
            // DB times are UTC for consistent worker processing.
            $utc = $scheduled->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $q = $db->prepare('SELECT id,name FROM em_lists WHERE id=? AND active=1');
            $m = $db->prepare("SELECT c.email,lm.recipient_type FROM em_list_members lm JOIN em_contacts c ON c.id=lm.contact_id AND c.active=1 WHERE lm.list_id=?");
            $payload = [];
            foreach ($listids as $lid) {
                $q->execute([$lid]);
                $list = $q->fetch();
                if (!$list)throw new RuntimeException('Invalid recipient list '.$lid);
                $m->execute([$lid]);
                $groups = ['to' => $extra['to'], 'cc' => $extra['cc'], 'bcc' => $extra['bcc']];
                foreach (['to', 'cc', 'bcc'] as $kind)$groups[$kind] = array_merge($groups[$kind], em_addresses((string)($_POST['list_extra'][$lid][$kind] ?? '')));
                $listExcluded = $excluded + array_fill_keys(array_map('strtolower', em_addresses((string)($_POST['list_exclude'][$lid] ?? ''))), true);
                foreach ($m as $member)$groups[$member['recipient_type']][] = $member['email'];
                $rec = em_recipient_map($groups);
                foreach ($rec as $role => $addresses)$rec[$role] = array_values(array_filter($addresses, fn($v) => !isset($listExcluded[strtolower($v)])));
                if (!$rec['to'])throw new RuntimeException('List '.$list['name'].' has no To recipient after exclusions');
                $total = array_sum(array_map('count', $rec));
                if ($total > 100)throw new RuntimeException('Recipient count exceeds 100 per message');
                $payload[] = ['list' => $list, 'recipients' => $rec, 'count' => $total];
            }
            $reqs = [];
            foreach ($reqids as $id) {
                $r = em_req($id, $u);
                if (!$r)throw new RuntimeException('Requirement '.$id.' is unavailable');
                $reqs[] = $r;
            }
            if (count($reqs) * count($payload) > 500)throw new RuntimeException('Campaign too large');
            $db->beginTransaction();
            try {
                $db->prepare('INSERT INTO em_campaigns(created_by,template_id,scheduled_at) VALUES(?,?,?)')->execute([(int)$u['uid'], $templateId, $utc]);
                $camp = (int)$db->lastInsertId();
                $insert = $db->prepare('INSERT INTO em_messages(campaign_id,reqid,list_id,list_name,subject,body,recipients_json,recipient_count,available_at) VALUES(?,?,?,?,?,?,?,?,?)');
                foreach ($reqs as $r) {
                    $rep = em_replacements($r);
                    foreach ($payload as $p) {
                        $insert->execute([$camp, $r['reqid'], $p['list']['id'], $p['list']['name'], em_mbcut(em_render($tpl['subject'], $rep), 255), em_render($tpl['body'], $rep), json_encode($p['recipients'], JSON_THROW_ON_ERROR), $p['count'], $utc]);
                    }
                }
                em_log('campaign_created', 'Requirements '.count($reqs).', lists '.count($payload).', messages '.(count($reqs) * count($payload)), null, $camp, (int)$u['uid']);
                $db->commit();
            }
            catch (Throwable $e) {
                $db->rollBack();
                throw $e;
            }
            unset($_SESSION['em_campaign_token']);
            em_redirect('queue', 'Campaign #'.$camp.' queued ('.(count($reqs) * count($payload)).' messages)');
        }
        if ($op === 'cancel') {
            $camp = (int)($_POST['campaign_id'] ?? 0);
            $q = $db->prepare('SELECT created_by FROM em_campaigns WHERE id=?');
            $q->execute([$camp]);
            $c = $q->fetch();
            if (!$c || ((int)$u['level'] !== 1 && (int)$c['created_by'] !== (int)$u['uid']))throw new RuntimeException('Not authorized');
            $db->beginTransaction();
            $db->prepare("UPDATE em_messages SET status='cancelled' WHERE campaign_id=? AND status='scheduled'")->execute([$camp]);
            $left = $db->prepare("SELECT COUNT(*) FROM em_messages WHERE campaign_id=? AND status IN ('sent','processing','failed')");
            $left->execute([$camp]);
            if ((int)$left->fetchColumn() === 0)$db->prepare("UPDATE em_campaigns SET status='cancelled' WHERE id=?")->execute([$camp]);
            em_log('campaign_cancelled', 'Pending messages cancelled', null, $camp, (int)$u['uid']);
            $db->commit();
            em_redirect('queue', 'Pending messages cancelled');
        }
        throw new RuntimeException('Unknown action');
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
?><style>.em-wrap{max-width:1300px;margin:25px auto;padding:18px}.em-wrap .panel{border:1px solid #ddd;border-radius:8px;padding:18px;margin:14px 0;background:#fff}.em-wrap input:not([type=checkbox]),.em-wrap select,.em-wrap textarea{max-width:100%;padding:7px;border:1px solid #ccc;border-radius:4px}.em-wrap label{margin:8px 12px 8px 0;display:inline-block}.em-wrap .btn{margin:3px}.em-wrap table{width:100%;border-collapse:collapse}.em-wrap td,.em-wrap th{padding:7px;border-bottom:1px solid #ddd;text-align:left}.em-wrap .muted{color:#666}.em-wrap .scroll{overflow:auto}.em-wrap .tag{background:#eee;padding:2px 6px;border-radius:5px}</style>
<div class="em-wrap"><h2>Requirement Email Distribution</h2>
<nav><a class="btn btn-default" href="admin.php?action=emaildistribution&page=requirements">Requirements</a><a class="btn btn-default" href="admin.php?action=emaildistribution&page=queue">Queue</a><a class="btn btn-default" href="admin.php?action=emaildistribution&page=history">History / Export</a><?php if ((int)$u['level'] === 1): ?><a class="btn btn-default" href="admin.php?action=emaildistribution&page=lists">Recipient Lists</a><a class="btn btn-default" href="admin.php?action=emaildistribution&page=templates">Templates</a><a class="btn btn-default" href="admin.php?action=emaildistribution&page=settings">SMTP Settings</a><?php endif;
?></nav>
<?php if (isset($_GET['notice'])): ?><div class="alert alert-success"><?= em_h($_GET['notice']) ?></div><?php endif;
?><?php if ($error): ?><div class="alert alert-danger"><?= em_h($error) ?></div><?php endif;
?><?php if ($page === 'requirements'): $type = (string)($_GET['type'] ?? '');
$client = (int)($_GET['client'] ?? 0);
$skill = (int)($_GET['skill'] ?? 0);
$from = (string)($_GET['from'] ?? '');
$to = (string)($_GET['to'] ?? '');
$share = (string)($_GET['share'] ?? '');
$term = trim((string)($_GET['q'] ?? ''));
$pg = max(1, (int)($_GET['p'] ?? 1));
$per = 40;
$where = ['r.status=1'];
$params = [];
if (in_array($type, ['C2C', 'W2', 'FTE', 'C2H'], true)) {
    $where[] = 'r.emp_type=?';
    $params[] = $type;
}
if ($client) {
    $where[] = 'r.cid=?';
    $params[] = $client;
}
if ($skill) {
    $where[] = 'r.skillid=?';
    $params[] = $skill;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $where[] = 'r.datetime>=?';
    $params[] = $from.' 00:00:00';
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $where[] = 'r.datetime<?';
    $params[] = date('Y-m-d', strtotime($to.' +1 day')).' 00:00:00';
}
if ($term !== '') {
    $where[] = '(r.job_title LIKE ? OR r.rlocation LIKE ? OR c.companyname LIKE ?)';
    foreach (range(1, 3) as $i)$params[] = '%'.$term.'%';
}
if ($share === 'not_shared')$where[] = "NOT EXISTS(SELECT 1 FROM em_messages x WHERE x.reqid=r.reqid AND x.status IN ('scheduled','processing','sent'))";
if ($share === 'sent')$where[] = "EXISTS(SELECT 1 FROM em_messages x WHERE x.reqid=r.reqid AND x.status='sent')";
if ($share === 'scheduled')$where[] = "EXISTS(SELECT 1 FROM em_messages x WHERE x.reqid=r.reqid AND x.status IN ('scheduled','processing'))";
$cond = implode(' AND ', $where);
$count = $db->prepare("SELECT COUNT(*) FROM req r LEFT JOIN clients c ON c.cid=r.cid WHERE $cond");
$count->execute($params);
$total = (int)$count->fetchColumn();
$offset = ($pg - 1) * $per;
$query = "SELECT r.reqid,r.job_title,r.rlocation,r.emp_type,r.datetime,c.companyname,s.skillname,(SELECT COUNT(*) FROM em_messages x WHERE x.reqid=r.reqid AND x.status='sent') AS sent_count,(SELECT COUNT(*) FROM em_messages x WHERE x.reqid=r.reqid AND x.status IN ('scheduled','processing')) AS queued_count,(SELECT GROUP_CONCAT(DISTINCT x.list_name ORDER BY x.list_name SEPARATOR ', ') FROM em_messages x WHERE x.reqid=r.reqid AND x.status='sent') AS sent_lists FROM req r LEFT JOIN clients c ON c.cid=r.cid LEFT JOIN skill s ON s.sid=r.skillid WHERE $cond ORDER BY r.datetime DESC,r.reqid DESC LIMIT $per OFFSET $offset";
$q = $db->prepare($query);
$q->execute($params);
$rows = $q->fetchAll();
$clients = $db->query('SELECT cid,companyname FROM clients ORDER BY companyname LIMIT 2000')->fetchAll();
$skills = $db->query('SELECT sid,skillname FROM skill ORDER BY skillname')->fetchAll();
?> <div class="panel"><h4>Filter requirements</h4><form method="get"><input type="hidden" name="action" value="emaildistribution"><input type="hidden" name="page" value="requirements">
 <label>Search <input name="q" value="<?= em_h($term) ?>" placeholder="Job, location, client"></label><label>Type <select name="type"><option value="">All</option><?php foreach (['C2C', 'W2', 'FTE', 'C2H'] as $v): ?><option <?= ($type === $v ? 'selected': '') ?>><?= em_h($v) ?></option><?php endforeach;
?></select></label>
 <label>Client <select name="client"><option value="0">All clients</option><?php foreach ($clients as $c): ?><option value="<?= $c['cid'] ?>" <?= $client == (int)$c['cid'] ? 'selected': '' ?>><?= em_h($c['companyname']) ?></option><?php endforeach;
?></select></label>
 <label>Skill <select name="skill"><option value="0">All skills</option><?php foreach ($skills as $s): ?><option value="<?= $s['sid'] ?>" <?= $skill == (int)$s['sid'] ? 'selected': '' ?>><?= em_h($s['skillname']) ?></option><?php endforeach;
?></select></label>
 <label>From <input type="date" name="from" value="<?= em_h($from) ?>"></label><label>To <input type="date" name="to" value="<?= em_h($to) ?>"></label><label>Sharing <select name="share"><option value="">All</option><?php foreach (['not_shared' => 'Not shared', 'sent' => 'Sent', 'scheduled' => 'Scheduled'] as $k => $v): ?><option value="<?= $k ?>" <?= $share === $k ? 'selected': '' ?>><?= $v ?></option><?php endforeach;
?></select></label><button class="btn btn-primary">Apply Filters</button></form></div>
 <form method="post" id="em-campaign"><input type="hidden" name="csrf" value="<?= em_h(em_csrf()) ?>"><input type="hidden" name="op" value="campaign"><input type="hidden" name="campaign_token" value="<?= em_h($_SESSION['em_campaign_token'] ?? ($_SESSION['em_campaign_token'] = bin2hex(random_bytes(20)))) ?>">
 <div class="panel scroll"><h4>Requirements <small>(<?= number_format($total) ?> results; 40 per page)</small></h4><table><thead><tr><th><input type="checkbox" id="em-all"></th><th>ID</th><th>Job Title</th><th>Type</th><th>Location</th><th>Client / Skill</th><th>Date</th><th>Sharing</th></tr></thead><tbody>
 <?php foreach ($rows as $r): ?><tr><td><input class="em-check" type="checkbox" name="reqids[]" value="<?= (int)$r['reqid'] ?>"></td><td><?= (int)$r['reqid'] ?></td><td><?= em_h($r['job_title']) ?></td><td><?= em_h($r['emp_type']) ?></td><td><?= em_h($r['rlocation']) ?></td><td><?= em_h($r['companyname']) ?><div class="muted"><?= em_h($r['skillname']) ?></div></td><td><?= em_h($r['datetime']) ?></td><td><?= $r['sent_count'] ? 'Sent: '.(int)$r['sent_count']: '' ?> <?= $r['queued_count'] ? 'Scheduled: '.(int)$r['queued_count']: '' ?> <?= !$r['sent_count'] && !$r['queued_count'] ? 'Not shared': '' ?><div class="muted"><?= em_h($r['sent_lists']) ?></div></td></tr><?php endforeach;
?></tbody></table>
 <?php $base = $_GET;
unset($base['p']);
for ($n = max(1, $pg - 2); $n <= min((int)ceil($total / $per), $pg + 2); $n++): $url = 'admin.php?'.http_build_query(array_merge($base, ['action' => 'emaildistribution', 'page' => 'requirements', 'p' => $n]));
?><a class="btn btn-default" href="<?= em_h($url) ?>"><?= $n ?></a><?php endfor;
?><p class="muted">Selections apply to the current page only. Select up to 100 requirements per campaign.</p></div>
 <div class="panel"><h4>Compose and schedule</h4><p><b>Separate email for every requirement × selected list.</b> Extra recipients are added to each list's email.</p><label>Template <select name="template_id" required><?php foreach ($templates as $t): ?><option value="<?= $t['id'] ?>"><?= em_h($t['name']) ?></option><?php endforeach;
?></select></label>
 <h5>Recipient Lists</h5><?php foreach ($lists as $l): ?><label><input type="checkbox" name="listids[]" value="<?= $l['id'] ?>" <?= in_array((int)$l['id'], array_filter([(int)$settings['default_to_list'], (int)$settings['default_cc_list'], (int)$settings['default_bcc_list']]), true) ? 'checked': '' ?>> <?= em_h($l['name']) ?></label><?php endforeach;
?> <details><summary>Optional per-list recipient exceptions</summary><?php foreach ($lists as $l): ?><div style="border-bottom:1px solid #ddd;padding:8px"><b><?= em_h($l['name']) ?></b><p><label>Extra To <input name="list_extra[<?= $l['id'] ?>][to]" placeholder="to@example.com"></label><label>Extra CC <input name="list_extra[<?= $l['id'] ?>][cc]" placeholder="cc@example.com"></label><label>Extra BCC <input name="list_extra[<?= $l['id'] ?>][bcc]" placeholder="bcc@example.com"></label><label>Exclude <input name="list_exclude[<?= $l['id'] ?>]" placeholder="exclude@example.com"></label></p></div><?php endforeach;
?></details>
 <div><label>Additional To<br><textarea name="extra_to" rows="2" cols="45" placeholder="extra@example.com"></textarea></label><label>Additional CC<br><textarea name="extra_cc" rows="2" cols="45"></textarea></label><label>Additional BCC<br><textarea name="extra_bcc" rows="2" cols="45"></textarea></label></div>
 <label>Exclude addresses for this campaign<br><textarea name="exclude" rows="2" cols="45" placeholder="excluded@example.com"></textarea></label>
 <label>Schedule (<?= em_h($settings['timezone_name']) ?>; blank = now) <input type="datetime-local" name="scheduled_at"></label><button class="btn btn-success" type="submit" onclick="return confirm('Create this email campaign? Every requirement will be sent separately to each selected list.')">Create Queue</button></div></form>
 <script>document.getElementById('em-all').addEventListener('change',function(){document.querySelectorAll('.em-check').forEach(x=>x.checked=this.checked)});document.getElementById('em-campaign').addEventListener('submit',function(e){if(!document.querySelector('.em-check:checked')||!document.querySelector('[name="listids[]"]:checked')){alert('Select at least one requirement and one list');e.preventDefault();}});</script>
<?php elseif ($page === 'lists'): $contacts = $db->query('SELECT * FROM em_contacts WHERE active=1 ORDER BY email LIMIT 3000')->fetchAll();
$members = $db->query('SELECT lm.list_id,lm.contact_id,lm.recipient_type,c.email,l.name FROM em_list_members lm JOIN em_contacts c ON c.id=lm.contact_id JOIN em_lists l ON l.id=lm.list_id ORDER BY l.name,c.email')->fetchAll();
?> <div class="panel"><h4>Create Recipient List</h4><form method="post"><input type="hidden" name="csrf" value="<?= em_csrf() ?>"><input type="hidden" name="op" value="create_list"><input name="name" required maxlength="150" placeholder="New list name"> <button class="btn btn-primary">Create List</button></form></div>
 <div class="panel"><h4>Add Email to Directory</h4><form method="post"><input type="hidden" name="csrf" value="<?= em_csrf() ?>"><input type="hidden" name="op" value="add_contact"><input name="email" type="email" required placeholder="email@example.com"> <input name="display_name" placeholder="Optional name"> <button class="btn btn-primary">Save Contact</button></form></div>
 <div class="panel"><h4>Assign Contact to List</h4><form method="post"><input type="hidden" name="csrf" value="<?= em_csrf() ?>"><input type="hidden" name="op" value="member"><select name="list_id" required><?php foreach ($lists as $l): ?><option value="<?= $l['id'] ?>"><?= em_h($l['name']) ?></option><?php endforeach;
?></select> <select name="contact_id" required><?php foreach ($contacts as $c): ?><option value="<?= $c['id'] ?>"><?= em_h($c['email']) ?></option><?php endforeach;
?></select> <select name="recipient_type"><option value="to">To</option><option value="cc">CC</option><option value="bcc">BCC</option></select> <button class="btn btn-primary">Add / Update</button></form></div>
 <div class="panel scroll"><h4>Current Members</h4><table><tr><th>List</th><th>Email</th><th>Role</th><th></th></tr><?php foreach ($members as $m): ?><tr><td><?= em_h($m['name']) ?></td><td><?= em_h($m['email']) ?></td><td><?= strtoupper($m['recipient_type']) ?></td><td><form method="post"><input type="hidden" name="csrf" value="<?= em_csrf() ?>"><input type="hidden" name="op" value="remove_member"><input type="hidden" name="list_id" value="<?= $m['list_id'] ?>"><input type="hidden" name="contact_id" value="<?= $m['contact_id'] ?>"><button class="btn btn-xs btn-danger" onclick="return confirm('Remove from list?')">Remove</button></form></td></tr><?php endforeach;
?></table></div>
<?php elseif ($page === 'templates'): $editId = (int)($_GET['edit'] ?? 0);
$editing = null;
foreach ($templates as $t)if ((int)$t['id'] === $editId)$editing = $t;
?> <div class="panel"><h4><?= $editing ? 'Edit': 'Create' ?> Email Template</h4><form method="post"><input type="hidden" name="csrf" value="<?= em_csrf() ?>"><input type="hidden" name="op" value="template"><input type="hidden" name="template_id" value="<?= $editing['id'] ?? 0 ?>"><p><input name="name" required style="width:420px" placeholder="Template name" value="<?= em_h($editing['name'] ?? '') ?>"></p><p><input name="subject" required style="width:650px" placeholder="Subject" value="<?= em_h($editing['subject'] ?? '') ?>"></p><p><textarea name="body" required rows="13" style="width:100%" placeholder="Email body"><?= em_h($editing['body'] ?? '') ?></textarea></p><p class="muted">Placeholders: {{req_id}}, {{job_title}}, {{location}}, {{employment_type}}, {{client}}, {{job_description}}. Plain-text emails are sent.</p><button class="btn btn-primary">Save Template</button></form></div>
 <div class="panel"><h4>Available Templates</h4><table><tr><th>Name</th><th>Subject</th><th></th></tr><?php foreach ($templates as $t): ?><tr><td><?= em_h($t['name']) ?></td><td><?= em_h($t['subject']) ?></td><td><a href="admin.php?action=emaildistribution&page=templates&edit=<?= $t['id'] ?>">Edit</a></td></tr><?php endforeach;
?></table></div>
<?php elseif ($page === 'settings'): ?> <div class="panel"><h4>Gmail SMTP and Rate Limits</h4><form method="post"><input type="hidden" name="csrf" value="<?= em_csrf() ?>"><input type="hidden" name="op" value="settings"><p><label>Gmail address <input type="email" name="smtp_email" required value="<?= em_h($settings['smtp_email']) ?>"></label><label>From name <input name="smtp_from_name" value="<?= em_h($settings['smtp_from_name']) ?>"></label></p><p><label>Gmail App Password <input type="password" name="smtp_password" autocomplete="new-password" placeholder="Leave blank to keep current"></label></p><p><label>Messages / rolling hour <input type="number" name="hourly_limit" min="1" max="100" value="<?= $settings['hourly_limit'] ?>"></label><label>Recipients / rolling 24h <input type="number" name="daily_recipient_limit" min="1" max="1000" value="<?= $settings['daily_recipient_limit'] ?>"></label><label>Minimum gap (seconds) <input type="number" name="min_gap_seconds" min="0" max="3600" value="<?= $settings['min_gap_seconds'] ?>"></label></p><p><label>Timezone <input name="timezone_name" value="<?= em_h($settings['timezone_name']) ?>"></label><label><input type="checkbox" name="queue_paused" <?= $settings['queue_paused'] ? 'checked': '' ?>> Pause queue</label></p><p><label>Sending window start (optional) <input type="time" name="send_start" value="<?= em_h($settings['send_start']) ?>"></label><label>Sending window end (optional) <input type="time" name="send_end" value="<?= em_h($settings['send_end']) ?>"></label></p><p class="muted">Select default lists to preselect on the compose screen. Each selected list receives its own email. List members retain their To/CC/BCC roles.</p>
 <?php foreach (['default_to_list' => 'Default To List', 'default_cc_list' => 'Default CC List', 'default_bcc_list' => 'Default BCC List'] as $key => $label): ?><label><?= $label ?> <select name="<?= $key ?>"><option value="">None</option><?php foreach ($lists as $l): ?><option value="<?= $l['id'] ?>" <?= (int)$settings[$key] === (int)$l['id'] ? 'selected': '' ?>><?= em_h($l['name']) ?></option><?php endforeach;
?></select></label><?php endforeach;
?><p><button class="btn btn-primary">Save Settings</button></p><p class="muted">Gmail STARTTLS port 587. SMTP passwords are encrypted using EMAIL_SECRET_KEY. Do not put the key in the web root.</p></form></div>
<?php elseif ($page === 'queue'): $stats = $db->query('SELECT status,COUNT(*) n FROM em_messages GROUP BY status')->fetchAll();
$campaigns = $db->query("SELECT c.*,u.name,(SELECT COUNT(*) FROM em_messages m WHERE m.campaign_id=c.id) total,(SELECT COUNT(*) FROM em_messages m WHERE m.campaign_id=c.id AND m.status='sent') sent,(SELECT COUNT(*) FROM em_messages m WHERE m.campaign_id=c.id AND m.status='failed') failed,(SELECT COUNT(*) FROM em_messages m WHERE m.campaign_id=c.id AND m.status='scheduled') pending FROM em_campaigns c LEFT JOIN users u ON u.uid=c.created_by ORDER BY c.id DESC LIMIT 100")->fetchAll();
?> <div class="panel"><h4>Queue Overview</h4><?php foreach ($stats as $s): ?><span class="tag"><?= em_h($s['status']) ?>: <?= (int)$s['n'] ?></span> <?php endforeach;
?><p>Queue: <?= $settings['queue_paused'] ? 'PAUSED': 'ACTIVE' ?>. Limit: <?= (int)$settings['hourly_limit'] ?> messages/hour, <?= (int)$settings['daily_recipient_limit'] ?> recipients/day.</p></div>
 <div class="panel scroll"><h4>Recent Campaigns</h4><table><tr><th>Campaign</th><th>Created By</th><th>Scheduled (UTC)</th><th>Messages</th><th>Sent</th><th>Failed</th><th>State</th><th></th></tr><?php foreach ($campaigns as $c): ?><tr><td>#<?= $c['id'] ?></td><td><?= em_h($c['name']) ?></td><td><?= em_h($c['scheduled_at']) ?></td><td><?= $c['total'] ?></td><td><?= $c['sent'] ?></td><td><?= $c['failed'] ?></td><td><?= em_h($c['status']) ?></td><td><?php if ($c['pending'] > 0 && ((int)$u['level'] === 1 || (int)$c['created_by'] === (int)$u['uid'])): ?><form method="post"><input type="hidden" name="csrf" value="<?= em_csrf() ?>"><input type="hidden" name="op" value="cancel"><input type="hidden" name="campaign_id" value="<?= $c['id'] ?>"><button class="btn btn-danger btn-xs" onclick="return confirm('Cancel all unsent messages in this campaign?')">Cancel Pending</button></form><?php endif;
?></td></tr><?php endforeach;
?></table></div>
<?php elseif ($page === 'history'): $histStatus = (string)($_GET['status'] ?? '');
$histList = (int)($_GET['list_id'] ?? 0);
$histReq = (int)($_GET['reqid'] ?? 0);
$histFrom = (string)($_GET['from'] ?? '');
$histTo = (string)($_GET['to'] ?? '');
$w = ['1=1'];
$p = [];
if (in_array($histStatus, ['scheduled', 'processing', 'sent', 'failed', 'cancelled'], true)) {
    $w[] = 'm.status=?';
    $p[] = $histStatus;
}
if ($histList) {
    $w[] = 'm.list_id=?';
    $p[] = $histList;
}
if ($histReq) {
    $w[] = 'm.reqid=?';
    $p[] = $histReq;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $histFrom)) {
    $w[] = 'm.created_at>=?';
    $p[] = $histFrom.' 00:00:00';
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $histTo)) {
    $w[] = 'm.created_at<?';
    $p[] = date('Y-m-d', strtotime($histTo.' +1 day')).' 00:00:00';
}
$condition = implode(' AND ', $w);
$histPage = max(1, (int)($_GET['p'] ?? 1));
$lim = 100;
$offset = ($histPage - 1) * $lim;
$s = $db->prepare("SELECT m.id,m.campaign_id,m.reqid,r.job_title,m.list_name,m.recipients_json,m.status,m.attempts,m.available_at,m.sent_at,m.last_error,m.created_at FROM em_messages m LEFT JOIN req r ON r.reqid=m.reqid WHERE $condition ORDER BY m.id DESC LIMIT $lim OFFSET $offset");
$s->execute($p);
$history = $s->fetchAll();
?> <div class="panel"><h4>Sharing History</h4><form method="get"><input type="hidden" name="action" value="emaildistribution"><input type="hidden" name="page" value="history"><label>Requirement <input type="number" name="reqid" value="<?= $histReq ?: '' ?>"></label><label>Status <select name="status"><option value="">All</option><?php foreach (['scheduled', 'processing', 'sent', 'failed', 'cancelled'] as $v): ?><option <?= $histStatus === $v ? 'selected': '' ?>><?= $v ?></option><?php endforeach;
?></select></label><label>List <select name="list_id"><option value="">All</option><?php foreach ($lists as $l): ?><option value="<?= $l['id'] ?>" <?= $histList === (int)$l['id'] ? 'selected': '' ?>><?= em_h($l['name']) ?></option><?php endforeach;
?></select></label><label>From <input type="date" name="from" value="<?= em_h($histFrom) ?>"></label><label>To <input type="date" name="to" value="<?= em_h($histTo) ?>"></label><button class="btn btn-primary">Filter</button> <button class="btn btn-success" type="submit" formaction="email_export.php">Export CSV</button></form></div>
 <div class="panel scroll"><table><tr><th>Message</th><th>Requirement</th><th>List</th><th>Recipients</th><th>Status</th><th>Attempts</th><th>Scheduled UTC</th><th>Sent UTC</th><th>Error</th></tr><?php foreach ($history as $h): ?><tr><td>#<?= $h['id'] ?> / Campaign <?= $h['campaign_id'] ?></td><td><?= $h['reqid'] ?> <?= em_h($h['job_title']) ?></td><td><?= em_h($h['list_name']) ?></td><td><?php $rr = json_decode($h['recipients_json'], true) ?: [];
foreach (['to', 'cc', 'bcc'] as $kind)if (!empty($rr[$kind]))echo '<div><b>'.strtoupper($kind).':</b> '.em_h(implode(', ', $rr[$kind])).'</div>';
?></td><td><?= em_h($h['status']) ?></td><td><?= $h['attempts'] ?></td><td><?= em_h($h['available_at']) ?></td><td><?= em_h($h['sent_at']) ?></td><td><?= em_h($h['last_error']) ?></td></tr><?php endforeach;
?></table><p><?php if ($histPage > 1): $params = $_GET;
$params['p'] = $histPage - 1;
?><a class="btn btn-default" href="admin.php?<?= em_h(http_build_query($params)) ?>">Previous</a><?php endif;
?><?php if (count($history) === $lim): $params = $_GET;
$params['p'] = $histPage + 1;
?><a class="btn btn-default" href="admin.php?<?= em_h(http_build_query($params)) ?>">Next</a><?php endif;
?></p></div>
<?php endif;
?><?php if (in_array($page, ['queue', 'history'], true)): $logs = $db->query('SELECT l.*,u.name FROM em_logs l LEFT JOIN users u ON u.uid=l.actor_uid ORDER BY l.id DESC LIMIT 100')->fetchAll();
?><div class="panel scroll"><h4>Recent Audit and Delivery Logs</h4><table><tr><th>UTC / DB Time</th><th>Event</th><th>Campaign</th><th>Message</th><th>User</th><th>Detail</th></tr><?php foreach ($logs as $l): ?><tr><td><?= em_h($l['created_at']) ?></td><td><?= em_h($l['event']) ?></td><td><?= em_h($l['campaign_id']) ?></td><td><?= em_h($l['message_id']) ?></td><td><?= em_h($l['name']) ?></td><td><?= em_h($l['detail']) ?></td></tr><?php endforeach;
?></table></div><?php endif;
?></div>
<?php require __DIR__.'/includes/footer.php';
?>