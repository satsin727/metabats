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
		$query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status =1 and hasinterview = 1 order by rcdate desc";
	}
	else
	{
		$query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status =1 and uid = $uid and hasinterview = 1 order by rcdate desc";
	}
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();
?>

<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Interviews</li>
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
						        <th data-field="Datetime"  data-sortable="true">Sub Date</th>
						        <th data-field="id" data-sortable="false">S.no</th>
						        <th data-field="cname"  data-sortable="true">Consultant</th>
								<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?>   <th data-field="name" data-sortable="true">SM</th> <?php }	?> 
						        <th data-field="Role"  data-sortable="true">Role</th>								
						        <th data-field="remail" data-sortable="true">Recruiter Email</th>
						        <th data-field="rate"  data-sortable="true">Rate</th>
						        <th data-field="client"  data-sortable="true">Client</th>
						        <th data-field="ECI"  data-sortable="true">ECI's Numbers</th> 
						        <th data-field="comment"  data-sortable="true">Comment</th>
								<!-- <?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?>   <th data-field="name" data-sortable="true">Add Client</th> <?php }	?> 
-->
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


$eci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `app_id`= $app_id and `eci_happened` = 1")->fetchColumn();

  /*	                              
								$q6 = "SELECT * from eci where `app_id` = $app_id";
								$ins7= $conn->prepare($q6);
								$ins7->execute(); 
								$ecidata = $ins7->fetch();

$app_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid")->fetchColumn();
$rc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid and `rcdone` =1")->fetchColumn();
$sub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid and `subdone` =1")->fetchColumn();
$eci_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= $reqid and `hasinterview` =1")->fetchColumn();
*/

	?>
    <tr>
		<td data-search="<?php echo $row['subdate']; ?>"> <?php $time = strtotime($row['subdate']); $myFormatForView = date("m/d/y g:i A", $time); echo $myFormatForView; ?></td>
    	<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
		<td data-search="<?php $row['cfname']." ".$row['clname']; ?>"> <?php echo $row['cfname']." ".$row['clname']; ?></td>
		<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?>	<td data-search="<?php echo $dta4['name']; ?>"><a href="#" onClick="alert('\n\n\n\n<?php echo "Name: ".$dta4['name']; ?>\n<?php echo"Email: ".$dta4['email']; ?>\n')"><?php echo $dta4['name']; ?></a> </td>   <?php } ?>
		<td data-search="<?php echo $dta2['skillname']; ?>"> <a id="various3" href="leads/view.php?id=<?php echo $row['reqid']; ?>"><?php echo $dta2['skillname']." - ".$dta5['rlocation']; ?></a></td>
    	
		<td data-search="<?php echo $dta3['remail']; ?>"> <a href="#" onClick="alert('\n\n\n\n<?php echo "Name: ".$dta3['rname']; ?>\n\n<?php echo"Email: ".$dta3['remail']; ?>\n\n<?php echo"Company Name: ".$dta3['companyname'];?>')"><?php echo $dta3['remail']; ?></a></td> 
		 <td data-search="<?php echo $row['rateperhour']; ?>"> <?php echo $row['rateperhour']; ?></td>
		<td data-search="<?php echo $row['t1ip_name']."/".$dta5["rend_client"]; ?>"> <?php echo $row['t1ip_name']."/".$dta5["rend_client"]; ?></td>
		<td> <?php echo $eci_num; ?>&nbsp;&nbsp;&nbsp;<a href="ecidetails.php?eciid=<?php echo $app_id; ?>"><button name="ecidetails" class="btn btn-primary">Show ECI Details</button></a></td>
		<td> <a href="comments.php?ecicom_id=<?php echo $app_id; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,'); return false;"><button name="viewcomment" class="btn btn-primary">View Comment</button></a> 
		<a href="addcomment.php?ecicom_id=<?php echo $app_id; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><button name="addcomment" class="btn btn-primary">Add Comment</button></a></td> 
	<!--  
		<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?> <td> 	
  		<a href="appdata_cmd.php?do=edit&id=<?php echo $row['app_id']; ?>"><img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /></a>
    				<a href ="appdata_cmd.php?do=delete&subid=<?php echo $row['app_id']; ?>" onClick="return confirm('Are you sure you want to remove this submission ?')"><img src="images/b_drop.png" alt="Delete" width="16" height="16" border="0" title="Delete"/></a> 
    	</td><?php } ?> -->
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
