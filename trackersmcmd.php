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

$conn=null;
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from users where `uid` = :u";
$ins= $conn->prepare($query);
$ins->bindValue( ":u", $sessid, PDO::PARAM_STR );
$ins->execute();
$dta = $ins->fetch();
$curdate =date('Y-m-d');

$olddate=0;
if(isset($_POST['date']))
{
    $cdate = $_POST['date'];
    $olddate = 1;
    $cdate = strtotime($cdate);
    $curdate =date('Y-m-d',$cdate);
}
else if(isset($_GET['date']))
{
	$olddate=1;
    $curdate = $_GET['date'];
}
else {
    $curdate =date('Y-m-d');
}

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
$selected = "showreports";
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{

$uid = $sessid;

if(isset($_GET['appsd_id']))
			{
                $appsd_id = $_GET['appsd_id'];
            }
           
            $app = 0;
            $rc = 0;
            $sub = 0;
            $eci = 0;            
            $daily = 0;        
            $weekly = 0;
            $monthly = 0;
            $yearly = 0;
            $download = "dapp";
	if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
	{
        $sm_id = $_GET['appsd_id'];
        if($sm_id == $uid || $dta['level'] == 1 || $dta['level'] == 2)
        {
		
        if(isset($_GET['appsd_id']))
			{
                $sm_id = $_GET['appsd_id'];
                $app = 1;
                $daily = 1;                
                $download = "dapp";
                $query = "SELECT * FROM `app_data` WHERE `uid`= $sm_id and `status` = 1 and DATE(appdate) = DATE('$curdate')";
            }
        if(isset($_GET['rcsd_id']))
			{
                $sm_id = $_GET['rcsd_id'];
                $rc = 1;
                $daily = 1;
                $download = "drc";
                $query = "SELECT * FROM `app_data` WHERE `uid`= $sm_id and `rcdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate')";
            }
            
        if(isset($_GET['subsd_id']))
            {
                $sm_id = $_GET['subsd_id'];                
                $sub = 1;
                $daily = 1;
                $download = "dsub";
                $query = "SELECT * FROM `app_data` WHERE `uid`= $sm_id and `subdone` = 1 and `status`= 1 and DATE(subdate) = DATE('$curdate')";
            }
        
        if(isset($_GET['ecisd_id']))
			{
                $sm_id = $_GET['ecisd_id'];
                $eci = 1;
                $daily = 1;
                $download = "deci";
                $query = "SELECT * FROM `eci` WHERE `sm_id`= $sm_id and `eci_happened` =1 and `status` = 1 and DATE(eci_date) = DATE('$curdate')";
            }
        
        if(isset($_GET['appsw_id']))
			{
                $sm_id = $_GET['appsw_id'];
                $app = 1;
                $weekly = 1;
                $download = "wapp";
                $query = "SELECT * FROM `app_data` WHERE `uid`= $sm_id and `status` = 1  and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate')";
            }
        
        if(isset($_GET['rcsw_id']))
			{
                $sm_id = $_GET['rcsw_id'];
                $rc = 1;   
                $weekly = 1;  
                $download = "wrc";           
                $query = "SELECT * FROM `app_data` WHERE `uid`= $sm_id and `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')";
            }
            
        if(isset($_GET['subsw_id']))
			{
                $sm_id = $_GET['subsw_id'];
                $sub = 1;
                $weekly = 1;
                $download = "wsub";
                $query = "SELECT * FROM `app_data` WHERE `uid`= $sm_id and `subdone` = 1 and `status`= 1 and WEEK(subdate) = WEEK('$curdate') and YEAR(subdate) = YEAR('$curdate')";
            }
        
        if(isset($_GET['ecisw_id']))
			{
                $sm_id = $_GET['ecisw_id'];                
                $eci = 1;
                $weekly = 1;
                $download = "weci";
                $query = "SELECT * FROM `eci` WHERE `sm_id`= $sm_id and `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK('$curdate') and YEAR(eci_date) = YEAR('$curdate')";
            }
       
       if(isset($_GET['appsm_id']))
			{
                $sm_id = $_GET['appsm_id'];
                $app = 1;
                $monthly = 1;                
                $download = "mapp";
                $query = "SELECT * FROM `app_data` WHERE `uid`= $sm_id and `status` = 1  and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate')";
            }
        
        if(isset($_GET['rcsm_id']))
			{
                $sm_id = $_GET['rcsm_id'];
                $rc = 1;       
                $monthly = 1;    
                $download = "mrc";     
                $query = "SELECT * FROM `app_data` WHERE `uid`= $sm_id and `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')";
            }
            
        if(isset($_GET['subsm_id']))
			{
                $sm_id = $_GET['subsm_id'];
                $sub = 1;
                $monthly = 1;
                $download = "msub";
                $query = "SELECT * FROM `app_data` WHERE `uid`= $sm_id and `subdone` = 1 and `status`= 1 and MONTH(subdate) = MONTH('$curdate') and YEAR(subdate) = YEAR('$curdate')";
            }
        
        if(isset($_GET['ecism_id']))
			{
                $sm_id = $_GET['ecism_id'];                
                $eci = 1;
                $monthly = 1;
                $download = "meci";
                $query = "SELECT * FROM `eci` WHERE `sm_id`= $sm_id and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate') and YEAR(eci_date) = YEAR('$curdate')";
            }
       
            if(isset($_GET['appsy_id']))
			{
                $sm_id = $_GET['appsy_id'];
                $app = 1;
                $yearly = 1;
                $download = "yapp";
                $query = "SELECT * FROM `app_data` WHERE `uid`= $sm_id and `status` = 1  and YEAR(appdate) = YEAR('$curdate')";
            }
        
        if(isset($_GET['rcsy_id']))
			{
                $sm_id = $_GET['rcsy_id'];
                $rc = 1;     
                $yearly = 1;        
                $download = "yrc";   
                $query = "SELECT * FROM `app_data` WHERE `uid`= $sm_id and `rcdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')";
            }
            
        if(isset($_GET['subsy_id']))
			{
                $sm_id = $_GET['subsy_id'];
                $sub = 1;
                $yearly = 1;
                $download = "ysub";
                $query = "SELECT * FROM `app_data` WHERE `uid`= $sm_id and `subdone` = 1 and `status`= 1 and YEAR(subdate) = YEAR('$curdate')";
            }
        
        if(isset($_GET['ecisy_id']))
			{
                $sm_id = $_GET['ecisy_id'];                
                $eci = 1;
                $yearly = 1;
                $download = "yeci";
                $query = "SELECT * FROM `eci` WHERE `sm_id`= $sm_id and `eci_happened` =1 and `status` = 1 and YEAR(eci_date) = YEAR('$curdate')";
            }

        }
     }
	
$ind= $conn->prepare($query);
$ind->execute();
$data = $ind->fetchAll();

$username = $conn->query("select name from users where uid = $sm_id")->fetchColumn();
/*
$skillid = $conn->query("select skill from consultants where cid = $consultantid")->fetchColumn();
$skill = $conn->query("SELECT skillname FROM `skill` WHERE `sid`= $skillid")->fetchColumn();
$cfname = $conn->query("select cfname from consultants where cid = $consultantid")->fetchColumn();
$clname = $conn->query("select clname from consultants where cid = $consultantid")->fetchColumn();

$reqid = $conn->query("select reqid from app_data where app_id = $eciid")->fetchColumn(); */
?>

<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active"><h3><?php echo $username; ?> - <?php
                if($app==1) { echo "Application Details"; }
                elseif($rc==1) { echo "Rate Confirmation Details"; }
                elseif($sub==1) { echo "Submissions Details"; }
                elseif($eci==1) { echo "Interview Details"; }
                ?>
                </h3></li>
			</ol>
</div>
<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-heading"> <a href="admin.php?action=showsmdata"><button name="back" class="btn btn-primary">Back</button></a> &nbsp;&nbsp;&nbsp;
                    <a target ="_blank" href="trackersmdownload.php?download=<?php 
                    
                    echo $download."&smid=".$sm_id;

                    if($olddate==1) { echo "&date=".$curdate; } 
                    
                    
                    ?>"><button name="download" class="btn btn-primary">Download it!</button></a>
                  
                  <!--
                    <?php if($dta['level'] == 3) {  /*?>
						<a target ="_blank" href="smreqsdownload.php?download=allreqs
						<?php if($olddate==1) { echo "&date=".$curdate; } ?>"><button name="downloadallreqs" class="btn btn-primary">Download Reqs</button></a> &nbsp;&nbsp;&nbsp;&nbsp;<a target ="_blank" href="smreqsdownload.php?download=allreqs&showweekly=1&showunique=1
						<?php if($olddate==1) { echo "&date=".$curdate; } ?>"><button name="downloadweeklyreqs" class="btn btn-primary">Download Unique Weekly Reqs</button></a>&nbsp;&nbsp;&nbsp;&nbsp;<a target ="_blank" href="smreqsdownload.php?download=allreqs&showmonthly=1&showunique=1
                    <?php if($olddate==1) { echo "&date=".$curdate; } ?>"><button name="downloadweeklyreqs" class="btn btn-primary">Download Unique Monthly Reqs</button></a><?php */ } ?> -->
				
				    </div>
        

					<div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uid" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="id">S.no</th>                                
						        <th data-field="Datetime"  data-sortable="true">Datetime</th>
								<?php   if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3) {	?>   <th data-field="name" data-sortable="true">Consultant Name</th> <?php }	?> 
						        <th data-field="Role"  data-sortable="true">Role</th>							
						        <th data-field="Client"  data-sortable="true">IP/CLient</th>	
                                <th data-field="remail" data-sortable="true">Recruiter Email</th>
                                <th data-field="arsstatus"  data-sortable="true">Status</th>
								<?php
                                if($app == 1)
                                { ?>
                                    <th data-field="Status"  data-sortable="true">RC Status</th>
                                <?php }
                                elseif($rc == 1)
                                { ?>
                                    <th data-field="Status"  data-sortable="true">Sub Status</th>
                                <?php }
                                elseif($sub == 1)
                                { ?>
                                    <th data-field="Status"  data-sortable="true">ECI Status</th>
                                <?php } ?>
						        <th data-field="comment"  data-sortable="true">Comment</th>
						    </tr>
						    </thead>
						   <tbody>
