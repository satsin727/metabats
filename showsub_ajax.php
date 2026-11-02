<?php
/*
 * Data for admin.php?action=showsub (showsub.php) - Submissions list.
 * Returns ONE PAGE of submissions as JSON for bootstrap-table (server-side pagination)
 * instead of building all 10,000+ rows (and 4 extra queries per row) in one page.
 *
 * Same rules as before:
 *   - ?smid=X          : submissions of recruiter X, last 30 days
 *   - level 1 (admin)  : all submissions, last 60 days
 *   - level 2 (manager): all submissions of existing users, last 30 days
 *   - level 3          : own submissions, last 30 days
 *   - "last N days" is counted back from ?date=YYYY-MM-DD (default: today)
 *   - newest submission first
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function ss_out($total, $rows, $code = 200)
{
    http_response_code($code);
    echo json_encode(array('total' => $total, 'rows' => $rows));
    exit;
}

if (empty($_SESSION['id']) || empty($_SESSION['username'])) {
    ss_out(0, array(), 401);
}
$conn = bats_db();
$st = $conn->prepare('SELECT uid, level, sess FROM users WHERE uid = ? LIMIT 1');
$st->execute(array($_SESSION['id']));
$me = $st->fetch(PDO::FETCH_ASSOC);
if (!$me || (string)$me['sess'] !== (string)$_SESSION['username']) {
    ss_out(0, array(), 401);
}
$level = (int)$me['level'];
if (!in_array($level, array(1, 2, 3), true)) {
    ss_out(0, array(), 403);
}
$showSM = ($level === 1 || $level === 2);

// ---- the same filters showsub.php used ----
$curdate = isset($_GET['date']) ? (string)$_GET['date'] : date('Y-m-d');
$params = array();
$join   = '';
if (isset($_GET['smid']) && $_GET['smid'] !== '') {
    $days = 30; $cond = 'A.uid = ?'; $params[] = $_GET['smid'];
} elseif ($level === 1) {
    $days = 60; $cond = '1=1';
} elseif ($level === 2) {
    $days = 30; $cond = '1=1'; $join = 'INNER JOIN users AS C ON A.uid = C.uid';
} else {
    $days = 30; $cond = 'A.uid = ?'; $params[] = (int)$me['uid'];
}
// Index-friendly range first; the original TO_DAYS() rule is kept so results are identical.
$where = "A.status = 1 AND A.subdone = 1 AND $cond
          AND A.subdate >= DATE(?) - INTERVAL $days DAY
          AND TO_DAYS(?) - TO_DAYS(A.subdate) <= $days";
$params[] = $curdate; $params[] = $curdate;

$from = "FROM app_data AS A
         LEFT JOIN consultants AS B ON A.consultant_id = B.cid
         $join
         LEFT JOIN skill   AS S  ON S.sid = B.skill
         LEFT JOIN clients AS CL ON CL.cid = A.client_id
         LEFT JOIN users   AS U  ON U.uid = A.uid
         LEFT JOIN req     AS R  ON R.reqid = A.reqid";

$subLabels = array(1 => 'Submitted to T2', 2 => 'Submitted to T1', 3 => 'Submitted to IP', 4 => 'Submitted to End Client', 5 => 'In-Process');
$arsLabels = array(1 => 'Connected', 2 => 'Not Connected', 3 => 'Voicemail', 4 => 'No Response', 5 => 'Cancelled',
                   6 => 'Rejected', 7 => 'In-Process', 8 => 'Got Test', 9 => 'Got Screening', 10 => 'Submitted to End Client');

// ---- search ----
$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
if ($search !== '') {
    $like = '%' . addcslashes($search, '%_\\') . '%';
    $cols = array("DATE_FORMAT(A.subdate, '%m/%d/%y')", 'CAST(A.subdate AS CHAR)', 'S.skillname', 'R.rlocation', 'CL.remail',
                  "CONCAT(IFNULL(B.cfname,''), ' ', IFNULL(B.clname,''))", 'CAST(A.rateperhour AS CHAR)',
                  "CONCAT(IFNULL(A.t1ip_name,''), '/', IFNULL(R.rend_client,''))");
    if ($showSM) { $cols[] = 'U.name'; }
    $or = array();
    foreach ($cols as $c) { $or[] = "$c LIKE ?"; $params[] = $like; }
    foreach (array('A.subto' => $subLabels, 'A.ars_status' => $arsLabels) as $col => $labels) {
        $codes = array();
        foreach ($labels as $code => $label) {
            if (stripos($label, $search) !== false) { $codes[] = (int)$code; }
        }
        if ($codes) { $or[] = "$col IN (" . implode(',', $codes) . ')'; }
    }
    $where .= ' AND (' . implode(' OR ', $or) . ')';
}

// ---- sorting (same columns as the table headers) ----
$sortMap = array(
    'Datetime' => 'A.subdate', 'name' => 'U.name', 'Role' => 'S.skillname', 'rlocation' => 'R.rlocation',
    'remail' => 'CL.remail', 'cname' => 'B.cfname %s, B.clname', 'rate' => 'A.rateperhour',
    'client' => 'A.t1ip_name', 'status' => 'A.subto', 'arcstatus' => 'A.ars_status',
);
$dir = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';
if (isset($_GET['sort'], $sortMap[$_GET['sort']])) {
    $s = $sortMap[$_GET['sort']];
    $orderBy = (strpos($s, '%s') !== false ? sprintf($s, $dir) : $s) . " $dir, A.subdate DESC, A.app_id DESC";
} else {
    $orderBy = 'A.subdate DESC, A.app_id DESC';      // same as before: newest first
}

$limit  = isset($_GET['limit'])  ? (int)$_GET['limit']  : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
if ($limit < 1)    { $limit = 50; }
if ($limit > 5000) { $limit = 5000; }
if ($offset < 0)   { $offset = 0; }

try {
    $c = $conn->prepare("SELECT COUNT(*) $from WHERE $where");
    $c->execute($params);
    $total = (int)$c->fetchColumn();

    $q = $conn->prepare("SELECT A.app_id, A.reqid, A.uid, A.subdate, A.rateperhour, A.t1ip_name, A.subto, A.ars_status,
                                B.cfname, B.clname, S.skillname, CL.remail, CL.rname, CL.companyname,
                                U.uid AS sm_uid, U.name AS sm_name, R.rlocation, R.rend_client
                         $from WHERE $where ORDER BY $orderBy LIMIT " . (int)$offset . ', ' . (int)$limit);
    $q->execute($params);
} catch (Exception $e) {
    error_log('showsub_ajax: ' . $e->getMessage());
    ss_out(0, array(), 500);
}

$h  = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$js = function ($v) { return addcslashes((string)$v, "\\'\"\n\r<>"); };   // text inside alert('...')
$pop = "onclick=\"window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;\"";
$arsHtml = array(1 => 'Connected&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', 2 => 'Not Connected', 3 => 'Voicemail&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                 4 => 'No Response', 5 => 'Cancelled&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', 6 => 'Rejected&nbsp;&nbsp;&nbsp;',
                 7 => 'In-Process&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', 8 => 'Got Test&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                 9 => 'Got Screening', 10 => 'Submitted to End Client');

$rows = array();
$i = $offset + 1;
while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    $app = (int)$r['app_id'];
    $row = array(
        'Datetime'  => $h(date('m/d/y g:i A', strtotime((string)$r['subdate']))),
        'id'        => $i++,
        'Role'      => '<a id="various3" target="_blank" href="leads/view.php?id=' . $h($r['reqid']) . '">' . $h($r['skillname']) . '</a>',
        'rlocation' => $h($r['rlocation']),
        'remail'    => '<a href="#" onClick="alert(\'\n\n\n\n' . $h($js('Name: ' . $r['rname'])) . '\n\n' . $h($js('Email: ' . $r['remail']))
                       . '\n\n' . $h($js('Company Name: ' . $r['companyname'])) . '\')">' . $h($r['remail']) . '</a>',
        'cname'     => $h($r['cfname'] . ' ' . $r['clname']),
        'rate'      => $h($r['rateperhour']),
        'client'    => $h($r['t1ip_name'] . '/' . $r['rend_client']),
        'status'    => (isset($subLabels[(int)$r['subto']]) && $r['subto'] !== null ? $subLabels[(int)$r['subto']] : '')
                       . ' <a href="addsubstatus.php?subid=' . $app . '" ' . $pop . '><button name="addstatus" class="btn btn-primary">Update Status</button></a>',
        'arcstatus' => (isset($arsHtml[(int)$r['ars_status']]) && $r['ars_status'] !== null ? $arsHtml[(int)$r['ars_status']] : '')
                       . ' <a href="addstatus.php?subid=' . $app . '" ' . $pop . '><button name="addstatus" class="btn btn-primary">Update Status</button></a>',
        'addECI'    => '<a href="addeci.php?subid=' . $app . '"><button name="addsub" class="btn btn-primary">Add ECI</button></a>',
        'comment'   => '<a href="comments.php?subcom_id=' . $app . '" ' . str_replace(',addressbar=no', ',', $pop) . '><button name="viewcomment" class="btn btn-primary">View Comment</button></a> '
                       . '<a href="addcomment.php?subcom_id=' . $app . '" ' . $pop . '><button name="addcomment" class="btn btn-primary">Add Comment</button></a>',
    );
    if ($showSM) {
        $row['name']   = '<a href="admin.php?action=showsub&smid=' . $h($r['sm_uid']) . '" target="_blank">' . $h($r['sm_name']) . '</a>';
        $row['action'] = '<a href ="appdata_cmd.php?do=delete&subid=' . $app . '" onClick="return confirm(\'Are you sure you want to remove this submission ?\')"><img src="images/b_drop.png" alt="Delete" width="16" height="16" border="0" title="Delete"/></a>';
    }
    $rows[] = $row;
}
ss_out($total, $rows);
