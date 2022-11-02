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

if($dta['level'] == 1 || $dta['level'] == 2)
{

	if(isset($_GET['status']))
	{
		$status = $_GET['status'];
	}
	else { $status = 1; }

$query = "select * from consultants where status = $status order by cfname asc";
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
					<div class="panel-heading"><a href="admin.php?action=addconsultant"><button name="addauser" class="btn btn-primary">Add a Consultant</button></a>&nbsp;&nbsp;&nbsp;<a href="admin.php?action=addskill"><button name="addauser" class="btn btn-primary">Add Skill</button></a>&nbsp;&nbsp;&nbsp;<a href="admin.php?action=assignconsultant"><button name="assignconsultant" class="btn btn-primary">Assign Consultant</button></a></div>
					<div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="name" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="ID">ID</th>
						        <th data-field="Skill" data-sortable="true">Skill</th>
						        <th data-field="name"  data-sortable="true">Name</th>
						        <th data-field="location" data-sortable="true">Location</th>
						        <th data-field="Visa Status" >Visa Status</th>
						        <th data-field="details" >Add Details</th>
						    </tr>
						    </thead>
						   <tbody>
<?php
$i=1;
foreach( $data as $row) { ?>
    <tr>
  		<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1; ?></td>
  		<?php
								$sid = $row['skill'];
								$conn2 = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
								$q2 = "select * from skill where `sid` = $sid";
								$ins2= $conn2->prepare($q2);
								$ins2->execute(); 
								$dta2 = $ins2->fetch(); 
								$conn2=null; ?>
		<td data-search="<?php echo $dta2['skillname']; ?>"> <?php echo $dta2['skillname']; ?></td>
    	<td data-search="<?php echo $row['cfname']; ?>"> <?php echo $row['cfname']; echo " "; echo $row['cmname'];echo " ";echo $row['clname']; ?></td>
    	<td data-search="<?php echo $row['colocation']; ?>"> <?php echo $row['colocation']; ?></td>
    	<td data-search="<?php echo $row['covisa']; ?>"> <?php echo $row['covisa']; ?></td>
    	<td> 
    		<a href="consultantdetails.php?add=marketdetails&id=<?php echo $row['cid']; ?>">Marketing</a>
    				 &nbsp;&nbsp;&nbsp;
    				<a href ="consultantdetails.php?add=educationdetails&id=<?php echo $row['cid']; ?>">Education</a>
    				 &nbsp;&nbsp;&nbsp;
    				<a href="consultantdetails.php?add=files&id=<?php echo $row['cid']; ?>">Other</a>&nbsp;&nbsp;&nbsp;
					<a href="consultantcmd.php?do=edit&id=<?php echo $row['cid']; ?>"><img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /></a>
    				<a href ="addcomment.php?conscom_id=<?php echo $row['cid']; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><img src="images/b_drop.png" alt="Delete" width="16" height="16" border="0" title="Delete"/></a>
    			
    	
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
