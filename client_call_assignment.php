<?php
require_once("config.php");

/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
*/

try {
    $conn = new PDO(
        DB_DSN,
        DB_USERNAME,
        DB_PASSWORD
    );

    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    die("Database connection failed.");
}

/*
|--------------------------------------------------------------------------
| Login and Session Validation
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['id']) ||
    (int)$_SESSION['id'] <= 0
) {
    header("Location: admin.php");
    exit;
}

$sessid = (int)$_SESSION['id'];

$stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE uid = :uid
    LIMIT 1
");

$stmt->execute(array(
    ":uid" => $sessid
));

$dta = $stmt->fetch(PDO::FETCH_ASSOC);

if (
    !$dta ||
    !isset($_SESSION['username']) ||
    $dta['sess'] != $_SESSION['username']
) {
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
| Search
|--------------------------------------------------------------------------
*/

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$where = "
    WHERE c.status = 1
    AND LOWER(c.remail) NOT LIKE 'abc@%'
";

$params = array();

if ($search !== '') {
    $where .= "
        AND c.domain LIKE :search
    ";

    $params[':search'] =
        "%" . $search . "%";
}

echo '<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">';

/*
|--------------------------------------------------------------------------
| Allowed User Levels
|--------------------------------------------------------------------------
*/

if (
    in_array(
        (int)$dta['level'],
        array(1, 2, 3),
        true
    )
) {
    $uid = (int)$dta['uid'];

    /*
     * h:
     * Latest real completed call.
     *
     * Comment-only history rows beginning with [NOTE]
     * are excluded.
     *
     * ps:
     * Pending scheduled call where called = 0.
     */

    $sql = "
        SELECT
            c.*,

            h.call_datetime,
            h.connected,
            h.response_type_id,
            h.comment AS last_call_comment,

            last_user.name AS called_by,

            ps.cl_id AS pending_cl_id,
            ps.call_date AS scheduled_call_date,
            ps.created_datetime AS scheduled_created_datetime,

            scheduled_user.name AS scheduled_by,

            CASE
                WHEN h.call_datetime IS NOT NULL
                AND h.call_datetime >= DATE_SUB(
                    NOW(),
                    INTERVAL 5 DAY
                )
                THEN 1
                ELSE 0
            END AS called_within_five_days

        FROM clients c

        /*
         * Latest completed call.
         *
         * [NOTE] entries are not calls and therefore
         * must not be selected here.
         */

        LEFT JOIN client_call_history h
            ON h.history_id = (
                SELECT h2.history_id
                FROM client_call_history h2
                WHERE h2.cid = c.cid
                AND COALESCE(
                    h2.comment,
                    ''
                ) NOT LIKE '[NOTE]%'
                ORDER BY
                    h2.call_datetime DESC,
                    h2.history_id DESC
                LIMIT 1
            )

        LEFT JOIN users last_user
            ON last_user.uid = h.uid

        /*
         * Retrieve one unresolved scheduled call.
         *
         * Priority:
         * 1. Today or future schedule
         * 2. Latest expired schedule
         */

        LEFT JOIN client_call_schedule ps
            ON ps.cl_id = (
                SELECT s2.cl_id
                FROM client_call_schedule s2
                WHERE s2.cid = c.cid
                AND COALESCE(
                    s2.called,
                    0
                ) = 0

                ORDER BY
                    CASE
                        WHEN s2.call_date >= CURDATE()
                        THEN 0
                        ELSE 1
                    END ASC,

                    CASE
                        WHEN s2.call_date >= CURDATE()
                        THEN s2.call_date
                        ELSE NULL
                    END ASC,

                    CASE
                        WHEN s2.call_date < CURDATE()
                        THEN s2.call_date
                        ELSE NULL
                    END DESC,

                    s2.cl_id DESC

                LIMIT 1
            )

        LEFT JOIN users scheduled_user
            ON scheduled_user.uid = ps.uid

        " . $where;

    /*
     * Level 1:
     * Can view all clients.
     *
     * Levels 2 and 3:
     * Can view only their assigned client records.
     */

    if ((int)$dta['level'] === 1) {

        $sql .= "
            ORDER BY
                c.datetime DESC,

                CASE
                    WHEN c.rphone IS NOT NULL
                    AND TRIM(c.rphone) != ''
                    THEN 1
                    ELSE 0
                END DESC,

                c.cid DESC

            LIMIT 500
        ";

    } else {

        $sql .= "
            AND c.uid = :uid

            ORDER BY
                c.datetime DESC,

                CASE
                    WHEN c.rphone IS NOT NULL
                    AND TRIM(c.rphone) != ''
                    THEN 1
                    ELSE 0
                END DESC,

                c.cid DESC
        ";

        $params[':uid'] = $uid;
    }

    $stmt = $conn->prepare($sql);

    foreach ($params as $key => $value) {

        if ($key === ':uid') {

            $stmt->bindValue(
                $key,
                $value,
                PDO::PARAM_INT
            );

        } else {

            $stmt->bindValue(
                $key,
                $value,
                PDO::PARAM_STR
            );
        }
    }

    $stmt->execute();

    $clients =
        $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
/*
|--------------------------------------------------------------------------
| Assignment Success Message
|--------------------------------------------------------------------------
*/
?>

<?php if (isset($_GET['assigned'])) { ?>

    <div class="alert alert-success">

        <strong>Success!</strong>

        <?php echo (int)$_GET['assigned']; ?>
        client(s) assigned successfully.

        <?php
        if (
            isset($_GET['duplicate']) &&
            (int)$_GET['duplicate'] > 0
        ) {
        ?>

            <br>

            <strong>
                <?php echo (int)$_GET['duplicate']; ?>
            </strong>

            client(s) were skipped because they already had an active
            schedule or were called within the last five days.

        <?php } ?>

        <?php
        if (
            isset($_GET['failed']) &&
            (int)$_GET['failed'] > 0
        ) {
        ?>

            <br>

            <strong>
                <?php echo (int)$_GET['failed']; ?>
            </strong>

            invalid client(s) were skipped.

        <?php } ?>

    </div>

<?php } ?>

<?php
/*
|--------------------------------------------------------------------------
| Validation Messages
|--------------------------------------------------------------------------
*/
?>

<?php
if (
    isset($_GET['msg']) &&
    $_GET['msg'] === 'date'
) {
?>

    <div class="alert alert-danger">
        Please select a valid call date.
    </div>

<?php } ?>

<?php
if (
    isset($_GET['msg']) &&
    $_GET['msg'] === 'noclients'
) {
?>

    <div class="alert alert-danger">
        Please select at least one client.
    </div>

<?php } ?>

<div id="assignmentActionMessage" style="display:none;"></div>

<div class="panel panel-default">

    <div class="panel-body">

        <form
            method="post"
            action="clientcallcmd.php?action=assign"
            id="assignmentForm">

            <div class="row">

                <!-- Call Date -->

                <div class="col-md-3">

                    <label for="call_date">
                        Call Date
                    </label>

                    <input
                        type="date"
                        name="call_date"
                        id="call_date"
                        class="form-control"
                        value="<?php echo date('Y-m-d'); ?>"
                        min="<?php echo date('Y-m-d'); ?>"
                        required>

                </div>

                <!-- Assignment Button -->

                <div class="col-md-3">

                    <label>&nbsp;</label>

                    <div>

                        <button
                            type="submit"
                            class="btn btn-success"
                            id="assignButton"
                            name="assign_submit"
                            value="1">

                            Assign Selected Clients

                        </button>

                    </div>

                </div>

                <!-- Selection Tools -->

                <div class="col-md-3">

                    <h4>
                        Selected:
                        <span id="selectedCount">0</span>
                    </h4>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="selectAll">

                        Select All

                    </button>

                    <button
                        type="button"
                        class="btn btn-warning"
                        id="clearAll">

                        Clear

                    </button>

                </div>

                <!-- Domain Search -->

                <?php
                if ((int)$dta['level'] === 1) {
                ?>

                    <div class="col-md-3">

                        <div class="form-group">

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Domain"
                                value="<?php
                                echo htmlspecialchars(
                                    $search,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>">

                        </div>

                        <button
                            type="submit"
                            formaction="admin.php?action=clientassignment"
                            formmethod="get"
                            class="btn btn-primary"
                            id="searchButton">

                            <span class="glyphicon glyphicon-search"></span>

                            Search

                        </button>

                        <input
                            type="hidden"
                            name="action"
                            value="clientassignment">

                    </div>

                <?php } ?>

            </div>

            <br>

            <?php if ($search !== '') { ?>

                <div class="alert alert-success">

                    Showing search results for

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $search,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                    </strong>

                    (Maximum 500 records)

                </div>

            <?php } ?>

            <table
                data-toggle="table"
                data-search="true"
                data-pagination="true"
                data-show-columns="true"
                data-show-toggle="true"
                data-show-refresh="true"
                data-page-size="100"
                class="table table-bordered table-hover">

                <thead>

                    <tr>

                        <th>
                            <input
                                type="checkbox"
                                id="masterCheck">
                        </th>

                        <th data-field="id">
                            S.no
                        </th>

                        <th
                            data-field="company"
                            data-sortable="true">

                            Company

                        </th>

                        <th
                            data-field="contact"
                            data-sortable="true">

                            Name

                        </th>

                        <th
                            data-field="email"
                            data-sortable="true">

                            Email

                        </th>

                        <th
                            data-field="phone"
                            data-sortable="true">

                            Phone

                        </th>

                        <th
                            data-field="domain"
                            data-sortable="true"
                            data-visible="false">

                            Domain

                        </th>

                        <th
                            data-field="location"
                            data-sortable="true"
                            data-visible="false">

                            Location

                        </th>

                        <th
                            data-field="timezone"
                            data-sortable="true"
                            data-visible="false">

                            Timezone

                        </th>

                        <th
                            data-field="tier"
                            data-sortable="true"
                            data-visible="false">

                            Tier

                        </th>

                        <th
                            data-field="lastcall"
                            data-sortable="true">

                            Last Called

                        </th>

                        <th
                            data-field="lastby"
                            data-sortable="true">

                            Last By

                        </th>

                        <th
                            data-field="status"
                            data-sortable="true">

                            Status

                        </th>

                        <th data-field="history">
                            Call History
                        </th>

                        <th data-field="editaction">
                            Edit
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php
                $i = 1;
                $today = date('Y-m-d');

                foreach ($clients as $row) {

                    /*
                     * Pending schedule information.
                     */

                    $hasPendingSchedule =
                        !empty($row['pending_cl_id']) &&
                        !empty($row['scheduled_call_date']);

                    $scheduledDateDb =
                        $hasPendingSchedule
                            ? $row['scheduled_call_date']
                            : '';

                    $scheduledDateText =
                        $hasPendingSchedule
                            ? date(
                                'd-M-Y',
                                strtotime($scheduledDateDb)
                            )
                            : '';

                    /*
                     * Active schedule:
                     * Today or future date.
                     */

                    $hasActiveSchedule =
                        $hasPendingSchedule &&
                        $scheduledDateDb >= $today;

                    /*
                     * Expired schedule:
                     * Date passed but no call was saved.
                     */

                    $hasExpiredSchedule =
                        $hasPendingSchedule &&
                        $scheduledDateDb < $today;

                    /*
                     * Latest real completed call was within five days.
                     *
                     * Comment-only [NOTE] entries have already been
                     * excluded from the latest-call SQL join.
                     */

                    $calledWithinFiveDays =
                        isset(
                            $row['called_within_five_days']
                        ) &&
                        (int)$row[
                            'called_within_five_days'
                        ] === 1;

                    /*
                     * Disable selection only when:
                     *
                     * 1. There is an active schedule; or
                     * 2. A real call occurred within the last five days.
                     *
                     * An expired schedule remains selectable.
                     */

                    $disableSelection =
                        $hasActiveSchedule ||
                        $calledWithinFiveDays;

                    /*
                     * Row highlighting.
                     */

                    $rowClass = '';

                    if ($hasActiveSchedule) {
                        $rowClass = 'warning';
                    } elseif ($hasExpiredSchedule) {
                        $rowClass = 'danger';
                    }
                ?>

                    <tr
                        id="assignment-row-<?php echo (int)$row['cid']; ?>"
                        data-cid="<?php echo (int)$row['cid']; ?>"
                        class="<?php echo htmlspecialchars($rowClass, ENT_QUOTES, 'UTF-8'); ?>">

                        <!-- Selection -->

                        <td class="assignment-selection">

                            <?php if ($disableSelection) { ?>

                                <input
                                    type="checkbox"
                                    class="clientCheck"
                                    disabled>

                                <br>

                                <?php
                                if ($calledWithinFiveDays) {
                                ?>

                                    <small class="text-danger">
                                        <!--

                                        <strong>
                                            Called within last 5 days
                                        </strong> -->

                                    </small>

                                <?php
                                } elseif ($hasActiveSchedule) {
                                ?>

                                    <small class="text-warning">
<!--
                                        <strong>
                                            Already scheduled
                                        </strong> -->

                                    </small>

                                <?php } ?>

                            <?php } else { ?>

                                <input
                                    type="checkbox"
                                    class="clientCheck"
                                    name="client[]"
                                    value="<?php
                                    echo (int)$row['cid'];
                                    ?>"
                                    data-company="<?php
                                    echo htmlspecialchars(
                                        $row['companyname'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>">

                                <?php
                                if ($hasExpiredSchedule) {
                                ?>

                                    <br>

                                    <small class="text-danger">
                                        <!--

                                        <strong>
                                            Missed schedule — select to reschedule
                                        </strong> <!--

                                    </small>

                                <?php } ?>

                            <?php } ?>

                        </td>

                        <!-- Serial Number -->

                        <td>
                            <?php echo $i++; ?>
                        </td>

                        <!-- Company -->

                        <td class="assignment-company">
                            <?php
                            echo htmlspecialchars(
                                $row['companyname'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                        <!-- Contact -->

                        <td class="assignment-contact">
                            <?php
                            echo htmlspecialchars(
                                $row['rname'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                        <!-- Email -->

                        <td class="assignment-email">
                            <?php
                            echo htmlspecialchars(
                                $row['remail'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                        <!-- Phone -->

                        <td class="assignment-phone">
                            <?php
                            echo htmlspecialchars(
                                $row['rphone'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                        <!-- Domain -->

                        <td class="assignment-domain">
                            <?php
                            echo htmlspecialchars(
                                $row['domain'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                        <!-- Location -->

                        <td class="assignment-location">
                            <?php
                            echo htmlspecialchars(
                                isset($row['rlocation'])
                                    ? $row['rlocation']
                                    : '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                        <!-- Timezone -->

                        <td class="assignment-timezone">
                            <?php
                            echo htmlspecialchars(
                                isset($row['rtimezon'])
                                    ? $row['rtimezon']
                                    : '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                        <!-- Tier -->

                        <td class="assignment-tier">
                            <?php
                            echo htmlspecialchars(
                                isset($row['tier'])
                                    ? $row['tier']
                                    : '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                        <!-- Last Called / Scheduled Date -->

                        <td class="assignment-lastcall">

                            <?php if ($hasActiveSchedule) { ?>

                                <span class="label label-warning">
                                    Call Scheduled
                                </span>

                                <br>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $scheduledDateText,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                </strong>

                            <?php
                            } elseif ($hasExpiredSchedule) {
                            ?>

                                <span class="label label-danger">
                                    Scheduled but Not Called
                                </span>

                                <br>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $scheduledDateText,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                </strong>

                                <br>

                                <small class="text-danger">
                                    Date passed
                                </small>

                            <?php
                            } elseif (empty($row['call_datetime'])) {
                            ?>

                                <span class="text-muted">
                                    Never
                                </span>

                            <?php } else { ?>

                                <?php
                                echo date(
                                    'd-M-Y',
                                    strtotime(
                                        $row['call_datetime']
                                    )
                                );
                                ?>

                                <?php
                                if ($calledWithinFiveDays) {
                                ?>

                                    <br>

                                    <small class="text-danger">
                                        Called within last 5 days
                                    </small>

                                <?php } ?>

                            <?php } ?>

                        </td>

                        <!-- Last By / Scheduled By -->

                        <td class="assignment-lastby">

                            <?php
                            if ($hasPendingSchedule) {
                            ?>

                                <?php
                                echo !empty(
                                    $row['scheduled_by']
                                )
                                    ? htmlspecialchars(
                                        $row['scheduled_by'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                    : '-';
                                ?>

                            <?php } else { ?>

                                <?php
                                echo !empty(
                                    $row['called_by']
                                )
                                    ? htmlspecialchars(
                                        $row['called_by'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                    : '-';
                                ?>

                            <?php } ?>

                        </td>

                        <!-- Status -->

                        <td class="assignment-status">

                            <?php if ($hasActiveSchedule) { ?>

                                <span class="label label-warning">
                                    Scheduled - Not Called
                                </span>

                            <?php
                            } elseif ($hasExpiredSchedule) {
                            ?>

                                <span class="label label-danger">
                                    Scheduled - Not Called
                                </span>

                                <br>

                                <small class="text-danger">
                                    Schedule date passed
                                </small>

                                <?php
                                if (!$calledWithinFiveDays) {
                                ?>

                                    <br>

                                    <small class="text-success">
                                        Available to reschedule
                                    </small>

                                <?php } ?>

                            <?php
                            } elseif (empty($row['call_datetime'])) {
                            ?>

                                <span class="label label-default">
                                    Never Called
                                </span>

                            <?php
                            } elseif ((int)$row['connected'] === 1) {
                            ?>

                                <span class="label label-success">
                                    Connected
                                </span>

                                <?php
                                if ($calledWithinFiveDays) {
                                ?>

                                    <br>

                                    <small class="text-danger">
                                        Selection locked for 5 days
                                    </small>

                                <?php } ?>

                            <?php } else { ?>

                                <span class="label label-danger">
                                    Not Connected
                                </span>

                                <?php
                                if ($calledWithinFiveDays) {
                                ?>

                                    <br>

                                    <small class="text-danger">
                                        Selection locked for 5 days
                                    </small>

                                <?php } ?>

                            <?php } ?>

                        </td>

                        <!-- History -->

                        <td>

                            <a
                                href="admin.php?action=clienthistory&amp;cid=<?php
                                echo (int)$row['cid'];
                                ?>" target="_blank"
                                class="btn btn-xs btn-info">

                                <span class="glyphicon glyphicon-time"></span>

                                History

                            </a>

                        </td>

                        <!-- Edit / Delete -->

                        <td class="assignment-actions">

                            <button
                                type="button"
                                class="btn btn-xs btn-info btnEditClient"
                                data-cid="<?php echo (int)$row['cid']; ?>">
                                <span class="glyphicon glyphicon-pencil"></span> Edit
                            </button>

                            <button
                                type="button"
                                class="btn btn-xs btn-danger btnDeleteClient"
                                data-cid="<?php echo (int)$row['cid']; ?>"
                                data-company="<?php echo htmlspecialchars($row['companyname'], ENT_QUOTES, 'UTF-8'); ?>">
                                <span class="glyphicon glyphicon-trash"></span> Delete
                            </button>

                        </td>

                    </tr>

                <?php } ?>

                <?php if (empty($clients)) { ?>

                    <tr>

                        <td
                            colspan="15"
                            class="text-center text-muted">

                            No clients found.

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

            <input
                type="hidden"
                name="assigned_by"
                value="<?php echo $sessid; ?>">

        </form>

    </div>

</div>

<!-- Client Edit Modal -->
<div class="modal fade" id="clientEditModal" tabindex="-1" role="dialog" aria-labelledby="clientEditModalTitle">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="clientEditModalTitle">Edit Client</h4>
            </div>
            <form id="clientEditForm">
                <div class="modal-body">
                    <input type="hidden" name="cid" id="edit_client_cid">
                    <div class="form-group"><label for="edit_companyname">Company Name</label><input type="text" class="form-control" name="companyname" id="edit_companyname" required></div>
                    <div class="form-group"><label for="edit_rname">Full Name</label><input type="text" class="form-control" name="rname" id="edit_rname"></div>
                    <div class="form-group"><label for="edit_rfname">First Name</label><input type="text" class="form-control" name="rfname" id="edit_rfname"></div>
                    <div class="form-group"><label for="edit_remail">Email ID</label><input type="email" class="form-control" name="remail" id="edit_remail"></div>
                    <div class="form-group"><label for="edit_rphone">Phone Number</label><input type="text" class="form-control" name="rphone" id="edit_rphone"></div>
                    <div class="form-group"><label for="edit_rlocation">Location</label><input type="text" class="form-control" name="rlocation" id="edit_rlocation"></div>
                    <div class="form-group"><label for="edit_tier">Tier</label><select class="form-control" name="tier" id="edit_tier"><option value="Tier 1">Tier 1</option><option value="Tier 2">Tier 2</option><option value="Implementation Partner">Implementation Partner</option></select></div>
                    <div class="form-group"><label for="edit_rtimezon">Timezone</label><select class="form-control" name="rtimezon" id="edit_rtimezon"><option value="EST">EST</option><option value="CST">CST</option><option value="MST">MST</option><option value="PST">PST</option></select></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" id="btnSaveClient" class="btn btn-primary">Save Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    'use strict';

    /*
    |--------------------------------------------------------------------------
    | Update Selected Count
    |--------------------------------------------------------------------------
    */

    function updateSelectedCount() {

        $('#selectedCount').text(
            $('.clientCheck:checked').length
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Individual Checkbox
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'change',
        '.clientCheck',
        function () {
            updateSelectedCount();
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Master Checkbox
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'change',
        '#masterCheck',
        function () {

            $('.clientCheck:not(:disabled)').prop(
                'checked',
                $(this).prop('checked')
            );

            updateSelectedCount();
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Select All
    |--------------------------------------------------------------------------
    */

    $('#selectAll').on(
        'click',
        function () {

            $('.clientCheck:not(:disabled)').prop(
                'checked',
                true
            );

            $('#masterCheck').prop(
                'checked',
                true
            );

            updateSelectedCount();
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Clear All
    |--------------------------------------------------------------------------
    */

    $('#clearAll').on(
        'click',
        function () {

            $('.clientCheck').prop(
                'checked',
                false
            );

            $('#masterCheck').prop(
                'checked',
                false
            );

            updateSelectedCount();
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Page Message
    |--------------------------------------------------------------------------
    */
    function showAssignmentMessage(message, type) {
        var cls = type === 'error' ? 'alert-danger' : 'alert-success';
        $('#assignmentActionMessage')
            .removeClass('alert-success alert-danger')
            .addClass('alert ' + cls)
            .text(message)
            .stop(true, true)
            .show();
        window.setTimeout(function () {
            $('#assignmentActionMessage').fadeOut();
        }, 4500);
    }

    function setText($row, selector, value) {
        $row.find(selector).text(value === null || typeof value === 'undefined' ? '' : String(value));
    }

    function updateAssignmentRow(rowData) {
        var cid = parseInt(rowData.cid, 10);
        var $row = $('#assignment-row-' + cid);
        if (!$row.length) { return; }

        setText($row, '.assignment-company', rowData.companyname);
        setText($row, '.assignment-contact', rowData.rname);
        setText($row, '.assignment-email', rowData.remail);
        setText($row, '.assignment-phone', rowData.rphone);
        setText($row, '.assignment-domain', rowData.domain);
        setText($row, '.assignment-location', rowData.rlocation);
        setText($row, '.assignment-timezone', rowData.rtimezon);
        setText($row, '.assignment-tier', rowData.tier);

        $row.find('.assignment-selection').html(rowData.selection_html || '');
        $row.find('.assignment-lastcall').html(rowData.lastcall_html || '');
        setText($row, '.assignment-lastby', rowData.last_by || '-');
        $row.find('.assignment-status').html(rowData.status_html || '');

        $row.removeClass('warning danger success');
        if (rowData.row_class) { $row.addClass(rowData.row_class); }

        $row.find('.btnDeleteClient').attr('data-company', rowData.companyname || 'this client');
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX Assignment - only Assign button; Search remains normal GET
    |--------------------------------------------------------------------------
    */
    $('#assignButton').on('click', function (event) {
        event.preventDefault();

        var $button = $(this);
        var selectedCount = $('.clientCheck:checked').length;

        if (selectedCount === 0) {
            alert('Please select at least one client.');
            return false;
        }

        if (!window.confirm('Assign selected clients to the selected date?')) {
            return false;
        }

        $button.prop('disabled', true).text('Assigning...');

        $.ajax({
            url: 'client_today_client_ajax.php?action=assign',
            type: 'POST',
            data: $('#assignmentForm').serialize(),
            dataType: 'json',
            cache: false
        }).done(function (res) {
            if (!res || res.status !== 'success') {
                alert(res && res.message ? res.message : 'Unable to assign selected clients.');
                return;
            }

            if (res.rows && $.isArray(res.rows)) {
                $.each(res.rows, function (_, rowData) {
                    updateAssignmentRow(rowData);
                });
            }

            $('.clientCheck:checked').prop('checked', false);
            $('#masterCheck').prop('checked', false);
            updateSelectedCount();

            var message = (parseInt(res.assigned, 10) || 0) + ' client(s) assigned successfully.';
            if ((parseInt(res.duplicate, 10) || 0) > 0) {
                message += ' ' + res.duplicate + ' skipped due to an active schedule or recent call.';
            }
            if ((parseInt(res.failed, 10) || 0) > 0) {
                message += ' ' + res.failed + ' invalid client(s) skipped.';
            }
            showAssignmentMessage(message, 'success');
        }).fail(function (xhr) {
            console.log(xhr.responseText);
            alert('Unable to assign selected clients.');
        }).always(function () {
            $button.prop('disabled', false).text('Assign Selected Clients');
        });

        return false;
    });

    /*
    |--------------------------------------------------------------------------
    | Edit Client Modal
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '.btnEditClient', function () {
        var cid = parseInt($(this).attr('data-cid'), 10);
        if (!cid) { return; }

        $.ajax({
            url: 'client_today_client_ajax.php',
            type: 'GET',
            data: { action: 'getclient', cid: cid, _: new Date().getTime() },
            dataType: 'json',
            cache: false
        }).done(function (res) {
            if (!res || res.status !== 'success' || !res.client) {
                alert(res && res.message ? res.message : 'Unable to load client details.');
                return;
            }
            $('#clientEditForm')[0].reset();
            $('#edit_client_cid').val(res.client.cid);
            $('#edit_companyname').val(res.client.companyname || '');
            $('#edit_rname').val(res.client.rname || '');
            $('#edit_rfname').val(res.client.rfname || '');
            $('#edit_remail').val(res.client.remail || '');
            $('#edit_rphone').val(res.client.rphone || '');
            $('#edit_rlocation').val(res.client.rlocation || '');
            $('#edit_tier').val(res.client.tier || 'Tier 1');
            $('#edit_rtimezon').val(res.client.rtimezon || 'CST');
            $('#clientEditModal').modal('show');
        }).fail(function (xhr) {
            console.log(xhr.responseText);
            alert('Unable to load client details.');
        });
    });

    $('#clientEditForm').on('submit', function (event) {
        event.preventDefault();
        var cid = parseInt($('#edit_client_cid').val(), 10);
        var $button = $('#btnSaveClient');
        if (!cid) { alert('Invalid client.'); return; }

        $button.prop('disabled', true).text('Saving...');
        $.ajax({
            url: 'client_today_client_ajax.php?action=saveclient',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            cache: false
        }).done(function (res) {
            if (!res || res.status !== 'success') {
                alert(res && res.message ? res.message : 'Unable to save client.');
                return;
            }
            var $row = $('#assignment-row-' + cid);
            setText($row, '.assignment-company', $('#edit_companyname').val());
            setText($row, '.assignment-contact', $('#edit_rname').val());
            setText($row, '.assignment-email', $('#edit_remail').val());
            setText($row, '.assignment-phone', $('#edit_rphone').val());
            setText($row, '.assignment-location', $('#edit_rlocation').val());
            setText($row, '.assignment-timezone', $('#edit_rtimezon').val());
            setText($row, '.assignment-tier', $('#edit_tier').val());
            $row.find('.btnDeleteClient').attr('data-company', $('#edit_companyname').val());
            $('#clientEditModal').modal('hide');
            showAssignmentMessage(res.message || 'Client updated successfully.', 'success');
        }).fail(function (xhr) {
            console.log(xhr.responseText);
            alert('Unable to save client.');
        }).always(function () {
            $button.prop('disabled', false).text('Save Client');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Soft Delete Client
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '.btnDeleteClient', function () {
        var $button = $(this);
        var cid = parseInt($button.attr('data-cid'), 10);
        var company = $button.attr('data-company') || 'this client';
        if (!cid) { return; }
        if (!window.confirm('Are you sure you want to remove ' + company + ' from active clients?')) { return; }

        $button.prop('disabled', true);
        $.ajax({
            url: 'client_today_client_ajax.php?action=deleteclient',
            type: 'POST',
            data: { cid: cid },
            dataType: 'json',
            cache: false
        }).done(function (res) {
            if (!res || res.status !== 'success') {
                alert(res && res.message ? res.message : 'Unable to delete client.');
                $button.prop('disabled', false);
                return;
            }
            $('#assignment-row-' + cid).fadeOut(250, function () { $(this).remove(); });
            showAssignmentMessage(res.message || 'Client removed from active clients.', 'success');
            updateSelectedCount();
        }).fail(function (xhr) {
            console.log(xhr.responseText);
            alert('Unable to delete client.');
            $button.prop('disabled', false);
        });
    });
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