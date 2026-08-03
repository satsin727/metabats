<?php
session_start();
require_once("config.php");

/*
|--------------------------------------------------------------------------
| Login and Session Validation
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['id']) || (int)$_SESSION['id'] <= 0) {
    header("Location: admin.php");
    exit;
}

$sessid = (int)$_SESSION['id'];

try {
    $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

$stmt = $conn->prepare("SELECT * FROM users WHERE uid = :uid LIMIT 1");
$stmt->execute(array(":uid" => $sessid));
$dta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dta || !isset($_SESSION['username']) || $dta['sess'] != $_SESSION['username']) {
    echo "<script>
        alert('Not Authorised to view this page.');
        window.location.href='login.php';
    </script>";
    exit;
}

require("includes/header.php");
require("includes/menu.php");

/*
|--------------------------------------------------------------------------
| Search Filter
|--------------------------------------------------------------------------
*/

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = "WHERE c.status = 1 AND LOWER(c.remail) NOT LIKE 'abc@%'";
$params = array();

if ($search !== '') {
    $where .= " AND c.domain LIKE :search ";
    $params[':search'] = "%" . $search . "%";
}

echo '<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">';

/*
|--------------------------------------------------------------------------
| Allowed User Levels
|--------------------------------------------------------------------------
*/

if (in_array((int)$dta['level'], array(1, 2, 3), true)) {
    $uid = (int)$dta['uid'];

    /*
     * h: Latest completed call from client_call_history.
     * ps: Pending schedule where called = 0.
     *
     * Schedule priority:
     * 1. Earliest schedule for today or a future date.
     * 2. If no active schedule exists, latest missed schedule.
     */
    $sql = "
        SELECT
            c.*,
            h.call_datetime,
            h.connected,
            last_user.name AS called_by,
            ps.cl_id AS pending_cl_id,
            ps.call_date AS scheduled_call_date,
            ps.created_datetime AS scheduled_created_datetime,
            scheduled_user.name AS scheduled_by,
            CASE
                WHEN h.call_datetime IS NOT NULL 
                AND h.call_datetime >= DATE_SUB(NOW(), INTERVAL 5 DAY)
                THEN 1
                ELSE 0
            END AS called_within_five_days
        FROM clients c
        LEFT JOIN client_call_history h
            ON h.history_id = (
                SELECT h2.history_id
                FROM client_call_history h2
                WHERE h2.cid = c.cid
                ORDER BY h2.call_datetime DESC, h2.history_id DESC
                LIMIT 1
            )
        LEFT JOIN users last_user
            ON last_user.uid = h.uid
        LEFT JOIN client_call_schedule ps
            ON ps.cl_id = (
                SELECT s2.cl_id
                FROM client_call_schedule s2
                WHERE s2.cid = c.cid
                AND COALESCE(s2.called, 0) = 0
                ORDER BY
                    CASE WHEN s2.call_date >= CURDATE() THEN 0 ELSE 1 END ASC,
                    CASE WHEN s2.call_date >= CURDATE() THEN s2.call_date ELSE NULL END ASC,
                    CASE WHEN s2.call_date < CURDATE() THEN s2.call_date ELSE NULL END DESC,
                    s2.cl_id DESC
                LIMIT 1
            )
        LEFT JOIN users scheduled_user
            ON scheduled_user.uid = ps.uid
        " . $where;

    /*
     * Level 1: View all clients.
     * Levels 2 and 3: View only clients assigned to the logged-in user.
     */
    if ((int)$dta['level'] === 1) {
        $sql .= "
            ORDER BY
                c.datetime DESC,
                CASE WHEN c.rphone IS NOT NULL AND TRIM(c.rphone) != '' THEN 1 ELSE 0 END DESC,
                c.cid DESC
            LIMIT 500
        ";
    } else {
        $sql .= "
            AND c.uid = :uid
            ORDER BY
                c.datetime DESC,
                CASE WHEN c.rphone IS NOT NULL AND TRIM(c.rphone) != '' THEN 1 ELSE 0 END DESC,
                c.cid DESC
        ";
        $params[':uid'] = $uid;
    }

    $stmt = $conn->prepare($sql);

    foreach ($params as $key => $value) {
        if ($key === ':uid') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }

    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <?php if (isset($_GET['assigned'])) { ?>
        <div class="alert alert-success">
            <strong>Success!</strong> <?php echo (int)$_GET['assigned']; ?> client(s) assigned successfully.

            <?php if (isset($_GET['duplicate']) && (int)$_GET['duplicate'] > 0) { ?>
                <br>
                <strong><?php echo (int)$_GET['duplicate']; ?></strong> client(s) were skipped because they already had an active schedule or were called within the last five days.
            <?php } ?>

            <?php if (isset($_GET['failed']) && (int)$_GET['failed'] > 0) { ?>
                <br>
                <strong><?php echo (int)$_GET['failed']; ?></strong> invalid client(s) were skipped.
            <?php } ?>
        </div>
    <?php } ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'date') { ?>
        <div class="alert alert-danger">
            Please select a call date.
        </div>
    <?php } ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'noclients') { ?>
        <div class="alert alert-danger">
            Please select at least one client.
        </div>
    <?php } ?>

    <div class="panel panel-default">
        <div class="panel-body">
            <form method="post" action="clientcallcmd.php?action=assign" id="assignmentForm">
                <div class="row">
                    <div class="col-md-3">
                        <label>Call Date</label>
                        <input type="date" name="call_date" id="call_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-success" id="assignButton" name="assign_submit" value="1">
                                Assign Selected Clients
                            </button>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <h4>Selected: <span id="selectedCount">0</span></h4>
                        <button type="button" class="btn btn-primary" id="selectAll">Select All</button>
                        <button type="button" class="btn btn-warning" id="clearAll">Clear</button>
                    </div>

                    <?php if ((int)$dta['level'] === 1) { ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <input type="text" name="search" class="form-control" placeholder="Domain" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <button type="submit" formaction="admin.php?action=clientassignment" formmethod="get" class="btn btn-primary" id="searchButton">
                                <span class="glyphicon glyphicon-search"></span> Search
                            </button>
                            <input type="hidden" name="action" value="clientassignment">
                        </div>
                    <?php } ?>
                </div>
                <br>

                <?php if ($search !== '') { ?>
                    <div class="alert alert-success">
                        Showing search results for <strong><?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?></strong> (Maximum 500 records)
                    </div>
                <?php } ?>

                <table data-toggle="table" data-search="true" data-pagination="true" data-show-columns="true" data-show-toggle="true" data-show-refresh="true" data-page-size="25" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="masterCheck"></th>
                            <th data-field="id">S.no</th>
                            <th data-field="company" data-sortable="true">Company</th>
                            <th data-field="contact" data-sortable="true">Name</th>
                            <th data-field="email" data-sortable="true">Email</th>
                            <th data-field="phone" data-sortable="true">Phone</th>
                            <th data-field="domain" data-sortable="true" data-visible="false">Domain</th>
                            <th data-field="location" data-sortable="true" data-visible="false">Location</th>
                            <th data-field="timezone" data-sortable="true" data-visible="false">Timezone</th>
                            <th data-field="tier" data-sortable="true" data-visible="false">Tier</th>
                            <th data-field="lastcall" data-sortable="true">Last Called</th>
                            <th data-field="lastby" data-sortable="true">Last By</th>
                            <th data-field="status" data-sortable="true">Status</th>
                            <th data-field="history">Call History</th>
                            <th data-field="editaction">Edit</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php
                    $i = 1;
                    $today = date('Y-m-d');

                    foreach ($clients as $row) {
                        /* Pending schedule exists. */
                        $hasPendingSchedule = !empty($row['pending_cl_id']) && !empty($row['scheduled_call_date']);
                        $scheduledDateDb = $hasPendingSchedule ? $row['scheduled_call_date'] : '';
                        $scheduledDateText = $hasPendingSchedule ? date('d-M-Y', strtotime($scheduledDateDb)) : '';

                        /* Active schedule: Scheduled for today or later. */
                        $hasActiveSchedule = $hasPendingSchedule && $scheduledDateDb >= $today;

                        /* Expired schedule: Scheduled date passed and call was not completed. */
                        $hasExpiredSchedule = $hasPendingSchedule && $scheduledDateDb < $today;

                        /* Latest completed call occurred within five days. */
                        $calledWithinFiveDays = isset($row['called_within_five_days']) && (int)$row['called_within_five_days'] === 1;

                        /* Disable client selection */
                        $disableSelection = $hasActiveSchedule || $calledWithinFiveDays;

                        /* Row highlighting. */
                        $rowClass = '';
                        if ($hasActiveSchedule) {
                            $rowClass = 'warning';
                        } elseif ($hasExpiredSchedule) {
                            $rowClass = 'danger';
                        }
                    ?>
                        <tr<?php echo $rowClass !== '' ? ' class="' . $rowClass . '"' : ''; ?>>
                            
                            <!-- Selection -->
                            <td>
                                <?php if ($disableSelection) { ?>
                                    <input type="checkbox" class="clientCheck" disabled>
                                    <br>
                                    <?php if ($calledWithinFiveDays) { ?>
                                        <small class="text-danger">
                                            <!-- <strong>Called within last 5 days</strong> -->
                                        </small>
                                    <?php } elseif ($hasActiveSchedule) { ?>
                                        <small class="text-warning">
                                            <!-- <strong>Already scheduled</strong> -->
                                        </small>
                                    <?php } ?>
                                <?php } else { ?>
                                    <input type="checkbox" class="clientCheck" name="client[]" value="<?php echo (int)$row['cid']; ?>" data-company="<?php echo htmlspecialchars($row['companyname'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php if ($hasExpiredSchedule) { ?>
                                        <br>
                                        <small class="text-danger">
                                            <!-- <strong>Missed schedule — select to reschedule</strong> -->
                                        </small>
                                    <?php } ?>
                                <?php } ?>
                            </td>

                            <!-- Serial Number -->
                            <td><?php echo $i++; ?></td>

                            <!-- Company -->
                            <td><?php echo htmlspecialchars($row['companyname'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <!-- Contact Name -->
                            <td><?php echo htmlspecialchars($row['rname'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <!-- Email -->
                            <td><?php echo htmlspecialchars($row['remail'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <!-- Phone -->
                            <td><?php echo htmlspecialchars($row['rphone'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <!-- Domain -->
                            <td><?php echo htmlspecialchars($row['domain'], ENT_QUOTES, 'UTF-8'); ?></td>

                            <!-- Location -->
                            <td><?php echo htmlspecialchars(isset($row['rlocation']) ? $row['rlocation'] : '', ENT_QUOTES, 'UTF-8'); ?></td>

                            <!-- Timezone -->
                            <td><?php echo htmlspecialchars(isset($row['rtimezon']) ? $row['rtimezon'] : '', ENT_QUOTES, 'UTF-8'); ?></td>

                            <!-- Tier -->
                            <td><?php echo htmlspecialchars(isset($row['tier']) ? $row['tier'] : '', ENT_QUOTES, 'UTF-8'); ?></td>

                            <!-- Last Called / Scheduled Date -->
                            <td>
                                <?php if ($hasActiveSchedule) { ?>
                                    <span class="label label-warning">Call Scheduled</span><br>
                                    <strong><?php echo htmlspecialchars($scheduledDateText, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php } elseif ($hasExpiredSchedule) { ?>
                                    <span class="label label-danger">Scheduled but Not Called</span><br>
                                    <strong><?php echo htmlspecialchars($scheduledDateText, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                    <small class="text-danger">Date passed</small>
                                <?php } elseif (empty($row['call_datetime'])) { ?>
                                    <span class="text-muted">Never</span>
                                <?php } else { ?>
                                    <?php echo date('d-M-Y', strtotime($row['call_datetime'])); ?>
                                    <?php if ($calledWithinFiveDays) { ?>
                                        <br><small class="text-danger">Called within last 5 days</small>
                                    <?php } ?>
                                <?php } ?>
                            </td>

                            <!-- Last Called By / Scheduled By -->
                            <td>
                                <?php if ($hasPendingSchedule) { ?>
                                    <?php echo !empty($row['scheduled_by']) ? htmlspecialchars($row['scheduled_by'], ENT_QUOTES, 'UTF-8') : '-'; ?>
                                <?php } else { ?>
                                    <?php echo !empty($row['called_by']) ? htmlspecialchars($row['called_by'], ENT_QUOTES, 'UTF-8') : '-'; ?>
                                <?php } ?>
                            </td>

                            <!-- Current Status -->
                            <td>
                                <?php if ($hasActiveSchedule) { ?>
                                    <span class="label label-warning">Scheduled - Not Called</span>
                                <?php } elseif ($hasExpiredSchedule) { ?>
                                    <span class="label label-danger">Scheduled - Not Called</span><br>
                                    <small class="text-danger">Schedule date passed</small>
                                    <?php if (!$calledWithinFiveDays) { ?>
                                        <br><small class="text-success">Available to reschedule</small>
                                    <?php } ?>
                                <?php } elseif (empty($row['call_datetime'])) { ?>
                                    <span class="label label-default">Never Called</span>
                                <?php } elseif ((int)$row['connected'] === 1) { ?>
                                    <span class="label label-success">Connected</span>
                                    <?php if ($calledWithinFiveDays) { ?>
                                        <br><small class="text-danger">Selection locked for 5 days</small>
                                    <?php } ?>
                                <?php } else { ?>
                                    <span class="label label-danger">Not Connected</span>
                                    <?php if ($calledWithinFiveDays) { ?>
                                        <br><small class="text-danger">Selection locked for 5 days</small>
                                    <?php } ?>
                                <?php } ?>
                            </td>

                            <!-- History -->
                            <td>
                                <a href="admin.php?action=clienthistory&cid=<?php echo (int)$row['cid']; ?>" class="btn btn-xs btn-info">
                                    <span class="glyphicon glyphicon-time"></span> History
                                </a>
                            </td>

                            <!-- Edit -->
                            <td>
                                <a href="listcmd.php?do=editcontact&lid=<?php echo (int)$row['lid']; ?>&id=<?php echo (int)$row['cid']; ?>" class="btn btn-xs btn-info">
                                    <span class="glyphicon glyphicon-pencil"></span> Edit
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <input type="hidden" name="assigned_by" value="<?php echo $sessid; ?>">
            </form>
        </div>
    </div>

<script>
    function updateSelectedCount() {
        document.getElementById("selectedCount").innerHTML = document.querySelectorAll(".clientCheck:checked").length;
    }

    /*
    |--------------------------------------------------------------------------
    | Individual Checkbox
    |--------------------------------------------------------------------------
    */
    $(document).on("change", ".clientCheck", function () {
        updateSelectedCount();
    });

    /*
    |--------------------------------------------------------------------------
    | Master Checkbox
    |--------------------------------------------------------------------------
    */
    $(document).on("change", "#masterCheck", function () {
        $(".clientCheck:not(:disabled)").prop("checked", $(this).prop("checked"));
        updateSelectedCount();
    });

    /*
    |--------------------------------------------------------------------------
    | Select All
    |--------------------------------------------------------------------------
    */
    $("#selectAll").click(function () {
        $(".clientCheck:not(:disabled)").prop("checked", true);
        $("#masterCheck").prop("checked", true);
        updateSelectedCount();
    });

    /*
    |--------------------------------------------------------------------------
    | Clear All
    |--------------------------------------------------------------------------
    */
    $("#clearAll").click(function () {
        $(".clientCheck").prop("checked", false);
        $("#masterCheck").prop("checked", false);
        updateSelectedCount();
    });

    /*
    |--------------------------------------------------------------------------
    | Assignment Form Validation
    |--------------------------------------------------------------------------
    */
    $("#assignmentForm").submit(function (e) {
        var submitter = null;

        if (e.originalEvent && e.originalEvent.submitter) {
            submitter = e.originalEvent.submitter;
        } else {
            submitter = document.activeElement;
        }

        /*
         * Validate selected clients only when the assignment
         * button submitted the form.
         */
        if (submitter && submitter.id === "assignButton") {
            if ($(".clientCheck:checked").length === 0) {
                alert("Please select at least one client.");
                e.preventDefault();
                return false;
            }
            return confirm("Assign selected clients to the selected date?");
        }
    });
</script>

<?php
} else {
    echo "<script>
        alert('You Need to be Admin to view this page.');
        window.location.href='admin.php';
    </script>";
}

echo "</div>";

require("includes/footer.php");

$conn = null;
?>