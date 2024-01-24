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
$curdate =date('Y-m-d');

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{	
	$olddate = 0;
	if(isset($_POST['date']))
	{
		$cdate = $_POST['date'];
		$olddate = 1;
		$cdate = strtotime($cdate);
		$curdate =date('Y-m-d',$cdate);
	}
	else
	{
		$curdate =date('Y-m-d');
	}
	
    $showweekly=0;
	if(isset($_GET['showweekly']))
	{
		$showweekly=1;
	}

	$showmonthly=0;
	if(isset($_GET['showmonthly']))
	{
		$showmonthly=1;
	}

	$smid=$dta['uid'];

	if(isset($_GET['smid']))
	{
		$smid=$_GET['smid'];
		$query = "select * from req where status =1 and uid = $smid and  DATE(datetime) = DATE('$curdate') order by datetime asc";
		if($showweekly==1)
		{
			$query = "select * from req where status =1 and uid = $smid and  WEEK(datetime) = WEEK('$curdate') and YEAR(datetime) = YEAR('$curdate') and  MONTH(datetime) = MONTH('$curdate') order by datetime asc";
		}

		if($showmonthly==1)
		{
			$query = "select * from req where status =1 and uid = $smid and  MONTH(datetime) = MONTH('$curdate') and YEAR(datetime) = YEAR('$curdate') order by datetime asc";
		}
	
	}
	elseif(isset($_GET['sid']))
	{
		$sid=$_GET['sid'];
		$query = "select * from req where status =1 and skillid = $sid and DATE(datetime) = DATE('$curdate') order by datetime asc";
		if($showweekly==1)
		{
			$query = "select * from req where status =1  and skillid = $sid and WEEK(datetime) = WEEK('$curdate') and  YEAR(datetime) = YEAR('$curdate') order by datetime asc";
		}
		if($showmonthly==1)
		{
			$query = "select * from req where status =1 and skillid = $sid and  MONTH(datetime) = MONTH('$curdate')  and  YEAR(datetime) = YEAR('$curdate') order by datetime asc";
		}
	}
	else
	{
		
		if($dta['level'] == 1 )
		{
			$query = "select * from req where status = 1 and DATE(datetime) = DATE('$curdate') order by datetime desc";
			if($showweekly==1)
			{
				$query = "select * from req where status =1 and WEEK(datetime) = WEEK('$curdate') and  YEAR(datetime) = YEAR('$curdate') order by datetime asc";
			}
			if($showmonthly==1)
			{
				$query = "select * from req where status =1 and  MONTH(datetime) = MONTH('$curdate')  and  YEAR(datetime) = YEAR('$curdate') order by datetime asc";
			}
		}
		if($dta['level'] == 2)
		{
			$query = "select * from req where status = 1 and DATE(datetime) = DATE('$curdate') order by datetime desc";			
			if($showweekly==1)
			{
				$query = "select * from req where status =1 and WEEK(datetime) = WEEK('$curdate') and YEAR(datetime) = YEAR('$curdate') order by datetime asc";
			}
			
			if($showmonthly==1)
			{
				$query = "select * from req where status =1 and MONTH(datetime) = MONTH('$curdate') and YEAR(datetime) = YEAR('$curdate') order by datetime asc";
			}
		}
		if($dta['level'] == 3)
		{
			$query = "select * from req where status = 1 and DATE(datetime) = DATE('$curdate') order by datetime desc";
			if($showweekly==1)
			{
				$query = "select * from req where status =1 and WEEK(datetime) = WEEK('$curdate') and  YEAR(datetime) = YEAR('$curdate') order by datetime asc";
			}
			
			if($showmonthly==1)
			{
				$query = "select * from req where status =1 and  MONTH(datetime) = MONTH('$curdate')  and  YEAR(datetime) = YEAR('$curdate') order by datetime asc";
			}			
		}
	}

//$query = "select * from req where status =1 order by datetime desc";

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
				<div class="panel-heading"> <td width="90%" align="left" valign="top"> <form action="" method="post"><input name="date" id="datepicker"> <button name="submit" class="btn btn-primary">Submit</button></form></td>  </div>
					<div class="panel-heading"> <a target ="_blank" href="admin.php?action=showallreqs&sid=7"><button name="javareks" class="btn btn-primary">Java Reqs</button></a>&nbsp;&nbsp;&nbsp;<a target ="_blank" href="admin.php?action=showallreqs&sid=10"><button name="spreks" class="btn btn-primary">Sailpoint Reqs</button></a>&nbsp;&nbsp;&nbsp;<a target ="_blank" href="admin.php?action=showallreqs&sid=6"><button name="spreks" class="btn btn-primary">Devops Reqs</button></a>&nbsp;&nbsp;&nbsp;&nbsp;<?php if($dta['level'] == 2 || $dta['level'] == 1) { ?><a target ="_blank" href="reqsdownload.php?download=allreqs<?php if($olddate==1) { echo "&date=".$curdate; } ?>"><button name="downloadallreqs" class="btn btn-primary">Download Reqs</button></a> &nbsp;&nbsp;&nbsp;&nbsp;<a target ="_blank" href="reqsdownload.php?download=allreqs&showweekly=1&showunique=1<?php if($olddate==1) { echo "&date=".$curdate; } ?>"><button name="downloadweeklyreqs" class="btn btn-primary">Download Unique Weekly Reqs</button></a>&nbsp;&nbsp;&nbsp;&nbsp;<a target ="_blank" href="reqsdownload.php?download=allreqs&showmonthly=1&showunique=1<?php if($olddate==1) { echo "&date=".$curdate; } ?>"><button name="downloadweeklyreqs" class="btn btn-primary">Download Unique Monthly Reqs</button></a><?php } if($dta['level'] == 1) { ?><a target ="_blank" href="reqcmd.php?do=qallupdate"><button name="updatequalified" class="btn btn-primary">Update Qualified</button></a> <?php } ?>
					<?php if(isset($_GET['sid'])) { ?> &nbsp;&nbsp;&nbsp;<a target ="_blank" href="admin.php?action=showallreqs&sid=<?php echo $sid; ?>&showweekly=1"><button name="reksweekly" class="btn btn-primary">Weekly Reqs</button></a>  &nbsp;&nbsp;&nbsp;<a target ="_blank" href="admin.php?action=showallreqs&sid=<?php echo $sid; ?>&showmonthly=1"><button name="reksmonthlyly" class="btn btn-primary">Monthly Reqs</button></a> <?php } ?>
				
					<?php if($dta['level'] == 3) { ?>
						<a target ="_blank" href="smreqsdownload.php?download=allreqs
						<?php if($olddate==1) { echo "&date=".$curdate; } ?>"><button name="downloadallreqs" class="btn btn-primary">Download Reqs</button></a> &nbsp;&nbsp;&nbsp;&nbsp;<a target ="_blank" href="smreqsdownload.php?download=allreqs&showweekly=1&showunique=1
						<?php if($olddate==1) { echo "&date=".$curdate; } ?>"><button name="downloadweeklyreqs" class="btn btn-primary">Download Unique Weekly Reqs</button></a>&nbsp;&nbsp;&nbsp;&nbsp;<a target ="_blank" href="smreqsdownload.php?download=allreqs&showmonthly=1&showunique=1
						<?php if($olddate==1) { echo "&date=".$curdate; } ?>"><button name="downloadweeklyreqs" class="btn btn-primary">Download Unique Monthly Reqs</button></a><?php } ?>
				
				</div>
					<div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uid" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="Datetime"  data-sortable="true" data-visible="false">Datetime</th>
						        <th data-field="id" data-sortable="false">S.no</th>
								<th data-field="Reqid"  data-sortable="true">Req ID</th>
						        <?php   if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3) {	?> <th data-field="rfname" data-sortable="true">SM</th> <?php } ?>
								<th data-field="Role"  data-sortable="true">Skill</th>	
						        <th data-field="rlocation"  data-sortable="true">Location</th>
								<th data-field="domain" data-sortable="true">Company Domain</th>								
								<?php   if($dta['level'] == 1 || $dta['level'] == 2 ) {	?>  <th data-field="remail" data-sortable="true" data-visible="false">Email</th><?php } ?>
						        <th data-field="Client name" data-sortable="true">End Client</th>
						        <th data-field="rrate"  data-sortable="true" data-visible="false">Rate</th>
						        <th data-field="reqstatus"  data-sortable="true">Status</th>			
						        <?php   if($dta['level'] == 1) { ?> <th data-field="qualified"  data-sortable="true">qualified</th> <?php } ?>
						        <th data-field="ServiceStatus"  data-sortable="true">Service Status</th>
								<?php   if($dta['level'] == 1 || $dta['level'] == 2 ) {	?> 				        <th data-field="Comment"  data-sortable="true" data-visible="true">Comment</th> 
						         <?php } ?>
								<?php   if($dta['level'] == 1 || $dta['level'] == 2 ) {	?> 				    
						        <th data-field="action" data-sortable="true" data-visible="false">Edit Action</th> <?php } ?>
								<?php   if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3) {	?> 				    
						        <th data-field="reqaction" data-sortable="true">Req Action</th> <?php } ?>
						    </tr>
						    </thead>
						   <tbody>
