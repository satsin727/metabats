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

    if(isset($_GET['do']))
    {
        
        $do=$_GET['do'];
        $reqid = $_GET['rid'];



		if($do=='editreqid')
	    {

			$data = $conn->query("select * from req where `reqid` = $reqid")->fetch();
			$client_id = $data['cid'];	
			$date = $data['datetime'];
            $time = strtotime($date);
            $curdate = date("dmy", $time); 
            $curweek = date("W", $time); 

            $req_id = "W".$curweek.$curdate."-".$data['ureq_id'];

			$conn=null;
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			$query = "select * from req where `reqid` = :reqid";
			$ins= $conn->prepare($query);
			$ins->bindValue( ":reqid", $reqid, PDO::PARAM_INT );
			$ins->execute();
			$data = $ins->fetch();
			$queryl = "select * from jd where `reqid` = :reqid";
			$ins1= $conn->prepare($queryl);
			$ins1->bindValue( ":reqid", $reqid, PDO::PARAM_INT );
			$ins1->execute();
			$data1 = $ins1->fetch();
			$query3 = "select * from skill where `sid` = :skillid";
			$ins3= $conn->prepare($query3);
			$ins3->bindValue( ":skillid", $data['skillid'], PDO::PARAM_INT );
			$ins3->execute();
			$data3 = $ins3->fetch();


		
			?>
		<form action="#" method="post">
			<tr><br><td><label>Req ID:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label> </td>
				<td><input name="ureq_id" class="form-control-in" placeholder="<?php echo $req_id; ?>" value="<?php echo $data['ureq_id']; ?>"></td> <br></tr><br>
			</tr>
				<br> <input type="hidden" name="rid" value="<?php echo $reqid; ?>">

			<tr>
			<br><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td class="button">
		<button type="submit" name="update">Update</button> </td>
			</tr>
		</form>

		<tr>
		<?php

			echo "<h2>".$data3['skillname']." - ".$data['rlocation']." - ".$data['rduration']."</h2><br>";
			echo $data1['rdesc'];

		}
    }
    if(isset($_POST['update']))
    {
        $reqid = $_POST['rid']; 
        $ureq_id = $_POST['ureq_id'];
       
        $conn=null;
        $conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
        $query = "update req set ureq_id = :u where reqid = $reqid";
        $ins= $conn->prepare($query);
        $ins->bindValue( ":u", $ureq_id, PDO::PARAM_STR );
        $ins->execute();

    }

}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>