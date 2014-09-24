<?php

session_start();
require_once("config/config.inc.php");
require_once("includes/Database.php");

//require_once("includes/load_hours_data.php");
//require_once("includes/load_rooms.php");

require_once("load.php");
require_once("includes/email_communication.php");
require_once("includes/verify_access.php");
restrict_access($db,array("patron"));


$reservation_id = $_GET['reservation_id'];

require_once("includes/header.php");
print("<div id='PageTitle'>Cancel Reservation</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Cancel Reservation</div>\n");
print("<br>\n");

$reservations = load_reservation_details($reservation_id);
if(count($reservations) > 1)
{
	// TODO: too many matches
}
else if(count($reservations) < 1)
{
	print("<h3>ERROR: Reservation could not be located.</h3>\n");
}
else
{
	foreach($reservations as $reservation)
		break;
	
		
	if(in_array('Staff',$_SESSION['LibRooms']['Roles']) || in_array('Admin',$_SESSION['LibRooms']['Roles']))
		$user_id = $reservation->user_id;
	else
		$user_id = $_SESSION['LibRooms']['UserID'];
	
	if(strcmp($reservation->user_id,$user_id))
	{
		$errors[] = "You do not have permission to cancel this reservation.";
	}
	
	if(count($errors) == 0)
	{
		if(!strcmp($_GET['confirm_cancel'],'1'))
		{
			if(strcmp($_GET['cancellation_reason'],''))
				$cancellation_reason = $_GET['cancellation_reason'];
			else
				$cancellation_reason = "Manually cancelled";
			$result = cancel_reservation($reservation->id,$user_id,$cancellation_reason);
			
			if(is_array($result))
			{
				// TODO: display error_messages array
			}
			else
			{
				print("<blockquote>\n");
				print("<h2>Reservation Cancelled</h2>\n");
				print("<br><a href='reservation_calendar.php'>Schedule a new reservation</a><br>\n");
				print("</blockquote>\n");
			}
		}
		else
		{
			$reservations = load_reservation_details($reservation_id);
			if(count($reservations) > 1)
			{
				// TODO: too many matches
			}
			else if(count($reservations) < 1)
			{
				print("<h3>ERROR: Reservation could not be located.</h3>\n");
			}
			else
			{
				foreach($reservations as $reservation)
					break;
				$room = $reservation->room;
				if(in_array('Staff',$_SESSION['LibRooms']['Roles']) || in_array('Admin',$_SESSION['LibRooms']['Roles']))
					$user_id = $reservation->user_id;
				else
					$user_id = $_SESSION['LibRooms']['UserID'];
					
				print("<br><h3><b>Scheduled Reservation:</b>\n");
				print("<blockquote>\n");
				print("Room $room->room_number - \n");
				$start = date('m/d/Y g:ia',strtotime($reservation->sched_start_time));
				$end = date('g:ia',strtotime($reservation->sched_end_time));
				print("$start-$end</h3>\n");
				if(!strcmp($reservation->key_checkout_time,''))
				{
					print("<form action='cancel_reservation.php'>\n");
					print("<input type='hidden' name='reservation_id' value='".$_GET['reservation_id']."'>\n");
					if(in_array('Staff',$_SESSION['LibRooms']['Roles']) || in_array('Admin',$_SESSION['LibRooms']['Roles']))
						print("Reason for Cancellation:<br><textarea name='cancellation_reason' rows='3' cols='50'></textarea><br>\n");
					print("<input type='hidden' name='confirm_cancel' value='1' type='submit'>\n");
					print("<input type='submit' value='Cancel Reservation' type='submit'>\n");
					print("</form>\n");
				}
				else
					print("<b>This room is currently checked out to you.<b><br>\n");
				
				print("</blockquote>\n");
				print("<br>\n");
			}
		}
	}
	else
	{
		display_errors($errors);
	}
}



require_once("includes/footer.php");

?>