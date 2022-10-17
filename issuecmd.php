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
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{


if(isset($_GET['do']))
{
	$do="foobar";
	$do=$_GET['do'];	
	$id=$_GET['id'];
	?>

		<?php

	if($do=='close')
	{
		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$inquery = "UPDATE `issues` SET `status` = 0 WHERE `issueid` = $id";
		$ins= $conn->prepare($inquery);
		$ins->execute();
		$conn=null;
		header( "Location: admin.php?action=listissues&status=1" );
	}
    if($do=='view')
    { 
        $conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
        $query = "select * from issues where `issueid` =  $id;";
        $ins= $conn->prepare($query);
        $ins->execute();
        $data = $ins->fetch();
        ?>
            	<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
						<div class="row">
							<ol class="breadcrumb">
								<li><a href=""><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
								<li class="active">View Issue</li>
							</ol>
						</div><!--/.row-->
						
						<div class="row">
							<div class="col-lg-12">&nbsp;
							</div>
						</div><!--/.row-->
								
						
						<div class="row">
							<div class="col-lg-12">
								<div class="panel panel-default">
                                <a href="admin.php?action=listissues&status=1"><button name="back" class="btn btn-primary">Back</button></a> </div>
                                <div class="panel-heading"> <h2><b><?php echo $data['headline'];?></b></h2></div>
                            
                            
					
									<div class="panel-body">
                                
								
                                <?php

                                echo $data['issuedesc'];


?>
	
										
								</div></div>
							</div><!-- /.col-->
						</div><!-- /.row -->
						
				</div>	

        <?php
    }
	if($do=='reopen')
	{
		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$inquery = "UPDATE `issues` SET `status` = 1 WHERE `issueid` = $id";
		$ins= $conn->prepare($inquery);
		$ins->execute();
		$conn=null;
		header( "Location: admin.php?action=listissues&status=0" );
	}

	if($do=='edit')
	{ 
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from issues where `issueid` =  $id;";
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetch();
$conn=null;

$cid = $data['consultant_id'];

?>
	<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Edit Issue</li>
			</ol>
		</div>	
			<form action="#" method="post">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
<div class="form-group">
								<td width="15%" align="left" valign="top">	<label>Issue headline:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="headline" class="form-control-in" placeholder="headline" value="<?php echo $data['headline']; ?>"></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Description:&nbsp;&nbsp;&nbsp;</label></td>
                                    <td width="90%" align="left" valign="top">	<textarea class="ckeditor" name="issuedesc" ><?php echo $data['issuedesc'];  ?></textarea> </td>
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
<input type="hidden" name="issueid" id="issueid" value="<?php echo trim($id); ?>"/> </td> <tr>

   							<td  align="left" ><button type="submit" name="update" class="btn btn-primary">Update</button> </td>					
                 </tr>
                 </table> </div>

</form>  
<?php
} //do edit

} //for $do
else
{
    echo "<script>
alert('Not a valid command.');
window.location.href='admin.php?action=listissues';
</script>";
}	
if(isset($_POST['update']))

{

 //   $sm_id = $sessid;
    $issueid = $_POST['issueid'];
    $headline=$_POST['headline'];	
    $issuedesc=$_POST['issuedesc'];	
    $consultant_id=$_POST['consultant_id'];		


    $inquery = "Update `issues` SET `headline` = $headline, `issuedesc` = $issuedesc, `consultant_id`= $consultant_id where issueid = $issueid";
    $ins= $conn->prepare($inquery);
    $ins->execute();
    header( "Location: admin.php?action=listissues&status=1" );

}

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