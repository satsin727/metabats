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

$download = $_GET['download'];

if($download == "app")
{
$query = "SELECT * FROM `app_data` WHERE `status` = 1  and MONTH(appdate) = MONTH(CURDATE()) order by appdate asc"; }
elseif($download == "rc")
{$query = "SELECT * FROM `app_data` WHERE `rcdone` = 1 and `status`= 1 and MONTH(rcdate) = MONTH(CURDATE()) order by rcdate asc";}
elseif($download == "sub")
{$query = "SELECT * FROM `app_data` WHERE `subdone` = 1 and  `rcdone` = 1 and `status`= 1 and MONTH(subdate) = MONTH(CURDATE()) order by subdate asc";}
elseif($download == "eci")
{$query = "SELECT * FROM `eci` WHERE `eci_happened` =1 and `status` = 1 and MONTH(eci_date) = MONTH(CURDATE())  order by eci_req_date asc";}

$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();

				if($download == "app")
				{
					$date = date("Y-m-d H:i:s");
                    $filename = "tmp/"."app_list_".$sessid."-".date("m-d-Y", strtotime($date) ).".csv";
                    $fp = fopen("$filename", 'w');
                    $txt = "S.no,Date,SM,Consultant Name,Skill,Location,BP Email,IP/EC,Status,Comment,Feedback\n";
                    fwrite($fp, $txt);
                    $i = 0;
                    foreach($data as $row) {
                        $i = $i+1;

                                $time = strtotime($row['appdate']); 
                        $date = date("m/d/y", $time);

                                $uid = $row['uid'];
                                $q4 = "SELECT * from users where `uid` = $uid";
								$ins5= $conn->prepare($q4);
								$ins5->execute(); 
								$dta4 = $ins5->fetch();
                        $sm = $dta4['name'];

                                $consultantid = $row['consultant_id'];
                                $cfname = $conn->query("select cfname from consultants where cid = $consultantid")->fetchColumn();    
                                $cmname = $conn->query("select cmname from consultants where cid = $consultantid")->fetchColumn();
                                $clname = $conn->query("select clname from consultants where cid = $consultantid")->fetchColumn();
                                
                        $consultantname = $cfname." ".$cmname." ".$clname;

                                $skillid = $conn->query("select skill from consultants where cid = $consultantid")->fetchColumn();
                        $skill = $conn->query("SELECT skillname FROM `skill` WHERE `sid`= $skillid")->fetchColumn();
                        
                                $reqid = $row['reqid'];
                        $location = $conn->query("select rlocation from req where reqid = $reqid")->fetchColumn();
                       
                                $cid = $row['client_id'];
                        $bpemail = $conn->query("SELECT remail from clients where `cid` = $cid")->fetchColumn();

                                $app_id = $row['app_id'];
                        $ipname = $conn->query("select t1ip_name from app_data where app_id = $app_id")->fetchColumn();
                        $clientname = $conn->query("select rend_client from req where reqid = $reqid")->fetchColumn();

                       
                        if(isset($ipname) && isset($clientname))
                        {
                        $client = $ipname."/".$clientname;
                        }
                        elseif(isset($ipname))
                        {
                        $client = $ipname;
                        }
                        elseif(isset($clientname))
                        {
                        $client = $clientname;
                        }
                        else
                        {
                        $client = "NA";
                        }

                        
                                $ars_status = $conn->query("SELECT `ars_status` FROM `app_data` WHERE `app_id` = $app_id")->fetchColumn();
                                if($ars_status == 1)
                                {
                                    $status =  "Connected";
                                }
                                elseif($ars_status == 2)
                                {
                                    $status =  "Not Connected";
                                }
                                elseif($ars_status == 3)
                                {
                                    $status =  "Voicemail;";
                                }
                                elseif($ars_status == 4)
                                {
                                    $status =  "No Response";
                                }
                                elseif($ars_status == 5)
                                {
                                    $status =  "Cancelled";
                                }
                                elseif($ars_status == 6)
                                {
                                    $status =  "Rejected";
                                }
                                elseif($ars_status == 7)
                                {
                                    $status =  "In-Process";
                                }
                                elseif($ars_status == 8)
                                {
                                    $status =  "Got Test";
                                }
                                elseif($ars_status == 9)
                                {
                                    $status =  "Got Screening";
                                }
                                elseif($ars_status == 10)
                                {
                                    $status =  "Submitted to End Client";
                                }
                       
                        $comment = $conn->query("SELECT `comment` FROM `comments` WHERE `com_postid` = $app_id")->fetchColumn();
                        if(isset($row['feedback']))
                        {
                            $feedback = $row['feedback'];
                        }
                        else
                        {
                            $feedback = "NA";
                        }
                    
                        $lineData = array($i,$date,$sm,$consultantname,$skill,$location,$bpemail,$client,$status,$comment,$feedback);
                        fputcsv($fp, $lineData,",");
                    }// for
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
                    exit();
				}

                elseif($download == "rc")
				{
					$date = date("Y-m-d H:i:s");
                    $filename = "tmp/"."rc_list_".$sessid."-".date("m-d-Y", strtotime($date) ).".csv";
                    $fp = fopen("$filename", 'w');
                    $txt = "S.no,Date,SM,Consultant Name,Skill,Location,BP Email,IP/EC,Status,Comment,Feedback\n";
                    fwrite($fp, $txt);
                    $i = 0;
                    foreach($data as $row) {
                        $i = $i+1;

                                $time = strtotime($row['appdate']); 
                        $date = date("m/d/y", $time);

                                $uid = $row['uid'];
                                $q4 = "SELECT * from users where `uid` = $uid";
								$ins5= $conn->prepare($q4);
								$ins5->execute(); 
								$dta4 = $ins5->fetch();
                        $sm = $dta4['name'];

                                $consultantid = $row['consultant_id'];
                                $cfname = $conn->query("select cfname from consultants where cid = $consultantid")->fetchColumn();    
                                $cmname = $conn->query("select cmname from consultants where cid = $consultantid")->fetchColumn();
                                $clname = $conn->query("select clname from consultants where cid = $consultantid")->fetchColumn();
                                
                        $consultantname = $cfname." ".$cmname." ".$clname;

                                $skillid = $conn->query("select skill from consultants where cid = $consultantid")->fetchColumn();
                        $skill = $conn->query("SELECT skillname FROM `skill` WHERE `sid`= $skillid")->fetchColumn();
                        
                                $reqid = $row['reqid'];
                        $location = $conn->query("select rlocation from req where reqid = $reqid")->fetchColumn();
                       
                                $cid = $row['client_id'];
                        $bpemail = $conn->query("SELECT remail from clients where `cid` = $cid")->fetchColumn();

                                $app_id = $row['app_id'];
                        $ipname = $conn->query("select t1ip_name from app_data where app_id = $app_id")->fetchColumn();
                        $clientname = $conn->query("select rend_client from req where reqid = $reqid")->fetchColumn();

                        if(isset($ipname) && isset($clientname))
                        {
                        $client = $ipname."/".$clientname;
                        }
                        elseif(isset($ipname))
                        {
                        $client = $ipname;
                        }
                        elseif(isset($clientname))
                        {
                        $client = $clientname;
                        }
                        else
                        {
                        $client = "NA";
                        }

                        
                                $ars_status = $conn->query("SELECT `ars_status` FROM `app_data` WHERE `app_id` = $app_id")->fetchColumn();
                                if($ars_status == 1)
                                {
                                    $status =  "Connected";
                                }
                                elseif($ars_status == 2)
                                {
                                    $status =  "Not Connected";
                                }
                                elseif($ars_status == 3)
                                {
                                    $status =  "Voicemail;";
                                }
                                elseif($ars_status == 4)
                                {
                                    $status =  "No Response";
                                }
                                elseif($ars_status == 5)
                                {
                                    $status =  "Cancelled";
                                }
                                elseif($ars_status == 6)
                                {
                                    $status =  "Rejected";
                                }
                                elseif($ars_status == 7)
                                {
                                    $status =  "In-Process";
                                }
                                elseif($ars_status == 8)
                                {
                                    $status =  "Got Test";
                                }
                                elseif($ars_status == 9)
                                {
                                    $status =  "Got Screening";
                                }
                                elseif($ars_status == 10)
                                {
                                    $status =  "Submitted to End Client";
                                }
                       
                        $comment = $conn->query("SELECT `comment` FROM `comments` WHERE `com_postid` = $app_id")->fetchColumn();
                        if(isset($row['feedback']))
                        {
                            $feedback = $row['feedback'];
                        }
                        else
                        {
                            $feedback = "NA";
                        }

                        $layer = $conn->query("SELECT `tier` FROM `clients` WHERE `cid` = $cid")->fetchColumn();
                        $rate = $row['rateperhour'];
                    
                        $lineData = array($i,$date,$sm,$consultantname,$skill,$location,$bpemail,$layer,$rate,$client,$status,$comment,$feedback);
                        fputcsv($fp, $lineData,",");
                    }// for
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
                    exit();
				}

                
                elseif($download == "sub")
				{
					$date = date("Y-m-d H:i:s");
                    $filename = "tmp/"."sub_list_".$sessid."-".date("m-d-Y", strtotime($date) ).".csv";
                    $fp = fopen("$filename", 'w');
                    $txt = "S.no,Date,SM,Consultant Name,Skill,Location,BP Email,IP/EC,Status,Comment,Feedback\n";
                    fwrite($fp, $txt);
                    $i = 0;
                    foreach($data as $row) {
                        $i = $i+1;

                                $time = strtotime($row['appdate']); 
                        $date = date("m/d/y", $time);

                                $uid = $row['uid'];
                                $q4 = "SELECT * from users where `uid` = $uid";
								$ins5= $conn->prepare($q4);
								$ins5->execute(); 
								$dta4 = $ins5->fetch();
                        $sm = $dta4['name'];

                                $consultantid = $row['consultant_id'];
                                $cfname = $conn->query("select cfname from consultants where cid = $consultantid")->fetchColumn();    
                                $cmname = $conn->query("select cmname from consultants where cid = $consultantid")->fetchColumn();
                                $clname = $conn->query("select clname from consultants where cid = $consultantid")->fetchColumn();
                                
                        $consultantname = $cfname." ".$cmname." ".$clname;

                                $skillid = $conn->query("select skill from consultants where cid = $consultantid")->fetchColumn();
                        $skill = $conn->query("SELECT skillname FROM `skill` WHERE `sid`= $skillid")->fetchColumn();
                        
                                $reqid = $row['reqid'];
                        $location = $conn->query("select rlocation from req where reqid = $reqid")->fetchColumn();
                       
                                $cid = $row['client_id'];
                        $bpemail = $conn->query("SELECT remail from clients where `cid` = $cid")->fetchColumn();

                                $app_id = $row['app_id'];
                        $ipname = $conn->query("select t1ip_name from app_data where app_id = $app_id")->fetchColumn();
                        $clientname = $conn->query("select rend_client from req where reqid = $reqid")->fetchColumn();
                        $client = "NA";
                        if($ipname != "" && $clientname !="")
                        {
                        $client = $ipname."/".$clientname;
                        }
                        elseif(isset($clientname))
                        {
                        $client = $clientname;
                        }
                        elseif(isset($ipname))
                        {
                        $client = $ipname;
                        }
                        
                                $ars_status = $conn->query("SELECT `ars_status` FROM `app_data` WHERE `app_id` = $app_id")->fetchColumn();
                                if($ars_status == 1)
                                {
                                    $status =  "Connected";
                                }
                                elseif($ars_status == 2)
                                {
                                    $status =  "Not Connected";
                                }
                                elseif($ars_status == 3)
                                {
                                    $status =  "Voicemail;";
                                }
                                elseif($ars_status == 4)
                                {
                                    $status =  "No Response";
                                }
                                elseif($ars_status == 5)
                                {
                                    $status =  "Cancelled";
                                }
                                elseif($ars_status == 6)
                                {
                                    $status =  "Rejected";
                                }
                                elseif($ars_status == 7)
                                {
                                    $status =  "In-Process";
                                }
                                elseif($ars_status == 8)
                                {
                                    $status =  "Got Test";
                                }
                                elseif($ars_status == 9)
                                {
                                    $status =  "Got Screening";
                                }
                                elseif($ars_status == 10)
                                {
                                    $status =  "Submitted to End Client";
                                }
                       
                        $comment = $conn->query("SELECT `comment` FROM `comments` WHERE `com_postid` = $app_id")->fetchColumn();
                        if(isset($row['feedback']))
                        {
                            $feedback = $row['feedback'];
                        }
                        else
                        {
                            $feedback = "NA";
                        }

                        $layer = $conn->query("SELECT `tier` FROM `clients` WHERE `cid` = $cid")->fetchColumn();
                        $rate = $row['rateperhour'];
                    
                        $lineData = array($i,$date,$sm,$consultantname,$skill,$location,$bpemail,$layer,$rate,$client,$status,$comment,$feedback);
                        fputcsv($fp, $lineData,",");
                    }// for
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
                    exit();
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