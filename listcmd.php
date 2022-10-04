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
	
elseif($do=='editcontact')
	{ 
		$cid=$_GET['id'];
		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$query ="Select * FROM lists where listid = $listid";
		$ins= $conn->prepare($query);
		$ins->execute();
		$udata = $ins->fetch();
		$status = $udata['status'];
		$conn = null;
if($udata['uid']==$userid || $dta['level'] == 1 || $dta['level'] == 2 )
	{  
		$dconn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$query ="Select * FROM clients where cid = $cid";
		$ins= $dconn->prepare($query);
		$ins->execute();
		$ucdata = $ins->fetch();
?>

		<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-body">

					<form action="listupdate.php" method="post">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Company Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="companyname" class="form-control-in" value="<?php echo $ucdata['companyname']; ?>" placeholder="Company Name"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
								<td width="15%" align="left" valign="top">	<label>Full Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="rname" class="form-control-in" value="<?php echo $ucdata['rname']; ?>" placeholder="Name"></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
	<div class="form-group">
								<td width="15%" align="left" valign="top">	<label>First Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top"><input name="rfname" class="form-control-in" value="<?php echo $ucdata['rfname']; ?>" placeholder="First Name"></td>
</div> </tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Email ID:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="remail" class="form-control-in" value="<?php echo $ucdata['remail']; ?>" placeholder="Email Address"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Phone Number:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input type="phone" name="rphone" class="form-control-in" value="<?php echo $ucdata['rphone']; ?>" placeholder="Phone Number"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">	
									<td width="15%" align="left" valign="top"><label>Location:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="rlocation" class="form-control-in" value="<?php echo $ucdata['companyname']; ?>" placeholder="Location"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Tier:&nbsp;&nbsp;&nbsp;</label></td>
									<td width="90%" align="left" valign="top">	<select name="tier" class="form-control-in">
										<option value="Tier 1" <?php if(trim($ucdata['tier'])==="Tier 1") { echo "selected"; } ?>>Tier 1</option>
           								<option value="Tier 2" <?php if(trim($ucdata['tier'])==="Tier 2") { echo "selected"; } ?>>Tier 2</option>
           								<option value="Implementation Partner">Implementation Partner</option>
									</select></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Timezone</label></td>
								<td width="90%" align="left" valign="top">	<select name="rtimezon" class="form-control-in">
										<option value="EST" <?php if(trim($ucdata['rtimezon'])==="EST") { echo "selected"; } ?>>EST</option>
           								<option value="CST" <?php if(trim($ucdata['rtimezon'])==="CST") { echo "selected"; } ?>>CST</option>
            							<option value="MST" <?php if(trim($ucdata['rtimezon'])==="MST") { echo "selected"; } ?>>MST</option>
            							<option value="PST" <?php if(trim($ucdata['rtimezon'])==="PST") { echo "selected"; } ?>>PST</option>
									</select></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<input type="hidden" name="cid" value="<?php echo $cid; ?>">
<input type="hidden" name="lid" value="<?php echo $listid; ?>">
   							<td  align="left" ><button type="submit" name="save" class="btn btn-primary">Save</button> </td>					
                 </tr>
                 </table>

</form>
						
				</div></div>
			</div><!-- /.col-->
		</div><!-- /.row -->
		

			
<?php	

}
	else { 
echo "<script>
				alert(' List is uploaded by another user.');
				window.location.href='admin.php?action=listall';
				</script>"; 


	}

}

	elseif($do=='addcontact')
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

					<form action="listadd.php" method="post">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<div class="form-group">
									<td width="15%" align="left" valign="top"><label>Company Name:&nbsp;&nbsp;&nbsp;</label></td>
								<td width="90%" align="left" valign="top">	<input name="companyname" class="form-control-in" placeholder="Company Name"></td>
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<div class="form-group">
								<td width="15%" align="left" valign="top">	<label>Full Name:&nbsp;&nbsp;&nbsp;</label></td>
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
           								<option value="Tier 2">Tier 2</option>
           								<option value="Implementation Partner">Implementation Partner</option>
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
</div></tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>
<input type="hidden" name="lid" value="<?php echo $listid; ?>">
   							<td  align="left" ><button type="submit" name="save" class="btn btn-primary">Save</button> </td>					
                 </tr>
                 </table>

</form>
						
				</div></div>
			</div><!-- /.col-->
		</div><!-- /.row -->
		

			
<?php	

}
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

					<form action="listupload.php" method="post" target="_blank" enctype="multipart/form-data" name="form" id="form">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
		<div class="form-group">
		<td width="10%" align="left" valign="top"><label>Upload List</label></td>
		<td width="25%" align="left" valign="top"><input name="file" type="file" id="csv">
		<p class="help-block">Upload only in .csv file only.</p></td>
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

