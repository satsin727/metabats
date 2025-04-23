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
$conn=null; 
$curdate =date('Y-m-d');

if(isset($_GET['date']))
{
    $curdate = $_GET['date'];
}

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

$download = $_GET['download'];
if($_GET['showunique']==1)
{
    $unique = 1; 
}

if($download == "allreqs")
{
    if($_GET['showweekly']==1)
    {
        $weekly=1;
        $query = "SELECT * FROM `req` WHERE `status` = 1  and WEEK(datetime) =  WEEK('$curdate') and YEAR(datetime) =  YEAR('$curdate')"; 
    }
    else if($_GET['showmonthly']==1)
    {
        $monthly=1;
        $query = "SELECT * FROM `req` WHERE `status` = 1  and MONTH(datetime) =  MONTH('$curdate') and YEAR(datetime) =  YEAR('$curdate')"; 
    }
    else 
    {
        $query = "SELECT * FROM `req` WHERE `status` = 1  and DATE(datetime) =  DATE('$curdate') and MONTH(datetime) =  MONTH('$curdate') and YEAR(datetime) =  YEAR('$curdate') GROUP BY (ureq_id)"; 
    }

$reqSources = [
    1  => "Inbox",
    2  => "Posting",
    3  => "Cold Calls",
    4  => "AMC",
    5  => "Prohires",
    6  => "Google Groups",
    7  => "LinkedIn",
    8  => "Job Portal - Dice",
    9  => "Job Portal - Techfetch",
    10 => "Job Portal - SimplyHired",
    11 => "Job Portal - Careerbuilder",
    12 => "Job Portal - Ziprecruiter",
    13 => "Job Portal - Monster",
    14 => "Job Portal - other",
    15 => "Company Websites",
    16 => "I-Labor",
    17 => "Other"
];



$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();

					$date = date("Y-m-d H:i:s");
                    $filename = "tmp/"."allreqs_".$sessid."-".date("m-d-Y", strtotime($date) ).".csv";
                    $fp = fopen("$filename", 'w');
                    $txt = "S.no,Date,Req_ID,Req Source,Skill,Location,Job Description,App Data,SM,BP Email,BP contact,IP/Tier1,End Client,Utilization Status,Total RC,Qualified,Req Status,Comment\n";
                    fwrite($fp, $txt);
                    $i = 0;
                    $level=0;
                    $value=0;
                    foreach($data as $row) {
                        $i = $i+1;
                                
                                $time = strtotime($row['datetime']); 
                                $cur_date = date("dmy", $time); 
                                $curweek = date("W", $time); 
                                $req_id = "W".$curweek.$cur_date."-".$row['ureq_id'];
                                $u_req_id = "W".$curweek.$cur_date."-".$row['ureq_id'];
                                $reqid_length = strlen($u_req_id);

                        if($unique==1 & $reqid_length>20)
                        {
                                $u_req_id = $row['reqid'];
                        }
                                
                        $date = date("m/d/y", $time);

                        $ureq_id = $row['ureq_id'];
                            $skillid = $row['skillid'];
						$selectedValue = $row['req_source'] ?? "17";
						$reqsource = $reqSources[$selectedValue];
                        $skill = $conn->query("SELECT skillname FROM `skill` WHERE `sid`= $skillid")->fetchColumn();
                            $jobtype = $row['jobtype'];
                        if($jobtype == 1) { $job = "Contract";} else { $job = "Contract to hire"; }
                            $reqid = $row['reqid'];
                        $location = $conn->query("select rlocation from req where reqid = $reqid")->fetchColumn();

                            $jd = $conn->query("select rdesc from jd where reqid = $reqid")->fetchColumn();
                        $jdtext = strip_tags($jd);
                        $jdtext = str_replace('&nbsp;', '', $jdtext);

                        $clientname = $conn->query("select rend_client from req where reqid = $reqid")->fetchColumn();

                        //posted by SM
                        if($weekly==1)
                        {
                            $sm_query = "select * from app_data as A Left Join req as B ON A.reqid  = B.reqid where B.reqid = '$reqid' and A.status=1";
                        }
						else if($monthly==1){
							$sm_query = "select * from app_data as A Left Join req as B ON A.reqid  = B.reqid where B.reqid = '$reqid' and A.status=1";
						}
                        else{
                        $sm_query = "select * from app_data as A Left Join req as B ON A.reqid  = B.reqid where B.ureq_id = '$ureq_id' and A.status=1 and DATE(B.datetime) =  DATE('$curdate') and MONTH(B.datetime) =  MONTH('$curdate') and YEAR(B.datetime) =  YEAR('$curdate') order by A.uid asc";
                        }
						
                        $sins= $conn->prepare($sm_query);
                        $sins->execute();
                        $smdata = $sins->fetchAll();
                        $appdata = "";
                        $bpcontact = "";
                        $comments = "";
						$sreqid = "";
						$ssmid = "";
                        $totalrc = 0;
                        $totalsub = 0;
                
                            foreach ($smdata as $sm)
                            {
                               /* $uid = $sm['B.uid'];
                            if(isset($smname)) { $sep = "\n"; }
                                $smname = $smname.$sep.$conn->query("SELECT * from users where `uid` = $uid")->fetchColumn();

                               */
                                $uid = $sm['uid'];
                                $smn = $conn->query("SELECT name from users where `uid` = $uid")->fetchColumn();

                                    $consultantid = $sm['consultant_id'];

                                    $consultantname = $conn->query("select cfname from consultants where cid = $consultantid")->fetchColumn();

                                $cid = $sm['client_id'];
                                $bpemail = $conn->query("SELECT remail from clients where `cid` = $cid")->fetchColumn();
                                $bpphone = $conn->query("SELECT rphone from clients where `cid` = $cid")->fetchColumn();

                                $bpcontact = $bpcontact.$bpemail."/".$bpphone."\n";

                                $rcdone = $sm['rcdone'];

                                if($rcdone==1)
                                {
                                    $totalrc =$totalrc+1;
                                }
                                $subdone = $sm['subdone'];
                                if($subdone==1)
                                {
                                    $totalsub =$totalsub+1;
                                }

                                $t1ip= $sm['t1ip_name'];

                                $appdata = $appdata.$smn." has applied ".$consultantname." through ".$bpemail." and did ".$rcdone." RC, ".$subdone." Sub."."\n"; 
                            
                                $appid = $sm['app_id'];
								if($sreqid == $reqid && $ssmid == $uid)
								{
									continue;
								}
							else {
									$sreqid = $reqid;
									$ssmid = $uid ;
                                    $com_query = "SELECT * FROM comments WHERE uid = $uid and ((com_postid = $reqid AND reqcom_id = 1) OR (com_postid = $appid AND appcom_id = 1) OR (com_postid = $appid AND rccom_id = 1) OR (com_postid = $appid AND subcom_id = 1) OR (com_postid = $appid AND ecicom_id = 1))";
                                    $cins= $conn->prepare($com_query);
                                    $cins->execute();
                                    $commentdata = $cins->fetchAll();
									$scom_id ="";
									$scuid = "";

                                foreach($commentdata as $comment)
                                {
									if($scom_id ==$comment['com_id'] && $scuid = $comment['uid'] )
									{
										continue;
									}
									else {
									$scom_id = $comment['com_id'];
                                    $uid = $comment['uid'];
                                    $scuid = $comment['uid'];
									
                                    $smname = $conn->query("SELECT name from users where `uid` = $uid")->fetchColumn();
                                    $comments = $comments.$smname.": ".trim($comment['comment'])." at ".$comment['datetime']."\n";
									}
                                }

                                if($row['reqstatus']==1)
                                {
                                    if($level<=2) 
                                    { 
                                        $level = 2; 
                                        $reqstatus = "Rejected";
                                    }
                                }
                                else if($row['reqstatus']==2)
                                {
                                    if($level<=3) 
                                    {
                                         $level = 3; 
                                         $reqstatus = "Closed";
                                    } 
                                }
                                else if($row['reqstatus']==3)
                                {
                                    if($level<=1) 
                                    { 
                                        $level = 1; 
                                        $reqstatus = "Not Connected"; 
                                    }
                                }
                                else if($row['reqstatus']==4)
                                {
                                    if($level<=4) 
                                    { 
                                        $level = 4; 
                                        $reqstatus = "Open";
                                    }
                                }
                                else if($row['reqstatus']==5)
                                {
                                    if($level<=5) 
                                    { 
                                        $level = 5;
                                        $reqstatus = "In process";
                                    }
                                }
                                else if($row['reqstatus']==6)
                                {
                                    if($level<=6) 
                                    { 
                                        $level = 6;
                                        $reqstatus = "No Number";
                                    }
                                }

                                if($row['qualified']==1)
                                {
                                    if($value==0) 
                                    { 
                                        $value = 1; 
                                        $qualified = "Qualified";
                                    }
                                }
                              /*  else if($row['qualified']==0)
                                {
                                    if($value==1) 
                                    { 
                                        $value = 0; 
                                        $qualified = "Not Qualified";
                                    }
                                } */
        
							} //close for same req id 

                            }

                           
                           
                            /*
                            if($weekly==1)
                            {
                                $totalrc = $conn->query("select count(*) from app_data as A Left Join req as B ON A.reqid  = B.reqid where B.reqid = '$reqid' and A.rcdone = 1 and B.status =1 and WEEK(A.rcdate) = WEEK('$curdate')")->fetchColumn();
                            }
                            elseif($monthly==1){
                                $totalrc = $conn->query("select count(*) from app_data as A Left Join req as B ON A.reqid  = B.reqid where B.reqid = '$reqid' and A.rcdone = 1 and B.status =1 and MONTH(A.rcdate) = MONTH('$curdate')")->fetchColumn();
                            }
                            else
                            {
                                $totalrc = $conn->query("select count(*) from app_data as A Left Join req as B ON A.reqid  = B.reqid where B.reqid = '$reqid' and A.rcdone = 1 and B.status =1")->fetchColumn();
                            }
                            */

                            if($totalrc>0)
                            {
                                $status = "Utilized";
                            }
                            else {
                                $status = "Unutilized";
                            }
                            if($value==0)
                            {
                                $qualified = "Not Qualified";
                            }
                            $value=0; //re-intialization
                            $level=0; //re-intialization
                     /*   if($unique==1)
                        {
                            $lineData = array($i,$date,$u_req_id,$skill,$location,$jdtext,$appdata,$bpcontact,$clientname,$status,$totalrc,$reqstatus,$comments);
                            fputcsv($fp, $lineData,",");
                        }
                        else
                        { */
                            //S.no,Date,Req_ID,Skill,Location,Job Description,App Data,SM,BP Email,BP contact,IP/Tier1,End Client,Utilization Status,Total RC,Comment
                            $lineData = array($i,$date,$req_id,$reqsource,$skill,$location,$jdtext,$appdata,$smn,$bpemail,$bpphone,$t1ip,$clientname,$status,$totalrc,$qualified,$reqstatus,$comments);
                            fputcsv($fp, $lineData,",");
                       // }
                        //$txt = "S.no,Date,Req_ID,Skill,Location,Job Description,App Data,End Client, Utilization Status,Total RC,Comment\n";
                        


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