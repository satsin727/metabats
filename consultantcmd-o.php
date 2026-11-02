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
$conn=null;

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
$selected = "dashboard";
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2)
{


if(isset($_GET['do']))
{

	$do=$_GET['do'];	
	$cid=$_GET['id'];
	?>
	<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active"><?php echo $do; ?> Consultant Basic Details</li>
			</ol>
		</div>
		<?php
	if($do=='delete')
	{
		$conn = bats_db();
		$inquery = ("UPDATE `consultants` SET `status` = '0' WHERE `cid` = " . bats_q($cid));
									$ins= $conn->prepare($inquery);
									$ins->execute();
									$conn=null;
									echo "<script>
											alert(' Consultant details deleted.');
											window.close();
											</script>"; 	
	}
	
	if($do=='edit')
	{ 

$conn = bats_db();
$query = "select * from consultants where `cid` = :cid";
$ins= $conn->prepare($query);
$ins->bindValue( ":cid", $cid, PDO::PARAM_INT );
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
								<td width="25%" align="left" valign="top">	<label>First Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="cfname" value="<?php echo $udata['cfname'];  ?>" class="form-control-in" placeholder="First Name"></td>
</div> </tr> <tr>
<div class="form-group">
								<td width="25%" align="left" valign="top">	<label>Middle Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="cmname"  value="<?php echo $udata['cmname'];  ?>" class="form-control-in" placeholder="Middle Name"></td>
</div> </tr>  <tr>
<div class="form-group">
								<td width="25%" align="left" valign="top">	<label>Last Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="clname"  value="<?php echo $udata['clname'];  ?>" class="form-control-in" placeholder="Last Name"></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
								<td width="15%" align="left" valign="top"><label>Role(Skill):</label>
								<?php
								$sid = $udata['skill'];
								$conn2 = bats_db();
								$q2 = ("select * from skill where `sid` = " . bats_q($sid));
								$ins2= $conn2->prepare($q2);
								$ins2->execute(); 
								$dta2 = $ins2->fetch(); 
								$conn2=null; ?>
								<td width="90%" align="left" valign="top">	<select name="skill" class="form-control-in">
								 <?php
								$conn2 = bats_db();
								$q3 = "select * from skill";
								$ins3= $conn2->prepare($q3);
								$ins3->execute(); 
								$dta3 = $ins3->fetchAll(); 
								foreach( $dta3 as $row3) { ?>
										<option value="<?php echo $row3['sid']; ?>" <?php if(trim($udata['skill'])==$row3['sid']) { echo "selected"; } ?>><?php echo $row3['skillname']; ?></option>
									<?php } ?></select></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

<div class="form-group">
									<td width="25%" align="left" valign="top"><label>DOB (MM/DD/YYYY):&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="dob"  value="<?php echo $udata['dob']; ?>" class="form-control-in" placeholder="DOB"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Consultant Original Email:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="co_email"  value="<?php echo $udata['co_email']; ?>" class="form-control-in" placeholder="Email Address"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>


<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Consultant Original Location:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="colocation"  value="<?php echo $udata['colocation']; ?>" class="form-control-in" placeholder="Original location"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Consultant Original Phone number:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="co_phonenumber" value="<?php echo $udata['co_phonenumber']; ?>"  class="form-control-in" placeholder="Original Phone number"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Consultant Original visa:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="covisa"  value="<?php echo $udata['covisa']; ?>" class="form-control-in" placeholder="Original Visa Status"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Relocation:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="relocation"  value="<?php echo $udata['relocation']; ?>" class="form-control-in" placeholder="Relocation Status"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Last 5 SSN:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="lastssn"  value="<?php echo $udata['lastssn']; ?>" class="form-control-in" placeholder="Last 5 SSN"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Passport Number:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="passportnumber"  value="<?php echo $udata['passportnumber']; ?>" class="form-control-in" placeholder="Passport Number"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Passport Country:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="passportcountry"  value="<?php echo $udata['passportcountry']; ?>" class="form-control-in" placeholder="Passport Country"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

<div class="form-group">
									<td width="15%" align="left" valign="top"><label>NDA (Agreement) Signed</label>
								<td width="90%" align="left" valign="top">	<select name="nda" " class="form-control-in">
										<option value="Yes" <?php if(trim($udata['nda'])==="Yes") { echo "selected"; } ?>>Yes</option>
           								<option value="No" <?php if(trim($udata['nda'])==="No") { echo "selected"; } ?>>No</option>
            							<option value="Not Required" <?php if(trim($udata['nda'])==="Not Required") { echo "selected"; } ?>>Not Required</option>
									</select></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

   							<td  align="left" ><button type="submit" name="update" class="btn btn-primary">Update</button> </td>					
                 </tr>
</form>
						
				</div></div>
			</div><!-- /.col-->
		</div><!-- /.row -->
		
	</div>


<?php	
if(isset($_POST['update']))

{
		$cfname = $_POST['cfname'];
		$cmname = $_POST['cmname'];
		$clname = $_POST['clname'];
		$skill = $_POST['skill'];
		$status=1;
		$co_email = $_POST['co_email'];
		$dob = $_POST['dob'];
		$colocation = $_POST['colocation'];
		$co_phonenumber = $_POST['co_phonenumber'];
		$covisa = $_POST['covisa'];
		$relocation = $_POST['relocation'];
		$lastssn = $_POST['lastssn'];
		$passportnumber = $_POST['passportnumber'];
		$passportcountry = $_POST['passportcountry'];
		$nda = $_POST['nda'];
		$conn = bats_db();
		 
		 $inquery = ("UPDATE `consultants` SET `status` = " . bats_q("$status") . ", `cfname` = " . bats_q("$cfname") . ", `cmname` = " . bats_q("$cmname") . ", `clname` = " . bats_q("$clname") . ", `skill` = " . bats_q("$skill") . ", `co_email` = " . bats_q("$co_email") . ", `colocation` = " . bats_q("$colocation") . ", `co_phonenumber` = " . bats_q("$co_phonenumber") . ", `covisa` = " . bats_q("$covisa") . ", `relocation` = " . bats_q("$relocation") . ", `lastssn` = " . bats_q("$lastssn") . ", `dob` = " . bats_q("$dob") . ", `passportnumber` = " . bats_q("$passportnumber") . ", `passportcountry` = " . bats_q("$passportcountry") . ", `nda` = " . bats_q("$nda") . " WHERE `cid` = " . bats_q($cid));
		
		$ins= $conn->prepare($inquery);
		$ins->execute();
		header( "Location: admin.php?action=listconsultants" );

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