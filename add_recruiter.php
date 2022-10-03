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

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3 )
{
?> 


<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href=""><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Add List</li>
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
								<td width="15%" align="left" valign="top">	<label>Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="name" class="form-control-in" placeholder="Name"></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Email ID:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="email" class="form-control-in" placeholder="Email Address"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Phone Number:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input type="phone" name="password" class="form-control-in" placeholder="Type a Password"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Company Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="companyname" class="form-control-in" placeholder="Company Name"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Access level</label></td>
								<td width="90%" align="left" valign="top">	<select name="level" class="form-control-in">
										<option value="1"> Admin</option>
           								<option value="2"> Manager/Lead</option>
            							<option value="3"> Recuiter</option>
									</select></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

   							<td  align="left" ><button type="submit" name="save" class="btn btn-primary">Save</button> </td>					
                 </tr>
                 </table>

</form>
						
				</div></div>
			</div><!-- /.col-->
		</div><!-- /.row -->
		
	</div>


<?php

if(isset($_POST['save']))
		{
		$username = $_POST['email'];
		$name=$_POST['name'];		
		 $u = $username;
	   	 $p= $_POST['password'];
	   	 	$mdemail = md5($p); 
	    	$baseemail = base64_encode($p);
	    	$code = base64_encode($baseemail); 
	    $uhash = md5($u);
	    $companyname=$_POST['companyname'];
	    $email=$_POST['email'];
	    $password = md5($mdemail.$code);
	    $sess ="0";
	    $level=$_POST['level'];
	    $status=1;
		$ptext = $code;
		$lastloginip = "0.0.0.0";
		$date = date("Y-m-d H:i:s");
		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		 $query = "select * from users where `email` = :email";
		 $ins= $conn->prepare($query);
		 $ins->bindValue( ":email", $email, PDO::PARAM_STR );
		 $ins->execute();
		 $dta = $ins->fetch();

		 if($dta['uid'])
		 {
echo "<script>
alert('A User with this email address exist.');
window.location.href='admin.php?action=listusers';
</script>"; 
		 }
else {
		 $inquery = "INSERT INTO `users` (`uid`,`username`, `name`, `uhash`, `companyname`,`email`, `password`, `sess`, `level`, `status`, `ptext`, `lastloginip`, `date`) VALUES (NULL, '$username', '$name', '$uhash', '$companyname', '$email', '$password', '$sess', '$level', '$status', '$ptext', '$lastloginip', '$date');";
		
		$ins= $conn->prepare($inquery);
		$ins->execute();
		header( "Location: admin.php?action=listusers" );
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
