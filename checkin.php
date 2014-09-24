<?php

session_start();
require_once("config/config.inc.php");
require_once("config/strings.inc.php");

require_once("includes/Database.php");

require_once("load.php");
require_once("includes/load_hours_data.php");
require_once("includes/load_rooms.php");

require_once("includes/verify_access.php");
restrict_access($db,array("staff","admin"));

$reservation_id = $_GET['reservation_id'];
$room_id = $_GET['room_id'];
if(isset($_GET['user_id']))
	$user = get_user_by_id($user_id);


require_once("includes/header.php");



$reservations = load_reservation_details($reservation_id);
//pr($reservations);
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
	
	print("<div id='PageTitle'>Check-In Key for Room $room->room_number</div>\n");
	print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Check-In</div>\n");

	print("<br>\n");
	
	if(!strcmp($reservation->status,'Checked Out') && isset($_GET['reservation_id']) && strcmp($room->id,'') && isset($_GET['checkin_key_barcode']))
	{
		$result = checkin_key($_GET['reservation_id'],$room->id,$_GET['checkin_key_barcode']);
		
		display_errors($result,array("reservation"=>$reservation));
		
		// reload
		$reservations = load_reservation_details($_GET['reservation_id']);
		foreach($reservations as $reservation)
			break;
			
		$room = $reservation->room;

		if(count($reservation->fines) > 0)
		{
			foreach($reservation->fines as $fine)
			{
				//pr($fine);
			}
		}
	}
	
	print("<div style='font-weight:bold; color:green;'>$message</div><br>\n");

	if(!strcmp($reservation->status,'Checked Out'))
	{
		print_checkin_form($reservation);
	}
	else if(!strcmp($reservation->status,'Completed'))
	{
		// reservation completed, display receipt link and fine info
		print("<blockquote>\n");
		print("<h2>Reservation Complete</h2>\n");
		print("<blockquote>\n");
		print("<b><a href='print_checkin_receipt.php?reservation_id=$reservation_id'>View/Print Check-in Receipt</a> | <a href='reservation_details.php?reservation_id=$reservation_id'>Reservation Details</a></b><br>\n");
		print("</blockquote>\n");
		
		print_fines_table($reservation);
		
		print("</blockquote>\n");
	}
	
	print("<br><br>\n");
	print("<a style='margin-left:30px;' href='reservation_calendar.php'>Back to the Calendar</a> | <a href='user_details.php?user_id=$user->id'>User Details for $user->first_name $user->last_name</a><br>\n");
}



require_once("includes/footer.php");

?>