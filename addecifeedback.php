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

		if(isset($_POST['addUpdate']))
		{	
			//$uid = $_POST['uid'];
			if(isset($_POST['eci_id']))
			{
				$eci_id = $_POST['eci_id'];
			}
			if(isset($_POST['cf']))
			{
				$cfeedback = $_POST['cfeedback'];
				$conn->query("UPDATE `eci` SET `eci_client_feedback` = '$cfeedback', `eci_cfeedback_datetime` = CURRENT_TIMESTAMP WHERE `eci_id` = $eci_id");
				echo "<script>alert('Client feedback added.');window.close();</script>";
			}
			elseif(isset($_POST['uf']))
			{
				$ufeedback = $_POST['ufeedback'];
				$conn->query("UPDATE `eci` SET `eci_usteam_feedback` = '$ufeedback' WHERE `eci_id` = $eci_id");
				echo "<script>alert('US Team feedback after Interview added.');window.close();</script>";
			}
			elseif(isset($_POST['nh']))
			{
				$nh_reason = $_POST['nhreason'];
				$conn->query("UPDATE `eci` SET `nh_reason` = $nh_reason WHERE `eci_id` = $eci_id");
				echo "<script>alert('Not happened reason added.');window.close();</script>";
			}


		//	$rate = $_POST['cf'];
		//	$t1ip_name = $_POST['t1ip_name'];
//			$ecname = $_POST['ecname'];
	//		$conn->query("UPDATE `app_data` SET `rcdone` = '1', `rcdate` = CURRENT_TIMESTAMP, `rateperhour` = $rate, `t1ip_name`= '$t1ip_name', `ars_status` =7 WHERE `app_id` = $app_id");
		//	echo "<script>alert('Rate Added.');window.close();</script>";
		}
		else
		{   $cf=0;
            $uf=0;
			if(isset($_GET['ecicfid']))
			{
				$eci_id=$_GET['ecicfid'];
                $cf=1;
			}
            elseif(isset($_GET['eciufid']))
			{
				$eci_id=$_GET['eciufid'];
                $uf=1;
			}
            elseif(isset($_GET['ecinhid']))
			{
				$eci_id=$_GET['ecinhid'];
                $nh=1;
			}
		          
			?>
		<form action="#" method="post">
			<tr><br><td><label>SM:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> </td>
				<td><?php echo $dta['name']; ?> </td> <br></tr><br>

				<br> <input type="hidden" name="eci_id" value="<?php echo $eci_id; ?>">

                <?php
                if($cf==1) { ?>
                    <input type="hidden" name="cf" value="<?php echo $cf; ?>">
                    <br>
                <tr><td><label>Client Feedback:&nbsp;</label></td>
					<td><input name="cfeedback" class="form-control-in" placeholder="Client Feedback"></td>
                </tr>
                <?php    }
                elseif($uf==1) { ?>
                    <input type="hidden" name="uf" value="<?php echo $uf; ?>">
                    <br>
                <tr><td><label>US Team Feedback:&nbsp;</label></td>
					<td><input name="ufeedback" class="form-control-in" placeholder="US Team Feedback"></td>
                </tr>
                <?php    }
                elseif($nh==1) { ?>
                    <input type="hidden" name="nh" value="<?php echo $nh; ?>">
                    <br>
                <tr><td><label>Not Happened Reason:&nbsp;</label></td>
					<td>
                    <select name="nhreason" class="form-control-in">
										<option value="1">Client no show</option>
           								<option value="2">Technical Issues by Client</option>
            							<option value="3">Technical Issues by Consultant</option>
            							<option value="4">Cancelled by Consultant</option>
            							<option value="5">Cancelled by US</option>
            							<option value="6">Other reason</option>
									</select>
                    </td>
                </tr>
                <?php    }
                ?>

			<tr>
			<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td class="button">
		<button type="submit" name="addUpdate">Update</button> </td>
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