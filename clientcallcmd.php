<?php
session_start();
require_once("config.php");

/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
*/

try {
    $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

/*
|--------------------------------------------------------------------------
| Login and Session Validation
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['id']) || (int)$_SESSION['id'] <= 0) {
    header("Location: admin.php");
    exit;
}

$uid = (int)$_SESSION['id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE uid = :uid LIMIT 1");
$stmt->execute(array(":uid" => $uid));
$dta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dta) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['username']) || $dta['sess'] != $_SESSION['username']) {
    session_destroy();
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Read Requested Action
|--------------------------------------------------------------------------
*/

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

/*
|--------------------------------------------------------------------------
| Route Actions
|--------------------------------------------------------------------------
*/

switch ($action) {

    /*
    |--------------------------------------------------------------------------
    | Assign Clients
    |--------------------------------------------------------------------------
    |
    | Assignment rules:
    |
    | 1. A client called within the last five days cannot be selected.
    | 2. A client scheduled for today or a future date cannot be selected.
    | 3. A passed schedule where called = 0 does not block reassignment.
    | 4. The old passed schedule remains in the database so that the
    |    history page can show "Scheduled - Not Called".
    |
    */

    case "assign":

        if ($_SERVER['REQUEST_METHOD'] !== "POST") {
            header("Location: admin.php?action=clientassignment");
            exit;
        }

        $callDate = isset($_POST['call_date']) ? trim($_POST['call_date']) : '';
        $clients = (isset($_POST['client']) && is_array($_POST['client'])) ? $_POST['client'] : array();

        /*
         * Validate selected call date.
         */
        if ($callDate === '') {
            header("Location: admin.php?action=clientassignment&msg=date");
            exit;
        }

        $dateObject = DateTime::createFromFormat('Y-m-d', $callDate);

        if (!$dateObject || $dateObject->format('Y-m-d') !== $callDate) {
            header("Location: admin.php?action=clientassignment&msg=date");
            exit;
        }

        /*
         * Do not permit a new assignment for a past date.
         */
        if ($callDate < date('Y-m-d')) {
            header("Location: admin.php?action=clientassignment&msg=date");
            exit;
        }

        if (count($clients) === 0) {
            header("Location: admin.php?action=clientassignment&msg=noclients");
            exit;
        }

        $assigned = 0;
        $duplicate = 0;
        $failed = 0;

        try {
            $conn->beginTransaction();

            /*
             * Check whether the latest completed call occurred
             * within the previous five days.
             */
            $recentCallCheck = $conn->prepare("
                SELECT history_id FROM client_call_history 
                WHERE cid = :cid AND call_datetime >= DATE_SUB(NOW(), INTERVAL 5 DAY) 
                ORDER BY call_datetime DESC, history_id DESC LIMIT 1
            ");

            /*
             * Check only active schedules.
             * A passed schedule does not block a new assignment.
             */
            $activeScheduleCheck = $conn->prepare("
                SELECT cl_id, call_date FROM client_call_schedule 
                WHERE cid = :cid AND COALESCE(called, 0) = 0 AND call_date >= CURDATE() 
                ORDER BY call_date ASC, cl_id ASC LIMIT 1
            ");

            /*
             * Insert a new schedule.
             * When a previous scheduled date passed without a call,
             * that old row remains unchanged. This allows the history
             * page to display the missed scheduled call.
             */
            $insertSchedule = $conn->prepare("
                INSERT INTO client_call_schedule (cid, uid, call_date, called, connected, latest_comment, created_datetime) 
                VALUES (:cid, :uid, :call_date, 0, 0, '', NOW())
            ");

            foreach ($clients as $clientId) {
                $clientId = (int)$clientId;

                if ($clientId <= 0) {
                    $failed++;
                    continue;
                }

                /*
                 * Confirm the client still exists and is active.
                 */
                $clientCheck = $conn->prepare("SELECT cid FROM clients WHERE cid = :cid AND status = 1 LIMIT 1");
                $clientCheck->execute(array(":cid" => $clientId));

                if (!$clientCheck->fetchColumn()) {
                    $failed++;
                    continue;
                }

                /*
                 * Block clients called within the last five days.
                 */
                $recentCallCheck->execute(array(":cid" => $clientId));
                if ($recentCallCheck->fetchColumn()) {
                    $duplicate++;
                    continue;
                }

                /*
                 * Block clients already scheduled for today or a future date.
                 */
                $activeScheduleCheck->execute(array(":cid" => $clientId));
                if ($activeScheduleCheck->fetch(PDO::FETCH_ASSOC)) {
                    $duplicate++;
                    continue;
                }

                /*
                 * Passed, unresolved schedules do not prevent
                 * this new schedule from being inserted.
                 */
                try {
                    $insertSchedule->execute(array(
                        ":cid" => $clientId,
                        ":uid" => $uid,
                        ":call_date" => $callDate
                    ));
                    $assigned++;
                } catch (PDOException $e) {
                    /*
                     * Handle database duplicate-key constraints.
                     */
                    if ($e->getCode() === "23000") {
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
            die("Database Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }

        header("Location: admin.php?action=clientassignment&assigned=" . $assigned . "&duplicate=" . $duplicate . "&failed=" . $failed);
        exit;

    /*
    |--------------------------------------------------------------------------
    | Save Call Response
    |--------------------------------------------------------------------------
    */

    case "savecall":
        saveCall($conn, $dta);
        exit;
    
    case "savecallback":
            saveCallback(
                $conn,
                $dta
            );
            exit;

    case "getcallrow":
        getCallRow(
            $conn,
            $dta
        );

        exit;

    /*
    |--------------------------------------------------------------------------
    | Reschedule Redirect
    |--------------------------------------------------------------------------
    */

    case "reschedule":
        header("Location: admin.php?action=todayscalls");
        exit;

    /*
    |--------------------------------------------------------------------------
    | Delete Assignment Redirect
    |--------------------------------------------------------------------------
    */

    case "deleteassignment":
        header("Location: admin.php?action=clientassignment");
        exit;

    /*
    |--------------------------------------------------------------------------
    | Default
    |--------------------------------------------------------------------------
    */

    default:
        header("Location: admin.php");
        exit;
}

/*
|--------------------------------------------------------------------------
| Save Call Function
|--------------------------------------------------------------------------
*/

function saveCall($conn, $userData)
{
    /*
     * Remove any accidental output before returning JSON.
     */
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(array("status" => "error", "message" => "Invalid request method."));
        exit;
    }

    $clid = isset($_POST['clid']) ? (int)$_POST['clid'] : 0;
    $responseType = isset($_POST['response_type']) ? (int)$_POST['response_type'] : 0;
    $connected = isset($_POST['connected']) ? (int)$_POST['connected'] : 0;
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
    $followupDate = isset($_POST['followup_date']) ? trim($_POST['followup_date']) : '';
    $uid = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

    /*
     * Normalize connected value.
     */
    $connected = $connected === 1 ? 1 : 0;

    if ($clid <= 0) {
        echo json_encode(array("status" => "error", "message" => "Invalid Call ID."));
        exit;
    }

    /*
     * Response type is required only when connected.
     */
    if ($connected === 1 && $responseType <= 0) {
        echo json_encode(array("status" => "error", "message" => "Please select a response type."));
        exit;
    }

    /*
     * Do not store response type zero for a not-connected call.
     */
    if ($connected === 0) {
        $responseType = null;
    }

    /*
     * Validate optional follow-up date.
     */
    if ($followupDate !== '') {
        $followupObject = DateTime::createFromFormat('Y-m-d', $followupDate);

        if (!$followupObject || $followupObject->format('Y-m-d') !== $followupDate) {
            echo json_encode(array("status" => "error", "message" => "Invalid follow-up date."));
            exit;
        }

        if ($followupDate < date('Y-m-d')) {
            echo json_encode(array("status" => "error", "message" => "Follow-up date cannot be in the past."));
            exit;
        }
    }

    try {
        $conn->beginTransaction();

        /*
         * Retrieve the scheduled call.
         */
        $scheduleStmt = $conn->prepare("SELECT cl_id, cid, uid, call_date, called FROM client_call_schedule WHERE cl_id = :clid LIMIT 1");
        $scheduleStmt->execute(array(":clid" => $clid));
        $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            $conn->rollBack();
            echo json_encode(array("status" => "error", "message" => "Call schedule not found."));
            exit;
        }

        /*
         * Level 1 administrators may update any assigned call.
         * Other users may update only their own assigned calls.
         */
        if ((int)$userData['level'] !== 1 && (int)$schedule['uid'] !== $uid) {
            $conn->rollBack();
            echo json_encode(array("status" => "error", "message" => "You are not authorised to update this call."));
            exit;
        }

        /*
         * Prevent the same scheduled call from being completed twice.
         */
        if ((int)$schedule['called'] === 1) {
            $conn->rollBack();
            echo json_encode(array("status" => "error", "message" => "This call response has already been saved."));
            exit;
        }

        $cid = (int)$schedule['cid'];

        /*
         * Update the scheduled-call row.
         */
        $updateSchedule = $conn->prepare("
            UPDATE client_call_schedule 
            SET called = 1, connected = :connected, response_type_id = :response_type_id, latest_comment = :comments 
            WHERE cl_id = :clid AND called = 0
        ");
        
        $updateSchedule->bindValue(":connected", $connected, PDO::PARAM_INT);
        
        if ($responseType === null) {
            $updateSchedule->bindValue(":response_type_id", null, PDO::PARAM_NULL);
        } else {
            $updateSchedule->bindValue(":response_type_id", $responseType, PDO::PARAM_INT);
        }
        
        $updateSchedule->bindValue(":comments", $comments, PDO::PARAM_STR);
        $updateSchedule->bindValue(":clid", $clid, PDO::PARAM_INT);
        $updateSchedule->execute();

        if ($updateSchedule->rowCount() === 0) {
            $conn->rollBack();
            echo json_encode(array("status" => "error", "message" => "The call was already updated by another user."));
            exit;
        }

        /*
         * Insert the completed call into call history.
         */
        $insertHistory = $conn->prepare("
            INSERT INTO client_call_history (cl_id, cid, uid, call_datetime, connected, response_type_id, comment, created_datetime) 
            VALUES (:clid, :cid, :uid, NOW(), :connected, :response_type_id, :comment, NOW())
        ");
        
        $insertHistory->bindValue(":clid", $clid, PDO::PARAM_INT);
        $insertHistory->bindValue(":cid", $cid, PDO::PARAM_INT);
        $insertHistory->bindValue(":uid", $uid, PDO::PARAM_INT);
        $insertHistory->bindValue(":connected", $connected, PDO::PARAM_INT);
        
        if ($responseType === null) {
            $insertHistory->bindValue(":response_type_id", null, PDO::PARAM_NULL);
        } else {
            $insertHistory->bindValue(":response_type_id", $responseType, PDO::PARAM_INT);
        }
        
        $insertHistory->bindValue(":comment", $comments, PDO::PARAM_STR);
        $insertHistory->execute();

        $followupScheduled = false;
        $followupSkipped = false;

        /*
         * Schedule an optional follow-up call.
         */
        if ($followupDate !== '') {

            /*
             * Do not create another active schedule when one
             * already exists for this client.
             */
            $activeFollowupCheck = $conn->prepare("
                SELECT cl_id FROM client_call_schedule 
                WHERE cid = :cid AND COALESCE(called, 0) = 0 AND call_date >= CURDATE() LIMIT 1
            ");
            $activeFollowupCheck->execute(array(":cid" => $cid));

            if ($activeFollowupCheck->fetchColumn()) {
                $followupSkipped = true;
            } else {
                $insertFollowup = $conn->prepare("
                    INSERT INTO client_call_schedule (cid, uid, call_date, called, connected, latest_comment, created_datetime) 
                    VALUES (:cid, :uid, :call_date, 0, 0, '', NOW())
                ");

                try {
                    $insertFollowup->execute(array(
                        ":cid" => $cid,
                        ":uid" => $uid,
                        ":call_date" => $followupDate
                    ));
                    $followupScheduled = true;
                } catch (PDOException $e) {
                    if ($e->getCode() === "23000") {
                        $followupSkipped = true;
                    } else {
                        throw $e;
                    }
                }
            }
        }

        $conn->commit();

        $message = "Call saved successfully.";
        if ($followupScheduled) {
            $message .= " Follow-up call scheduled.";
        }
        if ($followupSkipped) {
            $message .= " Follow-up was not added because an active schedule already exists.";
        }

        echo json_encode(array(
                "status" => "success",
                "message" => $message,
                "clid" => $clid,
                "connected" => $connected
            ));
        exit;

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode(array("status" => "error", "message" => "Database Error: " . $e->getMessage()));
        exit;
    }
}

function saveCallback($conn, $userData)
{
    /*
    |--------------------------------------------------------------------------
    | Return JSON Only
    |--------------------------------------------------------------------------
    */

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        echo json_encode(array(
            "status" => "error",
            "message" => "Invalid request method."
        ));

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Read Form Values
    |--------------------------------------------------------------------------
    */

    $clid = isset($_POST['clid'])
        ? (int)$_POST['clid']
        : 0;

    $responseType = isset($_POST['response_type'])
        ? (int)$_POST['response_type']
        : 0;

    $comments = isset($_POST['comments'])
        ? trim($_POST['comments'])
        : '';

    $followupDate = isset($_POST['followup_date'])
        ? trim($_POST['followup_date'])
        : '';

    $uid = isset($_SESSION['id'])
        ? (int)$_SESSION['id']
        : 0;

    if ($clid <= 0) {

        echo json_encode(array(
            "status" => "error",
            "message" => "Invalid Call ID."
        ));

        exit;
    }

    if ($responseType <= 0) {

        echo json_encode(array(
            "status" => "error",
            "message" => "Please select a response type."
        ));

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Optional Follow-up Date
    |--------------------------------------------------------------------------
    */

    if ($followupDate !== '') {

        $followupObject = DateTime::createFromFormat(
            'Y-m-d',
            $followupDate
        );

        if (
            !$followupObject ||
            $followupObject->format('Y-m-d') !==
                $followupDate
        ) {

            echo json_encode(array(
                "status" => "error",
                "message" => "Invalid follow-up date."
            ));

            exit;
        }

        if ($followupDate < date('Y-m-d')) {

            echo json_encode(array(
                "status" => "error",
                "message" =>
                    "Follow-up date cannot be in the past."
            ));

            exit;
        }
    }

    try {

        $conn->beginTransaction();

        /*
        |--------------------------------------------------------------------------
        | Retrieve Original Scheduled Call
        |--------------------------------------------------------------------------
        */

        $scheduleStmt = $conn->prepare("
            SELECT
                cl_id,
                cid,
                uid,
                call_date,
                called,
                connected
            FROM client_call_schedule
            WHERE cl_id = :clid
            LIMIT 1
        ");

        $scheduleStmt->execute(array(
            ":clid" => $clid
        ));

        $schedule = $scheduleStmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$schedule) {

            $conn->rollBack();

            echo json_encode(array(
                "status" => "error",
                "message" => "Call schedule not found."
            ));

            exit;
        }

        /*
         * Callback is available only after the original
         * call was marked Not Connected.
         */

        if (
            (int)$schedule['called'] !== 1 ||
            (int)$schedule['connected'] !== 0
        ) {

            $conn->rollBack();

            echo json_encode(array(
                "status" => "error",
                "message" =>
                    "A callback can only be added after a Not Connected call."
            ));

            exit;
        }

        /*
         * Admin can update any call.
         * Other users can update only their assigned calls.
         */

        if (
            (int)$userData['level'] !== 1 &&
            (int)$schedule['uid'] !== $uid
        ) {

            $conn->rollBack();

            echo json_encode(array(
                "status" => "error",
                "message" =>
                    "You are not authorised to update this call."
            ));

            exit;
        }

        $cid = (int)$schedule['cid'];

        /*
        |--------------------------------------------------------------------------
        | Create Callback Comment
        |--------------------------------------------------------------------------
        */

        $callbackComment =
            "Client called back.";

        if ($comments !== '') {
            $callbackComment .=
                " " . $comments;
        }

        /*
        |--------------------------------------------------------------------------
        | Insert New Callback History Entry
        |--------------------------------------------------------------------------
        |
        | The original Not Connected row remains unchanged.
        |
        */

        $insertHistory = $conn->prepare("
            INSERT INTO client_call_history (
                cl_id,
                cid,
                uid,
                call_datetime,
                connected,
                response_type_id,
                comment,
                created_datetime
            )
            VALUES (
                :clid,
                :cid,
                :uid,
                NOW(),
                1,
                :response_type_id,
                :comment,
                NOW()
            )
        ");

        $insertHistory->execute(array(
            ":clid" => $clid,
            ":cid" => $cid,
            ":uid" => $uid,
            ":response_type_id" => $responseType,
            ":comment" => $callbackComment
        ));

        /*
        |--------------------------------------------------------------------------
        | Update Today's Schedule to Latest Status
        |--------------------------------------------------------------------------
        |
        | The schedule will now display Connected, while the history
        | will still retain the earlier Not Connected entry.
        |
        */

        $updateSchedule = $conn->prepare("
            UPDATE client_call_schedule
            SET
                connected = 1,
                response_type_id = :response_type_id,
                latest_comment = :comment
            WHERE cl_id = :clid
        ");

        $updateSchedule->execute(array(
            ":response_type_id" => $responseType,
            ":comment" => $callbackComment,
            ":clid" => $clid
        ));

        $followupScheduled = false;
        $followupSkipped = false;

        /*
        |--------------------------------------------------------------------------
        | Optional Follow-up Schedule
        |--------------------------------------------------------------------------
        */

        if ($followupDate !== '') {

            /*
             * Check whether a pending schedule already exists.
             */

            $pendingCheck = $conn->prepare("
                SELECT cl_id
                FROM client_call_schedule
                WHERE cid = :cid
                AND COALESCE(called, 0) = 0
                AND call_date >= CURDATE()
                LIMIT 1
            ");

            $pendingCheck->execute(array(
                ":cid" => $cid
            ));

            if ($pendingCheck->fetchColumn()) {

                $followupSkipped = true;

            } else {

                $insertFollowup = $conn->prepare("
                    INSERT INTO client_call_schedule (
                        cid,
                        uid,
                        call_date,
                        called,
                        connected,
                        latest_comment,
                        created_datetime
                    )
                    VALUES (
                        :cid,
                        :uid,
                        :call_date,
                        0,
                        0,
                        '',
                        NOW()
                    )
                ");

                try {

                    $insertFollowup->execute(array(
                        ":cid" => $cid,
                        ":uid" => $uid,
                        ":call_date" => $followupDate
                    ));

                    $followupScheduled = true;

                } catch (PDOException $e) {

                    /*
                     * The database has a unique client/date key.
                     */

                    if ($e->getCode() === "23000") {
                        $followupSkipped = true;
                    } else {
                        throw $e;
                    }
                }
            }
        }

        $conn->commit();

        $message =
            "Client callback saved successfully.";

        if ($followupScheduled) {
            $message .=
                " Follow-up call scheduled.";
        }

        if ($followupSkipped) {
            $message .=
                " Follow-up was skipped because a schedule already exists for that client/date.";
        }

        echo json_encode(array(
            "status" => "success",
            "message" => $message,
            "clid" => $clid,
            "connected" => 1
        ));

        exit;

    } catch (PDOException $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        echo json_encode(array(
            "status" => "error",
            "message" =>
                "Database Error: " .
                $e->getMessage()
        ));

        exit;
    }
}

function getCallRow($conn, $userData)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/json');

    $clid = isset($_GET['clid'])
        ? (int)$_GET['clid']
        : 0;

    $uid = isset($_SESSION['id'])
        ? (int)$_SESSION['id']
        : 0;

    if ($clid <= 0) {

        echo json_encode(array(
            "status" => "error",
            "message" => "Invalid Call ID."
        ));

        exit;
    }

    try {

        $stmt = $conn->prepare("
            SELECT
                s.cl_id,
                s.cid,
                s.uid,
                s.called,
                s.connected,
                s.response_type_id,
                s.latest_comment,
                s.call_date
            FROM client_call_schedule s
            WHERE s.cl_id = :clid
            LIMIT 1
        ");

        $stmt->execute(array(
            ":clid" => $clid
        ));

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {

            echo json_encode(array(
                "status" => "error",
                "message" => "Call record not found."
            ));

            exit;
        }

        /*
         * Admin can view every row.
         * Other users can reload only their assigned rows.
         */

        if (
            (int)$userData['level'] !== 1 &&
            (int)$row['uid'] !== $uid
        ) {

            echo json_encode(array(
                "status" => "error",
                "message" =>
                    "You are not authorised to view this call."
            ));

            exit;
        }

        echo json_encode(array(
            "status" => "success",
            "cl_id" => (int)$row['cl_id'],
            "called" => (int)$row['called'],
            "connected" => (int)$row['connected'],
            "comment" => $row['latest_comment']
        ));

        exit;

    } catch (PDOException $e) {

        echo json_encode(array(
            "status" => "error",
            "message" =>
                "Database Error: " . $e->getMessage()
        ));

        exit;
    }
}

$conn = null;
?>