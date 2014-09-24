<?php

session_start();
require_once("config/config.inc.php");
require_once("config/strings.inc.php");

require_once("includes/Database.php");

require_once("load.php");
require_once("includes/load_hours_data.php");
require_once("includes/load_rooms.php");
require_once("includes/error_checking.php");

require_once("includes/verify_access.php");
restrict_access($db,array("staff","admin"));

$reservation_id = $_GET['reservation_id'];
$room_id = $_GET['room_id'];
$checkout_key_barcode = $_GET['checkout_key_barcode'];

if(isset($_GET['user_id']))
	$user = get_user_by_id($user_id);


// KEY CHECKOUT
if(isset($_GET['reservation_id']) && isset($_GET['room_id']) && isset($_GET['checkout_key_barcode']))
{
	// TODO: check to make sure key hasn't already been checked out (reload issue)
	$checkout_result = checkout_key($_GET['reservation_id'],$_GET['room_id'],$_GET['checkout_key_barcode']);
	//pr($checkout_result);
	
	// reload user
	$user = get_user_by_id($_GET['user_id']);
	//pr($user);

	if(!is_array($checkout_result))
	{
		if(isset($_GET['referrer']) && strcmp($_GET['referrer'],''))
		{
			header("location: ".$_GET['referrer']);
			exit();
		}
		else
		{
			header("location: user_details.php?user_id=$user->id");
			exit();
		}
	}

	$message = implode("<br>",$checkout_result);
}


require_once("includes/header.php");


$reservations = load_reservations(array('id'=>$reservation_id,'status'=>'Scheduled','active'=>'1'));
if(count($reservations) > 1)
{
	// error, more than one reservation with the same reservation id
}
else if(count($reservations) < 1)
{
	// error, reservation not found
}
else
{
	foreach($reservations as $reservation)
		break;

	//pr($reservation);
	
	$room = $reservation->room;
	$user = get_user_by_id($reservation->user_id);
	
	print("<div id='PageTitle'>Checkout Key for Room $room->room_number</div>\n");
	print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Manage Rooms</div>\n");

	print("<br>\n");
	
	display_error($message);

	if(!strcmp($reservation->status,'Checked Out'))
	{
		print("<h3>Room Currently Checked Out</h3>\n");
	}
	else
	{
		print_checkout_form($reservation);
	}
	
	print("<br><br>\n");
	print("<a style='margin-left:30px;' href='reservation_calendar.php'>Back to the Calendar</a> | <a href='user_details.php?search=$user->patron_id'>User Details for $user->first_name $user->last_name</a><br>\n");
}



require_once("includes/footer.php");

?>