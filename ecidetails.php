<?php
require( "config.php" );
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
$selected = "showeci";
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{

$uid = $dta['uid'];
$eciid = $_GET['eciid'];
	if($dta['level'] == 1 || $dta['level'] == 2)
	{
		$query = "select * from eci AS A LEFT JOIN app_data AS B ON A.app_id = B.app_id where A.app_id = $eciid order by A.datetime desc";
	}
	else
	{
		$query = "select * from eci AS A LEFT JOIN app_data AS B ON A.app_id = B.app_id where A.app_id = $eciid and B.uid = $uid order by A.datetime desc";
	}
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();

$consultantid = $conn->query("select consultant_id from app_data where app_id = $eciid")->fetchColumn();
$skillid = $conn->query("select skill from consultants where cid = $consultantid")->fetchColumn();
$skill = $conn->query("SELECT skillname FROM `skill` WHERE `sid`= $skillid")->fetchColumn();
$cfname = $conn->query("select cfname from consultants where cid = $consultantid")->fetchColumn();
$clname = $conn->query("select clname from consultants where cid = $consultantid")->fetchColumn();
$ipname = $conn->query("select t1ip_name from app_data where app_id = $eciid")->fetchColumn();
$reqid = $conn->query("select reqid from app_data where app_id = $eciid")->fetchColumn();
$clientname = $conn->query("select rend_client from req where reqid = $reqid")->fetchColumn();
?>

<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active"><h3>Interview Details - <?php echo $cfname." ".$clname." (".$skill." Consultant)"." with Client - ".$ipname."/".$clientname; ?></h3></li>
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
                                <th data-field="Datetime"  data-sortable="true">Date</th>
						        <th data-field="Round" data-sortable="false">Round</th>
						        <th data-field="Type"  data-sortable="true">Round Type</th>
								<th data-field="Day"  data-sortable="true">ECI Day</th>
								<th data-field="ECI Date"  data-sortable="true">ECI Date</th>
								<th data-field="ECI Time"  data-sortable="true">ECI Time</th>						
						        <th data-field="Mode of Interview" data-sortable="true">MOI</th>
						        <th data-field="Feedback"  data-sortable="true">Feedback</th>
						        <th data-field="Happened"  data-sortable="true">Happened</th>
<?php if($dta['level'] == 1 || $dta['level'] == 2) { ?>     
                                <th data-field="client"  data-sortable="true">US Team feedback</th> 
						        <th data-field="comment"  data-sortable="true">Not Happened Reason</th>
                                <?php } ?>
						    </tr>
						    </thead>
						   <tbody>
<?php
$i=1;
foreach( $data as $row) { 

$app_id = $row['app_id'];
$reqid = $row['reqid'];

	?>
    <tr>
		<td> <?php $time = strtotime($row['eci_req_date']); $myFormatForView = date("m/d/y", $time); echo $myFormatForView; ?></td>
    	<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
		<td> <?php
        $roundtype = $row['eci_round'];
        if($roundtype == 1) { echo "Screening"; } elseif($roundtype == 2) { echo "Test"; } elseif($roundtype == 3) { echo "Interview"; }
        ?></td>
		<td> <?php $time = strtotime($row['eci_date']); $myFormatForView = date("w", $time); if($myFormatForView==0) { echo "Sunday"; } elseif($myFormatForView==1) { echo "Monday"; } elseif($myFormatForView==2) { echo "Tuesday"; } elseif($myFormatForView==3) { echo "Wednesday"; } elseif($myFormatForView==4) { echo "Thursday"; } elseif($myFormatForView==5) { echo "Friday"; } elseif($myFormatForView==6) { echo "Saturday"; }?></td>
    	<td> <?php $time = strtotime($row['eci_date']); $myFormatForView = date("m/d/y", $time); echo $myFormatForView; ?></td>
    	<td> <?php echo $row['eci_time']; ?></td>
		<td data-search="<?php echo $row['eci_type']; ?>"> <?php echo $row['eci_type']; ?></td>
        <td> 
		<?php
        if($row['eci_client_feedback']!=null)
        {
         echo $row['eci_client_feedback'];
        }
		?>
		<a href="addecifeedback.php?ecicfid=<?php echo $row['eci_id']; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><button name="addecifeedback" class="btn btn-primary">Update Feedback</button></a></td> 
		<td> <?php
        if($row['eci_date']>date('Y-m-d H:i:s') )
        {
            echo "Yet to happen";
        }
        else
        {
            ?> <a href="ecicmd.php?do=changestatus&eciid=<?php echo $row['eci_id']; ?>" onClick="return confirm('This will changes status of ECI Happened. Are you sure to proceed?')"><?php if($row['eci_happened']==1) { echo "Yes"; } else echo "No"; ?></a>
    				 &nbsp;&nbsp;&nbsp; <?php 
        }
        ?></td>
		<?php if($dta['level'] == 1 || $dta['level'] == 2) { ?> 
        <td> 
		<?php
        if($row['eci_usteam_feedback']!=null)
        {
         echo $row['eci_usteam_feedback'];
        }
		?>
		<a href="addecifeedback.php?eciufid=<?php echo $row['eci_id']; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><button name="addecifeedback" class="btn btn-primary">Update Feedback</button></a></td> 
        <td> 
		<?php
        if($row['nh_reason']!="0")
        {
         if($row['nh_reason']==1) { echo "Client no show"; } elseif($row['nh_reason']==2) { echo "Tech issues by Client"; }  elseif($row['nh_reason']==3) { echo "Tech issues by Consultant"; } elseif($row['nh_reason']==4) { echo "Cancelled by consultant"; } elseif($row['nh_reason']==5) { echo "Cancelled by Client"; } elseif($row['nh_reason']==6) { echo "Reason not specified"; }
        }
		?>
		<a href="addecifeedback.php?ecinhid=<?php echo $row['eci_id']; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><button name="addecifeedback" class="btn btn-primary">Add Reason</button></a></td> 
		<?php } ?>
	<!--	<td data-search="<?php echo $row['eci_type']; ?>"> <?php echo $row['eci_type']; ?></td>
		<td data-search="<?php echo $row['eci_type']; ?>"> <?php echo $row['eci_type']; ?></td>
		
		<td data-search="<?php echo $dta3['remail']; ?>"> <a href="#" onClick="alert('\n\n\n\n<?php echo "Name: ".$dta3['rname']; ?>\n\n<?php echo"Email: ".$dta3['remail']; ?>\n\n<?php echo"Company Name: ".$dta3['companyname'];?>')"><?php echo $dta3['remail']; ?></a></td> 
		<td data-search="<?php $row['cfname']." ".$row['clname']; ?>"> <?php echo $row['cfname']." ".$row['clname']; ?></td>
        <td data-search="<?php echo $row['rateperhour']; ?>"> <?php echo $row['rateperhour']; ?></td>
		<td data-search="<?php echo $row['t1ip_name']."/".$dta5["rend_client"]; ?>"> <?php echo $row['t1ip_name']."/".$dta5["rend_client"]; ?></td>
		<td> <?php echo $eci_num; ?>&nbsp;&nbsp;&nbsp;<a href="ecidetails.php?eciid=<?php echo $app_id; ?>"><button name="ecidetails" class="btn btn-primary">Show ECI Details</button></a></td>
		<td> <a href="comments.php?ecicom_id=<?php echo $app_id; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,'); return false;"><button name="viewcomment" class="btn btn-primary">View Comment</button></a> 
		<a href="addcomment.php?ecicom_id=<?php echo $app_id; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,addressbar=no'); return false;"><button name="addcomment" class="btn btn-primary">Add Comment</button></a></td> 
    <?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?> <td> 	
  <!--  		<a href="appdata_cmd.php?do=edit&id=<?php echo $row['app_id']; ?>"><img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /></a>
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
