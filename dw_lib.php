<?php
/*
 * BATS Daily Workflow (2026-11)  -  admin.php?action=dailyworkflow
 * Recurring daily activities per user (e.g. each SM's daily routine), ticked off every day.
 *
 *  dw_activities  one row per activity per user (title, days of week, due time, active period)
 *  dw_checks      one row per activity per day when it was Done or Skipped (who / when / note)
 *  dw_log         audit trail: activity added / edited / stopped, items done / undone / skipped
 *
 * Admin: sees and manages everyone.  Manager: self + the people who report to them (users.rmid).
 * Everyone else: only their own day. Only the owner ticks their own activities.
 * Shares the login, user list and CSRF helpers of Open Tasks (tasks_lib.php).
 */
require_once __DIR__ . '/tasks_lib.php';

const DW_DAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
const DW_SECTIONS = ['Start of day', 'Sourcing', 'Submissions', 'Client follow-up', 'Interviews', 'Reporting', 'End of day', 'Other'];

function dw_schema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $db = tk_db();
    $db->exec("CREATE TABLE IF NOT EXISTS dw_activities (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        uid INT NOT NULL,
        title VARCHAR(250) NOT NULL,
        details TEXT NULL,
        section VARCHAR(60) NOT NULL DEFAULT 'Other',
        days VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5',
        due_time TIME NULL,
        target INT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        start_date DATE NOT NULL,
        end_date DATE NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_by INT NOT NULL,
        created_at DATETIME NOT NULL,
        updated_by INT NULL,
        updated_at DATETIME NULL,
        KEY idx_dwa_user (uid, active, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS dw_checks (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        activity_id INT NOT NULL,
        uid INT NOT NULL,
        work_date DATE NOT NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'done',
        count_done INT NULL,
        note VARCHAR(1000) NULL,
        checked_at DATETIME NOT NULL,
        UNIQUE KEY uq_dwc (activity_id, work_date),
        KEY idx_dwc_user (uid, work_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS dw_log (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        activity_id INT NULL,
        owner_uid INT NOT NULL,
        actor_uid INT NOT NULL,
        work_date DATE NULL,
        event VARCHAR(20) NOT NULL,
        detail TEXT NULL,
        ip VARCHAR(45) NULL,
        created_at DATETIME NOT NULL,
        KEY idx_dwl_owner (owner_uid, created_at),
        KEY idx_dwl_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS dw_meta (k VARCHAR(40) NOT NULL PRIMARY KEY, v VARCHAR(255) NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // 2026-11: activities are now shown in one order set by drag & drop (sort_order).
    // Once, number every person's activities in the order they were shown before (section, then order added).
    if (!$db->query("SELECT COUNT(*) FROM dw_meta WHERE k = 'order_v2'")->fetchColumn()) {
        $st = $db->prepare('UPDATE dw_activities SET sort_order = ? WHERE id = ?');
        $n = []; 
        foreach ($db->query("SELECT id, uid FROM dw_activities ORDER BY uid, FIELD(section, '" . implode("','", DW_SECTIONS) . "'), sort_order, id")->fetchAll() as $r) {
            $n[$r['uid']] = ($n[$r['uid']] ?? 0) + 1;
            $st->execute([$n[$r['uid']], $r['id']]);
        }
        $db->exec("INSERT IGNORE INTO dw_meta(k, v) VALUES('order_v2', NOW())");
    }
}
/**
 * Move an activity in its owner's workflow order: right after $afterId / right before $beforeId (0 = none -> top).
 * If it is dropped between two activities of another section, it joins that section. Returns the new section.
 */
function dw_reorder(array $a, int $afterId, int $beforeId): string {
    $db = tk_db();
    $q = $db->prepare('SELECT id, section FROM dw_activities WHERE uid = ? AND id <> ? ORDER BY sort_order, id');
    $q->execute([(int)$a['uid'], (int)$a['id']]);
    $list = $q->fetchAll(PDO::FETCH_KEY_PAIR);
    $ids = array_map('intval', array_keys($list));
    $idx = 0;
    if ($beforeId && ($p = array_search($beforeId, $ids, true)) !== false) $idx = $p;
    elseif ($afterId && ($p = array_search($afterId, $ids, true)) !== false) $idx = $p + 1;
    $section = (string)$a['section'];
    $prevS = $afterId && isset($list[$afterId]) ? $list[$afterId] : null;
    $nextS = $beforeId && isset($list[$beforeId]) ? $list[$beforeId] : null;
    if ($prevS !== null && $prevS === $nextS && $prevS !== $section) $section = $prevS;
    array_splice($ids, $idx, 0, [(int)$a['id']]);
    $st = $db->prepare('UPDATE dw_activities SET sort_order = ? WHERE id = ?');
    foreach ($ids as $k => $id) $st->execute([$k + 1, $id]);
    if ($section !== $a['section']) $db->prepare('UPDATE dw_activities SET section = ? WHERE id = ?')->execute([$section, (int)$a['id']]);
    return $section;
}
function dw_log(?int $act, int $owner, int $actor, string $event, string $detail = '', ?string $date = null): void {
    tk_db()->prepare('INSERT INTO dw_log(activity_id, owner_uid, actor_uid, work_date, event, detail, ip, created_at) VALUES(?,?,?,?,?,?,?,?)')
        ->execute([$act, $owner, $actor, $date, $event, $detail, substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45), tk_now()]);
}

/* ---------------- who sees whom ---------------- */
/** uids this user may view (self first). Admin: all active users; manager: self + team; others: self. */
function dw_visible_uids(array $u): array {
    if (tk_is_admin($u)) {
        $ids = array_keys(tk_users(true));
        return array_values(array_unique(array_merge([$u['uid']], $ids)));
    }
    if ($u['level'] === 2) return array_values(array_unique(array_merge([$u['uid']], array_filter(tk_team($u), fn($id) => !empty(tk_users()[$id]['active'])))));
    return [$u['uid']];
}
function dw_can_view(array $u, int $uid): bool { return in_array($uid, dw_visible_uids($u), true); }
/** Add / edit / stop activities: admin for anyone, manager for self + team. */
function dw_can_manage(array $u, int $uid): bool { return tk_is_mgr($u) && dw_can_view($u, $uid); }

/* ---------------- schedule ---------------- */
function dw_date(?string $d): string {
    $d = trim((string)$d);
    $t = $d === '' ? time() : strtotime($d);
    return date('Y-m-d', $t === false ? time() : $t);
}
/** Activities scheduled for $uid on $date, plus any that were checked that day (so history never loses a tick). */
function dw_day(int $uid, string $date): array {
    $dow = (int)date('N', strtotime($date));
    $db = tk_db();
    $q = $db->prepare("SELECT A.*, C.status AS c_status, C.note AS c_note, C.checked_at, C.count_done
        FROM dw_activities A LEFT JOIN dw_checks C ON C.activity_id = A.id AND C.work_date = ?
        WHERE A.uid = ? AND (C.id IS NOT NULL OR (A.start_date <= ? AND (A.end_date IS NULL OR A.end_date >= ?) AND FIND_IN_SET(?, A.days) > 0))
        ORDER BY A.sort_order, A.id");
    $q->execute([$date, $uid, $date, $date, (string)$dow]);
    $today = date('Y-m-d');
    $now = date('H:i:s');
    $out = [];
    foreach ($q as $r) {
        $st = $r['c_status'] ?: 'open';
        $late = false;
        if ($st === 'done' && $r['due_time'] && $r['checked_at']) $late = $r['checked_at'] > $date . ' ' . $r['due_time'];
        elseif ($st === 'done' && $r['checked_at']) $late = substr($r['checked_at'], 0, 10) > $date;
        $overdue = $st === 'open' && ($date < $today || ($date === $today && $r['due_time'] && $r['due_time'] < $now));
        $out[] = ['id' => (int)$r['id'], 'title' => (string)$r['title'], 'details' => (string)$r['details'], 'section' => (string)$r['section'],
            'due_time' => $r['due_time'] ? date('g:i A', strtotime($r['due_time'])) : '', 'due_raw' => $r['due_time'] ? substr($r['due_time'], 0, 5) : '',
            'target' => $r['target'] !== null ? (int)$r['target'] : null, 'count_done' => $r['count_done'] !== null ? (int)$r['count_done'] : null,
            'status' => $st, 'note' => (string)$r['c_note'], 'checked_at' => tk_fmt($r['checked_at']), 'late' => $late, 'overdue' => $overdue,
            'active' => (int)$r['active'] === 1, 'days' => array_map('intval', explode(',', (string)$r['days'])), 'owner' => tk_uname($r['uid'])];
    }
    return $out;
}
/** [done, skipped, total] per uid per date for a date range (scheduled activities only + ticks). */
function dw_summary(array $uids, string $from, string $to): array {
    $res = [];
    $db = tk_db();
    if (!$uids) return $res;
    $in = implode(',', array_map('intval', $uids));
    $acts = $db->query("SELECT id, uid, days, start_date, end_date FROM dw_activities WHERE uid IN ($in)")->fetchAll();
    $chk = [];
    $q = $db->prepare("SELECT activity_id, uid, work_date, status FROM dw_checks WHERE uid IN ($in) AND work_date BETWEEN ? AND ?");
    $q->execute([$from, $to]);
    foreach ($q as $c) $chk[$c['activity_id'] . '|' . $c['work_date']] = $c['status'];
    for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
        $dow = (string)date('N', strtotime($d));
        foreach ($acts as $a) {
            $key = $a['id'] . '|' . $d;
            $sched = $a['start_date'] <= $d && (!$a['end_date'] || $a['end_date'] >= $d) && in_array($dow, explode(',', $a['days']), true);
            if (!$sched && !isset($chk[$key])) continue;
            $u = (int)$a['uid'];
            $res[$u][$d] = $res[$u][$d] ?? [0, 0, 0];
            $res[$u][$d][2]++;
            if (($chk[$key] ?? '') === 'done') $res[$u][$d][0]++;
            if (($chk[$key] ?? '') === 'skipped') $res[$u][$d][1]++;
        }
    }
    return $res;
}
function dw_log_text(array $r): string {
    switch ($r['event']) {
        case 'added':    return 'added the activity';
        case 'edited':   return 'edited the activity';
        case 'stopped':  return 'stopped the activity';
        case 'resumed':  return 'restarted the activity';
        case 'done':     return 'marked it done';
        case 'undone':   return 'un-ticked it';
        case 'skipped':  return 'skipped it';
        case 'note':     return 'updated the note';
        case 'reordered': return 'changed the order';
        default:         return (string)$r['event'];
    }
}
