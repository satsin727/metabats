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


if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

		if(isset($_POST['addapp']))
		{	
			$uid = $_POST['uid'];
			$reqid = $_POST['reqid'];
//			$rate = $_POST['rate'];
			$consultant_id = $_POST['cid'];
			$data = $conn->query(("select * from req where `reqid` = " . bats_q($reqid)))->fetch();
			$client_id = $data['cid'];	
			$currentdatetime =date('Y-m-d H:i:s');

			$isdual = $conn->query(("SELECT COUNT(*) FROM `app_data` WHERE `reqid`= " . bats_q($reqid) . " and `uid` = " . bats_q($uid) . " and `consultant_id` = " . bats_q($consultant_id)))->fetchColumn();
			if($isdual!=0)
			{
				echo "<script>alert('Application Already Added. Avoid multiple application');window.close();</script>"; 
			}
			else
			{
			$conn->query(("INSERT INTO `app_data` (`uid`, `reqid`, `client_id`, `consultant_id`, `appdate`) VALUES ($uid, $reqid, $client_id, $consultant_id, " . bats_q("$currentdatetime") . ")"));
			echo "<script>alert('Application Added.');window.close();</script>"; }
		}
		else
		{
			if(isset($_GET['reqid']))
			{
				$reqid=$_GET['reqid'];
			}	

			$data = $conn->query(("select * from req where `reqid` = " . bats_q($reqid)))->fetch();
			$skillid = $data['skillid'];


			if($dta['level'] ==1 || $dta['level'] == 2)
				{
					$qcid = ("select * from consultants where status = 1 and skill = " . bats_q($skillid) . " order by cfname asc");
				}
			else 
				{ 
					$rmid = $conn->query(("select rmid from users where uid = " . bats_q($sessid)))->fetchColumn();
					$qcid = ("select * from assigned AS A INNER JOIN consultants AS B ON A.cid = B.cid where uid = " . bats_q($rmid) . " and B.skill = " . bats_q($skillid) . " and B.status = 1 order by B.cfname asc"); 
				}	

			$cq= $conn->prepare($qcid);
			$cq->execute();
			$cdta = $cq->fetchAll();
		
			?>
		<form action="#" method="post">
			<tr><br><td><label>SM:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> </td>
				<td><?php echo $dta['name']; ?> </td> <br></tr><br>
			<tr><td><label>Consultant:</label> </td>
				<td> 
				<select name="cid" >
									<?php
								foreach( $cdta as $row) { ?>
										<option value="<?php echo $row['cid']; ?>"><?php echo $row['cfname']." ".$row['clname']; ?></option>
									<?php } ?></select>
				</td> 
			</tr>
				<br> <input type="hidden" name="reqid" value="<?php echo $reqid; ?>">
				<br> <input type="hidden" name="uid" value="<?php echo $dta['uid']; ?>">
			<tr>
				<br>
			<!--	<td>
				<label>Rate:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
								$<input name="rate" class="form-control-in" placeholder="Rate in numbers">/hr
								</td> -->
				<br>
								</tr>
			<tr>
			<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td class="button">
		<button type="submit" name="addapp">Add Application</button> </td>
			</tr>
		</form>


		<?php
		}
		$conn=null;

}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>