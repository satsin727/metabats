<?php
session_start();

$sessid = $_SESSION['id'];

require("config.php");

$numberofrecords = 30;

try {

    $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // If no search term or search term is empty
    if (!isset($_POST['searchTerm']) || trim($_POST['searchTerm']) == "") {

        $stmt = $conn->prepare("
            SELECT *
            FROM clients
            WHERE uid = :uid
            ORDER BY remail
            LIMIT :limit
        ");

        $stmt->bindValue(':uid', $sessid, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $numberofrecords, PDO::PARAM_INT);
        $stmt->execute();

    } else {

        $search = trim($_POST['searchTerm']);

        $stmt = $conn->prepare("
            SELECT *
            FROM clients
            WHERE uid = :uid
            AND (
                rname LIKE :search
                OR companyname LIKE :search
                OR remail LIKE :search
            )
            ORDER BY remail
            LIMIT :limit
        ");

        $stmt->bindValue(':uid', $sessid, PDO::PARAM_INT);
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $numberofrecords, PDO::PARAM_INT);
        $stmt->execute();
    }

    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [];

    foreach ($clients as $client) {
        $response[] = [
            "cid"         => $client['cid'],
            "rname"       => $client['rname'],
            "companyname" => $client['companyname'],
            "remail"      => $client['remail']
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {

    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage()
    ]);

}

$conn = null;
exit;
?>