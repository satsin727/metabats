<?php
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
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2)
{
?> 


<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href=""><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Add a Consultant</li>
			</ol>
		</div><!--/.row-->
		
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
								<td width="90%" align="left" valign="top"><input name="cfname" class="form-control-in" placeholder="First Name"></td>
</div> </tr> <tr>
<div class="form-group">
								<td width="25%" align="left" valign="top">	<label>Middle Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="cmname" class="form-control-in" placeholder="Middle Name"></td>
</div> </tr>  <tr>
<div class="form-group">
								<td width="25%" align="left" valign="top">	<label>Last Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="clname" class="form-control-in" placeholder="Last Name"></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
								<td width="15%" align="left" valign="top"><label>Role(Skill):</label></td>
								<td width="90%" align="left" valign="top">	<select name="skill" class="form-control-in">
								<?php
								$conn2 = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
								$q2 = "select * from skill";
								$ins2= $conn2->prepare($q2);
								$ins2->execute();
								$dta2 = $ins2->fetchAll();
								foreach( $dta2 as $row2) { ?>
										<option value="<?php echo $row2['sid']; ?>"><?php echo $row2['skillname']; ?></option>
									<?php } ?></select></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Consultant Original Email:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="co_email" class="form-control-in" placeholder="Email Address"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<!--
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Consultant Marketing Email:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="cm_email" class="form-control-in" placeholder="Marketing Email Address"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Marketing Email Password:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="cm_password" class="form-control-in" placeholder="Marketing Password"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Consultant Posting Email:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="cp_email" class="form-control-in" placeholder="Posting Email Address"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Marketing Posting Password:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="cp_password" class="form-control-in" placeholder="Posting Password"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr> -->
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Consultant Original Location:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="colocation" class="form-control-in" placeholder="Original location"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<!--
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Consultant Marketing Location:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="cmlocation" class="form-control-in" placeholder="Marketing location"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr> --> 
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Consultant Original Phone number:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="co_phonenumber" class="form-control-in" placeholder="Original Phone number"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<!--
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Consultant Marketing Phone no:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="cm_phonenumber" class="form-control-in" placeholder="Marketing Marketing Phone no."></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr> -->
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Consultant Original visa:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="covisa" class="form-control-in" placeholder="Original Visa Status"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<!--
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Consultant Marketing visa:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="cmvisa" class="form-control-in" placeholder="Marketing Visa Status"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr> -->
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Relocation:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="relocation" class="form-control-in" placeholder="Relocation Status"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Last 4 SSN:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="lastssn" class="form-control-in" placeholder="Last 5 SSN"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Passport Number:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="passportnumber" class="form-control-in" placeholder="Passport Number"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Passport Country:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="passportcountry" class="form-control-in" placeholder="Passport Country"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<!--
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Bachelor Degree:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="bachelordegree" class="form-control-in" placeholder="Original Bachelor Degree Name"></td>
</div></tr> <tr>

<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Bachelor University:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="buniversity" class="form-control-in" placeholder="Bachelor's Univeristy"></td>
</div></tr><tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Bachelor Completed Year:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="byear" class="form-control-in" placeholder="Bachelor's Completed Year"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Master Degree:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="masterdegree" class="form-control-in" placeholder="Original Bachelor Degree Name"></td>
</div></tr> <tr>

<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Master's University:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="muniversity" class="form-control-in" placeholder="Master's Univeristy"></td>
</div></tr><tr>
<div class="form-group">
									<td width="25%" align="left" valign="top"><label>Master's Completed Year:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="myear" class="form-control-in" placeholder="Master's Completed Year"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>


<div class="form-group">
									<td width="10%" align="left" valign="top"><label>Resume</label></td>
									<td width="25%" align="left" valign="top"><input type="file" name="resume">
									 <p class="help-block">Insert only in .doc or .docx.</p></td>
</div>
</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="10%" align="left" valign="top"><label>Visa Copy</label></td>
									<td width="25%" align="left" valign="top"><input type="file" name="visacopy">
									 <p class="help-block">Insert only .jpg or .jpeg file.</p></td>
</div>
</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="10%" align="left" valign="top"><label>DL copy</label></td>
									<td width="25%" align="left" valign="top"><input type="file" name="dlcopy">
									 <p class="help-block">Insert only .jpg or .jpeg file.</p></td>
</div>
</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="10%" align="left" valign="top"><label>State ID copy</label></td>
									<td width="25%" align="left" valign="top"><input type="file" name="stateid">
									 <p class="help-block">Insert only .jpg or .jpeg file.</p></td>
</div>
</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="10%" align="left" valign="top"><label>Passport copy</label></td>
									<td width="25%" align="left" valign="top"><input type="file" name="passportcopy">
									 <p class="help-block">Insert only .jpg or .jpeg file.</p></td>
</div> -->
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>NDA (Agreement) Signed</label>
								<td width="90%" align="left" valign="top">	<select name="nda" class="form-control-in">
										<option value="Yes">Yes</option>
           								<option value="No">No</option>
            							<option value="Not Required">Not Required</option>
									</select></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

   							<td  align="left" ><button type="submit" name="save" class="btn btn-primary">Save</button> </td>					
                 </tr>
</form>
						
				</div></div>
			</div><!-- /.col-->
		</div><!-- /.row -->
		
	</div>


<?php

if(isset($_POST['save']))
		{
		$cfname = $_POST['cfname'];
		$cmname = $_POST['cmname'];
		$clname = $_POST['clname'];
		$skill = $_POST['skill'];
		$co_email = $_POST['co_email'];
		$status="1";
		$cm_email = "0";
		$cm_password = "0";	
		$cp_email = "0";
		$cp_password = "0";
		$colocation = $_POST['colocation'];
		$cmlocation = "0";
		$co_phonenumber = $_POST['co_phonenumber'];
		$cm_phonenumber = "0";
		$covisa = $_POST['covisa'];
		$cmvisa = "0";


		$relocation = $_POST['relocation']; 

		$resume = "0"; //resume

		$visacopy = "0"; //visa copy

		$dlcopy = "0"; //dlcopy

		$stateid = "0"; //state id

		$lastssn = $_POST['lastssn'];

		$passportcopy = "0"; //passport copy
		
		$passportnumber = $_POST['passportnumber'];
		$passportcountry = $_POST['passportcountry'];
		$bachelordegree = "0";
		$buniversity = "0";
		$byear = "0";
		$masterdegree = "0";
		$muniversity = "0";
		$myear = "0";				
		$nda = $_POST['nda'];
		$dateadded = date("Y-m-d");


		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		if(isset($co_email ))
		{
		 $query = "select * from consultants where `co_email` = \"$co_email\"";
		 $ins= $conn->prepare($query);
		 $ins->execute();
		 $dta = $ins->fetch();
		}

		 if(isset($dta['cid']))
		 {
echo "<script>
alert('This Consultant already exist in Database. Please check with Admin.');
window.location.href='admin.php?action=listconsultants';
</script>"; 
		 }
else {
		 $inquery = "INSERT INTO `consultants` (`cid`, `status`, `cfname`, `cmname`, `clname`, `skill`, `co_email`, `cm_email`, `cm_password`, `cp_email`, `cp_password`, `colocation`, `cmlocation`, `co_phonenumber`, `cm_phonenumber`, `covisa`, `cmvisa`, `relocation`, `resume`, `visacopy`, `dlcopy`, `stateid`, `lastssn`, `passportcopy`, `passportnumber`, `passportcountry`, `bachelordegree`, `buniversity`, `byear`, `masterdegree`, `muniversity`, `myear`, `dateadded`, `nda`) VALUES (NULL, '$status', '$cfname', '$cmname', '$clname', '$skill', '$co_email', '$cm_email', '$cm_password', '$cp_email', '$cp_password', '$colocation', '$cmlocation', '$co_phonenumber', '$cm_phonenumber', '$covisa', '$cmvisa', '$relocation', '$resume', '$visacopy', '$dlcopy', '$stateid', '$lastssn', '$passportcopy', '$passportnumber', '$passportcountry', '$bachelordegree', '$buniversity', '$byear', '$masterdegree', '$muniversity', '$myear', '$dateadded', '$nda');";
		$ins= $conn->prepare($inquery);
		$ins->execute();
		echo "<script>
alert('Consultant Added !!!');
window.location.href='admin.php?action=listconsultants';
</script>";
}
		}

}
else
{
	echo "<script>
alert('You Need to be Admin to view this page.');
window.location.href='admin.php';
</script>"; 
}
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>
