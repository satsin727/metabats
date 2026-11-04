<?php
/*
 * BATS Open Tasks - shared library (2026-11)
 * Tables: tk_tasks, tk_comments, tk_activity  (created automatically on first use,
 * also available as db_tasks_2026-11-04.sql).
 *
 * Workflow (stage -> allowed next stages):
 *   New -> To Do (assigned) -> In Progress -> In Review -> Completed
 *   In Progress / To Do <-> On Hold (reason required)
 *   Any open stage -> Cancelled (reason required); Completed / Cancelled -> Reopen (reason required)
 * Every create, edit, assignment, stage move and comment is written to tk_activity (audit trail).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_helpers.php';

const TK_STAGES = [
    'new'         => ['label' => 'New',         'color' => '#667085', 'board' => true],
    'todo'        => ['label' => 'To Do',       'color' => '#1570ef', 'board' => true],
    'in_progress' => ['label' => 'In Progress', 'color' => '#7a5af8', 'board' => true],
    'on_hold'     => ['label' => 'On Hold',     'color' => '#dc6803', 'board' => true],
    'in_review'   => ['label' => 'In Review',   'color' => '#0e9384', 'board' => true],
    'completed'   => ['label' => 'Completed',   'color' => '#079455', 'board' => true],
    'cancelled'   => ['label' => 'Cancelled',   'color' => '#98a2b3', 'board' => false],
];
const TK_FLOW = [
    'new'         => ['todo', 'in_progress', 'cancelled'],
    'todo'        => ['in_progress', 'on_hold', 'new', 'cancelled'],
    'in_progress' => ['in_review', 'on_hold', 'todo', 'completed', 'cancelled'],
    'on_hold'     => ['in_progress', 'todo', 'cancelled'],
    'in_review'   => ['completed', 'in_progress'],
    'completed'   => ['in_progress'],
    'cancelled'   => ['new'],
];
const TK_PRIORITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];
const TK_CATEGORIES = ['General', 'Recruiting', 'Requirement', 'Client', 'Consultant', 'Onboarding', 'Admin / HR', 'IT / Support', 'Finance', 'Other'];
const TK_LEVELS = [1 => 'Admin', 2 => 'Manager', 3 => 'Recruiter', 4 => 'Sales', 5 => 'Viewer'];

function tk_db(): PDO {
    static $db;
    if (!$db) {
        $db = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        $db->exec('SET NAMES utf8mb4');
    }
    return $db;
}
function tk_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function tk_now(): string { return date('Y-m-d H:i:s'); }
function tk_key(int $id): string { return 'TSK-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT); }
function tk_fmt(?string $dt, string $f = 'm/d/Y g:i A'): string { return ($dt && $dt[0] !== '0') ? date($f, strtotime($dt)) : ''; }

/** Logged-in, active BATS user (same session check as the rest of BATS). */
function tk_user(): array {
    if (empty($_SESSION['id']) || empty($_SESSION['username'])) { http_response_code(403); exit('Login required'); }
    $q = tk_db()->prepare('SELECT uid, name, username, level, rmid, sess, status FROM users WHERE uid=? LIMIT 1');
    $q->execute([(int)$_SESSION['id']]);
    $u = $q->fetch();
    if (!$u || (string)$u['sess'] !== (string)$_SESSION['username'] || (int)$u['status'] !== 1) { http_response_code(403); exit('Not authorized'); }
    $u['uid'] = (int)$u['uid']; $u['level'] = (int)$u['level'];
    return $u;
}
function tk_csrf(): string {
    if (empty($_SESSION['tk_csrf'])) $_SESSION['tk_csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['tk_csrf'];
}
function tk_verify(): void {
    if (!hash_equals(tk_csrf(), (string)($_POST['csrf'] ?? ''))) throw new RuntimeException('Security token expired. Refresh the page and try again.');
}

function tk_schema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $db = tk_db();
    $db->exec("CREATE TABLE IF NOT EXISTS tk_tasks (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT NULL,
        category VARCHAR(60) NOT NULL DEFAULT 'General',
        priority VARCHAR(10) NOT NULL DEFAULT 'medium',
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        created_by INT NOT NULL,
        assignee_uid INT NULL,
        manager_uid INT NULL,
        due_date DATE NULL,
        hold_reason VARCHAR(500) NULL,
        resolution VARCHAR(1000) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        version INT NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        assigned_at DATETIME NULL,
        started_at DATETIME NULL,
        review_at DATETIME NULL,
        completed_at DATETIME NULL,
        completed_by INT NULL,
        cancelled_at DATETIME NULL,
        is_deleted TINYINT(1) NOT NULL DEFAULT 0,
        KEY idx_tk_status (is_deleted, status, sort_order),
        KEY idx_tk_assignee (assignee_uid, status),
        KEY idx_tk_manager (manager_uid, status),
        KEY idx_tk_creator (created_by),
        KEY idx_tk_due (due_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS tk_comments (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        uid INT NOT NULL,
        body TEXT NOT NULL,
        created_at DATETIME NOT NULL,
        KEY idx_tkc_task (task_id, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS tk_activity (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        uid INT NOT NULL,
        event VARCHAR(30) NOT NULL,
        from_val VARCHAR(255) NULL,
        to_val VARCHAR(255) NULL,
        detail TEXT NULL,
        ip VARCHAR(45) NULL,
        created_at DATETIME NOT NULL,
        KEY idx_tka_task (task_id, id),
        KEY idx_tka_created (created_at),
        KEY idx_tka_user (uid, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Active users for the dropdowns: [uid => ['name'=>, 'level'=>, 'rmid'=>]] (includes inactive names for history). */
function tk_users(bool $activeOnly = false): array {
    static $all = null;
    if ($all === null) {
        $all = [];
        foreach (tk_db()->query('SELECT uid, name, username, level, rmid, status FROM users ORDER BY name') as $r) {
            $all[(int)$r['uid']] = ['uid' => (int)$r['uid'], 'name' => trim((string)$r['name']) !== '' ? (string)$r['name'] : (string)$r['username'],
                'level' => (int)$r['level'], 'rmid' => (int)$r['rmid'], 'active' => (int)$r['status'] === 1];
        }
    }
    return $activeOnly ? array_filter($all, fn($x) => $x['active']) : $all;
}
function tk_uname($uid): string {
    if (!$uid) return '';
    $all = tk_users();
    return $all[(int)$uid]['name'] ?? ('User #' . (int)$uid);
}

/* ---------------- permissions ---------------- */
function tk_is_admin(array $u): bool { return $u['level'] === 1; }
function tk_is_mgr(array $u): bool { return $u['level'] === 1 || $u['level'] === 2; }
/** uids of the people who report to this manager (users.rmid). */
function tk_team(array $u): array {
    return array_keys(array_filter(tk_users(), fn($x) => $x['rmid'] === $u['uid'] && $x['uid'] !== $u['uid']));
}
/** SQL condition (on alias T) for the tasks this user may see. */
function tk_scope_sql(array $u, array &$params): string {
    if (tk_is_admin($u)) return '1=1';
    $ids = [$u['uid']];
    if ($u['level'] === 2) $ids = array_merge($ids, tk_team($u));
    $in = implode(',', array_map('intval', $ids));
    $params[] = $u['uid']; $params[] = $u['uid'];
    return "(T.created_by = ? OR T.manager_uid = ? OR T.assignee_uid IN ($in))";
}
function tk_can_view(array $u, array $t): bool {
    if (tk_is_admin($u)) return true;
    if (in_array($u['uid'], [(int)$t['created_by'], (int)$t['manager_uid'], (int)$t['assignee_uid']], true)) return true;
    return $u['level'] === 2 && $t['assignee_uid'] && in_array((int)$t['assignee_uid'], tk_team($u), true);
}
/** Edit details / assign / reassign: admin, the task's manager, the creator, or the assignee's reporting manager. */
function tk_can_manage(array $u, array $t): bool {
    if (tk_is_admin($u) || (int)$t['manager_uid'] === $u['uid'] || (int)$t['created_by'] === $u['uid']) return true;
    return $u['level'] === 2 && $t['assignee_uid'] && in_array((int)$t['assignee_uid'], tk_team($u), true);
}
/** Can approve (In Review -> Completed, or complete a task that has a manager). */
function tk_can_approve(array $u, array $t): bool {
    if (tk_is_admin($u)) return true;
    if ($t['manager_uid']) return (int)$t['manager_uid'] === $u['uid'];
    return tk_can_manage($u, $t);
}
/** Stages this user may move the task to right now. */
function tk_next_stages(array $u, array $t): array {
    $isAssignee = (int)$t['assignee_uid'] === $u['uid'];
    $mg = tk_can_manage($u, $t);
    if (!$mg && !$isAssignee) return [];
    $out = [];
    foreach (TK_FLOW[$t['status']] ?? [] as $to) {
        if ($to === 'cancelled' && !$mg) continue;                                          // only owners cancel
        if ($to === 'completed' && !tk_can_approve($u, $t)) continue;                       // needs the manager's approval
        if ($t['status'] === 'in_review' && $to === 'in_progress' && !tk_can_approve($u, $t)) continue; // rework = reviewer's call
        if (in_array($t['status'], ['completed', 'cancelled'], true) && !$mg) continue;     // reopen = owners
        if ($to === 'todo' && !$t['assignee_uid']) continue;                                // To Do needs an assignee
        $out[] = $to;
    }
    return $out;
}
function tk_reason_needed(string $from, string $to): bool {
    return $to === 'on_hold' || $to === 'cancelled' || in_array($from, ['completed', 'cancelled'], true) || ($from === 'in_review' && $to === 'in_progress');
}

/* ---------------- data access ---------------- */
function tk_get(int $id): ?array {
    $q = tk_db()->prepare('SELECT * FROM tk_tasks WHERE id=? AND is_deleted=0');
    $q->execute([$id]);
    $t = $q->fetch();
    return $t ?: null;
}
function tk_log(int $task, int $uid, string $event, $from = null, $to = null, string $detail = ''): void {
    tk_db()->prepare('INSERT INTO tk_activity(task_id, uid, event, from_val, to_val, detail, ip, created_at) VALUES(?,?,?,?,?,?,?,?)')
        ->execute([$task, $uid, $event, $from === null ? null : mb_substr((string)$from, 0, 255), $to === null ? null : mb_substr((string)$to, 0, 255),
                   $detail, substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45), tk_now()]);
}
function tk_stage_label(?string $s): string { return TK_STAGES[$s]['label'] ?? (string)$s; }

/** Task as sent to the browser (names resolved, flags computed). */
function tk_public(array $t, array $u): array {
    $today = date('Y-m-d');
    $open = !in_array($t['status'], ['completed', 'cancelled'], true);
    return [
        'id' => (int)$t['id'], 'key' => tk_key((int)$t['id']), 'title' => (string)$t['title'], 'description' => (string)$t['description'],
        'category' => (string)$t['category'], 'priority' => (string)$t['priority'], 'status' => (string)$t['status'],
        'status_label' => tk_stage_label($t['status']), 'version' => (int)$t['version'],
        'created_by' => (int)$t['created_by'], 'created_name' => tk_uname($t['created_by']),
        'assignee_uid' => $t['assignee_uid'] ? (int)$t['assignee_uid'] : null, 'assignee_name' => tk_uname($t['assignee_uid']),
        'manager_uid' => $t['manager_uid'] ? (int)$t['manager_uid'] : null, 'manager_name' => tk_uname($t['manager_uid']),
        'due_date' => (string)$t['due_date'], 'due' => $t['due_date'] ? date('m/d/Y', strtotime($t['due_date'])) : '',
        'overdue' => $open && $t['due_date'] && $t['due_date'] < $today, 'due_today' => $open && $t['due_date'] === $today,
        'hold_reason' => (string)$t['hold_reason'], 'resolution' => (string)$t['resolution'],
        'created' => tk_fmt($t['created_at']), 'updated' => tk_fmt($t['updated_at']), 'assigned' => tk_fmt($t['assigned_at']),
        'started' => tk_fmt($t['started_at']), 'review' => tk_fmt($t['review_at']), 'completed' => tk_fmt($t['completed_at']),
        'completed_by' => tk_uname($t['completed_by']), 'cancelled' => tk_fmt($t['cancelled_at']),
        'age_days' => (int)floor((time() - strtotime($t['created_at'])) / 86400),
        'comments' => isset($t['ncomments']) ? (int)$t['ncomments'] : null,
        'can_manage' => tk_can_manage($u, $t), 'can_delete' => tk_is_admin($u),
        'next' => tk_next_stages($u, $t),
    ];
}

/** Human-readable line for one activity row. */
function tk_activity_text(array $a): string {
    $f = (string)$a['from_val']; $t = (string)$a['to_val'];
    switch ($a['event']) {
        case 'created':   return 'created the task';
        case 'status':    return 'moved it from ' . tk_stage_label($f) . ' to ' . tk_stage_label($t);
        case 'assigned':  return $f === '' ? 'assigned it to ' . $t : ($t === '' ? 'removed assignee ' . $f : 'reassigned it from ' . $f . ' to ' . $t);
        case 'manager':   return $f === '' ? 'set manager to ' . $t : ($t === '' ? 'removed manager ' . $f : 'changed manager from ' . $f . ' to ' . $t);
        case 'priority':  return 'changed priority from ' . (TK_PRIORITIES[$f] ?? $f) . ' to ' . (TK_PRIORITIES[$t] ?? $t);
        case 'due_date':  return $t === '' ? 'removed the due date' : ($f === '' ? 'set due date ' . $t : 'changed due date from ' . $f . ' to ' . $t);
        case 'title':     return 'renamed the task';
        case 'description': return 'updated the description';
        case 'category':  return 'changed category from ' . $f . ' to ' . $t;
        case 'comment':   return 'commented';
        case 'deleted':   return 'deleted the task';
        default:          return (string)$a['event'];
    }
}
