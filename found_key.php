<?php

session_start();

require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("load.php");

require_once("includes/verify_access.php");
restrict_access($db,array("staff","admin"));

$key_barcode = $_GET['key_barcode'];

$page_title = "Found Key";
require_once("includes/header.php");
print("<div id='PageTitle'>$page_title</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; $page_title</div>\n");
print("<br>\n");


if(isset($_GET['activate']))
{
	update_key_status($key_barcode,'Available');
	print("<h2>This key [$key_barcode] has now been re-activated!</h2>\n");	
}
else
{
	print("<h2>This key [$key_barcode] was reported lost!</h2>\n");	
	print("If the door locks have not been replaced, then this key can be made available for use again.<br>\n");

	print("<a href='found_key.php?key_barcode=$key_barcode&activate'>Return Key to Active Duty</a><br>\n");
}


require_once("includes/footer.php");


?>