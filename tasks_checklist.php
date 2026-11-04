<?php
/*
 * BATS Open Tasks - category checklists & sub-tasks (2026-11)
 *
 *  tk_checklist_tpl   checklist template items per category (managed by admins / managers)
 *  tk_subtasks        the checklist / sub-tasks of one task (copied from the template, plus any added by hand)
 *  tk_comments.subtask_id   comments can belong to the task or to one sub-task
 *  tk_activity.subtask_id   every sub-task action is in the task's activity log
 *
 * Required sub-tasks must be Done (or marked N/A with a reason) before the task can go to
 * In Review or Completed.
 */
require_once __DIR__ . '/tasks_lib.php';

function tk_has_col(string $table, string $col): bool {
    $q = tk_db()->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $q->execute([$table, $col]);
    return (int)$q->fetchColumn() > 0;
}
function tk_checklist_schema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    tk_schema();
    $db = tk_db();
    $db->exec("CREATE TABLE IF NOT EXISTS tk_checklist_tpl (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(60) NOT NULL,
        title VARCHAR(250) NOT NULL,
        is_required TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_by INT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        KEY idx_tkt_cat (category, active, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS tk_subtasks (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        tpl_id INT NULL,
        title VARCHAR(250) NOT NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'open',
        is_required TINYINT(1) NOT NULL DEFAULT 0,
        assignee_uid INT NULL,
        due_date DATE NULL,
        note VARCHAR(500) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_by INT NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        done_by INT NULL,
        done_at DATETIME NULL,
        is_deleted TINYINT(1) NOT NULL DEFAULT 0,
        KEY idx_tks_task (task_id, is_deleted, sort_order),
        KEY idx_tks_assignee (assignee_uid, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!tk_has_col('tk_comments', 'subtask_id')) $db->exec('ALTER TABLE tk_comments ADD COLUMN subtask_id INT NULL, ADD KEY idx_tkc_sub (subtask_id)');
    if (!tk_has_col('tk_activity', 'subtask_id')) $db->exec('ALTER TABLE tk_activity ADD COLUMN subtask_id INT NULL');
}

/* ---------------- templates ---------------- */
function tk_tpl_list(?string $category = null, bool $activeOnly = true): array {
    tk_checklist_schema();
    $w = $activeOnly ? 'active = 1' : '1=1';
    $p = [];
    if ($category !== null) { $w .= ' AND category = ?'; $p[] = $category; }
    $q = tk_db()->prepare("SELECT * FROM tk_checklist_tpl WHERE $w ORDER BY category, sort_order, id");
    $q->execute($p);
    return $q->fetchAll();
}

/** Copy the category's checklist into a task (items already copied are skipped). Returns how many were added. */
function tk_apply_checklist(int $taskId, string $category, int $uid): int {
    tk_checklist_schema();
    $db = tk_db();
    $have = $db->prepare('SELECT tpl_id FROM tk_subtasks WHERE task_id = ? AND is_deleted = 0 AND tpl_id IS NOT NULL');
    $have->execute([$taskId]);
    $existing = array_map('intval', $have->fetchAll(PDO::FETCH_COLUMN));
    $max = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM tk_subtasks WHERE task_id = ?');
    $max->execute([$taskId]);
    $order = (int)$max->fetchColumn();
    $ins = $db->prepare('INSERT INTO tk_subtasks(task_id, tpl_id, title, is_required, sort_order, created_by, created_at, updated_at) VALUES(?,?,?,?,?,?,?,?)');
    $n = 0; $now = tk_now(); $titles = [];
    foreach (tk_tpl_list($category) as $t) {
        if (in_array((int)$t['id'], $existing, true)) continue;
        $ins->execute([$taskId, (int)$t['id'], $t['title'], (int)$t['is_required'], ++$order, $uid, $now, $now]);
        $n++; $titles[] = $t['title'];
    }
    if ($n) tk_log_sub($taskId, null, $uid, 'checklist', null, (string)$n, $category . ' checklist: ' . implode(', ', $titles));
    return $n;
}

/* ---------------- sub-tasks ---------------- */
function tk_log_sub(int $task, ?int $sub, int $uid, string $event, $from = null, $to = null, string $detail = ''): void {
    tk_db()->prepare('INSERT INTO tk_activity(task_id, subtask_id, uid, event, from_val, to_val, detail, ip, created_at) VALUES(?,?,?,?,?,?,?,?,?)')
        ->execute([$task, $sub, $uid, $event, $from === null ? null : mb_substr((string)$from, 0, 255), $to === null ? null : mb_substr((string)$to, 0, 255),
                   $detail, substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45), tk_now()]);
}
function tk_subtasks(int $taskId): array {
    tk_checklist_schema();
    $q = tk_db()->prepare('SELECT S.*, (SELECT COUNT(*) FROM tk_comments C WHERE C.subtask_id = S.id) AS ncomments FROM tk_subtasks S WHERE S.task_id = ? AND S.is_deleted = 0 ORDER BY S.sort_order, S.id');
    $q->execute([$taskId]);
    return $q->fetchAll();
}
function tk_sub_get(int $id): ?array {
    $q = tk_db()->prepare('SELECT * FROM tk_subtasks WHERE id = ? AND is_deleted = 0');
    $q->execute([$id]);
    return $q->fetch() ?: null;
}
function tk_sub_public(array $s, array $u, array $t): array {
    $today = date('Y-m-d');
    return ['id' => (int)$s['id'], 'title' => (string)$s['title'], 'status' => (string)$s['status'], 'required' => (int)$s['is_required'] === 1,
        'from_tpl' => $s['tpl_id'] !== null, 'assignee_uid' => $s['assignee_uid'] ? (int)$s['assignee_uid'] : null, 'assignee_name' => tk_uname($s['assignee_uid']),
        'due_date' => (string)$s['due_date'], 'due' => $s['due_date'] ? date('m/d/Y', strtotime($s['due_date'])) : '',
        'overdue' => $s['status'] === 'open' && $s['due_date'] && $s['due_date'] < $today, 'note' => (string)$s['note'],
        'done_by' => tk_uname($s['done_by']), 'done_at' => tk_fmt($s['done_at']), 'created_by' => tk_uname($s['created_by']), 'created' => tk_fmt($s['created_at']),
        'comments' => (int)($s['ncomments'] ?? 0), 'can_edit' => tk_can_sub($u, $t, $s), 'can_delete' => tk_can_manage($u, $t)];
}
/** Work on a sub-task: task owners, the task assignee, or the person the sub-task is assigned to. Closed tasks are read-only. */
function tk_can_sub(array $u, array $t, ?array $s = null): bool {
    if (in_array($t['status'], ['completed', 'cancelled'], true)) return false;
    if (tk_can_manage($u, $t) || (int)$t['assignee_uid'] === $u['uid']) return true;
    return $s !== null && (int)$s['assignee_uid'] === $u['uid'];
}
/** [done (incl. N/A), total, open required titles] for a task. */
function tk_progress(int $taskId): array {
    $q = tk_db()->prepare("SELECT title, status, is_required FROM tk_subtasks WHERE task_id = ? AND is_deleted = 0");
    $q->execute([$taskId]);
    $done = 0; $total = 0; $openReq = [];
    foreach ($q as $r) {
        $total++;
        if ($r['status'] !== 'open') $done++;
        elseif ((int)$r['is_required'] === 1) $openReq[] = $r['title'];
    }
    return [$done, $total, $openReq];
}
/** Progress for many tasks at once: [task_id => [done, total]]. */
function tk_progress_map(array $ids): array {
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (!$ids) return [];
    tk_checklist_schema();
    $out = [];
    $q = tk_db()->query("SELECT task_id, SUM(status <> 'open') d, COUNT(*) n FROM tk_subtasks WHERE is_deleted = 0 AND task_id IN (" . implode(',', $ids) . ") GROUP BY task_id");
    foreach ($q as $r) $out[(int)$r['task_id']] = [(int)$r['d'], (int)$r['n']];
    return $out;
}
