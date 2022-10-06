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

if($dta['level'] == 1 || $dta['level'] == 2)
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
					<div class="panel-heading"><!-- <a href="admin.php?action=postreq"><button name="addauser" class="btn btn-primary">Post Req</button></a>&nbsp;&nbsp;&nbsp;<a href="admin.php?action=addskill"><button name="addauser" class="btn btn-primary">Add Skill</button></a> --></div>
					<div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uid" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="Datetime"  data-sortable="false">Datetime</th>
						        <th data-field="id" data-sortable="false">S.no</th>								
								<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?> <th data-field="smname" data-sortable="true">SM</th> <?php } ?>
						        <th data-field="Role"  data-sortable="true">Skill</th>	
						        <th data-field="rlocation"  data-sortable="true">Location</th>
						        <th data-field="rfname" data-sortable="true">Business Partner</th>
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
								$q3 = "SELECT * from clients where `cid` = $cid";
								$ins4= $conn->prepare($q3);
								$ins4->execute(); 
								$dta3 = $ins4->fetch();

$uid = $row['uid'];
								$q4 = "SELECT * from users where `uid` = $uid";
								$ins5= $conn->prepare($q4);
								$ins5->execute(); 
								$dta4 = $ins5->fetch();
$app_num = 0;
$rc_num = 0;
$sub_num = 0;
$eci_num = 0;

$reqid = $row['reqid'];

$app_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid")->fetchColumn();
$rc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid and `rcdone` =1")->fetchColumn();
$sub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid and `subdone` =1")->fetchColumn();
$eci_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid and `hasinterview` =1")->fetchColumn();

	?>
    <tr>
		<td data-search="<?php echo $row['datetime']; ?>"> <?php $time = strtotime($row['datetime']); $myFormatForView = date("m/d/y g:i A", $time); echo $myFormatForView; ?></td>
    	<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>  
		<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?> <td data-search="<?php echo $dta4['name']; ?>"><a href="#" onClick="alert('\n\n\n\n<?php echo "Name: ".$dta4['name']; ?>\n<?php echo"Email: ".$dta4['email']; ?>\n')"><?php echo $dta4['name']; ?></a> </td>   <?php } ?>
		<td data-search="<?php echo $dta2['skillname']; ?>"> <a id="various3" href="leads/view.php?id=<?php echo $row['reqid']; ?>"><?php echo $dta2['skillname']; ?></a></td>
    	<td data-search="<?php echo $row['rlocation']; ?>"> <?php echo $row['rlocation']; ?></td>
		<td data-search="<?php echo $dta3['remail']; ?>"> <a href="#" onClick="alert('\n\n\n\n<?php echo "Name: ".$dta3['rname']; ?>\n\n<?php echo"Email: ".$dta3['remail']; ?>\n\n<?php echo"Company Name: ".$dta3['companyname'];?>')"><?php echo $dta3['remail']; ?></a></td> 
    	<td data-search="<?php echo $row['rrate']; ?>"> <?php echo $row['rrate']; ?></td>   
    	<td> App: 
		<?php 
		
		$cd_appquery = "SELECT DISTINCT * FROM `app_data` AS A LEFT JOIN consultants as B on A.consultant_id = B.cid where A.reqid = $reqid";
		$rqp= $conn->prepare($cd_appquery);
		$rqp->execute(); 
		$cappdata = $rqp->fetchAll();
		
		$cd_rcquery = "SELECT DISTINCT * FROM `app_data` AS A LEFT JOIN consultants as B on A.consultant_id = B.cid where A.reqid = $reqid and rcdone = 1";
		$rqp= $conn->prepare($cd_rcquery);
		$rqp->execute(); 
		$crcdata = $rqp->fetchAll();

		$cd_subquery = "SELECT DISTINCT * FROM `app_data` AS A LEFT JOIN consultants as B on A.consultant_id = B.cid where A.reqid = $reqid and subdone = 1";
		$rqp= $conn->prepare($cd_subquery);
		$rqp->execute(); 
		$csubdata = $rqp->fetchAll();

		$cd_eciquery = "SELECT DISTINCT * FROM `app_data` AS A LEFT JOIN consultants as B on A.consultant_id = B.cid where A.reqid = $reqid and hasinterview = 1";
		$rqp= $conn->prepare($cd_eciquery);
		$rqp->execute(); 
		$cecidata = $rqp->fetchAll();
		$c_app_names = "Consultant Application: ";
		
		$c_rc_names = "RC done for : ";
		
		$c_sub_names = "Submitted consultants: ";
		
		$c_eci_names = "Consultant Interview requested: ";
				
		foreach($cappdata as $row)
		{
			$c_app_names = $c_app_names.$row['cfname']." ".$row['clname'].",";
		}
		foreach($crcdata as $row)
		{
			$c_rc_names = $c_rc_names.$row['cfname']." ".$row['clname'].",";
		}
		foreach($csubdata as $row)
		{
			$c_sub_names = $c_sub_names.$row['cfname']." ".$row['clname'].",";
		}
		foreach($cecidata as $row)
		{
			$c_eci_names = $c_eci_names.$row['cfname']." ".$row['clname'].",";
		}

		
		?> 
		<a href="#" onClick="alert('\n\n\n\n<?php echo $c_app_names; ?>')"><?php echo $app_num; ?></a> RC: <a href="#" onClick="alert('\n\n\n\n<?php echo $c_rc_names; ?>')"><?php echo $rc_num; ?></a> Sub: <a href="#" onClick="alert('\n\n\n\n<?php echo $c_sub_names; ?>')"><?php echo $sub_num; ?></a> ECI: <a href="#" onClick="alert('\n\n\n\n<?php echo $c_eci_names; ?>')"><?php echo $eci_num; ?></a></td>
		<td> <a href="comments.php?reqcom_id=<?php echo $reqid; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,'); return false;"><button name="viewcomment" class="btn btn-primary">View Comment</button></a> 
		<a href="addcomment.php?reqcom_id=<?php echo $reqid; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><button name="addcomment" class="btn btn-primary">Add Comment</button></a></td>     		
		<td>  	<?php if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3) {	?>
    		<a href="reqcmd.php?do=edit&id=<?php echo $row['reqid']; ?>"><img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /></a>
    		<?php  } if($dta['level'] == 1 || $dta['level'] == 2) {	?> 		<a href ="reqcmd.php?do=delete&id=<?php echo $row['reqid']; ?>" onClick="return confirm('Are you sure you want to remove this req ?')"><img src="images/b_drop.png" alt="Delete" width="16" height="16" border="0" title="Delete"/></a>
					<?php } ?>	<a href="addapp.php?reqid=<?php echo $reqid; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><button name="addapp" class="btn btn-primary">Add Application</button></a>
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
