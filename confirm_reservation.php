<?php

session_start();
require_once("config/config.inc.php");
require_once("config/strings.inc.php");
require_once("includes/Database.php");
require_once("includes/load_settings.php");
require_once("load.php");
require_once("includes/load_hours_data.php");
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

//print_r($_GET);
//print_r($_SESSION);

$selected_date = $_GET['selected_date'];
$room_id_parts = explode("_",$_GET['room_id']);
$room_id = $room_id_parts[0];
$room_section = $room_id_parts[1];
$room = $all_rooms[$room_id];
$start_time = $_GET['start_time'];
$end_time = $_GET['end_time'];
$key_barcode = $_GET['key_barcode'];
$otf = $_GET['otf'];


require_once("includes/header.php");


print("<div id='PageTitle'>Reservation Confirmation</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Reservation Confirmation</div>\n");

if(isset($_GET['submitted']))
{
	if(!strcmp($_GET['terms'],'on') && !strcmp($_GET['family_friendly_terms'],'on'))
	{
		$res_id = create_reservation($room_id,$room_section,$_SESSION['LibRooms']['UserID'],$selected_date,$start_time,$end_time);
		
		if(is_array($res_id))
		{
			// display error(s)
			print("<br>\n");
			foreach($res_id as $error)
				display_error($error->message,array("room_id"=>$room_id,"room_section"=>$room_section,"user_id"=>$_SESSION['LibRooms']['UserID'],"selected_date"=>$selected_date,"start_time"=>$start_time,"end_time"=>$end_time));
			
			print("<br><br>\n");
			print("<blockquote><a href='reservation_calendar.php'><b>Select a Different Reservation</b></a></blockquote>\n");
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
		if(isset($_GET['submitted']))
		{
			// lookup user
			$matching_users = find_users_by_name_or_patron_id($_GET['requesting_patron']);
			
			if(count($matching_users) == 0)
			{
				// if user doesn't exist, synch user
				//print("user does not exist... trying to create an account for them...");
				$synch_result = synch_user($_GET['requesting_patron']);
				// print_r($synch_result);
				if(is_array($synch_result))
					$error_messages = $synch_result;
				else
				{
					
					$matching_users = find_users_by_name_or_patron_id($_GET['requesting_patron']);
					// print("matching user:...<br>\n");
					// print_r($matching_users);
				}
			}
			
			if(count($matching_users) == 1)
			{
				// if only one user was found, create reservation for user and display reservation confirmation
				foreach($matching_users as $user)
					break;
				
				// synch user to make sure account is fully up-to-date				
				$synch_result = synch_user($_GET['requesting_patron']);
				
				$res_id = create_reservation($room_id,$room_section,$user->id,$selected_date,$start_time,$end_time,$otf);
				
				if(is_array($res_id))
				{
					// display error(s)
					//pr($res_id);
					print("<br>\n");
					foreach($res_id as $error)
						display_error($error->message,array("room_id"=>$room_id,"room_section"=>$room_section,"user_id"=>$user->id,"selected_date"=>$selected_date,"start_time"=>$start_time,"end_time"=>$end_time,"otf"=>$otf));

					print("<br><br>\n");
					print("<blockquote><a href='reservation_calendar.php'><b>Select a Different Reservation</b></a></blockquote>\n");
				}
				else
				{
					$reservations = load_reservation_details($res_id);
					if(count($reservations) > 0)
					{
						foreach($reservations as $reservation)
							break;
					}
					
					// if the key is known, then just checkout the room
					if(!strcmp($otf,'1') && strcmp($key_barcode,''))
					{
						// forward to checkout page
						print("<script>document.location.href = 'checkout.php?reservation_id=$reservation->id&room_id=$reservation->room_id&checkout_key_barcode=$key_barcode&user_id=$reservation->user_id';</script>");
					}
					else
					{
						print("<blockquote>\n");
						print("<br><h2>Reservation has been Confirmed for $user->first_name $user->last_name!</h2>\n");
						print("</blockquote>\n");
						
						print_checkout_form($reservation,urlencode("reservation_details.php?reservation_id=$res_id"));
					}
				}
		
				require_once("includes/footer.php");
				exit();
			}
			else
			{
				display_error("User could not be found.",array("user_id"=>$_GET['requesting_patron']));
			}
		}
	}
	else
	{
		$error_messages['terms'][] = "You must first agree to the terms of use policy";
	}
}

print("<form action='confirm_reservation.php'>\n");

if(!strcmp($user_type,'admin') || !strcmp($user_type,'staff'))
{
	print("<br><blockquote>\n");
	print("<h2>Room: ".ltrim($room->room_number,"0")."</h2>\n");
	print("<h2>Date: " . date("m/d/Y",strtotime($_GET['selected_date'])) . "</h2>\n");
	print("<h2>Start Time: " . date("h:ia",strtotime($_GET['start_time'])) . "</h2>\n");
	if(isset($_GET['end_time']))
	{
		print("<h2>End Time: " . date("h:ia",strtotime($_GET['end_time'])) . "</h2>\n");
		print("<input type='hidden' name='end_time' value='".$_GET['end_time']."'>\n");
	}
	else
	{
		print("[ToDO: display end time options with max available set as default]<br>\n");
	}
	print("<input type='hidden' name='key_barcode' value='$key_barcode'>\n");
	print("<input type='hidden' name='otf' value='$otf'>\n");
	
	print("<br><br><h2>Please enter ".PATRON_ID_LABEL." of Requesting Patron</h2><br>\n");
	print("<input id='requesting_patron' name='requesting_patron' value='".$_GET['requesting_patron']."' />\n");
	
	if(strcmp($_GET['requesting_patron'],''))
	{
		// select one of the matching patrons
		if(count($matching_patrons) == 0)
		{
			print("No users found matching that ".PATRON_ID_LABEL.". Please try again.<br>\n");
		}
		else
		{
			//print_r($matching_patrons);
		}
	}
	
	print("<input type='hidden' name='room_id' value='".$_GET['room_id']."'>\n");
	print("<input type='hidden' name='selected_date' value='".$_GET['selected_date']."'>\n");
	print("<input type='hidden' name='start_time' value='".$_GET['start_time']."'>\n");
	print("<input type='hidden' name='submitted' value='1'>\n");
	
	print("<input type='submit' value='Create Reservation' />\n");
	
	print("</blockquote>\n");
}
else
{
	print("<br><br><h2>Please confirm the following room reservation</h2><br>\n");
	print("<blockquote>\n");
	print("<b>Room $room->room_number (".date('m/d/y',strtotime($selected_date))." ".date('g:ia',strtotime($start_time))."-".date('g:ia',strtotime($end_time)).")</b><br>\n");
	print("<br>\n");

	// TODO: add inline terms of use to settings
	print("<ul>\n");
	print("<li>Observe the Library's <a href='http://library.pdx.edu/rights.html' target='_blank'>User Rights and Responsibilities</a></li>\n");
	print("<li>The person who reserved the room is the only person who may pick up the key from the 1st floor Circulation desk for checkout. Photo ID required. </li>\n");
	print("<li>Keys are the property of PSU Library and must remain in the building. </li>\n");
	print("<li>You are responsible for the key and the condition of the study room and for reporting damage to the Circulation Desk immediately.  </li>\n");
	print("<li>If you stay beyond the time your room is due, another group may enter the study room. Staff may ask you to leave, and you will accrue fines. </li>\n");
	print("</ul>\n");
	
	// TODO: add "terms of use" as a setting (overridden here to always be agreed to)
	display_errors($error_messages['terms'],$_GET);
	print("<input type='hidden' name='terms' value='on'>\n");
	
	// HACK: TODO: -- find a solution for this
	if(!strcmp(substr($_GET['room_id'],0,2),'21'))
	{
		print("<br><input type='checkbox' name='family_friendly_terms'> To use this special study room, I understand that I must have a child with me.<br>\n");
	}
	else
	{
		print("<input type='hidden' name='family_friendly_terms' value='on'>\n");
	}
	
	print("<input type='hidden' name='selected_date' value='".$_GET['selected_date']."'>\n");
	print("<input type='hidden' name='room_id' value='".$_GET['room_id']."'>\n");
	print("<input type='hidden' name='start_time' value='".$_GET['start_time']."'>\n");
	print("<input type='hidden' name='end_time' value='".$_GET['end_time']."'>\n");
	
	print("<br><input name='submitted' type='submit' value='Confirm Reservation'>\n");
	
	print("</blockquote>\n");
}

print("</form>\n");


require_once("includes/footer.php");

?>