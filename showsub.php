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
	$smid=$dta['uid'];

	if(isset($_GET['smid']))
	{
		$smid=$_GET['smid'];
		$query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status =1 and uid = $smid and subdone= 1  and TO_DAYS(curdate()) - TO_DAYS(A.subdate) <= 30 order by subdate desc";
	}
	else
	{
		
		if($dta['level'] == 1)
		{
			$query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status =1 and subdone= 1  and TO_DAYS(curdate()) - TO_DAYS(A.subdate) <= 30 order by subdate desc";
		}
		if($dta['level'] == 2)
		{
			$query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid  Inner Join users as C ON A.uid = C.uid where A.status =1 and subdone= 1  and C.rmid = $smid and TO_DAYS(curdate()) - TO_DAYS(A.subdate) <= 30 order by subdate desc";
		}
		if($dta['level'] == 3)
		{
			$query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status =1 and uid = $smid and subdone= 1  and TO_DAYS(curdate()) - TO_DAYS(A.subdate) <= 30 order by subdate desc";
		}
	}

$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();
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
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uid" data-sort-order="asc">
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
<?php
$i=1;
foreach( $data as $row) { 

$sid = $row['skill'];
								$q2 = "select * from skill where `sid` = $sid";
								$ins3= $conn->prepare($q2);
								$ins3->execute(); 
								$dta2 = $ins3->fetch();
$cid = $row['client_id'];
								$q3 = "SELECT * from clients where `cid` = $cid";
								$ins4= $conn->prepare($q3);
								$ins4->execute(); 
								$dta3 = $ins4->fetch();

$uid = $row['uid'];	
								$q4 = "SELECT * from users where `uid` = $uid";
								$ins5= $conn->prepare($q4);
								$ins5->execute(); 
								$dta4 = $ins5->fetch();

/*
$app_num = 0;
$rc_num = 0;
$sub_num = 0;
$eci_num = 0;
*/
$app_id = $row['app_id'];
$reqid = $row['reqid'];
								$q5 = "SELECT * from req where `reqid` = $reqid";
								$ins6= $conn->prepare($q5);
								$ins6->execute(); 
								$dta5 = $ins6->fetch();
/*	
$app_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid")->fetchColumn();
$rc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid and `rcdone` =1")->fetchColumn();
$sub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid and `subdone` =1")->fetchColumn();
$eci_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid and `hasinterview` =1")->fetchColumn();
*/

	?>
    <tr>
		<td data-search="<?php echo $row['subdate']; ?>"> <?php $time = strtotime($row['subdate']); $myFormatForView = date("m/d/y g:i A", $time); echo $myFormatForView; ?></td>
    	<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
		<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?>	<td data-search="<?php echo $dta4['name']; ?>"><a href="admin.php?action=showsub&smid=<?php echo $dta4['uid']; ?>" target="_blank"><?php echo $dta4['name']; ?></a> </td>   <?php } ?>
		<td data-search="<?php echo $dta2['skillname']; ?>"> <a id="various3" target="_blank" href="leads/view.php?id=<?php echo $row['reqid']; ?>"><?php echo $dta2['skillname']; ?></a></td>
    	<td data-search="<?php echo $dta5['rlocation']; ?>"> <?php echo $dta5['rlocation']; ?></td>
		<td data-search="<?php echo $dta3['remail']; ?>"> <a href="#" onClick="alert('\n\n\n\n<?php echo "Name: ".$dta3['rname']; ?>\n\n<?php echo"Email: ".$dta3['remail']; ?>\n\n<?php echo"Company Name: ".$dta3['companyname'];?>')"><?php echo $dta3['remail']; ?></a></td> 
		<td data-search="<?php $row['cfname']." ".$row['clname']; ?>"> <?php echo $row['cfname']." ".$row['clname']; ?></td>
        <td data-search="<?php echo $row['rateperhour']; ?>"> <?php echo $row['rateperhour']; ?></td>
		<td data-search="<?php echo $row['t1ip_name']."/".$dta5["rend_client"]; ?>"> <?php echo $row['t1ip_name']."/".$dta5["rend_client"]; ?></td>
		
		<td> 
		<?php
		$status = $conn->query("SELECT `subto` FROM `app_data` WHERE `app_id` = $app_id")->fetchColumn();
		if($status == 1)
		{
			echo "Submitted to T2";
		}
		elseif($status == 2)
		{
			echo "Submitted to T1";
		}
		elseif($status == 3)
		{
			echo "Submitted to IP";
		}
		elseif($status == 4)
		{
			echo "Submitted to End Client";
		}
		elseif($status == 5)
		{
			echo "In-Process";
		}

		?>
		<a href="addsubstatus.php?subid=<?php echo $app_id; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><button name="addstatus" class="btn btn-primary">Update Status</button></a></td> 
		 
		<td> 
		<?php
		$ars_status = $conn->query("SELECT `ars_status` FROM `app_data` WHERE `app_id` = $app_id")->fetchColumn();

		if($ars_status == 1)
		{
			echo "Connected&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		elseif($ars_status == 2)
		{
			echo "Not Connected";
		}
		elseif($ars_status == 3)
		{
			echo "Voicemail&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		elseif($ars_status == 4)
		{
			echo "No Response";
		}
		elseif($ars_status == 5)
		{
			echo "Cancelled&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		elseif($ars_status == 6)
		{
			echo "Rejected&nbsp;&nbsp;&nbsp;";
		}
		elseif($ars_status == 7)
		{
			echo "In-Process&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		elseif($ars_status == 8)
		{
			echo "Got Test&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		elseif($ars_status == 9)
		{
			echo "Got Screening";
		}
		elseif($ars_status == 10)
		{
			echo "Submitted to End Client";
		}

		?>
		<a href="addstatus.php?subid=<?php echo $app_id; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><button name="addstatus" class="btn btn-primary">Update Status</button></a></td> 

		<td> <a href="addeci.php?subid=<?php echo $app_id; ?>"><button name="addsub" class="btn btn-primary">Add ECI</button></a></td> 
		<td> <a href="comments.php?subcom_id=<?php echo $app_id; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,'); return false;"><button name="viewcomment" class="btn btn-primary">View Comment</button></a> 
		<a href="addcomment.php?subcom_id=<?php echo $app_id; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><button name="addcomment" class="btn btn-primary">Add Comment</button></a></td> 
    <?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?> <td> 	
  <!--  		<a href="appdata_cmd.php?do=edit&id=<?php echo $row['app_id']; ?>"><img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /></a> -->
    				<a href ="appdata_cmd.php?do=delete&subid=<?php echo $row['app_id']; ?>" onClick="return confirm('Are you sure you want to remove this submission ?')"><img src="images/b_drop.png" alt="Delete" width="16" height="16" border="0" title="Delete"/></a>
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
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>
