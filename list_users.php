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

if($dta['level'] == 1)
{

$query = "select * from users";
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();
?>

<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Dashboard</li>
			</ol>
</div>
<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-heading"><a href="admin.php?action=adduser"><button name="addauser" class="btn btn-primary">Add a User</button></a></div>
					<div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uid" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="uid" data-sortable="true">ID</th>
						        <th data-field="name"  data-sortable="true">Name</th>
						        <th data-field="email" data-sortable="true">Email</th>
						        <th data-field="Status" >Status</th>
						        <th data-field="cmd" >Actions</th>
						    </tr>
						    </thead>
						   <tbody>
<?php
$i=1;
foreach( $data as $row) { ?>
    <tr>
  		<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
    	<td data-search="<?php echo $row['name']; ?>"> <?php echo $row['name']; ?></td>
    	<td data-search="<?php echo $row['email']; ?>"> <?php echo $row['email']; ?></td>    	
    	<td> <?php if($row['status']==1) { echo "Active"; } else echo "Disabled"; ?></td>
    	<td> 
    		<a href="usercmd.php?do=edit&id=<?php echo $row['uid']; ?>"><img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /></a>
    				 &nbsp;&nbsp;&nbsp;
    				<a href ="usercmd.php?do=delete&id=<?php echo $row['uid']; ?>" onClick="return confirm('Are you sure you want to delete ?')"><img src="images/b_drop.png" alt="Delete" width="16" height="16" border="0" title="Delete"/></a>
    				 &nbsp;&nbsp;&nbsp;
    				<a href="usercmd.php?do=changestatus&id=<?php echo $row['uid']; ?>"><?php if($row['status']==1) { echo "Deactivate"; } else echo "Activate"; ?></a>
    			
    	</td>
    </tr>
    <?php
}
?>
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
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>
