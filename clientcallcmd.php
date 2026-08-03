<?php
require_once("config.php");

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

if (!defined('CLIENT_NOTE_PREFIX')) {
    define('CLIENT_NOTE_PREFIX', '[NOTE] ');
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

$uid = (int)$_SESSION['id'];

$stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE uid = :uid
    LIMIT 1
");

$stmt->execute(array(
    ":uid" => $uid
));

$dta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dta) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (
    !isset($_SESSION['username']) ||
    $dta['sess'] != $_SESSION['username']
) {
    session_destroy();
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Read Requested Action
|--------------------------------------------------------------------------
*/

$action = isset($_GET['action'])
    ? trim($_GET['action'])
    : '';

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
    | Rules:
    | 1. A real call within the last five days blocks selection.
    | 2. Comment-only history entries do not trigger the five-day lock.
    | 3. An active schedule for today or later blocks another assignment.
    | 4. An expired schedule where called = 0 does not block reassignment.
    |
    */

    case "assign":

        if ($_SERVER['REQUEST_METHOD'] !== "POST") {
            header(
                "Location: admin.php?action=clientassignment"
            );
            exit;
        }

        $callDate = isset($_POST['call_date'])
            ? trim($_POST['call_date'])
            : '';

        $clients = (
            isset($_POST['client']) &&
            is_array($_POST['client'])
        )
            ? $_POST['client']
            : array();

        if ($callDate === '') {
            header(
                "Location: admin.php?action=clientassignment&msg=date"
            );
            exit;
        }

        $dateObject = DateTime::createFromFormat(
            'Y-m-d',
            $callDate
        );

        if (
            !$dateObject ||
            $dateObject->format('Y-m-d') !== $callDate ||
            $callDate < date('Y-m-d')
        ) {
            header(
                "Location: admin.php?action=clientassignment&msg=date"
            );
            exit;
        }

        if (count($clients) === 0) {
            header(
                "Location: admin.php?action=clientassignment&msg=noclients"
            );
            exit;
        }

        $assigned = 0;
        $duplicate = 0;
        $failed = 0;

        try {
            $conn->beginTransaction();

            $clientCheck = $conn->prepare("
                SELECT cid
                FROM clients
                WHERE cid = :cid
                AND status = 1
                LIMIT 1
            ");

            /*
             * Comment-only history entries begin with [NOTE].
             * They must not be treated as completed calls.
             */

            $recentCallCheck = $conn->prepare("
                SELECT history_id
                FROM client_call_history
                WHERE cid = :cid
                AND call_datetime >= DATE_SUB(
                    NOW(),
                    INTERVAL 5 DAY
                )
                AND COALESCE(comment, '') NOT LIKE '[NOTE]%'
                ORDER BY
                    call_datetime DESC,
                    history_id DESC
                LIMIT 1
            ");

            $activeScheduleCheck = $conn->prepare("
                SELECT
                    cl_id,
                    call_date
                FROM client_call_schedule
                WHERE cid = :cid
                AND COALESCE(called, 0) = 0
                AND call_date >= CURDATE()
                ORDER BY
                    call_date ASC,
                    cl_id ASC
                LIMIT 1
            ");

            $insertSchedule = $conn->prepare("
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

            foreach ($clients as $clientId) {

                $clientId = (int)$clientId;

                if ($clientId <= 0) {
                    $failed++;
                    continue;
                }

                $clientCheck->execute(array(
                    ":cid" => $clientId
                ));

                if (!$clientCheck->fetchColumn()) {
                    $failed++;
                    continue;
                }

                /*
                 * Block only real calls within the last five days.
                 */

                $recentCallCheck->execute(array(
                    ":cid" => $clientId
                ));

                if ($recentCallCheck->fetchColumn()) {
                    $duplicate++;
                    continue;
                }

                /*
                 * Block an existing schedule for today or later.
                 */

                $activeScheduleCheck->execute(array(
                    ":cid" => $clientId
                ));

                if (
                    $activeScheduleCheck->fetch(
                        PDO::FETCH_ASSOC
                    )
                ) {
                    $duplicate++;
                    continue;
                }

                try {
                    $insertSchedule->execute(array(
                        ":cid" => $clientId,
                        ":uid" => $uid,
                        ":call_date" => $callDate
                    ));

                    $assigned++;

                } catch (PDOException $e) {

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

            die(
                "Database Error: " .
                htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                )
            );
        }

        header(
            "Location: admin.php?action=clientassignment" .
            "&assigned=" . $assigned .
            "&duplicate=" . $duplicate .
            "&failed=" . $failed
        );

        exit;

    /*
    |--------------------------------------------------------------------------
    | Save Initial Call
    |--------------------------------------------------------------------------
    */

    case "savecall":

        saveCall(
            $conn,
            $dta
        );

        exit;

    /*
    |--------------------------------------------------------------------------
    | Save Client Callback
    |--------------------------------------------------------------------------
    */

    case "savecallback":

        saveCallback(
            $conn,
            $dta
        );

        exit;

    /*
    |--------------------------------------------------------------------------
    | Save Comment-Only History Entry
    |--------------------------------------------------------------------------
    */

    case "savecomment":

        saveComment(
            $conn,
            $dta
        );

        exit;

    /*
    |--------------------------------------------------------------------------
    | Get One Updated Call Row
    |--------------------------------------------------------------------------
    */

    case "getcallrow":

        getCallRow(
            $conn,
            $dta
        );

        exit;

    /*
    |--------------------------------------------------------------------------
    | Existing Redirect Actions
    |--------------------------------------------------------------------------
    */

    case "reschedule":

        header(
            "Location: admin.php?action=todayscalls"
        );

        exit;

    case "deleteassignment":

        header(
            "Location: admin.php?action=clientassignment"
        );

        exit;

    default:

        header("Location: admin.php");
        exit;
}

/*
|--------------------------------------------------------------------------
| Save Initial Call
|--------------------------------------------------------------------------
*/

function saveCall(
    $conn,
    $userData
) {
    prepareJsonResponse();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError(
            "Invalid request method."
        );
    }

    $clid = isset($_POST['clid'])
        ? (int)$_POST['clid']
        : 0;

    $responseType = isset($_POST['response_type'])
        ? (int)$_POST['response_type']
        : 0;

    $connected = isset($_POST['connected'])
        ? (int)$_POST['connected']
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

    $connected =
        $connected === 1 ? 1 : 0;

    if ($clid <= 0) {
        jsonError(
            "Invalid Call ID."
        );
    }

    /*
     * A response type is required only for a connected call.
     */

    if (
        $connected === 1 &&
        $responseType <= 0
    ) {
        jsonError(
            "Please select a response type."
        );
    }

    if ($connected === 0) {
        $responseType = null;
    }

    validateOptionalDate(
        $followupDate,
        "Follow-up date"
    );

    try {
        $conn->beginTransaction();

        $schedule = getScheduleForUpdate(
            $conn,
            $clid,
            $uid,
            $userData
        );

        if (
            (int)$schedule['called'] === 1
        ) {
            $conn->rollBack();

            jsonError(
                "This call response has already been saved."
            );
        }

        $cid =
            (int)$schedule['cid'];

        /*
         * Update the scheduled call.
         */

        $updateSchedule = $conn->prepare("
            UPDATE client_call_schedule
            SET
                called = 1,
                connected = :connected,
                response_type_id = :response_type_id,
                latest_comment = :comments
            WHERE cl_id = :clid
            AND called = 0
        ");

        $updateSchedule->bindValue(
            ":connected",
            $connected,
            PDO::PARAM_INT
        );

        bindNullableInteger(
            $updateSchedule,
            ":response_type_id",
            $responseType
        );

        $updateSchedule->bindValue(
            ":comments",
            $comments,
            PDO::PARAM_STR
        );

        $updateSchedule->bindValue(
            ":clid",
            $clid,
            PDO::PARAM_INT
        );

        $updateSchedule->execute();

        if (
            $updateSchedule->rowCount() === 0
        ) {
            $conn->rollBack();

            jsonError(
                "The call was already updated by another user."
            );
        }

        /*
         * Insert completed call into history.
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
                :connected,
                :response_type_id,
                :comment,
                NOW()
            )
        ");

        $insertHistory->bindValue(
            ":clid",
            $clid,
            PDO::PARAM_INT
        );

        $insertHistory->bindValue(
            ":cid",
            $cid,
            PDO::PARAM_INT
        );

        $insertHistory->bindValue(
            ":uid",
            $uid,
            PDO::PARAM_INT
        );

        $insertHistory->bindValue(
            ":connected",
            $connected,
            PDO::PARAM_INT
        );

        bindNullableInteger(
            $insertHistory,
            ":response_type_id",
            $responseType
        );

        $insertHistory->bindValue(
            ":comment",
            $comments,
            PDO::PARAM_STR
        );

        $insertHistory->execute();

        /*
         * Add optional follow-up schedule.
         */

        $followupResult = scheduleFollowup(
            $conn,
            $cid,
            $uid,
            $followupDate
        );

        $conn->commit();

        $message =
            "Call saved successfully.";

        $message .= buildFollowupMessage(
            $followupResult
        );

        jsonSuccess(array(
            "message" => $message,
            "clid" => $clid,
            "connected" => $connected
        ));

    } catch (PDOException $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        jsonError(
            "Database Error: " .
            $e->getMessage()
        );
    }
}

/*
|--------------------------------------------------------------------------
| Save Client Callback
|--------------------------------------------------------------------------
*/

function saveCallback(
    $conn,
    $userData
) {
    prepareJsonResponse();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError(
            "Invalid request method."
        );
    }

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
        jsonError(
            "Invalid Call ID."
        );
    }

    if ($responseType <= 0) {
        jsonError(
            "Please select a response type."
        );
    }

    validateOptionalDate(
        $followupDate,
        "Follow-up date"
    );

    try {
        $conn->beginTransaction();

        $schedule = getScheduleForUpdate(
            $conn,
            $clid,
            $uid,
            $userData
        );

        /*
         * A callback can be added only after a Not Connected call.
         */

        if (
            (int)$schedule['called'] !== 1 ||
            (int)$schedule['connected'] !== 0
        ) {
            $conn->rollBack();

            jsonError(
                "A callback can only be added after a Not Connected call."
            );
        }

        $cid =
            (int)$schedule['cid'];

        $callbackComment =
            "Client called back.";

        if ($comments !== '') {
            $callbackComment .=
                " " . $comments;
        }

        /*
         * Preserve the original Not Connected history entry.
         * Add a separate Connected callback history entry.
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
            ":response_type_id" =>
                $responseType,
            ":comment" =>
                $callbackComment
        ));

        /*
         * Change the current schedule status to Connected.
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
            ":response_type_id" =>
                $responseType,
            ":comment" =>
                $callbackComment,
            ":clid" => $clid
        ));

        $followupResult = scheduleFollowup(
            $conn,
            $cid,
            $uid,
            $followupDate
        );

        $conn->commit();

        $message =
            "Client callback saved successfully.";

        $message .= buildFollowupMessage(
            $followupResult
        );

        jsonSuccess(array(
            "message" => $message,
            "clid" => $clid,
            "connected" => 1
        ));

    } catch (PDOException $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        jsonError(
            "Database Error: " .
            $e->getMessage()
        );
    }
}

/*
|--------------------------------------------------------------------------
| Save Comment-Only Entry
|--------------------------------------------------------------------------
|
| The note is stored in the existing history comment column with a
| reserved [NOTE] prefix. No database-column change is needed.
|
| This action does not change:
| - called
| - connected
| - response_type_id
| - latest_comment in client_call_schedule
|
*/

function saveComment(
    $conn,
    $userData
) {
    prepareJsonResponse();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonError(
            "Invalid request method."
        );
    }

    $clid = isset($_POST['clid'])
        ? (int)$_POST['clid']
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
        jsonError(
            "Invalid Call ID."
        );
    }

    if ($comments === '') {
        jsonError(
            "Please enter a comment."
        );
    }

    validateOptionalDate(
        $followupDate,
        "Follow-up date"
    );

    try {
        $conn->beginTransaction();

        $schedule = getScheduleForUpdate(
            $conn,
            $clid,
            $uid,
            $userData
        );

        /*
         * Comment button is available only after an initial
         * call result has been saved.
         */

        if (
            (int)$schedule['called'] !== 1
        ) {
            $conn->rollBack();

            jsonError(
                "Please save the initial call result before adding a comment."
            );
        }

        $cid =
            (int)$schedule['cid'];

        $currentConnected =
            (int)$schedule['connected'] === 1
                ? 1
                : 0;

        /*
         * Prefix is hidden later on the history page.
         */

        $noteComment =
            CLIENT_NOTE_PREFIX .
            $comments;

        $insertNote = $conn->prepare("
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
                :connected,
                NULL,
                :comment,
                NOW()
            )
        ");

        $insertNote->execute(array(
            ":clid" => $clid,
            ":cid" => $cid,
            ":uid" => $uid,
            ":connected" =>
                $currentConnected,
            ":comment" =>
                $noteComment
        ));

        /*
         * An optional follow-up can still be created.
         */

        $followupResult = scheduleFollowup(
            $conn,
            $cid,
            $uid,
            $followupDate
        );

        $conn->commit();

        $message =
            "Comment added successfully.";

        $message .= buildFollowupMessage(
            $followupResult
        );

        jsonSuccess(array(
            "message" => $message,
            "clid" => $clid,
            "connected" =>
                $currentConnected
        ));

    } catch (PDOException $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        jsonError(
            "Database Error: " .
            $e->getMessage()
        );
    }
}

/*
|--------------------------------------------------------------------------
| Get One Updated Call Row
|--------------------------------------------------------------------------
*/

function getCallRow(
    $conn,
    $userData
) {
    prepareJsonResponse();

    $clid = isset($_GET['clid'])
        ? (int)$_GET['clid']
        : 0;

    $uid = isset($_SESSION['id'])
        ? (int)$_SESSION['id']
        : 0;

    if ($clid <= 0) {
        jsonError(
            "Invalid Call ID."
        );
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

        $row = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$row) {
            jsonError(
                "Call record not found."
            );
        }

        if (
            (int)$userData['level'] !== 1 &&
            (int)$row['uid'] !== $uid
        ) {
            jsonError(
                "You are not authorised to view this call."
            );
        }

        jsonSuccess(array(
            "cl_id" =>
                (int)$row['cl_id'],
            "called" =>
                (int)$row['called'],
            "connected" =>
                (int)$row['connected'],
            "comment" =>
                $row['latest_comment']
        ));

    } catch (PDOException $e) {

        jsonError(
            "Database Error: " .
            $e->getMessage()
        );
    }
}

/*
|--------------------------------------------------------------------------
| JSON Response Helpers
|--------------------------------------------------------------------------
*/

function prepareJsonResponse()
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header(
        'Content-Type: application/json'
    );

    header(
        'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
    );
}

function jsonSuccess($data)
{
    $response = array_merge(
        array(
            "status" => "success"
        ),
        $data
    );

    echo json_encode($response);
    exit;
}

function jsonError($message)
{
    echo json_encode(array(
        "status" => "error",
        "message" => $message
    ));

    exit;
}

/*
|--------------------------------------------------------------------------
| Date Validation
|--------------------------------------------------------------------------
*/

function validateOptionalDate(
    $dateValue,
    $fieldLabel
) {
    if ($dateValue === '') {
        return;
    }

    $dateObject = DateTime::createFromFormat(
        'Y-m-d',
        $dateValue
    );

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !==
            $dateValue
    ) {
        jsonError(
            "Invalid " .
            strtolower($fieldLabel) .
            "."
        );
    }

    if (
        $dateValue < date('Y-m-d')
    ) {
        jsonError(
            $fieldLabel .
            " cannot be in the past."
        );
    }
}

