<?php
/* JSON endpoint for Daily Workflow (admin.php?action=dailyworkflow). Login required; POSTs need the CSRF token. */
require_once __DIR__ . '/dw_lib.php';
header('Cache-Control: no-store');

function dw_out(array $d, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
set_exception_handler(function ($e) {
    if ($e instanceof PDOException) error_log('dw_ajax: ' . $e->getMessage());
    dw_out(['ok' => false, 'error' => $e instanceof PDOException ? 'Database error. Please try again.' : $e->getMessage()], 400);
});

$u = tk_user();
dw_schema();
$db = tk_db();
$op = (string)($_REQUEST['op'] ?? '');
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($isPost) tk_verify();
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$today = date('Y-m-d');
const DW_BACKFILL_DAYS = 7;   // owners can still tick items up to 7 days back (shown as "late")

function dw_need_post(bool $p): void { if (!$p) dw_out(['ok' => false, 'error' => 'POST required'], 405); }
function dw_act(int $id): array {
    $q = tk_db()->prepare('SELECT * FROM dw_activities WHERE id = ?');
    $q->execute([$id]);
    $a = $q->fetch();
    if (!$a) throw new RuntimeException('Activity not found.');
    return $a;
}
function dw_days_in($v): string {
    $d = array_values(array_unique(array_filter(array_map('intval', (array)$v), fn($x) => $x >= 1 && $x <= 7)));
    sort($d);
    if (!$d) throw new RuntimeException('Pick at least one day of the week.');
    return implode(',', $d);
}
function dw_days_label(string $days): string {
    $d = array_map('intval', explode(',', $days));
    if ($d === [1, 2, 3, 4, 5]) return 'Mon-Fri';
    if (count($d) === 7) return 'Every day';
    return implode(', ', array_map(fn($x) => DW_DAYS[$x] ?? '', $d));
}

switch ($op) {

case 'day':
    $uid = (int)($_GET['uid'] ?? 0) ?: $u['uid'];
    if (!dw_can_view($u, $uid)) throw new RuntimeException('You can only see your own daily workflow.');
    $date = dw_date($_GET['date'] ?? '');
    $items = dw_day($uid, $date);
    $done = count(array_filter($items, fn($x) => $x['status'] === 'done'));
    $skip = count(array_filter($items, fn($x) => $x['status'] === 'skipped'));
    $own = $uid === $u['uid'];
    $canCheck = $own && $date <= $today && $date >= date('Y-m-d', strtotime('-' . DW_BACKFILL_DAYS . ' days'));
    $sum = dw_summary([$uid], date('Y-m-d', strtotime($date . ' -6 days')), $date);
    $week = [];
    for ($d = date('Y-m-d', strtotime($date . ' -6 days')); $d <= $date; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
        $s = $sum[$uid][$d] ?? [0, 0, 0];
        $week[] = ['date' => $d, 'label' => date('D m/d', strtotime($d)), 'done' => $s[0], 'skipped' => $s[1], 'total' => $s[2]];
    }
    dw_out(['ok' => true, 'uid' => $uid, 'name' => tk_uname($uid), 'date' => $date, 'label' => date('l, F j, Y', strtotime($date)), 'is_today' => $date === $today,
        'items' => $items, 'done' => $done, 'skipped' => $skip, 'total' => count($items), 'can_check' => $canCheck, 'own' => $own,
        'can_manage' => dw_can_manage($u, $uid), 'future' => $date > $today, 'too_old' => $own && $date < date('Y-m-d', strtotime('-' . DW_BACKFILL_DAYS . ' days')), 'week' => $week]);

case 'check':
    dw_need_post($isPost);
    $a = dw_act((int)($_POST['activity_id'] ?? 0));
    if ((int)$a['uid'] !== $u['uid']) throw new RuntimeException('Only ' . tk_uname($a['uid']) . ' can tick their own activities.');
    $date = dw_date($_POST['date'] ?? '');
    if ($date > $today) throw new RuntimeException('You cannot tick an activity for a future day.');
    if ($date < date('Y-m-d', strtotime('-' . DW_BACKFILL_DAYS . ' days'))) throw new RuntimeException('Activities older than ' . DW_BACKFILL_DAYS . ' days can no longer be changed.');
    $st = (string)($_POST['status'] ?? '');
    $note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 1000);
    $count = ($_POST['count'] ?? '') === '' ? null : max(0, (int)$_POST['count']);
    if ($st === 'skipped' && $note === '') throw new RuntimeException('Give a reason for skipping this activity.');
    $cur = $db->prepare('SELECT status, note FROM dw_checks WHERE activity_id = ? AND work_date = ?');
    $cur->execute([(int)$a['id'], $date]);
    $old = $cur->fetch();
    if ($st === 'open') {
        if ($old) {
            $db->prepare('DELETE FROM dw_checks WHERE activity_id = ? AND work_date = ?')->execute([(int)$a['id'], $date]);
            dw_log((int)$a['id'], $u['uid'], $u['uid'], 'undone', $a['title'], $date);
        }
    } elseif (in_array($st, ['done', 'skipped'], true)) {
        $db->prepare('INSERT INTO dw_checks(activity_id, uid, work_date, status, count_done, note, checked_at) VALUES(?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE checked_at = IF(status = VALUES(status), checked_at, VALUES(checked_at)), status = VALUES(status), count_done = VALUES(count_done), note = VALUES(note)')
           ->execute([(int)$a['id'], $u['uid'], $date, $st, $count, $note !== '' ? $note : null, tk_now()]);
        $ev = (!$old || $old['status'] !== $st) ? $st : 'note';
        dw_log((int)$a['id'], $u['uid'], $u['uid'], $ev, $a['title'] . ($count !== null ? ' (' . $count . ($a['target'] ? '/' . (int)$a['target'] : '') . ')' : '') . ($note !== '' ? ' - ' . $note : ''), $date);
    } else throw new RuntimeException('Unknown status.');
    dw_out(['ok' => true]);

case 'team':   // completion of everyone this user can see, for one day + the last 7 days
    if (!tk_is_mgr($u)) throw new RuntimeException('Only admins and managers can see the team view.');
    $date = dw_date($_GET['date'] ?? '');
    $uids = dw_visible_uids($u);
    $from = date('Y-m-d', strtotime($date . ' -6 days'));
    $sum = dw_summary($uids, $from, $date);
    $rows = [];
    foreach ($uids as $id) {
        $d = $sum[$id][$date] ?? [0, 0, 0];
        $wk = [0, 0, 0];
        foreach ($sum[$id] ?? [] as $x) { $wk[0] += $x[0]; $wk[1] += $x[1]; $wk[2] += $x[2]; }
        $n = (int)$db->query('SELECT COUNT(*) FROM dw_activities WHERE active = 1 AND uid = ' . (int)$id)->fetchColumn();
        if (!$n && !$wk[2] && $id !== $u['uid']) continue;   // people with no workflow are left out
        $usr = tk_users()[$id] ?? ['level' => 0];
        $rows[] = ['uid' => $id, 'name' => tk_uname($id), 'role' => TK_LEVELS[$usr['level']] ?? '', 'me' => $id === $u['uid'],
            'done' => $d[0], 'skipped' => $d[1], 'total' => $d[2], 'open' => $d[2] - $d[0] - $d[1],
            'week_done' => $wk[0], 'week_total' => $wk[2], 'week_pct' => $wk[2] ? (int)round(($wk[0] + $wk[1]) * 100 / $wk[2]) : null, 'activities' => $n];
    }
    $without = array_values(array_map(fn($id) => ['uid' => $id, 'name' => tk_uname($id)], array_filter($uids, fn($id) => !in_array($id, array_column($rows, 'uid'), true))));
    dw_out(['ok' => true, 'date' => $date, 'label' => date('l, F j, Y', strtotime($date)), 'rows' => $rows, 'without' => $without]);

case 'report':   // per user per day, date range; JSON or CSV
    $uids = dw_visible_uids($u);
    if ((int)($_GET['uid'] ?? 0) > 0) { if (!in_array((int)$_GET['uid'], $uids, true)) throw new RuntimeException('Not allowed.'); $uids = [(int)$_GET['uid']]; }
    $from = dw_date($_GET['from'] ?? date('Y-m-d', strtotime('-13 days')));
    $to = dw_date($_GET['to'] ?? $today);
    if ($to < $from) [$from, $to] = [$to, $from];
    if ((strtotime($to) - strtotime($from)) / 86400 > 92) throw new RuntimeException('Pick a range of up to 3 months.');
    if (!empty($_GET['csv'])) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="daily_workflow_' . $from . '_to_' . $to . '.csv"');
        $o = fopen('php://output', 'w');
        fwrite($o, "\xEF\xBB\xBF");
        fputcsv($o, ['Date', 'Day', 'User', 'Section', 'Activity', 'Due by', 'Target', 'Status', 'Count', 'Checked at', 'Late', 'Note']);
        foreach ($uids as $id) for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            foreach (dw_day($id, $d) as $it) fputcsv($o, [date('m/d/Y', strtotime($d)), date('D', strtotime($d)), tk_uname($id), $it['section'], $it['title'], $it['due_time'],
                $it['target'], $it['status'] === 'open' ? ($d < $today ? 'Missed' : 'Open') : ucfirst($it['status']), $it['count_done'], $it['checked_at'], $it['late'] ? 'Yes' : '', $it['note']]);
        }
        exit;
    }
    $sum = dw_summary($uids, $from, $to);
    $days = [];
    for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) $days[] = ['date' => $d, 'label' => date('D', strtotime($d)) . '<br>' . date('m/d', strtotime($d))];
    $rows = [];
    foreach ($uids as $id) {
        if (empty($sum[$id]) && $id !== $u['uid']) continue;
        $cells = []; $t = [0, 0, 0];
        foreach ($days as $d) { $s = $sum[$id][$d['date']] ?? null; $cells[] = $s; if ($s) { $t[0] += $s[0]; $t[1] += $s[1]; $t[2] += $s[2]; } }
        $rows[] = ['uid' => $id, 'name' => tk_uname($id), 'cells' => $cells, 'done' => $t[0], 'skipped' => $t[1], 'total' => $t[2], 'pct' => $t[2] ? (int)round(($t[0] + $t[1]) * 100 / $t[2]) : null];
    }
    dw_out(['ok' => true, 'from' => $from, 'to' => $to, 'days' => $days, 'rows' => $rows, 'today' => $today]);

