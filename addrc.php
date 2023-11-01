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


if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

		if(isset($_POST['addrc']))
		{	
			//$uid = $_POST['uid'];
			$app_id = $_POST['app_id'];
			$rate = $_POST['rate'];
			$t1ip_name = $_POST['t1ip_name'];
			$currentdatetime =date('Y-m-d H:i:s');
//			$ecname = $_POST['ecname'];
//			$data = $conn->query("select * from app_data where `app_id` = $app_id")->fetch();


		$isdual = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `app_id`= $app_id and `rcdone` = 1")->fetchColumn();
		if($isdual!=0)
		{
			echo "<script>alert('RC Already Added. Please focus.');window.close();</script>"; 
		}
		else
		{
			$conn->query("UPDATE `app_data` SET `rcdone` = '1', `rcdate` = '$currentdatetime', `rateperhour` = $rate, `t1ip_name`= '$t1ip_name', `ars_status` =7 WHERE `app_id` = $app_id");
			echo "<script>alert('Rate Added.');window.close();</script>"; }
		}
		else
		{
			if(isset($_GET['appcom_id']))
			{
				$app_id=$_GET['appcom_id'];
			}
			
            
       /*     if($dta['level'] ==1 || $dta['level'] == 2)
			{
				$qcid = "select * from consultants";
			}
			else 
			{ 
				$qcid = "select * from assigned AS A INNER JOIN consultants AS B ON A.cid = B.cid where uid =$sessid"; 
			}

			$cq= $conn->prepare($qcid);
			$cq->execute();
			$cdta = $cq->fetchAll(); */
		
			?>
		<form action="#" method="post">
			<tr><br><td><label>SM:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> </td>
				<td><?php echo $dta['name']; ?> </td> <br></tr><br>
		<!--	<tr> <td><label>Consultant:</label> </td>
				<td> 
				<select name="cid" >
									<?php
								foreach( $cdta as $row) { ?>
										<option value="<?php echo $row['cid']; ?>"><?php echo $row['cfname']." ".$row['clname']; ?></option>
									<?php } ?></select>
				</td> 
			</tr> 
            		<br> <input type="hidden" name="uid" value="<?php echo $dta['uid']; ?>">
        -->
				<br> <input type="hidden" name="app_id" value="<?php echo $app_id; ?>">
		
			<tr> 
				<br>
				<td>
				<label>Rate:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
								$<input name="rate" class="form-control-in" placeholder="Rate in numbers">/hr
								</td>
								<br><br>
				<td>
				<label>IP/T1 name:&nbsp;</label>
								<input name="t1ip_name" class="form-control-in" placeholder="IP/Tier 1 Company name only">
				</td>
				
	<!--			<label>End Client Name:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
								<input name="ecname" class="form-control-in" placeholder="End Client"> -->
				</td>
				<br>
								</tr>
			<tr>
			<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td class="button">
		<button type="submit" name="addrc">Add RC</button> </td>
			</tr>
		</form>


		<?php
		}

}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>