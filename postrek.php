<?php
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

require("includes/header.php");
require("includes/menu.php");

?> 
<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Post Requirement</li>
			</ol>
		</div><!--/.row-->
		
			<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-body">

					<form action="#" method="post">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr> <div class="form-group">
								<td width="15%" align="left" valign="top"><label>Skill:</label></td>
								<td width="85%" align="left" valign="top">	<select name="skillid" class="form-control-in">
									<?php
								$conn2 = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
								$q2 = "select * from skill";
								$ins2= $conn2->prepare($q2);
								$ins2->execute();
								$dta2 = $ins2->fetchAll();
								foreach( $dta2 as $row2) { ?>
										<option value="<?php echo $row2['sid']; ?>"><?php echo $row2['skillname']; ?></option>
									<?php } ?></select> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php if($dta['level'] == 1) { ?> <a href="admin.php?action=addskill">Add Skill</a><?php } ?><label>SM:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
									<select name="uid" class="form-control-in">
									<?php
									
									if($dta['level'] == 1 )
									{
									$conn2 = null;
								$conn2 = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
								$q2 = "select * from users";
								$ins2= $conn2->prepare($q2);
								$ins2->execute();
								$dta2 = $ins2->fetchAll();
								
								foreach( $dta2 as $row2) { ?>
										<option value="<?php echo $row2['uid']; ?>"><?php echo $row2['name']; ?></option>
								<?php } } else { ?> <option value="<?php echo $dta['uid']; ?>"><?php echo $dta['name']; ?></option><?php }?></select>					
                 </tr><tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> 

      <!--    <tr>
<div class="form-group">
								<td width="15%" align="left" valign="top">	<label>Job Title:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="85%" align="left" valign="top"><input name="role" class="form-control-sub" placeholder="Job Title" ></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr>  -->
<tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Location:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="5%" align="left" valign="top"><input name="rlocation" class="form-control-in" placeholder="Location"><label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Duration:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
								<input name="rduration" class="form-control-in" placeholder="Duration in Months"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> 

<tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Job type:&nbsp;&nbsp;&nbsp;</label>
								<td width="85%" align="left" valign="top">	<select name="jobtype" class="form-control-in">
										<option value="1">Contract</option>
           								<option value="2">Contract to hire</option>
									</select>
									<label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Email:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
									<div class="search-box">
									<input name="cemail" type="text" autocomplete="off" placeholder="Email ID" />
									<div class="result"></div>
									</div>
								</td>
</div></tr><tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr>

<tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Rate($/hr):&nbsp;&nbsp;&nbsp;</label></td>
								<td width="5%" align="left" valign="top"><input name="rrate" class="form-control-in" placeholder="Rate only in numbers"><label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;End Client:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
								<input name="rend_client" class="form-control-in" placeholder="End Client Name (No IP or PV name)"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> 
<tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Tier type:&nbsp;&nbsp;&nbsp;</label>
								<td width="85%" align="left" valign="top">	<select name="ttype" class="form-control-in">
										<option value="1">Tier 1</option>
           								<option value="2">Tier 2</option>
									</select>
									<label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Nationality:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;	</label>
									<select name="nationality" class="form-control-in">
										<option value="1">American</option>
           								<option value="2">Indian</option>
									</select>
								</td>
</div></tr><tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr>

<tr>
<div class="form-group">
								<td width="15%" align="left" valign="top"><label>Requirement Source:&nbsp;&nbsp;&nbsp;</label>
								<td width="85%" align="left" valign="top">	
									<select name="req_source" class="form-control-in">
										<option value="1">Inbox</option>										
           								<option value="2">Posting</option>										
           								<option value="3">Cold Calls</option>																				
           								<option value="4">AMC</option>																				
           								<option value="5">Prohires</option>																				
           								<option value="6">Google Groups</option>																				
           								<option value="7">LinkedIn</option>										
           								<option value="8">Job Portal - Dice</option>										
           								<option value="9">Job Portal - Techfetch</option>										
           								<option value="10">Job Portal - SimplyHired</option>										
           								<option value="11">Job Portal - Careerbuilder</option>										
           								<option value="12">Job Portal - Ziprecruiter</option>										
           								<option value="13">Job Portal - Monster</option>										
           								<option value="14">Job Portal - other</option>																				
           								<option value="15">Company Websites</option>																				
           								<option value="16">I-Labor</option>																				
           								<option value="17">Other</option>
									</select>
								</td>
</div></tr><tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr>

<tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Job Description:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<textarea class="ckeditor" name="rdesc" ></textarea> </td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> 
           
<tr>
<td width="15%" align="left" valign="top"></td><td width="90%" align="left" valign="top">
<button type="submit" name="save" class="btn btn-primary">Save</button> </td>
</td>
</tr>       </table>
</form>


				</div></div>
			</div><!-- /.col-->
		</div><!-- /.row -->
		
	</div>	<!--/.main-->