<?php
$i=1;
function checkmail($email) {
    $find1 = strpos($email, '@');
    $find2 = strpos($email, '.');
    return ($find1 !== false && $find2 !== false);
 } 
foreach( $data as $row) { 

$reqid = $row['reqid'];


	$conn = null;
	$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );

	$udate = $conn->query("select datetime from req where reqid = $reqid")->fetchColumn();
	$utime = strtotime($udate);
	$cur_date = date("dmy", $utime); 
	$curweek = date("W", $utime); 
	$ureq_id = "W".$curweek.$cur_date."-".$row['ureq_id'];


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



$app_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid and `status`= 1")->fetchColumn();
$rc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid and `rcdone` =1")->fetchColumn();
$sub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid and `subdone` =1")->fetchColumn();
$eci_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid and `hasinterview` =1")->fetchColumn();

	?>
    <tr>
		<td data-search="<?php echo $row['datetime']; ?>"> <?php $time = strtotime($row['datetime']); $myFormatForView = date("m/d/y g:i A", $time); echo $myFormatForView; ?></td>
    	<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
		<td> <?php echo $ureq_id; ?><a target="_blank" href="reqnoedit.php?do=editreqid&rid=<?php echo $row['reqid']; ?>"><img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /></a></td>
    	<?php   if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3) {	?>	<td data-search="<?php echo $dta4['name']; ?>"><a href="admin.php?action=showallreqs&smid=<?php echo $dta4['uid']; ?>" target="_blank"><?php echo $dta4['name']; ?></a> </td>   <?php } ?>
		<td data-search="<?php echo $dta2['skillname']; ?>"> <a id="various3" target="_blank" href="le
		ads/view.php?id=<?php echo $row['reqid']; ?>"><?php echo $dta2['skillname']; ?></a></td>
    	<td data-search="<?php echo $row['rlocation']; ?>"> <?php echo $row['rlocation']; ?></td>

		
    	<?php
		
		$email = $dta3['remail'];
		
		$domain = substr($email, strpos($email, '@') + 1);
		
		$clientname = $conn->query("select rend_client from req where reqid = $reqid")->fetchColumn();

		?>

		<td data-search="<?php echo $domain; ?>"> <?php echo $domain; ?></a></td>
		<?php
		if($dta['level'] == 1 || $dta['level'] == 2) {
			?>
		<td data-search="<?php echo $email; ?>"> <?php echo $email; ?></a></td>
			<?php
		}
		 	?>	
		<td data-search="<?php echo $clientname; ?>"> <?php echo $clientname; ?></a></td> 
		<td data-search="<?php echo $row['rrate']; ?>"> <?php echo $row['rrate']; ?></td>
		
		<?php
		if($row['reqstatus']==1)
		{
			$reqcolour = "red";
			$reqstatus = "Rejected";
		}
		else if($row['reqstatus']==2)
		{
			$reqcolour = "orange";
			$reqstatus = "Closed";
		}
		else if($row['reqstatus']==3)
		{
			$reqcolour = "blue";
			$reqstatus = "Not Connected";
		}
		else if($row['reqstatus']==4)
		{
			$reqcolour = "green";
			$reqstatus = "Open";
		}
		else if($row['reqstatus']==5)
		{
			$reqcolour = "green";
			$reqstatus = "In process";
		}

