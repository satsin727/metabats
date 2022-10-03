<?php

ob_start();
session_start();
ini_set( "display_errors", false );
date_default_timezone_set("America/Chicago");
//define( "ADMIN_USERNAME", "admin@metahorizon.com" );
//define( "ADMIN_PASSWORD", "deathburner" );

define( "DB_DSN", "mysql:host=162.241.80.6;dbname=oejwxwmy_bats" );
define( "DB_USERNAME", "oejwxwmy_bats" );
define( "DB_PASSWORD", "m3+@h0riz0n!" );

if(!isset($_SESSION['username'])){
$_SESSION['username']=0;
}


?>