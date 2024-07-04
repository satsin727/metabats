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

//$curdate =date('Y-m-d');
if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{

$olddate = 0;
$uid = $dta['uid'];

if(isset($_POST['date']))
{
    $curdate = $_POST['date'];
	$olddate = 1;
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
				<li class="active"><h3><?php echo "Monthly Reports for ".date('F, Y',strtotime($curdate)); ?>
                </h3></li>
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
                                        <th data-field="ID" data-sortable="true">ID</th>
                                        <th data-field="name"  data-sortable="true">Name</th>     
                                        
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
                                       
                                       <th data-field="yapp" data-sortable="true" data-visible="false">Yearly App</th>                          
                                       <th data-field="yrc" data-sortable="true" data-visible="false">Yearly RC</th>                         
                                       <th data-field="ysub" data-sortable="true" data-visible="false">Yearly Sub</th> 
                                       <th data-field="yeci" data-sortable="true" data-visible="false">Yearly ECI</th>
                                        
                </tr>
                </thead>
						    
                                    
                  <tbody>
                           <?php
                            if($dta['level'] == 1 || $dta['level'] == 2)
                              {
                              $query = "select * from users where `level` > 1 and status = 1 order by name asc";
                              }
                              else
                              {
                              $query = "select * from users where `uid` = $uid and status = 1 order by name asc";
                              }
                          $ins= $conn->prepare($query);
                          $ins->execute();
                          $data = $ins->fetchAll();
                    $i=1; 
                                        foreach( $data as $row) { ?>
                                            <tr>
                                                <td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1; ?></td>

                                                <td data-search="<?php echo $row['name']; ?>"> <?php echo $row['name']; ?></td>
                                          
                                                <?php
                                                $uid = $row['uid'];
                                                
                                                $conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
                                                
                                                if($dta['level'] == 1 || $dta['level'] == 2)
                                                {

                                                  $qeci = "select distinct app_id from eci where sm_id = $uid and `eci_happened` = 1 and `eci_round` = 3 and `status` = 1 and YEAR(eci_date) = YEAR('$curdate')";
                                                  $ins= $conn->prepare($qeci);
                                                  $ins->execute();
                                                  $deci = $ins->fetchAll();
                                                  

                                                  $dapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1 and DATE(appdate) = DATE('$curdate')")->fetchColumn();
                                                  $drc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                                                  $dsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
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
                                                  

                                                  $wapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1  and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                                                  $wrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  $wsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
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
                                                  
                                                  $mapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1  and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                                                  $mrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  $msub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
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
                                                  
                                                  $yapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1  and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                                                  $yrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  $ysub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                                                    if( date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }                                                 
                                                  $yeci_num = $c;
                                                
                                                }
                                                else
                                                {
                                                 // $uid =  $_SESSION['id'];
                                                 
                                                 $qeci = "select distinct app_id from eci where sm_id = $uid and `eci_happened` = 1 and `eci_round` = 3 and `status` = 1 and YEAR(eci_date) = YEAR('$curdate')";
                                                 $ins= $conn->prepare($qeci);
                                                 $ins->execute();
                                                 $deci = $ins->fetchAll();

                                                  $dapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1 and `uid` = $uid and DATE(appdate) = DATE('$curdate')")->fetchColumn();
                                                  $drc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                                                  $dsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
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
                                                  

                                                  $wapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1  and `uid` = $uid and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                                                  $wrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  $wsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
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
                                                  
                                                  $mapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1  and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                                                  $mrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  $msub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
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
                                                  
                                                  $yapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1  and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                                                  $yrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  $ysub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                                                    if( date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }                                                 
                                                  $yeci_num = $c;
                                                  
                                                }                                             
                                                
                                                
                                                ?>

                                                <td> <a href="trackersmcmd.php?appsd_id=<?php echo $uid?>"><?php echo $dapp_num; ?> </a></td>
                                                <td> <a href="trackersmcmd.php?rcsd_id=<?php echo $uid?>"><?php echo $drc_num; ?></a></td> 
                                               <td> <a href="trackersmcmd.php?subsd_id=<?php echo $uid?>"><?php echo $dsub_num; ?></a></td> 
                                                <td> <a href="trackersmcmd.php?ecisd_id=<?php echo $uid?>"><?php echo $deci_num; ?></a></td> 

                                                <td> <a href="trackersmcmd.php?appsw_id=<?php echo $uid?>"><?php echo $wapp_num; ?></a></td>
                                                <td> <a href="trackersmcmd.php?rcsw_id=<?php echo $uid?>"><?php echo $wrc_num; ?></a></td> 
                                               <td> <a href="trackersmcmd.php?subsw_id=<?php echo $uid?>"><?php echo $wsub_num; ?></a></td> 
                                                <td> <a href="trackersmcmd.php?ecisw_id=<?php echo $uid?>"><?php echo $weci_num; ?></a></td> 
                                                
                                                <td> <a href="trackersmcmd.php?appsm_id=<?php echo $uid?><?php if($olddate==1) { ?>&date=<?php echo $curdate; ?>" <?php } ?>><?php echo $mapp_num; ?></a></td>
                                                <td> <a href="trackersmcmd.php?rcsm_id=<?php echo $uid?>"><?php echo $mrc_num; ?></a></td> 
                                               <td> <a href="trackersmcmd.php?subsm_id=<?php echo $uid?>"><?php echo $msub_num; ?></a></td> 
                                                <td> <a href="trackersmcmd.php?ecism_id=<?php echo $uid?>"><?php echo $meci_num; ?></a></td> 
                                                
                                                <td> <a href="trackersmcmd.php?appsy_id=<?php echo $uid?>"><?php echo $yapp_num; ?></a></td>
                                                <td> <a href="trackersmcmd.php?rcsy_id=<?php echo $uid?>"><?php echo $yrc_num; ?></a></td> 
                                               <td> <a href="trackersmcmd.php?subsy_id=<?php echo $uid?>"><?php echo $ysub_num; ?></a></td> 
                                                <td> <a href="trackersmcmd.php?ecisy_id=<?php echo $uid?>"><?php echo $yeci_num; ?></a></td> 

                                            </tr>
                                                    <?php
                                                } 
                                                
                    ?>


						   
            <tr>
                                      
                                      <td></td>
                                      <td>Total</td>
<?php

                  $conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
                  //$curdate =date('Y-m-d');
                  
                  if($dta['level'] == 1 || $dta['level'] == 2)
                  {
                    $qeci = "select distinct app_id from eci where `eci_happened` = 1 and `eci_round` = 3 and `status` = 1 and YEAR(eci_date) = YEAR('$curdate')";
                    $ins= $conn->prepare($qeci);
                    $ins->execute();
                    $deci = $ins->fetchAll();
                    
                    $dtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `status` = 1 and DATE(appdate) = DATE('$curdate')")->fetchColumn();
                    $dtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                    $dtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `subdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                //    $dteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and DATE(eci_date) = DATE('$curdate')")->fetchColumn();
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



                    $wtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                    $wtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    $wtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    //$wteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and WEEK(eci_date) = WEEK('$curdate') and YEAR(eci_date) = YEAR('$curdate')")->fetchColumn();
                  
                    $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                                                    if(date("W",strtotime($date)) == date("W",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $wteci_num = $c;



                    $mtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                    $mtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    $mtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                   
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

                    $ytapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                    $ytrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    $ytsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();


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
                  else
                  {
                   
                    
                    $qeci = "select distinct app_id from eci where sm_id = $uid and `eci_happened` = 1 and `eci_round` = 3 and `status` = 1 and YEAR(eci_date) = YEAR('$curdate')";
                    $ins= $conn->prepare($qeci);
                    $ins->execute();
                    $deci = $ins->fetchAll();

                    $dtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1 and `uid` = $uid and DATE(appdate) = DATE('$curdate')")->fetchColumn();
                    $dtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                    $dtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and `uid` = $uid and DATE(subdate) = DATE('$curdate')")->fetchColumn();
                    //$dteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and `sm_id` = $uid and DATE(eci_date) = DATE('$curdate')")->fetchColumn();
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


                    $wtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and `uid` = $uid and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                    $wtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    $wtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    //$wteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and `sm_id` = $uid and WEEK(eci_date) = WEEK('$curdate') and YEAR(eci_date) = YEAR('$curdate')")->fetchColumn();
                    $c=0;
                    foreach($deci as $ueci)
                    { $a = $ueci['app_id'];
                      $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
                      if(date("W",strtotime($date)) == date("W",strtotime(date("m/d/y"))) && date("m",strtotime($date)) == date("m",strtotime(date("m/d/y"))) && date("y",strtotime($date)) == date("y",strtotime(date("m/d/y"))) )
                      {
                        $c++;
                      }
                    }
                    $wteci_num = $c;


                    $mtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and `uid` = $uid and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                    $mtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `uid` = $uid and `status`= 1 and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    $mtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and `uid` = $uid and MONTH(rcdate) = MONTH('$curdate') and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    //$mteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and `sm_id` = $uid and MONTH(eci_date) = MONTH('$curdate') and YEAR(eci_date) = YEAR('$curdate')")->fetchColumn();
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


                    $ytapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and `uid` = $uid and YEAR(appdate) = YEAR('$curdate')")->fetchColumn();
                    $ytrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `uid` = $uid and `status`= 1 and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    $ytsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and `uid` = $uid and YEAR(rcdate) = YEAR('$curdate')")->fetchColumn();
                    //$mteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and `sm_id` = $uid and MONTH(eci_date) = MONTH('$curdate') and YEAR(eci_date) = YEAR('$curdate')")->fetchColumn();
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


?>
                                                <td> <?php echo $dtapp_num; ?></td>
                                                <td> <?php echo $dtrc_num; ?></td> 
                                                <td> <?php echo $dtsub_num; ?></td> 
                                                <td> <?php echo $dteci_num; ?></td> 

                                                <td> <?php echo $wtapp_num; ?></td>
                                                <td> <?php echo $wtrc_num; ?></td> 
                                                <td> <?php echo $wtsub_num; ?></td> 
                                                <td> <?php echo $wteci_num; ?></td> 

                                                <td> <?php echo $mtapp_num; ?></td>
                                                <td> <?php echo $mtrc_num; ?></td> 
                                                <td> <?php echo $mtsub_num; ?></td> 
                                                <td> <?php echo $mteci_num; ?></td> 

                                                <td> <?php echo $ytapp_num; ?></td>
                                                <td> <?php echo $ytrc_num; ?></td> 
                                                <td> <?php echo $ytsub_num; ?></td> 
                                                <td> <?php echo $yteci_num; ?></td> 
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
