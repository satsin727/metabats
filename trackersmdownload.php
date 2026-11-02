<?php
require( "config.php" );
require_once __DIR__ . '/db_helpers.php'; // shared DB helpers (also loaded by config.php)
if($_SESSION['id'])
{
$sessid = $_SESSION['id'];
}
else
{
	header( "Location: admin.php" ); exit; 

}

$conn=null;
$conn = bats_db();
$query = "select * from users where `uid` = :u";
$ins= $conn->prepare($query);
$ins->bindValue( ":u", $sessid, PDO::PARAM_STR );
$ins->execute();
$dta = $ins->fetch();
$conn=null; 
$curdate =date('Y-m-d');

if(isset($_GET['date']))
{
    $curdate =$_GET['date'];
}
$uid = $dta['uid'];
$smid = $_GET['smid'];

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'] && $uid == $smid)
{

$download = $_GET['download'];

if($download == "mapp")
{
$query = ("SELECT * FROM `app_data` WHERE `status` = 1 and`uid`= " . bats_q($uid) . " and appdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND appdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(appdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(appdate) = YEAR(" . bats_q("$curdate") . ")  order by appdate asc"); }
elseif($download == "mrc")
{$query = ("SELECT * FROM `app_data` WHERE `rcdone` = 1 and`uid`= " . bats_q($uid) . " and `status`= 1 and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(rcdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ") order by rcdate asc");}
elseif($download == "msub")
{$query = ("SELECT * FROM `app_data` WHERE `subdone` = 1 and  `rcdone` = 1 and `status`= 1  and`uid`= " . bats_q($uid) . " and subdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND subdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(subdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(subdate) = YEAR(" . bats_q("$curdate") . ") order by subdate asc");}
elseif($download == "meci")
{$query = ("SELECT * FROM `eci` WHERE `eci_happened` =1 and `status` = 1  and`uid`= " . bats_q($uid) . " and eci_date >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND eci_date < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND MONTH(eci_date) = MONTH(" . bats_q("$curdate") . ")  and YEAR(eci_date) = YEAR(" . bats_q("$curdate") . ")  order by eci_req_date asc");}
elseif($download == "wapp")
{$query = ("SELECT * FROM `app_data` WHERE `status` = 1   and`uid`= " . bats_q($uid) . " and appdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND appdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(appdate) = WEEK(" . bats_q("$curdate") . ") and YEAR(appdate) = YEAR(" . bats_q("$curdate") . ") order by appdate asc"); }
elseif($download == "wrc")
{$query = ("SELECT * FROM `app_data` WHERE `rcdone` = 1  and`uid`= " . bats_q($uid) . " and `status`= 1 and rcdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY, DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND rcdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY, DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(rcdate) = WEEK(" . bats_q("$curdate") . ")  and MONTH(rcdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ") order by rcdate asc");}
elseif($download == "wsub")
{$query = ("SELECT * FROM `app_data` WHERE `subdone` = 1  and`uid`= " . bats_q($uid) . " and  `rcdone` = 1 and `status`= 1 and subdate >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY, DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND subdate < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY, DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(subdate) = WEEK(" . bats_q("$curdate") . ") and MONTH(subdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(subdate) = YEAR(" . bats_q("$curdate") . ") order by subdate asc");}
elseif($download == "weci")
{$query = ("SELECT * FROM `eci` WHERE `eci_happened` =1 and`uid`= " . bats_q($uid) . "  and `status` = 1 and eci_date >= GREATEST(MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY, DATE(" . bats_q("$curdate") . ") - INTERVAL 6 DAY) AND eci_date < LEAST(MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY, DATE(" . bats_q("$curdate") . ") + INTERVAL 7 DAY) AND WEEK(eci_date) = WEEK(" . bats_q("$curdate") . ")  and MONTH(eci_date) = MONTH(" . bats_q("$curdate") . ") and YEAR(eci_date) = YEAR(" . bats_q("$curdate") . ")  order by eci_req_date asc");}
elseif($download == "dapp")
{$query = ("SELECT * FROM `app_data` WHERE `status` = 1   and`uid`= " . bats_q($uid) . " and appdate >= GREATEST(DATE(" . bats_q("$curdate") . "), MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND appdate < LEAST(DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY, MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND DATE(appdate) = DATE(" . bats_q("$curdate") . ") and MONTH(appdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(appdate) = YEAR(" . bats_q("$curdate") . ") order by appdate asc"); }
elseif($download == "drc")
{$query = ("SELECT * FROM `app_data` WHERE `rcdone` = 1  and`uid`= " . bats_q($uid) . " and `status`= 1 and rcdate >= GREATEST(DATE(" . bats_q("$curdate") . "), MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND rcdate < LEAST(DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY, MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND DATE(rcdate) = DATE(" . bats_q("$curdate") . ")  and MONTH(rcdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(rcdate) = YEAR(" . bats_q("$curdate") . ") order by rcdate asc");}
elseif($download == "dsub")
{$query = ("SELECT * FROM `app_data` WHERE `subdone` = 1  and`uid`= " . bats_q($uid) . " and  `rcdone` = 1 and `status`= 1 and subdate >= GREATEST(DATE(" . bats_q("$curdate") . "), MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND subdate < LEAST(DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY, MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND DATE(subdate) = DATE(" . bats_q("$curdate") . ")  and MONTH(subdate) = MONTH(" . bats_q("$curdate") . ") and YEAR(subdate) = YEAR(" . bats_q("$curdate") . ") order by subdate asc");}
elseif($download == "deci")
{$query = ("SELECT * FROM `eci` WHERE `eci_happened` =1  and`uid`= " . bats_q($uid) . " and `status` = 1 and eci_date >= GREATEST(DATE(" . bats_q("$curdate") . "), MAKEDATE(YEAR(" . bats_q("$curdate") . "),1), LAST_DAY(" . bats_q("$curdate") . ") - INTERVAL 1 MONTH + INTERVAL 1 DAY) AND eci_date < LEAST(DATE(" . bats_q("$curdate") . ") + INTERVAL 1 DAY, MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1), LAST_DAY(" . bats_q("$curdate") . ") + INTERVAL 1 DAY) AND DATE(eci_date) = DATE(" . bats_q("$curdate") . ")  and MONTH(eci_date) = MONTH(" . bats_q("$curdate") . ") and YEAR(eci_date) = YEAR(" . bats_q("$curdate") . ") order by eci_req_date asc");}
elseif($download == "yapp")
{$query = ("SELECT * FROM `app_data` WHERE `status` = 1   and`uid`= " . bats_q($uid) . " and appdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND appdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(appdate) = YEAR(" . bats_q("$curdate") . ") order by appdate asc"); }
elseif($download == "allapp")
{$query = ("SELECT * FROM `app_data` WHERE `status` = 1  and`uid`= " . bats_q($uid) . " order by appdate asc"); }
elseif($download == "yrc")
{$query = ("SELECT * FROM `app_data` WHERE `status` = 1  and`uid`= " . bats_q($uid) . " and  `rcdone` = 1 and appdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND appdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(appdate) = YEAR(" . bats_q("$curdate") . ") order by appdate asc"); }
elseif($download == "yeci")
{$query = ("SELECT * FROM `eci` WHERE `eci_happened` =1  and`uid`= " . bats_q($uid) . " and `status` = 1 and eci_date >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND eci_date < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(eci_date) = YEAR(" . bats_q("$curdate") . ") order by eci_req_date asc"); }
elseif($download == "teci")
{$query = ("SELECT * FROM `eci` WHERE `eci_happened` =1  and`uid`= " . bats_q($uid) . " and `status` = 1 order by eci_req_date asc"); }
elseif($download == "ysub")
{$query = ("SELECT * FROM `app_data` WHERE `subdone` = 1  and`uid`= " . bats_q($uid) . " and  `rcdone` = 1 and `status`= 1 and subdate >= MAKEDATE(YEAR(" . bats_q("$curdate") . "),1) AND subdate < MAKEDATE(YEAR(" . bats_q("$curdate") . ")+1,1) AND YEAR(subdate) = YEAR(" . bats_q("$curdate") . ") order by subdate asc");}

$conn = null;
$conn = bats_db();
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();

				if($download == "mapp" || $download == "wapp" || $download == "dapp")
				{
					$date = date("Y-m-d H:i:s");
                    $filename = "tmp/"."app_list_".$sessid."-".date("m-d-Y", strtotime($date) ).".csv";
                    $fp = fopen("$filename", 'w');
                    $txt = "S.no,Date,Req_ID,SM,Consultant Name,Skill,Location,BP Company Name,BP Name,BP Email,BP Phone,BP Location,BP Timezone,Tier,Client,RC Status,Sub Status,Status,Comment\n";
                    fwrite($fp, $txt);
                    $i = 0;
                    foreach($data as $row) {
                        $i = $i+1;

                                $time = strtotime($row['appdate']); 
                        $date = date("m/d/y", $time);

                                $uid = $row['uid'];
                                $q4 = ("SELECT * from users where `uid` = " . bats_q($uid));
								$ins5= $conn->prepare($q4);
								$ins5->execute(); 
								$dta4 = $ins5->fetch();
                        $sm = $dta4['name'];

                                $consultantid = $row['consultant_id'];
                                $cfname = bats_col('consultants', 'cid', $consultantid, 'cfname');    
                                $cmname = bats_col('consultants', 'cid', $consultantid, 'cmname');
                                $clname = bats_col('consultants', 'cid', $consultantid, 'clname');
                                
                        $consultantname = $cfname." ".$cmname." ".$clname;

                                $skillid = bats_col('consultants', 'cid', $consultantid, 'skill');
                        $skill = bats_col('skill', 'sid', $skillid, 'skillname');
                        
                                $reqid = $row['reqid'];
                        $location = bats_col('req', 'reqid', $reqid, 'rlocation');
                                $ureq_id = bats_col('req', 'reqid', $reqid, 'ureq_id');
                                $udate = bats_col('req', 'reqid', $reqid, 'datetime');
                                $utime = strtotime($udate);
                                $cur_date = date("dmy", $utime); 
                                $curweek = date("W", $utime); 
                                $ureq_id = "W".$curweek.$cur_date."-".$ureq_id;

                       // $jd = $conn->query("select rdesc from jd where reqid = $reqid")->fetchColumn();
                       // $jdtext = strip_tags(html_entity_decode($jd));
                       // $jdtext = str_replace('Â', '', $jdtext);                        
                       // $jdtext = str_replace('â', '', $jdtext);
                      //  $jdtext = str_replace('€', '', $jdtext);
                        
                                $cid = $row['client_id'];
                        $bpcompany = bats_col('clients', 'cid', $cid, 'companyname');
                        $bpname = bats_col('clients', 'cid', $cid, 'rname');
                        $bpemail = bats_col('clients', 'cid', $cid, 'remail');
                        $bpphone = bats_col('clients', 'cid', $cid, 'rphone');                        
                        $bplocation = bats_col('clients', 'cid', $cid, 'rlocation');
                        $bptier = bats_col('clients', 'cid', $cid, 'tier');      
                        $bptimezone = bats_col('clients', 'cid', $cid, 'rtimezon');    
                    

                                $app_id = $row['app_id'];
                        $ipname = bats_col('app_data', 'app_id', $app_id, 't1ip_name');
                        $clientname = bats_col('req', 'reqid', $reqid, 'rend_client');

                       
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

                        
                                $ars_status = bats_col('app_data', 'app_id', $app_id, 'ars_status');
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
                                elseif($ars_status == 11)
                                {
                                    $status =  "Rejected - Bad Profile";
                                }
                                elseif($ars_status == 12)
                                {
                                    $status =  "Rejected - Senior Requirement";
                                }
                                elseif($ars_status == 13)
                                {
                                    $status =  "Rejected - Local candidate needed";
                                }
                                elseif($ars_status == 14)
                                {
                                    $status =  "Rejected - Position went onhold";
                                }
                                elseif($ars_status == 15)
                                {
                                    $status =  "Rejected - Day 1 Onsite Required";
                                }
                                elseif($ars_status == 16)
                                {
                                    $status =  "Rejected - Low Rate";
                                }
                                elseif($ars_status == 17)
                                {
                                    $status =  "Rejected - Consultant Response";
                                }
                                elseif($ars_status == 18)
                                {
                                    $status =  "Rejected - Due to Linkedin";
                                }
                        $comment = $conn->query(("SELECT `comment` FROM `comments` WHERE `com_postid` = " . bats_q($app_id) . " and appcom_id = 1"))->fetchColumn();
                        if(isset($row['feedback']))
                        {
                            $feedback = $row['feedback'];
                        }
                        else
                        {
                            $feedback = "NA";
                        }
                        if($row['rcdone']==1)
                        {
                            $rcdone = "Yes";
                            if($row['subdone']==0)
                            {
                                $subdone = "No";
                                $comment = $conn->query(("SELECT `comment` FROM `comments` WHERE `com_postid` = " . bats_q($app_id) . " and rccom_id = 1"))->fetchColumn();    
                            }
                            else {
                                $subdone = "Yes";
                                $comment = $conn->query(("SELECT `comment` FROM `comments` WHERE `com_postid` = " . bats_q($app_id) . " and subcom_id = 1"))->fetchColumn();
                            }
                            if($comment=="")
                            {
                                $comment = $conn->query(("SELECT `comment` FROM `comments` WHERE `com_postid` = " . bats_q($app_id) . " and appcom_id = 1"))->fetchColumn();
                            }
                        }
                        else
                        {
                            $rcdone = "No";
                            $subdone = "No";
                        }
                    
                        $lineData = array($i,$date,$ureq_id,$sm,$consultantname,$skill,$location,$bpcompany,$bpname,$bpemail,$bpphone,$bplocation,$bptimezone,$bptier,$client,$rcdone,$subdone,$status,$comment);
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
            @unlink($filename); // do not leave client data in tmp/
                    exit();
				}

                elseif($download == "yapp" || $download == "allapp" || $download == "yrc" || $download == "ysub")
				{
					$date = date("Y-m-d H:i:s");
                    $filename = "tmp/"."allapp_list_".$sessid."-".date("m-d-Y", strtotime($date) ).".csv";
                    $fp = fopen("$filename", 'w');
                    $txt = "S.no,Date,SM,Consultant Name,Skill,Location,BP Company Name,BP Name,BP Email,BP Phone,BP Location,BP Timezone,Tier,Client,RC Status,Sub Status,Status,Comment,Feedback\n";
                    fwrite($fp, $txt);
                    $i = 0;
                    foreach($data as $row) {
                        $i = $i+1;

                                $time = strtotime($row['appdate']); 
                        $date = date("m/d/y", $time);

                                $uid = $row['uid'];
                                $q4 = ("SELECT * from users where `uid` = " . bats_q($uid));
								$ins5= $conn->prepare($q4);
								$ins5->execute(); 
								$dta4 = $ins5->fetch();
                        $sm = $dta4['name'];

                                $consultantid = $row['consultant_id'];
                                $cfname = bats_col('consultants', 'cid', $consultantid, 'cfname');    
                                $cmname = bats_col('consultants', 'cid', $consultantid, 'cmname');
                                $clname = bats_col('consultants', 'cid', $consultantid, 'clname');
                                
                        $consultantname = $cfname." ".$cmname." ".$clname;

                                $skillid = bats_col('consultants', 'cid', $consultantid, 'skill');
                        $skill = bats_col('skill', 'sid', $skillid, 'skillname');
                        
                                $reqid = $row['reqid'];
                        $location = bats_col('req', 'reqid', $reqid, 'rlocation');
                        /*        $ureq_id = bats_col('req', 'reqid', $reqid, 'ureq_id');
                                $udate = bats_col('req', 'reqid', $reqid, 'datetime');
                                $utime = strtotime($udate);
                                $cur_date = date("dmy", $utime); 
                                $curweek = date("W", $utime); 
                                $ureq_id = "W".$curweek.$cur_date."-".$ureq_id;
                        */
                                $cid = $row['client_id'];
/*
                                $cq = "SELECT * from clients where `cid` = $cid";
                                $pq1= $conn->prepare($cq);
                                $pq1->execute(); 
                                $cdata = $pq1->fetchAll();
                        $bpcompany = $cdata['companyname'];
                        $bpname = $cdata['rname'];
                        $bpemail = $cdata['remail'];
                        $bpphone = $cdata['rphone'];
                        $bplocation = $cdata['rlocation'];
                        $bptier = $cdata['tier'];
                        $bptimezone = $cdata['rtimezon']; */
                        $bpcompany = bats_col('clients', 'cid', $cid, 'companyname');
                        $bpname = bats_col('clients', 'cid', $cid, 'rname');
                        $bpemail = bats_col('clients', 'cid', $cid, 'remail');
                        $bpphone = bats_col('clients', 'cid', $cid, 'rphone');                        
                        $bplocation = bats_col('clients', 'cid', $cid, 'rlocation');
                        $bptier = bats_col('clients', 'cid', $cid, 'tier');      
                        $bptimezone = bats_col('clients', 'cid', $cid, 'rtimezon');   
                    //    $bpuid = bats_col('clients', 'cid', $cid, 'uid'); 
                    //    $bpsm = bats_col('users', 'uid', $bpuid, 'name');                      
                                $app_id = $row['app_id'];
                        $ipname = bats_col('app_data', 'app_id', $app_id, 't1ip_name');
                        $clientname = bats_col('req', 'reqid', $reqid, 'rend_client');

                       

                    
                                $ars_status = bats_col('app_data', 'app_id', $app_id, 'ars_status');
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
                                elseif($ars_status == 11)
                                {
                                    $status =  "Rejected - Bad Profile";
                                }
                                elseif($ars_status == 12)
                                {
                                    $status =  "Rejected - Senior Requirement";
                                }
                                elseif($ars_status == 13)
                                {
                                    $status =  "Rejected - Local candidate needed";
                                }
                                elseif($ars_status == 14)
                                {
                                    $status =  "Rejected - Position went onhold";
                                }
                                elseif($ars_status == 15)
                                {
                                    $status =  "Rejected - Day 1 Onsite Required";
                                }
                                elseif($ars_status == 16)
                                {
                                    $status =  "Rejected - Low Rate";
                                }
                                elseif($ars_status == 17)
                                {
                                    $status =  "Rejected - Consultant Response";
                                }
                                elseif($ars_status == 18)
                                {
                                    $status =  "Rejected - Due to Linkedin";
                                }
                        $comment = $conn->query(("SELECT `comment` FROM `comments` WHERE `com_postid` = " . bats_q($app_id) . " and appcom_id = 1"))->fetchColumn();
                        /*
                        $appid = $row['app_id'];
                                    $com_query = "select * from comments where com_postid = $appid";
                                    $cins= $conn->prepare($com_query);
                                    $cins->execute();
                                    $commentdata = $cins->fetchAll();

                                foreach($commentdata as $comment)
                                {
                                    $uid = $comment['uid'];
                                    $smname = bats_col('users', 'uid', $uid, 'name');
                                    $comments = $comments.$smname.": ".$comment['comment']." at ".$comment['datetime']."\n";
                                }
                                
                        */
                        
                        if(isset($row['feedback']))
                        {
                            $feedback = $row['feedback'];
                        }
                        else
                        {
                            $feedback = "NA";
                        }
                        if($row['rcdone']==1)
                        {
                            $rcdone = "Yes";
                            if($row['subdone']==0)
                            {
                                $subdone = "No";
                                $comment = $conn->query(("SELECT `comment` FROM `comments` WHERE `com_postid` = " . bats_q($app_id) . " and rccom_id = 1"))->fetchColumn();    
                            }
                            else {
                                $subdone = "Yes";
                                $comment = $conn->query(("SELECT `comment` FROM `comments` WHERE `com_postid` = " . bats_q($app_id) . " and subcom_id = 1"))->fetchColumn();
                            }
                            if($comment=="")
                            {
                                $comment = $conn->query(("SELECT `comment` FROM `comments` WHERE `com_postid` = " . bats_q($app_id) . " and appcom_id = 1"))->fetchColumn();
                            }
                        }
                        else
                        {
                            $rcdone = "No";
                            $subdone = "No";
                        }
                    //S.no,Date,SM,Consultant Name,Skill,Location,BP Company Name,BP Name,BP Email,BP Phone,BP Location,Tier,BP_SM,Client,RC Status,Sub Status
                        $lineData = array($i,$date,$sm,$consultantname,$skill,$location,$bpcompany,$bpname,$bpemail,$bpphone,$bplocation,$bptimezone,$bptier,$clientname,$rcdone,$subdone,$status,$comment,$feedback);
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
            @unlink($filename); // do not leave client data in tmp/
                    exit();
                    
				}
                elseif($download == "mrc" || $download == "wrc" || $download == "drc")
				{
					$date = date("Y-m-d H:i:s");
                    $filename = "tmp/"."rc_list_".$sessid."-".date("m-d-Y", strtotime($date) ).".csv";
                    $fp = fopen("$filename", 'w');
                    $txt = "S.no,Date,SM,Consultant Name,Skill,Location,Job Description,BP Email,BP Phone,Rate,Tier,IP/EC,Sub Status,Status,Comment\n";
                    fwrite($fp, $txt);
                    $i = 0;
                    foreach($data as $row) {
                        $i = $i+1;

                                $time = strtotime($row['appdate']); 
                        $date = date("m/d/y", $time);

                                $uid = $row['uid'];
                                $q4 = ("SELECT * from users where `uid` = " . bats_q($uid));
								$ins5= $conn->prepare($q4);
								$ins5->execute(); 
								$dta4 = $ins5->fetch();
                        $sm = $dta4['name'];

                                $consultantid = $row['consultant_id'];
                                $cfname = bats_col('consultants', 'cid', $consultantid, 'cfname');    
                                $cmname = bats_col('consultants', 'cid', $consultantid, 'cmname');
                                $clname = bats_col('consultants', 'cid', $consultantid, 'clname');
                                
                        $consultantname = $cfname." ".$cmname." ".$clname;

                                $skillid = bats_col('consultants', 'cid', $consultantid, 'skill');
                        $skill = bats_col('skill', 'sid', $skillid, 'skillname');
                        
                                $reqid = $row['reqid'];
                        $location = bats_col('req', 'reqid', $reqid, 'rlocation');
                      
                        $jd = $conn->query(("select rdesc from jd where reqid = " . bats_q($reqid)))->fetchColumn();
                        $jdtext = strip_tags(html_entity_decode($jd));
                        $jdtext = str_replace('â€¢Â', '', $jdtext);
                        $jdtext = str_replace('Â', '', $jdtext);                        
                        $jdtext = str_replace('â', '', $jdtext);
                        $jdtext = str_replace('â€“', '', $jdtext);
                        
                            $cid = $row['client_id'];
                        $bpemail = bats_col('clients', 'cid', $cid, 'remail');
                        $bpphone = bats_col('clients', 'cid', $cid, 'rphone');
                        

                                $app_id = $row['app_id'];
                        $ipname = bats_col('app_data', 'app_id', $app_id, 't1ip_name');
                        $clientname = bats_col('req', 'reqid', $reqid, 'rend_client');

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

                        
                                $ars_status = bats_col('app_data', 'app_id', $app_id, 'ars_status');
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
                                elseif($ars_status == 11)
                                {
                                    $status =  "Rejected - Bad Profile";
                                }
                                elseif($ars_status == 12)
                                {
                                    $status =  "Rejected - Senior Requirement";
                                }
                                elseif($ars_status == 13)
                                {
                                    $status =  "Rejected - Local candidate needed";
                                }
                                elseif($ars_status == 14)
                                {
                                    $status =  "Rejected - Position went onhold";
                                }
                                elseif($ars_status == 15)
                                {
                                    $status =  "Rejected - Day 1 Onsite Required";
                                }
                                elseif($ars_status == 16)
                                {
                                    $status =  "Rejected - Low Rate";
                                }
                                elseif($ars_status == 17)
                                {
                                    $status =  "Rejected - Consultant Response";
                                }
                                elseif($ars_status == 18)
                                {
                                    $status =  "Rejected - Due to Linkedin";
                                }
                       
                        $comment = $conn->query(("SELECT `comment` FROM `comments` WHERE `com_postid` = " . bats_q($app_id) . " and rccom_id = 1"))->fetchColumn();
                        if(isset($row['feedback']))
                        {
                            $feedback = $row['feedback'];
                        }
                        else
                        {
                            $feedback = "NA";
                        }
                        if($row['subdone']==1)
                        {
                            $subdone = "Yes";
                            $comment = $conn->query(("SELECT `comment` FROM `comments` WHERE `com_postid` = " . bats_q($app_id) . " and subcom_id = 1"))->fetchColumn();
                        }
                        else
                        {
                            $subdone = "No";
                        }

                        $layer = bats_col('clients', 'cid', $cid, 'tier');
                        $rate = $row['rateperhour'];
                    
                        $lineData = array($i,$date,$sm,$consultantname,$skill,$location,$jdtext,$bpemail,$bpphone,$layer,$rate,$client,$subdone,$status,$comment);
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
            @unlink($filename); // do not leave client data in tmp/
                    exit();
				}

                
                elseif($download == "msub" || $download == "wsub" || $download == "dsub")
				{
					$date = date("Y-m-d H:i:s");
                    $filename = "tmp/"."sub_list_".$sessid."-".date("m-d-Y", strtotime($date) ).".csv";
                    $fp = fopen("$filename", 'w');
                    $txt = "S.no,Date,SM,Consultant Name,Skill,Location,BP Email,BP Phone,Tier,Rate,IP/EC,ECI Status,Status,Comment\n";
                    fwrite($fp, $txt);
                    $i = 0;
                    foreach($data as $row) {
                        $i = $i+1;

                                $time = strtotime($row['appdate']); 
                        $date = date("m/d/y", $time);

                                $uid = $row['uid'];
                                $q4 = ("SELECT * from users where `uid` = " . bats_q($uid));
								$ins5= $conn->prepare($q4);
								$ins5->execute(); 
								$dta4 = $ins5->fetch();
                        $sm = $dta4['name'];

                                $consultantid = $row['consultant_id'];
                                $cfname = bats_col('consultants', 'cid', $consultantid, 'cfname');    
                                $cmname = bats_col('consultants', 'cid', $consultantid, 'cmname');
                                $clname = bats_col('consultants', 'cid', $consultantid, 'clname');
                                
                        $consultantname = $cfname." ".$cmname." ".$clname;

                                $skillid = bats_col('consultants', 'cid', $consultantid, 'skill');
                        $skill = bats_col('skill', 'sid', $skillid, 'skillname');
                        
                                $reqid = $row['reqid'];
                        $location = bats_col('req', 'reqid', $reqid, 'rlocation');
                       
                                $cid = $row['client_id'];
                        $bpemail = bats_col('clients', 'cid', $cid, 'remail');

                        $bpphone = bats_col('clients', 'cid', $cid, 'rphone');
                        

                                $app_id = $row['app_id'];
                        $ipname = bats_col('app_data', 'app_id', $app_id, 't1ip_name');
                        $clientname = bats_col('req', 'reqid', $reqid, 'rend_client');
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
                        
                                $ars_status = bats_col('app_data', 'app_id', $app_id, 'ars_status');
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
                                elseif($ars_status == 11)
                                {
                                    $status =  "Rejected - Bad Profile";
                                }
                                elseif($ars_status == 12)
                                {
                                    $status =  "Rejected - Senior Requirement";
                                }
                                elseif($ars_status == 13)
                                {
                                    $status =  "Rejected - Local candidate needed";
                                }
                                elseif($ars_status == 14)
                                {
                                    $status =  "Rejected - Position went onhold";
                                }
                                elseif($ars_status == 15)
                                {
                                    $status =  "Rejected - Day 1 Onsite Required";
                                }
                                elseif($ars_status == 16)
                                {
                                    $status =  "Rejected - Low Rate";
                                }
                                elseif($ars_status == 17)
                                {
                                    $status =  "Rejected - Consultant Response";
                                }
                                elseif($ars_status == 18)
                                {
                                    $status =  "Rejected - Due to Linkedin";
                                }
                       
                        $comment = $conn->query(("SELECT `comment` FROM `comments` WHERE `com_postid` = " . bats_q($app_id) . " and subcom_id = 1"))->fetchColumn();
                        if(isset($row['feedback']))
                        {
                            $feedback = $row['feedback'];
                        }
                        else
                        {
                            $feedback = "NA";
                        }
                        if($row['hasinterview']==1)
                        {
                            $hasinterview = "Yes";
                        }
                        else
                        {
                            $hasinterview = "No";
                        }


                        $layer = bats_col('clients', 'cid', $cid, 'tier');
                        $rate = $row['rateperhour'];
                    
                        $lineData = array($i,$date,$sm,$consultantname,$skill,$location,$bpemail,$bpphone,$layer,$rate,$client,$hasinterview,$status,$comment);
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
            @unlink($filename); // do not leave client data in tmp/
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