<?php
require("includes/footer.php"); 

if (isset($_POST['save']))
{
	function checkemail($str) {

		$str = strtolower($str);
		return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z0-9]{2,9}$/ix", trim(strtolower($str)))) ? FALSE : TRUE;
  }
	if ( empty($_POST['jobtype']) || empty($_POST['rlocation']) || empty($_POST['rduration']) || empty($_POST['rdesc']) || empty($_POST['skillid']) || empty($_POST['cemail']) || !checkemail(strtolower($_POST['cemail'])) ) 
{
echo "<script>
alert(' All correct value is required. Please double check the email and other details !!!');
</script>";
}
else {

$jobtype = $_POST['jobtype'];
$rlocation = $_POST['rlocation'];
$rduration = $_POST['rduration'];
$rrate = $_POST['rrate'];
$rend_client = $_POST['rend_client'];
$rdesc = $_POST['rdesc'];
$skillid = $_POST['skillid'];
$uid = $_POST['uid'];
$remail = trim(strtolower($_POST['cemail']));
$domain = substr($remail, strpos($remail, '@') + 1);
$ttype = $_POST['ttype'];
$req_source = $_POST['req_source'];
$nationality = $_POST['nationality'];
$currentdatetime =date('Y-m-d H:i:s');
$conn= null;
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from clients where `remail` = :remail";
$ins= $conn->prepare($query);
$ins->bindValue( ":remail", $remail, PDO::PARAM_STR );
$ins->execute();
$cdta = $ins->fetch();
if(isset($cdta['cid']))
	{
	$cid = $cdta['cid'];
	}
else {

	$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
	$query = "select * from users where `uid` = :u";
	$ins= $conn->prepare($query);
	$ins->bindValue( ":u", $uid, PDO::PARAM_STR );
	$ins->execute();
	$dta = $ins->fetch();
	$lid= $dta['def_lid'];

	$cinsertquery = $conn->prepare("INSERT INTO `clients` (`lid`, `uid`, `companyname`, `rname`, `rfname`, `remail`, `domain`, `rphone`, `rlocation`, `rtimezon`, `tier`, `status`, `filetarget`) VALUES (:lid, :uid, NULL, NULL, NULL, :remail, '$domain', NULL, NULL, NULL, NULL, '1', 'manual');");
	$cinsertquery->bindValue( ":uid", $uid, PDO::PARAM_INT );
	$cinsertquery->bindValue( ":lid", $lid, PDO::PARAM_INT );
	$cinsertquery->bindValue( ":remail", $remail, PDO::PARAM_STR );
	$cinsertquery->execute();
	$cid = $conn->lastInsertId();
}
$conn= null;
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );

 $que= $conn->prepare("INSERT INTO `req` (`uid`, `cid`, `jobtype`, `rlocation`, `rduration`, `rrate`, `rend_client`, `skillid`, `req_source`, `ttype`, `nationality`, `datetime`) VALUES ( :uid, :cid, :jobtype, :rlocation, :rduration, :rrate, :rend_client, :skillid, :req_source, :ttype, :nationality, '$currentdatetime');");
 $que->bindValue( ":uid", $uid, PDO::PARAM_INT );
 $que->bindValue( ":cid", $cid, PDO::PARAM_INT );
 $que->bindValue( ":jobtype", $jobtype, PDO::PARAM_INT );
 $que->bindValue( ":rlocation", $rlocation, PDO::PARAM_STR );
 $que->bindValue( ":rduration", $rduration, PDO::PARAM_STR );
 $que->bindValue( ":rrate", $rrate, PDO::PARAM_INT );
 $que->bindValue( ":rend_client", $rend_client, PDO::PARAM_STR );
 $que->bindValue( ":skillid", $skillid, PDO::PARAM_INT );
 $que->bindValue( ":req_source", $req_source, PDO::PARAM_INT );
 $que->bindValue( ":ttype", $ttype, PDO::PARAM_INT );
 $que->bindValue( ":nationality", $nationality, PDO::PARAM_INT );
 $que->execute();
 
 $reqid = $conn->lastInsertId();
 
 $que1= $conn->prepare("INSERT INTO `jd` (`reqid`, `rdesc`) VALUES ( :reqid, :rdesc );");
 $que1->bindValue( ":reqid", $reqid, PDO::PARAM_INT );
 $que1->bindValue( ":rdesc", $rdesc, PDO::PARAM_STR );
 $que1->execute();
 

 echo "<script>
											alert('Requirement Added.');
											window.location.href='admin.php?action=showreqs';
											</script>"; 
 
} // is check null values

} // isset post submit



}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>