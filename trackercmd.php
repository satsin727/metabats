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
$curdate =date('Y-m-d'); //2023-10-30

if(isset($_GET['date']))
{
    $curdate =$_GET['date'];
}


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
            $app = 0;
            $rc = 0;
            $sub = 0;
            $eci = 0;
            $total =0;
	if($dta['level'] == 1 || $dta['level'] == 2)
	{
		
        if(isset($_GET['appcd_id']))
			{
                $consultantid = $_GET['appcd_id'];
                $app = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `status` = 1 and DATE(appdate) = DATE('$curdate') and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate')";
            }
        if(isset($_GET['rccd_id']))
			{
                $consultantid = $_GET['rccd_id'];
                $rc = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `rcdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate') and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')";
            }
            
        if(isset($_GET['subcd_id']))
            {
                $consultantid = $_GET['subcd_id'];                
                $sub = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `subdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate') and MONTH(rcdate) = MONTH('$curdate') and YEAR(subdate) = YEAR('$curdate')";
            }
        
        if(isset($_GET['ecicd_id']))
			{
                $consultantid = $_GET['ecicd_id'];
                $eci = 1;
                $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1 and DATE(eci_date) = DATE('$curdate')  and MONTH(eci_date) = MONTH('$curdate') and YEAR(eci_date) = YEAR('$curdate')";
            }
        
            if(isset($_GET['appcw_id']))
			{
                $consultantid = $_GET['appcw_id'];
                $app = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `status` = 1  and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate')";
            }
        
        if(isset($_GET['rccw_id']))
			{
                $consultantid = $_GET['rccw_id'];
                $rc = 1;                
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')";
            }
            
        if(isset($_GET['subcw_id']))
			{
                $consultantid = $_GET['subcw_id'];
                $sub = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `subdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')";
            }
        
        if(isset($_GET['ecicw_id']))
			{
                $consultantid = $_GET['ecicw_id'];                
                $eci = 1;
                $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK('$curdate') and YEAR(eci_date) = YEAR('$curdate')";
            //    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1";
            }
            
        if(isset($_GET['appcm_id']))
			{
                $consultantid = $_GET['appcm_id'];
                $app = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `status` = 1  and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate')";
            }
        
        if(isset($_GET['rccm_id']))
			{
                $consultantid = $_GET['rccm_id'];
                $rc = 1;                
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')";
            }
            
        if(isset($_GET['subcm_id']))
			{
                $consultantid = $_GET['subcm_id'];
                $sub = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `subdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')";
            }
        
        if(isset($_GET['ecicm_id']))
			{
                $consultantid = $_GET['ecicm_id'];                
                $eci = 1;
                $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate') and YEAR(eci_date) = YEAR('$curdate')";
            //    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1";
            }

                 
        if(isset($_GET['appcy_id']))
        {
            $consultantid = $_GET['appcy_id'];
            $app = 1;
            $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `status` = 1  and YEAR(appdate) = YEAR('$curdate')";
        }
    
    if(isset($_GET['rccy_id']))
        {
            $consultantid = $_GET['rccy_id'];
            $rc = 1;                
            $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `rcdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')";
        }
        
    if(isset($_GET['subcy_id']))
        {
            $consultantid = $_GET['subcy_id'];
            $sub = 1;
            $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')";
        }
    
    if(isset($_GET['ecicy_id']))
        {
            $consultantid = $_GET['ecicy_id'];                
            $eci = 1;
            $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1 and YEAR(eci_date) = YEAR('$curdate')";
        //    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1";
        }

    if(isset($_GET['appcall_id']))
        {
            $consultantid = $_GET['appcall_id'];
            $app = 1;
            $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `status` = 1";
        }
        
    if(isset($_GET['rccall_id']))
        {
            $consultantid = $_GET['rccall_id'];
            $app = 1;
            $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `rcdone` = 1 and `status` = 1";
        }
        
    if(isset($_GET['subcall_id']))
        {
            $consultantid = $_GET['subcall_id'];
            $app = 1;
            $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `rcdone` = 1  and `subdone` = 1 and `status` = 1";
        }
    
    if(isset($_GET['ecicall_id']))
        {
            $consultantid = $_GET['ecicall_id'];                
            $eci = 1;
            $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1";
        //    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1";
        }


###########################################################################################################################################################################
###################################################################################################################################################################################
      

            if(isset($_GET['appcdt_id']))
            {
                $app = 1;
                $total=1;                
                $download = "dapp";
                $query = "SELECT * FROM `app_data` WHERE  `status` = 1 and DATE(appdate) = DATE('$curdate') and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate') order by appdate asc";
            }
            if(isset($_GET['rccdt_id']))
            {
                $rc = 1;                
                $total=1;
                $download = "drc";
                $query = "SELECT * FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate') and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate') order by rcdate asc";
            }

            if(isset($_GET['subcdt_id']))
            {               
                $sub = 1;
                $total=1;                
                $download = "dsub";
                $query = "SELECT * FROM `app_data` WHERE `rcdone` = 1 and `subdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate') and MONTH(rcdate) = MONTH('$curdate') and YEAR(subdate) = YEAR('$curdate') order by subdate asc";
            }

            if(isset($_GET['ecicdt_id']))
            {
                $eci = 1;
                $total=1;
                $download = "deci";
                $query = "SELECT * FROM `eci` WHERE `eci_happened` =1 and `status` = 1 and DATE(eci_date) = DATE('$curdate') and MONTH(eci_date) = MONTH('$curdate') and YEAR(eci_date) = YEAR('$curdate') order by eci_req_date asc";
            }

            if(isset($_GET['appcwt_id']))
            {
                $app = 1;
                $total=1;
                $download = "wapp";
                $query = "SELECT * FROM `app_data` WHERE `status` = 1  and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate') order by appdate asc";
            }

            if(isset($_GET['rccwt_id']))
            {
                $rc = 1;  
                $total=1;    
                $download = "wrc";          
                $query = "SELECT * FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate') order by rcdate asc";
            }

            if(isset($_GET['subcwt_id']))
            {
                $sub = 1;
                $total=1;
                $download = "wsub";
                $query = "SELECT * FROM `app_data` WHERE `rcdone` = 1 and `subdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate') order by subdate asc";
            }

            if(isset($_GET['ecicwt_id']))
            {               
                $eci = 1;
                $total=1;
                $download = "weci";
                $query = "SELECT * FROM `eci` WHERE `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK('$curdate') and YEAR(eci_date) = YEAR('$curdate') order by eci_req_date asc";
            //    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1";
            }

            if(isset($_GET['appcmt_id']))
            {
                $app = 1;
                $total=1;
                $query = "SELECT * FROM `app_data` WHERE `status` = 1  and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate') order by appdate asc";
                $download = "mapp";
            }
            if(isset($_GET['appcyt_id']))
            {
                $app = 1;
                $total=1;
                $query = "SELECT * FROM `app_data` WHERE `status` = 1  and YEAR(appdate) = YEAR('$curdate') order by appdate asc";
                $download = "yapp";
            }

            if(isset($_GET['rccmt_id']))
            {
                $rc = 1;   
                $total=1;             
                $query = "SELECT * FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate') order by rcdate asc";
                $download = "mrc";
            }

            
            if(isset($_GET['rccyt_id']))
            {
                $rc = 1;   
                $total=1;             
                $query = "SELECT * FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate') order by rcdate asc";
                $download = "yrc";
            }

            if(isset($_GET['subcmt_id']))
            {
                $sub = 1;
                $total=1;
                $query = "SELECT * FROM `app_data` WHERE `subdone` = 1 and `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate') order by subdate asc";
                $download = "msub";
            }

            
            if(isset($_GET['subcyt_id']))
            {
                $sub = 1;
                $total=1;
                $query = "SELECT * FROM `app_data` WHERE `subdone` = 1 and `rcdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate') order by subdate asc";
                $download = "ysub";
            }


            if(isset($_GET['ecicmt_id']))
            {               
                $eci = 1;
                $total=1;
                $query = "SELECT * FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate') and YEAR(eci_date) = YEAR('$curdate')  order by eci_req_date asc";
                $download = "meci";
            //    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1";
            }
            if(isset($_GET['ecicall_id']))
            {               
                $eci = 1;
                $total=1;
                $query = "SELECT * FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `status` = 1 order by eci_req_date asc";
                $download = "teci";
            //    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1";
            }

            if(isset($_GET['ecicyt_id']))
            {               
                $eci = 1;
                $total=1;
                $query = "SELECT * FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and YEAR(eci_date) = YEAR('$curdate')  order by eci_req_date asc";
                $download = "yeci";
            //    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1";
            }


    }
	else
	{
            if(isset($_GET['appcd_id']))
                {
                    $consultantid = $_GET['appcd_id'];
                    $app = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultantid`= $consultantid and `status` = 1 and `uid` = $uid and DATE(appdate) = DATE('$curdate') and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate')";
                }
            if(isset($_GET['rccd_id']))
                {
                    $consultantid = $_GET['rccd_id'];
                    $rc = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `rcdone` = 1 and `uid` = $uid and `status`= 1 and DATE(rcdate) = DATE('$curdate') and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')";
                }
                
            if(isset($_GET['subcd_id']))
                {
                    $consultantid = $_GET['subcd_id'];
                    $sub = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `subdone` = 1 and `uid` = $uid and `status`= 1 and DATE(subdate) = DATE('$curdate') and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')";
                }
            
            if(isset($_GET['ecicd_id']))
                {
                    $consultantid = $_GET['ecicd_id'];
                    $eci = 1;
                    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `sm_id` = $uid and `status` = 1 and DATE(eci_date) = DATE('$curdate') and MONTH(eci_date) = MONTH('$curdate') and YEAR(eci_date) = YEAR('$curdate')";
                }
            
            if(isset($_GET['appcw_id']))
                {
                    $consultantid = $_GET['appcw_id'];
                    $app = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `uid` = $uid and `status` = 1  and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate')";
                }
            
            if(isset($_GET['rccw_id']))
                {
                    $consultantid = $_GET['rccw_id'];
                    $rc = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `rcdone` = 1 and `uid` = $uid and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')";
                }
                
            if(isset($_GET['subcw_id']))
                {
                    $consultantid = $_GET['subcw_id'];
                    $sub = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid and `subdone` = 1 and `uid` = $uid and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')";
                }
            
            if(isset($_GET['ecicw_id']))
                {
                    $consultantid = $_GET['ecicw_id'];
                    $eci = 1;
                    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `sm_id` = $uid and `status` = 1 and WEEK(eci_date) = WEEK('$curdate') and YEAR(eci_date) = YEAR('$curdate')";
                  //  $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `sm_id` = $uid and `status` = 1";
                }
                        
            if(isset($_GET['appcm_id']))
                {
                    $consultantid = $_GET['appcm_id'];
                    $app = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid  and `uid` = $uid and `status` = 1  and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate')";
                }
            
            if(isset($_GET['rccm_id']))
                {
                    $consultantid = $_GET['rccm_id'];
                    $rc = 1;                
                    $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid  and `uid` = $uid and `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')";
                }
                
            if(isset($_GET['subcm_id']))
                {
                    $consultantid = $_GET['subcm_id'];
                    $sub = 1;
                    $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid  and `uid` = $uid and `subdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')";
                }
            
            if(isset($_GET['ecicm_id']))
                {
                    $consultantid = $_GET['ecicm_id'];                
                    $eci = 1;
                    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid  and `sm_id` = $uid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate') and YEAR(eci_date) = YEAR('$curdate')";
                //    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1";
                }
                                        
            if(isset($_GET['appcy_id']))
            {
                $consultantid = $_GET['appcy_id'];
                $app = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid  and `uid` = $uid and `status` = 1  YEAR(appdate) = YEAR('$curdate')";
            }
        
        if(isset($_GET['rccy_id']))
            {
                $consultantid = $_GET['rccy_id'];
                $rc = 1;                
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid  and `uid` = $uid and `rcdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')";
            }
            
        if(isset($_GET['subcy_id']))
            {
                $consultantid = $_GET['subcy_id'];
                $sub = 1;
                $query = "SELECT * FROM `app_data` WHERE `consultant_id`= $consultantid  and `uid` = $uid and `subdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')";
            }
        
        if(isset($_GET['ecicy_id']))
            {
                $consultantid = $_GET['ecicy_id'];                
                $eci = 1;
                $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid  and `sm_id` = $uid and `eci_happened` =1 and `status` = 1 and YEAR(eci_date) = YEAR('$curdate')";
            //    $query = "SELECT * FROM `eci` WHERE `consultant_id`= $consultantid and `eci_happened` =1 and `status` = 1";
            }
    
    }
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();

