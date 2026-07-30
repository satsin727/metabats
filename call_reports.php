<?php
session_start();
require_once("config.php");

$conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);

if (isset($_SESSION['id'])) {
    $sessid = $_SESSION['id'];
} else {
    header("Location: admin.php");
    exit;
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE uid = :uid LIMIT 1");
$stmt->execute([":uid" => $sessid]);
$dta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!isset($_SESSION['username']) || $dta['sess'] != $_SESSION['username']) {
    echo "<script>alert('Not Authorised to view this page.'); window.location.href='login.php';</script>";
    exit;
}

// Get filter parameters
// Get filter parameters safely


// Get filter parameters safely


// Fix: Only cast to int if it's not an empty string
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'daily';
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');
$view_uid = isset($_GET['view_uid']) ? (int)$_GET['view_uid'] : null; // Can be 0 for Team Total or >0 for specific user
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
$dayOfWeek = $dt->format('N'); // 1 = Monday, 7 = Sunday
if ($dayOfWeek != 1) {
    $dt->modify('last monday');
}
$dateMon = $dt->format('Y-m-d');
$dateTue = (clone $dt)->modify('+1 day')->format('Y-m-d');
$dateWed = (clone $dt)->modify('+2 days')->format('Y-m-d');
$dateThu = (clone $dt)->modify('+3 days')->format('Y-m-d');
$dateFri = (clone $dt)->modify('+4 days')->format('Y-m-d');

// Fetch all Response Types for the dropdown
$respStmt = $conn->prepare("SELECT id, response_name FROM client_response_type WHERE status = 1 ORDER BY response_name");
$respStmt->execute();
$responseTypes = $respStmt->fetchAll(PDO::FETCH_ASSOC);

require("includes/header.php");
require("includes/menu.php");

echo '<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">';
?>

