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
$curdate =date('Y-m-d');

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

$download = $_GET['download'];

if($download == "allreqs")
{
$query = "SELECT * FROM `req` WHERE `status` = 1  and DATE(datetime) =  DATE('$curdate') GROUP BY (ureq_id)"; 


$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();

					$date = date("Y-m-d H:i:s");
                    $filename = "tmp/"."allreqs_".$sessid."-".date("m-d-Y", strtotime($date) ).".csv";
                    $fp = fopen("$filename", 'w');
                    $txt = "S.no,Date,Req_ID,Skill,Location,Job Description,SM,BP Email,End Client, Utilization Status,Total RC,Comment\n";
                    fwrite($fp, $txt);
                    $i = 0;
                    foreach($data as $row) {
                        $i = $i+1;
                                
                                $time = strtotime($row['datetime']); 
                                $curdate = date("dmy", $time); 
                                $curweek = date("W", $time); 
                                $req_id = "W".$curweek.$curdate."-".$row['ureq_id'];
                        $date = date("m/d/y", $time);

                        $ureq_id = $row['ureq_id'];
                            $skillid = $row['skillid'];
                        $skill = $conn->query("SELECT skillname FROM `skill` WHERE `sid`= $skillid")->fetchColumn();
                            $jobtype = $row['jobtype'];
                        if($jobtype == 1) { $job = "Contract";} else { $job = "Contract to hire"; }
                            $reqid = $row['reqid'];
                        $location = $conn->query("select rlocation from req where reqid = $reqid")->fetchColumn();

                            $jd = $conn->query("select rdesc from jd where reqid = $reqid")->fetchColumn();
                        $jdtext = strip_tags($jd);

                        $clientname = $conn->query("select rend_client from req where reqid = $reqid")->fetchColumn();


                        //posted by SM
                        $sm_query = "select * from app_data as A Left Join req as B ON A.reqid  = B.reqid where ureq_id = $ureq_id and datetime > $curdate";
                        $sins= $conn->prepare($sm_query);
                        $sins->execute();
                        $smdata = $sins->fetchAll();
                        $appdata = "";
                        $comments = "";
                            foreach ($smdata as $sm)
                            {
                               /* $uid = $sm['B.uid'];
                            if(isset($smname)) { $sep = "\n"; }
                                $smname = $smname.$sep.$conn->query("SELECT * from users where `uid` = $uid")->fetchColumn();

                               */
                                $uid = $sm['A.uid'];
                                $smn = $conn->query("SELECT name from users where `uid` = $uid")->fetchColumn();

                                    $consultantid = $sm['A.consultant_id'];

                                    $consultantname = $conn->query("select cfname from consultants where cid = $consultantid")->fetchColumn();

                                $cid = $sm['A.client_id'];
                                $bpemail = $conn->query("SELECT remail from clients where `cid` = $cid")->fetchColumn();

                                $rcdone = $sm['A.rcdone'];
                                $subdone = $sm['A.subdone'];

                                $appdata = $appdata.$smn." has applied ".$consultantname." through ".$bpemail." and did ".$rcdone." RC, ".$subdone." Sub."."\n"; 
                            
                                $appid = $sm['A.app_id'];
                                    $com_query = "select * from comments where com_postid = $appid";
                                    $cins= $conn->prepare($com_query);
                                    $cins->execute();
                                    $commentdata = $cins->fetchAll();

                                foreach($commentdata as $comment)
                                {
                                    $uid = $comment['uid'];
                                    $smname = $conn->query("SELECT name from users where `uid` = $uid")->fetchColumn();
                                    $comments = $comments.$smname.": ".$comment['comment']." at ".$comment['datetime'];
                                }
                                

                            }
                        
                            $totalrc = $conn->query("select count(*) from app_data as A Left Join req as B ON A.reqid  = B.reqid where ureq_id = $ureq_id and rcdone = 1 datetime > $curdate") ;
                            if($totalrc>0)
                            {
                                $reqstatus = "Utilized";
                            }
                            else {
                                $reqstatus = "Unutilized";
                            }
 
                        $txt = "S.no,Date,Req_ID,Skill,Location,Job Description,App Data,End Client, Utilization Status,Total RC,Comment\n";
                        $lineData = array($i,$date,$req_id,$skill,$location,$jdtext,$appdata,$clientname,$reqstatus,$totalrc,$comments);
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

}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>