if($total==0)
{
    $skillid = $conn->query("select skill from consultants where cid = $consultantid")->fetchColumn();
    $skill = $conn->query("SELECT skillname FROM `skill` WHERE `sid`= $skillid")->fetchColumn();
    $cfname = $conn->query("select cfname from consultants where cid = $consultantid")->fetchColumn();
    $cmname = $conn->query("select cmname from consultants where cid = $consultantid")->fetchColumn();
    $clname = $conn->query("select clname from consultants where cid = $consultantid")->fetchColumn();
    $cmnumber = $conn->query("select cm_phonenumber from consultants where cid = $consultantid")->fetchColumn();
    $conumber = $conn->query("select co_phonenumber from consultants where cid = $consultantid")->fetchColumn();
}
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
                ?> - <?php if($total==0)
                { echo $cfname." ".$clname." (".$skill." Consultant)"; } else { echo "All Consultants"; } ?>
                </h3></li>
			</ol>
</div>
<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
                <div class="panel-heading"> <a href="admin.php?action=showdailydata"><button name="back" class="btn btn-primary">Back</button></a>
               <?php if(isset($download) && ($dta['level'] == 1 || $dta['level'] == 2) ) { ?> <a href="trackerdownload.php?download=<?php echo $download; ?>"><button name="download" class="btn btn-primary">Download</button></a></div> <?php } ?>
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
        <?php if($eci==1) { ?>
        <td data-search="<?php echo $dta3['companyname']; ?>"><?php echo $dta3['companyname']; ?></td>      
        <td data-search="<?php echo $dta3['rname']; ?>"><?php echo $dta3['rname']; ?></td> 
        <td data-search="<?php echo $dta3['remail']; ?>"> <?php echo $dta3['remail']; ?></td> 
        <td data-search="<?php echo $dta3['rphone']; ?>"><?php echo $dta3['rphone']; ?></td> <?php } else { ?>
        <td data-search="<?php echo $dta3['remail']; ?>"> <a href="#" onClick="alert('\n\n\n\n<?php echo "Name: ".$dta3['rname']; ?>\n\n<?php echo"Email: ".$dta3['remail']; ?>\n\n<?php echo"Company Name: ".$dta3['companyname'];?>')"><?php echo $dta3['remail']; ?></a></td> <?php } ?>

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
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>
