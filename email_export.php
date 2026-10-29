<?php
require_once __DIR__.'/email_lib.php';$u=em_user();$db=em_db();
$w=['1=1'];$p=[];$status=(string)($_GET['status']??'');if(in_array($status,['scheduled','processing','sent','failed','cancelled'],true)){$w[]='m.status=?';$p[]=$status;}
$lid=(int)($_GET['list_id']??0);if($lid){$w[]='m.list_id=?';$p[]=$lid;}$req=(int)($_GET['reqid']??0);if($req){$w[]='m.reqid=?';$p[]=$req;}
foreach(['from'=>'>=','to'=>'<'] as $key=>$op){$value=(string)($_GET[$key]??'');if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$value)){$w[]='m.created_at'.$op.'?';$p[]=$key==='to'?date('Y-m-d',strtotime($value.' +1 day')).' 00:00:00':$value.' 00:00:00';}}
$sql='SELECT m.id,m.campaign_id,m.reqid,r.job_title,m.list_name,m.recipients_json,m.status,m.attempts,m.available_at,m.sent_at,m.last_error,m.created_at FROM em_messages m LEFT JOIN req r ON r.reqid=m.reqid WHERE '.implode(' AND ',$w).' ORDER BY m.id DESC LIMIT 20000';$s=$db->prepare($sql);$s->execute($p);
header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="email-sharing-history-'.gmdate('Ymd-His').'.csv"');$out=fopen('php://output','w');fputcsv($out,['Message ID','Campaign ID','Requirement ID','Job Title','Recipient List','To','CC','BCC','Status','Attempts','Scheduled UTC','Sent UTC','Error','Created UTC']);
function em_csv_safe($v): string {$v=(string)$v;return preg_match('/^[\s]*[=+@\-]/u',$v)?"'".$v:$v;}
while($r=$s->fetch()){$rc=json_decode($r['recipients_json'],true)?:[];$line=[$r['id'],$r['campaign_id'],$r['reqid'],$r['job_title'],$r['list_name'],implode('; ',$rc['to']??[]),implode('; ',$rc['cc']??[]),implode('; ',$rc['bcc']??[]),$r['status'],$r['attempts'],$r['available_at'],$r['sent_at'],$r['last_error'],$r['created_at']];fputcsv($out,array_map('em_csv_safe',$line));}fclose($out);
