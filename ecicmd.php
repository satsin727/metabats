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
$userid=$dta['uid'];

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
$selected = "listall";
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3 )
{


if(isset($_GET['do']))
{
	$do="foobar";
	$do=$_GET['do'];	
	$eciid=$_GET['eciid'];
	?>
	<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>

			</ol>
		</div>
		<?php
		
		
		
	if($do=='changestatus')
	{
		$query ="Select * FROM eci where eci_id = $eciid";
		$ins= $conn->prepare($query);
		$ins->execute();
		$udata = $ins->fetch();
		$status = $udata['eci_happened'];
if($udata['sm_id']==$userid || $dta['level'] == 1 || $dta['level'] == 2 )
		{
			if($status==1) 
			{ 
				$inquery = "UPDATE `eci` SET `eci_happened` = '0' WHERE `eci_id` = $eciid";
				$ins= $conn->prepare($inquery);
				$ins->execute();
				echo "<script>
				alert('Status changed');
				window.location.href='admin.php?action=showeci';
				</script>"; 

			}

			if($status==0) 
			{ 
                $inquery = "UPDATE `eci` SET `eci_happened` = '1' WHERE `eci_id` = $eciid";
				$ins= $conn->prepare($inquery);
				$ins->execute();
				echo "<script>
				alert('Status changed');
				window.location.href='admin.php?action=showeci';
				</script>"; 

			}
		}
	else { 
echo "<script>
				alert('You ar enot allowed to do changes');
				window.location.href='admin.php?action=showeci';
				</script>"; 
	}	
    }

	if($do=='addt1client')
	{
		?>
		<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<div class="row">
						<div class="col-lg-12">
							<div class="panel panel-default">
								<div class="panel-heading"></div> 
								<div class="panel-body">

								<form action="#" method="post">
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr> <h4>IP/Tier 1 Details<h4></tr>
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
			</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr>
			<tr>
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