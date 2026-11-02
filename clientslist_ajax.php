<?php
/*
 * Data for admin.php?action=clientslist (viewcombined.php).
 * Returns ONE PAGE of the client list as JSON for bootstrap-table (server-side pagination),
 * instead of sending all 50,000+ rows to the browser at once.
 *
 * Same rules as before:
 *   - level 1 / 2 : all active clients
 *   - level 3     : only their own active clients
 *   - one row per recruiter email (duplicates of the same email are hidden)
 *   - default order: by email (A-Z), like the old list
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function cl_out($total, $rows, $code = 200)
{
    http_response_code($code);
    echo json_encode(array('total' => $total, 'rows' => $rows));
    exit;
}

if (empty($_SESSION['id']) || empty($_SESSION['username'])) {
    cl_out(0, array(), 401);
}

$conn = bats_db();
$st = $conn->prepare('SELECT uid, level, sess FROM users WHERE uid = ? LIMIT 1');
$st->execute(array($_SESSION['id']));
$me = $st->fetch(PDO::FETCH_ASSOC);
if (!$me || (string)$me['sess'] !== (string)$_SESSION['username']) {
    cl_out(0, array(), 401);
}
$level = (int)$me['level'];
if (!in_array($level, array(1, 2, 3), true)) {
    cl_out(0, array(), 403);
}

// ---- paging / sorting / search parameters from bootstrap-table ----
$limit  = isset($_GET['limit'])  ? (int)$_GET['limit']  : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
if ($limit < 1)    { $limit = 50; }
if ($limit > 5000) { $limit = 5000; }
if ($offset < 0)   { $offset = 0; }

$sortMap = array(                       // table column (data-field) => SQL column
    'Company Name'   => 'c.companyname',
    'Full Name Name' => 'c.rname',
    'Email ID'       => 'c.remail',
    'Phone Number'   => 'c.rphone',
    'Location'       => 'c.rlocation',
    'Timezone'       => 'c.rtimezon',
    'Tier'           => 'c.tier',
);
$sort  = isset($_GET['sort'], $sortMap[$_GET['sort']]) ? $sortMap[$_GET['sort']] : 'c.remail';
$order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'DESC' : 'ASC';
$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';

// ---- one row per email: the first (lowest id) active client row for each email ----
$innerWhere = '`status` = 1';
$params = array();
if ($level === 3) {
    $innerWhere .= ' AND `uid` = ?';
    $params[] = (int)$me['uid'];
}
$from = "FROM clients c
         JOIN (SELECT MIN(cid) AS cid FROM clients WHERE $innerWhere GROUP BY remail) m ON m.cid = c.cid";

$where = '';
if ($search !== '') {
    $like = '%' . addcslashes($search, '%_\\') . '%';
    $cols = array('c.companyname', 'c.rname', 'c.remail', 'c.rphone', 'c.rlocation', 'c.rtimezon', 'c.tier');
    $parts = array();
    foreach ($cols as $col) {
        $parts[] = "$col LIKE ?";
        $params[] = $like;
    }
    $where = 'WHERE (' . implode(' OR ', $parts) . ')';
}

try {
    $cnt = $conn->prepare("SELECT COUNT(*) $from $where");
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();

    $sql = "SELECT c.companyname, c.rname, c.remail, c.rphone, c.rlocation, c.rtimezon, c.tier
            $from $where
            ORDER BY $sort $order, c.remail ASC, c.cid ASC
            LIMIT " . (int)$offset . ', ' . (int)$limit;
    $q = $conn->prepare($sql);
    $q->execute($params);
} catch (Exception $e) {
    error_log('clientslist_ajax: ' . $e->getMessage());
    cl_out(0, array(), 500);
}

$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$rows = array();
$i = $offset + 1;
while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = array(
        'ID'             => $i++,
        'Company Name'   => $h($r['companyname']),
        'Full Name Name' => $h($r['rname']),
        'Email ID'       => $h($r['remail']),
        'Phone Number'   => $h($r['rphone']),
        'Location'       => $h($r['rlocation']),
        'Timezone'       => $h($r['rtimezon']),
        'Tier'           => $h($r['tier']),
    );
}
cl_out($total, $rows);
