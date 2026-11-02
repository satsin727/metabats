<?php
require( "config.php" );
require_once __DIR__ . '/db_helpers.php'; // shared DB helpers (also loaded by config.php)
if($_SESSION['id'])
{
$sessid = $_SESSION['id'];
}
else
{
	header( "Location: admin.php" ); exit; 

}

$conn=null;
$conn = bats_db();
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
echo '<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">';
if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3 )
{
		$listid= $_POST['lid'];
		$conn = bats_db();
		$query =("Select * FROM lists where listid = " . bats_q($listid));
		$ins= $conn->prepare($query);
		$ins->execute();	
		$udata = $ins->fetch();
		$conn = null;
		$status = $udata['status'];
		$count = $udata['total'];
if($udata['uid']==$userid || $dta['level'] == 1 || 	$dta['level'] == 2 )
		{
		
//upload content

			if(isset($_POST['save']))
			{ 

			$status = 1;
			$col1 = $_POST['companyname'];
            $col2 = $_POST['rname'];
            $col3 = trim(trim($_POST['remail'],"\'\"[]~`;:\t%")," ");    
            if (!filter_var($col3, FILTER_VALIDATE_EMAIL)) 
            {
  			$col3 = "Invalid Email Address";
  			$status = 0;
			}
			$domain = substr($col3, strpos($col3, '@') + 1);
			$col4 = $_POST['rphone'];
			$col5 = $_POST['rlocation'];
            $col6 = $_POST['rtimezon'];
            $col7 = $_POST['tier'];
			$col8 = $_POST['rfname'];
			$target = "manual";
			$conn = bats_db();

			$csql = ("SELECT COUNT(*) FROM `clients` WHERE `lid` = " . bats_q("$listid") . " AND `uid` = " . bats_q("$userid") . " AND `status` = 1 AND `remail` LIKE " . bats_q("$col3"));
			$cinsql = $conn->prepare($csql);
			$cinsql->execute();
			$rowcounts =  $cinsql->fetchColumn();
			if($rowcounts == 0)
			{ 
			$sql = ("INSERT into clients (`cid`, `lid`, `uid`, `companyname`, `rname`, `remail`, `domain`, `rphone`, `rlocation`, `rtimezon`, `tier`, `rfname` , `status`, `filetarget`) values (Null, " . bats_q("$listid") . ", " . bats_q("$userid") . ", " . bats_q("$col1") . "," . bats_q("$col2") . "," . bats_q("$col3") . ", " . bats_q("$domain") . "," . bats_q("$col4") . "," . bats_q("$col5") . "," . bats_q("$col6") . "," . bats_q("$col7") . "," . bats_q("$col8") . "," . bats_q("$status") . "," . bats_q("$target") . ")");
			if($status!=0)
			{
			$count= $count+1;
			}
			$insl= $conn->prepare($sql);
			$insl->execute();
			$totalupdatequery =("UPDATE `lists` SET `total` = " . bats_q("$count") . " WHERE `listid` = " . bats_q("$listid") . ";");
			$ins= $conn->prepare($totalupdatequery);
			$ins->execute(); }
			$conn = null;	


	}//post submit	 
//upload end content
echo "<script>
alert('Contact Successfully Added.');
window.location.href='admin.php?action=listall';
</script>";
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