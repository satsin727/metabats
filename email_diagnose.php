<?php
// Run only via SSH / Bluehost Terminal: php email_diagnose.php
if (PHP_SAPI !== 'cli') {http_response_code(403);exit('CLI only');}
error_reporting(E_ALL);ini_set('display_errors','1');
$root=__DIR__;
foreach (['config.php','email_lib.php','email_distribution.php','includes/header.php','includes/menu.php','includes/footer.php','vendor/autoload.php'] as $file) {
 echo (is_readable($root.'/'.$file)?'[OK] ':'[MISSING] ').$file.PHP_EOL;
}
foreach (['pdo_mysql','openssl','mbstring'] as $ext) echo (extension_loaded($ext)?'[OK] ':'[MISSING] ').'PHP extension '.$ext.PHP_EOL;
try {
 require_once $root.'/email_lib.php';
 $db=em_db();echo '[OK] Database connection'.PHP_EOL;
 foreach (['em_settings','em_lists','em_contacts','em_list_members','em_templates','em_campaigns','em_messages','em_logs','req','users'] as $table) {
  try {$db->query('SELECT 1 FROM `'.$table.'` LIMIT 1');echo '[OK] Table '.$table.PHP_EOL;}
  catch(Throwable $e){echo '[ERROR] Table '.$table.' : '.$e->getMessage().PHP_EOL;}
 }
 $settings=em_settings();echo ($settings?'[OK] ':'[ERROR] ').'em_settings row id=1'.PHP_EOL;
 $key=getenv('EMAIL_SECRET_KEY');$raw=$key?base64_decode($key,true):false;
 echo ($raw!==false && strlen($raw)===32?'[OK] ':'[ERROR] ').'EMAIL_SECRET_KEY available and correctly sized'.PHP_EOL;
} catch(Throwable $e){echo '[ERROR] '.$e->getMessage().PHP_EOL;exit(1);}
