<?php
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


if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
require("includes/menu.php");

?> 
<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Reports</li>
			</ol>
		</div><!--/.row-->
		<div class="row">
			<div class="col-lg-12">
				<!--<h1 class="page-header">ConsultantWise Reports</h1> -->
			</div>
		</div><!--/.row-->
<style type="text/css">
    .btn {
    background-color: #30a5ff; /* Green */
    border: none;
    color: white;
    padding: 15px 15px 15px 15px;
    margin: 5px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 15px;
    width: 30%;
    height: 50px;
    }
</style>
       <a href="admin.php?action=showdailydata"><button class="btn">Realtime Daily App/RC/Sub</button></a>
       <a href="admin.php?action=showwtddata"><button class="btn">WTD App/RC/Sub</button></a>
       <a href="admin.php?action=showmtd"><button class="btn">Monthly Report</button></a>
        <button class="btn">Application Tracker</button>
		<button class="btn">RC Tracker</button>
        <button class="btn">Sub Tracker</button>
        <button class="btn">WTD Application Tracker</button>
		<button class="btn">WTD RC Tracker</button>
        <button class="btn">WTD Sub Tracker</button>     
        <button class="btn">MTD Application Tracker</button>
		<button class="btn">MTD RC Tracker</button>
        <button class="btn">MTD Sub Tracker</button>

        <a href="admin.php?action=showsmdata"><button class="btn">SM Snapshot</button> </a>              
        <button class="btn">Cummulative App/RC/Sub</button>
        <button class="btn">Skill Wise Req/App/RC/Sub/ECI</button>


	</div>	<!--/.main-->

<?php
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>