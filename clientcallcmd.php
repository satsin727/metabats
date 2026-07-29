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

try {
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database Connection Failed : " . $e->getMessage());
}

$uid = $_SESSION['id'];

$stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE uid = :uid
    LIMIT 1
");

$stmt->execute(array(
    ":uid" => $uid
));

if ($stmt->rowCount() == 0) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$dta = $stmt->fetch(PDO::FETCH_ASSOC);

$action = "";

if (isset($_GET['action'])) {
    $action = trim($_GET['action']);
}

/*
|--------------------------------------------------------------------------
| Route Actions
|--------------------------------------------------------------------------
*/

switch ($action) {

    /*==========================================================
    =            Assign Client Call
    ==========================================================*/
    case "assign":
        if ($_SERVER['REQUEST_METHOD'] != "POST") {
            header("Location: admin.php?action=clientassignment");
            exit;
        }

        $call_date = "";
        if (isset($_POST['call_date'])) {
            $call_date = trim($_POST['call_date']);
        }

        $clients = array();
        if (isset($_POST['client'])) {
            $clients = $_POST['client'];
        }

        if ($call_date == "") {
            header("Location: admin.php?action=clientassignment&msg=date");
            exit;
        }

        if (count($clients) == 0) {
            header("Location: admin.php?action=clientassignment&msg=noclients");
            exit;
        }

        $assigned = 0;
        $duplicate = 0;
        $failed = 0;

        $conn->beginTransaction();

        $insert = $conn->prepare("
            INSERT INTO client_call_schedule (
                cid, uid, call_date, called, connected, latest_comment, created_datetime
            ) VALUES (
                :cid, :uid, :call_date, 0, 0, '', NOW()
            )
        ");

        try {
            foreach ($clients as $cid) {
                $cid = (int)$cid;

                if ($cid <= 0) {
                    $failed++;
                    continue;
                }

                try {
                    $insert->execute(array(
                        ":cid"       => $cid,
                        ":uid"       => $uid,
                        ":call_date" => $call_date
                    ));
                    $assigned++;
                } catch(PDOException $e) {
                    if ($e->getCode() == "23000") {
                        $duplicate++;
                    } else {
                        throw $e;
                    }
                }
            }

            $conn->commit();

        } catch(PDOException $e) {
            $conn->rollBack();
            die("Database Error : " . $e->getMessage());
        }

        header("Location: admin.php?action=clientassignment&assigned=" . $assigned . "&duplicate=" . $duplicate . "&failed=" . $failed);
        exit;
        break;

    /*==========================================================
    =            Save Client Call (AJAX Endpoint)
    ==========================================================*/
    case "savecall":
        saveCall($conn);
        break;

    /*==========================================================
    =            Reschedule Call
    ==========================================================*/
    case "reschedule":
        header("Location: admin.php?action=todayscalls");
        exit;
        break;

    /*==========================================================
    =            Delete Assignment
    ==========================================================*/
    case "deleteassignment":
        header("Location: admin.php?action=clientassignment");
        exit;
        break;



        // Inside clientcallcmd.php
    case "notconnected":
        
        if (isset($_GET['action']) && $_GET['action'] == 'notconnected') {
            // Clear any previous output or blank spaces that might break JSON
            ob_clean(); 
            
            // Tell the browser to expect strict JSON
            header('Content-Type: application/json');

            $clid = $_POST['clid'];
            $connected = $_POST['connected']; 

            try {
                $stmt = $conn->prepare("UPDATE client_call_schedule SET called = 1, connected = :connected WHERE cl_id = :clid");
                $stmt->execute([
                    ':connected' => $connected,
                    ':clid' => $clid
                ]);

                echo json_encode(['status' => 'success']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            }
            
            // Stop script execution immediately so nothing else loads
            exit; 
            break;
        }

    /*==========================================================
    =            Default
    ==========================================================*/
    default:
        header("Location: admin.php");
        exit;
        break;
}


/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function saveCall($conn) {
    // Clear any previous output buffers to prevent stray text/whitespace from breaking JSON
    if (ob_get_length()) {
        ob_clean();
    }
    
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(array("status" => "error", "message" => "Invalid request method."));
        exit;
    }

    $clid          = isset($_POST['clid']) ? (int)$_POST['clid'] : 0;
    $response_type = isset($_POST['response_type']) ? (int)$_POST['response_type'] : 0;
    $connected     = isset($_POST['connected']) ? (int)$_POST['connected'] : 1;
    $comments      = isset($_POST['comments']) ? trim($_POST['comments']) : '';
    $followup_date = isset($_POST['followup_date']) ? trim($_POST['followup_date']) : '';
    $uid           = $_SESSION['id'];

    if ($clid <= 0) {
        echo json_encode(array("status" => "error", "message" => "Invalid Call ID."));
        exit;
    }
    if ($response_type <= 0) {
        echo json_encode(array("status" => "error", "message" => "Please select a response type."));
        exit;
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("SELECT cid FROM client_call_schedule WHERE cl_id = :clid LIMIT 1");
        $stmt->execute(array(":clid" => $clid));
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            $conn->rollBack();
            echo json_encode(array("status" => "error", "message" => "Call schedule not found."));
            exit;
        }
        $cid = $schedule['cid'];

        // Update schedule table
        $update = $conn->prepare("
            UPDATE client_call_schedule 
            SET 
                called = 1, 
                connected = :connected, 
                response_type_id = :response_type_id,
                latest_comment = :comments 
            WHERE cl_id = :clid
        ");
        $update->execute(array(
            ":connected" => $connected,
            ":comments"  => $comments,
            ":response_type_id" => $response_type,
            ":clid"      => $clid
        ));

        // Insert into history table
        $insertHistory = $conn->prepare("
            INSERT INTO client_call_history (
                cl_id, cid, uid, call_datetime, connected, response_type_id, comment, created_datetime
            ) VALUES (
                :clid, :cid, :uid, NOW(), :connected, :response_type_id, :comment, NOW()
            )
        ");
        $insertHistory->execute(array(
            ":clid"             => $clid,
            ":cid"              => $cid,
            ":uid"              => $uid,
            ":connected"        => $connected,
            ":response_type_id" => $response_type,
            ":comment"          => $comments
        ));

        // Only schedule a new row if a follow-up date was actually provided
        if (!empty($followup_date)) {
            $insertFollowup = $conn->prepare("
                INSERT INTO client_call_schedule (
                    cid, uid, call_date, called, connected, latest_comment, created_datetime
                ) VALUES (
                    :cid, :uid, :call_date, 0, 0, '', NOW()
                )
            ");
            $insertFollowup->execute(array(
                ":cid"       => $cid,
                ":uid"       => $uid,
                ":call_date" => $followup_date
            ));
        }

        $conn->commit();

        echo json_encode(array("status" => "success", "message" => "Call saved successfully."));
        exit;

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode(array("status" => "error", "message" => "Database Error: " . $e->getMessage()));
        exit;
    }
}


$conn = null;
?>