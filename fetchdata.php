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

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
$selected = "showreports";
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{

$uid = $sessid;

$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$skill=0;
if(isset($_GET['m']) && isset($_GET['y']))
{
    $m = $_GET['m'];
    $y = $_GET['y'];
    if(isset($_GET['s']))
    {
        $skill = $_GET['s'];
    }
    
    if(isset($_GET['app'])==1)
    {
        $query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where B.skill = $skill and A.status = 1 and ( MONTH(A.appdate) = $m AND YEAR(A.appdate) = $y ) order by A.appdate asc";
        if($skill=0)
        {
            $query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and ( MONTH(A.appdate) = $m AND YEAR(A.appdate) = $y ) order by A.appdate asc";
        }
    }
    elseif(isset($_GET['rc'])==1)
    {
        $query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where B.skill = $skill and A.status = 1 and rcdone = 1 and ( MONTH(A.appdate) = $m AND YEAR(A.appdate) = $y ) order by A.appdate asc";
        if($skill=0)
        {
            $query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and rcdone = 1 and ( MONTH(A.appdate) = $m AND YEAR(A.appdate) = $y ) order by A.appdate asc";
        }
    }
    elseif(isset($_GET['sub'])==1)
    {
        $query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where B.skill = $skill and A.status = 1 and rcdone = 1 and subdone = 1 and ( MONTH(A.appdate) = $m AND YEAR(A.appdate) = $y ) order by A.appdate asc";
        if($skill=0)
        {
            $query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and rcdone = 1 and subdone = 1 and ( MONTH(A.appdate) = $m AND YEAR(A.appdate) = $y ) order by A.appdate asc";
        }
   
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
				<li class="active"><h3><?php
                if($app==1) { echo "Application Details"; }
                elseif($rc==1) { echo "Rate Confirmation Details"; }
                elseif($sub==1) { echo "Submissions Details"; }
                elseif($eci==1) { echo "Interview Details"; }
                ?><?php echo "All Consultants"; ?>
                </h3></li>
			</ol>
</div>
<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
                <div class="panel-heading"> <a href="admin.php?action=showmtddata"><button name="back" class="btn btn-primary">Back</button></a>
               <?php if(isset($download) && $dta['level'] == 1 ) { ?> <a href="trackerdownload.php?download=<?php echo $download; ?>"><button name="download" class="btn btn-primary">Download</button></a></div> <?php } ?>
					<div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uid" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="id">S.no</th>                                
						        <th data-field="Datetime"  data-sortable="true">Datetime</th>
								<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?>   <th data-field="name" data-sortable="true">SM</th> <?php }	?> 
						        <th data-field="Role"  data-sortable="true">Role</th>							
						        <th data-field="Client"  data-sortable="true">IP/CLient</th>
                                <th data-field="consultant" data-sortable="true">Consultant</th>
                                <th data-field="remail" data-sortable="true">Recruiter Email</th>
                                <th data-field="Status"  data-sortable="true">Status</th>
								<?php
                                if($app == 1)
                                { ?>
                                    <th data-field="RCStatus"  data-sortable="true">RC Status</th>
                                <?php }
                                elseif($rc == 1)
                                { ?>
                                    <th data-field="SubStatus"  data-sortable="true">Sub Status</th>
                                <?php }
                                elseif($sub == 1)
                                { ?>
                                    <th data-field="ECIStatus"  data-sortable="true">ECI Status</th>
                                <?php } ?>
						        <th data-field="comment"  data-sortable="true">Comment</th>
						    </tr>
						    </thead>
						   <tbody>
<?php
$i=1;
foreach( $data as $row) { 
$consultantid = $row['consultant_id'];
    if($total==1)
{
    $skillid = $conn->query("select skill from consultants where cid = $consultantid")->fetchColumn();
    $skill = $conn->query("SELECT skillname FROM `skill` WHERE `sid`= $skillid")->fetchColumn();
    $cfname = $conn->query("select cfname from consultants where cid = $consultantid")->fetchColumn();    
    $cmname = $conn->query("select cmname from consultants where cid = $consultantid")->fetchColumn();
    $clname = $conn->query("select clname from consultants where cid = $consultantid")->fetchColumn();
    $cmnumber = $conn->query("select cm_phonenumber from consultants where cid = $consultantid")->fetchColumn();
    $conumber = $conn->query("select co_phonenumber from consultants where cid = $consultantid")->fetchColumn();
}

if($app == 1 || $rc == 1 || $sub == 1)
    {
        $app_id = $row['app_id'];
        $reqid = $row['reqid'];
        $cid = $row['client_id'];
        $uid = $row['uid'];	
    }
elseif($eci == 1) {
    $app_id = $row['app_id'];
    $reqid = $row['req_id'];
    $cid = $row['t2id'];
    $uid = $row['sm_id'];	
}


								$q3 = "SELECT * from clients where `cid` = $cid";
								$ins4= $conn->prepare($q3);
								$ins4->execute(); 
								$dta3 = $ins4->fetch();


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
        elseif($sub==1) { $date = $row['rcdate'];  }
        elseif($eci==1) { $date = $row['eci_date']; $eci_time = $row['eci_time'];  }
        
        
        echo $date; ?>"> <?php $time = strtotime($date); $myFormatForView = date("m/d/y", $time); echo $myFormatForView; if($eci==1) { echo " ".$eci_time; } ?></td>
		<?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?>	<td data-search="<?php echo $dta4['name']; ?>"><a href="#" onClick="alert('\n\n\n\n<?php echo "Name: ".$dta4['name']; ?>\n<?php echo"Email: ".$dta4['email']; ?>\n')"><?php echo $dta4['name']; ?></a> </td>   <?php } ?>
        <td data-search="<?php echo $skill." ".$dta5['rlocation']; ?>"> <a id="various3" href="leads/view.php?id=<?php echo $reqid; ?>" target="_blank"><?php echo $skill." - ".$dta5['rlocation']." - ".$dta5['rduration']; ?></a></td>
		<td data-search="<?php echo $ipname."/".$clientname; ?>"> <?php if(empty($ipname)) { echo $clientname; } else { echo $ipname."/".$clientname; } ?></td>
		
        
        <td data-search="<?php echo $cfname." ".$clname;?>"><a href="#" onClick="alert('\n\n\n\n<?php echo "Marketing Number: ".$cmnumber; ?>\n\n<?php echo "Personal Number: ".$conumber; ?>\n\n')"><?php echo $cfname." ".$cmname." ".$clname;?></a></td>
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
    <!--    <?php   if($dta['level'] == 1 || $dta['level'] == 2) {	?> <td> 	
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

$conn = null;
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>
