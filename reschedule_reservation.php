<?php

session_start();
require_once("config/config.inc.php");
require_once("config/strings.inc.php");
require_once("includes/Database.php");
require_once("includes/load_settings.php");
require_once("load.php");
require_once("includes/load_rooms.php");

require_once("includes/email_communication.php");
require_once("includes/error_checking.php");

require_once("includes/verify_access.php");
restrict_access($db,array("patron"));

if(isset($_SESSION['LibRooms']['Roles']))
{
	if(in_array('Staff',$_SESSION['LibRooms']['Roles']))
		$user_type = "staff";
	if(in_array('Admin',$_SESSION['LibRooms']['Roles']))
		$user_type = "admin";
}

$selected_date = $_GET['selected_date'];
$room_id_parts = explode("_",$_GET['room_id']);
$room_id = $room_id_parts[0];
$room_section = $room_id_parts[1];
$room = $all_rooms[$room_id];
$start_time = $_GET['start_time'];
$end_time = $_GET['end_time'];
$reschedule = $_GET['reschedule'];


require_once("includes/header.php");

print("<div id='PageTitle'>Reservation Confirmation</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Reservation Confirmation</div>\n");


// load the currently scheduled reservation
if(isset($_GET['reschedule']))
{
	// load previously scheduled reservation info
	$origs = load_reservations(array('id'=>$_GET['reschedule']));
	if(count($origs) > 1)
	{
		// error multiple reservations with the same id
	}
	else if(count($origs) == 0)
	{
		display_error("Reservation could not be found.",array("rescheduled_reservation_id"=>$_GET['reschedule']));
	}
	else
	{
		foreach($origs as $orig)
			break;
		
		
		if(strcmp($orig->status,'Scheduled'))
		{
			display_error("This reservation cannot be rescheduled. Current status: $orig->status",$orig);
			require_once("includes/footer.php");
			exit();
		}
		
		if(!strcmp($user_type,'admin') || !strcmp($user_type,'staff') || !strcmp($orig->user_id,$_SESSION['LibRooms']['UserID']))
		{
			// ok to edit
		}
		else
		{
			display_error("You do not have permission to change this reservation.",array("user_type"=>$user_type,"original_reservation"=>$orig));
			require_once("includes/footer.php");
			exit();
		}
	}
}

if(isset($_GET['submitted']))
{
	if(!strcmp($_GET['terms'],'on'))
	{
		$res_id = reschedule_reservation($reschedule,$room_id,$room_section,$_SESSION['LibRooms']['UserID'],$selected_date,$start_time,$end_time);
		
		if(is_array($res_id))
		{
			// display error(s)
			print("<br>\n");
			foreach($res_id as $error)
				display_error($error->message);
			
			print("<br><br>\n");
			print("<blockquote><a href='reservation_calendar.php?reschedule=$reschedule'><b>Select a Different Reservation</b></a></blockquote>\n");
		}
		else
		{
			print("<blockquote>\n");
			print("<br><h2>Your Reservation has been Confirmed!</h2>\n");
			print("<br>\n");
			print(RESERVATION_CONFIRMED_TEXT);
			print("<br>\n");
			print("<br><a href='my_reservations.php'>View My Reservations</a><br>\n");
			print("</blockquote>\n");
		}
		
		require_once("includes/footer.php");
		exit();
	}
	else if(!strcmp($user_type,'admin') || !strcmp($user_type,'staff'))
	{
		$res_id = reschedule_reservation($reschedule,$room_id,$room_section,$user->id,$selected_date,$start_time,$end_time);
		
		if(is_array($res_id))
		{
			// display error(s)
			print("<br>\n");
			foreach($res_id as $error)
				display_error($error->message);
			print("<br><br>\n");
			print("<blockquote><a href='reservation_calendar.php'><b>Select a Different Reservation</b></a></blockquote>\n");
		}
		else
		{
			print("<blockquote>\n");
			print("<br><h2>Rescheduled reservation has been Confirmed!</h2>\n");
			print("</blockquote>\n");
			
			// if reservation start time is within the early checkout time and the room is available, display form to checkout the room immediately
			$reservations = load_reservation_details($res_id);
			if(count($reservations) > 0)
			{
				foreach($reservations as $reservation)
					break;
				print_checkout_form($reservation,urlencode("reservation_details.php?reservation_id=$res_id"));
			}
		}

		require_once("includes/footer.php");
		exit();
	}
	else
	{
		$error_messages['terms'][] = "You must first agree to the terms of use policy";
	}
}

print("<form action='reschedule_reservation.php'>\n");

print("<br><br><h2>Please confirm the following room reservation change</h2><br>\n");
print("<blockquote>\n");
print("<b>OLD: Room ".$orig->room->room_number." (".date('m/d/y',strtotime($orig->date))." ".date('g:ia',strtotime($orig->sched_start_time))."-".date('g:ia',strtotime($orig->sched_end_time)).")</b><br>\n");
print("<br>\n");
print("<b>NEW: Room $room->room_number (".date('m/d/y',strtotime($selected_date))." ".date('g:ia',strtotime($start_time))."-".date('g:ia',strtotime($end_time)).")</b><br>\n");
print("<br>\n");
//print("Responsible Party: <b>".$_SESSION['LibRooms']['FirstName']." ".$_SESSION['LibRooms']['LastName']." (".$_SESSION['LibRooms']['Email'].")</b><br>\n");

if(strcmp($user_type,'admin') && strcmp($user_type,'staff'))
{
	display_errors($error_messages['terms'],$_GET);
	print("<input type='hidden' name='terms' value='on'>\n");
}
print("<input type='hidden' name='reschedule' value='".$_GET['reschedule']."'>\n");
print("<input type='hidden' name='selected_date' value='".$_GET['selected_date']."'>\n");
print("<input type='hidden' name='room_id' value='".$_GET['room_id']."'>\n");
print("<input type='hidden' name='start_time' value='".$_GET['start_time']."'>\n");
print("<input type='hidden' name='end_time' value='".$_GET['end_time']."'>\n");
print("<br><input name='submitted' type='submit' value='Confirm Rescheduled Reservation'>\n");
print("</blockquote>\n");

print("</form>\n");

require_once("includes/footer.php");

?>