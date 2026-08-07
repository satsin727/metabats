<?php
// Hide errors/notices from printing into the CSV file
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once("config.php");

if (!isset($_SESSION['id']) || (int)$_SESSION['id'] <= 0) {
    http_response_code(403);
    exit('Unauthorized');
}

$sessid = (int)$_SESSION['id'];

try {
    $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed.');
}

/*
 * Validate the authenticated user exactly like call_reports.php.
 */
$userStmt = $conn->prepare("
    SELECT uid, name, level, sess
    FROM users
    WHERE uid = :uid
    LIMIT 1
");
$userStmt->execute(array(':uid' => $sessid));
$dta = $userStmt->fetch(PDO::FETCH_ASSOC);

if (
    !$dta ||
    !isset($_SESSION['username']) ||
    $dta['sess'] != $_SESSION['username']
) {
    http_response_code(403);
    exit('Unauthorized');
}

$userLevel = (int)$dta['level'];
$isAdminOrManager = in_array($userLevel, array(1, 2), true);
$isRecruiter = ($userLevel === 3);

if (!$isAdminOrManager && !$isRecruiter) {
    http_response_code(403);
    exit('Unauthorized');
}

// Retrieve variables matching call_reports.php
$report_type = isset($_GET['report_type']) ? trim($_GET['report_type']) : 'daily';

if (!in_array($report_type, array('daily', 'weekly', 'monthly'), true)) {
    $report_type = 'daily';
}

$filter_date = isset($_GET['filter_date'])
    ? trim($_GET['filter_date'])
    : date('Y-m-d');

$dateCheck = DateTime::createFromFormat('Y-m-d', $filter_date);
if (!$dateCheck || $dateCheck->format('Y-m-d') !== $filter_date) {
    $filter_date = date('Y-m-d');
}

$requested_view_uid = isset($_GET['view_uid'])
    ? (int)$_GET['view_uid']
    : 0;

/*
 * CSV access control
 * -------------------------------------------------------------
 * Levels 1 and 2:
 *   - view_uid = 0 => team export
 *   - view_uid > 0 => selected recruiter's export
 *
 * Level 3:
 *   - may export ONLY their own UID
 *   - changing view_uid to 0 or another user's UID is rejected
 *   - when view_uid is omitted, their own UID is still forced
 */
if ($isRecruiter) {
    if (
        isset($_GET['view_uid']) &&
        $requested_view_uid !== $sessid
    ) {
        http_response_code(403);
        exit('Unauthorized export request.');
    }

    $view_uid = $sessid;
} else {
    $view_uid = $requested_view_uid;
}

$filter_connected = isset($_GET['filter_connected'])
    ? $_GET['filter_connected']
    : '';

$filter_response = (
    isset($_GET['filter_response']) &&
    $_GET['filter_response'] !== ''
)
    ? (int)$_GET['filter_response']
    : '';

$detail_date = isset($_GET['detail_date'])
    ? trim($_GET['detail_date'])
    : '';

// -------------------------------------------------------------
// Date Calculations for Weekly and Monthly Views
// -------------------------------------------------------------
$dt = new DateTime($filter_date);

// Monthly boundaries
$startMonth = (clone $dt)->modify('first day of this month')->format('Y-m-d');
$endMonth = (clone $dt)->modify('last day of this month')->format('Y-m-d');

// Weekly boundaries (Monday - Friday)
$dayOfWeek = $dt->format('N'); 
if ($dayOfWeek != 1) {
    $dt->modify('last monday');
}
$dateMon = $dt->format('Y-m-d');
$dateTue = (clone $dt)->modify('+1 day')->format('Y-m-d');
$dateWed = (clone $dt)->modify('+2 days')->format('Y-m-d');
$dateThu = (clone $dt)->modify('+3 days')->format('Y-m-d');
$dateFri = (clone $dt)->modify('+4 days')->format('Y-m-d');

// -------------------------------------------------------------
// Detail Query Building Based on the Schedule Table
// -------------------------------------------------------------
$detailParams = [];
$detailConds = " 1=1 ";

/*
 * UID scope is applied server-side.
 * Recruiters always reach this point with view_uid = session UID.
 * Admin/Manager may use 0 for team-wide export.
 */
if ($view_uid > 0) {
    $detailConds .= " AND s.uid = :uid ";
    $detailParams[':uid'] = $view_uid;
}

// Prioritize detail_date if a specific day was clicked in the weekly view
if ($detail_date != '') {
    $detailDateCheck = DateTime::createFromFormat('Y-m-d', $detail_date);

    if (
        $detailDateCheck &&
        $detailDateCheck->format('Y-m-d') === $detail_date
    ) {
        $detailConds .= " AND s.call_date = :ddate ";
        $detailParams[':ddate'] = $detail_date;
    } else {
        http_response_code(400);
        exit('Invalid detail date.');
    }
} elseif ($report_type == 'daily') {
    $detailConds .= " AND s.call_date = :fdate ";
    $detailParams[':fdate'] = $filter_date;
} elseif ($report_type == 'weekly') {
    $detailConds .= " AND s.call_date BETWEEN :dmon AND :dfri ";
    $detailParams[':dmon'] = $dateMon;
    $detailParams[':dfri'] = $dateFri;
} elseif ($report_type == 'monthly') {
    $detailConds .= " AND s.call_date BETWEEN :dstart AND :dend ";
    $detailParams[':dstart'] = $startMonth;
    $detailParams[':dend'] = $endMonth;
}

// Apply Dropdown Filters
if ($filter_connected === 'not_called') {
    $detailConds .= " AND s.called = 0";
} elseif ($filter_connected === 'called') {
    $detailConds .= " AND s.called = 1"; 
} elseif ($filter_connected === '1') {
    $detailConds .= " AND s.called = 1 AND s.connected = 1";
} elseif ($filter_connected === '0') {
    $detailConds .= " AND s.called = 1 AND s.connected = 0";
}

if ($filter_response !== '') {
    $detailConds .= " AND s.response_type_id = :resp";
    $detailParams[':resp'] = $filter_response;
}

$sql = "SELECT c.cid, c.companyname, c.rname, c.rphone, c.remail,
        s.call_date, s.called, s.connected,
        rt.response_name, u.name AS recruiter_name
        FROM client_call_schedule s
        JOIN clients c ON s.cid = c.cid
        JOIN users u ON s.uid = u.uid
        LEFT JOIN client_response_type rt ON s.response_type_id = rt.id
        WHERE $detailConds
        ORDER BY s.call_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($detailParams);

/*
 * Latest 5 comments.
 *
 * For recruiter exports, comments are also restricted by the
 * authenticated recruiter's UID. This prevents a shared client's
 * comments entered by another recruiter from leaking into the CSV.
 *
 * Admin / Manager keep the existing all-history behavior.
 */
if ($isRecruiter) {
    $commentStmt = $conn->prepare("
        SELECT call_datetime, comment
        FROM client_call_history
        WHERE cid = :cid
          AND uid = :comment_uid
          AND comment != ''
          AND comment IS NOT NULL
        ORDER BY call_datetime DESC
        LIMIT 5
    ");
} else {
    $commentStmt = $conn->prepare("
        SELECT call_datetime, comment
        FROM client_call_history
        WHERE cid = :cid
          AND comment != ''
          AND comment IS NOT NULL
        ORDER BY call_datetime DESC
        LIMIT 5
    ");
}

// Configure Headers to Download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Call_Report_' . $report_type . '_' . $filter_date . '.csv');

$output = fopen('php://output', 'w');

// Set CSV Column Headers including S.No
fputcsv($output, [
    'S.No',
    'Recruiter', 
    'Company Name', 
    'Contact Name', 
    'Phone', 
    'Email', 
    'Schedule Date', 
    'Status', 
    'Response Type', 
    'Latest 5 Comments (Date: Comment)'
]);

$i = 1;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    
    // Determine status text format
    if ($row['called'] == 0) {
        $statusText = 'Assigned (Not Called)';
    } elseif ($row['connected'] == 1) {
        $statusText = 'Connected';
    } else {
        $statusText = 'Not Connected';
    }

    // Fetch latest 5 comments allowed for this export
    if ($isRecruiter) {
        $commentStmt->execute(array(
            ':cid' => $row['cid'],
            ':comment_uid' => $sessid
        ));
    } else {
        $commentStmt->execute(array(
            ':cid' => $row['cid']
        ));
    }

    $commentsArray = [];
    
    while ($c = $commentStmt->fetch(PDO::FETCH_ASSOC)) {
        $cleanComment = str_replace(array("\r", "\n"), ' ', $c['comment']); 
        $dateFormatted = date("d-M-y", strtotime($c['call_datetime']));
        $commentsArray[] = "[" . $dateFormatted . "] " . $cleanComment;
    }
    
    $combinedComments = !empty($commentsArray) ? implode(" | ", $commentsArray) : '-';

    fputcsv($output, [
        $i++,
        $row['recruiter_name'],
        $row['companyname'],
        $row['rname'],
        $row['rphone'],
        $row['remail'],
        date("Y-m-d", strtotime($row['call_date'])),
        $statusText,
        ($row['response_name'] ? $row['response_name'] : 'N/A'),
        $combinedComments
    ]);
}

fclose($output);
exit;
?>