case 'acts':   // activity definitions the user can manage (or their own, read-only)
    $uid = (int)($_GET['uid'] ?? 0);
    $uids = $uid ? [$uid] : dw_visible_uids($u);
    foreach ($uids as $id) if (!dw_can_view($u, $id)) throw new RuntimeException('Not allowed.');
    $in = implode(',', array_map('intval', $uids));
    $rows = [];
    foreach ($db->query("SELECT * FROM dw_activities WHERE uid IN ($in) ORDER BY active DESC, uid, sort_order, id") as $a) {
        $rows[] = ['id' => (int)$a['id'], 'uid' => (int)$a['uid'], 'owner' => tk_uname($a['uid']), 'title' => (string)$a['title'], 'details' => (string)$a['details'],
            'section' => (string)$a['section'], 'days' => array_map('intval', explode(',', $a['days'])), 'days_label' => dw_days_label($a['days']),
            'due_time' => $a['due_time'] ? substr($a['due_time'], 0, 5) : '', 'due_label' => $a['due_time'] ? date('g:i A', strtotime($a['due_time'])) : '',
            'target' => $a['target'] !== null ? (int)$a['target'] : null, 'active' => (int)$a['active'] === 1, 'start' => date('m/d/Y', strtotime($a['start_date'])),
            'end' => $a['end_date'] ? date('m/d/Y', strtotime($a['end_date'])) : '', 'created_by' => tk_uname($a['created_by']), 'created' => tk_fmt($a['created_at']),
            'can_manage' => dw_can_manage($u, (int)$a['uid'])];
    }
    dw_out(['ok' => true, 'acts' => $rows]);

