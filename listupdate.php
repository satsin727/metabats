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
			$cid= $_POST['cid'];
			$conn = bats_db();
			$query =("Select * FROM clients where cid = " . bats_q($cid));
			$ins= $conn->prepare($query);
			$ins->execute();	
			$udata = $ins->fetch();
			$status = 1;
            $target = "manual";
			
			$col1 = $_POST['companyname'];
            $col2 = $_POST['rname'];
			$col3 = trim(trim($_POST['remail'],"\'\"[]~`;:\t%")," ");
			$domain = substr($col3, strpos($col3, '@') + 1);
            $col4 = $_POST['rphone'];
			$col5 = $_POST['rlocation'];
            $col6 = $_POST['rtimezon'];
            $col7 = $_POST['tier'];
			$col8 = $_POST['rfname'];
			$conn = bats_db();

			$csql = ("SELECT COUNT(*) FROM `clients` WHERE `lid` = " . bats_q("$listid") . " AND `uid` = " . bats_q("$userid") . " AND  `remail` LIKE " . bats_q("$col3"));
			$cinsql = $conn->prepare($csql);
			$cinsql->execute();
			$rowcounts =  $cinsql->fetchColumn();
			if($rowcounts == 0)
			{
				$col3 = $udata['remail'];
			}
			$sql = ("UPDATE `clients` SET `companyname` = " . bats_q("$col1") . ", `rname` = " . bats_q("$col2") . ", `remail` = " . bats_q("$col3") . ", `domain` = " . bats_q("$domain") . ", `rphone` = " . bats_q("$col4") . ", `rlocation` = " . bats_q("$col5") . ", `rtimezon` = " . bats_q("$col6") . ", `tier` = " . bats_q("$col7") . ", `rfname` = " . bats_q("$col8") . " WHERE `cid` = :cid;");
			$insl= $conn->prepare($sql);
			$insl->bindValue( ":cid", $cid, PDO::PARAM_INT );
			$insl->execute();
			$conn = null;	


	}//post submit	 
//upload end content    
echo "<script>
window.location.href='listcmd.php?do=viewlist&lid=$listid';
alert('Contact Successfully Updated.');

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