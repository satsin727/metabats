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
					<div class="panel-heading"><!-- <a href="admin.php?action=postreq"><button name="addauser" class="btn btn-primary">Post Req</button></a>&nbsp;&nbsp;&nbsp;<a href="admin.php?action=addskill"><button name="addauser" class="btn btn-primary">Add Skill</button></a> --></div>
					<div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uid" data-sort-order="asc">
						    <thead>
						    <tr>
                                        <th data-field="ID">ID</th>
                                        <th data-field="Skill" data-sortable="true">Skill</th>
                                        <th data-field="name"  data-sortable="true">Name</th>
                                        <th data-field="dateadded" data-sortable="true">Date Added</th>                               
                                        <th data-field="tenure" >Tenure</th> 
                                     <!--   <th data-field="Visa Status" >Visa Status</th>
                                        <th data-field="dob" >DOB</th>
                                        <th data-field="byear" >Graduation Year</th>-->
                                        
                                       <th data-field="dapp" data-sortable="true">Today App</th>                          
                                       <th data-field="drc" data-sortable="true">Today RC</th>                         
                                       <th data-field="dsub" data-sortable="true">Today Sub</th> 
                                       <th data-field="deci" data-sortable="true">Today ECI</th> 

                                       
                                       <th data-field="wapp" data-sortable="true">Weekly App</th>                          
                                       <th data-field="wrc" data-sortable="true">Weekly RC</th>                         
                                       <th data-field="wsub" data-sortable="true">Weekly Sub</th> 
                                       <th data-field="weci" data-sortable="true">Weekly ECI</th> 
                                    </tr>
						    </thead>
						   <tbody>

                           <?php
                            if($dta['level'] == 1 || $dta['level'] == 2)
                              {
                              $query = "select * from consultants where `status` = 1 order by cfname asc";
                              }
                              else{
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
                                                                $todaydate = strtotime(date());
                                                                $diff = abs($todaydate - $time);
                                                                $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
                                                        ?>
                                                <td data-search="<?php echo $myFormatForView; ?>"> <?php echo $myFormatForView; ?></td>
                                                <td data-search="<?php echo $days; ?>"> <?php echo $days; ?></td>
                                                <?php
                                                $cid = $row['cid'];
                                                $conns = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );

                                                $dapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1 and DATE(appdate) = CURDATE()")->fetchColumn();
                                                $drc_num = $conn2->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and DATE(rcdate) = CURDATE()")->fetchColumn();
                                                $dsub_num = $conns->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `subdone` = 1 and `status`= 1 and DATE(subdate) = CURDATE()")->fetchColumn();
                                                $deci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and DATE(eci_date) = CURDATE()")->fetchColumn();
                                                

                                                $wapp_num = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1  and WEEK(appdate) = WEEK(CURDATE())")->fetchColumn();
                                                $wrc_num = $conn2->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status`= 1 and WEEK(rcdate) = WEEK(CURDATE())")->fetchColumn();
                                                $wsub_num = $conns->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `subdone` = 1 and `status`= 1 and WEEK(subdate) = WEEK(CURDATE())")->fetchColumn();
                                                $weci_num = $conn->query("SELECT COUNT(*) FROM `eci` WHERE `consultant_id`= $cid and `eci_happened` =1 and `status` = 1 and WEEK(eci_date) = WEEK(CURDATE())")->fetchColumn();
                                                                                                
                                                
                                                
                                                ?>

                                                <td> <?php echo $dapp_num; ?></td>
                                                <td> <?php echo $drc_num; ?></td> 
                                               <td> <?php echo $dsub_num; ?></td> 
                                                <td> <?php echo $deci_num; ?></td> 

                                                <td> <?php echo $wapp_num; ?></td>
                                                <td> <?php echo $wrc_num; ?></td> 
                                               <td> <?php echo $wsub_num; ?></td> 
                                                <td> <?php echo $weci_num; ?></td> 

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