case 'act_save':
    dw_need_post($isPost);
    if (!tk_is_mgr($u)) throw new RuntimeException('Only admins and managers can add or change daily activities.');
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '' || mb_strlen($title) > 250) throw new RuntimeException('Activity name is required (max 250 characters).');
    $section = in_array($_POST['section'] ?? '', DW_SECTIONS, true) ? $_POST['section'] : 'Other';
    $days = dw_days_in($_POST['days'] ?? []);
    $due = trim((string)($_POST['due_time'] ?? ''));
    if ($due !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $due)) throw new RuntimeException('Due time must look like 10:30.');
    $target = ($_POST['target'] ?? '') === '' ? null : max(1, min(10000, (int)$_POST['target']));
    $details = mb_substr(trim((string)($_POST['details'] ?? '')), 0, 5000);
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $a = dw_act($id);
        if (!dw_can_manage($u, (int)$a['uid'])) throw new RuntimeException('You cannot change this person\'s activities.');
        $ch = [];
        foreach (['title' => $title, 'section' => $section, 'days' => $days, 'details' => $details] as $k => $v) if ((string)$a[$k] !== (string)$v) $ch[] = $k === 'days' ? 'days: ' . dw_days_label($a['days']) . ' -> ' . dw_days_label($days) : ($k === 'details' ? 'details' : $k . ': "' . $a[$k] . '" -> "' . $v . '"');
        if (substr((string)$a['due_time'], 0, 5) !== $due) $ch[] = 'due by: ' . ($a['due_time'] ? substr($a['due_time'], 0, 5) : 'none') . ' -> ' . ($due ?: 'none');
        if ((string)$a['target'] !== (string)$target) $ch[] = 'target: ' . ($a['target'] ?? 'none') . ' -> ' . ($target ?? 'none');
        $db->prepare('UPDATE dw_activities SET title=?, section=?, days=?, due_time=?, target=?, details=?, updated_by=?, updated_at=? WHERE id=?')
           ->execute([$title, $section, $days, $due ?: null, $target, $details, $u['uid'], tk_now(), $id]);
        if ($ch) dw_log($id, (int)$a['uid'], $u['uid'], 'edited', $title . ': ' . implode('; ', $ch));
        dw_out(['ok' => true, 'saved' => 1]);
    }
    $uids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['uids'] ?? [])))));
    if (!$uids) throw new RuntimeException('Pick at least one person for this activity.');
    foreach ($uids as $id2) if (!dw_can_manage($u, $id2) || empty(tk_users()[$id2]['active'])) throw new RuntimeException('You cannot add activities for ' . tk_uname($id2) . '.');
    $start = dw_date($_POST['start_date'] ?? $today);
    $mx = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM dw_activities WHERE uid = ?');
    $ins = $db->prepare('INSERT INTO dw_activities(uid, title, details, section, days, due_time, target, sort_order, start_date, created_by, created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
    $db->beginTransaction();
    foreach ($uids as $id2) {
        $mx->execute([$id2]);
        $ins->execute([$id2, $title, $details, $section, $days, $due ?: null, $target, (int)$mx->fetchColumn(), $start, $u['uid'], tk_now()]);
        dw_log((int)$db->lastInsertId(), $id2, $u['uid'], 'added', $title . ' (' . dw_days_label($days) . ($due ? ', by ' . $due : '') . ($target ? ', target ' . $target : '') . ')');
    }
    $db->commit();
    dw_out(['ok' => true, 'saved' => count($uids)]);

case 'act_stop':
case 'act_resume':
    dw_need_post($isPost);
    $a = dw_act((int)($_POST['id'] ?? 0));
    if (!dw_can_manage($u, (int)$a['uid'])) throw new RuntimeException('You cannot change this person\'s activities.');
    if ($op === 'act_stop') {
        // stops from tomorrow: today's item (and history) stays
        $db->prepare('UPDATE dw_activities SET active = 0, end_date = ?, updated_by = ?, updated_at = ? WHERE id = ?')->execute([$today, $u['uid'], tk_now(), (int)$a['id']]);
        dw_log((int)$a['id'], (int)$a['uid'], $u['uid'], 'stopped', $a['title']);
    } else {
        $db->prepare('UPDATE dw_activities SET active = 1, end_date = NULL, start_date = LEAST(start_date, ?), updated_by = ?, updated_at = ? WHERE id = ?')->execute([$today, $u['uid'], tk_now(), (int)$a['id']]);
        dw_log((int)$a['id'], (int)$a['uid'], $u['uid'], 'resumed', $a['title']);
    }
    dw_out(['ok' => true]);

case 'act_move':      // ↑ / ↓ buttons: swap with the previous / next active activity
case 'act_reorder':   // drag & drop: after_id / before_id = the activities right above / below the drop point
    dw_need_post($isPost);
    $a = dw_act((int)($_POST['id'] ?? 0));
    if (!dw_can_manage($u, (int)$a['uid'])) throw new RuntimeException('Only admins and managers can change the order of activities.');
    $after = (int)($_POST['after_id'] ?? 0); $before = (int)($_POST['before_id'] ?? 0);
    if ($op === 'act_move') {
        $q = $db->prepare('SELECT id FROM dw_activities WHERE uid = ? AND active = 1 ORDER BY sort_order, id');
        $q->execute([(int)$a['uid']]);
        $list = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
        $i = array_search((int)$a['id'], $list, true);
        if ($i === false) dw_out(['ok' => true]);
        if (($_POST['dir'] ?? '') === 'up') { if ($i === 0) dw_out(['ok' => true]); $before = $list[$i - 1]; $after = $list[$i - 2] ?? 0; }
        else { if (!isset($list[$i + 1])) dw_out(['ok' => true]); $after = $list[$i + 1]; $before = $list[$i + 2] ?? 0; }
    }
    foreach ([$after, $before] as $nid) if ($nid) { $n = dw_act($nid); if ((int)$n['uid'] !== (int)$a['uid']) throw new RuntimeException('Activities belong to different people.'); }
    $db->beginTransaction();
    $sec = dw_reorder($a, $after, $before);
    $where = $before ? 'before "' . dw_act($before)['title'] . '"' : ($after ? 'after "' . dw_act($after)['title'] . '"' : 'to the top');
    dw_log((int)$a['id'], (int)$a['uid'], $u['uid'], 'reordered', $a['title'] . ': moved ' . $where . ($sec !== $a['section'] ? ' (section: ' . $a['section'] . ' -> ' . $sec . ')' : ''));
    $db->commit();
    dw_out(['ok' => true, 'section' => $sec]);

case 'act_copy':   // copy one person's active routine to other people
    dw_need_post($isPost);
    $from = (int)($_POST['from_uid'] ?? 0);
    if (!dw_can_view($u, $from) || !tk_is_mgr($u)) throw new RuntimeException('Not allowed.');
    $to = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['uids'] ?? [])), fn($x) => $x !== $from)));
    if (!$to) throw new RuntimeException('Pick at least one person to copy to.');
    foreach ($to as $id2) if (!dw_can_manage($u, $id2)) throw new RuntimeException('You cannot add activities for ' . tk_uname($id2) . '.');
    $src = $db->prepare('SELECT * FROM dw_activities WHERE uid = ? AND active = 1 ORDER BY sort_order, id');
    $src->execute([$from]);
    $src = $src->fetchAll();
    if (!$src) throw new RuntimeException(tk_uname($from) . ' has no active activities to copy.');
    $have = $db->prepare('SELECT COUNT(*) FROM dw_activities WHERE uid = ? AND active = 1 AND title = ?');
    $mxc = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM dw_activities WHERE uid = ?');
    $ins = $db->prepare('INSERT INTO dw_activities(uid, title, details, section, days, due_time, target, sort_order, start_date, created_by, created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
    $n = 0;
    $db->beginTransaction();
    foreach ($to as $id2) foreach ($src as $k => $a) {
        if ($k === 0) { $mxc->execute([$id2]); $base = (int)$mxc->fetchColumn(); }   // copied steps go after their own, in the same order
        $have->execute([$id2, $a['title']]);
        if ((int)$have->fetchColumn() > 0) continue;   // already has it
        $ins->execute([$id2, $a['title'], $a['details'], $a['section'], $a['days'], $a['due_time'], $a['target'], $base + $k + 1, $today, $u['uid'], tk_now()]);
        dw_log((int)$db->lastInsertId(), $id2, $u['uid'], 'added', $a['title'] . ' (copied from ' . tk_uname($from) . ')');
        $n++;
    }
    $db->commit();
    dw_out(['ok' => true, 'added' => $n]);

case 'log':
    $uids = dw_visible_uids($u);
    if ((int)($_GET['uid'] ?? 0) > 0) { if (!in_array((int)$_GET['uid'], $uids, true)) throw new RuntimeException('Not allowed.'); $uids = [(int)$_GET['uid']]; }
    $from = dw_date($_GET['from'] ?? date('Y-m-d', strtotime('-7 days')));
    $to = dw_date($_GET['to'] ?? $today);
    $in = implode(',', array_map('intval', $uids));
    $q = $db->prepare("SELECT * FROM dw_log WHERE owner_uid IN ($in) AND created_at >= ? AND created_at < ? ORDER BY id DESC LIMIT 500");
    $q->execute([$from, date('Y-m-d', strtotime($to . ' +1 day'))]);
    $rows = [];
    foreach ($q as $r) $rows[] = ['at' => tk_fmt($r['created_at']), 'owner' => tk_uname($r['owner_uid']), 'by' => tk_uname($r['actor_uid']), 'event' => $r['event'],
        'text' => dw_log_text($r), 'detail' => (string)$r['detail'], 'for_date' => $r['work_date'] ? date('m/d/Y', strtotime($r['work_date'])) : ''];
    dw_out(['ok' => true, 'rows' => $rows]);

default:
    dw_out(['ok' => false, 'error' => 'Unknown action'], 400);
}
