<?php
require( "config.php" );
if($_SESSION['id'])
{
$sessid = $_SESSION['id'];
}
else
{
	header( "Location: admin.php" ); 

}
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from users where `uid` = :u";
$ins= $conn->prepare($query);
$ins->bindValue( ":u", $sessid, PDO::PARAM_STR );
$ins->execute();
$dta = $ins->fetch();
$curdate =date('Y-m-d');

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
$selected = "showreports";
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{

$uid = $sessid;

$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );

if(isset($_GET['m']) && isset($_GET['y']) && isset($_GET['s']))

{
    $m = $_GET['m'];
    $y = $_GET['y'];
    $skill = $_GET['s'];
    $query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where B.skill = $skill and ( MONTH(A.appdate) = MONTH('$cdate') AND YEAR(A.appdate) = YEAR('$cdate') ) order by A.appdate asc";
    

}



$ins= $conn->prepare($q);
$ins->execute();
$data = $ins->fetchAll();

}
else
{
	echo "<script>
alert('You Need to be Admin to view this page.');
window.location.href='admin.php';
</script>"; 
}
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>