/*
|--------------------------------------------------------------------------
| Retrieve and Authorize Schedule
|--------------------------------------------------------------------------
*/

function getScheduleForUpdate(
    $conn,
    $clid,
    $uid,
    $userData
) {
    $scheduleStmt = $conn->prepare("
        SELECT
            cl_id,
            cid,
            uid,
            call_date,
            called,
            connected,
            response_type_id,
            latest_comment
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

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        jsonError(
            "Call schedule not found."
        );
    }

    /*
     * Level 1 can update any scheduled call.
     * Other levels can update only their assigned calls.
     */

    if (
        (int)$userData['level'] !== 1 &&
        (int)$schedule['uid'] !==
            (int)$uid
    ) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        jsonError(
            "You are not authorised to update this call."
        );
    }

    return $schedule;
}

/*
|--------------------------------------------------------------------------
| Bind Nullable Integer
|--------------------------------------------------------------------------
*/

function bindNullableInteger(
    $statement,
    $parameter,
    $value
) {
    if ($value === null) {

        $statement->bindValue(
            $parameter,
            null,
            PDO::PARAM_NULL
        );

    } else {

        $statement->bindValue(
            $parameter,
            (int)$value,
            PDO::PARAM_INT
        );
    }
}

/*
|--------------------------------------------------------------------------
| Optional Follow-up Scheduling
|--------------------------------------------------------------------------
*/

function scheduleFollowup(
    $conn,
    $cid,
    $uid,
    $followupDate
) {
    $result = array(
        "scheduled" => false,
        "skipped" => false
    );

    if ($followupDate === '') {
        return $result;
    }

    /*
     * Prevent another unresolved active schedule.
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
        $result['skipped'] = true;
        return $result;
    }

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
            ":call_date" =>
                $followupDate
        ));

        $result['scheduled'] = true;

    } catch (PDOException $e) {

        if ($e->getCode() === "23000") {
            $result['skipped'] = true;
        } else {
            throw $e;
        }
    }

    return $result;
}

/*
|--------------------------------------------------------------------------
| Follow-up Result Message
|--------------------------------------------------------------------------
*/

function buildFollowupMessage(
    $followupResult
) {
    if (!is_array($followupResult)) {
        return '';
    }

    if (
        !empty(
            $followupResult['scheduled']
        )
    ) {
        return " Follow-up call scheduled.";
    }

    if (
        !empty(
            $followupResult['skipped']
        )
    ) {
        return " Follow-up was skipped because an active schedule already exists.";
    }

    return '';
}

$conn = null;
?>