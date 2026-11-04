<?php
/*
 * JSON endpoint for the Open Tasks board (admin.php?action=opentasks).
 * Needs a logged-in BATS user; every POST needs the page's CSRF token.
 */
require_once __DIR__ . '/tasks_lib.php';
require_once __DIR__ . '/tasks_notify.php';
require_once __DIR__ . '/tasks_checklist.php';
header('Cache-Control: no-store');

function tk_out(array $d, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
class TkConflict extends RuntimeException {}
set_exception_handler(function ($e) {
    $code = $e instanceof TkConflict ? 409 : 400;
    $msg = ($e instanceof PDOException) ? 'Database error. Please try again.' : $e->getMessage();
    if ($e instanceof PDOException) error_log('tasks_ajax: ' . $e->getMessage());
    tk_out(['ok' => false, 'error' => $msg], $code);
});

$u = tk_user();
tk_schema();
tk_checklist_schema();
$db = tk_db();
$op = (string)($_REQUEST['op'] ?? '');
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($isPost) tk_verify();
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();   // do not block other BATS tabs

/* ---------- helpers ---------- */
function tk_need_post(bool $isPost): void { if (!$isPost) tk_out(['ok' => false, 'error' => 'POST required'], 405); }
function tk_load_for(array $u, int $id): array {
    $t = tk_get($id);
    if (!$t || !tk_can_view($u, $t)) throw new RuntimeException('Task not found or you do not have access to it.');
    return $t;
}
function tk_valid_user($uid, bool $mgrOnly = false): ?int {
    if ($uid === null || $uid === '' || (int)$uid === 0) return null;
    $all = tk_users(true);
    if (!isset($all[(int)$uid])) throw new RuntimeException('Selected user is not active.');
    if ($mgrOnly && !in_array($all[(int)$uid]['level'], [1, 2], true)) throw new RuntimeException('Manager must be an Admin or Manager user.');
    return (int)$uid;
}
function tk_valid_date(?string $d): ?string {
    $d = trim((string)$d);
    if ($d === '') return null;
    $t = strtotime($d);
    if ($t === false) throw new RuntimeException('Invalid due date.');
    return date('Y-m-d', $t);
}
/** Filtered task query shared by the board, list and CSV export. */
function tk_find(array $u, array $f, bool $board): array {
    $params = [];
    $w = ['T.is_deleted = 0', tk_scope_sql($u, $params)];
    $q = trim((string)($f['q'] ?? ''));
    if ($q !== '') {
        if (preg_match('/^(?:TSK-)?0*(\d+)$/i', $q, $m)) { $w[] = '(T.id = ? OR T.title LIKE ?)'; $params[] = (int)$m[1]; $params[] = '%' . $q . '%'; }
        else { $w[] = '(T.title LIKE ? OR T.description LIKE ?)'; $params[] = '%' . $q . '%'; $params[] = '%' . $q . '%'; }
    }
    foreach (['assignee' => 'T.assignee_uid', 'manager' => 'T.manager_uid', 'creator' => 'T.created_by'] as $k => $col) {
        if (($f[$k] ?? '') === 'none') $w[] = "$col IS NULL";
        elseif ((int)($f[$k] ?? 0) > 0) { $w[] = "$col = ?"; $params[] = (int)$f[$k]; }
    }
    if (isset(TK_PRIORITIES[$f['priority'] ?? ''])) { $w[] = 'T.priority = ?'; $params[] = $f['priority']; }
    if (in_array($f['category'] ?? '', TK_CATEGORIES, true)) { $w[] = 'T.category = ?'; $params[] = $f['category']; }
    switch ($f['mine'] ?? '') {
        case 'assigned': $w[] = 'T.assignee_uid = ?'; $params[] = $u['uid']; break;
        case 'created':  $w[] = 'T.created_by = ?';   $params[] = $u['uid']; break;
        case 'managing': $w[] = 'T.manager_uid = ?';  $params[] = $u['uid']; break;
        case 'review':   $w[] = "T.status = 'in_review' AND (T.manager_uid = ?" . (tk_is_admin($u) ? ' OR T.manager_uid IS NULL' : '') . ')'; $params[] = $u['uid']; break;
    }
    if (($f['due'] ?? '') === 'overdue') { $w[] = "T.due_date < ? AND T.status NOT IN ('completed','cancelled')"; $params[] = date('Y-m-d'); }
    elseif (($f['due'] ?? '') === 'today') { $w[] = 'T.due_date = ?'; $params[] = date('Y-m-d'); }
    elseif (($f['due'] ?? '') === 'week') { $w[] = 'T.due_date >= ? AND T.due_date < ?'; $params[] = date('Y-m-d'); $params[] = date('Y-m-d', strtotime('+7 days')); }
    if (isset(TK_STAGES[$f['status'] ?? ''])) { $w[] = 'T.status = ?'; $params[] = $f['status']; }
    elseif ($board) {
        // board: all open work + anything completed in the last N days (default 30); cancelled only on request
        $days = max(1, min(3650, (int)($f['done_days'] ?? 30)));
        $w[] = "(T.status NOT IN ('completed','cancelled') OR (T.status = 'completed' AND T.completed_at >= ?)" . (!empty($f['show_cancelled']) ? ' OR T.status = \'cancelled\'' : '') . ')';
        $params[] = date('Y-m-d H:i:s', strtotime("-$days days"));
    }
    $sql = 'SELECT T.*, (SELECT COUNT(*) FROM tk_comments C WHERE C.task_id = T.id) AS ncomments FROM tk_tasks T WHERE ' . implode(' AND ', $w)
         . " ORDER BY FIELD(T.priority,'critical','high','medium','low'), (T.due_date IS NULL), T.due_date, T.id DESC LIMIT 2000";
    $s = tk_db()->prepare($sql);
    $s->execute($params);
    return $s->fetchAll();
}
function tk_stats(array $u): array {
    $params = [];
    $scope = tk_scope_sql($u, $params);
    $today = date('Y-m-d');
    $s = tk_db()->prepare("SELECT
        SUM(T.status NOT IN ('completed','cancelled')) AS open_total,
        SUM(T.status NOT IN ('completed','cancelled') AND T.due_date < ?) AS overdue,
        SUM(T.status NOT IN ('completed','cancelled') AND T.due_date = ?) AS due_today,
        SUM(T.status = 'in_review') AS in_review,
        SUM(T.status = 'on_hold') AS on_hold,
        SUM(T.status NOT IN ('completed','cancelled') AND T.assignee_uid IS NULL) AS unassigned,
        SUM(T.status = 'completed' AND T.completed_at >= ?) AS done_7d,
        SUM(T.status NOT IN ('completed','cancelled') AND T.assignee_uid = ?) AS mine
        FROM tk_tasks T WHERE T.is_deleted = 0 AND $scope");
    $s->execute(array_merge([$today, $today, date('Y-m-d H:i:s', strtotime('-7 days')), $u['uid']], $params));
    return array_map('intval', $s->fetch() ?: []);
}

/* ---------- operations ---------- */
switch ($op) {

case 'board':
    $rows = tk_find($u, $_GET, ($_GET['view'] ?? 'board') !== 'list');
    $pm = tk_progress_map(array_column($rows, 'id'));
    tk_out(['ok' => true, 'tasks' => array_map(function ($t) use ($u, $pm) { $p = tk_public($t, $u); $p['check'] = $pm[(int)$t['id']] ?? [0, 0]; return $p; }, $rows), 'stats' => tk_stats($u), 'now' => date('m/d/Y g:i A')]);

case 'stats':
    tk_out(['ok' => true, 'stats' => tk_stats($u)]);

case 'get':
    $t = tk_load_for($u, (int)($_GET['id'] ?? 0));
    $c = $db->prepare('SELECT * FROM tk_comments WHERE task_id = ? ORDER BY id');
    $c->execute([(int)$t['id']]);
    $subs = tk_subtasks((int)$t['id']);
    $subTitle = [];
    foreach ($subs as $sb) $subTitle[(int)$sb['id']] = $sb['title'];
    $comments = array_map(fn($r) => ['id' => (int)$r['id'], 'by' => tk_uname($r['uid']), 'uid' => (int)$r['uid'], 'body' => (string)$r['body'], 'at' => tk_fmt($r['created_at']),
        'subtask_id' => $r['subtask_id'] ? (int)$r['subtask_id'] : null, 'subtask' => $r['subtask_id'] ? ($subTitle[(int)$r['subtask_id']] ?? '(removed sub-task)') : ''], $c->fetchAll());
    $a = $db->prepare('SELECT * FROM tk_activity WHERE task_id = ? ORDER BY id DESC');
    $a->execute([(int)$t['id']]);
    $acts = array_map(fn($r) => ['by' => tk_uname($r['uid']), 'event' => $r['event'], 'subtask_id' => $r['subtask_id'] ? (int)$r['subtask_id'] : null, 'text' => tk_activity_text($r), 'detail' => (string)$r['detail'], 'at' => tk_fmt($r['created_at'])], $a->fetchAll());
    [$pd, $pt] = tk_progress((int)$t['id']);
    $pub = tk_public($t, $u); $pub['check'] = [$pd, $pt];
    tk_out(['ok' => true, 'task' => $pub, 'comments' => $comments, 'activity' => $acts,
        'subtasks' => array_map(fn($x) => tk_sub_public($x, $u, $t), $subs), 'can_add_sub' => tk_can_sub($u, $t),
        'tpl_missing' => count(array_filter(tk_tpl_list($t['category']), fn($x) => !in_array((int)$x['id'], array_map('intval', array_column($subs, 'tpl_id')), true)))]);

case 'create':
    tk_need_post($isPost);
    if ($u['level'] === 5) throw new RuntimeException('Viewer accounts cannot create tasks.');
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '' || mb_strlen($title) > 200) throw new RuntimeException('Title is required (max 200 characters).');
    $prio = isset(TK_PRIORITIES[$_POST['priority'] ?? '']) ? $_POST['priority'] : 'medium';
    $cat = in_array($_POST['category'] ?? '', TK_CATEGORIES, true) ? $_POST['category'] : 'General';
    $assignee = tk_valid_user($_POST['assignee_uid'] ?? null);
    $manager = tk_valid_user($_POST['manager_uid'] ?? null, true);
    if ($assignee && $assignee !== $u['uid'] && !tk_is_mgr($u) && !$manager) {
        // recruiters may raise tasks for others, but a manager must own them
        $manager = (tk_users()[$u['uid']]['rmid'] ?? 0) ? tk_valid_user(tk_users()[$u['uid']]['rmid'], true) : null;
    }
    $due = tk_valid_date($_POST['due_date'] ?? '');
    $now = tk_now();
    $status = $assignee ? 'todo' : 'new';
    $db->beginTransaction();
    $db->prepare('INSERT INTO tk_tasks(title, description, category, priority, status, created_by, assignee_uid, manager_uid, due_date, created_at, updated_at, assigned_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')
       ->execute([$title, trim((string)($_POST['description'] ?? '')), $cat, $prio, $status, $u['uid'], $assignee, $manager, $due, $now, $now, $assignee ? $now : null]);
    $id = (int)$db->lastInsertId();
    tk_log($id, $u['uid'], 'created', null, $status, $title);
    if ($assignee) tk_log($id, $u['uid'], 'assigned', '', tk_uname($assignee));
    if ($manager) tk_log($id, $u['uid'], 'manager', '', tk_uname($manager));
    if (($_POST['apply_checklist'] ?? '1') !== '0') tk_apply_checklist($id, $cat, $u['uid']);
    $db->commit();
    $nq = tk_notify('task_created', $id, $u['uid']);
    tk_out(['ok' => true, 'task' => tk_public(tk_get($id), $u), 'notify' => $nq]);

case 'update':
    tk_need_post($isPost);
    $t = tk_load_for($u, (int)($_POST['id'] ?? 0));
    if (!tk_can_manage($u, $t)) throw new RuntimeException('Only the task manager, the creator or an admin can edit this task.');
    if ((int)($_POST['version'] ?? 0) !== (int)$t['version']) throw new TkConflict('This task was changed by someone else a moment ago. It has been refreshed - please review and save again.');
    $new = [
        'title' => trim((string)($_POST['title'] ?? $t['title'])),
        'description' => trim((string)($_POST['description'] ?? $t['description'])),
        'category' => in_array($_POST['category'] ?? '', TK_CATEGORIES, true) ? $_POST['category'] : $t['category'],
        'priority' => isset(TK_PRIORITIES[$_POST['priority'] ?? '']) ? $_POST['priority'] : $t['priority'],
        'due_date' => array_key_exists('due_date', $_POST) ? tk_valid_date($_POST['due_date']) : $t['due_date'],
        'assignee_uid' => array_key_exists('assignee_uid', $_POST) ? tk_valid_user($_POST['assignee_uid']) : ($t['assignee_uid'] ? (int)$t['assignee_uid'] : null),
        'manager_uid' => array_key_exists('manager_uid', $_POST) ? tk_valid_user($_POST['manager_uid'], true) : ($t['manager_uid'] ? (int)$t['manager_uid'] : null),
    ];
    if ($new['title'] === '' || mb_strlen($new['title']) > 200) throw new RuntimeException('Title is required (max 200 characters).');
    $status = $t['status'];
    if ($new['assignee_uid'] && $status === 'new') $status = 'todo';                   // assigning moves New -> To Do
    if (!$new['assignee_uid'] && $status === 'todo') $status = 'new';                  // un-assigning moves it back
    $now = tk_now();
    $db->beginTransaction();
    // assigned_at first: MySQL evaluates SET left to right, so it must see the old assignee
    $s = $db->prepare('UPDATE tk_tasks SET assigned_at = IF(? <> IFNULL(assignee_uid,0), ?, assigned_at), title=?, description=?, category=?, priority=?, due_date=?,
            assignee_uid=?, manager_uid=?, status=?, updated_at=?, version = version + 1 WHERE id=? AND version=?');
    $s->execute([(int)$new['assignee_uid'], $new['assignee_uid'] ? $now : null, $new['title'], $new['description'], $new['category'], $new['priority'], $new['due_date'],
                 $new['assignee_uid'], $new['manager_uid'], $status, $now, (int)$t['id'], (int)$t['version']]);
    if ($s->rowCount() !== 1) { $db->rollBack(); throw new TkConflict('This task was changed by someone else a moment ago. Please refresh and try again.'); }
    $id = (int)$t['id'];
    if ($new['title'] !== $t['title']) tk_log($id, $u['uid'], 'title', $t['title'], $new['title']);
    if ($new['description'] !== (string)$t['description']) tk_log($id, $u['uid'], 'description');
    if ($new['category'] !== $t['category']) { tk_log($id, $u['uid'], 'category', $t['category'], $new['category']); tk_apply_checklist($id, $new['category'], $u['uid']); }
    if ($new['priority'] !== $t['priority']) tk_log($id, $u['uid'], 'priority', $t['priority'], $new['priority']);
    if ((string)$new['due_date'] !== (string)$t['due_date']) tk_log($id, $u['uid'], 'due_date', $t['due_date'] ? date('m/d/Y', strtotime($t['due_date'])) : '', $new['due_date'] ? date('m/d/Y', strtotime($new['due_date'])) : '');
    if ((int)$new['assignee_uid'] !== (int)$t['assignee_uid']) tk_log($id, $u['uid'], 'assigned', tk_uname($t['assignee_uid']), tk_uname($new['assignee_uid']));
    if ((int)$new['manager_uid'] !== (int)$t['manager_uid']) tk_log($id, $u['uid'], 'manager', tk_uname($t['manager_uid']), tk_uname($new['manager_uid']));
    if ($status !== $t['status']) tk_log($id, $u['uid'], 'status', $t['status'], $status, 'Automatic (assignment changed)');
    $db->commit();
    $nq = ($new['assignee_uid'] && (int)$new['assignee_uid'] !== (int)$t['assignee_uid']) ? tk_notify('task_assigned', $id, $u['uid']) : 0;
    tk_out(['ok' => true, 'task' => tk_public(tk_get($id), $u), 'notify' => $nq]);

case 'move':
    tk_need_post($isPost);
    $t = tk_load_for($u, (int)($_POST['id'] ?? 0));
    $to = (string)($_POST['to'] ?? '');
    if ((int)($_POST['version'] ?? 0) !== (int)$t['version']) throw new TkConflict('This task was changed by someone else a moment ago. The board has been refreshed.');
    if ($to === $t['status']) tk_out(['ok' => true, 'task' => tk_public($t, $u)]);
    if (!isset(TK_STAGES[$to]) || !in_array($to, tk_next_stages($u, $t), true)) {
        $allowed = array_map('tk_stage_label', tk_next_stages($u, $t));
        throw new RuntimeException('A task in ' . tk_stage_label($t['status']) . ' cannot be moved to ' . tk_stage_label($to) . ' by you. '
            . ($allowed ? 'Allowed: ' . implode(', ', $allowed) . '.' : 'You are not the assignee or an owner of this task.')
            . ($to === 'completed' && $t['manager_uid'] ? ' Move it to In Review so ' . tk_uname($t['manager_uid']) . ' can approve it.' : ''));
    }
    if (in_array($to, ['in_review', 'completed'], true)) {
        [, , $openReq] = tk_progress((int)$t['id']);
        if ($openReq) throw new RuntimeException('These required checklist items are still open: ' . implode('; ', array_slice($openReq, 0, 8)) . (count($openReq) > 8 ? ' …' : '') . '. Mark them Done (or N/A with a reason) first.');
    }
    $reason = trim((string)($_POST['reason'] ?? ''));
    if (tk_reason_needed($t['status'], $to) && $reason === '') throw new RuntimeException('Please give a reason for moving this task to ' . tk_stage_label($to) . '.');
    $now = tk_now();
    $set = ['status = ?', 'updated_at = ?', 'version = version + 1'];
    $p = [$to, $now];
    if ($to === 'in_progress' && !$t['started_at']) { $set[] = 'started_at = ?'; $p[] = $now; }
    if ($to === 'in_review') { $set[] = 'review_at = ?'; $p[] = $now; }
    if ($to === 'on_hold') { $set[] = 'hold_reason = ?'; $p[] = mb_substr($reason, 0, 500); }
    if ($to === 'completed') { $set[] = 'completed_at = ?'; $p[] = $now; $set[] = 'completed_by = ?'; $p[] = $u['uid']; $set[] = 'resolution = ?'; $p[] = mb_substr($reason, 0, 1000) ?: null; }
    if ($to === 'cancelled') { $set[] = 'cancelled_at = ?'; $p[] = $now; $set[] = 'resolution = ?'; $p[] = mb_substr($reason, 0, 1000); }
    if (in_array($t['status'], ['completed', 'cancelled'], true)) { $set[] = 'completed_at = NULL'; $set[] = 'completed_by = NULL'; $set[] = 'cancelled_at = NULL'; $set[] = 'resolution = NULL'; }
    if ($t['status'] === 'on_hold') { $set[] = 'hold_reason = NULL'; }
    $p[] = (int)$t['id']; $p[] = (int)$t['version'];
    $db->beginTransaction();
    $s = $db->prepare('UPDATE tk_tasks SET ' . implode(', ', $set) . ' WHERE id = ? AND version = ?');
    $s->execute($p);
    if ($s->rowCount() !== 1) { $db->rollBack(); throw new TkConflict('This task was changed by someone else a moment ago. The board has been refreshed.'); }
    $label = in_array($t['status'], ['completed', 'cancelled'], true) ? 'Reopened: ' : '';
    tk_log((int)$t['id'], $u['uid'], 'status', $t['status'], $to, $label . $reason);
    $db->commit();
    $nq = tk_notify(tk_move_event($t['status'], $to), (int)$t['id'], $u['uid'], ['from' => $t['status'], 'reason' => $reason]);
    tk_out(['ok' => true, 'task' => tk_public(tk_get((int)$t['id']), $u), 'notify' => $nq]);

case 'comment':
    tk_need_post($isPost);
    $t = tk_load_for($u, (int)($_POST['id'] ?? 0));
    $body = trim((string)($_POST['body'] ?? ''));
    if ($body === '' || mb_strlen($body) > 5000) throw new RuntimeException('Comment cannot be empty (max 5000 characters).');
    $sub = null;
    if ((int)($_POST['subtask_id'] ?? 0) > 0) {
        $sub = tk_sub_get((int)$_POST['subtask_id']);
        if (!$sub || (int)$sub['task_id'] !== (int)$t['id']) throw new RuntimeException('Sub-task not found.');
    }
    $now = tk_now();
    $db->beginTransaction();
    $db->prepare('INSERT INTO tk_comments(task_id, subtask_id, uid, body, created_at) VALUES(?,?,?,?,?)')->execute([(int)$t['id'], $sub ? (int)$sub['id'] : null, $u['uid'], $body, $now]);
    $db->prepare('UPDATE tk_tasks SET updated_at = ? WHERE id = ?')->execute([$now, (int)$t['id']]);
    tk_log_sub((int)$t['id'], $sub ? (int)$sub['id'] : null, $u['uid'], 'comment', null, $sub ? $sub['title'] : null, mb_substr($body, 0, 300));
    $db->commit();
    $nq = tk_notify('task_comment', (int)$t['id'], $u['uid'], ['comment' => $body]);
    tk_out(['ok' => true, 'notify' => $nq]);

case 'delete':
    tk_need_post($isPost);
    if (!tk_is_admin($u)) throw new RuntimeException('Only an admin can delete tasks.');
    $t = tk_load_for($u, (int)($_POST['id'] ?? 0));
    $db->prepare('UPDATE tk_tasks SET is_deleted = 1, updated_at = ?, version = version + 1 WHERE id = ?')->execute([tk_now(), (int)$t['id']]);
    tk_log((int)$t['id'], $u['uid'], 'deleted', $t['status'], null, $t['title']);
    tk_out(['ok' => true]);

case 'activity':   // global audit trail (filters + paging), JSON or CSV
    $params = [];
    $w = [tk_scope_sql($u, $params)];
    $from = tk_valid_date($_GET['from'] ?? '') ?: date('Y-m-d', strtotime('-30 days'));
    $to = tk_valid_date($_GET['to'] ?? '') ?: date('Y-m-d');
    $w[] = 'A.created_at >= ? AND A.created_at < ?'; $params[] = $from; $params[] = date('Y-m-d', strtotime($to . ' +1 day'));
    if ((int)($_GET['user'] ?? 0) > 0) { $w[] = 'A.uid = ?'; $params[] = (int)$_GET['user']; }
    if (preg_match('/^[a-z_]{2,30}$/', (string)($_GET['event'] ?? ''))) { $w[] = 'A.event = ?'; $params[] = $_GET['event']; }
    if (preg_match('/^(?:TSK-)?0*(\d+)$/i', trim((string)($_GET['task'] ?? '')), $m)) { $w[] = 'A.task_id = ?'; $params[] = (int)$m[1]; }
    $sqlBase = 'FROM tk_activity A JOIN tk_tasks T ON T.id = A.task_id WHERE ' . implode(' AND ', $w);
    $csv = !empty($_GET['csv']);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per = 50;
    $cnt = $db->prepare('SELECT COUNT(*) ' . $sqlBase);
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();
    $s = $db->prepare('SELECT A.*, T.title ' . $sqlBase . ' ORDER BY A.id DESC' . ($csv ? ' LIMIT 50000' : ' LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per)));
    $s->execute($params);
    $rows = [];
    foreach ($s as $r) $rows[] = ['at' => tk_fmt($r['created_at']), 'task_id' => (int)$r['task_id'], 'key' => tk_key((int)$r['task_id']), 'title' => (string)$r['title'],
        'by' => tk_uname($r['uid']), 'event' => (string)$r['event'], 'text' => tk_activity_text($r), 'detail' => (string)$r['detail'], 'ip' => (string)$r['ip']];
    if ($csv) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="task_activity_' . $from . '_to_' . $to . '.csv"');
        $o = fopen('php://output', 'w');
        fwrite($o, "\xEF\xBB\xBF");
        fputcsv($o, ['Date / Time', 'Task', 'Title', 'User', 'Action', 'Details', 'IP']);
        foreach ($rows as $r) fputcsv($o, [$r['at'], $r['key'], $r['title'], $r['by'], $r['text'], $r['detail'], $r['ip']]);
        exit;
    }
    tk_out(['ok' => true, 'rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => max(1, (int)ceil($total / $per)), 'from' => $from, 'to' => $to]);

case 'export':   // task list as CSV (same filters as the list view)
    $rows = tk_find($u, $_GET, false);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="open_tasks_' . date('Y-m-d') . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    $pm = tk_progress_map(array_column($rows, 'id'));
    fputcsv($o, ['Task', 'Title', 'Stage', 'Checklist', 'Priority', 'Category', 'Assignee', 'Manager', 'Created By', 'Created', 'Due', 'Overdue', 'Started', 'Completed', 'Completed By', 'Resolution / Reason', 'Description']);
    foreach ($rows as $t) {
        $p = tk_public($t, $u);
        $pc = $pm[(int)$t['id']] ?? [0, 0];
        fputcsv($o, [$p['key'], $p['title'], $p['status_label'], $pc[1] ? $pc[0] . '/' . $pc[1] : '', TK_PRIORITIES[$p['priority']] ?? $p['priority'], $p['category'], $p['assignee_name'], $p['manager_name'], $p['created_name'],
            $p['created'], $p['due'], $p['overdue'] ? 'Yes' : '', $p['started'], $p['completed'], $p['completed_by'], $p['resolution'] ?: $p['hold_reason'], $p['description']]);
    }
    exit;

/* ---------- checklist / sub-tasks ---------- */
case 'sub_add':
    tk_need_post($isPost);
    $t = tk_load_for($u, (int)($_POST['id'] ?? 0));
    if (!tk_can_sub($u, $t)) throw new RuntimeException(in_array($t['status'], ['completed', 'cancelled'], true) ? 'Reopen the task to change its checklist.' : 'Only the assignee or the task owners can add sub-tasks.');
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '' || mb_strlen($title) > 250) throw new RuntimeException('Sub-task title is required (max 250 characters).');
    $assignee = tk_valid_user($_POST['assignee_uid'] ?? null);
    $due = tk_valid_date($_POST['due_date'] ?? '');
    $req = !empty($_POST['required']) && tk_can_manage($u, $t) ? 1 : 0;
    $now = tk_now();
    $mx = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM tk_subtasks WHERE task_id = ?'); $mx->execute([(int)$t['id']]);
    $db->beginTransaction();
    $db->prepare('INSERT INTO tk_subtasks(task_id, title, is_required, assignee_uid, due_date, sort_order, created_by, created_at, updated_at) VALUES(?,?,?,?,?,?,?,?,?)')
       ->execute([(int)$t['id'], $title, $req, $assignee, $due, (int)$mx->fetchColumn(), $u['uid'], $now, $now]);
    $sid = (int)$db->lastInsertId();
    tk_log_sub((int)$t['id'], $sid, $u['uid'], 'sub_added', null, $title, trim(($assignee ? 'Assigned to ' . tk_uname($assignee) . '. ' : '') . ($due ? 'Due ' . date('m/d/Y', strtotime($due)) . '.' : '') . ($req ? ' Required.' : '')));
    $db->prepare('UPDATE tk_tasks SET updated_at = ? WHERE id = ?')->execute([$now, (int)$t['id']]);
    $db->commit();
    tk_out(['ok' => true]);

case 'sub_status':   // open / done / na
    tk_need_post($isPost);
    $sub = tk_sub_get((int)($_POST['sub_id'] ?? 0));
    if (!$sub) throw new RuntimeException('Sub-task not found.');
    $t = tk_load_for($u, (int)$sub['task_id']);
    if (!tk_can_sub($u, $t, $sub)) throw new RuntimeException(in_array($t['status'], ['completed', 'cancelled'], true) ? 'Reopen the task to change its checklist.' : 'You cannot update this sub-task.');
    $to = (string)($_POST['status'] ?? '');
    if (!in_array($to, ['open', 'done', 'na'], true) || $to === $sub['status']) tk_out(['ok' => true]);
    $note = trim((string)($_POST['note'] ?? ''));
    if ($to === 'na' && $note === '') throw new RuntimeException('Give a reason for marking this item N/A.');
    if ($to === 'na' && (int)$sub['is_required'] === 1 && !tk_can_manage($u, $t)) throw new RuntimeException('Only the task manager can mark a required item N/A.');
    $now = tk_now();
    $db->beginTransaction();
    $db->prepare('UPDATE tk_subtasks SET status = ?, note = ?, done_by = ?, done_at = ?, updated_at = ? WHERE id = ?')
       ->execute([$to, $to === 'na' ? mb_substr($note, 0, 500) : ($to === 'done' && $note !== '' ? mb_substr($note, 0, 500) : null), $to === 'open' ? null : $u['uid'], $to === 'open' ? null : $now, $now, (int)$sub['id']]);
    tk_log_sub((int)$t['id'], (int)$sub['id'], $u['uid'], $to === 'done' ? 'sub_done' : ($to === 'na' ? 'sub_na' : 'sub_reopened'), $sub['status'], $sub['title'], $note);
    $db->prepare('UPDATE tk_tasks SET updated_at = ? WHERE id = ?')->execute([$now, (int)$t['id']]);
    $db->commit();
    [$pd, $pt] = tk_progress((int)$t['id']);
    tk_out(['ok' => true, 'check' => [$pd, $pt]]);

case 'sub_update':
    tk_need_post($isPost);
    $sub = tk_sub_get((int)($_POST['sub_id'] ?? 0));
    if (!$sub) throw new RuntimeException('Sub-task not found.');
    $t = tk_load_for($u, (int)$sub['task_id']);
    if (!tk_can_sub($u, $t, $sub)) throw new RuntimeException('You cannot edit this sub-task.');
    $title = trim((string)($_POST['title'] ?? $sub['title']));
    if ($title === '' || mb_strlen($title) > 250) throw new RuntimeException('Sub-task title is required (max 250 characters).');
    $assignee = array_key_exists('assignee_uid', $_POST) ? tk_valid_user($_POST['assignee_uid']) : ($sub['assignee_uid'] ? (int)$sub['assignee_uid'] : null);
    $due = array_key_exists('due_date', $_POST) ? tk_valid_date($_POST['due_date']) : $sub['due_date'];
    $req = tk_can_manage($u, $t) && array_key_exists('required', $_POST) ? (!empty($_POST['required']) ? 1 : 0) : (int)$sub['is_required'];
    $ch = [];
    if ($title !== $sub['title']) $ch[] = 'Renamed from "' . $sub['title'] . '"';
    if ((int)$assignee !== (int)$sub['assignee_uid']) $ch[] = 'Assignee: ' . (tk_uname($sub['assignee_uid']) ?: 'none') . ' -> ' . (tk_uname($assignee) ?: 'none');
    if ((string)$due !== (string)$sub['due_date']) $ch[] = 'Due: ' . ($sub['due_date'] ? date('m/d/Y', strtotime($sub['due_date'])) : 'none') . ' -> ' . ($due ? date('m/d/Y', strtotime($due)) : 'none');
    if ($req !== (int)$sub['is_required']) $ch[] = $req ? 'Marked required' : 'No longer required';
    if (!$ch) tk_out(['ok' => true]);
    $now = tk_now();
    $db->beginTransaction();
    $db->prepare('UPDATE tk_subtasks SET title = ?, assignee_uid = ?, due_date = ?, is_required = ?, updated_at = ? WHERE id = ?')->execute([$title, $assignee, $due, $req, $now, (int)$sub['id']]);
    tk_log_sub((int)$t['id'], (int)$sub['id'], $u['uid'], 'sub_edited', null, $title, implode('. ', $ch));
    $db->commit();
    tk_out(['ok' => true]);

case 'sub_delete':
    tk_need_post($isPost);
    $sub = tk_sub_get((int)($_POST['sub_id'] ?? 0));
    if (!$sub) throw new RuntimeException('Sub-task not found.');
    $t = tk_load_for($u, (int)$sub['task_id']);
    if (!tk_can_manage($u, $t) || in_array($t['status'], ['completed', 'cancelled'], true)) throw new RuntimeException('Only the task owners can remove sub-tasks (and only while the task is open).');
    $db->beginTransaction();
    $db->prepare('UPDATE tk_subtasks SET is_deleted = 1, updated_at = ? WHERE id = ?')->execute([tk_now(), (int)$sub['id']]);
    tk_log_sub((int)$t['id'], (int)$sub['id'], $u['uid'], 'sub_deleted', $sub['status'], $sub['title']);
    $db->commit();
    tk_out(['ok' => true]);

case 'sub_move':   // up / down
    tk_need_post($isPost);
    $sub = tk_sub_get((int)($_POST['sub_id'] ?? 0));
    if (!$sub) throw new RuntimeException('Sub-task not found.');
    $t = tk_load_for($u, (int)$sub['task_id']);
    if (!tk_can_sub($u, $t)) throw new RuntimeException('You cannot reorder this checklist.');
    $list = array_map(fn($x) => (int)$x['id'], tk_subtasks((int)$t['id']));
    $i = array_search((int)$sub['id'], $list, true);
    $j = ($_POST['dir'] ?? '') === 'up' ? $i - 1 : $i + 1;
    if ($i !== false && isset($list[$j])) {
        [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
        $st = $db->prepare('UPDATE tk_subtasks SET sort_order = ? WHERE id = ?');
        foreach ($list as $k => $sid) $st->execute([$k + 1, $sid]);
    }
    tk_out(['ok' => true]);

case 'checklist_apply':
    tk_need_post($isPost);
    $t = tk_load_for($u, (int)($_POST['id'] ?? 0));
    if (!tk_can_sub($u, $t)) throw new RuntimeException('You cannot change this checklist.');
    $db->beginTransaction();
    $n = tk_apply_checklist((int)$t['id'], (string)$t['category'], $u['uid']);
    $db->commit();
    tk_out(['ok' => true, 'added' => $n]);

/* ---------- checklist templates (admins & managers) ---------- */
case 'tpl_list':
    $rows = tk_tpl_list(null, true);
    $by = [];
    foreach (TK_CATEGORIES as $c) $by[$c] = [];
    foreach ($rows as $r) $by[$r['category']][] = ['id' => (int)$r['id'], 'title' => (string)$r['title'], 'required' => (int)$r['is_required'] === 1];
    tk_out(['ok' => true, 'templates' => $by, 'can_edit' => tk_is_mgr($u)]);

case 'tpl_save':
    tk_need_post($isPost);
    if (!tk_is_mgr($u)) throw new RuntimeException('Only admins and managers can edit checklist templates.');
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '' || mb_strlen($title) > 250) throw new RuntimeException('Checklist item is required (max 250 characters).');
    $req = !empty($_POST['required']) ? 1 : 0;
    if ((int)($_POST['tpl_id'] ?? 0) > 0) {
        $db->prepare('UPDATE tk_checklist_tpl SET title = ?, is_required = ?, updated_at = ? WHERE id = ? AND active = 1')->execute([$title, $req, tk_now(), (int)$_POST['tpl_id']]);
    } else {
        $cat = (string)($_POST['category'] ?? '');
        if (!in_array($cat, TK_CATEGORIES, true)) throw new RuntimeException('Unknown category.');
        $mx = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM tk_checklist_tpl WHERE category = ?'); $mx->execute([$cat]);
        $db->prepare('INSERT INTO tk_checklist_tpl(category, title, is_required, sort_order, created_by, created_at) VALUES(?,?,?,?,?,?)')->execute([$cat, $title, $req, (int)$mx->fetchColumn(), $u['uid'], tk_now()]);
    }
    tk_out(['ok' => true]);

case 'tpl_delete':
    tk_need_post($isPost);
    if (!tk_is_mgr($u)) throw new RuntimeException('Only admins and managers can edit checklist templates.');
    $db->prepare('UPDATE tk_checklist_tpl SET active = 0, updated_at = ? WHERE id = ?')->execute([tk_now(), (int)($_POST['tpl_id'] ?? 0)]);
    tk_out(['ok' => true]);

case 'tpl_move':
    tk_need_post($isPost);
    if (!tk_is_mgr($u)) throw new RuntimeException('Only admins and managers can edit checklist templates.');
    $q = $db->prepare('SELECT category FROM tk_checklist_tpl WHERE id = ? AND active = 1'); $q->execute([(int)($_POST['tpl_id'] ?? 0)]);
    $cat = $q->fetchColumn();
    if ($cat !== false) {
        $list = array_map(fn($x) => (int)$x['id'], tk_tpl_list($cat));
        $i = array_search((int)$_POST['tpl_id'], $list, true);
        $j = ($_POST['dir'] ?? '') === 'up' ? $i - 1 : $i + 1;
        if ($i !== false && isset($list[$j])) {
            [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
            $st = $db->prepare('UPDATE tk_checklist_tpl SET sort_order = ? WHERE id = ?');
            foreach ($list as $k => $id) $st->execute([$k + 1, $id]);
        }
    }
    tk_out(['ok' => true]);

/* ---------- notifications ---------- */
case 'notify_run':   // called by the board right after an action; sends what is due
    tk_need_post($isPost);
    @set_time_limit(60);
    ignore_user_abort(true);
    tk_out(['ok' => true, 'result' => tk_notify_run(15, 25)]);

case 'nsettings_get':
    if (!tk_is_admin($u)) throw new RuntimeException('Administrator permission required.');
    $s = tk_nsettings();
    em_schema();
    $e = em_settings();
    tk_out(['ok' => true, 'settings' => [
        'email_enabled' => (int)$s['email_enabled'], 'chat_enabled' => (int)$s['chat_enabled'], 'smtp_mode' => $s['smtp_mode'],
        'smtp_host' => $s['smtp_host'], 'smtp_port' => (int)$s['smtp_port'], 'smtp_secure' => $s['smtp_secure'], 'smtp_email' => (string)$s['smtp_email'],
        'smtp_from_name' => $s['smtp_from_name'], 'has_password' => !empty($s['smtp_password_enc']), 'email_hourly_limit' => (int)$s['email_hourly_limit'],
        'skip_actor' => (int)$s['skip_actor'], 'group_ids' => $s['group_ids'], 'rules' => $s['rules'], 'updated' => tk_fmt($s['updated_at']), 'updated_by' => tk_uname($s['updated_by'])],
        'shared_smtp' => ['email' => (string)($e['smtp_email'] ?? ''), 'ready' => filter_var((string)($e['smtp_email'] ?? ''), FILTER_VALIDATE_EMAIL) && !empty($e['smtp_password_enc'])],
        'groups' => array_map(fn($g) => ['id' => (int)$g['id'], 'name' => (string)$g['name'], 'is_default' => (int)$g['is_default']], em_chat_groups(true)),
        'events' => TK_EVENTS, 'placeholders' => TK_PLACEHOLDERS, 'defaults' => tk_default_rules()]);

case 'nsettings_save':
    tk_need_post($isPost);
    if (!tk_is_admin($u)) throw new RuntimeException('Administrator permission required.');
    // the page sends everything as one base64 + reversed JSON field so Bluehost Mod_Security does not block templates/URLs
    $raw = base64_decode(strrev((string)($_POST['payload_x'] ?? '')), true);
    $in = $raw === false ? null : json_decode($raw, true);
    if (!is_array($in)) throw new RuntimeException('Settings could not be read. Refresh the page and try again.');
    $s = tk_nsettings();
    $mode = ($in['smtp_mode'] ?? '') === 'custom' ? 'custom' : 'shared';
    $email = trim((string)($in['smtp_email'] ?? ''));
    $pass = (string)($in['smtp_password'] ?? '');
    if ($mode === 'custom') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email address for the task SMTP account.');
        if ($pass === '' && empty($s['smtp_password_enc'])) throw new RuntimeException('Enter the SMTP password (Gmail: an App Password).');
        if (!preg_match('/^[A-Za-z0-9.-]{3,150}$/', (string)($in['smtp_host'] ?? ''))) throw new RuntimeException('Invalid SMTP server name.');
    }
    $rules = [];
    $def = tk_default_rules();
    $validGroups = array_map(fn($g) => (int)$g['id'], em_chat_groups(true));
    foreach ($def as $ev => $d) {
        $r = is_array($in['rules'][$ev] ?? null) ? $in['rules'][$ev] : [];
        $cc = [];
        foreach (preg_split('/[,;\s]+/', (string)($r['cc'] ?? '')) as $a) { $a = trim($a); if ($a === '') continue; if (!filter_var($a, FILTER_VALIDATE_EMAIL)) throw new RuntimeException(TK_EVENTS[$ev] . ': "' . $a . '" is not a valid email address.'); $cc[] = $a; }
        $rules[$ev] = ['email' => !empty($r['email']) ? 1 : 0, 'chat_on' => !empty($r['chat_on']) ? 1 : 0,
            'to' => array_values(array_intersect(['assignee', 'manager', 'creator'], (array)($r['to'] ?? []))),
            'cc' => implode(', ', $cc), 'groups' => array_values(array_intersect(array_map('intval', (array)($r['groups'] ?? [])), $validGroups)),
            'subject' => mb_substr(trim((string)($r['subject'] ?? '')) ?: $d['subject'], 0, 300),
            'body' => trim((string)($r['body'] ?? '')) ?: $d['body'], 'chat' => trim((string)($r['chat_tpl'] ?? '')) ?: $d['chat']];
    }
    $groups = array_values(array_intersect(array_map('intval', (array)($in['group_ids'] ?? [])), $validGroups));
    if (!empty($in['chat_enabled']) && !$groups) throw new RuntimeException('Select at least one Google Chat group for task notifications (or switch Google Chat off).');
    $db->prepare('UPDATE tk_settings SET email_enabled=?, chat_enabled=?, smtp_mode=?, smtp_host=?, smtp_port=?, smtp_secure=?, smtp_email=?, smtp_from_name=?,
        smtp_password_enc = COALESCE(?, smtp_password_enc), email_hourly_limit=?, skip_actor=?, chat_groups=?, rules_json=?, updated_at=?, updated_by=? WHERE id = 1')
       ->execute([!empty($in['email_enabled']) ? 1 : 0, !empty($in['chat_enabled']) ? 1 : 0, $mode, (string)($in['smtp_host'] ?? 'smtp.gmail.com') ?: 'smtp.gmail.com',
           max(1, min(65535, (int)($in['smtp_port'] ?? 587))), in_array($in['smtp_secure'] ?? '', ['tls', 'ssl', 'none'], true) ? $in['smtp_secure'] : 'tls',
           $email ?: null, mb_substr(trim((string)($in['smtp_from_name'] ?? '')) ?: 'BATS Tasks', 0, 150), $pass !== '' ? em_encrypt($pass) : null,
           max(1, min(500, (int)($in['email_hourly_limit'] ?? 40))), !empty($in['skip_actor']) ? 1 : 0, implode(',', $groups),
           json_encode($rules, JSON_UNESCAPED_UNICODE), tk_now(), $u['uid']]);
    tk_out(['ok' => true]);

case 'notify_test_email':
    tk_need_post($isPost);
    if (!tk_is_admin($u)) throw new RuntimeException('Administrator permission required.');
    $to = trim((string)($_POST['to'] ?? ''));
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email address to send the test to.');
    $s = tk_nsettings();
    $smtp = tk_smtp($s);
    $m = tk_mailer($smtp);
    tk_send_email($m, $to, '[BATS] Test - task notifications', "Hi,\n\nThis is a **test email** from BATS Open Tasks.\nSent through **" . $smtp[3] . "** (" . $smtp[0] . ").\n\n- BATS");
    $m->smtpClose();
    tk_out(['ok' => true, 'result' => 'Test email sent to ' . $to . ' from ' . $smtp[3]]);

case 'notify_test_chat':
    tk_need_post($isPost);
    if (!tk_is_admin($u)) throw new RuntimeException('Administrator permission required.');
    [$hook, $gname] = em_group_webhook((int)($_POST['group_id'] ?? 0) ?: null);
    em_gchat_post($hook, "*[TEST] BATS Open Tasks*\nTask notifications will be posted in this group (" . $gname . ").");
    tk_out(['ok' => true, 'result' => 'Test message posted to "' . $gname . '"']);

case 'notify_log':
    if (!tk_is_admin($u)) throw new RuntimeException('Administrator permission required.');
    tk_notify_schema();
    $w = '1=1'; $p = [];
    if (in_array($_GET['status'] ?? '', ['pending', 'processing', 'sent', 'failed', 'skipped'], true)) { $w = 'N.status = ?'; $p[] = $_GET['status']; }
    $q = $db->prepare("SELECT N.*, T.title FROM tk_notify N LEFT JOIN tk_tasks T ON T.id = N.task_id WHERE $w ORDER BY N.id DESC LIMIT 200");
    $q->execute($p);
    $rows = [];
    foreach ($q as $r) $rows[] = ['id' => (int)$r['id'], 'key' => tk_key((int)$r['task_id']), 'task_id' => (int)$r['task_id'], 'title' => (string)$r['title'],
        'event' => TK_EVENTS[$r['event']] ?? $r['event'], 'channel' => $r['channel'] === 'chat' ? 'Google Chat' : 'Email', 'to' => (string)($r['target_name'] ?: $r['target']) . ($r['channel'] === 'email' ? ' <' . $r['target'] . '>' : ''),
        'subject' => (string)$r['subject'], 'body' => (string)$r['body'], 'status' => $r['status'], 'attempts' => (int)$r['attempts'], 'error' => (string)$r['last_error'],
        'created' => tk_fmt($r['created_at']), 'sent' => tk_fmt($r['sent_at']), 'by' => tk_uname($r['actor_uid'])];
    $c = $db->query("SELECT status, COUNT(*) n FROM tk_notify GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    tk_out(['ok' => true, 'rows' => $rows, 'counts' => array_map('intval', $c)]);

case 'notify_retry':
    tk_need_post($isPost);
    if (!tk_is_admin($u)) throw new RuntimeException('Administrator permission required.');
    $id = (int)($_POST['id'] ?? 0);
    $s = $db->prepare("UPDATE tk_notify SET status = 'pending', attempts = 0, available_at = ?, last_error = NULL WHERE status IN ('failed','skipped')" . ($id ? ' AND id = ?' : ''));
    $s->execute($id ? [tk_now(), $id] : [tk_now()]);
    $n = $s->rowCount();
    tk_out(['ok' => true, 'requeued' => $n, 'result' => tk_notify_run(15, 25)]);

default:
    tk_out(['ok' => false, 'error' => 'Unknown action'], 400);
}
