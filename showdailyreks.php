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
          <div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="false" data-sort-name="name" data-sort-order="asc">
						    <thead>
						    <tr>
                                        <th data-field="ID">ID</th>
                                        <th data-field="Skill" data-sortable="true">Skill</th>
                                        <th data-field="name"  data-sortable="true">Name</th>
                                        <th data-field="dateadded" data-sortable="true">Date Added</th>                               
                                        <th data-field="tenure" data-sortable="true">Tenure</th> 
                                        <th data-field="Visa Status" data-sortable="true">Visa Status</th>
                                        <th data-field="dob" data-sortable="true">DOB</th>
                                        <th data-field="byear" data-sortable="true">Graduation Year</th>
                                        
                                        <th data-field="dapp" data-sortable="true">Today App</th>                          
                                       <th data-field="drc" data-sortable="true">Today RC</th>                         
                                       <th data-field="dsub" data-sortable="true">Today Sub</th> 
                                       <th data-field="deci" data-sortable="true">Today ECI</th> 

                                       <th data-field="wapp" data-sortable="true">Weekly App</th>                          
                                       <th data-field="wrc" data-sortable="true">Weekly RC</th>                         
                                       <th data-field="wsub" data-sortable="true">Weekly Sub</th> 
                                       <th data-field="weci" data-sortable="true">Weekly ECI</th>

                                       
                                       <th data-field="wapp" data-sortable="true">Monthly App</th>                          
                                       <th data-field="wrc" data-sortable="true">Monthly RC</th>                         
                                       <th data-field="wsub" data-sortable="true">Monthly Sub</th> 
                                       <th data-field="weci" data-sortable="true">Monthly ECI</th>
                                        
                </tr>
                </thead>
						    
                                    
                  <tbody>
                           <?php
                            if($dta['level'] == 1 || $dta['level'] == 2)
                              {
                              $query = "select * from consultants where `status` = 1 order by cfname asc";
                              }
                              else
                              {
                              $query = "select * from assigned AS A LEFT JOIN consultants AS B ON A.cid = B.cid where B.status =1 and A.uid = $uid order by B.cfname asc";
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
                                                  $dapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1 and DATE(appdate) = CURDATE()")->fetchColumn();
                                                  $drc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and DATE(rcdate) = CURDATE()")->fetchColumn();
                                                  $dsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `subdone` = 1 and `status`= 1 and DATE(rcdate) = CURDATE()")->fetchColumn();
                                                  $deci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and DATE(eci_date) = CURDATE()")->fetchColumn();
                                                  

                                                  $wapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1  and WEEK(appdate) = WEEK(CURDATE())")->fetchColumn();
                                                  $wrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK(CURDATE())")->fetchColumn();
                                                  $wsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `subdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK(CURDATE())")->fetchColumn();
                                                  $qeci = "select distinct app_id from eci where consultant_id = $cid and `eci_happened` =1 ";
                                                  $ins= $conn->prepare($qeci);
                                                  $ins->execute();
                                                  $deci = $ins->fetchAll();
                                                  $c=0;
                                                  foreach($deci as $ueci)
                                                  { $a = $ueci['app_id'];
                                                    $date = $conn->query("SELECT eci_date FROM `eci` WHERE `app_id`= $a")->fetchColumn();
                                                    if(date("W",strtotime($date)) == date("W",strtotime(date("m/d/y"))) )
                                                    {
                                                      $c++;
                                                    }
                                                  }
                                                  $weci_num = $c;
                                                //  $weci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK(CURDATE())")->fetchColumn();
                                                //  $weci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1")->fetchColumn();
                                                  
                                                $mapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1  and WEEK(appdate) = WEEK(CURDATE())-1")->fetchColumn();
                                                $mrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK(CURDATE())-1")->fetchColumn();
                                                $msub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `subdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK(CURDATE())-1")->fetchColumn();
                                                
                                                $meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK(CURDATE())-1")->fetchColumn();
                                                
                                              
                                              }
                                                else
                                                {
                                                 // $uid =  $_SESSION['id'];
                                                  $dapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1 and `uid` = $uid and DATE(appdate) = CURDATE()")->fetchColumn();
                                                  $drc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = CURDATE()")->fetchColumn();
                                                  $dsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `subdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = CURDATE()")->fetchColumn();
                                                  $deci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and DATE(eci_date) = CURDATE()")->fetchColumn();
                                                  

                                                  $wapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1  and `uid` = $uid and WEEK(appdate) = WEEK(CURDATE())")->fetchColumn();
                                                  $wrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK(CURDATE())")->fetchColumn();
                                                  $wsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `subdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK(CURDATE())")->fetchColumn();
                                                  $weci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and WEEK(eci_date) = WEEK(CURDATE())")->fetchColumn();
                                                  
                                                }                                             
                                                
                                                
                                                ?>

                                                <td> <a href="trackercmd.php?appcd_id=<?php echo $cid; ?>"><?php echo $dapp_num; ?></a></td>
                                                <td> <a href="trackercmd.php?rccd_id=<?php echo $cid; ?>"><?php echo $drc_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?subcd_id=<?php echo $cid; ?>"><?php echo $dsub_num; ?></a></td>
                                                <td> <a href="trackercmd.php?ecicd_id=<?php echo $cid; ?>"><?php echo $deci_num; ?></a></td>

                                                <td> <a href="trackercmd.php?appcw_id=<?php echo $cid; ?>"><?php echo $wapp_num; ?></a></td>
                                                <td> <a href="trackercmd.php?rccw_id=<?php echo $cid; ?>"><?php echo $wrc_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?subcw_id=<?php echo $cid; ?>"><?php echo $wsub_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?ecicw_id=<?php echo $cid; ?>"><?php echo $weci_num; ?></a></td> 

                                                
                                                <td> <a href="trackercmd.php?appcw_id=<?php echo $cid; ?>"><?php echo $mapp_num; ?></a></td>
                                                <td> <a href="trackercmd.php?rccw_id=<?php echo $cid; ?>"><?php echo $mrc_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?subcw_id=<?php echo $cid; ?>"><?php echo $msub_num; ?></a></td> 
                                                <td> <a href="trackercmd.php?ecicw_id=<?php echo $cid; ?>"><?php echo $meci_num; ?></a></td> 

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

                  $conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
                  
                  if($dta['level'] == 1 || $dta['level'] == 2)
                  {
                    $dtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `status` = 1 and DATE(appdate) = CURDATE()")->fetchColumn();
                    $dtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and DATE(rcdate) = CURDATE()")->fetchColumn();
                    $dtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `subdone` = 1 and `status`= 1 and DATE(rcdate) = CURDATE()")->fetchColumn();
                   // $dteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `eci_happened` =1 and `status` = 1 and DATE(eci_date) = CURDATE()")->fetchColumn();
                    $dteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `eci_happened` =1 and `status` = 1 and DATE(eci_date) = CURDATE()")->fetchColumn();
                    

                    $wtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and WEEK(appdate) = WEEK(CURDATE())")->fetchColumn();
                    $wtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK(CURDATE())")->fetchColumn();
                    $wtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK(CURDATE())")->fetchColumn();
                    $wteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK(CURDATE())")->fetchColumn();
                   // $wteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK(CURDATE())")->fetchColumn();
                    }
                  else
                  {
                    $dtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1 and `uid` = $uid and DATE(appdate) = CURDATE()")->fetchColumn();
                    $dtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = CURDATE()")->fetchColumn();
                    $dtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = CURDATE()")->fetchColumn();
                    $dteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and DATE(eci_date) = CURDATE()")->fetchColumn();
                    

                    $wtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and `uid` = $uid and WEEK(appdate) = WEEK(CURDATE())")->fetchColumn();
                    $wtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK(CURDATE())")->fetchColumn();
                    $wtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(subdate) = WEEK(CURDATE())")->fetchColumn();
                    $wteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and WEEK(eci_date) = WEEK(CURDATE())")->fetchColumn();
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
