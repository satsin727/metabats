<?php

    $sessid = $_SESSION['id'];

    require("config.php");
    $numberofrecords = 30;

    if(!isset($_POST['searchTerm']))
    {
        $conn = null;
		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
        $stmt = $conn->prepare("select * from clients where uid = :u");
        $stmt->bindValue( ":u", $sessid, PDO::PARAM_INT );
        $stmt->execute();
        $dta2 = $stmt->fetchAll();
        $conn = null;
     
     }
     else
     {
        $search = $_POST["searchTerm"];
        $conn2 = null;
        $conn2 = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
        $query = "
        SELECT * FROM clients 
        WHERE rname LIKE '%".$search."%'
        OR companyname LIKE '%".$search."%' 
        OR remail LIKE '%".$search."%'
        ORDER BY remail LIMIT :limit
        ";
        $ins2= $conn2->prepare($query);
        $ins2->bindValue(':limit', (int)$numberofrecords, PDO::PARAM_INT);
        $ins2->execute();
        $dta2 = $ins2->fetchAll();
        $conn2 = null;
    }

    $response = array();

    foreach( $dta2 as $client) {
        $response[] = array(
            "cid" => $client['cid'],
            "rname" => $client['rname'],
            "companyname" => $client['companyname'],
            "remail" => $client['remail']        
        );
    }
    echo json_encode($response);
    exit();

?>