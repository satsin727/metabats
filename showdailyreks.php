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
$curdate =date('Y-m-d');

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{

  $olddate = 0;
  $monthly =0;
  $weekly=0;
  $yearly=0;

if(isset($_GET['showweekly']))
	{
		$weekly = 1;
	}
if(isset($_GET['showmonthly']))
	{
		$monthly = 1;
	}
if(isset($_GET['showyearly']))
	{
		$yearly = 1;
	}

  
	if(isset($_POST['date']))
	{
		$cdate = $_POST['date'];
		$olddate = 1;
		$cdate = strtotime($cdate);
		$curdate =date('Y-m-d',$cdate);
	}
  elseif(isset($_GET['date']))
	{
		$cdate = $_GET['date'];
		$olddate = 1;
		$cdate = strtotime($cdate);
		$curdate =date('Y-m-d',$cdate);
	}
	else
	{
		$curdate =date('Y-m-d');
	}

$uid = $dta['uid'];

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
          <div class="panel-heading"> <a target ="_blank" href="admin.php?action=showdailydata&showweekly=1&showmonthly=1&showyearly=1<?php if($olddate==1) { echo "&date=".$curdate; } ?>"><button name="alldata" class="btn btn-primary">Show Weekly and Monthly data</button></a>&nbsp;&nbsp;&nbsp;<a href="admin.php?action=showreports"><button name="back" class="btn btn-primary">Back</button></a></div>
          <div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="false" data-sort-name="name" data-sort-order="asc">
						    <thead>
						    <tr>
                                        <th data-field="ID">ID</th>
                                        <th data-field="Skill" data-sortable="true">Skill</th>
                                        <th data-field="name"  data-sortable="true">Name</th>
                                        <th data-field="dateadded" data-sortable="true">Date Added</th>                               
                                        <th data-field="tenure" data-sortable="true" data-show-toggle="false">Tenure</th> 
                                        <th data-field="Visa Status" data-sortable="true" data-show-toggle="false">Visa Status</th>
                                        <th data-field="dob" data-sortable="true" data-show-toggle="false" data-visible="false">DOB</th>
                                        <th data-field="byear" data-sortable="true" data-show-toggle="false" data-visible="false">Graduation Year</th>
                                        
                                        <th data-field="dapp" data-sortable="true">Today App</th>                          
                                        <th data-field="drc" data-sortable="true">Today RC</th>                         
                                        <th data-field="dsub" data-sortable="true">Today Sub</th> 
                                        <th data-field="deci" data-sortable="true">Today ECI</th> 
                                        <?php if($weekly==1) { ?>
                                        <th data-field="wapp" data-sortable="true">Weekly App</th>                          
                                        <th data-field="wrc" data-sortable="true">Weekly RC</th>                         
                                        <th data-field="wsub" data-sortable="true">Weekly Sub</th> 
                                        <th data-field="weci" data-sortable="true">Weekly ECI</th>

                                        <?php } ?>
                                        <?php if($monthly==1) { ?>
                                        <th data-field="mapp" data-sortable="true">Monthly App</th>                          
                                        <th data-field="mrc" data-sortable="true">Monthly RC</th>                         
                                        <th data-field="msub" data-sortable="true">Monthly Sub</th> 
                                        <th data-field="meci" data-sortable="true">Monthly ECI</th>

                                        <?php } ?>
                                        <?php if($yearly==1) { ?>
                                        <th data-field="yapp" data-sortable="true" data-visible="false">YTD App</th>                          
                                        <th data-field="yrc" data-sortable="true" data-visible="false">YTD RC</th>                         
                                        <th data-field="ysub" data-sortable="true" data-visible="false">YTD Sub</th> 
                                        <th data-field="yeci" data-sortable="true" data-visible="false">YTD ECI</th>
                                        <?php } ?>
                                        
                </tr>
                </thead>
						    
                                    
                  <tbody>
                           <?php
                            if($dta['level'] == 1 || $dta['level'] == 2)
                              {
                              $query = "select * from consultants where status = 1 order by cfname asc";
                              }
                              else
                              {
                              $query = "select * from assigned AS A LEFT JOIN consultants AS B ON A.cid = B.cid where A.uid = $uid order by B.cfname asc";
                              }
                          $ins= $conn->prepare($query);
                          $ins->execute();
                          $data = $ins->fetchAll();
                    $i=1; 
                                        foreach( $data as $row) { ?>
                                            <tr>
                                                <td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1; ?></td>

                                                <?php
                                                                        $sid = $row['skill'];
                                                                        $conn2 = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
                                                                        $q2 = "select * from skill where `sid` = $sid";
                                                                        $ins2= $conn2->prepare($q2);
                                                                        $ins2->execute(); 
                                                                        $dta2 = $ins2->fetch(); 
                                                                        ?>
                                                <td data-search="<?php echo $dta2['skillname']; ?>"> <?php echo $dta2['skillname']; ?></td>
  
                                                <td data-search="<?php echo $row['cfname']; ?>"> <?php echo $row['cfname']." ".$row['cmname']." ".$row['clname']; ?></td>
                                                        <?php
                                                                $time = strtotime($row['dateadded']); 
                                                                $myFormatForView = date("m/d/y", $time); 
                                                                $todaydate = strtotime(date("Y-m-d H:i:s"));
                                                                $diff = abs($todaydate - $time);
                                                                $years = floor($diff / (365*60*60*24));
                                                                $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
                                                                $days = floor($diff/ (60*60*24));

                                                        ?>
                                                <td data-search="<?php echo $myFormatForView; ?>"> <?php echo $myFormatForView; ?></td>
                                                <td data-search="<?php echo $days; ?>"> <?php echo $days; ?></td>
                                                <td data-search="<?php echo $row['cmvisa']; ?>"> <?php echo $row['cmvisa']; ?></td>
                                                <?php
                                                                $time = strtotime($row['dob']); 
                                                                $myFormatForView = date("m/d/y", $time); 

                                                ?>
                                                
                                                <td data-search="<?php echo $myFormatForView; ?>"> <?php echo $myFormatForView; ?></td>
                                                <td data-search="<?php echo $row['byear']; ?>"> <?php echo $row['byear']; ?></td>
                                        
                                                <?php
                                                $cid = $row['cid'];
                                                $conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
                                                
                                                if($dta['level'] == 1 || $dta['level'] == 2)
                                                {
                                                  $dapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1 and DATE(appdate) = DATE('$curdate')")->fetchColumn();
                                                  $drc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                                                  $dsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                                                  $qeci = "select distinct app_id from eci where consultant_id = $cid and `eci_happened` = 1 and `eci_round` = 3 and `status` = 1 ";
                                                  $ins= $conn->prepare($qeci);
                                                  $ins->execute();
                                                  $deci = $ins->fetchAll();
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                                                    if(date("d",strtotime($date)) == date("d",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $deci_num = $c;
                                                  //$deci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and DATE(eci_date) = '$curdate'")->fetchColumn();
                                                  
                                                  if($weekly==1) { 
                                                  $wapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1  and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                                                  $wrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  $wsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                                                    if(date("W",strtotime($date)) == date("W",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $weci_num = $c;
                                                //  $weci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK('$curdate')")->fetchColumn();
                                                //  $weci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1")->fetchColumn();
                                                }
                                                if($monthly==1) {
                                                $mapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1  and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                                                $mrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                $msub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')")->fetchColumn();
                                                
                                                $c=0;
                                                foreach($deci as $ueci)
                                                { $a = $ueci['app_id'];
                                                  $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                                                  if(date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                  {
                                                    $c++;
                                                  }
                                                }
                                                $meci_num = $c;
                                              }
                                              if($yearly==1) {                                                  
                                                $yapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1 and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                                                $yrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                $ysub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')")->fetchColumn();
                                                
                                                $c=0;
                                                foreach($deci as $ueci)
                                                { $a = $ueci['app_id'];
                                                  $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                                                  if(date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                  {
                                                    $c++;
                                                  }
                                                }
                                                $yeci_num = $c;
                                              }
                                              
                                              }
                                                else
                                                {
                                                 // $uid =  $_SESSION['id'];
                                                  $dapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1 and `uid` = $uid and DATE(appdate) = DATE('$curdate')")->fetchColumn();
                                                  $drc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                                                  $dsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                                                  //$deci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and DATE(eci_date) = '$curdate'")->fetchColumn();
                                                  $qeci = "select distinct app_id from eci where consultant_id = $cid and `eci_happened` =1 and `eci_round` = 3 and `sm_id` = $uid  and `status` = 1";
                                                  $ins= $conn->prepare($qeci);
                                                  $ins->execute();
                                                  $deci = $ins->fetchAll();
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                                                    if(date("d",strtotime($date)) == date("d",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $deci_num = $c;

                                                  if($weekly==1) {

                                                  $wapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1  and `uid` = $uid and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                                                  $wrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  $wsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  //$weci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and WEEK(eci_date) = WEEK('$curdate')")->fetchColumn();
                                                  
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                                                    if(date("W",strtotime($date)) == date("W",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $weci_num = $c;

                                                }
                                                if($monthly==1) {

                                                  $mapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1 and `uid` = $uid and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                                                  $mrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and `uid` = $uid and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  $msub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and `uid` = $uid and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')-1")->fetchColumn();
                                                  
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                                                    if(date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $meci_num = $c;

                                                }

                                                if($yearly==1) {

                                                  
                                                  $yapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1 and `uid` = $uid and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                                                  $yrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and `uid` = $uid and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  $ysub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and `uid` = $uid and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')-1")->fetchColumn();
                                                  
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                                                    if(date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $yeci_num = $c;
                                                }

                                                }                                             
                                                
                                                
                                                ?>

                                                <td data-search="<?php echo $dapp_num; ?>"> <a href="trackercmd.php?appcd_id=<?php echo $cid; ?>"><?php echo $dapp_num; ?></a></td>
                                                <td data-search="<?php echo $drc_num; ?>"> <a href="trackercmd.php?rccd_id=<?php echo $cid; ?>"><?php echo $drc_num; ?></a></td> 
                                                <td data-search="<?php echo $dsub_num; ?>"> <a href="trackercmd.php?subcd_id=<?php echo $cid; ?>"><?php echo $dsub_num; ?></a></td>
                                                <td data-search="<?php echo $deci_num; ?>"> <a href="trackercmd.php?ecicd_id=<?php echo $cid; ?>"><?php echo $deci_num; ?></a></td>

                                                <?php if($weekly==1) { ?>
                                                <td data-search="<?php echo $wapp_num; ?>"> <a href="trackercmd.php?appcw_id=<?php echo $cid; ?>"><?php echo $wapp_num; ?></a></td>
                                                <td data-search="<?php echo $wrc_num; ?>"> <a href="trackercmd.php?rccw_id=<?php echo $cid; ?>"><?php echo $wrc_num; ?></a></td> 
                                                <td data-search="<?php echo $wsub_num; ?>"> <a href="trackercmd.php?subcw_id=<?php echo $cid; ?>"><?php echo $wsub_num; ?></a></td> 
                                                <td data-search="<?php echo $weci_num; ?>"> <a href="trackercmd.php?ecicw_id=<?php echo $cid; ?>"><?php echo $weci_num; ?></a></td> 

                                                <?php }
                                                if($monthly==1) { ?>
                                                <td data-search="<?php echo $mapp_num; ?>"> <a href="trackercmd.php?appcm_id=<?php echo $cid; ?>"><?php echo $mapp_num; ?></a></td>
                                                <td data-search="<?php echo $mrc_num; ?>"> <a href="trackercmd.php?rccm_id=<?php echo $cid; ?>"><?php echo $mrc_num; ?></a></td> 
                                                <td data-search="<?php echo $msub_num; ?>"> <a href="trackercmd.php?subcm_id=<?php echo $cid; ?>"><?php echo $msub_num; ?></a></td> 
                                                <td data-search="<?php echo $meci_num; ?>"> <a href="trackercmd.php?ecicm_id=<?php echo $cid; ?>"><?php echo $meci_num; ?></a></td> 

                                                <?php }
                                                if($yearly==1) { ?>                                        
                                                <td data-search="<?php echo $yapp_num; ?>"> <a href="trackercmd.php?appcy_id=<?php echo $cid; ?>"><?php echo $yapp_num; ?></a></td>
                                                <td data-search="<?php echo $yrc_num; ?>"> <a href="trackercmd.php?rccy_id=<?php echo $cid; ?>"><?php echo $yrc_num; ?></a></td> 
                                                <td data-search="<?php echo $ysub_num; ?>"> <a href="trackercmd.php?subcy_id=<?php echo $cid; ?>"><?php echo $ysub_num; ?></a></td> 
                                                <td data-search="<?php echo $yeci_num; ?>"> <a href="trackercmd.php?ecicy_id=<?php echo $cid; ?>"><?php echo $yeci_num; ?></a></td> 

                                                <?php } ?>
                                            </tr>
                                                    <?php                                
                                                } 
                                                
                    ?>


						   
            <tr>
                                      <td></td>
                                      <td></td>
                                      <td></td>
                                      <td></td>
                                      <td></td>
                                      <td></td>
                                      <td></td>
                                      <td>Total</td>
<?php

$conn=null;
                  $conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
                  
                  if($dta['level'] == 1 || $dta['level'] == 2)
                  {
                    $dtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `status` = 1 and DATE(appdate) = DATE('$curdate')")->fetchColumn();
                    $dtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                    $dtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `subdone` = 1 and `rcdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                    // $dteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `eci_happened` =1 and `status` = 1 and DATE(eci_date) = '$curdate'")->fetchColumn();
                    // $dteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `eci_happened` =1 and `status` = 1 and DATE(eci_date) = '$curdate'")->fetchColumn();
                      
                    $qeci = "select distinct app_id from eci where `eci_happened` =1 and `eci_round` = 3 and `status` = 1";
                                                  $ins= $conn->prepare($qeci);
                                                  $ins->execute();
                                                  $deci = $ins->fetchAll();
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                    { $a = $ueci['app_id'];
                                                      $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                                                      if(date("d",strtotime($date)) == date("d",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                      {
                                                        $c++;
                                                      }
                                                    }
                                                  $dteci_num = $c;
                                                  if($weekly==1) {
                    $wtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                    $wtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    $wtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    //$wteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK('$curdate')")->fetchColumn();
                    // $wteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK('$curdate')")->fetchColumn();
                    
                    $c=0;
                    foreach($deci as $ueci)
                    { $a = $ueci['app_id'];
                      $date = $conn->query("SELECT eci_date FROM `eci` WHERE  `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                      if(date("W",strtotime($date)) == date("W",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                      {
                        $c++;
                      }
                    }
                    $wteci_num = $c;
                  }
                  if($monthly==1) {

                    $mtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `status` = 1  and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                    $mtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    $mtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `subdone` = 1 and `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')-1")->fetchColumn();
                    
                    $c=0;
                    foreach($deci as $ueci)
                    { $a = $ueci['app_id'];
                      $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                      if(date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))))
                      {
                        $c++;
                      }
                    }
                    $mteci_num = $c;
                  }

                  if($yearly==1) {
                    
                    $ytapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `status` = 1  and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                    $ytrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    $ytsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `subdone` = 1 and `rcdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')-1")->fetchColumn();
                    
                    $c=0;
                    foreach($deci as $ueci)
                    { $a = $ueci['app_id'];
                      $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                      if(date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))))
                      {
                        $c++;
                      }
                    }
                    $yteci_num = $c;
                  }
                  
                  
                  }
                  else
                  {
                    $dtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1 and `uid` = $uid and DATE(appdate) = DATE('$curdate')")->fetchColumn();
                    $dtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                    $dtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `rcdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                    //$dteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and DATE(eci_date) = $curdate")->fetchColumn();
                    $qeci = "select distinct app_id from eci where `eci_happened` =1  and `eci_round` = 3  and `status` = 1 and `sm_id` = $uid";
                    $ins= $conn->prepare($qeci);
                    $ins->execute();
                    $deci = $ins->fetchAll();
                    $c=0;
                    foreach($deci as $ueci)
                      { $a = $ueci['app_id'];
                        $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                        if(date("d",strtotime($date)) == date("d",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                        {
                          $c++;
                        }
                      }
                    $dteci_num = $c;

                    if($weekly==1) {

                    $wtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and `uid` = $uid and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                    $wtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    $wtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `rcdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(subdate) = WEEK('$curdate') and YEAR(subdate) = YEAR('$curdate')")->fetchColumn();
                    //$wteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and WEEK(eci_date) = WEEK('$curdate')")->fetchColumn();
                  
                    $c=0;
                    foreach($deci as $ueci)
                    { $a = $ueci['app_id'];
                      $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                      if(date("W",strtotime($date)) == date("W",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))))
                      {
                        $c++;
                      }
                    }
                    $wteci_num = $c;

                  }

                  if($monthly==1) {

                    $mtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `status` = 1 and `uid` = $uid and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                    $mtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and `uid` = $uid and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    $mtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `subdone` = 1 and `rcdone` = 1 and `status`= 1 and `uid` = $uid and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')-1")->fetchColumn();
                    
                    $c=0;
                    foreach($deci as $ueci)
                    { $a = $ueci['app_id'];
                      $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                      if(date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                      {
                        $c++;
                      }
                      }
                    $mteci_num = $c;

                  }
                  if($yearly==1) {
                  
                    $ytapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `status` = 1 and `uid` = $uid and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                    $ytrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and `uid` = $uid and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    $ytsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `subdone` = 1 and `rcdone` = 1 and `status`= 1 and `uid` = $uid and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')-1")->fetchColumn();
                    
                    $c=0;
                    foreach($deci as $ueci)
                    { $a = $ueci['app_id'];
                      $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                      if(date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                      {
                        $c++;
                      }
                      }
                    $yteci_num = $c;
                  }
                  }     


?> 
                                                
                                                <td> <a href="trackercmd.php?appcdt_id=1"><?php echo $dtapp_num; ?></a></td>
                                                <td> <a href="trackercmd.php?rccdt_id=1"><?php echo $dtrc_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?subcdt_id=1"><?php echo $dtsub_num; ?></a></td>
                                                <td> <a href="trackercmd.php?ecicdt_id=1"><?php echo $dteci_num; ?></a></td>

                                                <?php if($weekly==1) { ?>
                                                <td> <a href="trackercmd.php?appcwt_id=1"><?php echo $wtapp_num; ?></a></td>
                                                <td> <a href="trackercmd.php?rccwt_id=1"><?php echo $wtrc_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?subcwt_id=1"><?php echo $wtsub_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?ecicwt_id=1"><?php echo $wteci_num; ?></a></td> 

                                                <?php }
                                                if($monthly==1) { ?>
                                                <td> <a href="trackercmd.php?appcmt_id=1"><?php echo $mtapp_num; ?></a></td>
                                                <td> <a href="trackercmd.php?rccmt_id=1"><?php echo $mtrc_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?subcmt_id=1"><?php echo $mtsub_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?ecicmt_id=1"><?php echo $mteci_num; ?></a></td> 
                                                
                                                <?php }
                                                if($yearly==1) { ?>
                                                
                                                <td> <a href="trackercmd.php?appcyt_id=1"><?php echo $ytapp_num; ?></a></td>
                                                <td> <a href="trackercmd.php?rccyt_id=1"><?php echo $ytrc_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?subcyt_id=1"><?php echo $ytsub_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?ecicyt_id=1"><?php echo $yteci_num; ?></a></td> 
                                                <?php } ?>

                                      </tr>
                                              </table>
                                              </tbody>
					</div>
				</div>
			</div>
		</div>

</div>
<?php


$conn=null;

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
