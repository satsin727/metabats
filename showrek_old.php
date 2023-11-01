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

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{

$uid = $dta['uid'];

if($dta['level'] == 1)
{
	$query = "select * from req where status =1 order by datetime desc";
}
else {
	$query = "select * from req where status =1 && uid = $uid order by datetime desc";
}


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
					<div class="panel-heading"><a href="admin.php?action=postreq"><button name="addauser" class="btn btn-primary">Post Req</button></a>&nbsp;&nbsp;&nbsp;<a href="admin.php?action=addskill"><button name="addauser" class="btn btn-primary">Add Skill</button></a></div>
					<div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uid" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="Datetime"  data-sortable="true">Datetime</th>
						        <th data-field="id" data-sortable="true">S.no</th>
						        <th data-field="name" data-sortable="true">Business Partner</th>
						        <th data-field="Role"  data-sortable="true">Job Title</th>	
						        <th data-field="rlocation"  data-sortable="true">Location</th>
						        <th data-field="rrate"  data-sortable="true">Rate</th>
						        <th data-field="ServiceStatus"  data-sortable="true">Service Status</th>
						        <th data-field="Comment"  data-sortable="true">Comment</th>
						        <th data-field="action" data-sortable="true">Action</th>
						    </tr>
						    </thead>
						   <tbody>
<?php
$i=1;
foreach( $data as $row) { 

$sid = $row['skillid'];
								$q2 = "select * from skill where `sid` = $sid";
								$ins3= $conn->prepare($q2);
								$ins3->execute(); 
								$dta2 = $ins3->fetch();
$cid = $row['cid'];
								$q3 = "select * from clients where `cid` = $cid";
								$ins4= $conn->prepare($q3);
								$ins4->execute(); 
								$dta3 = $ins4->fetch();

						
	?>
    <tr>
		<td data-search="<?php echo $row['datetime']; ?>"> <?php $time = strtotime($row['datetime']); $myFormatForView = date("m/d/y g:i A", $time); echo $myFormatForView; ?></td>
    	<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
    	<td data-search="<?php echo $dta3['remail']; ?>"> <a href="#" onClick="alert('\n\n\n\n<?php echo "Name: ".$dta3['rname']; ?>\n\n<?php echo"Email: ".$dta3['remail']; ?>\n\n<?php echo"Company Name: ".$dta3['companyname'];?>')"><?php echo $dta3['remail']; ?></a></td>   
		<td data-search="<?php echo $dta2['skillname']; ?>"> <a id="various3" href="leads/view.php?id=<?php echo $row['reqid']; ?>"><?php echo $dta2['skillname']; ?></a></td>
    	<td data-search="<?php echo $row['rlocation']; ?>"> <?php echo $row['rlocation']; ?></td>
    	<td data-search="<?php echo $row['rrate']; ?>"> <?php echo $row['rrate']; ?></td>   
    	<td> App: 0 RC: 0 Sub: 0 ECI: 0</td>
		<td data-search="<?php echo "comment"; ?>"> <?php echo "comment"; ?></td>     		
    	<td> 
    		<a href="reqcmd.php?do=edit&id=<?php echo $row['reqid']; ?>"><img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /></a>
    				<a href ="reqcmd.php?do=delete&id=<?php echo $row['reqid']; ?>" onClick="return confirm('Are you sure you want to remove this req ?')"><img src="images/b_drop.png" alt="Delete" width="16" height="16" border="0" title="Delete"/></a>
    			
    	
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
