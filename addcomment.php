<?php
require( "config.php" );
require( "includes/header.php" );
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

		if(isset($_POST['addcomment']))
		{	
			$currentdatetime =date('Y-m-d H:i:s');
			$com_postid = $_POST['com_postid'];
			$com_type = $_POST['com_type'];
			$uid = $_POST['uid'];
			$comment = $_POST['comment'];


			if(isset($_POST['isissue'])) { $issue = 1; } else { $issue = 0; }
			$reqcom_id = 0;
			$appcom_id = 0;
			$rccom_id = 0;
			$subcom_id = 0;
			$ecicom_id = 0;
			$pocom_id = 0;
			$conscom_id = 0;

			if($com_type == 1) { $reqcom_id = 1; }
			elseif($com_type == 2) { $appcom_id = 1; }
			elseif($com_type == 3) { $rccom_id = 1; }
			elseif($com_type == 4) { $subcom_id = 1; }
			elseif($com_type == 5) { $ecicom_id = 1; }
			elseif($com_type == 6) { $pocom_id = 1; }
			elseif($com_type == 7) { $conscom_id = 1; }
			if(isset($_POST['end_date']))
			{
					$end_date = date('Y-m-d', strtotime($_POST['end_date']));

					$conn->query("UPDATE `consultants` SET `end_date` = '$end_date' WHERE `cid` = $com_postid");
			}
			
			$qc = "INSERT INTO `comments` (`com_id`, `com_type`, `uid`, `com_postid`, `reqcom_id`, `appcom_id`, `rccom_id`, `subcom_id`, `ecicom_id`, `pocom_id`, `conscom_id`, `imp_issue`, `comment`, `datetime`) VALUES ( null, $com_type, $uid, $com_postid, $reqcom_id, $appcom_id, $rccom_id, $subcom_id, $ecicom_id, $pocom_id, $conscom_id, $issue, :comment, '$currentdatetime');";
			$insq= $conn->prepare($qc);
			$insq->bindValue( ":comment", $comment, PDO::PARAM_STR );
			$insq->execute();
			if($com_type ==7) {
			echo "<script>window.location.href='consultantcmd.php?do=delete&id=$com_postid'</script>";
			}
			else {
				echo "<script>alert('Comment Added.');window.close();</script>";
			
			}
		}
		else{
			?>
		<form action="#" method="post">
			<tr><br><td><label>SM:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> </td>
				<td><?php echo $dta['name']; ?> </td> <br></tr><br>
			<tr>
			<?php if($com_type ==7) {
				?>
				<td><input name="end_date" id="datepicker"></td>

				<?php
			 }?><td><label>Comment:</label> </td>
				<td> <textarea name="comment" "width: 200px; height: 400px;"></textarea> </td> 
			</tr>
				<br> <input type="hidden" name="com_postid" value="<?php echo $com_postid; ?>">
				<br> <input type="hidden" name="com_type" value="<?php echo $com_type; ?>">
				<br> <input type="hidden" name="uid" value="<?php echo $dta['uid']; ?>">
			
			<tr>
			<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<input type="checkbox" name="isissue" value="1">
		<label> Check it to list this as an issue/challenge.</label><br>
			<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td class="button">
		<button type="submit" name="addcomment">Add Comment</button> </td>
			</tr>
		</form>


		<?php
		}

	

require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>