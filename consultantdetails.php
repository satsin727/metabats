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
$selected = "listconsultants";
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2|| $dta['level'] == 3)
{


					if(isset($_GET['add']))
											{
												$add=$_GET['add'];	
												$cid=$_GET['id'];
												?>
												<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
													<div class="row">
														<ol class="breadcrumb">
															<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
															<li class="active">Add Details</li>
														</ol>
													</div>
													<?php
												if($add=='marketdetails')
												{
																					$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
																					$query ="Select * FROM consultants where `cid` = :cid";
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
																												<td width="25%" align="left" valign="top"><label>Consultant Marketing Email:&nbsp;&nbsp;&nbsp;</label></td>
																											<td width="90%" align="left" valign="top">	<input name="cm_email" class="form-control-in" value="<?php echo $udata['cm_email']; ?>" placeholder="Marketing Email Address"></td>
																			</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
																			<div class="form-group">
																												<td width="25%" align="left" valign="top"><label>Marketing Email Password:&nbsp;&nbsp;&nbsp;</label></td>
																											<td width="90%" align="left" valign="top">	<input name="cm_password"  value="<?php echo $udata['cm_password']; ?>" class="form-control-in" placeholder="Marketing Password"></td>
																			</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
																			<div class="form-group">
																												<td width="25%" align="left" valign="top"><label>Consultant Posting Email:&nbsp;&nbsp;&nbsp;</label></td>
																											<td width="90%" align="left" valign="top">	<input name="cp_email"  value="<?php echo $udata['cp_email']; ?>" class="form-control-in" placeholder="Posting Email Address"></td>
																			</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
																			<div class="form-group">
																												<td width="25%" align="left" valign="top"><label>Marketing Posting Password:&nbsp;&nbsp;&nbsp;</label></td>
																											<td width="90%" align="left" valign="top">	<input name="cp_password"  value="<?php echo $udata['cp_password']; ?>" class="form-control-in" placeholder="Posting Password"></td>
																			</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr> 
																			<div class="form-group">
																												<td width="25%" align="left" valign="top"><label>Consultant Marketing Location:&nbsp;&nbsp;&nbsp;</label></td>
																											<td width="90%" align="left" valign="top">	<input name="cmlocation"  value="<?php echo $udata['cmlocation']; ?>" class="form-control-in" placeholder="Marketing location"></td>
																			</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
																			<div class="form-group">
																												<td width="25%" align="left" valign="top"><label>Consultant Marketing Phone no:&nbsp;&nbsp;&nbsp;</label></td>
																											<td width="90%" align="left" valign="top">	<input name="cm_phonenumber"  value="<?php echo $udata['cm_phonenumber']; ?>" class="form-control-in" placeholder="Marketing Marketing Phone no."></td>
																			</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr> 
																			<div class="form-group">
																												<td width="25%" align="left" valign="top"><label>Consultant Marketing visa:&nbsp;&nbsp;&nbsp;</label></td>
																											<td width="90%" align="left" valign="top">	<input name="cmvisa"  value="<?php echo $udata['cmvisa']; ?>" class="form-control-in" placeholder="Marketing Visa Status"></td>
																			</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr>
																			<tr>

																			   							<td  align="left" ><button type="submit" name="update" class="btn btn-primary">Update</button> </td>
																										
																			                 </tr>
																			</form>
																									
																							</div></div>
																						</div><!-- /.col-->
																					</div><!-- /.row -->


																			<?php	
																			if(isset($_POST['update']))
																					{
																					
																					$cm_email = $_POST['cm_email'];
																					$cm_password = $_POST['cm_password'];	
																					$cp_email = $_POST['cp_email'];
																					$cp_password = $_POST['cp_password'];
																					$cmlocation = $_POST['cmlocation'];
																					$cm_phonenumber = $_POST['cm_phonenumber'];
																					$cmvisa = $_POST['cmvisa'];


																					$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
																					 $query = "select * from consultants where `cid` = :cid";
																					 $ins= $conn->prepare($query);
																					 $ins->bindValue( ":cid", $cid, PDO::PARAM_INT );
																					 $ins->execute();
																					 $dta = $ins->fetch();

																					 if($dta['cid'])
																					 {

																					 $inquery = "UPDATE `consultants` SET `cm_email` = '$cm_email', `cm_password` = '$cm_password', `cp_email` = '$cp_email', `cp_password` = '$cp_password', `cmlocation` = '$cmlocation', `cm_phonenumber` = '$cm_phonenumber', `cmvisa` = '$cmvisa' WHERE `cid` = :cid;";
																					$ins= $conn->prepare($inquery);
																					$ins->bindValue( ":cid", $cid, PDO::PARAM_INT );
																					$ins->execute();																					
																					$conn=null;
																					header( "Location: admin.php?action=listconsultants" ); 
																					}
																					}  // isset post add-> marketing details
											} //do market details

												if($add=='educationdetails')
												{
																					$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
																					$query ="Select * FROM consultants where `cid` = :cid";
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
																												<td width="25%" align="left" valign="top"><label>Bachelor Degree:&nbsp;&nbsp;&nbsp;</label></td>
																											<td width="90%" align="left" valign="top">	<input name="bachelordegree"  value="<?php echo $udata['bachelordegree']; ?>" class="form-control-in" placeholder="Original Bachelor Degree Name"></td>
																			</div></tr> <tr>

																			<div class="form-group">
																												<td width="25%" align="left" valign="top"><label>Bachelor University:&nbsp;&nbsp;&nbsp;</label></td>
																											<td width="90%" align="left" valign="top">	<input name="buniversity"  value="<?php echo $udata['buniversity']; ?>" class="form-control-in" placeholder="Bachelor's Univeristy"></td>
																			</div></tr><tr>
																			<div class="form-group">
																												<td width="25%" align="left" valign="top"><label>Bachelor Completed Year:&nbsp;&nbsp;&nbsp;</label></td>
																											<td width="90%" align="left" valign="top">	<input name="byear"  value="<?php echo $udata['byear']; ?>" class="form-control-in" placeholder="Bachelor's Completed Year"></td>
																			</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
																			<div class="form-group">
																												<td width="25%" align="left" valign="top"><label>Master Degree:&nbsp;&nbsp;&nbsp;</label></td>
																											<td width="90%" align="left" valign="top">	<input name="masterdegree"  value="<?php echo $udata['masterdegree']; ?>" class="form-control-in" placeholder="Original Bachelor Degree Name"></td>
																			</div></tr> <tr>

																			<div class="form-group">
																												<td width="25%" align="left" valign="top"><label>Master's University:&nbsp;&nbsp;&nbsp;</label></td>
																											<td width="90%" align="left" valign="top">	<input name="muniversity"  value="<?php echo $udata['muniversity']; ?>" class="form-control-in" placeholder="Master's Univeristy"></td>
																			</div></tr><tr>
																			<div class="form-group">
																												<td width="25%" align="left" valign="top"><label>Master's Completed Year:&nbsp;&nbsp;&nbsp;</label></td>
																											<td width="90%" align="left" valign="top">	<input name="myear"  value="<?php echo $udata['myear']; ?>" class="form-control-in" placeholder="Master's Completed Year"></td>
																			</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> 
																			<tr>

																			   							<td  align="left" ><button type="submit" name="update" class="btn btn-primary">Update</button> </td>
																										
																			                 </tr>
																			</form>
																									
																							</div></div>
																						</div><!-- /.col-->
																					</div><!-- /.row -->


																			<?php	
																			if(isset($_POST['update']))
																					{
																					
																					$bachelordegree = $_POST['bachelordegree'];
																					$buniversity = $_POST['buniversity'];
																					$byear = $_POST['byear'];
																					$masterdegree = $_POST['masterdegree'];
																					$muniversity = $_POST['muniversity'];
																					$myear = $_POST['myear'];


																					$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
																					 $query = "select * from consultants where `cid` = :cid";
																					 $ins= $conn->prepare($query);
																					 $ins->bindValue( ":cid", $cid, PDO::PARAM_INT );
																					 $ins->execute();
																					 $dta = $ins->fetch();

																					 if($dta['cid'])
																					 {

																					 $inquery = "UPDATE `consultants` SET `bachelordegree` = '$bachelordegree', `buniversity` = '$buniversity', `byear` = '$byear', `masterdegree` = '$masterdegree', `muniversity` = '$muniversity', `myear` = '$myear' WHERE `cid` = :cid";
																					$ins= $conn->prepare($inquery);
																					$ins->bindValue( ":cid", $cid, PDO::PARAM_INT );
																					$ins->execute();																					
																					$conn=null;
																					header( "Location: admin.php?action=listconsultants" ); 
																					}
																					}  // isset post add-> education details
											} //add education


											if($add=='files')
											{
																						$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
																						$query ="Select * FROM consultants where `cid` = :cid";
																						$ins= $conn->prepare($query);
																						$ins->bindValue( ":cid", $cid, PDO::PARAM_INT );
																						$ins->execute();
																						$udata = $ins->fetch();
																						$conn=null;
																						$resume = $udata['resume'];	
																						$visacopy = $udata['visacopy']; //visa copy
																						$dlcopy = $udata['dlcopy']; //dlcopy
																						$stateid = $udata['stateid']; //state id
																						$passportcopy = $udata['passportcopy']; //passport copy
																				?>
																						
																						<div class="row">
																							<div class="col-lg-12">&nbsp;
																							</div>
																						</div><!--/.row-->
																								
																						
																						<div class="row">
																							<div class="col-lg-12">
																								<div class="panel panel-default">
																									<div class="panel-body">

																									<form action="#" method="post" enctype="multipart/form-data">
																				  <table width="100%" border="0" cellspacing="0" cellpadding="0">
																				          <tr>
																				<div class="form-group">
																													<td width="10%" align="left" valign="top"><label>Resume</label></td>
																													<td width="25%" align="left" valign="top"><input type="file" name="resume1">
																													<p class="help-block">Insert only in .doc or .docx.</p></td>
																												<?php if($resume!=='0') {  ?>	<td width="60%" align="left" valign="top"><?php echo "Uploaded Resume: "; ?><a href="download.php?resume=<?php echo $resume; ?>&cid=<?php echo $udata['cid']; ?>"><?php  echo $udata['cfname']." Resume"; ?></a></td> <?php } ?>
																				</div> 
																				</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
																				<div class="form-group">
																													<td width="10%" align="left" valign="top"><label>Visa Copy</label></td>
																													<td width="25%" align="left" valign="top"><input type="file" name="visacopy">
																													 <p class="help-block">Insert only .jpg or .jpeg file.</p></td>
																													 <?php if($visacopy!=='0') {  ?>	<td width="60%" align="left" valign="top"><?php echo "Uploaded Visa Copy: "; ?><a href="download.php?visacopy=<?php echo $visacopy; ?>&cid=<?php echo $udata['cid']; ?>"><?php  echo $udata['cfname']." Visa"; ?></a></td> <?php } ?>
																				</div>
																				</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
																				<div class="form-group">
																													<td width="10%" align="left" valign="top"><label>DL copy</label></td>
																													<td width="25%" align="left" valign="top"><input type="file" name="dlcopy">
																													 <p class="help-block">Insert only .jpg or .jpeg file.</p></td>
																													 <?php if($dlcopy!=='0') {  ?>	<td width="60%" align="left" valign="top"><?php echo "Uploaded DL Copy: "; ?><a href="download.php?dlcopy=<?php echo $dlcopy; ?>&cid=<?php echo $udata['cid']; ?>"><?php  echo $udata['cfname']." DL"; ?></a></td> <?php } ?>
																				</div>
																				</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
																				<div class="form-group">
																													<td width="10%" align="left" valign="top"><label>State ID copy</label></td>
																													<td width="25%" align="left" valign="top"><input type="file" name="stateid">
																													 <p class="help-block">Insert only .jpg or .jpeg file.</p></td>
																													 <?php if($stateid!=='0') {  ?>	<td width="60%" align="left" valign="top"><?php echo "Uploaded State ID Copy: "; ?><a href="download.php?stateid=<?php echo $stateid; ?>&cid=<?php echo $udata['cid']; ?>"><?php  echo $udata['cfname']." StateID Copy"; ?></a></td> <?php } ?>
																				</div>
																				</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
																				<div class="form-group">
																													<td width="10%" align="left" valign="top"><label>Passport copy</label></td>
																													<td width="25%" align="left" valign="top"><input type="file" name="passportcopy">
																													 <p class="help-block">Insert only .jpg or .jpeg file.</p></td>
																													 <?php if($passportcopy!=='0') {  ?>	<td width="60%" align="left" valign="top"><?php echo "Uploaded Passport Copy: "; ?><a href="download.php?passportcopy=<?php echo $passportcopy; ?>&cid=<?php echo $udata['cid']; ?>"><?php  echo $udata['cfname']." Passport Copy"; ?></a></td> <?php } ?>
																				</div> 
																				</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> 
																				<tr>

																				   							<td  align="left" ><input type="submit" name="update" class="btn btn-primary"> </td>
																											
																				                 </tr>
																				</form>
																										
																								</div></div>
																							</div><!-- /.col-->
																						</div><!-- /.row -->


																				<?php	
																				if(isset($_POST['update']))
																						{
																									$rname=$_FILES['resume1']['name'];
																									$rext = strtolower(substr(strrchr($rname, '.'), 1));
																								if($rext == doc || $rext == docx)
																								{
																									
																									$date = date("Y-m-d H:i:s");
																									$hresume =md5(md5($rname).$date);
																									$file= $hresume.".".$rext;
																									$target = "consultants_data/resume/".$file;
																									if($udata['resume'] !== 0)
																									{
																									$oresume = "consultants_data/resume/".$resume;
																									unlink($oresume);
																									}	 
																									$resume=$file;											
																									move_uploaded_file($_FILES["resume1"]["tmp_name"], $target);
																								} //rname

																								$visaname = $_FILES['visacopy']['name'];
																								$vext = strtolower(substr(strrchr($visaname, '.'), 1));
																								if($vext == jpg || $vext == jpeg)
																								{
																									
																									$date = date("Y-m-d H:i:s");
																									$hvisa =md5(md5($visaname).$date);
																									$file= $hvisa.".".$vext;
																									$vtarget = "consultants_data/visa/".$file;
																									if($udata['visacopy'] !== 0)
																									{
																									$ovisa = "consultants_data/visa/".$visacopy;
																									unlink($ovisa);
																									}	 
																									$visacopy=$file;											
																									move_uploaded_file($_FILES["visacopy"]["tmp_name"], $vtarget);
																								}

																								$dlname = $_FILES['dlcopy']['name'];
																								$dext = strtolower(substr(strrchr($dlname, '.'), 1));
																								if($dext == jpg || $dext == jpeg)
																								{
																									
																									$date = date("Y-m-d H:i:s");
																									$hdl =md5(md5($dlname).$date);
																									$file= $hdl.".".$dext;
																									$dtarget = "consultants_data/dl/".$file;
																									if($udata['dlcopy'] !== 0)
																									{
																									$odl = "consultants_data/dl/".$dlcopy;
																									unlink($odl);
																									}	 
																									$dlcopy=$file;											
																									move_uploaded_file($_FILES["dlcopy"]["tmp_name"], $dtarget);
																								}


																								$stateidname = $_FILES['stateid']['name'];
																								$sext = strtolower(substr(strrchr($stateidname, '.'), 1));
																								if($sext == jpg || $sext == jpeg)
																								{
																									
																									$date = date("Y-m-d H:i:s");
																									$sdl =md5(md5($stateidname).$date);
																									$file= $sdl.".".$sext;
																									$starget = "consultants_data/stateid/".$file;
																									if($udata['stateid'] !== 0)
																									{
																									$sdl = "consultants_data/stateid/".$stateid;
																									unlink($sdl);
																									}	 
																									$stateid=$file;											
																									move_uploaded_file($_FILES["stateid"]["tmp_name"], $starget);
																								}



																								$pcname = $_FILES['passportcopy']['name'];
																								$pext = strtolower(substr(strrchr($pcname, '.'), 1));
																								if($pext == jpg || $pext == jpeg)
																								{
																									
																									$date = date("Y-m-d H:i:s");
																									$pdl =md5(md5($pcname).$date);
																									$file= $pdl.".".$pext;
																									$ptarget = "consultants_data/passportcopy/".$file;
																									if($udata['passportcopy'] !== 0)
																									{
																									$pdl = "consultants_data/passportcopy/".$passportcopy;
																									unlink($pdl);
																									}	 
																									$passportcopy=$file;											
																									move_uploaded_file($_FILES["passportcopy"]["tmp_name"], $ptarget);
																								}
																					
																					
																						

																									$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
																									 $query = "select * from consultants where `cid` = :cid";
																									 $ins= $conn->prepare($query);
																									 $ins->bindValue( ":cid", $cid, PDO::PARAM_INT );
																									 $ins->execute();
																									 $dta = $ins->fetch();

																									 if($dta['cid'])
																									 {

																									 $inquery = "UPDATE `consultants` SET `resume` = '$resume', `visacopy` = '$visacopy', `dlcopy` = '$dlcopy', `stateid` = '$stateid', `passportcopy` = '$passportcopy' WHERE `consultants`.`cid` = :cid;";
																									$ins= $conn->prepare($inquery);
																									$ins->bindValue( ":cid", $cid, PDO::PARAM_INT );
																									$ins->execute();																						
																									$conn=null;
																									} 
																				header( "Location: admin.php?action=listconsultants" ); 
																						}  // isset post add-> files

											} //add files
											if($add!=='marketdetails' && $add!=='files' && $add!=='educationdetails') {
											echo "<script>
											alert('Not a valid command.');
											window.location.href='admin.php?action=listconsultants';
											</script>";
											}
												


					} //for isset $add

					} //for admin/manager
else
{
	echo "<script>
alert('You Need to be Admin or Manager to view this page.');
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