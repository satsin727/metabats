	<?php

ob_start();
session_start();
ini_set( "display_errors", true );
date_default_timezone_set("America/Chicago");
//define( "ADMIN_USERNAME", "admin@metahorizon.com" );
//define( "ADMIN_PASSWORD", "deathburner" );

define( "DB_DSN", "mysql:host=localhost;dbname=new_bats" );
define( "DB_USERNAME", "metahorizon" );
define( "DB_PASSWORD", "metahorizon" );

if(!isset($_SESSION['username'])){
$_SESSION['username']=0;
}


?>