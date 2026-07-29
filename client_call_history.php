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

if (!isset($_SESSION['username']) || $dta['sess'] != $_SESSION['username']) {
    echo "<script>
        alert('Not Authorised to view this page.');
        window.location.href='login.php';
    </script>";
    exit;
}

$cid = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;

if ($cid <= 0) {
    echo "<script>
        alert('Invalid Client.');
        window.location.href='admin.php?action=callinglist';
    </script>";
    exit;
}

/*==========================================================
=            Client Information
==========================================================*/

$stmt = $conn->prepare("
    SELECT
        cid,
        companyname,
        rname,
        rphone,
        remail,
        domain
    FROM clients
    WHERE cid = :cid
    LIMIT 1
");

$stmt->execute(array(
    ":cid" => $cid
));

$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    echo "<script>
        alert('Client not found.');
        window.location.href='admin.php?action=callinglist';
    </script>";
    exit;
}

/*==========================================================
=            Summary Counts
==========================================================*/

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_calls,
        SUM(CASE WHEN connected=1 THEN 1 ELSE 0 END) AS connected_calls,
        SUM(CASE WHEN connected=0 THEN 1 ELSE 0 END) AS not_connected_calls
    FROM client_call_history
    WHERE cid=:cid
");

$stmt->execute(array(
    ":cid"=>$cid
));

$summary = $stmt->fetch(PDO::FETCH_ASSOC);

/*==========================================================
=            Latest Call
==========================================================*/

$stmt = $conn->prepare("
    SELECT

        h.call_datetime,

        h.connected,

        rt.response_name,

        u.name recruiter

    FROM client_call_history h

    LEFT JOIN client_response_type rt
        ON rt.id=h.response_type_id

    LEFT JOIN users u
        ON u.uid=h.uid

    WHERE h.cid=:cid

    ORDER BY h.call_datetime DESC

    LIMIT 1
");

$stmt->execute(array(
    ":cid"=>$cid
));

$latest = $stmt->fetch(PDO::FETCH_ASSOC);

/*==========================================================
=            Complete Call History
==========================================================*/

$stmt = $conn->prepare("
    SELECT

        h.history_id,

        h.call_datetime,

        h.connected,

        h.comment,

        rt.response_name,

        u.name recruiter

    FROM client_call_history h

    LEFT JOIN client_response_type rt
        ON rt.id=h.response_type_id

    LEFT JOIN users u
        ON u.uid=h.uid

    WHERE h.cid=:cid

    ORDER BY h.call_datetime DESC
");

$stmt->execute(array(
    ":cid"=>$cid
));

require("includes/header.php");
require("includes/menu.php");

echo '<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">';
?>

<div class="panel panel-default">

    <div class="panel-heading">
        <h3 class="panel-title">Client Call History</h3>
    </div>

    <div class="panel-body">

        <div class="row">

            <div class="col-md-6">

                <table class="table table-bordered">

                    <tr>
                        <th width="35%">Company</th>
                        <td><?php echo htmlspecialchars($client['companyname']); ?></td>
                    </tr>

                    <tr>
                        <th>Contact</th>
                        <td><?php echo htmlspecialchars($client['rname']); ?></td>
                    </tr>

                    <tr>
                        <th>Phone</th>
                        <td><?php echo htmlspecialchars($client['rphone']); ?></td>
                    </tr>

                </table>

            </div>

            <div class="col-md-6">

                <table class="table table-bordered">

                    <tr>
                        <th width="35%">Email</th>
                        <td><?php echo htmlspecialchars($client['remail']); ?></td>
                    </tr>

                    <tr>
                        <th>Website</th>
                        <td><?php echo htmlspecialchars($client['domain']); ?></td>
                    </tr>

                </table>

            </div>

        </div>
        <hr>

        <div class="row">

            <div class="col-md-2">

                <div class="panel panel-primary">
                    <div class="panel-heading text-center">
                        Total Calls
                    </div>
                    <div class="panel-body text-center">
                        <h3><?php echo (int)$summary['total_calls']; ?></h3>
                    </div>
                </div>

            </div>

            <div class="col-md-2">

                <div class="panel panel-success">
                    <div class="panel-heading text-center">
                        Connected
                    </div>
                    <div class="panel-body text-center">
                        <h3><?php echo (int)$summary['connected_calls']; ?></h3>
                    </div>
                </div>

            </div>

            <div class="col-md-2">

                <div class="panel panel-danger">
                    <div class="panel-heading text-center">
                        Not Connected
                    </div>
                    <div class="panel-body text-center">
                        <h3><?php echo (int)$summary['not_connected_calls']; ?></h3>
                    </div>
                </div>

            </div>

            <div class="col-md-6">

                <table class="table table-bordered">

                    <tr>

                        <th width="35%">Last Call</th>

                        <td>

                            <?php
                            if (!empty($latest['call_datetime'])) {
                                echo date("d-M-Y h:i A", strtotime($latest['call_datetime']));
                            } else {
                                echo "-";
                            }
                            ?>

                        </td>

                    </tr>

                    <tr>

                        <th>Last Response</th>

                        <td>

                            <?php
                            echo !empty($latest['response_name'])
                                ? htmlspecialchars($latest['response_name'])
                                : "-";
                            ?>

                        </td>

                    </tr>

                    <tr>

                        <th>Recruiter</th>

                        <td>

                            <?php
                            echo !empty($latest['recruiter'])
                                ? htmlspecialchars($latest['recruiter'])
                                : "-";
                            ?>

                        </td>

                    </tr>

                </table>

            </div>

        </div>

        <hr>

        <h3>Call History</h3>

        <table
            id="historyTable"
            data-toggle="table"
            data-search="true"
            data-pagination="true"
            data-page-size="25"
            data-show-columns="true"
            data-show-toggle="true"
            data-show-refresh="true"
            class="table table-bordered table-hover">

            <thead>

                <tr>

                    <th data-sortable="true">S.No</th>

                    <th data-sortable="true">Call Date & Time</th>

                    <th data-sortable="true">Recruiter</th>

                    <th data-sortable="true">Status</th>

                    <th data-sortable="true">Response Type</th>

                    <th>Comments</th>

                </tr>

            </thead>

            <tbody>

            <?php

            $i = 1;

            while($row = $stmt->fetch(PDO::FETCH_ASSOC))
            {
            ?>

                <tr>

                    <td>
                        <?php echo $i++; ?>
                    </td>

                    <td>

                        <?php
                        echo date(
                            "d-M-Y h:i A",
                            strtotime($row['call_datetime'])
                        );
                        ?>

                    </td>

                    <td>

                        <?php
                        echo htmlspecialchars($row['recruiter']);
                        ?>

                    </td>

                    <td>

                        <?php if($row['connected']==1){ ?>

                            <span class="label label-success">
                                Connected
                            </span>

                        <?php } else { ?>

                            <span class="label label-danger">
                                Not Connected
                            </span>

                        <?php } ?>

                    </td>

                    <td>

                        <?php
                        echo !empty($row['response_name'])
                            ? htmlspecialchars($row['response_name'])
                            : "-";
                        ?>

                    </td>

                    <td style="white-space:normal;">

                        <?php
                        echo nl2br(htmlspecialchars($row['comment']));
                        ?>

                    </td>

                </tr>

            <?php
            }
            ?>

            </tbody>

        </table>

            </div>

</div>

</div>

<?php

require("includes/footer.php");

$conn = null;

?>