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
$userid=$dta['uid'];

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
$selected = "listall";
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3 )
{


if(isset($_GET['do']))
{
	$do="foobar";
	$do=$_GET['do'];	
	?>
	<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
			</ol>
		</div>
		<?php
	if($do=='delete')
	{
		if(isset($_GET['appid']))
		{
			$app_id = $_GET['appid'];
			$conn->query("UPDATE `app_data` SET `status` = '0' WHERE `app_id` = $app_id");
			echo "<script>
			alert('Application Deleted');
			window.location.href='admin.php?action=showapplications';
			</script>";
		}
		elseif(isset($_GET['rcid']))
		{
			$app_id = $_GET['rcid'];
			$conn->query("UPDATE `app_data` SET `rcdone` = '0' WHERE `app_id` = $app_id");
			echo "<script>
			alert('RC Deleted');
			window.location.href='admin.php?action=showrc';
			</script>";
		}
		elseif(isset($_GET['subid']))
		{
			$app_id = $_GET['subid'];
			$conn->query("UPDATE `app_data` SET `subdone` = '0' WHERE `app_id` = $app_id");
			echo "<script>
			alert('Sub Deleted');
			window.location.href='admin.php?action=showsub';
			</script>";
		}
		elseif(isset($_GET['eciid']))
		{
			$eci_id = $_GET['eciid'];
			$conn->query("UPDATE `eci	` SET `status` = '0' WHERE `eci_id` = $eci_id");
			echo "<script>
			alert('ECI Deleted');
			window.location.href='admin.php?action=showeci';
			</script>";
		}
		
	}	

	elseif($do=='addsub')
	{
			$app_id = $_GET['rcid'];
			$conn->query("UPDATE `app_data` SET `subdone` = '1', `subdate` = CURRENT_TIMESTAMP, `subto` = 1, `ars_status` =7 WHERE `app_id` = $app_id");
			echo "<script>
			alert('Sub Added');
			window.location.href='admin.php?action=showrc';
			</script>";		
	}

	else
	{
		echo "<script>
alert('Not a valid command.');
window.location.href='admin.php?action=listall';
</script>";
	}

} //for $do

} //for admin
else
{
	echo "<script>
alert('You Need to be Admin to view this page.');
window.location.href='admin.php';
</script>"; 
}

?>
</div>

<?php
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>
