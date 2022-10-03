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
$conn=null;

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
								<td width="15%" align="left" valign="top">	<label>List Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="listname" class="form-control-in" placeholder="List Name"></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> 
<tr> 	<td  align="left" ><button type="submit" name="save" class="btn btn-primary">Save</button> </td>					
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
		$listname = $_POST['listname'];
		$uid=$dta['uid'];
		$date = date("Y-m-d H:i:s");
		$status="1";
		$total="0";
		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$query = "INSERT INTO `lists` (`listid`, `listname`, `uid`, `date`, `status`, `total`) VALUES (NULL,'$listname', '$uid', '$date', '$status', '$total');";
		$ins= $conn->prepare($query);
		$ins->execute();
		header( "Location: admin.php?action=listall" );

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
