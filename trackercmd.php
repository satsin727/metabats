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
$selected = "showreports";
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{

$uid = $sessid;

if(isset($_GET['appcd_id']))
			{
                $appcd_id = $_GET['appcd_id'];
            }
	if($dta['level'] == 1 || $dta['level'] == 2)
	{
		
        if(isset($_GET['appcd_id']))
			{
                $consultantid = $_GET['appcd_id'];
                $app = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `status` = 1 and DATE(appdate) = CURDATE()";
            }
        if(isset($_GET['rccd_id']))
			{
                $consultantid = $_GET['rccd_id'];
                $rc = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `rcdone` = 1 and `status`= 1 and DATE(rcdate) = CURDATE()";
            }
            
        if(isset($_GET['subcd_id']))
            {
                $consultantid = $_GET['subcd_id'];                
                $sub = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `subdone` = 1 and `status`= 1 and DATE(subdate) = CURDATE()";
            }
        
        if(isset($_GET['ecicd_id']))
			{
                $consultantid = $_GET['ecicd_id'];
                $eci = 1;
                $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1 and DATE(eci_date) = CURDATE()";
            }
        
        if(isset($_GET['appcw_id']))
			{
                $consultantid = $_GET['appcw_id'];
                $app = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `status` = 1  and WEEK(appdate) = WEEK(CURDATE())";
            }
        
        if(isset($_GET['rccw_id']))
			{
                $consultantid = $_GET['rccw_id'];
                $rc = 1;                
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK(CURDATE())";
            }
            
        if(isset($_GET['subcw_id']))
			{
                $consultantid = $_GET['subcw_id'];
                $sub = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `subdone` = 1 and `status`= 1 and WEEK(subdate) = WEEK(CURDATE())";
            }
        
        if(isset($_GET['ecicw_id']))
			{
                $consultantid = $_GET['ecicw_id'];                
                $eci = 1;
                $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK(CURDATE())";
            }
    }
	else
	{
            if(isset($_GET['appcd_id']))
                {
                    $consultantid = $_GET['appcd_id'];
                    $app = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultantid`= $consultantid and `status` = 1 and `uid` = $uid and DATE(appdate) = CURDATE()";
                }
            if(isset($_GET['rccd_id']))
                {
                    $consultantid = $_GET['rccd_id'];
                    $rc = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `rcdone` = 1 and `uid` = $uid and `status`= 1 and DATE(rcdate) = CURDATE()";
                }
                
            if(isset($_GET['subcd_id']))
                {
                    $consultantid = $_GET['subcd_id'];
                    $sub = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `subdone` = 1 and `uid` = $uid and `status`= 1 and DATE(subdate) = CURDATE()";
                }
            
            if(isset($_GET['ecicd_id']))
                {
                    $consultantid = $_GET['ecicd_id'];
                    $eci = 1;
                    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `sm_id` = $uid and `status` = 1 and DATE(eci_date) = CURDATE()";
                }
            
            if(isset($_GET['appcw_id']))
                {
                    $consultantid = $_GET['appcw_id'];
                    $app = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `uid` = $uid and `status` = 1  and WEEK(appdate) = WEEK(CURDATE())";
                }
            
            if(isset($_GET['rccw_id']))
                {
                    $consultantid = $_GET['rccw_id'];
                    $rc = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `rcdone` = 1 and `uid` = $uid and `status`= 1 and WEEK(rcdate) = WEEK(CURDATE())";
                }
                
            if(isset($_GET['subcw_id']))
                {
                    $consultantid = $_GET['subcw_id'];
                    $sub = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `subdone` = 1 and `uid` = $uid and `status`= 1 and WEEK(subdate) = WEEK(CURDATE())";
                }
            
            if(isset($_GET['ecicw_id']))
                {
                    $consultantid = $_GET['ecicw_id'];
                    $eci = 1;
                    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `sm_id` = $uid and `status` = 1 and WEEK(eci_date) = WEEK(CURDATE())";
                }
    
    }
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();

$skillid = $conn->query("select skill from consultants where cid = $consultantid")->fetchColumn();
$skill = $conn->query("SELECT skillname FROM `skill` WHERE `sid`= $skillid")->fetchColumn();
$cfname = $conn->query("select cfname from consultants where cid = $consultantid")->fetchColumn();
$clname = $conn->query("select clname from consultants where cid = $consultantid")->fetchColumn();
/*
$reqid = $conn->query("select reqid from app_data where app_id = $eciid")->fetchColumn(); */
?>

<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active"><h3><?php
                if($app==1) { echo "Application Details"; }
                elseif($rc==1) { echo "Rate Confirmation Details"; }
                elseif($sub==1) { echo "Submissions Details"; }
                elseif($eci==1) { echo "Interview Details"; }
                ?> - <?php echo $cfname." ".$clname." (".$skill." Consultant)"; ?>
                </h3></li>
			</ol>
</div>
<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-heading"> <a href="admin.php?action=showdailydata"><button name="back" class="btn btn-primary">Back</button></a></div>
					<div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uid" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="id" data-sortable="false">S.no</th>                                
						        <th data-field="Datetime"  data-sortable="true">Datetime</th>
								<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?>   <th data-field="name" data-sortable="true">SM</th> <?php }	?> 
						        <th data-field="Role"  data-sortable="true">Role</th>							
						        <th data-field="Client"  data-sortable="true">IP/CLient</th>	
                                <th data-field="remail" data-sortable="true">Recruiter Email</th>
								<th data-field="Status"  data-sortable="true">RC Status</th>
                                <th data-field="Status"  data-sortable="true">Status</th>
						        <th data-field="comment"  data-sortable="true">Comment</th>
						    </tr>
						    </thead>
						   <tbody>
<?php
$i=1;
foreach( $data as $row) { 

$app_id = $row['app_id'];
$reqid = $row['reqid'];


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

								$q5 = "SELECT * from req where `reqid` = $reqid";
								$ins6= $conn->prepare($q5);
								$ins6->execute(); 
								$dta5 = $ins6->fetch();

$ipname = $conn->query("select t1ip_name from app_data where app_id = $app_id")->fetchColumn();
$clientname = $conn->query("select rend_client from req where reqid = $reqid")->fetchColumn();
	?>
    <tr>
		
    	<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
        <td data-search="<?php 
        
        if($app==1) { $date = $row['appdate']; }
        elseif($rc==1) { $date = $row['rcdate'];  }
        elseif($sub==1) { $date = $row['subdate'];  }
        elseif($eci==1) { $date = $row['eci_date'];  }
        
        
        echo $date; ?>"> <?php $time = strtotime($date); $myFormatForView = date("m/d/y g:i A", $time); echo $myFormatForView; ?></td>
		<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?>	<td data-search="<?php echo $dta4['name']; ?>"><a href="#" onClick="alert('\n\n\n\n<?php echo "Name: ".$dta4['name']; ?>\n<?php echo"Email: ".$dta4['email']; ?>\n')"><?php echo $dta4['name']; ?></a> </td>   <?php } ?>
        <td data-search="<?php echo $skill." ".$dta5['rlocation']; ?>"> <?php echo $skill." - ".$dta5['rlocation']." - ".$dta5['rduration']; ?></td>
		<td data-search="<?php echo $ipname."/".$clientname; ?>"> <?php if(empty($ipname)) { echo $clientname; } else { echo $ipname."/".$clientname; } ?></td>
		
        
        <td data-search="<?php echo $dta3['remail']; ?>"> <a href="#" onClick="alert('\n\n\n\n<?php echo "Name: ".$dta3['rname']; ?>\n\n<?php echo"Email: ".$dta3['remail']; ?>\n\n<?php echo"Company Name: ".$dta3['companyname'];?>')"><?php echo $dta3['remail']; ?></a></td> 

		<td> 
		<?php
		$ars_status = $conn->query("SELECT `ars_status` FROM `app_data` WHERE `app_id` = $app_id")->fetchColumn();
		/*
		    <option value="1">Connected</option>
    <option value="2">Not Connected</option>
    <option value="3">Voicemail</option>
    <option value="4">No Response</option>
    <option value="5">Cancelled = Pulled Back</option>
    <option value="6">Rejected</option>
    <option value="7">In-Process</option>
    <option value="8">Got Test</option>
    <option value="9">Got Screening</option>
    <option value="10">Submitted to End Client</option>
	*/
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

        if($row['rcdone']==1) { $rc="Yes";} else { $rc="No";}
		?>
        <td data-search="<?php echo $rc; ?>"> <?php echo $rc; ?></td>
		<td> <a href="comments.php?appcom_id=<?php echo $app_id; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,'); return false;"><button name="viewcomment" class="btn btn-primary">View Comment</button></a> </td> 
    <?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?> <td> 	
  <!--  		<a href="appdata_cmd.php?do=edit&id=<?php echo $row['app_id']; ?>"><img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /></a> -->
    				<a href ="appdata_cmd.php?do=delete&appid=<?php echo $row['app_id']; ?>" onClick="return confirm('Are you sure you want to remove this application ?')"><img src="images/b_drop.png" alt="Delete" width="16" height="16" border="0" title="Delete"/></a>
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
