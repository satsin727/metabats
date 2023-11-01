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
$conn=null;

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
$selected = "dashboard";
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{


if(isset($_GET['do']))
{
	$userid=$dta['uid'];
	$do=$_GET['do'];	
	$reqid=$_GET['id'];
	?>
	<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active"><?php echo $do; ?>Edit Requirement</li>
			</ol>
		</div>
		<?php
	if($do=='delete')
	{
		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$inquery = "UPDATE `req` SET `status` = '0' WHERE `reqid` = $reqid";
									$ins= $conn->prepare($inquery);
									$ins->execute();
									$conn=null;
									echo "<script>
											alert('Requirement is set as closed');
											window.location.href='admin.php?action=showreqs';
											</script>"; 	
	}
	
	elseif($do=='edit')
	{ 

$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from req where `reqid` = :reqid";
$ins= $conn->prepare($query);
$ins->bindValue( ":reqid", $reqid, PDO::PARAM_INT );
$ins->execute();
$udata = $ins->fetch();
$conn=null;


		?>
		
		<div class="row">
			<div class="col-lg-12">&nbsp;
			</div>
		</div><!--/.row-->
				
		
		<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-body">

	
					<form action="#" method="post">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">


  <tr>
<div class="form-group">
								 <td width="15%" align="left" valign="top"><label>Skill:</label></td>
								<td width="85%" align="left" valign="top">	<select name="skillid" class="form-control-in">
									<?php
									$sid = $udata['skillid'];

								$q2 = "select * from skill";
								$conn2 = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
								$ins2= $conn2->prepare($q2);
								$ins2->execute();
								$dta2 = $ins2->fetchAll();
								foreach( $dta2 as $row2) { ?>
										<option <?php if($row2['sid']== $sid) { echo "selected";}?> value="<?php echo $row2['sid']; ?>"><?php echo $row2['skillname']; ?></option>
									<?php } ?></select> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="admin.php?action=addskill">Add Skill</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label>SM:</label>
									<select name="uid" class="form-control-in">
									<?php
									
									if($dta['level'] == 1)
									{
									$conn2 = null;
								$conn2 = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
								$q2 = "select * from users";
								$ins2= $conn2->prepare($q2);
								$ins2->execute();
								$dta2 = $ins2->fetchAll();
								
								foreach( $dta2 as $row2) { ?>
										<option value="<?php echo $row2['uid']; ?>"><?php echo $row2['name']; ?></option>
								<?php } } else { ?> <option value="<?php echo $dta['uid']; ?>"><?php echo $dta['name']; ?></option><?php }?></select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br><br>
								<input type="hidden" name="reqid" id="reqid" value="<?php echo trim($reqid); ?>"/> </td>					
                 </tr>

<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Location:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="5%" align="left" valign="top"><input name="rlocation" class="form-control-in" placeholder="Location"  value="<?php echo $udata['rlocation'];  ?>"><label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Duration:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
								<input name="rduration" class="form-control-in" placeholder="Duration in Months"  value="<?php echo $udata['rduration'];  ?>"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> 

<tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Job type:&nbsp;&nbsp;&nbsp;</label>
								<td width="85%" align="left" valign="top">	<select name="jobtype" class="form-control-in">
										<option <?php if($udata['jobtype']=="1"){ echo "selected";}?> value="1">Contract</option>
           								<option <?php if($udata['jobtype']=="2"){ echo "selected";}?> value="2">Contract to hire</option>
									</select>
									<label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Client:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
									
									<?php
										$cid = $udata['cid'];
										$q3 = "select * from clients where `cid` = $cid";
										$ins3= $conn2->prepare($q3);
										$ins3->execute();
										$dta3= $ins3->fetch();
										$remail = $dta3['remail'];
									?>
									
									<div class="search-box">
									<input name="cemail" type="text" autocomplete="off" placeholder="Email ID" value="<?php echo $remail;  ?>" />
									<div class="result"></div>
									</div>
								
								</td>
</div></tr><tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr>


<tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Rate($/hr):&nbsp;&nbsp;&nbsp;</label></td>
								<td width="5%" align="left" valign="top"><input name="rrate" class="form-control-in" placeholder="Rate only in numerical."  value="<?php echo $udata['rrate'];  ?>"><label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;End Client:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
								<input name="rend_client" class="form-control-in" placeholder="End Client"  value="<?php echo $udata['rend_client'];  ?>"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> 

<tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Job Description:&nbsp;&nbsp;&nbsp;</label></td>
									<?php
								$conn2 = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
								$q2 = "select * from jd where reqid = $reqid";
								$ins2= $conn2->prepare($q2);
								$ins2->execute();
								$desc = $ins2->fetch();
									?>
								<td width="90%" align="left" valign="top">	<textarea class="ckeditor" name="rdesc"><?php echo $desc['rdesc'];  ?></textarea> </td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> 
<tr>
<td width="15%" align="left" valign="top"></td><td width="90%" align="left" valign="top">
<button type="submit" name="update" class="btn btn-primary">Update</button> </td>
</td>
</tr>   


                 </table>

</form>
						
				</div></div>
			</div><!-- /.col-->
		</div><!-- /.row -->
		
	</div>


<?php	
if(isset($_POST['update']))

{

if ( empty($_POST['jobtype']) || empty($_POST['rlocation']) || empty($_POST['rduration']) || empty($_POST['rdesc']) || empty($_POST['skillid']) )
{

echo "<script>
alert(' All value is required !!!');
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
$reqid = $_POST['reqid'];
$remail = $_POST['cemail'];

$conn= null;
$conn= null;
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from clients where `remail` = :remail";
$ins= $conn->prepare($query);
$ins->bindValue( ":remail", $remail, PDO::PARAM_STR );
$ins->execute();
$cdta = $ins->fetch();
if($cdta['cid']!= null)
	{
	$cid = $cdta['cid'];
	}
else {
	$cinsertquery = $conn->prepare("INSERT INTO `clients` (`lid`, `uid`, `companyname`, `rname`, `rfname`, `remail`, `rphone`, `rlocation`, `rtimezon`, `tier`, `status`, `filetarget`) VALUES ('1', :uid, NULL, NULL, NULL, :remail, NULL, NULL, NULL, NULL, '1', 'manual');");
	$cinsertquery->bindValue( ":uid", $uid, PDO::PARAM_INT );
	$cinsertquery->bindValue( ":remail", $remail, PDO::PARAM_STR );
	$cinsertquery->execute();
	$cid = $conn->lastInsertId();
}
$conn= null;

$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );

 $que= $conn->prepare("Update `req` SET `uid` = :uid, `jobtype` = :jobtype, `rlocation` = :rlocation, `rduration` = :rduration, `rrate` = :rrate, `rend_client` = :rend_client, `skillid` = :skillid WHERE `reqid` = $reqid");
 $que->bindValue( ":uid", $uid, PDO::PARAM_INT );
 $que->bindValue( ":jobtype", $jobtype, PDO::PARAM_INT );
 $que->bindValue( ":rlocation", $rlocation, PDO::PARAM_STR );
 $que->bindValue( ":rduration", $rduration, PDO::PARAM_STR );
 $que->bindValue( ":rrate", $rrate, PDO::PARAM_INT );
 $que->bindValue( ":rend_client", $rend_client, PDO::PARAM_STR );
 $que->bindValue( ":skillid", $skillid, PDO::PARAM_INT );
 $que->execute();
 
 $que1= $conn->prepare("Update `jd` SET `rdesc` = :rdesc WHERE `reqid` = $reqid");
 $que1->bindValue( ":rdesc", $rdesc, PDO::PARAM_STR );
 $que1->execute();
 
 /*
 echo "<script>
											alert('Requirement Updated.');
											window.location.href='admin.php?action=showreqs';
											</script>"; */
 
} // is check null values


}


} //do edit

else
	{
		echo "<script>
alert('Not a valid command.');
window.location.href='admin.php?action=listconsultants';
</script>";
	}


} //for $do

} //for admin
else
{
	echo "<script>
alert('You Need to be Admin to view this page.');
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