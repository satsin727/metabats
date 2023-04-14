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
                                                  $dapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1 and DATE(appdate) = DATE('$curdate')")->fetchColumn();
                                                  $drc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                                                  $dsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                                                  $deci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `sm_id`= $uid and `eci_happened` =1 and `status` = 1 and DATE(eci_date) = DATE('$curdate')")->fetchColumn();
                                                  

                                                  $wapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1  and WEEK(appdate) = WEEK('$curdate')")->fetchColumn();
                                                  $wrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate')")->fetchColumn();
                                                  $wsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate')")->fetchColumn();
                                                  $weci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `sm_id`= $uid and `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK('$curdate')")->fetchColumn();
                                                  
                                                  $mapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1  and MONTH(appdate) = MONTH('$curdate')")->fetchColumn();
                                                  $mrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate')")->fetchColumn();
                                                  $msub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate')")->fetchColumn();
                                                  $meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `sm_id`= $uid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')")->fetchColumn();
                                                  
                                                
                                                }
                                                else
                                                {
                                                 // $uid =  $_SESSION['id'];
                                                  $dapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1 and `uid` = $uid and DATE(appdate) = DATE('$curdate')")->fetchColumn();
                                                  $drc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                                                  $dsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                                                  $deci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `sm_id`= $uid and `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and DATE(eci_date) = DATE('$curdate')")->fetchColumn();
                                                  

                                                  $wapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1  and `uid` = $uid and WEEK(appdate) = WEEK('$curdate')")->fetchColumn();
                                                  $wrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK('$curdate')")->fetchColumn();
                                                  $wsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK('$curdate')")->fetchColumn();
                                                  $weci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `sm_id`= $uid and `eci_happened` =1 and `status` = 1 and `sm_id` = $uid and WEEK(eci_date) = WEEK('$curdate')")->fetchColumn();
                                                  
                                                  $mapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `status` = 1  and MONTH(appdate) = MONTH('$curdate')")->fetchColumn();
                                                  $mrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate')")->fetchColumn();
                                                  $msub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `uid`= $uid and `subdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate')")->fetchColumn();
                                                  $meci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `sm_id`= $uid and `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')")->fetchColumn();
                                                  
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
                                                
                                                <td> <a href="trackersmcmd.php?appsm_id=<?php echo $uid?>"><?php echo $mapp_num; ?></a></td>
                                                <td> <a href="trackersmcmd.php?rcsm_id=<?php echo $uid?>"><?php echo $mrc_num; ?></a></td> 
                                               <td> <a href="trackersmcmd.php?subsm_id=<?php echo $uid?>"><?php echo $msub_num; ?></a></td> 
                                                <td> <a href="trackersmcmd.php?ecism_id=<?php echo $uid?>"><?php echo $meci_num; ?></a></td> 

                                            </tr>
                                                    <?php
                                                } 
                                                
                    ?>


						   
            <tr>
                                      
                                      <td></td>
                                      <td>Total</td>
<?php

                  $conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
                  $curdate =date('Y-m-d');
                  
                  if($dta['level'] == 1 || $dta['level'] == 2)
                  {
                    
                    $dtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `status` = 1 and DATE(appdate) = DATE('$curdate')")->fetchColumn();
                    $dtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                    $dtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `subdone` = 1 and `status`= 1 and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                    $dteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and DATE(eci_date) = DATE('$curdate')")->fetchColumn();
                    

                    $wtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and WEEK(appdate) = WEEK('$curdate')")->fetchColumn();
                    $wtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate')")->fetchColumn();
                    $wtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK('$curdate')")->fetchColumn();
                    $wteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and WEEK(eci_date) = WEEK('$curdate')")->fetchColumn();
                  
                    $mtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and MONTH(appdate) = MONTH('$curdate')")->fetchColumn();
                    $mtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate')")->fetchColumn();
                    $mtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH('$curdate')")->fetchColumn();
                    $mteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and MONTH(eci_date) = MONTH('$curdate')")->fetchColumn();
                  
                  
                  }
                  else
                  {

                    $dtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1 and `uid` = $uid and DATE(appdate) = DATE('$curdate')")->fetchColumn();
                    $dtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and `uid` = $uid and DATE(rcdate) = DATE('$curdate')")->fetchColumn();
                    $dtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and `uid` = $uid and DATE(subdate) = DATE('$curdate')")->fetchColumn();
                    $dteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and `sm_id` = $uid and DATE(eci_date) = DATE('$curdate')")->fetchColumn();
                    

                    $wtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and `uid` = $uid and WEEK(appdate) = WEEK('$curdate')")->fetchColumn();
                    $wtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK('$curdate')")->fetchColumn();
                    $wtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and `uid` = $uid and WEEK(rcdate) = WEEK('$curdate')")->fetchColumn();
                    $wteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and `sm_id` = $uid and WEEK(eci_date) = WEEK('$curdate')")->fetchColumn();
                 
                    $mtapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `status` = 1  and `uid` = $uid and MONTH(appdate) = MONTH('$curdate')")->fetchColumn();
                    $mtrc_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `rcdone` = 1 and `uid` = $uid and `status`= 1 and MONTH(rcdate) = MONTH('$curdate')")->fetchColumn();
                    $mtsub_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE  `subdone` = 1 and `status`= 1 and `uid` = $uid and MONTH(rcdate) = MONTH('$curdate')")->fetchColumn();
                    $mteci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE  `eci_happened` =1 and `eci_round` = 3 and `status` = 1 and `uid` = $uid and MONTH(eci_date) = MONTH('$curdate')")->fetchColumn();
                  
                 
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