elseif($do=='download')
{

if($dta['level'] == 1 || $dta['level'] == 2 )
{
	$dsql="SELECT `companyname`, `rname`, `rfname`, `remail`, `rphone`, `rlocation`, `rtimezon`, `tier` FROM `clients` WHERE `lid` = '$listid' AND `status` = 1";
}
else
{
	$dsql="SELECT `companyname`, `rname`, `rfname`, `remail`, `rphone`, `rlocation`, `rtimezon`, `tier` FROM `clients` WHERE `lid` = '$listid' AND `uid` = '$userid' AND `status` = 1";
}
	$connd = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
	$dins= $connd->prepare($dsql);
	$dins->execute();

	$lnquery ="SELECT * FROM `lists` WHERE `listid` = '$listid' AND `status` = 1";
	$lins=$connd->prepare($lnquery);
	$lins->execute();
	$ldata=$lins->fetch();
	$listname = $ldata['listname'];

	$date = date("Y-m-d H:i:s");
	$filename = "tmp/".$listname."_".$sessid."-".date("m-d-Y", strtotime($date) ).".csv";
	$fp = fopen("$filename", 'w');
	$txt = "Company Name,Full Name,First Name,Email ID,Phone Number,Location,Timezone,Tier 1/2/IP\n";
	fwrite($fp, $txt);
	while ($row = $dins->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($fp, $row);
}// whilw
fclose($fp);
			header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($filename));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filename));
            ob_clean();
            flush();
readfile($filename);
header( "Location: admin.php?action=listall" ); 


}//do download

elseif($do=='delete')
{ 
	$listid=$_GET['lid'];
	$cid=$_GET['id'];
	$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
	$inquery = "UPDATE `clients` SET `status` = '0' WHERE `clients`.`cid` = $cid;";
	$ins= $conn->prepare($inquery);
	$ins->execute();
	$conn=null;
	header( "Location: listcmd.php?do=viewlist&lid=$listid" );
} //do delete


elseif($do=='viewlist')
{

if($dta['level'] == 1 || $dta['level'] == 2 )
{
	$queryv = "select * from clients where `lid` = $listid AND `status` = 1";
}
elseif ($dta['level'] == 3)
{
$queryv = "select * from clients where `lid` = '$listid' AND `uid` = $userid AND `status` = 1"; }
$connv = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$insv= $connv->prepare($queryv);
$insv->execute();
$datav = $insv->fetchAll();

?>
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uid" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="ID" data-sortable="true">ID</th>
						        <th data-field="Company Name"  data-sortable="true">Company Name</th>
						        <th data-field="Full Name Name" data-sortable="true">Full Name</th>
						        <th data-field="Email ID" data-sortable="true">Email ID</th>						        
						        <th data-field="Phone Number" data-sortable="true">Phone Number</th>
						        <th data-field="Location" data-sortable="true">Location</th>
						        <th data-field="Timezone" data-sortable="true">Timezone</th>
						        <th data-field="Tier" data-sortable="true">Tier</th>
						      <?php if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3) { ?>  
						      	<th data-field="cmd" >Actions</th> 
						      <?php } ?>
						    </tr>
						    </thead>
						   <tbody>
<?php
$i=1;
foreach( $datav as $row) {
?>
    <tr>
  		<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
    	<td data-search="<?php echo $row['companyname']; ?>"> <?php echo $row['companyname']; ?></td>
    	<td data-search="<?php echo $row['rname']; ?>"> <?php echo $row['rname']; ?></td>    	
    	<td data-search="<?php echo $row['remail']; ?>"> <?php echo $row['remail']; ?></td>
    	<td data-search="<?php echo $row['rphone']; ?>"> <?php echo $row['rphone']; ?></td>
    	<td data-search="<?php echo $row['rlocation']; ?>"> <?php echo $row['rlocation']; ?></td>    	
    	<td data-search="<?php echo $row['rtimezon']; ?>"> <?php echo $row['rtimezon']; ?></td>   	
    	<td data-search="<?php echo $row['tier']; ?>"> <?php echo $row['tier']; ?></td>
    	 <?php  /**
		<a href="listcmd.php?do=addcontact&lid=<?php echo $row['listid']; ?>">Add Contact<!--<img src="images/b_edit.png" alt="Edit" width="16" height="16" border="0" title="Edit" /> --></a>
    				 &nbsp;&nbsp;&nbsp;
    				<a href ="listcmd.php?do=download&lid=<?php echo $row['listid']; ?>" onClick="return confirm('Are you sure you want to Download ?')">Download List</a>
    				 &nbsp;&nbsp;&nbsp; */ ?>
    		
    		<a href="listcmd.php?do=editcontact&lid=<?php echo $listid; ?>&id=<?php echo $row['cid']; ?>"><img src="images/b_edit.png" alt="Change" width="16" height="16" border="0" title="Change" /></a>
    				 &nbsp;&nbsp;&nbsp;
					 <?php if($dta['level'] == 1 || $dta['level'] == 2 )
{ 
	?> <td> 
    				<a href ="listcmd.php?do=delete&lid=<?php echo $listid; ?>&id=<?php  echo $row['cid']; ?>" onClick="return confirm('Are you sure you want to delete ?')"><img src="images/b_drop.png" alt="Delete" width="16" height="16" border="0" title="Delete"/></a>
    				 &nbsp;&nbsp;&nbsp;    			
    	</td> <?php } ?>
    </tr>	
    <?php
}
?>
						   </tbody>
						</table>

<?php

}
	else
	{
		echo "<script>
alert('Not a valid command.');
window.location.href='admin.php?action=listall';
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