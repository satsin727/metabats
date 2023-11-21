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

		if(isset($_POST['addars_status']))
		{	
			$ars_status=$_POST['ars_status'];    
			$appid = $_POST['postid'];

		//	$qc ="UPDATE `app_data` SET `ars_status` = $ars_status WHERE `app_data`.`app_id` = $postid";
            $conn->query("UPDATE `app_data` SET `ars_status` = $ars_status WHERE `app_data`.`app_id` = $appid")->execute();
			echo "<script>alert('Status Updated.');window.close();</script>";
		}
		else{
			?>
		<form action="#" method="post">
			<tr><br><td><label>SM:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> </td>
				<td><?php echo $dta['name']; ?> </td> <br></tr><br>
                <tr><td><label>Status:</label> </td>
				<td> 
				<select name="ars_status">
				<?php
                  if(isset($_GET['appid']))
			{
				$appid=$_GET['appid']; 
                $ars_status = $conn->query("SELECT `ars_status` FROM `app_data` WHERE `app_id` = $appid")->fetchColumn();?>
                <option value="1" <?php if($ars_status ==1) { echo "selected"; } ?>>Connected</option>
                <option value="2" <?php if($ars_status ==2) { echo "selected"; } ?>>Not Connected</option>
                <option value="3" <?php if($ars_status ==3) { echo "selected"; } ?>>Voicemail</option>
                <option value="4" <?php if($ars_status ==4) { echo "selected"; } ?>>Connected and No Response</option>
				<option value="6" <?php if($ars_status ==6) { echo "selected"; } ?>>Rejected - Other</option>
				<option value="11" <?php if($ars_status ==11) { echo "selected"; } ?>>Rejected - Bad Profile</option>
                <option value="12" <?php if($ars_status ==12) { echo "selected"; } ?>>Rejected - Senior Requirement</option>
				<option value="13" <?php if($ars_status ==13) { echo "selected"; } ?>>Rejected - Local candidate needed</option>				
				<option value="14" <?php if($ars_status ==14) { echo "selected"; } ?>>Rejected - Position went onhold</option>				
				<option value="15" <?php if($ars_status ==15) { echo "selected"; } ?>>Rejected - Day 1 Onsite Required</option>			
				<option value="16" <?php if($ars_status ==16) { echo "selected"; } ?>>Rejected - Low Rate</option>				
				<option value="11" <?php if($ars_status ==18) { echo "selected"; } ?>>Rejected - Due to Linkedin</option>
				
				
            <?php
			}
			elseif(isset($_GET['rcid']))
			{
				$appid=$_GET['rcid'];
                $ars_status = $conn->query("SELECT `ars_status` FROM `app_data` WHERE `app_id` = $appid")->fetchColumn();?>

                <option value="4" <?php if($ars_status ==4) { echo "selected"; } ?>>No Response/Not Submitted</option>
                <option value="5" <?php if($ars_status ==5) { echo "selected"; } ?>>Cancelled</option>
                <option value="6" <?php if($ars_status ==6) { echo "selected"; } ?>>Rejected</option>
                <option value="7" <?php if($ars_status ==7) { echo "selected"; } ?>>In-Process</option>
				<option value="14" <?php if($ars_status ==14) { echo "selected"; } ?>>Rejected - Position went onhold</option>				
				<option value="11" <?php if($ars_status ==11) { echo "selected"; } ?>>Rejected - Bad Profile</option>				
				<option value="11" <?php if($ars_status ==17) { echo "selected"; } ?>>Rejected - Consultant Response</option>
            <?php
			}
			elseif(isset($_GET['subid']))
			{
				$appid=$_GET['subid']; 
                $ars_status = $conn->query("SELECT `ars_status` FROM `app_data` WHERE `app_id` = $appid")->fetchColumn();?>
                
            <option value="8"  <?php if($ars_status ==8) { echo "selected"; } ?>>Got Test</option>
            <option value="9" <?php if($ars_status ==9) { echo "selected"; } ?>>Got Screening</option>
            <option value="10" <?php if($ars_status ==10) { echo "selected"; } ?>>Submitted to End Client</option>
            <option value="6" <?php if($ars_status ==6) { echo "selected"; } ?>>Rejected</option>
            <option value="7" <?php if($ars_status ==7) { echo "selected"; } ?>>In-Process</option>
            <option value="4" <?php if($ars_status ==4) { echo "selected"; } ?>>No Response/Not Submitted</option>
            <?php
			}
			?>
									
									</select>
				</td> 
			</tr>
				<br> <input type="hidden" name="postid" value="<?php echo $appid; ?>">
			<tr>
			<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
						<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td class="button">
		<button type="submit" name="addars_status">Add Status</button> </td>
			</tr>
		</form>


		<?php
		}

		$conn = null;

}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>