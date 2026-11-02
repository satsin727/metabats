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

  table, th, tr {
    margin:30px;
    width: -webkit-fill-available;
  }
  td {
    width: 50%;
    margin:30px;    
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
</div>

<!-- span16 --> 
<div class="span12">
<h2></h2>
<br>
</div>
  </div>
</div>

<?php

if(isset($_GET['id']))
{

require("../config.php");
require_once dirname(__DIR__) . '/db_helpers.php'; // shared DB helpers (also loaded by config.php)

if($_SESSION['id'])
{
$sessid = $_SESSION['id'];
}

$conn=null;
$conn = bats_db();
$query = "select * from users where `uid` = :u";
$ins= $conn->prepare($query);
$ins->bindValue( ":u", $sessid, PDO::PARAM_STR );
$ins->execute();
$dta = $ins->fetch();

$reqid=$_GET['id'];

$conn=null;
$conn = bats_db();
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
      $rquery = ("select * from app_data where `reqid` = " . bats_q($reqid));
      $rins= $conn->prepare($rquery);
      $rins->execute();
      $rdata = $rins->fetchAll();

      if($dta['level'] == 1 || $dta['level'] == 2) 
      { 

        $time = strtotime($data['datetime']); 
          $myFormatForView = date("m/d/y g:i A", $time); 
        echo "<br>Date: ".$myFormatForView;
          $uid = $data['uid'];
        echo "<br>SM name: ".bats_col('users', 'uid', $uid, 'name');
          $cur_date = date("dmy", $time); 
          $curweek = date("W", $time); 
	      echo "<br>Req ID: "."W".$curweek.$cur_date."-".$data['ureq_id']; ?><a target="_blank" href="../reqnoedit.php?do=editreqid&rid=<?php echo $data['reqid']; ?>"><img src="../images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /></a> <?php
          $cid = $data['cid'];
        echo "<br>BP Email: ".bats_col('clients', 'cid', $cid, 'remail');
        echo "<br>BP Phone: ".bats_col('clients', 'cid', $cid, 'rphone');
        echo "<br>Rate: "."$".$data['rrate']."/hr";
        echo "<br>End Client: ".$data['rend_client'];
          if($data['ttype'] == 1) { echo "<br>Tier 1 req? Yes"; } else { echo "<br>Tier 1 req? No"; }

          ?>

      </td>
     
        <td>

        <?php
            if($data['qualified']==1) 
            { echo "Change Status:"; } 
            else { echo "Change Status:";  } 
        
        ?>
        <a href="../reqcmd.php?do=<?php if($data['qualified']==1) { echo "nqupdate"; } else { echo "qupdate"; } ?>&id=<?php echo $data['reqid']; ?>" target="_blank"><?php if($data['qualified']==1) { echo "Qualified"; } else { echo "Not Qualified"; } ?></a>


        </td>
</tr>
<tr>   
    <td>
      <table>
      <tr>
        <td>
        <?php
          
          echo "<b>Applied consultant:</b><br>";
          foreach($rdata as $row)
          {
            $consultant_id = $row['consultant_id'];
            echo bats_col('consultants', 'cid', $consultant_id, 'cfname')." ".bats_col('consultants', 'cid', $consultant_id, 'clname'); 
            echo "<br>";
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
                echo bats_col('consultants', 'cid', $consultant_id, 'cfname')." ".bats_col('consultants', 'cid', $consultant_id, 'clname'); 
                echo "<br>";
              }
            }
          }

            ?>
        </td>
      </tr>
      </table>
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
                    $conn = bats_db();
                    $query = ("select * from comments as A INNER JOIN users as B ON A.uid = B.uid where `com_type` = 1 and `com_postid` = " . bats_q($reqid) . " order by datetime desc");
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
                      
                      $query = ("select * from comments as A INNER JOIN users as B ON A.uid = B.uid where (`com_postid` = " . bats_q($app_id) . ") and (`com_type` = 2 OR `com_type` = 3 OR `com_type` = 4 ) order by datetime desc");
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

 
</body></html>