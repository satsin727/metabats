<?php
if($_SESSION['id'])
{
$sessid = $_SESSION['id'];
}
else
{
	header( "Location: admin.php" ); 

}
$conn=null;
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from users where `uid` = :u";
$ins= $conn->prepare($query);
$ins->bindValue( ":u", $sessid, PDO::PARAM_STR );
$ins->execute();
$dta = $ins->fetch();

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
require("includes/menu.php");

?> 
<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Dashboard</li>
			</ol>
		</div><!--/.row-->
		
		<div class="row">
			<div class="col-lg-12">
				<h1 class="page-header">Welcome to Metahorizon Bench ATS</h1>
			</div>
		</div><!--/.row-->
		
	</div>	<!--/.main-->

	<a href="admin.php?action=updatecallinglist"><button>Upload Calling list</button></a>

<?php
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>