<?php
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

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3 )
{
?> 


<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href=""><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Add Issues/Escalations</li>
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
								<td width="15%" align="left" valign="top">	<label>Issue headline:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="headline" class="form-control-in" placeholder="Name"></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Description:&nbsp;&nbsp;&nbsp;</label></td>
                                    <td width="90%" align="left" valign="top">	<textarea class="ckeditor" name="issuedesc" ></textarea> </td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Related to:</label></td>
								<td width="90%" align="left" valign="top">	<select name="consultant_id" class="form-control-in">
                                <option value="0">None - other escalations</option>
                                <?php
                                $query = "select * from consultants order by cfname asc";
                                $ins= $conn->prepare($query);
                                $ins->execute();
                                $data = $ins->fetchAll();

                                foreach($data as $consultant)
{

?>
										<option value="<?php echo $consultant['cid']; ?>"><?php echo $consultant['cfname']." ".$consultant['cmname']." ".$consultant['clname']; ?></option>
                        <?php } ?>
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
		$sm_id = $sessid;
		$headline=$_POST['headline'];	
		$issuedesc=$_POST['issuedesc'];	
		$consultant_id=$_POST['consultant_id'];		


		$inquery = "INSERT INTO `issues` (`headline`, `issuedesc`, `sm_id`, `consultant_id`) VALUES (:headline, '$issuedesc', '$sm_id', '$consultant_id');";
		$ins= $conn->prepare($inquery);
		$ins->bindParam(':headline', $headline, PDO::PARAM_STR);
		$ins->execute();
		header( "Location: admin.php?action=listissues&status=1" );
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
