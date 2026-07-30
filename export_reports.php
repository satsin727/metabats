<?php
// Hide errors/notices from printing into the CSV file
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once("config.php");

if (!isset($_SESSION['id'])) {
    exit('Unauthorized');
}

$conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);

// Retrieve Variables safely matching call_reports.php
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'daily';
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');
$view_uid = isset($_GET['view_uid']) ? (int)$_GET['view_uid'] : 0;
$filter_connected = isset($_GET['filter_connected']) ? $_GET['filter_connected'] : '';
$filter_response = (isset($_GET['filter_response']) && $_GET['filter_response'] !== '') ? (int)$_GET['filter_response'] : '';
$detail_date = isset($_GET['detail_date']) ? trim($_GET['detail_date']) : '';

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

// If view_uid is set and greater than 0, filter by that user. If 0, include all users (Team Total).
if ($view_uid > 0) {
    $detailConds .= " AND s.uid = :uid ";
    $detailParams[':uid'] = $view_uid;
}

// Prioritize detail_date if a specific day was clicked in the weekly view
if ($detail_date != '') {
    $detailConds .= " AND s.call_date = :ddate ";
    $detailParams[':ddate'] = $detail_date;
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

// Prepare statement for the Latest 5 Comments from client history table
$commentStmt = $conn->prepare("
    SELECT call_datetime, comment 
    FROM client_call_history 
    WHERE cid = :cid 
    AND comment != '' 
    AND comment IS NOT NULL
    ORDER BY call_datetime DESC 
    LIMIT 5
");

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

    // Fetch latest 5 comments for this specific client from history
    $commentStmt->execute([':cid' => $row['cid']]);
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