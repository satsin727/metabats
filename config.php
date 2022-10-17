<?php

ob_start();
session_start();
error_reporting(0);
ini_set( 'display_errors', 0 );
ini_set('error_reporting', 0 );
ini_set('session.cookie_lifetime', 60 * 60 * 24 * 365);
ini_set('session.gc-maxlifetime', 60 * 60 * 24 * 365);
date_default_timezone_set("America/Chicago");

define( "DB_DSN", "mysql:host=localhost;dbname=oejwxwmy_bats" );
define( "DB_USERNAME", "oejwxwmy_bats" );
define( "DB_PASSWORD", "m3+@h0riz0n!" );

if(!isset($_SESSION['username'])){
$_SESSION['username']=0;
}

/*
function checkEmail($email) {
    $find1 = strpos($email, '@');
    $find2 = strpos($email, '.');
    return ($find1 !== false && $find2 !== false && $find2 > $find1);
 }  //return true or false

 function checkemail($str) {
    return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? FALSE : TRUE;
}
*/


?>