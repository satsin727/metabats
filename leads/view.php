<!DOCTYPE html>
<html class="fa-events-icons-ready"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>Bench ATS - Metahorizon</title>
  
  <link rel="stylesheet" media="screen" href="application.css">

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
  <div class="span12"><style>
.search_jobs{
float:left;
padding-left:10px;
}
</style>
<?php

if(isset($_GET['id']))
{

require("../config.php");
$reqid=$_GET['id'];

$conn=null;
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from req where `reqid` = :reqid";
$ins= $conn->prepare($query);
$ins->bindValue( ":reqid", $reqid, PDO::PARAM_INT );
$ins->execute();
$data = $ins->fetch();
$queryl = "select * from jd where `reqid` = :reqid";
$ins1= $conn->prepare($queryl);
$ins1->bindValue( ":reqid", $reqid, PDO::PARAM_INT );
$ins1->execute();
$data1 = $ins1->fetch();
$query3 = "select * from skill where `sid` = :skillid";
$ins3= $conn->prepare($query3);
$ins3->bindValue( ":skillid", $data['skillid'], PDO::PARAM_INT );
$ins3->execute();
$data3 = $ins3->fetch();
?>
<table>
  <tr>
      <td>

      <?php   
      
      if($_SESSION['username'] == 1) 
      { 
         if($row['qualified']==1) 
         { echo "Qualified"; } 
         else { echo "Not Qualified"; } ?>
         <a href="../reqcmd.php?do=<?php if($row['qualified']==1) { echo "nqupdate"; } else { echo "qupdate"; } ?>&id=<?php echo $row['reqid']; ?>" target="_blank"><?php if($row['qualified']==1) { echo "Qualified"; } else { echo "Not Qualified"; } ?></a>
         
        <?php 
      } ?>


      </td>
  </tr>
  <tr>
      <td>
            <br><br>
      </td>
  </tr>

<tr>
      <td>
            <?php
            echo "<h2>".$data3['skillname']." - ".$data['rlocation']." - ".$data['rduration']."</h2><br>";
            echo $data1['rdesc'];
            ?>
      </td>
<td>
<?php

if($_SESSION['username'] == 1) 
      { 
                    $conn=null;
                    $conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
                    $query = "select * from comments as A INNER JOIN users as B ON A.uid = B.uid where `com_type` = 1 and `com_postid` = $reqid order by datetime desc";
                    $ins= $conn->prepare($query);
                    $ins->execute();
                    $data = $ins->fetchAll();

                    foreach($data as $row)
                    {
                      if($row['reqcom_id'] ==1) { echo "<h4><b>Req comments</b></h4>"; }
                      echo $row['datetime']." posted by ".$row['name']."<br>\n";
                      echo $row['comment']."\n<br><br>";
                    }

                    $rquery = "select * from app_data where `reqid` = $reqid";
                    $rins= $conn->prepare($rquery);
                    $rins->execute();
                    $rdata = $rins->fetchAll();

                    foreach($rdata as $comment)
                    {	
                      $app_id = $comment['app_id'];
                      $query = "select * from comments as A INNER JOIN users as B ON A.uid = B.uid where (`com_postid` = $app_id) and (`com_type` = 2 OR `com_type` = 3 OR `com_type` = 4 ) order by datetime desc";
                      $ins= $conn->prepare($query);
                      $ins->execute();
                      $data = $ins->fetchAll();

                      foreach($data as $row)
                      {
                        if($row['appcom_id'] ==1) { echo "<h4><b>Application comments</b></h4>"; }
                        else if($row['rccom_id'] ==1) { echo "<h4><b>RC comments.</b></h4>"; }
                        else if($row['subcom_id'] ==1) { echo "<h4><b>Submission comments.</b></h4>"; }

                        echo $row['datetime']." posted by ".$row['name']."<br>\n";
                        echo $row['comment']."\n<br><br>";
                      }
                    }
        }

?>
</td>
</tr>

<?php

}
else
{
echo "<script>
alert('Please select the requirement to view from the list !!!');
window.location.href='index.php';
</script>"; 	
}
?>
<!-- span16 --> 
    <div class="span12">
<h2></h2>
<br>
</div>
  </div>
</div>

 
</body></html>