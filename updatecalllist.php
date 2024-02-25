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

$listid = $dta['def_lid'];
$userid = $dta['uid'];

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

    require("includes/header.php");
    $selected = "calling";
    require("includes/menu.php");
    ?>

<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
		<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Dashboard</li>
			</ol>
		</div><!--/.row-->
		
<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
							<div class="panel-body">	

<?php

    if(isset($_POST['submit']))
    { 

    $lname=$_FILES['file']['name'];
    $lext = strtolower(substr(strrchr($lname, '.'), 1));
    if($lext == "csv" || $lext == "txt")
    {
        $digits = 6;
        $unumber = rand(pow(10, $digits-1), pow(10, $digits)-1);
        $date = date("Y-m-d H:i:s");
        $lfname = basename($_FILES['file']['name'],$lext);
        $file= $listid."-".date("m-d-Y", strtotime($date) )."-".$unumber."-".$lfname.$lext;
        $target = "files/lists/".$file;										
        move_uploaded_file($_FILES["file"]["tmp_name"], $target);
    }
    ini_set('auto_detect_line_endings',TRUE);
    $handle = fopen($target,'r');
    while ( ($data = fgetcsv($handle) ) !== FALSE ) {
        $status = 1;
      //  $sn0 = $data[0];
        $smname = $data[1];
        $companyname = $data[2];
        $rname = $data[3];
        //$rfname = $data[2];
        $col4 = trim(trim($data[4],"\'\"[]~`;:\t%")," ");    
            if (!filter_var($col4, FILTER_VALIDATE_EMAIL)) 
            {
            $col4 = "Invalid Email Address";
            $status = 0;
            }
        
        $rphone = $data[5];
        $rphone = str_replace(' ', '', strtolower($rphone));        
        $rphone = str_replace('(', '', strtolower($rphone));               
        $rphone = str_replace(')', '', strtolower($rphone));               
        $rphone = str_replace('-', '', strtolower($rphone));               
        $rphone = str_replace('ext', 'x', strtolower($rphone));
        $date = $data[6];
            $ddate = strtotime($date);
            $dialed_date =date('Y-m-d',$ddate);
        $condata = str_replace(' ', '', strtolower($data[7]));
        if($condata =="connected")
            {
                $connected = 1;
            } 
            else {
                $connected = 0;
            }
        $comment = $data[8];
        $special_notes = $data[9];

        if($dta['level'] == 1 || $dta['level'] == 2)
        {
            $smname = str_replace(' ', '', $data[1]);
            $userid = $conn->query("SELECT uid FROM `users` WHERE `name` = '$smname'")->fetchColumn();
            $listid = $conn->query("SELECT def_lid FROM `users` WHERE `uid` = '$userid'")->fetchColumn();

        }


        if($status!=0 && $userid != null)
        {
            
            $rowcounts =  $conn->query("SELECT COUNT(*) FROM `clients` WHERE `remail` = '$col4'")->fetchColumn();
            if($rowcounts == 0)
            { 
                $sql = "INSERT into clients (`cid`, `lid`, `uid`, `companyname`, `rname`, `remail`, `rphone`,`status`) 
                values (Null, '$listid', '$userid', '$companyname','$rname','$col4','$rphone','$status')";
                $insl= $conn->prepare($sql);
                $insl->execute();
            }
            else{
                $sql = "UPDATE clients SET `companyname` = '$companyname', `rname` = '$rname', `rphone`= '$rphone' where remail = '$col4'";
                $insl= $conn->prepare($sql);
                $insl->execute();
            }
            $client_id = $conn->query("SELECT cid FROM `clients` WHERE `remail` = '$col4'")->fetchColumn();
            
            $sql = "INSERT into client_call (`cid`, `dialed_date`, `connected`, `smid`, `comment`, `special_notes`) values ($client_id, '$dialed_date','$connected','$userid','$comment','$special_notes')";
            $insl= $conn->prepare($sql);    
            $insl->execute();
        }
        
     } 
     $conn = null;	

     echo "<script>
    alert('Calling List Successfully Uploaded.');
    window.location.href='admin.php?action=listall';
    </script>";

    }
    else{


        ?>

					<form action="" method="post" target="_blank" enctype="multipart/form-data" name="form" id="form">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
		<div class="form-group">
		<td width="10%" align="left" valign="top"><label>Upload Calling list</label></td>
		<td width="25%" align="left" valign="top"><input name="file" type="file" id="csv">
		<p class="help-block">Upload only in .csv file only.</p></td>
</div> 
</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

   							<td  align="left" ><button type="submit" name="submit" class="btn btn-primary">Import</button> </td>					
                 </tr>
                 </table>

</form>

<?php

    }

?>

						
</div></div>
			</div><!-- /.col-->
		</div><!-- /.row -->
</div>

        <?php

}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>