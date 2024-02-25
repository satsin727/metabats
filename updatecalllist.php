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
        //$sno = $data[0];
        $companyname = $data[1];
        $rname = $data[2];
        //$rfname = $data[2];
        $col4 = trim(trim($data[3],"\'\"[]~`;:\t%")," ");    
        if (!filter_var($col4, FILTER_VALIDATE_EMAIL)) 
        {
          $col4 = "Invalid Email Address";
          $status = 0;
        }
        $date = $data[4];
            $ddate = strtotime($date);
            $dialed_date =date('Y-m-d',$ddate);
        $condata = str_replace(' ', '', strtolower($data[5]));
        if($condata =="connected")
            {
                $connected = 1;
            } 
            else {
                $connected = 0;
            }
        $comment = $data[6];
        $special_notes = $data[7];

        if($status!=0)
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

    }
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>