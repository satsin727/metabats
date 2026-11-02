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


if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
require("includes/menu.php");
echo '<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
		<div class="col-lg-12">
				<div class="panel panel-default">
				<div class="panel-heading"><a href="admin.php?action=listall"><button name="clientslist" class="btn btn-primary">Clients list</button></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="admin.php?action=clientlistdownload"><button name="Download" class="btn btn-primary">Download</button></a></div>
	';
if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{

// The list is loaded page by page from clientslist_ajax.php (server-side pagination),
// so the browser no longer receives all 50,000+ rows at once.
?>
						<table data-toggle="table" data-url="clientslist_ajax.php" data-side-pagination="server" data-cache="false" data-page-size="50" data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="ID" data-sortable="false">ID</th>
						        <th data-field="Company Name"  data-sortable="true">Company Name</th>
						        <th data-field="Full Name Name" data-sortable="true">Full Name</th>
						        <th data-field="Email ID" data-sortable="true">Email ID</th>						        
						        <th data-field="Phone Number" data-sortable="true">Phone Number</th>
						        <th data-field="Location" data-sortable="true">Location</th>
						        <th data-field="Timezone" data-sortable="true">Timezone</th>
						        <th data-field="Tier" data-sortable="true">Tier</th>
						      <?php /* if($dta['level'] == 1 || $dta['level'] == 2 ) { ?>  
						      	<th data-field="c	md" >Actions</th> 
						 	     <?php } */?>
						    </tr>
						    </thead>
						   <tbody>
						   </tbody>
						</table>
						
<?php

} // for client queries


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
	