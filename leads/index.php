<!DOCTYPE html>
<html class="fa-events-icons-ready"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>Bench ATS - Metahorizon</title>
  
  <link rel="stylesheet" media="screen" href="application.css">
<script src="../js/jquery-1.12.4.js"></script>
<script src="../js/jquery.dataTables.min.js"></script>
<script src="../js/dataTables.bootstrap.min.js"></script>
<link href="../css/bootstrap.min.css" rel="stylesheet">

  <style>
    .breadcrumb{
      margin-left: 30px;
      margin-right: 30px;
    }
    .alert{
      margin-left: 28px;
    }
    .container{
      margin-left:10px;
      margin-left:20px;
      width: auto !important;
    }
  </style>
  
</head>
<body>

<div class="navbar navbar-fixed-top">
  <div class="navbar-inner">
    <div class="container" style="padding-left: 20px;">
      <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </a>
      <a class="brand" href="index.php">Bench ATS</a>

    </div>
  </div>
</div>


<div class="container">
 
  <div class="row">
  <ul class="breadcrumb">
        <li>
      <a href="index.php">Home</a> <span class="divider">&gt;</span>
    </li>
    <li class="active">Jobs</li>
  </ul>

<!-- span16 --> 
    <div class="span12"><style>
.search_jobs{
float:left;
padding-left:10px;
}
</style>
<h1>Jobs</h1>

<input type="hidden" name="direction" id="direction">
<input type="hidden" name="sort" id="sort">

<div id="list" style="clear:both;">
<div style="width:100%;">
  <div style="clear:both;float:left;"></div>
</div>
<div style="clear:both;"></div>
<?php

require("../config.php");
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$tdreqcount = "SELECT count(*) FROM `req` WHERE `skillid` = 6 and `datetime` >= CURDATE()";
$indcount= $conn->prepare($tdreqcount);
$indcount->execute();
$devopscount = $indcount->fetch();

$tjreqcount = "SELECT count(*) FROM `req` WHERE `skillid` = 7 and `datetime` >= CURDATE()";
$injcount= $conn->prepare($tjreqcount);
$injcount->execute();
$javacount = $injcount->fetch();

$tsreqcount = "SELECT count(*) FROM `req` WHERE `skillid` = 10 and `datetime` >= CURDATE()";
$inscount= $conn->prepare($tsreqcount);
$inscount->execute();
$sailcount = $inscount->fetch();

$tqreqcount = "SELECT count(*) FROM `req` WHERE `skillid` = 9 and `datetime` >= CURDATE()";
$inqcount= $conn->prepare($tqreqcount);
$inqcount->execute();
$qacount = $inqcount->fetch();

$tscureqcount = "SELECT count(*) FROM `req` WHERE `skillid` = 8 and `datetime` >= CURDATE()";
$insmcount= $conn->prepare($tscureqcount);
$insmcount->execute();
$scrumcount = $insmcount->fetch();

$tsecreqcount = "SELECT count(*) FROM `req` WHERE `skillid` = 12 and `datetime` >= CURDATE()";
$inseccount= $conn->prepare($tsecreqcount);
$inseccount->execute();
$seccount = $inseccount->fetch();

$tdsreqcount = "SELECT count(*) FROM `req` WHERE `skillid` = 11 and `datetime` >= CURDATE()";
$indscount= $conn->prepare($tdsreqcount);
$indscount->execute();
$dscount = $indscount->fetch();


echo "Total requirements: <br>Devops: ".$devopscount[0]."&nbsp;&nbsp;&nbsp;&nbsp;Java: ".$javacount[0]."&nbsp;&nbsp;&nbsp;&nbsp;Sailpoint: ".$sailcount[0]."&nbsp;&nbsp;&nbsp;&nbsp;QA: ".$qacount[0]."&nbsp;&nbsp;&nbsp;&nbsp;Scrum Master: ".$scrumcount[0]."&nbsp;&nbsp;&nbsp;&nbsp;Security Engineer: ".$seccount[0]."&nbsp;&nbsp;&nbsp;&nbsp;Data Science: ".$dscount[0];

 $query = "select * from req where `datetime` >= CURDATE()order by datetime desc";
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();
?>
<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="false" data-sort-name="uid" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="id" data-sortable="true">ID</th>
						        <th data-field="Datetime"  data-sortable="true">Datetime</th> 
						        <th data-field="Role"  data-sortable="true">Job Role</th>
						        <th data-field="Skill" data-sortable="true">Skill</th>
						        <th data-field="SM" data-sortable="true">SM</th>
						        <th data-field="action" data-sortable="true">Action</th>
						    </tr>
						    </thead>
						   <tbody>
<?php

$i=1;

foreach( $data as $row) { 	

$sid = $row['skillid'];
								$q2 = "select * from skill where `sid` = $sid";
								$ins3= $conn->prepare($q2);
								$ins3->execute(); 
								$dta2 = $ins3->fetch();
						
$uid = $row['uid'];
								$q3 = "select * from users where `uid` = $uid";
								$ins4= $conn->prepare($q3);
								$ins4->execute(); 
								$dta3 = $ins4->fetch();
						
	?>
    <tr>
  		<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
		<td data-search="<?php echo $row['datetime']; ?>"> <?php $time = strtotime($row['datetime']); $myFormatForView = date("m/d/y g:i A", $time); echo $myFormatForView; ?></td>
    	<td data-search="<?php echo $row['role']." ".$row['rlocation']; ?>"> <a href="view.php?id=<?php echo $row['reqid']; ?>"><?php echo $row['role']." - ".$row['rlocation']." - ".$row['rduration']; ?></a></td>
    	<td data-search="<?php echo $dta2['skillname']; ?>"> <?php echo $dta2['skillname']; ?></td>    	
    	<td data-search="<?php echo $dta3['name']; ?>"> <?php echo $dta3['name']; ?></td> 	
    	<td> <a class="btn btn-small btn_very_small btn-primary" href="#">Submit</a></td>       	

    </tr>
    <?php  //for if
} //foreach
$conn=null; 
?>
						   </tbody>
						</table>
					</div>
				</div>
			</div>
		</div>


</div>

<br>
</div>
  </div>
</div>

<script src="../js/jquery-1.11.1.min.js"></script>
	<script src="../js/bootstrap.min.js"></script>
	<script src="../js/chart.min.js"></script>
	<script src="../js/bootstrap-table.js"></script>
 
</body></html>