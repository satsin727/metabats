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

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{

$uid = $dta['uid'];
if($dta['level'] == 1 || $dta['level'] == 2 )
{
$query = "select * from lists";
}
elseif ($dta['level'] == 3)
{	
$query = "select * from lists where `uid` = $uid AND `status` = 1"; }
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
					<div class="panel-heading"><a href="admin.php?action=addlist"><button name="addauser" class="btn btn-primary">Add a List </button></a></div>
					<div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uid" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="lid">ID</th>
						        <th data-field="listname"  data-sortable="true">List Name</th>
						        <th data-field="Manager Name" data-sortable="true">Uploaded by SM</th>
						        <th data-field="Date" data-sortable="true">Uploaded Date</th>
						        <th data-field="cmd" >Actions</th>
						    </tr>
						    </thead>
						   <tbody>
<?php
$i=1;
foreach( $data as $row) {
$listuid=$row['uid'];
$query = "select * from users where `uid` = :u";
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$ins= $conn->prepare($query);
$ins->bindValue( ":u", $listuid, PDO::PARAM_INT );
$ins->execute();
$dtal = $ins->fetch();

if($dta['level'] == 1 || $dta['level'] == 2 )
{
$ins3= $conn->prepare("SELECT COUNT(*) FROM `clients` WHERE `lid`=:lid and `status` = 1");
}
else{
$ins3= $conn->prepare("SELECT COUNT(*) FROM `clients` WHERE `lid`=:lid and `uid` = $listuid  and `status` = 1");
}
$ins3->bindValue( ":lid", $row['listid'], PDO::PARAM_INT );
$ins3->execute();
$total = $ins3->fetch();
$conn=null;

 ?>
    <tr>
  		<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
    	<td data-search="<?php echo $row['listname']; ?>"> <?php echo $row['listname']." (".$total['COUNT(*)']." contacts)"; ?></td>
    	<td data-search="<?php echo $dtal['name']; ?>"> <?php echo $dtal['name']; ?></td>    	
    	<td data-search="<?php echo $row['date']; ?>"> <?php echo $row['date']; ?></td>   
    	<td> 
		<a href="listcmd.php?do=addcontact&lid=<?php echo $row['listid']; ?>">Add Contact<!--<img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /> --></a>
    				 &nbsp;&nbsp;&nbsp;
    				<a href ="listcmd.php?do=download&lid=<?php echo $row['listid']; ?>" onClick="return confirm('Are you sure you want to Download ?')">Download List</a>
    				 &nbsp;&nbsp;&nbsp;
    				<?php if($dta['level'] == 1 || $dta['level'] == 2 )
{
	?> <a href="listcmd.php?do=changestatus&lid=<?php echo $row['listid']; ?>"><?php if($row['status']==1) { echo "Deactivate"; } else echo "Activate"; ?></a>
    				 &nbsp;&nbsp;&nbsp; <?php } ?>
    				<a href="listcmd.php?do=viewlist&lid=<?php echo $row['listid']; ?>">View List</a>
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
	