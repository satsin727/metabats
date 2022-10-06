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

require("includes/header.php");
$selected = "showsub";
require("includes/menu.php");

if(isset($_POST['saveeci']))
{
	$uid = $sessid;
	$app_id = $_POST['appid'];
/*	$companyname = $_POST['companyname'];
	$rname = $_POST['rname'];
	$rfname = $_POST['rfname'];
	$remail = $_POST['remail'];
	$rphone = $_POST['rphone'];
	$rlocation = $_POST['rlocation'];
	$tier = $_POST['tier'];
	$rtimezon = $_POST['rtimezon'];
*/
	$eci_date = date('Y-m-d', strtotime($_POST['eci_date']));
	$eci_time = $_POST['eci_time'];
	$eci_req_date = date('Y-m-d', strtotime($_POST['eci_req_date']));
	$eci_type = $_POST['eci_type'];
	$eci_round = $_POST['eci_round'];

	if($dta['level'] == 1 || $dta['level'] == 2)
	{
		$query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status =1 and subdone= 1 and app_id = $app_id order by subdate desc";
	}
	else
	{
		$query = "select * from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where A.status =1 and uid = $uid and subdone= 1 and app_id = $app_id order by subdate desc";
	} 
	$ins= $conn->prepare($query);
	$ins->execute();
	$data = $ins->fetch();
	$t2id = $data['client_id'];
	$req_id = $data['reqid'];
	$consultant_id = $data['consultant_id'];
	$skill_id = $data['skill'];
	$sm_id = $data['uid'];


//	$query = "INSERT INTO `eci` (`eci_id`, `sm_id`, `t2id`, `t1id`, `app_id`, `req_id`, `consultant_id`, `skill_id`, `eci_type`, `eci_date`, `eci_time`, `eci_req_date`, `eci_round`, `eci_happened`, `eci_client_feedback`, `eci_usteam_feedback`, `status`, `nh_reason`, `datetime`) VALUES (NULL, $sm_id, $t2id, '', $app_id, $req_id, $consultant_id, $skill_id, '$eci_type', '$eci_date', '$eci_time', '$eci_req_date', $eci_round, '0', NULL, NULL, 1, '', CURRENT_TIMESTAMP)";
	$conn->query("INSERT INTO `eci` (`eci_id`, `sm_id`, `t2id`, `t1id`, `app_id`, `req_id`, `consultant_id`, `skill_id`, `eci_type`, `eci_date`, `eci_time`, `eci_req_date`, `eci_round`, `eci_happened`, `eci_client_feedback`, `eci_usteam_feedback`, `status`, `nh_reason`, `datetime`) VALUES (NULL, $sm_id, $t2id, '', $app_id, $req_id, $consultant_id, $skill_id, '$eci_type', '$eci_date', '$eci_time', '$eci_req_date', $eci_round, '0', NULL, NULL, 1, '', CURRENT_TIMESTAMP)");

	if($dta['level'] == 1 || $dta['level'] == 2)
	{
	$conn->query("UPDATE `app_data` SET `hasinterview` = '1', `ars_status` =7 WHERE `app_id` = $app_id");
	}
	else
	{
	$conn->query("UPDATE `app_data` SET `hasinterview` = '1', `ars_status` =7 WHERE `app_id` = $app_id and `uid` = $sm_id");
	} 
//	$cid = $conn->lastInsertId();
//	$conn->query("UPDATE `app_data` SET `hasinterview` = '1', `ars_status` =7 WHERE `app_id` = $app_id and `uid` = $uid");
//	echo $query;
	echo "<script>alert('ECI Added.');window.location.href='admin.php?action=showsub';</script>";
}
else{
$app_id = $_GET['subid'];

?>
<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">	
		<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-heading"></div> 
					<div class="panel-body">

					<form action="#" method="post">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
<!--<tr> <h4>IP/Tier 1 Details<h4></tr>
<tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>T1/IP Company Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="companyname" class="form-control-in" placeholder="Company Name"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
								<td width="15%" align="left" valign="top">	<label>POC Full Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="rname" class="form-control-in" placeholder="Name"></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
	<div class="form-group">
								<td width="15%" align="left" valign="top">	<label>First Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="rfname" class="form-control-in" placeholder="First Name"></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Email ID:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="remail" class="form-control-in" placeholder="Email Address"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Phone Number:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input type="phone" name="rphone" class="form-control-in" placeholder="Phone Number"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">	
									<td width="15%" align="left" valign="top"><label>Location:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="rlocation" class="form-control-in" placeholder="Location"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Tier:&nbsp;&nbsp;&nbsp;</label></td>
									<td width="90%" align="left" valign="top">	<select name="tier" class="form-control-in">
										<option value="Tier 1">Tier 1</option>
           								<option value="IP">Implementation Partner</option>
									</select></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Timezone</label></td>
								<td width="90%" align="left" valign="top">	<select name="rtimezon" class="form-control-in">
										<option value="EST"> EST</option>
           								<option value="CST"> CST</option>
            							<option value="MST"> MST</option>
            							<option value="PST"> PST</option>
									</select></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> -->
<tr> <td><h4>ECI Details<h4></tr> </td><tr>
<div class="form-group">	

									<td width="15%" align="left" valign="top"><label>ECI Date:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="eci_date" id="datepicker"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

<td width="15%" align="left" valign="top"><label>ECI Time:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="eci_time" class="form-control-in" placeholder="Time in CST (24hr format)"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

<td width="15%" align="left" valign="top"><label>Requested Date:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="eci_req_date" id="datepicker2"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> 
<tr>
<div class="form-group">	
									<td width="15%" align="left" valign="top"><label>Mode of ECI:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="eci_type" class="form-control-in" placeholder="Mode of Interview"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr>

<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Round Type:</label></td>
								<td width="90%" align="left" valign="top">	<select name="eci_round" class="form-control-in">
										<option value="1">Screening</option>
           								<option value="2">Test</option>
            							<option value="3">Interview</option>
									</select></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr>

<tr>
<input type="hidden" name="appid" value="<?php echo $app_id; ?>">
   							<td  align="left" ><button type="submit" name="saveeci" class="btn btn-primary">Save</button> </td>					
                 </tr>
                 </table>

</form>
						
				</div></div>
			</div><!-- /.col-->
		</div><!-- /.row -->
		
</div>
			
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