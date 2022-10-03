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
$conn=null; 

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

$cid = $_GET['cid'];
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from consultants where `cid` = :cid";
$ins= $conn->prepare($query);
$ins->bindValue( ":cid", $cid, PDO::PARAM_INT );
$ins->execute();
$conn=null;
$dta = $ins->fetch();
$f=$dta['cfname'];
$l=$dta['clname'];

				if($dta['resume']==$_GET['resume'])
				{
					$resume = $dta['resume'];
					$ext = strtolower(substr(strrchr($resume, '.'), 1));
					$file=$f." ".$l."_Resume".".".$ext;
					$tfile="consultants_data/resume/".$resume;
					header('Pragma: public');
					header('Expires: 0');
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Cache-Control: private', false); // required for certain browsers 
					header('Content-Disposition: attachment; filename="'. basename($file) . '";');
					header('Content-Transfer-Encoding: binary');
					header('Content-Length: ' . filesize($tfile));
					ob_clean();
    				flush();
					readfile($tfile);
					exit;
				}


				if($dta['dlcopy']==$_GET['dlcopy'])
				{
					$dlcopy = $dta['dlcopy'];
					$ext = strtolower(substr(strrchr($dlcopy, '.'), 1));
					$file=$f." ".$l."_DL".".".$ext;
					$tfile="consultants_data/dl/".$dlcopy;
					header('Pragma: public');
					header('Expires: 0');
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Cache-Control: private', false); // required for certain browsers 
					header('Content-Disposition: attachment; filename="'. basename($file) . '";');
					header('Content-Transfer-Encoding: binary');
					header('Content-Length: ' . filesize($tfile));
					ob_clean();
    				flush();
					readfile($tfile);
					exit;
				}


				if($dta['visacopy']==$_GET['visacopy'])
				{
					$visacopy = $dta['visacopy'];
					$ext = strtolower(substr(strrchr($visacopy, '.'), 1));
					$file=$f." ".$l."_Visa".".".$ext;
					$tfile="consultants_data/visa/".$visacopy;
					header('Pragma: public');
					header('Expires: 0');
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Cache-Control: private', false); // required for certain browsers 
					header('Content-Disposition: attachment; filename="'. basename($file) . '";');
					header('Content-Transfer-Encoding: binary');
					header('Content-Length: ' . filesize($tfile));
					ob_clean();
    				flush();
					readfile($tfile);
					exit;



				}
				if($dta['stateid']==$_GET['stateid'])
				{
					$stateid = $dta['stateid'];
					$ext = strtolower(substr(strrchr($stateid, '.'), 1));
					$file=$f." ".$l."_StateID".".".$ext;
					$tfile="consultants_data/stateid/".$stateid;
					header('Pragma: public');
					header('Expires: 0');
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Cache-Control: private', false); // required for certain browsers 
					header('Content-Disposition: attachment; filename="'. basename($file) . '";');
					header('Content-Transfer-Encoding: binary');
					header('Content-Length: ' . filesize($tfile));
					ob_clean();
    				flush();
					readfile($tfile);
					exit;



				}
				if($dta['passportcopy']==$_GET['passportcopy'])
				{
					$passportcopy = $dta['passportcopy'];
					$ext = strtolower(substr(strrchr($passportcopy, '.'), 1));
					$file=$f." ".$l."_Passport Copy".".".$ext;
					$tfile="consultants_data/passportcopy/".$passportcopy;
					header('Pragma: public');
					header('Expires: 0');
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Cache-Control: private', false); // required for certain browsers 
					header('Content-Disposition: attachment; filename="'. basename($file) . '";');
					header('Content-Transfer-Encoding: binary');
					header('Content-Length: ' . filesize($tfile));
					ob_clean();
    				flush();
					readfile($tfile);
					exit;


				}



?>
</div>

<?php

}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>