<div class="panel panel-default">
    <div class="panel-heading">Call Reports Snapshot</div>
    <div class="panel-body">
        
        <!-- Snapshot Filters -->
        <form method="get" class="form-inline">
            <input type="hidden" name="action" value="callreports">
            <?php if ($view_uid !== null) { ?>
                <input type="hidden" name="view_uid" value="<?php echo $view_uid; ?>">
            <?php } ?>
            
            <div class="form-group">
                <label>Report Type:</label>
                <select name="report_type" class="form-control" onchange="this.form.submit()">
                    <option value="daily" <?php if($report_type=='daily') echo 'selected'; ?>>Daily</option>
                    <option value="weekly" <?php if($report_type=='weekly') echo 'selected'; ?>>Weekly</option>
                    <option value="monthly" <?php if($report_type=='monthly') echo 'selected'; ?>>Monthly</option>
                </select>
            </div>
            <div class="form-group">
                <label>Reference Date:</label>
                <input type="date" name="filter_date" class="form-control" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Generate Snapshot</button>
        </form>
        <hr>

        <div class="table-responsive">
            
        <?php 
        /* =========================================================================
           DAILY VIEW
        ========================================================================= */
        if ($report_type == 'daily') { 
        ?>
            <h4>Daily Report for <?php echo date("d-M-Y", strtotime($filter_date)); ?></h4>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr class="info">
                        <th>Recruiter Name</th>
                        <th>Total Assigned</th>
                        <th>Total Called</th>
                        <th>Connected</th>
                        <th>Not Connected</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $dailySql = "SELECT u.uid, u.name,
                                COUNT(s.cl_id) as assigned_count,
                                SUM(CASE WHEN s.called = 1 THEN 1 ELSE 0 END) as total_called,
                                SUM(CASE WHEN s.called = 1 AND s.connected = 1 THEN 1 ELSE 0 END) as total_connected,
                                SUM(CASE WHEN s.called = 1 AND s.connected = 0 THEN 1 ELSE 0 END) as not_connected
                                FROM users u
                                INNER JOIN client_call_schedule s ON u.uid = s.uid 
                                WHERE s.call_date = :fdate
                                GROUP BY u.uid, u.name";
                    
                    $dailyStmt = $conn->prepare($dailySql);
                    $dailyStmt->execute([':fdate' => $filter_date]);
                    
                    $sum_assigned = 0; $sum_called = 0; $sum_conn = 0; $sum_not_conn = 0;

                    while ($row = $dailyStmt->fetch(PDO::FETCH_ASSOC)) {
                        $sum_assigned += $row['assigned_count'];
                        $sum_called += $row['total_called'];
                        $sum_conn += $row['total_connected'];
                        $sum_not_conn += $row['not_connected'];

                        $baseLink = "?action=callreports&report_type=daily&filter_date=$filter_date&view_uid=".$row['uid'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><a href="<?php echo $baseLink; ?>" style="font-weight:bold;"><?php echo (int)$row['assigned_count']; ?></a></td>
                            <td><a href="<?php echo $baseLink; ?>&filter_connected=called"><?php echo (int)$row['total_called']; ?></a></td>
                            <td><a href="<?php echo $baseLink; ?>&filter_connected=1" class="text-success" style="font-weight:bold;"><?php echo (int)$row['total_connected']; ?></a></td>
                            <td><a href="<?php echo $baseLink; ?>&filter_connected=0" class="text-danger" style="font-weight:bold;"><?php echo (int)$row['not_connected']; ?></a></td>
                        </tr>
                    <?php } 
                    
                    // Team Total Row
                    $teamBaseLink = "?action=callreports&report_type=daily&filter_date=$filter_date&view_uid=0";
                    ?>
                    <tr style="font-weight:bold; background-color: #f9f9f9;">
                        <td>Team Total</td>
                        <td><a href="<?php echo $teamBaseLink; ?>"><?php echo $sum_assigned; ?></a></td>
                        <td><a href="<?php echo $teamBaseLink; ?>&filter_connected=called"><?php echo $sum_called; ?></a></td>
                        <td><a href="<?php echo $teamBaseLink; ?>&filter_connected=1" class="text-success"><?php echo $sum_conn; ?></a></td>
                        <td><a href="<?php echo $teamBaseLink; ?>&filter_connected=0" class="text-danger"><?php echo $sum_not_conn; ?></a></td>
                    </tr>
                </tbody>
            </table>

        <?php 
        /* =========================================================================
           WEEKLY VIEW
        ========================================================================= */
        } elseif ($report_type == 'weekly') { 
        ?>
            <h4>Weekly Report (<?php echo date("d-M", strtotime($dateMon)) . " to " . date("d-M-Y", strtotime($dateFri)); ?>)</h4>
            <table class="table table-bordered table-hover text-center">
                <thead>
                    <tr class="info">
                        <th rowspan="2" style="vertical-align: middle; text-align: left;">Recruiter Name</th>
                        <th colspan="2">Mon (<?php echo date("d", strtotime($dateMon)); ?>)</th>
                        <th colspan="2">Tue (<?php echo date("d", strtotime($dateTue)); ?>)</th>
                        <th colspan="2">Wed (<?php echo date("d", strtotime($dateWed)); ?>)</th>
                        <th colspan="2">Thu (<?php echo date("d", strtotime($dateThu)); ?>)</th>
                        <th colspan="2">Fri (<?php echo date("d", strtotime($dateFri)); ?>)</th>
                        <th colspan="2" class="success">Total This Week</th>
                        <th colspan="2" class="warning">Total This Month</th>
                    </tr>
                    <tr class="active">
                        <!-- Mon --> <th>Calls</th><th>Conn</th>
                        <!-- Tue --> <th>Calls</th><th>Conn</th>
                        <!-- Wed --> <th>Calls</th><th>Conn</th>
                        <!-- Thu --> <th>Calls</th><th>Conn</th>
                        <!-- Fri --> <th>Calls</th><th>Conn</th>
                        <!-- Week --> <th class="success">Calls</th><th class="success">Conn</th>
                        <!-- Month --> <th class="warning">Calls</th><th class="warning">Conn</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $weeklySql = "SELECT u.uid, u.name,
                        -- Monday
                        SUM(CASE WHEN s.call_date = :mon AND s.called = 1 THEN 1 ELSE 0 END) as mon_calls,
                        SUM(CASE WHEN s.call_date = :mon AND s.called = 1 AND s.connected = 1 THEN 1 ELSE 0 END) as mon_conn,
                        -- Tuesday
                        SUM(CASE WHEN s.call_date = :tue AND s.called = 1 THEN 1 ELSE 0 END) as tue_calls,
                        SUM(CASE WHEN s.call_date = :tue AND s.called = 1 AND s.connected = 1 THEN 1 ELSE 0 END) as tue_conn,
                        -- Wednesday
                        SUM(CASE WHEN s.call_date = :wed AND s.called = 1 THEN 1 ELSE 0 END) as wed_calls,
                        SUM(CASE WHEN s.call_date = :wed AND s.called = 1 AND s.connected = 1 THEN 1 ELSE 0 END) as wed_conn,
                        -- Thursday
                        SUM(CASE WHEN s.call_date = :thu AND s.called = 1 THEN 1 ELSE 0 END) as thu_calls,
                        SUM(CASE WHEN s.call_date = :thu AND s.called = 1 AND s.connected = 1 THEN 1 ELSE 0 END) as thu_conn,
                        -- Friday
                        SUM(CASE WHEN s.call_date = :fri AND s.called = 1 THEN 1 ELSE 0 END) as fri_calls,
                        SUM(CASE WHEN s.call_date = :fri AND s.called = 1 AND s.connected = 1 THEN 1 ELSE 0 END) as fri_conn,
                        
                        -- This Week Total
                        SUM(CASE WHEN s.call_date BETWEEN :mon AND :fri AND s.called = 1 THEN 1 ELSE 0 END) as week_calls,
                        SUM(CASE WHEN s.call_date BETWEEN :mon AND :fri AND s.called = 1 AND s.connected = 1 THEN 1 ELSE 0 END) as week_conn,
                        
                        -- This Month Total
                        SUM(CASE WHEN s.call_date BETWEEN :startm AND :endm AND s.called = 1 THEN 1 ELSE 0 END) as month_calls,
                        SUM(CASE WHEN s.call_date BETWEEN :startm AND :endm AND s.called = 1 AND s.connected = 1 THEN 1 ELSE 0 END) as month_conn

                        FROM users u
                        INNER JOIN client_call_schedule s ON u.uid = s.uid 
                        WHERE s.call_date BETWEEN :startm AND :endm
                        GROUP BY u.uid, u.name
                        HAVING month_calls > 0";
                    
                    $weekStmt = $conn->prepare($weeklySql);
                    $weekStmt->execute([
                        ':mon' => $dateMon, ':tue' => $dateTue, ':wed' => $dateWed, ':thu' => $dateThu, ':fri' => $dateFri,
                        ':startm' => $startMonth, ':endm' => $endMonth
                    ]);
                    
                    $t_mon_calls = 0; $t_mon_conn = 0;
                    $t_tue_calls = 0; $t_tue_conn = 0;
                    $t_wed_calls = 0; $t_wed_conn = 0;
                    $t_thu_calls = 0; $t_thu_conn = 0;
                    $t_fri_calls = 0; $t_fri_conn = 0;
                    $t_week_calls = 0; $t_week_conn = 0;
                    $t_month_calls = 0; $t_month_conn = 0;

                    while ($row = $weekStmt->fetch(PDO::FETCH_ASSOC)) {
                        $t_mon_calls += $row['mon_calls']; $t_mon_conn += $row['mon_conn'];
                        $t_tue_calls += $row['tue_calls']; $t_tue_conn += $row['tue_conn'];
                        $t_wed_calls += $row['wed_calls']; $t_wed_conn += $row['wed_conn'];
                        $t_thu_calls += $row['thu_calls']; $t_thu_conn += $row['thu_conn'];
                        $t_fri_calls += $row['fri_calls']; $t_fri_conn += $row['fri_conn'];
                        $t_week_calls += $row['week_calls']; $t_week_conn += $row['week_conn'];
                        $t_month_calls += $row['month_calls']; $t_month_conn += $row['month_conn'];

                        $baseLink = "?action=callreports&report_type=weekly&filter_date=$filter_date&view_uid=".$row['uid'];
                        ?>
                        <tr>
                            <td style="text-align: left;"><a href="<?php echo $baseLink; ?>"><?php echo htmlspecialchars($row['name']); ?></a></td>
                            
                            <td><a href="<?php echo $baseLink; ?>&detail_date=<?php echo $dateMon; ?>&filter_connected=called"><?php echo (int)$row['mon_calls']; ?></a></td>
                            <td><a href="<?php echo $baseLink; ?>&detail_date=<?php echo $dateMon; ?>&filter_connected=1" class="text-success"><?php echo (int)$row['mon_conn']; ?></a></td>
                            
                            <td><a href="<?php echo $baseLink; ?>&detail_date=<?php echo $dateTue; ?>&filter_connected=called"><?php echo (int)$row['tue_calls']; ?></a></td>
                            <td><a href="<?php echo $baseLink; ?>&detail_date=<?php echo $dateTue; ?>&filter_connected=1" class="text-success"><?php echo (int)$row['tue_conn']; ?></a></td>
                            
                            <td><a href="<?php echo $baseLink; ?>&detail_date=<?php echo $dateWed; ?>&filter_connected=called"><?php echo (int)$row['wed_calls']; ?></a></td>
                            <td><a href="<?php echo $baseLink; ?>&detail_date=<?php echo $dateWed; ?>&filter_connected=1" class="text-success"><?php echo (int)$row['wed_conn']; ?></a></td>
                            
                            <td><a href="<?php echo $baseLink; ?>&detail_date=<?php echo $dateThu; ?>&filter_connected=called"><?php echo (int)$row['thu_calls']; ?></a></td>
                            <td><a href="<?php echo $baseLink; ?>&detail_date=<?php echo $dateThu; ?>&filter_connected=1" class="text-success"><?php echo (int)$row['thu_conn']; ?></a></td>
                            
                            <td><a href="<?php echo $baseLink; ?>&detail_date=<?php echo $dateFri; ?>&filter_connected=called"><?php echo (int)$row['fri_calls']; ?></a></td>
                            <td><a href="<?php echo $baseLink; ?>&detail_date=<?php echo $dateFri; ?>&filter_connected=1" class="text-success"><?php echo (int)$row['fri_conn']; ?></a></td>
                            
                            <td class="success"><a href="<?php echo $baseLink; ?>&filter_connected=called" style="font-weight:bold;"><?php echo (int)$row['week_calls']; ?></a></td>
                            <td class="success"><a href="<?php echo $baseLink; ?>&filter_connected=1" style="font-weight:bold; color:green;"><?php echo (int)$row['week_conn']; ?></a></td>
                            
                            <td class="warning"><a href="?action=callreports&report_type=monthly&filter_date=<?php echo $filter_date; ?>&view_uid=<?php echo $row['uid']; ?>&filter_connected=called" style="font-weight:bold;"><?php echo (int)$row['month_calls']; ?></a></td>
                            <td class="warning"><a href="?action=callreports&report_type=monthly&filter_date=<?php echo $filter_date; ?>&view_uid=<?php echo $row['uid']; ?>&filter_connected=1" style="font-weight:bold; color:green;"><?php echo (int)$row['month_conn']; ?></a></td>
                        </tr>
                    <?php } 
                    
                    // Team Total Row for Weekly
                    $teamBaseLink = "?action=callreports&report_type=weekly&filter_date=$filter_date&view_uid=0";
                    ?>
                    <tr style="font-weight:bold; background-color: #f9f9f9;">
                        <td style="text-align: left;">Team Total</td>
                        
                        <td><a href="<?php echo $teamBaseLink; ?>&detail_date=<?php echo $dateMon; ?>&filter_connected=called"><?php echo $t_mon_calls; ?></a></td>
                        <td><a href="<?php echo $teamBaseLink; ?>&detail_date=<?php echo $dateMon; ?>&filter_connected=1" class="text-success"><?php echo $t_mon_conn; ?></a></td>
                        
                        <td><a href="<?php echo $teamBaseLink; ?>&detail_date=<?php echo $dateTue; ?>&filter_connected=called"><?php echo $t_tue_calls; ?></a></td>
                        <td><a href="<?php echo $teamBaseLink; ?>&detail_date=<?php echo $dateTue; ?>&filter_connected=1" class="text-success"><?php echo $t_tue_conn; ?></a></td>
                        
                        <td><a href="<?php echo $teamBaseLink; ?>&detail_date=<?php echo $dateWed; ?>&filter_connected=called"><?php echo $t_wed_calls; ?></a></td>
                        <td><a href="<?php echo $teamBaseLink; ?>&detail_date=<?php echo $dateWed; ?>&filter_connected=1" class="text-success"><?php echo $t_wed_conn; ?></a></td>
                        
                        <td><a href="<?php echo $teamBaseLink; ?>&detail_date=<?php echo $dateThu; ?>&filter_connected=called"><?php echo $t_thu_calls; ?></a></td>
                        <td><a href="<?php echo $teamBaseLink; ?>&detail_date=<?php echo $dateThu; ?>&filter_connected=1" class="text-success"><?php echo $t_thu_conn; ?></a></td>
                        
                        <td><a href="<?php echo $teamBaseLink; ?>&detail_date=<?php echo $dateFri; ?>&filter_connected=called"><?php echo $t_fri_calls; ?></a></td>
                        <td><a href="<?php echo $teamBaseLink; ?>&detail_date=<?php echo $dateFri; ?>&filter_connected=1" class="text-success"><?php echo $t_fri_conn; ?></a></td>
                        
                        <td class="success"><a href="<?php echo $teamBaseLink; ?>&filter_connected=called"><?php echo $t_week_calls; ?></a></td>
                        <td class="success"><a href="<?php echo $teamBaseLink; ?>&filter_connected=1" style="color:green;"><?php echo $t_week_conn; ?></a></td>
                        
                        <td class="warning"><a href="?action=callreports&report_type=monthly&filter_date=<?php echo $filter_date; ?>&view_uid=0&filter_connected=called"><?php echo $t_month_calls; ?></a></td>
                        <td class="warning"><a href="?action=callreports&report_type=monthly&filter_date=<?php echo $filter_date; ?>&view_uid=0&filter_connected=1" style="color:green;"><?php echo $t_month_conn; ?></a></td>
                    </tr>
                </tbody>
            </table>

        <?php 
        /* =========================================================================
           MONTHLY VIEW
        ========================================================================= */
        } elseif ($report_type == 'monthly') { 
        ?>
            <h4>Monthly Report for <?php echo date("F Y", strtotime($filter_date)); ?></h4>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr class="info">
                        <th>Recruiter Name</th>
                        <th>Total Assigned</th>
                        <th>Total Called</th>
                        <th>Connected</th>
                        <th>Not Connected</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $monthSql = "SELECT u.uid, u.name,
                                COUNT(s.cl_id) as assigned_count,
                                SUM(CASE WHEN s.called = 1 THEN 1 ELSE 0 END) as total_called,
                                SUM(CASE WHEN s.called = 1 AND s.connected = 1 THEN 1 ELSE 0 END) as total_connected,
                                SUM(CASE WHEN s.called = 1 AND s.connected = 0 THEN 1 ELSE 0 END) as not_connected
                                FROM users u
                                INNER JOIN client_call_schedule s ON u.uid = s.uid 
                                WHERE s.call_date BETWEEN :startm AND :endm
                                GROUP BY u.uid, u.name";
                    
                    $monthStmt = $conn->prepare($monthSql);
                    $monthStmt->execute([':startm' => $startMonth, ':endm' => $endMonth]);
                    
                    $sum_assigned = 0; $sum_called = 0; $sum_conn = 0; $sum_not_conn = 0;

                    while ($row = $monthStmt->fetch(PDO::FETCH_ASSOC)) {
                        $sum_assigned += $row['assigned_count'];
                        $sum_called += $row['total_called'];
                        $sum_conn += $row['total_connected'];
                        $sum_not_conn += $row['not_connected'];

                        $baseLink = "?action=callreports&report_type=monthly&filter_date=$filter_date&view_uid=".$row['uid'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><a href="<?php echo $baseLink; ?>" style="font-weight:bold;"><?php echo (int)$row['assigned_count']; ?></a></td>
                            <td><a href="<?php echo $baseLink; ?>&filter_connected=called"><?php echo (int)$row['total_called']; ?></a></td>
                            <td><a href="<?php echo $baseLink; ?>&filter_connected=1" class="text-success" style="font-weight:bold;"><?php echo (int)$row['total_connected']; ?></a></td>
                            <td><a href="<?php echo $baseLink; ?>&filter_connected=0" class="text-danger" style="font-weight:bold;"><?php echo (int)$row['not_connected']; ?></a></td>
                        </tr>
                    <?php } 
                    
                    // Team Total Row for Monthly
                    $teamBaseLink = "?action=callreports&report_type=monthly&filter_date=$filter_date&view_uid=0";
                    ?>
                    <tr style="font-weight:bold; background-color: #f9f9f9;">
                        <td>Team Total</td>
                        <td><a href="<?php echo $teamBaseLink; ?>"><?php echo $sum_assigned; ?></a></td>
                        <td><a href="<?php echo $teamBaseLink; ?>&filter_connected=called"><?php echo $sum_called; ?></a></td>
                        <td><a href="<?php echo $teamBaseLink; ?>&filter_connected=1" class="text-success"><?php echo $sum_conn; ?></a></td>
                        <td><a href="<?php echo $teamBaseLink; ?>&filter_connected=0" class="text-danger"><?php echo $sum_not_conn; ?></a></td>
                    </tr>
                </tbody>
            </table>
        <?php } ?>

        </div> <!-- End Table Responsive -->
    </div>
</div>

<?php 
/* =========================================================================
   DETAILED VIEW & EXPORT
========================================================================= */
if ($view_uid !== null) { 
?>
<!-- Detailed View Panel -->
<div class="panel panel-info">
    <div class="panel-heading">
        <?php echo ($view_uid == 0) ? "Team-Wide Call Details & Export" : "User Call Details & Export"; ?>
    </div>
    <div class="panel-body">
        
        <!-- Detail Filters & CSV Export -->
        <form method="get" class="form-inline">
            <input type="hidden" name="action" value="callreports">
            <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($report_type); ?>">
            <input type="hidden" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
            <input type="hidden" name="view_uid" value="<?php echo $view_uid; ?>">
            <input type="hidden" name="detail_date" value="<?php echo htmlspecialchars($detail_date); ?>">
            
            <div class="form-group">
                <label>Status:</label>
                <select name="filter_connected" class="form-control">
                    <option value="">-- All Assigned --</option>
                    <option value="called" <?php if($filter_connected == 'called') echo 'selected'; ?>>All Called</option>
                    <option value="not_called" <?php if($filter_connected == 'not_called') echo 'selected'; ?>>Assigned (Not Called)</option>
                    <option value="1" <?php if($filter_connected == '1') echo 'selected'; ?>>Called & Connected</option>
                    <option value="0" <?php if($filter_connected == '0') echo 'selected'; ?>>Called & Not Connected</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Response Type:</label>
                <select name="filter_response" class="form-control">
                    <option value="">-- All --</option>
                    <?php foreach($responseTypes as $rt) { ?>
                        <option value="<?php echo $rt['id']; ?>" <?php if($filter_response == $rt['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($rt['response_name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-info">Apply Filters</button>
            
            <button type="submit" formaction="export_reports.php" class="btn btn-success pull-right">
                <span class="glyphicon glyphicon-download-alt"></span> Download CSV
            </button>
        </form>
        <br>

        <table class="table table-bordered table-striped" data-toggle="table" data-pagination="true" data-search="true">
            <thead>
                <tr>
                    <th>S.no</th>
                    <th>Recruiter</th>
                    <th>Company Name</th>
                    <th>Contact Name</th>
                    <th>Phone</th>
                    <th>Schedule Date</th>
                    <th>Status</th>
                    <th>Response Type</th>
                    <th>Latest Comment</th>
                    <th>History</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Detail Query Building Based on the Schedule Table
                $detailParams = [];
                $detailConds = " 1=1 ";
                
                // If view_uid is set and greater than 0, filter by that user. If 0, show all users.
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

                $detailSql = "SELECT c.cid, c.companyname, c.rname, c.rphone, 
                              s.call_date, s.called, s.connected, s.latest_comment,
                              rt.response_name, u.name AS recruiter_name
                              FROM client_call_schedule s
                              JOIN clients c ON s.cid = c.cid
                              JOIN users u ON s.uid = u.uid
                              LEFT JOIN client_response_type rt ON s.response_type_id = rt.id
                              WHERE $detailConds
                              ORDER BY s.call_date DESC";

                $detailStmt = $conn->prepare($detailSql);
                $detailStmt->execute($detailParams);

                $i = 1;
                while ($dRow = $detailStmt->fetch(PDO::FETCH_ASSOC)) {
                    ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($dRow['recruiter_name']); ?></td>
                        <td><?php echo htmlspecialchars($dRow['companyname']); ?></td>
                        <td><?php echo htmlspecialchars($dRow['rname']); ?></td>
                        <td><?php echo htmlspecialchars($dRow['rphone']); ?></td>
                        <td><?php echo date("d-M-Y", strtotime($dRow['call_date'])); ?></td>
                        <td>
                            <?php if($dRow['called'] == 0) { ?>
                                <span class="label label-default">Assigned (Not Called)</span>
                            <?php } elseif($dRow['connected'] == 1) { ?>
                                <span class="label label-success">Connected</span>
                            <?php } else { ?>
                                <span class="label label-danger">Not Connected</span>
                            <?php } ?>
                        </td>
                        <td><?php echo htmlspecialchars($dRow['response_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($dRow['latest_comment'] ?? '-'); ?></td>
                        <td>
                            <a href="admin.php?action=clienthistory&cid=<?php echo $dRow['cid']; ?>" class="btn btn-xs btn-info">
                                <span class="glyphicon glyphicon-time"></span> History
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php } ?>

</div>

<?php 
require("includes/footer.php"); 
$conn = null;
?>