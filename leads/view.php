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

if($_SESSION['id'])
{
$sessid = $_SESSION['id'];
}

$conn=null;
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from users where `uid` = :u";
$ins= $conn->prepare($query);
$ins->bindValue( ":u", $sessid, PDO::PARAM_STR );
$ins->execute();
$dta = $ins->fetch();

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
      
      if($dta['level'] == 1 || $dta['level'] == 2) 
      { 
         if($data['qualified']==1) 
         { echo "Change Status:"; } 
         else { echo "Change Status:"; } ?>
         <a href="../reqcmd.php?do=<?php if($data['qualified']==1) { echo "nqupdate"; } else { echo "qupdate"; } ?>&id=<?php echo $data['reqid']; ?>" target="_blank"><?php if($data['qualified']==1) { echo "Qualified"; } else { echo "Not Qualified"; } ?></a>
         

      </td>
     
        <td>

        <?php

          $time = strtotime($data['datetime']); 
          $myFormatForView = date("m/d/y g:i A", $time); 
        echo "<br>Date: ".$myFormatForView;
          $uid = $data['uid'];
        echo "<br>SM name: ".$conn->query("SELECT name from users where uid = $uid")->fetchColumn();
          $cur_date = date("dmy", $time); 
          $curweek = date("W", $time); 
	      echo "<br>Req ID: "."W".$curweek.$cur_date."-".$data['ureq_id']; ?><a target="_blank" href="../reqnoedit.php?do=editreqid&rid=<?php echo $data['reqid']; ?>"><img src="../images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /></a> <?php
          $cid = $data['cid'];
        echo "<br>BP Email: ".$conn->query("SELECT remail from clients where cid = $cid")->fetchColumn();
        echo "<br>BP Phone: ".$conn->query("SELECT rphone from clients where cid = $cid")->fetchColumn();
        echo "<br>Rate: "."$".$data['rrate']."/hr";
        echo "<br>End Client: ".$data['rend_client'];
          if($data['ttype'] == 1) { echo "<br>Tier 1 req? Yes"; } else { echo "<br>Tier 1 req? No"; }

        }
        ?>

        </td>
      
<td>
    <?php
      $rquery = "select * from app_data where `reqid` = $reqid";
      $rins= $conn->prepare($rquery);
      $rins->execute();
      $rdata = $rins->fetchAll();
      echo "<b>Applied consultant:</b><br>";
      foreach($rdata as $row)
      {
        $consultant_id = $row['consultant_id'];
        echo $conn->query("SELECT cfname from consultants where cid = $consultant_id")->fetchColumn()." ".$conn->query("SELECT clname from consultants where cid = $consultant_id")->fetchColumn(); 
      }

    ?>
</td>
    <td>
      <?php
    echo "<b>RC consultants:</b><br>";
      foreach($rdata as $row)
      {
        if($row['rcdone']==1)
        {
          $consultant_id = $row['consultant_id'];
          echo $conn->query("SELECT cfname from consultants where cid = $consultant_id")->fetchColumn()." ".$conn->query("SELECT clname from consultants where cid = $consultant_id")->fetchColumn(); 
        }
      }

      ?>
    </td>

    </tr>
<tr>
      <td>
            <?php
if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3) 
{ 

            echo "<h2>".$data3['skillname']." - ".$data['rlocation']." - ".$data['rduration']."</h2><br>";
            echo $data1['rdesc'];
            ?>
      </td>
<td>
<?php


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