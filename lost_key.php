<?php

session_start();

require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("includes/email_communication.php");
require_once("load.php");

require_once("includes/verify_access.php");
restrict_access($db,array("staff","admin"));


$key_barcode = $_GET['key_barcode'];
$reservations = get_reservation_for_checked_out_key($key_barcode);	

$page_title = "Lost Key";
require_once("includes/header.php");
print("<div id='PageTitle'>$page_title</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; $page_title</div>\n");
print("<br>\n");

if(count($reservations) == 0)
{
	display_error("Associated reservation could not be found for key [$key_barcode]",$_GET);
	exit();
}
else if(count($reservations) > 1)
{
	display_error("Multiple reservations for the same key checkout [$key_barcode]",$reservations);
	exit();
}
else
{
	foreach($reservations as $reservation)
		break;
	
	// assign lost key fine to reservation
	assign_fine($reservation->id,FINE_LOST_KEY,"Lost key fine");
	
	// assign lost key processing fee to reservation
	assign_fine($reservation->id,LOST_KEY_PROCESSING_FEE,"Lost key processing fee");
	
	// change reservation to completed status
	update_reservation_status($reservation->id,"Completed");
	
	// set key status as lost
	update_key_status($key_barcode,"Lost");
	
	// send email notice to user
	lost_key_email($db,$reservation->id);
	
	print("<h2>Key [$key_barcode] successfully reported lost</h2>\n");
	
}

require_once("includes/footer.php");


?>