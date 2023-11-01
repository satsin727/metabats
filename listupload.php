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

$conn=null;
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from users where `uid` = :u";
$ins= $conn->prepare($query);
$ins->bindValue( ":u", $sessid, PDO::PARAM_STR );
$ins->execute();
$dta = $ins->fetch();
$userid=$dta['uid'];

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
$selected = "listall";
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3 )
{
		$listid= $_POST['lid'];
		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$query ="Select * FROM lists where listid = $listid";
		$ins= $conn->prepare($query);
		$ins->execute();	
		$udata = $ins->fetch();
		$conn = null;
		$status = $udata['status'];
		$count = $udata['total'];
if($udata['uid']==$userid || $dta['level'] == 1 || 	$dta['level'] == 2 )
		{
		
//upload content

			if(isset($_POST['submit']))
			{ 

			$lname=$_FILES['file']['name'];
			$lext = strtolower(substr(strrchr($lname, '.'), 1));
			if($lext == "csv" || $rext == "txt")
			{
				$digits = 6;
				$unumber = rand(pow(10, $digits-1), pow(10, $digits)-1);
				$date = date("Y-m-d H:i:s");
				$lfname = basename($_FILES['file']['name'],$lext);
				$file= $listid."-".date("m-d-Y", strtotime($date) )."-".$unumber."-".$lfname.$lext;
				$target = "files/lists/".$file;										
				move_uploaded_file($_FILES["file"]["tmp_name"], $target);
			}
			ini_set('auto_detect_line_endings',TRUE);
			$handle = fopen($target,'r');
while ( ($data = fgetcsv($handle) ) !== FALSE ) {
			$status = 1;
			$col1 = $data[0];
            $col2 = $data[1];
			$col3 = $data[2];
            $col4 = trim(trim($data[3],"\'\"[]~`;:\t%")," ");    
            if (!filter_var($col4, FILTER_VALIDATE_EMAIL)) 
            {
  			$col4 = "Invalid Email Address";
  			$status = 0;
			}
			$col5 = $data[4];
            $col6 = $data[5];
            $col7 = $data[6];
			$col8 = $data[7];
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			if($status!=0)
			{
			$csql = "SELECT COUNT(*) FROM `clients` WHERE `lid` = '$listid' AND `uid` = '$userid' AND `status` = 1 AND `remail` LIKE '$col4'";
			$cinsql = $conn->prepare($csql);
			$cinsql->execute();
			$rowcounts =  $cinsql->fetchColumn();
			if($rowcounts == 0)
			{ 
			$sql = "INSERT into clients (`cid`, `lid`, `uid`, `companyname`, `rname`, `rfname`, `remail`, `rphone`, `rlocation`, `rtimezon`, `tier`, `status`, `filetarget`) values (Null, '$listid', '$userid', '$col1','$col2','$col3','$col4','$col5','$col6','$col7','$col8','$status','$target')";
			
			$count= $count+1;
			
			$insl= $conn->prepare($sql);



			$insl->execute();
			$totalupdatequery ="UPDATE `lists` SET `total` = '$count' WHERE `listid` = '$listid';";
			$ins= $conn->prepare($totalupdatequery);
			$ins->execute(); 
			}
			else
			{
				$sql = "UPDATE clients SET uid = '$userid', `companyname` = '$col1', `rname` = '$col2', `rfname` = '$col3', `rphone`= '$col5' where remail = '$col4'";
				$insl= $conn->prepare($sql);
				$insl->execute();
			}

			}
			$conn = null;	

} //end while
echo "<script>
alert('List Successfully Uploaded.');
window.location.href='admin.php?action=listall';
</script>";

	}//post submit	 
//upload end content

}		
	 
	

} //for admin
else
{
	echo "<script>
alert('You Need to be Authorised to view this page.');
window.location.href='admin.php';
</script>"; 
}

?>
</div>

<?php
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>