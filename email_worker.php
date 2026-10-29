<?php
// CLI ONLY: php email_worker.php; run every minute via Task Scheduler / cron.
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require_once __DIR__.'/email_lib.php';require_once __DIR__.'/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
$db=em_db();$settings=em_settings();if((int)$settings['queue_paused']===1){echo "Queue paused\n";exit;}
if(!filter_var($settings['smtp_email'],FILTER_VALIDATE_EMAIL)||empty($settings['smtp_password_enc'])){fwrite(STDERR,"Configure Gmail SMTP in settings first\n");exit(1);}
// MySQL named lock protects against concurrent worker executions, even on multiple hosts.
$lock=$db->query("SELECT GET_LOCK('bats_em_worker_global',0)")->fetchColumn();if((int)$lock!==1){echo "Another worker is active\n";exit;}
try {
 $password=em_decrypt($settings['smtp_password_enc']);
 $now=new DateTimeImmutable('now',new DateTimeZone('UTC'));$tz=new DateTimeZone($settings['timezone_name']);
 $local=$now->setTimezone($tz);$clock=$local->format('H:i:s');
 if($settings['send_start']!==null&&$settings['send_end']!==null){$start=$settings['send_start'];$end=$settings['send_end'];$allowed=$start<=$end?($clock>=$start&&$clock<=$end):($clock>=$start||$clock<=$end);if(!$allowed){echo "Outside sending window\n";exit;}}
 $last=$db->query("SELECT sent_at FROM em_messages WHERE status='sent' ORDER BY sent_at DESC LIMIT 1")->fetchColumn();if($last&&time()-strtotime($last)<(int)$settings['min_gap_seconds']){echo "Waiting for minimum gap\n";exit;}
 $hourly=(int)$db->query("SELECT COUNT(*) FROM em_messages WHERE status='sent' AND sent_at >= UTC_TIMESTAMP() - INTERVAL 1 HOUR")->fetchColumn();if($hourly>=(int)$settings['hourly_limit']){echo "Hourly limit reached\n";exit;}
 $daily=(int)$db->query("SELECT COALESCE(SUM(recipient_count),0) FROM em_messages WHERE status='sent' AND sent_at >= UTC_TIMESTAMP() - INTERVAL 24 HOUR")->fetchColumn();
 $s=$db->query("SELECT * FROM em_messages WHERE status='scheduled' AND available_at<=UTC_TIMESTAMP() ORDER BY available_at,id LIMIT 1");$item=$s->fetch();if(!$item){echo "No due messages\n";exit;}
 if($daily+(int)$item['recipient_count']>(int)$settings['daily_recipient_limit']){echo "Daily recipient limit reached\n";exit;}
 $db->prepare("UPDATE em_messages SET status='processing',locked_at=UTC_TIMESTAMP(),attempts=attempts+1 WHERE id=? AND status='scheduled'")->execute([$item['id']]);
 $rc=json_decode($item['recipients_json'],true,512,JSON_THROW_ON_ERROR);
 $mail=new PHPMailer(true);$mail->isSMTP();$mail->Host='smtp.gmail.com';$mail->SMTPAuth=true;$mail->Username=$settings['smtp_email'];$mail->Password=$password;$mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS;$mail->Port=587;$mail->Timeout=25;$mail->CharSet='UTF-8';$mail->setFrom($settings['smtp_email'],$settings['smtp_from_name']);
 foreach($rc['to']??[] as $a)$mail->addAddress($a);foreach($rc['cc']??[] as $a)$mail->addCC($a);foreach($rc['bcc']??[] as $a)$mail->addBCC($a);
 $mail->isHTML(false);$mail->Subject=$item['subject'];$mail->Body=$item['body'];
 try{$mail->send();$db->prepare("UPDATE em_messages SET status='sent',sent_at=UTC_TIMESTAMP(),last_error=NULL,smtp_message_id=? WHERE id=?")->execute([$mail->getLastMessageID(),$item['id']]);em_log('smtp_accepted','SMTP accepted message', (int)$item['id'],(int)$item['campaign_id']);echo "Sent message #{$item['id']}\n";}
 catch(Throwable $e){$msg=mb_substr($e->getMessage(),0,800);$attempt=(int)$item['attempts']+1;
  // Temporary SMTP errors retry with exponential backoff. Permanent 5xx or auth errors fail.
  $temporary=preg_match('/(?:^|\D)4[0-9]{2}(?:\D|$)|4\.7\.|timed? out|connection|temporar|try again/i',$msg)===1;
  if($temporary&&$attempt<5){$minutes=min(240,5*(2**($attempt-1)));$db->prepare("UPDATE em_messages SET status='scheduled',available_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL $minutes MINUTE),last_error=? WHERE id=?")->execute([$msg,$item['id']]);em_log('retry_scheduled','Retry in '.$minutes.' minutes: '.$msg,(int)$item['id'],(int)$item['campaign_id']);}
  else{$db->prepare("UPDATE em_messages SET status='failed',last_error=? WHERE id=?")->execute([$msg,$item['id']]);em_log('send_failed',$msg,(int)$item['id'],(int)$item['campaign_id']);}
  fwrite(STDERR,"Send failed: $msg\n");
 }
} finally {$db->query("SELECT RELEASE_LOCK('bats_em_worker_global')");}
