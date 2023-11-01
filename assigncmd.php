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


if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2)
{


if(isset($_GET['do']))
{
	$do="foobar";
	$do=$_GET['do'];	
	$id=$_GET['id'];
	?>
	<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Change Assignments</li>
			</ol>
		</div>
		<?php

	if($do=='delete')
	{
		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$inquery = "DELETE FROM `assigned` WHERE `id` = $id;";
		$ins= $conn->prepare($inquery);
		$ins->execute();
		$conn=null;
		header( "Location: admin.php?action=assign" );
	}
	
	if(isset($_POST['assign']))  
{ 
$uid = $_POST['userid'];
$cid = $_POST['consultantid'];
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "UPDATE `assigned` SET `cid` = '$cid', `uid` = '$uid' WHERE `id` = $id;";
$ins= $conn->prepare($query);
$ins->execute();
header( "Location: admin.php?action=assign" );

}
	if($do=='change')
	{ 
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from assigned where `id` =  $id;";
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetch();
$conn=null;



?>				<form action="#" method="post">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
<div class="form-group">
									<td width="10%" align="left" valign="top"><label>Select User</label> </td>									
								<td width="30%" align="left" valign="top">
									<?php
									$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
									$query = "select * from users where `status` = 1";
									$ins= $conn->prepare($query);
									$ins->execute();
									$udata = $ins->fetchAll();
									?> 
									<select name="userid">class="form-control-in">
									<?php
									foreach( $udata as $row1) {
									?>
										<option value="<?php echo $row1['uid']; ?>"><?php echo $row1['name']; ?></option>
										<?php } ?>
									</select></td>
<td width="5%" align="left" valign="top"><label>&nbsp;===>&nbsp;</label></td>
									<td width="15%" align="left" valign="top"><label>Select Consultant</label></td>							
								<td width="30%" align="left" valign="top">	<select name="consultantid" class="form-control-in">

									<?php
									$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
									$query2 = "select * from consultants where `status` = 1";
									$ins2= $conn->prepare($query2);
									$ins2->execute();
									$cdata = $ins2->fetchAll();
									foreach( $cdata as $row2) {
									?>
										<option value="<?php echo $row2['cid']; ?>"><?php echo $row2['cfname']; ?></option>
										<?php } ?>
									</select></td>
									</div>
									</tr>
							<tr>
							<tr> <td align="left" valign="top">&nbsp;</td>	</tr>
<div class="form-group">	<td  width="0%" align="left" valign="top">	
   							<td  width="30%" align="left" valign="top"><button type="submit" name="assign" class="btn btn-primary">Assign</button> </td> </div>					
                 </tr>
                 <tr> <td align="left" valign="top">&nbsp;</td>	</tr>

                 </table>
</form>  
<?php
} //do edit
	
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