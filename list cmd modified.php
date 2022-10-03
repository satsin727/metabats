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
	$listid=$_GET['lid'];
	?>
	<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active"><?php echo $do; ?> List Details</li>
			</ol>
		</div>
		<?php
	if($do=='changestatus')
	{
		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$query ="Select * FROM lists where listid = $listid";
		$ins= $conn->prepare($query);
		$ins->execute();
		$udata = $ins->fetch();
		$status = $udata['status'];
if($udata['uid']==$userid || $dta['level'] == 1 || $dta['level'] == 2 )
		{
			if($status==1) 
			{ 
				$inquery = "UPDATE `lists` SET `status` = '0' WHERE `listid` = $listid";
				$ins= $conn->prepare($inquery);
				$ins->execute();
				echo "<script>
				alert(' List has been Disabled.');
				window.location.href='admin.php?action=listall';
				</script>"; 

			}

			if($status==0) 
			{ 
				$inquery = "UPDATE `lists` SET `status` = '1' WHERE `listid` = $listid";
				$ins= $conn->prepare($inquery);
				$ins->execute();
				echo "<script>
				alert(' List has been Enabled.');
				window.location.href='admin.php?action=listall';
				</script>"; 

			}
		}
	else { 
echo "<script>
				alert(' List is uploaded by another user.');
				window.location.href='admin.php?action=listall';
				</script>"; 


	}
	}

	if($do=='addcontact')
	{ 
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$query ="Select * FROM lists where listid = $listid";
		$ins= $conn->prepare($query);
		$ins->execute();
		$udata = $ins->fetch();
		$status = $udata['status'];
if($udata['uid']==$userid || $dta['level'] == 1 || $dta['level'] == 2 )
	{  ?>

		<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-heading"><a href="listcmd.php?do=uploadlist&lid=<?php echo $udata['listid']; ?>"><button name="addauser" class="btn btn-primary">Upload List </button></a></div>
					<div class="panel-body">

					<form action="#" method="post">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Company Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="companyname" class="form-control-in" placeholder="Company Name"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
								<td width="15%" align="left" valign="top">	<label>Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="name" class="form-control-in" placeholder="Name"></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Email ID:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="email" class="form-control-in" placeholder="Email Address"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Phone Number:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input type="phone" name="phone" class="form-control-in" placeholder="Phone Number"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Location:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="location" class="form-control-in" placeholder="Location"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Timezone</label></td>
								<td width="90%" align="left" valign="top">	<select name="timezone" class="form-control-in">
										<option value="1"> EST</option>
           								<option value="2"> CST</option>
            							<option value="3"> MST</option>
            							<option value="4"> PST</option>
									</select></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

   							<td  align="left" ><button type="submit" name="save" class="btn btn-primary">Save</button> </td>					
                 </tr>
                 </table>

</form>
						
				</div></div>
			</div><!-- /.col-->
		</div><!-- /.row -->
		

			
<?php	}
	else { 
echo "<script>
				alert(' List is uploaded by another user.');
				window.location.href='admin.php?action=listall';
				</script>"; 


	}

} //do add contact
			elseif($do=='uploadlist')
	{ 
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$query ="Select * FROM lists where listid = $listid";
		$ins= $conn->prepare($query);
		$ins->execute();
		$udata = $ins->fetch();
		$status = $udata['status'];
if($udata['uid']==$userid || $dta['level'] == 1 || $dta['level'] == 2 )
	{  ?>

		<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
							<div class="panel-body">

					<form action="listupload.php" method="post" target="_blank">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
		<div class="form-group">
		<td width="10%" align="left" valign="top"><label>Upload List</label></td>
		<td width="25%" align="left" valign="top"><input type="file" name="file">
		<p class="help-block">Upload only in .csv or .xls or .xlsx file only.</p></td>
		<input type="hidden" name="lid" value="<?php echo $listid; ?>">
</div> 
</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

   							<td  align="left" ><button type="submit" name="submit" class="btn btn-primary">Import</button> </td>					
                 </tr>
                 </table>

</form>
						
				</div></div>
			</div><!-- /.col-->
		</div><!-- /.row -->
				
<?php	}
	else { 
echo "<script>
				alert(' List is uploaded by another user.');
				window.location.href='admin.php?action=listall';
				</script>"; 


	}

} //do add contact
	else
	{
		echo "<script>
alert('Not a valid command.');
window.location.href='admin.php?action=listusers';
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