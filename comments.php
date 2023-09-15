<?php
require( "config.php" );
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

/*

reqcom_id =  1
appcom_id =  2
rccom_id =   3
subcom_id =  4
ecicom_id =  5
pocom_id =   6 
conscom_id = 7

*/


if(isset($_GET['reqcom_id']))
{
	$com_postid=$_GET['reqcom_id'];
	$com_type=1;

}
elseif(isset($_GET['appcom_id']))
{
	$com_postid=$_GET['appcom_id'];
	$com_type=2;
}
elseif(isset($_GET['rccom_id']))
{
	$com_postid=$_GET['rccom_id'];
	$com_type=3;
}
elseif(isset($_GET['subcom_id']))
{
	$com_postid=$_GET['subcom_id'];
	$com_type=4;
}
elseif(isset($_GET['ecicom_id']))
{
	$com_postid=$_GET['ecicom_id'];
	$com_type=5;

}
elseif(isset($_GET['pocom_id']))
{
	$com_postid=$_GET['pocom_id'];
	$com_type=6;

}
elseif(isset($_GET['conscom_id']))
{
	$com_postid=$_GET['conscom_id'];
	$com_type=7;
}

if($com_type==1)
{
	$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
	$query = "select * from comments as A INNER JOIN users as B ON A.uid = B.uid where `com_type` = $com_type and `com_postid` = $com_postid order by datetime desc";
	$ins= $conn->prepare($query);
	$ins->execute();
	$data = $ins->fetchAll();

	foreach($data as $row)
	{
		if($row['reqcom_id'] ==1) { echo "<h4><b>Req comments</b></h4>"; }
		echo $row['datetime']." posted by ".$row['name']."<br>\n";
		echo $row['comment']."\n<br><br>";
	}

	$rquery = "select app_id from app_data where `reqid` = $com_postid";
	$rins= $conn->prepare($rquery);
	$rins->execute();
	$rdata = $rins->fetchAll();

	foreach($rdata as $comment)
	{	
		$app_id = $rdata['app_id'];
		$query = "select * from comments as A INNER JOIN users as B ON A.uid = B.uid where `com_postid` = $app_id and (`com_type` = 2 OR `com_type` = 3 OR `com_type` = 4 ) order by datetime desc";
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
else
{
	$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
	$query = "select * from comments as A INNER JOIN users as B ON A.uid = B.uid where `com_type` = $com_type and `com_postid` = $com_postid order by datetime desc";
	$ins= $conn->prepare($query);
	$ins->execute();
	$data = $ins->fetchAll();

	foreach($data as $row)
	{
		echo $row['datetime']." posted by ".$row['name']."<br>\n";
		echo $row['comment']."\n<br><br>";
	}
}

}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>