<?php
$i=1;
foreach( $data as $row) { 
if($app == 1 || $rc == 1 || $sub == 1)
    {
        $app_id = $row['app_id'];
        $reqid = $row['reqid'];
        $cid = $row['client_id'];
        $consultant_id = $row['consultant_id'];	
    }
elseif($eci == 1) {
    $app_id = $row['app_id'];
    $reqid = $row['req_id'];
    $cid = $row['t2id'];
    $consultant_id = $row['consultant_id'];	
}


								$q3 = "SELECT * from clients where `cid` = $cid";
								$ins4= $conn->prepare($q3);
								$ins4->execute(); 
								$dta3 = $ins4->fetch();


								$q4 = "SELECT * from consultants where `cid` = $consultant_id";
								$ins5= $conn->prepare($q4);
								$ins5->execute(); 
								$dta4 = $ins5->fetch();

								$q5 = "SELECT * from req where `reqid` = $reqid";
								$ins6= $conn->prepare($q5);
								$ins6->execute(); 
								$dta5 = $ins6->fetch();

$ipname = $conn->query("select t1ip_name from app_data where app_id = $app_id")->fetchColumn();
$clientname = $conn->query("select rend_client from req where reqid = $reqid")->fetchColumn();
$skillid = $conn->query("select skill from consultants where cid = $consultant_id")->fetchColumn();
$skill = $conn->query("SELECT skillname FROM `skill` WHERE `sid`= $skillid")->fetchColumn();
	?>
    <tr>
		
    	<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
        <td data-search="<?php 
        
        if($app==1) { $date = $row['appdate']; }
        elseif($rc==1) { $date = $row['rcdate'];  }
        elseif($sub==1) { $date = $row['rcdate'];  }
        elseif($eci==1) { $date = $row['eci_date'];  }
        
        echo $date; ?>"> <?php $time = strtotime($date); $myFormatForView = date("m/d/y g:i A", $time); echo $myFormatForView; ?></td>
		<?php   if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3) {	?>	<td data-search="<?php echo $dta4['cfname']; ?>"> <?php echo $dta4['cfname']." ".$dta4['cmname']." ".$dta4['clname']; ?> </td>   <?php } ?>
        <td data-search="<?php echo $skill." ".$dta5['rlocation']; ?>"> <a id="various3" href="leads/view.php?id=<?php if($eci==1) { echo $row['req_id']; } else { echo $row['reqid']; } ?>"><?php echo $skill." - ".$dta5['rlocation']." - ".$dta5['rduration']; ?></a></td>
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
        $astatus="No";
        if($app == 1 && $row['rcdone']==1) { $astatus="Yes"; }
        elseif($rc == 1 && $row['subdone']==1) { $astatus="Yes"; }
        elseif($sub == 1 && $row['hasinterview']==1) { $astatus="Yes"; }
        elseif($eci == 1) { $astatus="Yes"; }
	 ?> </td>
       <?php if($app == 1) {  ?><td data-search="<?php echo $astatus; ?>"> <?php echo $astatus; ?>  </td>
		<td> <a href="comments.php?appcom_id=<?php echo $app_id; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,'); return false;"><button name="viewcomment" class="btn btn-primary">View Comment</button></a> </td> <?php } ?>
        <?php if($rc == 1) {  ?><td data-search="<?php echo $astatus; ?>"> <?php echo $astatus; ?>  </td>
		<td> <a href="comments.php?rccom_id=<?php echo $app_id; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,'); return false;"><button name="viewcomment" class="btn btn-primary">View Comment</button></a> </td> <?php } ?>
        <?php if($sub==1) {  ?><td data-search="<?php echo $astatus; ?>"> <?php echo $astatus; ?>  </td>
		<td> <a href="comments.php?subcom_id=<?php echo $app_id; ?>" onclick="window.open(this.href,'popupwindow','toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,height=400,width=400,'); return false;"><button name="viewcomment" class="btn btn-primary">View Comment</button></a> </td> <?php } ?>
   
   
     <!--     <?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?> <td> 	
  		<a href="appdata_cmd.php?do=edit&id=<?php echo $row['app_id']; ?>"><img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /></a> 
    				<a href ="appdata_cmd.php?do=delete&appid=<?php echo $row['app_id']; ?>" onClick="return confirm('Are you sure you want to remove this application ?')"><img src="images/b_drop.png" alt="Delete" width="16" height="16" border="0" title="Delete"/></a>
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
