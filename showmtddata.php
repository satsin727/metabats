


<?php

//MTD consultant wise Data
if($_SESSION['id'])
{
$sessid = $_SESSION['id'];
}
else
{
	header( "Location: admin.php" ); exit; 

}

$conn=null;
$conn = bats_db();
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

$uid = $dta['uid'];

if(isset($_POST['date']))
{
 $curdate = $_POST['date'];
}
else
{
 $curdate = date("m/d/y");
}
$curdate = strtotime($curdate);
$curdate = date('Y-m-d H:i:s',$curdate);

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
          <div class="panel-heading"> <a href="admin.php?action=showreports"><button name="back" class="btn btn-primary">Back</button></a></div>
          <div class="panel-heading"> <td width="90%" align="left" valign="top"> <form action="" method="post"><input name="date" id="datepicker"> <button name="submit" class="btn btn-primary">Submit</button></td> <td> Current date: <?php echo date("m/d/y",strtotime($curdate)); ?> </div>
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
                                        <th data-field="dob" data-sortable="true" data-show-toggle="false">DOB</th>
                                        <th data-field="byear" data-sortable="true" data-show-toggle="false">Graduation Year</th>
                                        
                                        <th data-field="dapp" data-sortable="true">Today App</th>                          
                                        <th data-field="drc" data-sortable="true">Today RC</th>                         
                                        <th data-field="dsub" data-sortable="true">Today Sub</th> 
                                        <th data-field="deci" data-sortable="true">Today ECI</th> 

                                        <th data-field="wapp" data-sortable="true">Weekly App</th>                          
                                        <th data-field="wrc" data-sortable="true">Weekly RC</th>                         
                                        <th data-field="wsub" data-sortable="true">Weekly Sub</th> 
                                        <th data-field="weci" data-sortable="true">Weekly ECI</th>

                                        
                                        <th data-field="mapp" data-sortable="true">Monthly App</th>                          
                                        <th data-field="mrc" data-sortable="true">Monthly RC</th>                         
                                        <th data-field="msub" data-sortable="true">Monthly Sub</th> 
                                        <th data-field="meci" data-sortable="true">Monthly ECI</th>

                                        
                                        <th data-field="yapp" data-sortable="true" data-visible="false">YTD App</th>                          
                                        <th data-field="yrc" data-sortable="true" data-visible="false">YTD RC</th>                         
                                        <th data-field="ysub" data-sortable="true" data-visible="false">YTD Sub</th> 
                                        <th data-field="yeci" data-sortable="true" data-visible="false">YTD ECI</th>
                                        
                </tr>
                </thead>
						    
                                    
                  <tbody>
                           <?php
                           if($dta['level'] == 1 || $dta['level'] == 2)
                           {
                           $query = ("select * from consultants where (end_date = '0000-00-00' or (MONTH(end_date)>= MONTH(" . bats_q("$curdate") . ") AND YEAR(end_date)>= YEAR(" . bats_q("$curdate") . ")) ) order by cfname asc");
                           }
                           else
                           {
                           $query = ("select * from assigned AS A LEFT JOIN consultants AS B ON A.cid = B.cid where A.uid = " . bats_q($uid) . " and (end_date = '0000-00-00' or MONTH(end_date)>= MONTH(" . bats_q("$curdate") . ")) order by B.cfname asc");
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
                                                                        $conn2 = bats_db();
                                                                        $q2 = ("select * from skill where `sid` = " . bats_q($sid));
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
                                                $conn = bats_db();
                                                
                                                if($dta['level'] == 1 || $dta['level'] == 2)
                                                {
                                                  $dapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `status` = 1 and appdate >= DATE(" . bats_q("$curdate") . ") AND appdate < DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY AND DATE(appdate) = DATE(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  $drc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `status`= 1 and rcdate >= DATE(" . bats_q("$curdate") . ") AND rcdate < DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY AND DATE(rcdate) = DATE(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  $dsub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and rcdate >= DATE(" . bats_q("$curdate") . ") AND rcdate < DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY AND DATE(rcdate) = DATE(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  $qeci = ("select distinct app_id from eci where consultant_id = " . bats_q($cid) . " and `eci_happened` = 1 and `eci_round` = 3 and `status` = 1 ");
                                                  $ins= $conn->prepare($qeci);
                                                  $ins->execute();
                                                  $deci = $ins->fetchAll();
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                                                    if(date("d",strtotime($date)) == date("d",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $deci_num = $c;
                                                  //$deci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and eci_date >= DATE('$curdate') AND eci_date < DATE('$curdate') + INTERVAL 1 DAY AND DATE(eci_date) = '$curdate'")->fetchColumn();
                                                  

                                                  $wapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `status` = 1  and appdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND appdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(appdate) = WEEK(" . bats_q("$curdate") . ") and YEAR(appdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  $wrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `status`= 1 and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(rcdate) = WEEK(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  $wsub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(rcdate) = WEEK(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();

                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                                                    if(date("W",strtotime($date)) == date("W",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $weci_num = $c;
                                                //  $weci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK('$curdate')")->fetchColumn();
                                                //  $weci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1")->fetchColumn();
                                                  
                                                $mapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `status` = 1  and appdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND appdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(appdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(appdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                $mrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `status`= 1 and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(rcdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                $msub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(rcdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')")->fetchColumn();

                                                $c=0;
                                                foreach($deci as $ueci)
                                                { $a = $ueci['app_id'];
                                                  $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                                                  if(date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                  {
                                                    $c++;
                                                  }
                                                }
                                                $meci_num = $c;

                                                
                                                $yapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `status` = 1 and appdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND appdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(appdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                $yrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `status`= 1 and rcdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND rcdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                $ysub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and rcdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND rcdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')")->fetchColumn();

                                                $c=0;
                                                foreach($deci as $ueci)
                                                { $a = $ueci['app_id'];
                                                  $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                                                  if(date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                  {
                                                    $c++;
                                                  }
                                                }
                                                $yeci_num = $c;
                                              
                                              }
                                                else
                                                {
                                                 // $uid =  $_SESSION['id'];
                                                  $dapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `status` = 1 and `uid` = " . bats_q($uid) . " and appdate >= DATE(" . bats_q("$curdate") . ") AND appdate < DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY AND DATE(appdate) = DATE(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  $drc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= DATE(" . bats_q("$curdate") . ") AND rcdate < DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY AND DATE(rcdate) = DATE(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  $dsub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= DATE(" . bats_q("$curdate") . ") AND rcdate < DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY AND DATE(rcdate) = DATE(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  //$deci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and eci_date >= DATE('$curdate') AND eci_date < DATE('$curdate') + INTERVAL 1 DAY AND DATE(eci_date) = '$curdate'")->fetchColumn();
                                                  $qeci = ("select distinct app_id from eci where consultant_id = " . bats_q($cid) . " and `eci_happened` =1 and `eci_round` = 3 and `sm_id` = " . bats_q($uid) . "  and `status` = 1");
                                                  $ins= $conn->prepare($qeci);
                                                  $ins->execute();
                                                  $deci = $ins->fetchAll();
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                                                    if(date("d",strtotime($date)) == date("d",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $deci_num = $c;

                                                  $wapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `status` = 1  and `uid` = " . bats_q($uid) . " and appdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND appdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(appdate) = WEEK(" . bats_q("$curdate") . ") and YEAR(appdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  $wrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(rcdate) = WEEK(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  $wsub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(rcdate) = WEEK(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  //$weci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and WEEK(eci_date) = WEEK('$curdate')")->fetchColumn();
                                                  
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                                                    if(date("W",strtotime($date)) == date("W",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $weci_num = $c;

                                                  $mapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `status` = 1 and `uid` = " . bats_q($uid) . " and appdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND appdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(appdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(appdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  $mrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(rcdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  $msub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(rcdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')-1")->fetchColumn();
                                                  
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                                                    if(date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $meci_num = $c;

                                                  $yapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `status` = 1 and `uid` = " . bats_q($uid) . " and appdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND appdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(appdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  $yrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND rcdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  $ysub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= " . bats_q($cid) . " and `rcdone` = 1 and `subdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND rcdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                                                  //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')-1")->fetchColumn();
                                                  
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                                                    if(date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $yeci_num = $c;



                                                }                                             
                                                
                                                
                                                ?>

                                                <td data-search="<?php echo $dapp_num; ?>"> <a href="trackercmd.php?appcd_id=<?php echo $cid; ?>"><?php echo $dapp_num; ?></a></td>
                                                <td data-search="<?php echo $drc_num; ?>"> <a href="trackercmd.php?rccd_id=<?php echo $cid; ?>"><?php echo $drc_num; ?></a></td> 
                                                <td data-search="<?php echo $dsub_num; ?>"> <a href="trackercmd.php?subcd_id=<?php echo $cid; ?>"><?php echo $dsub_num; ?></a></td>
                                                <td data-search="<?php echo $deci_num; ?>"> <a href="trackercmd.php?ecicd_id=<?php echo $cid; ?>"><?php echo $deci_num; ?></a></td>

                                                <td data-search="<?php echo $wapp_num; ?>"> <a href="trackercmd.php?appcw_id=<?php echo $cid; ?>"><?php echo $wapp_num; ?></a></td>
                                                <td data-search="<?php echo $wrc_num; ?>"> <a href="trackercmd.php?rccw_id=<?php echo $cid; ?>"><?php echo $wrc_num; ?></a></td> 
                                                <td data-search="<?php echo $wsub_num; ?>"> <a href="trackercmd.php?subcw_id=<?php echo $cid; ?>"><?php echo $wsub_num; ?></a></td> 
                                                <td data-search="<?php echo $weci_num; ?>"> <a href="trackercmd.php?ecicw_id=<?php echo $cid; ?>"><?php echo $weci_num; ?></a></td> 

                                                
                                                <td data-search="<?php echo $mapp_num; ?>"> <a href="trackercmd.php?appcm_id=<?php echo $cid; ?>"><?php echo $mapp_num; ?></a></td>
                                                <td data-search="<?php echo $mrc_num; ?>"> <a href="trackercmd.php?rccm_id=<?php echo $cid; ?>"><?php echo $mrc_num; ?></a></td> 
                                                <td data-search="<?php echo $msub_num; ?>"> <a href="trackercmd.php?subcm_id=<?php echo $cid; ?>"><?php echo $msub_num; ?></a></td> 
                                                <td data-search="<?php echo $meci_num; ?>"> <a href="trackercmd.php?ecicm_id=<?php echo $cid; ?>"><?php echo $meci_num; ?></a></td> 
                                                   
                                                <td data-search="<?php echo $yapp_num; ?>"> <a href="trackercmd.php?appcy_id=<?php echo $cid; ?>"><?php echo $yapp_num; ?></a></td>
                                                <td data-search="<?php echo $yrc_num; ?>"> <a href="trackercmd.php?rccy_id=<?php echo $cid; ?>"><?php echo $yrc_num; ?></a></td> 
                                                <td data-search="<?php echo $ysub_num; ?>"> <a href="trackercmd.php?subcy_id=<?php echo $cid; ?>"><?php echo $ysub_num; ?></a></td> 
                                                <td data-search="<?php echo $yeci_num; ?>"> <a href="trackercmd.php?ecicy_id=<?php echo $cid; ?>"><?php echo $yeci_num; ?></a></td> 

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

                  $conn = bats_db();
                  
                  if($dta['level'] == 1 || $dta['level'] == 2)
                  {
                    $dtapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `status` = 1 and appdate >= DATE(" . bats_q("$curdate") . ") AND appdate < DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY AND DATE(appdate) = DATE(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $dtrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and rcdate >= DATE(" . bats_q("$curdate") . ") AND rcdate < DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY AND DATE(rcdate) = DATE(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $dtsub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `subdone` = 1 and `rcdone` = 1 and `status`= 1 and rcdate >= DATE(" . bats_q("$curdate") . ") AND rcdate < DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY AND DATE(rcdate) = DATE(" . bats_q("$curdate") . ")"))->fetchColumn();
                    // $dteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `eci_happened` =1 and `status` = 1 and eci_date >= DATE('$curdate') AND eci_date < DATE('$curdate') + INTERVAL 1 DAY AND DATE(eci_date) = '$curdate'")->fetchColumn();
                    // $dteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `eci_happened` =1 and `status` = 1 and eci_date >= DATE('$curdate') AND eci_date < DATE('$curdate') + INTERVAL 1 DAY AND DATE(eci_date) = '$curdate'")->fetchColumn();
                      
                    $qeci = "select distinct app_id from eci where `eci_happened` =1 and `eci_round` = 3 and `status` = 1";
                                                  $ins= $conn->prepare($qeci);
                                                  $ins->execute();
                                                  $deci = $ins->fetchAll();
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                                                    if(date("d",strtotime($date)) == date("d",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $dteci_num = $c;

                    $wtapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and appdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND appdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(appdate) = WEEK(" . bats_q("$curdate") . ") and YEAR(appdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $wtrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(rcdate) = WEEK(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $wtsub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `rcdone` = 1 and `status`= 1 and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(rcdate) = WEEK(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    //$wteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK('$curdate')")->fetch();
                    // $wteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK('$curdate')")->fetch();

                    $c=0;
                    foreach($deci as $ueci)
                    { $a = $ueci['app_id'];
                      $date = $conn->query(("SELECT eci_date FROM `eci` WHERE  `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                      if(date("W",strtotime($date)) == date("W",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                      {
                        $c++;
                      }
                    }
                    $wteci_num = $c;

                    $mtapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `status` = 1  and appdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND appdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(appdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(appdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $mtrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(rcdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $mtsub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `subdone` = 1 and `rcdone` = 1 and `status`= 1 and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(rcdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')-1")->fetchColumn();

                    $c=0;
                    foreach($deci as $ueci)
                    { $a = $ueci['app_id'];
                      $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                      if(date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))))
                      {
                        $c++;
                      }
                    }
                    $mteci_num = $c;
                  
                    
                    $ytapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `status` = 1  and appdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND appdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(appdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $ytrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and rcdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND rcdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $ytsub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `subdone` = 1 and `rcdone` = 1 and `status`= 1 and rcdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND rcdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')-1")->fetchColumn();
                    
                    $c=0;
                    foreach($deci as $ueci)
                    { $a = $ueci['app_id'];
                      $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                      if(date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))))
                      {
                        $c++;
                      }
                    }
                    $yteci_num = $c;
                  
                  
                  }
                  else
                  {
                    $dtapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1 and `uid` = " . bats_q($uid) . " and appdate >= DATE(" . bats_q("$curdate") . ") AND appdate < DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY AND DATE(appdate) = DATE(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $dtrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= DATE(" . bats_q("$curdate") . ") AND rcdate < DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY AND DATE(rcdate) = DATE(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $dtsub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `rcdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= DATE(" . bats_q("$curdate") . ") AND rcdate < DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY AND DATE(rcdate) = DATE(" . bats_q("$curdate") . ")"))->fetchColumn();
                    //$dteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and DATE(eci_date) = $curdate")->fetchColumn();
                    $qeci = ("select distinct app_id from eci where `eci_happened` =1  and `eci_round` = 3  and `status` = 1 and `sm_id` = " . bats_q($uid));
                    $ins= $conn->prepare($qeci);
                    $ins->execute();
                    $deci = $ins->fetchAll();
                    $c=0;
                    foreach($deci as $ueci)
                      { $a = $ueci['app_id'];
                        $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                        if(date("d",strtotime($date)) == date("d",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                        {
                          $c++;
                        }
                      }
                    $dteci_num = $c;

                    $wtapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and `uid` = " . bats_q($uid) . " and appdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND appdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(appdate) = WEEK(" . bats_q("$curdate") . ") and YEAR(appdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $wtrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(rcdate) = WEEK(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $wtsub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `rcdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and WEEK(subdate) = WEEK(" . bats_q("$curdate") . ") and rcdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND rcdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    //$wteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and WEEK(eci_date) = WEEK('$curdate')")->fetchColumn();
                  
                    
                    $c=0;
                    foreach($deci as $ueci)
                    { $a = $ueci['app_id'];
                      $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                      if(date("W",strtotime($date)) == date("W",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))))
                      {
                        $c++;
                      }
                    }
                    $wteci_num = $c;

                    $mtapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `status` = 1 and `uid` = " . bats_q($uid) . " and appdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND appdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(appdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(appdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $mtrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(rcdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $mtsub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `subdone` = 1 and `rcdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(rcdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')-1")->fetchColumn();
                    
                    $c=0;
                    foreach($deci as $ueci)
                    { $a = $ueci['app_id'];
                      $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                      if(date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                      {
                        $c++;
                      }
                      }
                    $mteci_num = $c;
                  
                    
                    $ytapp_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `status` = 1 and `uid` = " . bats_q($uid) . " and appdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND appdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(appdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $ytrc_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND rcdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    $ytsub_num = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `subdone` = 1 and `rcdone` = 1 and `status`= 1 and `uid` = " . bats_q($uid) . " and rcdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND rcdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ")"))->fetchColumn();
                    //$meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')-1")->fetchColumn();
                    
                    $c=0;
                    foreach($deci as $ueci)
                    { $a = $ueci['app_id'];
                      $date = $conn->query(("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= " . bats_q($a)))->fetchColumn();
                      if(date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                      {
                        $c++;
                      }
                      }
                    $yteci_num = $c;
                  }     


?> 
                                                
                                                <td> <a href="trackercmd.php?appcdt_id=1"><?php echo $dtapp_num; ?></a></td>
                                                <td> <a href="trackercmd.php?rccdt_id=1"><?php echo $dtrc_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?subcdt_id=1"><?php echo $dtsub_num; ?></a></td>
                                                <td> <a href="trackercmd.php?ecicdt_id=1"><?php echo $dteci_num; ?></a></td>

                                                <td> <a href="trackercmd.php?appcwt_id=1"><?php echo $wtapp_num; ?></a></td>
                                                <td> <a href="trackercmd.php?rccwt_id=1"><?php echo $wtrc_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?subcwt_id=1"><?php echo $wtsub_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?ecicwt_id=1"><?php echo $wteci_num; ?></a></td> 

                                                
                                                <td> <a href="trackercmd.php?appcmt_id=1"><?php echo $mtapp_num; ?></a></td>
                                                <td> <a href="trackercmd.php?rccmt_id=1"><?php echo $mtrc_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?subcmt_id=1"><?php echo $mtsub_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?ecicmt_id=1"><?php echo $mteci_num; ?></a></td> 

                                                
                                                <td> <a href="trackercmd.php?appcyt_id=1"><?php echo $ytapp_num; ?></a></td>
                                                <td> <a href="trackercmd.php?rccyt_id=1"><?php echo $ytrc_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?subcyt_id=1"><?php echo $ytsub_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?ecicyt_id=1"><?php echo $yteci_num; ?></a></td> 

                                      </tr>
                                              </table>
                                              </tbody>
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
