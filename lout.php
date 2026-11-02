<?php
require( "config.php" );
if(isset($_SESSION['id']))
{
$sessid = $_SESSION['id'];
}
else
{
	header( "Location: admin.php" ); 
	exit;
}
// SECURITY FIX: only log out the user who owns this session (was: any id from the URL)
$uid = $_SESSION['id'];

$conn=null;
 $conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD);
 $q4 = "UPDATE `users` set `sess` = \"0\" where `uid` = :id ";
 $lout= $conn->prepare($q4);
 $lout->bindValue( ":id", $uid, PDO::PARAM_INT );
 $lout->execute();
 unset( $_SESSION['username'] );
  unset ($_SESSION['id']);
  unset ($_SESSION['date']); 
  header( "Location: admin.php" );
  exit;
  
ob_flush();

?>