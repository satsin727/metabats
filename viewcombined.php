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
echo '<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
<div class="col-lg-12">
				<div class="panel panel-default">
			<div class="panel-heading"><a href="admin.php?action=clientlistdownload"><button name="Download" class="btn btn-primary">Download</button></a></div>

';
if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{

$uid = $dta['uid'];


if($dta['level'] == 1 || $dta['level'] == 2 )
{
$queryv = "select distinct * from clients where `status` = 1 group by `remail`";
}
elseif ($dta['level'] == 3)
{
$queryv = "select distinct * from clients where `uid` = $uid AND `status` = 1 group by `remail`"; }
$connv = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$insv= $connv->prepare($queryv);
$insv->execute();
$datav = $insv->fetchAll();

?>
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uid" data-sort-order="asc">
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
<?php
$i=1;
foreach( $datav as $row) {
?>
    <tr>
  		<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
    	<td data-search="<?php echo $row['companyname']; ?>"> <?php echo $row['companyname']; ?></td>
    	<td data-search="<?php echo $row['rname']; ?>"> <?php echo $row['rname']; ?></td>    	
    	<td data-search="<?php echo $row['remail']; ?>"> <?php echo $row['remail']; ?></td>
    	<td data-search="<?php echo $row['rphone']; ?>"> <?php echo $row['rphone']; ?></td>
    	<td data-search="<?php echo $row['rlocation']; ?>"> <?php echo $row['rlocation']; ?></td>    	
    	<td data-search="<?php echo $row['rtimezon']; ?>"> <?php echo $row['rtimezon']; ?></td>   	
    	<td data-search="<?php echo $row['tier']; ?>"> <?php echo $row['tier']; ?></td>
    	<?php /* <td> 
		<a href="listcmd.php?do=addcontact&lid=<?php echo $row['listid']; ?>">Add Contact<!--<img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /> --></a>
    				 &nbsp;&nbsp;&nbsp;
    				<a href ="listcmd.php?do=download&lid=<?php echo $row['listid']; ?>" onClick="return confirm('Are you sure you want to Download ?')">Download List</a>
    				 &nbsp;&nbsp;&nbsp;
    				<?php if($dta['level'] == 1 || $dta['level'] == 2 )
{
	?> <a href="listcmd.php?do=changestatus&lid=<?php echo $row['listid']; ?>"><?php if($row['status']==1) { echo "Deactivate"; } else echo "Activate"; ?></a>
    				 &nbsp;&nbsp;&nbsp; <?php } ?>
    				<a href="listcmd.php?do=viewlist&lid=<?php echo $row['listid']; ?>">View List</a>
    	</td>  */ ?>
    </tr>
    <?php
}
?>
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
	