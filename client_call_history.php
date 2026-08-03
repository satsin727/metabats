<?php
require_once("config.php");

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

if (!defined('CLIENT_NOTE_PREFIX')) {
    define('CLIENT_NOTE_PREFIX', '[NOTE]');
}

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

/*
|--------------------------------------------------------------------------
| Validate Client ID
|--------------------------------------------------------------------------
*/

$cid = isset($_GET['cid'])
    ? (int)$_GET['cid']
    : 0;

if ($cid <= 0) {
    echo "<script>
        alert('Invalid Client.');
        window.location.href='admin.php?action=callinglist';
    </script>";

    exit;
}

/*
|--------------------------------------------------------------------------
| Client Information
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Completed Call Summary
|--------------------------------------------------------------------------
|
| Comment-only entries start with [NOTE].
| These entries are excluded from the call totals.
|
*/

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_calls,

        SUM(
            CASE
                WHEN connected = 1 THEN 1
                ELSE 0
            END
        ) AS connected_calls,

        SUM(
            CASE
                WHEN connected = 0 THEN 1
                ELSE 0
            END
        ) AS not_connected_calls

    FROM client_call_history

    WHERE cid = :cid
    AND COALESCE(comment, '') NOT LIKE '[NOTE]%'
");

$stmt->execute(array(
    ":cid" => $cid
));

$summary = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Comment Count
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_comments
    FROM client_call_history
    WHERE cid = :cid
    AND COALESCE(comment, '') LIKE '[NOTE]%'
");

$stmt->execute(array(
    ":cid" => $cid
));

$commentSummary = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Pending Schedule Summary
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS pending_schedules,

        SUM(
            CASE
                WHEN call_date < CURDATE() THEN 1
                ELSE 0
            END
        ) AS missed_schedules,

        SUM(
            CASE
                WHEN call_date >= CURDATE() THEN 1
                ELSE 0
            END
        ) AS active_schedules

    FROM client_call_schedule

    WHERE cid = :cid
    AND COALESCE(called, 0) = 0
");

$stmt->execute(array(
    ":cid" => $cid
));

$scheduleSummary = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Latest Real Call
|--------------------------------------------------------------------------
|
| Comment-only history entries are excluded.
|
*/

