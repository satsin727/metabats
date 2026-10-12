<?php
if($_SESSION['id'])
{
$sessid = $_SESSION['id'];
}
else
{
	header( "Location: admin.php" ); 

}

//testing

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
<?php if (!empty($addConsultantError)) { ?><div class="alert alert-danger"><?php echo htmlspecialchars($addConsultantError, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
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
									<td width="25%" align="left" valign="top"><label>DOB:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input type="date" name="dob" class="form-control-in" value="<?php echo htmlspecialchars($_POST['dob'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" max="<?php echo date('Y-m-d'); ?>" required></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

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
									<td width="25%" align="left" valign="top"><label>Last SSN Digits:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="lastssn" class="form-control-in" placeholder="Last SSN Digits"></td>
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

if (isset($_POST['save'])) {
    $cfname = trim((string)($_POST['cfname'] ?? ''));
    $cmname = trim((string)($_POST['cmname'] ?? ''));
    $clname = trim((string)($_POST['clname'] ?? ''));
    $skill = filter_var($_POST['skill'] ?? null, FILTER_VALIDATE_INT);
    $co_email = trim((string)($_POST['co_email'] ?? ''));
    $dob = trim((string)($_POST['dob'] ?? ''));
    $colocation = trim((string)($_POST['colocation'] ?? ''));
    $co_phonenumber = trim((string)($_POST['co_phonenumber'] ?? ''));
    $covisa = trim((string)($_POST['covisa'] ?? ''));
    $relocation = trim((string)($_POST['relocation'] ?? ''));
    $lastssnInput = trim((string)($_POST['lastssn'] ?? ''));
    $passportnumber = trim((string)($_POST['passportnumber'] ?? ''));
    $passportcountry = trim((string)($_POST['passportcountry'] ?? ''));
    $nda = (string)($_POST['nda'] ?? 'No');

    $parsedDob = DateTimeImmutable::createFromFormat('!Y-m-d', $dob);
    $validDob = $parsedDob !== false && $parsedDob->format('Y-m-d') === $dob
        && $dob <= date('Y-m-d');

    if ($cfname === '' || $clname === '' || !$skill || !$validDob) {
        echo '<script>alert(' . json_encode('Please provide first name, last name, skill, and a valid DOB using the calendar.') . ');</script>';
    } elseif ($lastssnInput !== '' && !ctype_digit($lastssnInput)) {
        echo '<script>alert(' . json_encode('SSN digits must contain numbers only.') . ');</script>';
    } else {
        try {
            $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check duplicate original email only when it is populated.
            $exists = false;
            if ($co_email !== '') {
                $check = $conn->prepare('SELECT cid FROM consultants WHERE co_email = :email LIMIT 1');
                $check->execute([':email' => $co_email]);
                $exists = (bool)$check->fetchColumn();
            }

            if ($exists) {
                echo '<script>alert(' . json_encode('This Consultant already exists in Database. Please check with Admin.') . ');</script>';
            } else {
                // dateadded is generated server-side; never taken from the form.
                // Columns not entered here retain the original application defaults.
                $sql = "INSERT INTO consultants
                    (status, cfname, cmname, clname, skill, co_email, cm_email,
                     cm_password, cp_email, cp_password, colocation, cmlocation,
                     co_phonenumber, cm_phonenumber, covisa, cmvisa, relocation,
                     resume, visacopy, dlcopy, stateid, lastssn, passportcopy,
                     dob, passportnumber, passportcountry, bachelordegree,
                     buniversity, byear, masterdegree, muniversity, myear,
                     dateadded, nda)
                    VALUES
                    (1, :cfname, :cmname, :clname, :skill, :co_email, '0',
                     '0', '0', '0', :colocation, '0',
                     :co_phonenumber, '0', :covisa, '0', :relocation,
                     '0', '0', '0', '0', :lastssn, '0',
                     :dob, :passportnumber, :passportcountry, '0',
                     '0', 0, '0', '0', 0,
                     :dateadded, :nda)";
                $insert = $conn->prepare($sql);
                $insert->execute([
                    ':cfname' => $cfname,
                    ':cmname' => $cmname,
                    ':clname' => $clname,
                    ':skill' => $skill,
                    ':co_email' => $co_email,
                    ':colocation' => $colocation,
                    ':co_phonenumber' => $co_phonenumber,
                    ':covisa' => $covisa,
                    ':relocation' => $relocation,
                    ':lastssn' => $lastssnInput === '' ? 0 : (int)$lastssnInput,
                    ':dob' => $dob,
                    ':passportnumber' => $passportnumber,
                    ':passportcountry' => $passportcountry,
                    ':dateadded' => date('Y-m-d'),
                    ':nda' => $nda,
                ]);
                echo '<script>alert(' . json_encode('Consultant Added !!!') . ');window.location.href="admin.php?action=listconsultants";</script>';
            }
        } catch (PDOException $e) {
            error_log('Add consultant failed: ' . $e->getMessage());
            echo '<script>alert(' . json_encode('Unable to save consultant. Please contact the administrator.') . ');</script>';
        } finally {
            $conn = null;
        }
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
