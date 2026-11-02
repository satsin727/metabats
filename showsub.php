<?php
if($_SESSION['id'])
{
$sessid = $_SESSION['id'];
}
else
{
	header( "Location: admin.php" ); exit; 

}

$conn=null;
$conn = bats_db();
$query = "select * from users where `uid` = :u";
$ins= $conn->prepare($query);
$ins->bindValue( ":u", $sessid, PDO::PARAM_STR );
$ins->execute();
$dta = $ins->fetch();
$curdate =date('Y-m-d');

if(isset($_GET['date']))
{
    $curdate = $_GET['date'];
}

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{
	// Rows are loaded page by page from showsub_ajax.php (server-side pagination).
	$ajaxUrl = 'showsub_ajax.php?date=' . rawurlencode($curdate);
	if (isset($_GET['smid'])) { $ajaxUrl .= '&smid=' . rawurlencode($_GET['smid']); }
?>

<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Submissions</li>
			</ol>
</div>
<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-heading"><!-- <a href="admin.php?action=postreq"><button name="addauser" class="btn btn-primary">Post Req</button></a>&nbsp;&nbsp;&nbsp;<a href="admin.php?action=addskill"><button name="addauser" class="btn btn-primary">Add Skill</button></a> --></div>
					<div class="panel-body">
						<table data-toggle="table" data-url="<?php echo htmlspecialchars($ajaxUrl, ENT_QUOTES); ?>" data-side-pagination="server" data-cache="false" data-page-size="50" data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-order="desc">
						    <thead>
						    <tr>
						        <th data-field="Datetime"  data-sortable="true">Datetime</th>
						        <th data-field="id" data-sortable="false">S.no</th>
								<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?>   <th data-field="name" data-sortable="true">SM</th> <?php }	?> 
						        <th data-field="Role"  data-sortable="true">Skill</th>	
						        <th data-field="rlocation"  data-sortable="true">Location</th>								
						        <th data-field="remail" data-sortable="true">Recruiter Email</th>
						        <th data-field="cname"  data-sortable="true">Consultant</th>
						        <th data-field="rate"  data-sortable="true">Rate</th>
						        <th data-field="client"  data-sortable="true">Client</th>								
						        <th data-field="status"  data-sortable="true">Submission Status</th>
						        <th data-field="arcstatus"  data-sortable="true">Status</th>
								<th data-field="addECI"  data-sortable="true">Add ECI</th>
						        <th data-field="comment"  data-sortable="true">Comment</th>
								<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?> 	
						        <th data-field="action" data-sortable="true">Action</th>  <?php } ?>
						    </tr>
						    </thead>
						   <tbody>
						   </tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

</div>
<?php

}
else
{
	echo "<script>
alert('You Need to be Admin to view this page.');
window.location.href='admin.php';
</script>"; 
}
$conn = null;
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>
