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
$ins->bindValue( ":u", $sessid, PDO::PARAM_INT );
$ins->execute();
$dta = $ins->fetch();


if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2)
{

?>

<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Assign Consultants</li>
			</ol>
</div>
<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-body">
<?php if(isset($_POST['assign']))  
{ 
$uid = $_POST['userid'];
$cid = $_POST['consultantid'];
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
/*$q= "Select * from assigned where `cid`= $cid and `uid` = $uid;";
$ins= $conn->prepare($q);
$ins->execute();
$udata = $ins->fetch();*/
$ifalreadyassigned = $conn->query("SELECT COUNT(*)from assigned where `cid`= $cid and `uid` = $uid")->fetchColumn();
if($ifalreadyassigned!=0)
{
echo "<script>
alert('Consultant to this user already assigned.!!!');
</script>";  
}

else {
	$query = "INSERT INTO `assigned` (`id`, `cid`, `uid`) VALUES (NULL, '$cid', '$uid');";
	$ins= $conn->prepare($query);
	$ins->execute();
	
 }

}
?>				<form action="#" method="post">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
<div class="form-group">
									<td width="10%" align="left" valign="top"><label>Select User</label> </td>									
								<td width="30%" align="left" valign="top">	<select name="userid" class="form-control-in">
									<?php
									$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
									$query = "select * from users where `status` = 1";
									$ins= $conn->prepare($query);
									$ins->execute();
									$udata = $ins->fetchAll();
									foreach( $udata as $row1) {
									?>
										<option value="<?php echo $row1['uid']; ?>"><?php echo $row1['name']; ?></option>
										<?php } ?>
									</select></td>
<td width="5%" align="left" valign="top"><label>&nbsp;===>&nbsp;</label></td>
									<td width="15%" align="left" valign="top"><label>Select Consultant</label></td>							
								<td width="30%" align="left" valign="top">	<select name="consultantid" class="form-control-in">

									<?php
									$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
									$query2 = "select * from consultants where `status` = 1";
									$ins2= $conn->prepare($query2);
									$ins2->execute();
									$cdata = $ins2->fetchAll();
									foreach( $cdata as $row2) {
									?>
										<option value="<?php echo $row2['cid']; ?>"><?php echo $row2['cfname']." ".$row2['clname']; ?></option>
										<?php } ?>
									</select></td>
									</div>
									</tr>
							<tr>
							<tr> <td align="left" valign="top">&nbsp;</td>	</tr>
<div class="form-group">	<td  width="0%" align="left" valign="top">&nbsp;</td>	
   							<td  width="30%" align="left" valign="top"><button type="submit" name="assign" class="btn btn-primary">Assign</button> </td> </div>					
                 </tr>
                 <tr> <td align="left" valign="top">&nbsp;</td>	</tr>
</form>  
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="uid">ID</th>
						        <th data-field="name"  data-sortable="true">Consultant Name</th>
						        <th data-field="skill" data-sortable="true">Skill</th>
						        <th data-field="Manager" data-sortable="true">Assigned Manager</th>
						        <th data-field="Action" data-sortable="true">Actions</th>
						    </tr>
						    </thead>
						   <tbody>
<?php
$i=1;
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from assigned";
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();
$conn=null;
foreach( $data as $row) { 
$cid=$row['cid'];
$uid=$row['uid'];
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from users where `uid` = $uid AND `status` = 1";
$ins= $conn->prepare($query);
$ins->execute();
$udata = $ins->fetch();
$query2 = "select * from consultants where `cid` = $cid AND `status` = 1";
$ins2= $conn->prepare($query2);
$ins2->execute();
$cdata = $ins2->fetch();

if($cdata['cid']!== null && $udata['uid']!== null)
{
								$sid = $cdata['skill'];
								$q2 = "select * from skill where `sid` = $sid";
								$ins3= $conn->prepare($q2);
								$ins3->execute(); 
								$dta2 = $ins3->fetch(); 
								$conn=null; 
	?>
    <tr>
  		<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
    	<td data-search="<?php echo $cdata['cfname']; ?>"> <?php echo $cdata['cfname']." ".$cdata['clname']; ?></td>
    	<td data-search="<?php echo $dta2['skillname']; ?>"> <?php echo $dta2['skillname']; ?></td>    	
    	<td data-search="<?php echo $udata['name']; ?>"> <?php echo $udata['name']; ?></td>  
    	<td> 
    		<a href="assigncmd.php?do=change&id=<?php echo $row['id']; ?>"><img src="images/b_edit.png" alt="Change" width="16" height="16" border="0" title="Change" /></a>
    				 &nbsp;&nbsp;&nbsp;
    				<a href ="assigncmd.php?do=delete&id=<?php  echo $row['id']; ?>" onClick="return confirm('Are you sure you want to delete ?')"><img src="images/b_drop.png" alt="Delete" width="16" height="16" border="0" title="Delete"/></a>
    				 &nbsp;&nbsp;&nbsp;    			
    	</td> 
    </tr>
    <?php  } //for if
} //foreach
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
alert('You Need to be Admin/Manager to view this page.');
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
