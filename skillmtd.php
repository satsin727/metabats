<?php

//MTD Skill wise Data - test mode

//MTD consultant wise Data

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

if($dta['level'] == 1 || $dta['level'] == 2)
{
    $uid = $dta['uid'];

require("includes/header.php");

    if(isset($_POST['date']))
    {
        $cdate = $_POST['date'];
    }
    else
    {
        $cdate = date("m/d/y");
    }
    $cdate = strtotime($cdate);
    $cdate = date('Y-m-d H:i:s',$cdate);
?>
<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active"><h3><?php echo "Monthly Reports for ".date('F, Y',strtotime($cdate)); ?>
                </h3></li>
			</ol>
</div>

<div class="row">
			<div class="col-lg-12">
			<div class="panel panel-default">
            <!-- <div class="panel-heading"> <a href="admin.php?action=showreports"><button name="back" class="btn btn-primary">Back</button></a></div> -->
            <div class="panel-heading"> <td width="90%" align="left" valign="top"> <form action="" method="post"><input name="date" id="datepicker"> <button name="submit" class="btn btn-primary">Submit</button></td> <td> Current date: <?php echo date("m/d/y",strtotime($cdate)); ?> </div>
            <div class="panel-body">

<br>
<table border="1" cellpadding="1" cellspacing="1" style="width:500px">

    <thead>
		 <tr>
            <th>Skill</th>
            <th>App</th>
			<th>RC</th>
			<th>Sub</th>            
			<th>ECI</th>
        </tr>
    <tbody>
        <?php
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$q = "select * from skill";
$ins= $conn->prepare($q);
$ins->execute();
$data = $ins->fetchAll();

foreach( $data as $row) 
{

    $skill = $row['sid'];
    $app = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and B.skill = $skill and ( MONTH(A.appdate) = MONTH('$cdate') AND YEAR(A.appdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
    $rc = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and B.skill = $skill and rcdone = 1 and ( MONTH(A.rcdate) = MONTH('$cdate') AND YEAR(A.rcdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
    $sub = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and B.skill = $skill and rcdone = 1 and subdone = 1 and( MONTH(A.rcdate) = MONTH('$cdate') AND YEAR(A.rcdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
        $qeci = "select distinct app_id from eci where `eci_happened` =1  and `eci_round` = 3  and `status` = 1 and skill_id = $skill";
        $ins= $conn->prepare($qeci);
        $ins->execute();
        $deci = $ins->fetchAll();
        $c=0;
        foreach($deci as $ueci)
        { $a = $ueci['app_id'];
        $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
        if( date("m",strtotime($date)) == date("m",strtotime($cdate))  && date("y",strtotime($date)) == date("y",strtotime($cdate)) )
        {
            $c++;
        }
        }
        $eci = $c; 
        $m= date("m",strtotime($cdate));
        $y= date("Y",strtotime($cdate));
        ?>
        <tr>
                    <td><?php echo $row['skillname']; ?></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&s=<?php echo $skill; ?>&app=1"><?php echo $app; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&s=<?php echo $skill; ?>&rc=1"><?php echo $rc; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&s=<?php echo $skill; ?>&sub=1"><?php echo $sub; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&s=<?php echo $skill; ?>&eci=1"><?php echo $eci; ?></a></td>
        </tr>
        <?php 

} ?>

<?php
 $mtapp = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and  ( MONTH(A.appdate) = MONTH('$cdate') AND YEAR(A.appdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
 $mtrc = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and rcdone = 1 and ( MONTH(A.rcdate) = MONTH('$cdate') AND YEAR(A.rcdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
 $mtsub = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and rcdone = 1 and subdone = 1 and( MONTH(A.rcdate) = MONTH('$cdate') AND YEAR(A.rcdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
    $qeci = "select distinct app_id from eci where `eci_happened` =1  and `eci_round` = 3  and `status` = 1";
    $ins= $conn->prepare($qeci);
    $ins->execute();
    $deci = $ins->fetchAll();
    $c=0;
    foreach($deci as $ueci)
    { $a = $ueci['app_id'];
    $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
    if( date("m",strtotime($date)) == date("m",strtotime($cdate))  && date("y",strtotime($date)) == date("y",strtotime($cdate)) )
    {
        $c++;
    }
    }
 $mteci = $c;

?>
        <tr>
                    <td>Total:</td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&app=1"><?php echo $mtapp; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&rc=1"><?php echo $mtrc; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&sub=1"><?php echo $mtsub; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&eci=1"><?php echo $mteci; ?></a></td>
        </tr>


		
	</tbody>
</table>

<p>&nbsp;</p>
<?php echo "<h2> Daily Data</h2>"; ?>

<table border="1" cellpadding="1" cellspacing="1" style="width:500px">

    <thead>
		 <tr>
            <th>Skill</th>
            <th>Reqs</th>
            <th>worked</th>
            <th>App</th>
			<th>RC</th>
			<th>Sub</th>            
			<th>ECI</th>
        </tr>
    <tbody>
        <?php
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$q = "select * from skill";
$ins= $conn->prepare($q);
$ins->execute();
$data = $ins->fetchAll();

foreach( $data as $row) 
{

    $skill = $row['sid'];
    $reqs = $conn->query("select COUNT(distinct ureq_id) from req where status = 1 and skillid = $skill and ( DATE(datetime) = DATE('$cdate') AND MONTH(datetime) = MONTH('$cdate') AND YEAR(datetime) = YEAR('$cdate') ) order by datetime asc")->fetchColumn();
    $worked = $conn->query("select COUNT(distinct A.ureq_id) from req AS A LEFT JOIN app_data AS B ON A.reqid= B.reqid where A.status = 1 and B.status = 1 and A.skillid = $skill AND B.rcdone =1 and ( DATE(A.datetime) = DATE('$cdate') AND MONTH(A.datetime) = MONTH('$cdate') AND YEAR(A.datetime) = YEAR('$cdate') ) order by A.datetime asc")->fetchColumn();
    
    $app = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and B.skill = $skill and ( DATE(A.appdate) = DATE('$cdate') AND MONTH(A.appdate) = MONTH('$cdate') AND YEAR(A.appdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
    $rc = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and B.skill = $skill and rcdone = 1 and ( DATE(A.rcdate) = DATE('$cdate') AND MONTH(A.rcdate) = MONTH('$cdate') AND YEAR(A.rcdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
    $sub = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and B.skill = $skill and rcdone = 1 and subdone = 1 and( DATE(A.rcdate) = DATE('$cdate') AND MONTH(A.rcdate) = MONTH('$cdate') AND YEAR(A.rcdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
        $qeci = "select distinct app_id from eci where `eci_happened` =1  and `eci_round` = 3  and `status` = 1 and skill_id = $skill";
        $ins= $conn->prepare($qeci);
        $ins->execute();
        $deci = $ins->fetchAll();
        $c=0;
        foreach($deci as $ueci)
        { $a = $ueci['app_id'];
        $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
        if( date("d",strtotime($date)) == date("d",strtotime($cdate)) && date("m",strtotime($date)) == date("m",strtotime($cdate))  && date("y",strtotime($date)) == date("y",strtotime($cdate)) )
        {
            $c++;
        }
        }
        $eci = $c; 
        $m= date("m",strtotime($cdate));
        $y= date("Y",strtotime($cdate));
        ?>
        <tr>

                    <td><?php echo $row['skillname']; ?></td>
                    
                    <td><?php echo $reqs; ?></a></td>
                    <td><?php echo $worked; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&s=<?php echo $skill; ?>&app=1"><?php echo $app; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&s=<?php echo $skill; ?>&rc=1"><?php echo $rc; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&s=<?php echo $skill; ?>&sub=1"><?php echo $sub; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&s=<?php echo $skill; ?>&eci=1"><?php echo $eci; ?></a></td>
        </tr>
        <?php 

} ?>

<?php
 $mtapp = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and  ( DATE(A.appdate) = DATE('$cdate') AND MONTH(A.appdate) = MONTH('$cdate') AND YEAR(A.appdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
 $mtrc = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and rcdone = 1 and ( DATE(A.rcdate) = DATE('$cdate') AND MONTH(A.rcdate) = MONTH('$cdate') AND YEAR(A.rcdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
 $mtsub = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and rcdone = 1 and subdone = 1 and( DATE(A.rcdate) = DATE('$cdate') AND MONTH(A.rcdate) = MONTH('$cdate') AND YEAR(A.rcdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
    $qeci = "select distinct app_id from eci where `eci_happened` =1  and `eci_round` = 3  and `status` = 1";
    $ins= $conn->prepare($qeci);
    $ins->execute();
    $deci = $ins->fetchAll();
    $c=0;
    foreach($deci as $ueci)
    { $a = $ueci['app_id'];
    $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
    if( date("d",strtotime($date)) == date("d",strtotime($cdate)) && date("m",strtotime($date)) == date("m",strtotime($cdate))  && date("y",strtotime($date)) == date("y",strtotime($cdate)) )
    {
        $c++;
    }
    }
 $mteci = $c;

?>
        <tr>
                    <td>Total:</td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&app=1"><?php echo $mtapp; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&rc=1"><?php echo $mtrc; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&sub=1"><?php echo $mtsub; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&eci=1"><?php echo $mteci; ?></a></td>
        </tr>


		
	</tbody>
</table>


<p>&nbsp;</p>
<?php echo "<h2> Weekly Data</h2>"; ?>

<table border="1" cellpadding="1" cellspacing="1" style="width:500px">

    <thead>
		 <tr>
            <th>Skill</th>
            <th>App</th>
			<th>RC</th>
			<th>Sub</th>            
			<th>ECI</th>
        </tr>
    <tbody>
        <?php
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$q = "select * from skill";
$ins= $conn->prepare($q);
$ins->execute();
$data = $ins->fetchAll();

foreach( $data as $row) 
{

    $skill = $row['sid'];
    $app = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and B.skill = $skill and ( WEEK(A.appdate) = WEEK('$cdate') AND MONTH(A.appdate) = MONTH('$cdate') AND YEAR(A.appdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
    $rc = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and B.skill = $skill and rcdone = 1 and ( WEEK(A.rcdate) = WEEK('$cdate') AND MONTH(A.rcdate) = MONTH('$cdate') AND YEAR(A.rcdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
    $sub = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and B.skill = $skill and rcdone = 1 and subdone = 1 and( WEEK(A.rcdate) = WEEK('$cdate') AND MONTH(A.rcdate) = MONTH('$cdate') AND YEAR(A.rcdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
        $qeci = "select distinct app_id from eci where `eci_happened` =1  and `eci_round` = 3  and `status` = 1 and skill_id = $skill";
        $ins= $conn->prepare($qeci);
        $ins->execute();
        $deci = $ins->fetchAll();
        $c=0;
        foreach($deci as $ueci)
        { $a = $ueci['app_id'];
        $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
        if( date("W",strtotime($date)) == date("W",strtotime($cdate)) && date("m",strtotime($date)) == date("m",strtotime($cdate))  && date("y",strtotime($date)) == date("y",strtotime($cdate)) )
        {
            $c++;
        }
        }
        $eci = $c; 
        $m= date("m",strtotime($cdate));
        $y= date("Y",strtotime($cdate));
        ?>
        <tr>
                    <td><?php echo $row['skillname']; ?></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&s=<?php echo $skill; ?>&app=1"><?php echo $app; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&s=<?php echo $skill; ?>&rc=1"><?php echo $rc; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&s=<?php echo $skill; ?>&sub=1"><?php echo $sub; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&s=<?php echo $skill; ?>&eci=1"><?php echo $eci; ?></a></td>
        </tr>
        <?php 

} ?>

<?php
 $mtapp = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and  ( WEEK(A.appdate) = WEEK('$cdate') AND MONTH(A.appdate) = MONTH('$cdate') AND YEAR(A.appdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
 $mtrc = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and rcdone = 1 and ( WEEK(A.rcdate) = WEEK('$cdate') AND MONTH(A.rcdate) = MONTH('$cdate') AND YEAR(A.rcdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
 $mtsub = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status = 1 and rcdone = 1 and subdone = 1 and( WEEK(A.rcdate) = WEEK('$cdate') AND MONTH(A.rcdate) = MONTH('$cdate') AND YEAR(A.rcdate) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
    $qeci = "select distinct app_id from eci where `eci_happened` =1  and `eci_round` = 3  and `status` = 1";
    $ins= $conn->prepare($qeci);
    $ins->execute();
    $deci = $ins->fetchAll();
    $c=0;
    foreach($deci as $ueci)
    { $a = $ueci['app_id'];
    $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
    if( date("W",strtotime($date)) == date("W",strtotime($cdate)) && date("m",strtotime($date)) == date("m",strtotime($cdate))  && date("y",strtotime($date)) == date("y",strtotime($cdate)) )
    {
        $c++;
    }
    }
 $mteci = $c;

?>
        <tr>
                    <td>Total:</td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&app=1"><?php echo $mtapp; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&rc=1"><?php echo $mtrc; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&sub=1"><?php echo $mtsub; ?></a></td>
                    <td><a href="fetchdata.php?m=<?php echo $m; ?>&y=<?php echo $y; ?>&eci=1"><?php echo $mteci; ?></a></td>
        </tr>


		
	</tbody>
</table>
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
