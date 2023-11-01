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

		if(isset($_POST['addreqstatus']))
		{	
			$uid = $_POST['uid'];
			$reqid = $_POST['reqid'];
			$reqstatusid = $_POST['reqstatus'];
		/*	$data = $conn->query("select * from req where `reqid` = $reqid")->fetch();
			$client_id = $data['cid'];	
			$currentdatetime =date('Y-m-d H:i:s'); */

			$conn->query("Update req set reqstatus = $reqstatusid where reqid = $reqid");
			echo "<script>alert('Status updated.');window.close();</script>"; 
		}
		else
		{
			if(isset($_GET['reqid']))
			{
				$reqid=$_GET['reqid'];
			}	
	
			?>
		<form action="#" method="post">
			<tr><br><td><label>SM:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> </td>
				<td><?php echo $dta['name']; ?> </td> <br></tr><br>
			<tr><td><label>Status:</label> </td>
				<td> 
				<select name="reqstatus" >
				<option value="1">Rejected</option>
                <option value="2">Closed</option>
                <option value="3">Not connected</option>
                <option value="4">Open</option>
                <option value="5">In-Process</option>
				</select>
				</td> 
			</tr>
				<br> <input type="hidden" name="reqid" value="<?php echo $reqid; ?>">
				<br> <input type="hidden" name="uid" value="<?php echo $dta['uid']; ?>">

			<tr>
			<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td class="button">
		<button type="submit" name="addreqstatus">Add Req Status</button> </td>
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