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

/*
ars_status = 1 //Connected
ars_status = 2 //Not Connected
ars_status = 3 //Voice Mail
ars_status = 4 //No Response
ars_status = 5 //Cancelled
ars_status = 6 //Rejected
ars_status = 7 //In-process
ars_status = 8 //Got Test
ars_status = 9 //Got Screening
ars_status = 10 //Submitted to EC

    <option value="1">Connected</option>
    <option value="2">Not Connected</option>
    <option value="3">Voicemail</option>
    <option value="4">No Response</option>
    <option value="5">Cancelled = Pulled Back</option>
    <option value="6">Rejected</option>
    <option value="7">In-Process</option>
    <option value="8">Got Test</option>
    <option value="9">Got Screening</option>
    <option value="10">Submitted to End Client</option>
*/

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

		if(isset($_POST['addsub_status']))
		{	
			$sub_status=$_POST['sub_status'];    
			$appid = $_POST['postid'];
			
		
		//	$qc ="UPDATE `app_data` SET `ars_status` = $ars_status WHERE `app_data`.`app_id` = $postid";
            $conn->query("UPDATE `app_data` SET `subto` = $sub_status WHERE `app_data`.`app_id` = $appid")->execute();
			echo "<script>alert('Sub Status Updated.');window.close();</script>";}
		
		else{
			?>
		<form action="#" method="post">
			<tr><br><td><label>SM:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> </td>
				<td><?php echo $dta['name']; ?> </td> <br></tr><br>
                <tr><td><label>Status:</label> </td>
				<td> 
				<select name="sub_status">
				<?php
				$appid=$_GET['subid']; 
                $sub_status = $conn->query("SELECT `subto` FROM `app_data` WHERE `app_id` = $appid")->fetchColumn();?>
                <option value="1" <?php if($sub_status ==1) { echo "selected"; } ?>>Submitted to T2</option>
                <option value="2" <?php if($sub_status ==2) { echo "selected"; } ?>>Submitted to T1</option>
                <option value="3" <?php if($sub_status ==3) { echo "selected"; } ?>>Submitted to IP</option>
                <option value="4" <?php if($sub_status ==4) { echo "selected"; } ?>>Submitted to EC</option>
                <option value="7" <?php if($sub_status ==7) { echo "selected"; } ?>>In-Process<br></option>
									
									</select>
				</td> 
			</tr>
				<br> <input type="hidden" name="postid" value="<?php echo $appid; ?>">
			<tr>
			<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
						<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td class="button">
		<button type="submit" name="addsub_status">Update Status</button> </td>
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