$stmt = $conn->prepare("
    SELECT
        h.call_datetime,
        h.connected,
        h.comment,
        rt.response_name,
        u.name AS recruiter

    FROM client_call_history h

    LEFT JOIN client_response_type rt
        ON rt.id = h.response_type_id

    LEFT JOIN users u
        ON u.uid = h.uid

    WHERE h.cid = :cid
    AND COALESCE(h.comment, '') NOT LIKE '[NOTE]%'

    ORDER BY
        h.call_datetime DESC,
        h.history_id DESC

    LIMIT 1
");

$stmt->execute(array(
    ":cid" => $cid
));

$latestCall = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Next Active Scheduled Call
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        s.cl_id,
        s.call_date,
        s.created_datetime,
        s.latest_comment,
        u.name AS recruiter

    FROM client_call_schedule s

    LEFT JOIN users u
        ON u.uid = s.uid

    WHERE s.cid = :cid
    AND COALESCE(s.called, 0) = 0
    AND s.call_date >= CURDATE()

    ORDER BY
        s.call_date ASC,
        s.cl_id ASC

    LIMIT 1
");

$stmt->execute(array(
    ":cid" => $cid
));

$nextSchedule = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Latest Missed Scheduled Call
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        s.cl_id,
        s.call_date,
        s.created_datetime,
        s.latest_comment,
        u.name AS recruiter

    FROM client_call_schedule s

    LEFT JOIN users u
        ON u.uid = s.uid

    WHERE s.cid = :cid
    AND COALESCE(s.called, 0) = 0
    AND s.call_date < CURDATE()

    ORDER BY
        s.call_date DESC,
        s.cl_id DESC

    LIMIT 1
");

$stmt->execute(array(
    ":cid" => $cid
));

$latestMissedSchedule =
    $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Complete Call, Comment, and Schedule Timeline
|--------------------------------------------------------------------------
|
| This includes:
|
| 1. Completed calls
| 2. Client callbacks
| 3. Comment-only history entries
| 4. Pending and missed schedules
|
*/

$stmt = $conn->prepare("
    SELECT
        h.history_id AS event_id,
        'history' AS event_type,

        h.call_datetime AS event_datetime,
        DATE(h.call_datetime) AS event_date,

        h.connected,
        h.response_type_id,
        rt.response_name,

        h.comment,
        h.created_datetime,

        u.name AS recruiter,

        NULL AS scheduled_call_date

    FROM client_call_history h

    LEFT JOIN client_response_type rt
        ON rt.id = h.response_type_id

    LEFT JOIN users u
        ON u.uid = h.uid

    WHERE h.cid = :cid_history

    UNION ALL

    SELECT
        s.cl_id AS event_id,
        'scheduled_call' AS event_type,

        CONCAT(
            s.call_date,
            ' 00:00:00'
        ) AS event_datetime,

        s.call_date AS event_date,

        NULL AS connected,
        s.response_type_id,
        NULL AS response_name,

        s.latest_comment AS comment,
        s.created_datetime,

        u.name AS recruiter,

        s.call_date AS scheduled_call_date

    FROM client_call_schedule s

    LEFT JOIN users u
        ON u.uid = s.uid

    WHERE s.cid = :cid_schedule
    AND COALESCE(s.called, 0) = 0

    ORDER BY
        event_datetime DESC,
        created_datetime DESC
");

$stmt->execute(array(
    ":cid_history" => $cid,
    ":cid_schedule" => $cid
));

$timeline = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Page Layout
|--------------------------------------------------------------------------
*/

require("includes/header.php");
require("includes/menu.php");

echo '<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">';
?>

<div class="panel panel-default">

    <div class="panel-heading">

        <h3 class="panel-title">
            Client Call History
        </h3>

    </div>

    <div class="panel-body">

        <!-- Client Information -->

        <div class="row">

            <div class="col-md-6">

                <table class="table table-bordered">

                    <tr>

                        <th width="35%">
                            Company
                        </th>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $client['companyname'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                    </tr>

                    <tr>

                        <th>
                            Contact
                        </th>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $client['rname'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                    </tr>

                    <tr>

                        <th>
                            Phone
                        </th>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $client['rphone'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                    </tr>

                </table>

            </div>

            <div class="col-md-6">

                <table class="table table-bordered">

                    <tr>

                        <th width="35%">
                            Email
                        </th>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $client['remail'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                    </tr>

                    <tr>

                        <th>
                            Website
                        </th>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $client['domain'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                    </tr>

                </table>

            </div>

        </div>

        <hr>

        <!-- Summary Cards -->

        <div class="row">

            <div class="col-md-2">

                <div class="panel panel-primary">

                    <div class="panel-heading text-center">
                        Total Calls
                    </div>

                    <div class="panel-body text-center">

                        <h3>
                            <?php
                            echo (int)$summary['total_calls'];
                            ?>
                        </h3>

                    </div>

                </div>

            </div>

            <div class="col-md-2">

                <div class="panel panel-success">

                    <div class="panel-heading text-center">
                        Connected
                    </div>

                    <div class="panel-body text-center">

                        <h3>
                            <?php
                            echo (int)$summary['connected_calls'];
                            ?>
                        </h3>

                    </div>

                </div>

            </div>

            <div class="col-md-2">

                <div class="panel panel-danger">

                    <div class="panel-heading text-center">
                        Not Connected
                    </div>

                    <div class="panel-body text-center">

                        <h3>
                            <?php
                            echo (int)$summary['not_connected_calls'];
                            ?>
                        </h3>

                    </div>

                </div>

            </div>

            <div class="col-md-2">

                <div class="panel panel-info">

                    <div class="panel-heading text-center">
                        Comments
                    </div>

                    <div class="panel-body text-center">

                        <h3>
                            <?php
                            echo (int)$commentSummary['total_comments'];
                            ?>
                        </h3>

                    </div>

                </div>

            </div>

            <div class="col-md-2">

                <div class="panel panel-warning">

                    <div class="panel-heading text-center">
                        Active Schedules
                    </div>

                    <div class="panel-body text-center">

                        <h3>
                            <?php
                            echo (int)$scheduleSummary[
                                'active_schedules'
                            ];
                            ?>
                        </h3>

                    </div>

                </div>

            </div>

            <div class="col-md-2">

                <div class="panel panel-danger">

                    <div class="panel-heading text-center">
                        Missed Schedules
                    </div>

                    <div class="panel-body text-center">

                        <h3>
                            <?php
                            echo (int)$scheduleSummary[
                                'missed_schedules'
                            ];
                            ?>
                        </h3>

                    </div>

                </div>

            </div>

        </div>

        <!-- Latest Call and Next Schedule -->

        <div class="row">

            <!-- Latest Completed Call -->

            <div class="col-md-6">

                <div class="panel panel-info">

                    <div class="panel-heading">

                        <strong>
                            Latest Completed Call
                        </strong>

                    </div>

                    <div class="panel-body">

                        <table class="table table-bordered">

                            <tr>

                                <th width="35%">
                                    Call Date
                                </th>

                                <td>

                                    <?php
                                    if (
                                        !empty(
                                            $latestCall[
                                                'call_datetime'
                                            ]
                                        )
                                    ) {
                                        echo date(
                                            'd-M-Y h:i A',
                                            strtotime(
                                                $latestCall[
                                                    'call_datetime'
                                                ]
                                            )
                                        );
                                    } else {
                                        echo '-';
                                    }
                                    ?>

                                </td>

                            </tr>

                            <tr>

                                <th>
                                    Called By
                                </th>

                                <td>

                                    <?php
                                    echo !empty(
                                        $latestCall['recruiter']
                                    )
                                        ? htmlspecialchars(
                                            $latestCall[
                                                'recruiter'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                        : '-';
                                    ?>

                                </td>

                            </tr>

                            <tr>

                                <th>
                                    Status
                                </th>

                                <td>

                                    <?php
                                    if (
                                        empty(
                                            $latestCall[
                                                'call_datetime'
                                            ]
                                        )
                                    ) {
                                    ?>

                                        <span class="label label-default">
                                            No Completed Calls
                                        </span>

                                    <?php
                                    } elseif (
                                        (int)$latestCall[
                                            'connected'
                                        ] === 1
                                    ) {
                                    ?>

                                        <span class="label label-success">
                                            Connected
                                        </span>

                                    <?php
                                    } else {
                                    ?>

                                        <span class="label label-danger">
                                            Not Connected
                                        </span>

                                    <?php } ?>

                                </td>

                            </tr>

                            <tr>

                                <th>
                                    Response
                                </th>

                                <td>

                                    <?php
                                    echo !empty(
                                        $latestCall[
                                            'response_name'
                                        ]
                                    )
                                        ? htmlspecialchars(
                                            $latestCall[
                                                'response_name'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                        : '-';
                                    ?>

                                </td>

                            </tr>

                        </table>

                    </div>

                </div>

            </div>

            <!-- Next Active Schedule -->

            <div class="col-md-6">

                <div class="panel panel-warning">

                    <div class="panel-heading">

                        <strong>
                            Next Scheduled Call
                        </strong>

                    </div>

                    <div class="panel-body">

                        <table class="table table-bordered">

                            <tr>

                                <th width="35%">
                                    Scheduled Date
                                </th>

                                <td>

                                    <?php
                                    if ($nextSchedule) {
                                        echo date(
                                            'd-M-Y',
                                            strtotime(
                                                $nextSchedule[
                                                    'call_date'
                                                ]
                                            )
                                        );
                                    } else {
                                        echo '-';
                                    }
                                    ?>

                                </td>

                            </tr>

                            <tr>

                                <th>
                                    Scheduled By
                                </th>

                                <td>

                                    <?php
                                    echo (
                                        $nextSchedule &&
                                        !empty(
                                            $nextSchedule[
                                                'recruiter'
                                            ]
                                        )
                                    )
                                        ? htmlspecialchars(
                                            $nextSchedule[
                                                'recruiter'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                        : '-';
                                    ?>

                                </td>

                            </tr>

                            <tr>

                                <th>
                                    Status
                                </th>

                                <td>

                                    <?php if ($nextSchedule) { ?>

                                        <span class="label label-warning">
                                            Scheduled - Not Called
                                        </span>

                                    <?php } else { ?>

                                        <span class="label label-default">
                                            No Active Schedule
                                        </span>

                                    <?php } ?>

                                </td>

                            </tr>

                            <tr>

                                <th>
                                    Scheduled On
                                </th>

                                <td>

                                    <?php
                                    if (
                                        $nextSchedule &&
                                        !empty(
                                            $nextSchedule[
                                                'created_datetime'
                                            ]
                                        )
                                    ) {
                                        echo date(
                                            'd-M-Y h:i A',
                                            strtotime(
                                                $nextSchedule[
                                                    'created_datetime'
                                                ]
                                            )
                                        );
                                    } else {
                                        echo '-';
                                    }
                                    ?>

                                </td>

                            </tr>

                        </table>

                    </div>

                </div>

            </div>

        </div>

        <!-- Latest Missed Schedule -->

        <?php if ($latestMissedSchedule) { ?>

            <div class="alert alert-danger">

                <strong>
                    Latest Missed Scheduled Call:
                </strong>

                <?php
                echo date(
                    'd-M-Y',
                    strtotime(
                        $latestMissedSchedule['call_date']
                    )
                );
                ?>

                — Scheduled by

                <strong>
                    <?php
                    echo !empty(
                        $latestMissedSchedule['recruiter']
                    )
                        ? htmlspecialchars(
                            $latestMissedSchedule[
                                'recruiter'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        : 'Unknown User';
                    ?>
                </strong>

                but the call was not completed.

            </div>

        <?php } ?>

        <hr>

        <!-- Complete Timeline -->

        <h3>
            Complete Call, Comment and Schedule History
        </h3>

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

                    <th data-sortable="true">
                        S.No
                    </th>

                    <th data-sortable="true">
                        Date and Time
                    </th>

                    <th data-sortable="true">
                        User
                    </th>

                    <th data-sortable="true">
                        Event
                    </th>

                    <th data-sortable="true">
                        Status
                    </th>

                    <th data-sortable="true">
                        Response Type
                    </th>

                    <th>
                        Comments / Details
                    </th>

                </tr>

            </thead>

            <tbody>

            <?php
            $i = 1;
            $today = date('Y-m-d');

            foreach ($timeline as $row) {

                $isScheduled =
                    $row['event_type'] ===
                    'scheduled_call';

                $rawComment = isset($row['comment'])
                    ? trim((string)$row['comment'])
                    : '';

                /*
                 * Identify a comment-only entry.
                 */

                $isNote =
                    !$isScheduled &&
                    strpos(
                        $rawComment,
                        CLIENT_NOTE_PREFIX
                    ) === 0;

                /*
                 * Identify a callback entry.
                 */

                $isCallback =
                    !$isScheduled &&
                    !$isNote &&
                    stripos(
                        $rawComment,
                        'Client called back.'
                    ) === 0;

                /*
                 * Remove the internal [NOTE] prefix before display.
                 */

                $displayComment = $rawComment;

                if ($isNote) {
                    $displayComment = preg_replace(
                        '/^\[NOTE\]\s*/',
                        '',
                        $rawComment
                    );
                }

                $scheduledDateDb =
                    $isScheduled
                        ? $row[
                            'scheduled_call_date'
                        ]
                        : '';

                $isExpiredSchedule =
                    $isScheduled &&
                    !empty($scheduledDateDb) &&
                    $scheduledDateDb < $today;

                $isActiveSchedule =
                    $isScheduled &&
                    !empty($scheduledDateDb) &&
                    $scheduledDateDb >= $today;

                $rowClass = '';

                if ($isExpiredSchedule) {
                    $rowClass = 'danger';
                } elseif ($isActiveSchedule) {
                    $rowClass = 'warning';
                } elseif ($isNote) {
                    $rowClass = 'info';
                } elseif ($isCallback) {
                    $rowClass = 'success';
                }
            ?>

                <tr<?php
                echo $rowClass !== ''
                    ? ' class="' .
                        $rowClass .
                        '"'
                    : '';
                ?>>

                    <!-- Serial Number -->

                    <td>
                        <?php echo $i++; ?>
                    </td>

                    <!-- Event Date -->

                    <td>

                        <?php if ($isScheduled) { ?>

                            <strong>
                                <?php
                                echo date(
                                    'd-M-Y',
                                    strtotime(
                                        $scheduledDateDb
                                    )
                                );
                                ?>
                            </strong>

                            <br>

                            <small class="text-muted">
                                Scheduled call date
                            </small>

                        <?php } else { ?>

                            <?php
                            echo date(
                                'd-M-Y h:i A',
                                strtotime(
                                    $row[
                                        'event_datetime'
                                    ]
                                )
                            );
                            ?>

                        <?php } ?>

                    </td>

                    <!-- User -->

                    <td>

                        <?php
                        echo !empty(
                            $row['recruiter']
                        )
                            ? htmlspecialchars(
                                $row['recruiter'],
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : '-';
                        ?>

                    </td>

                    <!-- Event -->

                    <td>

                        <?php if ($isScheduled) { ?>

                            <span class="label label-warning">
                                Call Scheduled
                            </span>

                        <?php } elseif ($isNote) { ?>

                            <span class="label label-info">
                                Comment Added
                            </span>

                        <?php } elseif ($isCallback) { ?>

                            <span class="label label-success">
                                Client Callback
                            </span>

                        <?php } else { ?>

                            <span class="label label-primary">
                                Call Completed
                            </span>

                        <?php } ?>

                    </td>

                    <!-- Status -->

                    <td>

                        <?php if ($isExpiredSchedule) { ?>

                            <span class="label label-danger">
                                Scheduled - Not Called
                            </span>

                            <br>

                            <small class="text-danger">
                                Scheduled date passed
                            </small>

                        <?php } elseif ($isActiveSchedule) { ?>

                            <span class="label label-warning">
                                Scheduled - Not Called
                            </span>

                        <?php } elseif ($isNote) { ?>

                            <span class="label label-info">
                                Comment Added
                            </span>

                            <br>

                            <small class="text-muted">
                                Call status unchanged
                            </small>

                        <?php } elseif (
                            (int)$row['connected'] === 1
                        ) { ?>

                            <span class="label label-success">
                                Connected
                            </span>

                        <?php } else { ?>

                            <span class="label label-danger">
                                Not Connected
                            </span>

                        <?php } ?>

                    </td>

                    <!-- Response Type -->

                    <td>

                        <?php
                        if (
                            $isScheduled ||
                            $isNote
                        ) {
                            echo '-';
                        } else {
                            echo !empty(
                                $row[
                                    'response_name'
                                ]
                            )
                                ? htmlspecialchars(
                                    $row[
                                        'response_name'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                                : '-';
                        }
                        ?>

                    </td>

                    <!-- Comment / Details -->

                    <td style="white-space: normal;">

                        <?php if ($isScheduled) { ?>

                            Call scheduled by

                            <strong>
                                <?php
                                echo !empty(
                                    $row['recruiter']
                                )
                                    ? htmlspecialchars(
                                        $row[
                                            'recruiter'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                    : 'Unknown User';
                                ?>
                            </strong>

                            <?php
                            if (
                                !empty(
                                    $row[
                                        'created_datetime'
                                    ]
                                )
                            ) {
                            ?>

                                on

                                <?php
                                echo date(
                                    'd-M-Y h:i A',
                                    strtotime(
                                        $row[
                                            'created_datetime'
                                        ]
                                    )
                                );
                                ?>.

                            <?php } ?>

                            <?php if ($isExpiredSchedule) { ?>

                                <br>

                                <span class="text-danger">
                                    The scheduled date passed, but no call response was saved.
                                </span>

                            <?php } ?>

                            <?php if ($displayComment !== '') { ?>

                                <br><br>

                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $displayComment,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                );
                                ?>

                            <?php } ?>

                        <?php } elseif ($isNote) { ?>

                            <strong>
                                Additional Comment:
                            </strong>

                            <br>

                            <?php
                            echo $displayComment !== ''
                                ? nl2br(
                                    htmlspecialchars(
                                        $displayComment,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                )
                                : '-';
                            ?>

                        <?php } else { ?>

                            <?php
                            echo $displayComment !== ''
                                ? nl2br(
                                    htmlspecialchars(
                                        $displayComment,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                )
                                : '-';
                            ?>

                        <?php } ?>

                    </td>

                </tr>

            <?php } ?>

            <?php if (empty($timeline)) { ?>

                <tr>

                    <td
                        colspan="7"
                        class="text-center text-muted">

                        No calls, comments, or scheduled calls found.

                    </td>

                </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>

</div>

</div>

<?php
require("includes/footer.php");

$conn = null;
?>