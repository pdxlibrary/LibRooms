<?php


define("APPLICATION_MODE","DEVELOPMENT");	// DEVELOPMENT or PRODUCTION

if(APPLICATION_MODE == "DEVELOPMENT")
{
	// Database
	define("DB_TYPE","mysql");
	define("DB_SERVER","");
	define("DB_PORT","3306");
	define("DB_USER","");
	define("DB_PASS","");
	define("DB_NAME","");
}
else if(APPLICATION_MODE == "PRODUCTION")
{
	// Database
	define("DB_TYPE","mysql");
	define("DB_SERVER","");
	define("DB_PORT","3306");
	define("DB_USER","");
	define("DB_PASS","");
	define("DB_NAME","");
}
else
{
	exit();
}

// web location of the application
define("WEB_ROOT","/studyrooms/");

// Email
define("NOTICE_EMAIL_ADDRESS","");
define("SMTP_HOST","");



// TODO: move options to a table and add to settings screen
$capacity_options = array();
//$capacity_options["1-4"] = "1-4 People";
//$capacity_options["5-8"] = "5-8 People";
//$capacity_options["9-1000"] = "9+ People";
$capacity_options["1"] = "1";
$capacity_options["2"] = "2";
$capacity_options["3"] = "3";
$capacity_options["4"] = "4";
$capacity_options["5"] = "5";
$capacity_options["6"] = "6";
$capacity_options["7"] = "7";
$capacity_options["8"] = "8";
$capacity_options["8"] = "9";
$capacity_options["10"] = "10";
$capacity_options["11"] = "11";
$capacity_options["12"] = "12";
$capacity_options["13"] = "13";
$capacity_options["14"] = "14";
$capacity_options["15"] = "15";
$capacity_options["16"] = "16";
$capacity_options["17"] = "17";
$capacity_options["18"] = "18";

$status_options = array();
$status_options[] = "Scheduled";
$status_options[] = "Checked Out";
$status_options[] = "Completed";
$status_options[] = "Cancelled";

$fine_resolution_options = array("Resolved","Unresolved");





?>