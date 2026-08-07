<?php
require_once("config.php");

header('Content-Type: application/json; charset=utf-8');

function jsonResponse($status, $message, $extra = array())
{
    echo json_encode(array_merge(array(
        'status' => $status,
        'message' => $message
    ), $extra));
    exit;
}

if (!isset($_SESSION['id']) || (int)$_SESSION['id'] <= 0) {
    jsonResponse('error', 'Your session has expired. Please login again.');
}

try {
    $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userStmt = $conn->prepare("SELECT uid, level FROM users WHERE uid = :uid LIMIT 1");
    $userStmt->execute(array(':uid' => (int)$_SESSION['id']));
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse('error', 'Invalid user session.');
    }

    $userId = (int)$user['uid'];
    $userLevel = (int)$user['level'];
    $action = isset($_GET['action']) ? trim($_GET['action']) : '';

    function userCanAccessClient($conn, $cid, $userId, $userLevel)
    {
        if ($userLevel === 1) {
            $stmt = $conn->prepare("SELECT cid FROM clients WHERE cid = :cid LIMIT 1");
            $stmt->execute(array(':cid' => $cid));
            return (bool)$stmt->fetchColumn();
        }

        if ($userLevel === 2 || $userLevel === 3) {
            /*
             * A level 2/3 user may access a client that is directly
             * assigned to them in clients.uid OR appears in one of their
             * call schedules. This supports both Client Call Assignment
             * and Today's Calls without broadening access to other users'
             * clients.
             */
            $stmt = $conn->prepare("
                SELECT c.cid
                FROM clients c
                WHERE c.cid = :cid
                  AND (
                        c.uid = :uid
                        OR EXISTS (
                            SELECT 1
                            FROM client_call_schedule s
                            WHERE s.cid = c.cid
                              AND s.uid = :uid2
                        )
                  )
                LIMIT 1
            ");
            $stmt->execute(array(
                ':cid' => $cid,
                ':uid' => $userId,
                ':uid2' => $userId
            ));
            return (bool)$stmt->fetchColumn();
        }

        return false;
    }

    if ($action === 'assign') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request.');
        }

        if (!in_array($userLevel, array(1, 2, 3), true)) {
            jsonResponse('error', 'You are not authorised to assign client calls.');
        }

        $callDate = isset($_POST['call_date']) ? trim($_POST['call_date']) : '';
        $clients = (isset($_POST['client']) && is_array($_POST['client']))
            ? $_POST['client']
            : array();

        $dateObject = DateTime::createFromFormat('Y-m-d', $callDate);

        if (
            $callDate === '' ||
            !$dateObject ||
            $dateObject->format('Y-m-d') !== $callDate ||
            $callDate < date('Y-m-d')
        ) {
            jsonResponse('error', 'Please select a valid call date.');
        }

        if (count($clients) === 0) {
            jsonResponse('error', 'Please select at least one client.');
        }

        $assigned = 0;
        $duplicate = 0;
        $failed = 0;
        $requestedClientIds = array();

        try {
            $conn->beginTransaction();

            $recentCallCheck = $conn->prepare("\n                SELECT history_id\n                FROM client_call_history\n                WHERE cid = :cid\n                AND call_datetime >= DATE_SUB(NOW(), INTERVAL 5 DAY)\n                ORDER BY call_datetime DESC, history_id DESC\n                LIMIT 1\n            ");

            $activeScheduleCheck = $conn->prepare("\n                SELECT cl_id, call_date\n                FROM client_call_schedule\n                WHERE cid = :cid\n                AND COALESCE(called, 0) = 0\n                AND call_date >= CURDATE()\n                ORDER BY call_date ASC, cl_id ASC\n                LIMIT 1\n            ");

            $clientCheck = $conn->prepare("\n                SELECT cid\n                FROM clients\n                WHERE cid = :cid\n                AND status = 1\n                LIMIT 1\n            ");

            $insertSchedule = $conn->prepare("\n                INSERT INTO client_call_schedule (\n                    cid, uid, call_date, called, connected,\n                    latest_comment, created_datetime\n                ) VALUES (\n                    :cid, :uid, :call_date, 0, 0, '', NOW()\n                )\n            ");

            foreach ($clients as $clientId) {
                $clientId = (int)$clientId;

                if ($clientId <= 0) {
                    $failed++;
                    continue;
                }

                $requestedClientIds[$clientId] = $clientId;

                $clientCheck->execute(array(':cid' => $clientId));
                if (!$clientCheck->fetchColumn()) {
                    $failed++;
                    continue;
                }

                $recentCallCheck->execute(array(':cid' => $clientId));
                if ($recentCallCheck->fetchColumn()) {
                    $duplicate++;
                    continue;
                }

                $activeScheduleCheck->execute(array(':cid' => $clientId));
                if ($activeScheduleCheck->fetch(PDO::FETCH_ASSOC)) {
                    $duplicate++;
                    continue;
                }

                try {
                    $insertSchedule->execute(array(
                        ':cid' => $clientId,
                        ':uid' => $userId,
                        ':call_date' => $callDate
                    ));
                    $assigned++;
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        $duplicate++;
                    } else {
                        throw $e;
                    }
                }
            }

            $conn->commit();
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            jsonResponse('error', 'Unable to save the assignment.');
        }

        $rowStmt = $conn->prepare("\n            SELECT\n                c.cid, c.lid, c.companyname, c.rname, c.remail,\n                c.rphone, c.domain, c.rlocation, c.rtimezon, c.tier,\n                h.call_datetime, h.connected, last_user.name AS called_by,\n                ps.cl_id AS pending_cl_id, ps.call_date AS scheduled_call_date,\n                scheduled_user.name AS scheduled_by,\n                CASE\n                    WHEN h.call_datetime IS NOT NULL\n                    AND h.call_datetime >= DATE_SUB(NOW(), INTERVAL 5 DAY)\n                    THEN 1 ELSE 0\n                END AS called_within_five_days\n            FROM clients c\n            LEFT JOIN client_call_history h\n                ON h.history_id = (\n                    SELECT h2.history_id\n                    FROM client_call_history h2\n                    WHERE h2.cid = c.cid\n                    AND COALESCE(h2.comment, '') NOT LIKE '[NOTE]%'\n                    ORDER BY h2.call_datetime DESC, h2.history_id DESC\n                    LIMIT 1\n                )\n            LEFT JOIN users last_user ON last_user.uid = h.uid\n            LEFT JOIN client_call_schedule ps\n                ON ps.cl_id = (\n                    SELECT s2.cl_id\n                    FROM client_call_schedule s2\n                    WHERE s2.cid = c.cid\n                    AND COALESCE(s2.called, 0) = 0\n                    ORDER BY\n                        CASE WHEN s2.call_date >= CURDATE() THEN 0 ELSE 1 END ASC,\n                        CASE WHEN s2.call_date >= CURDATE() THEN s2.call_date ELSE NULL END ASC,\n                        CASE WHEN s2.call_date < CURDATE() THEN s2.call_date ELSE NULL END DESC,\n                        s2.cl_id DESC\n                    LIMIT 1\n                )\n            LEFT JOIN users scheduled_user ON scheduled_user.uid = ps.uid\n            WHERE c.cid = :cid\n            AND c.status = 1\n            LIMIT 1\n        ");

        $rows = array();
        $today = date('Y-m-d');

        foreach ($requestedClientIds as $clientId) {
            $rowStmt->execute(array(':cid' => $clientId));
            $row = $rowStmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                continue;
            }

            $hasPendingSchedule = !empty($row['pending_cl_id']) && !empty($row['scheduled_call_date']);
            $scheduledDateDb = $hasPendingSchedule ? $row['scheduled_call_date'] : '';
            $scheduledDateText = $hasPendingSchedule ? date('d-M-Y', strtotime($scheduledDateDb)) : '';
            $hasActiveSchedule = $hasPendingSchedule && $scheduledDateDb >= $today;
            $hasExpiredSchedule = $hasPendingSchedule && $scheduledDateDb < $today;
            $calledWithinFiveDays = isset($row['called_within_five_days']) && (int)$row['called_within_five_days'] === 1;
            $disableSelection = $hasActiveSchedule || $calledWithinFiveDays;
            $rowClass = $hasActiveSchedule ? 'warning' : ($hasExpiredSchedule ? 'danger' : '');

            if ($disableSelection) {
                $selectionHtml = '<input type="checkbox" class="clientCheck" disabled>';
            } else {
                $selectionHtml = '<input type="checkbox" class="clientCheck" name="client[]" value="' .
                    (int)$row['cid'] . '" data-company="' .
                    htmlspecialchars($row['companyname'], ENT_QUOTES, 'UTF-8') . '">';
            }

            if ($hasActiveSchedule) {
                $lastCallHtml = '<span class="label label-warning">Call Scheduled</span><br><strong>' .
                    htmlspecialchars($scheduledDateText, ENT_QUOTES, 'UTF-8') . '</strong>';
            } elseif ($hasExpiredSchedule) {
                $lastCallHtml = '<span class="label label-danger">Scheduled but Not Called</span><br><strong>' .
                    htmlspecialchars($scheduledDateText, ENT_QUOTES, 'UTF-8') .
                    '</strong><br><small class="text-danger">Date passed</small>';
            } elseif (empty($row['call_datetime'])) {
                $lastCallHtml = '<span class="text-muted">Never</span>';
            } else {
                $lastCallHtml = htmlspecialchars(date('d-M-Y', strtotime($row['call_datetime'])), ENT_QUOTES, 'UTF-8');
                if ($calledWithinFiveDays) {
                    $lastCallHtml .= '<br><small class="text-danger">Called within last 5 days</small>';
                }
            }

            $lastBy = $hasPendingSchedule
                ? (!empty($row['scheduled_by']) ? $row['scheduled_by'] : '-')
                : (!empty($row['called_by']) ? $row['called_by'] : '-');

            if ($hasActiveSchedule) {
                $statusHtml = '<span class="label label-warning">Scheduled - Not Called</span>';
            } elseif ($hasExpiredSchedule) {
                $statusHtml = '<span class="label label-danger">Scheduled - Not Called</span>' .
                    '<br><small class="text-danger">Schedule date passed</small>';
                if (!$calledWithinFiveDays) {
                    $statusHtml .= '<br><small class="text-success">Available to reschedule</small>';
                }
            } elseif (empty($row['call_datetime'])) {
                $statusHtml = '<span class="label label-default">Never Called</span>';
            } elseif ((int)$row['connected'] === 1) {
                $statusHtml = '<span class="label label-success">Connected</span>';
                if ($calledWithinFiveDays) {
                    $statusHtml .= '<br><small class="text-danger">Selection locked for 5 days</small>';
                }
            } else {
                $statusHtml = '<span class="label label-danger">Not Connected</span>';
                if ($calledWithinFiveDays) {
                    $statusHtml .= '<br><small class="text-danger">Selection locked for 5 days</small>';
                }
            }

            $rows[] = array(
                'cid' => (int)$row['cid'],
                'lid' => (int)$row['lid'],
                'companyname' => $row['companyname'],
                'rname' => $row['rname'],
                'remail' => $row['remail'],
                'rphone' => $row['rphone'],
                'domain' => $row['domain'],
                'rlocation' => isset($row['rlocation']) ? $row['rlocation'] : '',
                'rtimezon' => isset($row['rtimezon']) ? $row['rtimezon'] : '',
                'tier' => isset($row['tier']) ? $row['tier'] : '',
                'last_by' => $lastBy,
                'row_class' => $rowClass,
                'selection_html' => $selectionHtml,
                'lastcall_html' => $lastCallHtml,
                'status_html' => $statusHtml
            );
        }

        jsonResponse('success', 'Assignment completed.', array(
            'assigned' => $assigned,
            'duplicate' => $duplicate,
            'failed' => $failed,
            'rows' => $rows
        ));
    }

    if ($action === 'getclient') {
        $cid = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;

        if ($cid <= 0) {
            jsonResponse('error', 'Invalid client.');
        }

        if (!userCanAccessClient($conn, $cid, $userId, $userLevel)) {
            jsonResponse('error', 'You are not authorized to access this client.');
        }

        $stmt = $conn->prepare("
            SELECT
                cid,
                companyname,
                rname,
                rfname,
                remail,
                rphone,
                rlocation,
                rtimezon,
                tier,
                domain,
                status
            FROM clients
            WHERE cid = :cid
            LIMIT 1
        ");
        $stmt->execute(array(':cid' => $cid));
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$client) {
            jsonResponse('error', 'Client was not found.');
        }

        jsonResponse('success', 'Client loaded.', array('client' => $client));
    }

    if ($action === 'saveclient') {
        $cid = isset($_POST['cid']) ? (int)$_POST['cid'] : 0;

        if ($cid <= 0) {
            jsonResponse('error', 'Invalid client.');
        }

        if (!userCanAccessClient($conn, $cid, $userId, $userLevel)) {
            jsonResponse('error', 'You are not authorized to edit this client.');
        }

        $companyname = isset($_POST['companyname']) ? trim($_POST['companyname']) : '';
        $rname = isset($_POST['rname']) ? trim($_POST['rname']) : '';
        $rfname = isset($_POST['rfname']) ? trim($_POST['rfname']) : '';
        $remail = isset($_POST['remail']) ? trim($_POST['remail']) : '';
        $rphone = isset($_POST['rphone']) ? trim($_POST['rphone']) : '';
        $rlocation = isset($_POST['rlocation']) ? trim($_POST['rlocation']) : '';
        $rtimezon = isset($_POST['rtimezon']) ? trim($_POST['rtimezon']) : '';
        $tier = isset($_POST['tier']) ? trim($_POST['tier']) : '';

        if ($companyname === '') {
            jsonResponse('error', 'Company Name is required.');
        }

        $allowedTiers = array('Tier 1', 'Tier 2', 'Implementation Partner');
        $allowedTimezones = array('EST', 'CST', 'MST', 'PST');

        if (!in_array($tier, $allowedTiers, true)) {
            jsonResponse('error', 'Invalid tier selected.');
        }

        if (!in_array($rtimezon, $allowedTimezones, true)) {
            jsonResponse('error', 'Invalid timezone selected.');
        }

        if ($remail !== '' && !filter_var($remail, FILTER_VALIDATE_EMAIL)) {
            jsonResponse('error', 'Please enter a valid email address.');
        }

        $stmt = $conn->prepare("
            UPDATE clients
            SET
                companyname = :companyname,
                rname = :rname,
                rfname = :rfname,
                remail = :remail,
                rphone = :rphone,
                rlocation = :rlocation,
                rtimezon = :rtimezon,
                tier = :tier
            WHERE cid = :cid
              AND status = 1
        ");

        $stmt->execute(array(
            ':companyname' => $companyname,
            ':rname' => $rname,
            ':rfname' => $rfname,
            ':remail' => $remail,
            ':rphone' => $rphone,
            ':rlocation' => $rlocation,
            ':rtimezon' => $rtimezon,
            ':tier' => $tier,
            ':cid' => $cid
        ));

        jsonResponse('success', 'Client updated successfully.', array('cid' => $cid));
    }

    /*
    |--------------------------------------------------------------------------
    | Remove One Today's Calls Assignment
    |--------------------------------------------------------------------------
    |
    | This removes only the selected unresolved schedule row.
    | It does NOT change clients.status and does NOT delete the client.
    |
    */
    if ($action === 'removeassignment') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request.');
        }

        $clid = isset($_POST['clid']) ? (int)$_POST['clid'] : 0;

        if ($clid <= 0) {
            jsonResponse('error', 'Invalid call assignment.');
        }

        $scheduleStmt = $conn->prepare("
            SELECT
                cl_id,
                cid,
                uid,
                call_date,
                called
            FROM client_call_schedule
            WHERE cl_id = :clid
            LIMIT 1
        ");
        $scheduleStmt->execute(array(':clid' => $clid));
        $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            jsonResponse('error', 'Call assignment was not found.');
        }

        /*
         * Level 1 can remove any unresolved assignment.
         * Levels 2 and 3 can remove only their own assignment.
         */
        if (
            $userLevel !== 1 &&
            (int)$schedule['uid'] !== $userId
        ) {
            jsonResponse(
                'error',
                'You are not authorized to remove this call assignment.'
            );
        }

        /*
         * Do not remove a completed call schedule because callback/history
         * functionality can still depend on its cl_id.
         */
        if ((int)$schedule['called'] !== 0) {
            jsonResponse(
                'error',
                'A completed call cannot be removed from Today\'s Calls.'
            );
        }

        $deleteStmt = $conn->prepare("
            DELETE FROM client_call_schedule
            WHERE cl_id = :clid
              AND called = 0
        ");
        $deleteStmt->execute(array(':clid' => $clid));

        if ($deleteStmt->rowCount() === 0) {
            jsonResponse(
                'error',
                'The call assignment could not be removed.'
            );
        }

        jsonResponse(
            'success',
            'Assignment removed from Today\'s Calls. The client remains active.',
            array(
                'clid' => $clid,
                'cid' => (int)$schedule['cid']
            )
        );
    }

    if ($action === 'deleteclient') {
        $cid = isset($_POST['cid']) ? (int)$_POST['cid'] : 0;

        if ($cid <= 0) {
            jsonResponse('error', 'Invalid client.');
        }

        if (!userCanAccessClient($conn, $cid, $userId, $userLevel)) {
            jsonResponse('error', 'You are not authorized to delete this client.');
        }

        $stmt = $conn->prepare("
            UPDATE clients
            SET status = 0
            WHERE cid = :cid
              AND status = 1
        ");
        $stmt->execute(array(':cid' => $cid));

        jsonResponse('success', 'Client removed from active clients.', array('cid' => $cid));
    }

    jsonResponse('error', 'Invalid request.');

} catch (PDOException $e) {
    jsonResponse('error', 'Database request failed.');
}
