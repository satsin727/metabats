<?php

require("config.php");

try{
    $pdo = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die("ERROR: Could not connect. " . $e->getMessage());
}
 
// Attempt search query execution
try{
    if(isset($_REQUEST["term"])){
        // create prepared statement
        $sql = "SELECT DISTINCT remail FROM clients WHERE remail LIKE :term LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $term = $_REQUEST["term"] . '%';
        // bind parameters to statement
        $stmt->bindParam(":term", $term);
        // execute the prepared statement
        $stmt->execute();
        if($stmt->rowCount() > 0){
            function checkemail($str) {
                return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", trim(strtolower($str))) ) ? FALSE : TRUE;
          }
            while($row = $stmt->fetch()){
               if(!checkemail($_POST['cemail']))
                {
                echo "<p>" . strtolower($row["remail"]) . "</p>"; }
            }
        } 
    }  
} catch(PDOException $e){
    die("ERROR: Could not able to execute $sql. " . $e->getMessage());
}
 
// Close statement
unset($stmt);
 
// Close connection
unset($pdo);


?>