?>
		<td data-search="<?php echo $reqstatus; ?>"> <b><h4><font color="<?php echo $reqcolour; ?>"><?php echo $reqstatus; ?></font></h4></b></td> 
		<?php   if($dta['level'] == 1) { ?>	<td data-search="<?php if($row['qualified']==1) { echo "Qualified"; } else { echo "Not Qualified"; } ?>"> <a href="reqcmd.php?do=<?php if($row['qualified']==1) { echo "nqupdate"; } else { echo "qupdate"; } ?>&id=<?php echo $row['reqid']; ?>" target="_blank"><?php if($row['qualified']==1) { echo "Qualified"; } else { echo "Not Qualified"; } ?></a></td> <?php } ?>

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
		<a href="#" onClick="alert('\n\n\n\n<?php echo $c_app_names; ?>'); return false;"><?php echo $app_num; ?></a> RC: <a href="#" onClick="alert('\n\n\n\n<?php echo $c_rc_names; ?>'); return false;"><?php echo $rc_num; ?></a> Sub: <a href="#" onClick="alert('\n\n\n\n<?php echo $c_sub_names; ?>'); return false;"><?php echo $sub_num; ?></a> ECI: <a href="#" onClick="alert('\n\n\n\n<?php echo $c_eci_names; ?>'); return false;"><?php echo $eci_num; ?></a></td>
		<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?> 	<td> <a href="comments.php?reqcom_id=<?php echo $reqid; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,'); return false;"><button name="viewcomment" class="btn btn-primary">View Comment</button></a> 
		<a href="addcomment.php?reqcom_id=<?php echo $reqid; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><img src="images/add.png" alt="add comment" width="16" height="16" border="0" title="add" /></a></td>     		<?php } ?>
		 <?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?> 	<td>
    		<a href="reqcmd.php?do=edit&id=<?php echo $row['reqid']; ?>"><img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /></a>
    				<a href ="reqcmd.php?do=delete&id=<?php echo $row['reqid']; ?>" onClick="return confirm('Are you sure you want to remove this req ?')"><img src="images/b_drop.png" alt="Delete" width="16" height="16" border="0" title="Delete"/></a> <?php } ?>
					<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?>					<a href="addapp.php?reqid=<?php echo $reqid; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><button name="addapp" class="btn btn-primary">Add Application</button></a></td> <?php } ?>
   
	
	 <?php   if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3) {	?>		<td>			
		<a href="addreqstatus.php?reqid=<?php echo $reqid; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><button name="addreqstatus" class="btn btn-primary">Add Req status</button></a> 
		</td><?php } ?>
 
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
$conn = null;
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>
