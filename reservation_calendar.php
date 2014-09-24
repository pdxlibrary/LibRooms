<?php

session_start();


/* WURFL Redirect */
require_once("includes/wurfl_device_detector.php");
if(!strcmp($requestingDevice->getCapability("is_wireless_device"),"true") && strcmp($requestingDevice->getCapability("is_tablet"),"true"))
{
	//redirect to mobile
	header("location:http://library.pdx.edu/studyrooms/m");
}


require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("load.php");
require_once("includes/load_hours.php");
require_once("includes/load_settings.php");

// Authentication
$allowed_roles = array("public");
require_once("includes/verify_access.php");
if(isset($_GET['login']) && !isset($_SESSION['LibRooms']['UserID']))
	restrict_access($db,array("patron"));
else
	restrict_access($db,array("public","patron"));
	
if(isset($_SESSION['LibRooms']['Roles']))
{
	if(in_array('Staff',$_SESSION['LibRooms']['Roles']))
		$user_type = "staff";
	if(in_array('Admin',$_SESSION['LibRooms']['Roles']))
		$user_type = "admin";
}

if(isset($_GET['selected_date']))
{
	$selected_date = $_GET['selected_date'];
	
	// if selected date is earlier than today, just change it to today's date
	if(strtotime($selected_date) < strtotime(date('Y-m-d')))
		$selected_date = date('Y-m-d');
		
	// if selected date greater than the reservation window, just change it to today's date
	if(strtotime($selected_date) >= strtotime("+".MAX_FUTURE_RES_DAYS." days",strtotime(date('Y-m-d',strtotime('now')))))
		$selected_date = date('Y-m-d');
}
else
	$selected_date = date('Y-m-d');

$_GET['selected_date'] = $selected_date;
	
$possible_amenities = array();
if(isset($_GET['amenities']))
	$selected_amenities = $_GET['amenities'];

$room_filter = array();
$room_filter['out_of_order'] = "No";
if(isset($_GET['capacity_filter']) && strcmp($_GET['capacity_filter'],''))
	$room_filter['capacity'] = $_GET['capacity_filter'];
$room_filter['amenities and'] = $_GET['amenity_filter'];
$room_filter['group_by'] = "room_group_id";

$todays_hours = load_hours($db,$selected_date);
$all_rooms = load_rooms(null,$room_filter);
$all_room_groups = load_room_groups();
$all_reservations = load_calendar_reservations();

require_once("includes/header.php");


?>


<div id='PageTitle'>Reserve a Study Room</div>
<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; Reserve a Study Room</div>
<div class='screen_reader_content'><a href='reservation_calendar_ada.php'>Click here to use our screen reader friendly reservation form</a></div>

<table width='100%'>
<tr valign='top'>
	<td width='650' style='font-size:14px;'>
		<br>
		<b>Click an available time slot on the calendar below to reserve a room</b><br><br>
		<?php 
			require_once("includes/calendar_filter_form.php");
		?>
	</td>
	<td style='font-size:14px' align='center'>
		<br>
		<?php
			require_once("includes/available_credits_widget.php");
		?>	
		<br>
		<?php
			require_once("includes/calendar_legend.php");
		?>
	</td>
</tr>
</table>


<form action='confirm_reservation.php'>
<span id='held_reservation'><br></span>
<div id="tooltip" class="tooltip"></div>
<div id="slot_too_short" class="tooltip"></div>
</form>

<?php


if(isset($_GET['reschedule']))
{
	$reservations = load_reservations(array('id'=>$_GET['reschedule']));
	foreach($reservations as $orig_reservation)
		break;
	
	if(strcmp($orig_reservation->status,'Scheduled'))
	{
		display_error("This reservation cannot be rescheduled. Current status: $orig_reservation->status");
		require_once("includes/footer.php");
		exit();
	}
	
	if(strcmp($user_type,'staff') && strcmp($user_type,'admin') && strcmp($orig_reservation->user_id,$_SESSION['LibRooms']['UserID']))
	{
		// user does not have permission to reschedule this reservation
		display_error("You do not have permission to change this reservation.",array("user_id"=>$_SESSION['LibRooms']['UserID'],"reservation"=>$orig_reservation));
		require_once("includes/footer.php");
		exit();
	}
	else
	{
		// display info about reservation rescheduling from
		$orig_room = $all_rooms[$orig_reservation->room_id];
		print("<div id='reschedule_note' align='center'>Rescheduling Reservation: Room $orig_room->room_number - ".date("m/d/Y",strtotime($orig_reservation->date))." (".date("g:ia",strtotime($orig_reservation->sched_start_time))." - ".date("g:ia",strtotime($orig_reservation->sched_end_time)).")</div>\n");
	}
}



print_calendar_date_tabs($todays_hours);

print("<div id='calendar_container'>\n");


if($todays_hours->closed)
{
	// print_calendar_date_tabs($todays_hours);
	print("<br><h3 align='center'>No Rooms Available - Library Closed</h3>\n");
}
else
{

	$timeslots = $todays_hours->slots;
	
	$front_fill_slots = 0;
	$timeslot = $timeslots[0];
	$timeslot_pointer = date("Y-m-d H:00:00",strtotime($timeslot));
	// print("timeslot: $timeslot<br>\n");
	while(strtotime($timeslot_pointer) < strtotime($timeslot))
	{
		$timeslot_pointer = date('Y-m-d H:i:s',strtotime("+".RES_PRECISION." minutes",strtotime($timeslot_pointer)));
		$front_fill_slots++;
	}
	// print("front fill slots: $front_fill_slots<br>\n");
	
	$end_fill_slots = 0;
	$timeslot = $timeslots[(count($timeslots)-1)];
	$timeslot_pointer = date("Y-m-d H:00:00",strtotime("+".RES_PRECISION." minutes",strtotime($timeslot)));
	// print("timeslot: $timeslot<br>\n");
	while(strtotime($timeslot_pointer) < strtotime($timeslot))
	{
		$timeslot_pointer = date('Y-m-d H:i:s',strtotime("+".RES_PRECISION." minutes",strtotime($timeslot_pointer)));
		$end_fill_slots++;
	}
	// print("end fill slots: $end_fill_slots<br>\n");
	
	$total_timeslots = count($timeslots) + $front_fill_slots + $end_fill_slots;

	
	$timeslot_width = @floor((750-($total_timeslots*2))/$total_timeslots); // subtract 1px for each border and divide available space by the number of slots
	
	if(count($all_rooms) == 0)
	{
		// if no rooms can be found
		// print_calendar_date_tabs($todays_hours);
		print("<div style='text-align:center; font-size:14px; font-weight:bold; padding:25px;'>Sorry, no rooms match the features you selected.</div>\n");
	}
	
	foreach($all_room_groups as $room_group_id => $room_group)
	{
		if(isset($all_rooms[$room_group_id]))
		{
			print("<div class='room_group_header'>");
			print("$room_group->name");
			if(strcmp($room_group->description,''))
				print(": $room_group->description");
			print("</div>\n");
			// print_calendar_date_tabs($todays_hours);
			print("<table class='reservation_calendar' cellpadding='0' cellspacing='0'>\n");
			$hour_columns = print_column_labels($todays_hours->slots);
			
			foreach($all_rooms[$room_group_id] as $room_id => $room)
			{
				if(!strcmp($room->out_of_order,'Yes'))
				{
					// room is out-of-order, don't show in list
					continue;
				}
				
				if(isset($all_reservations[str_replace("-","",$todays_hours->date)][$room_id]))
				{
					$room_reservations = $all_reservations[str_replace("-","",$todays_hours->date)][$room_id];
				}
				else
				{
					$room_reservations = array();
					$slot_too_short = false;
					$no_time_to_start = false;
				}
				
				// pr($room_reservations);
				
				print("<tr valign='middle'>");
				// room number
				print("<td style='font-size:10pt; padding:2px; border-right:1px solid #999; height:100%; vertical-align:middle;' rowspan='$room->max_simultaneous_reservations'>");
				print("<b><a href='".WEB_ROOT."room_details.php?room_id=$room_id' style='color:#5C7200'>Room $room->room_number</a></b><br>\n");
				print("($room->capacity chairs)");
				
				//if($room->max_simultaneous_reservations > 1)
				//	print("<br>Simultaneous Users Allowed: $room->max_simultaneous_reservations\n");
				
				print("</td>");
				
				for($m=1;$m<=$room->max_simultaneous_reservations;$m++)
				{
					$reservations_index = 0;
					$front_filled = false;
					$front_fill_slots = 0;
					$end_filled = false;
					
					if(isset($room_reservations[$m]))
						$room_section_reservations = $room_reservations[$m];
					else
						$room_section_reservations = array();

					// pr($room_section_reservations);
						
					for($i=0;$i<count($timeslots);$i++)
					{
						$timeslot = $timeslots[$i];
						$timeslot_id = date('YmdHis',strtotime($timeslot));
						$timeslot_min = date('i',strtotime($timeslot));
						$no_time_to_start = false;
						
						if(!strcmp($timeslot_min,'00'))
							$style = "style='border-left:1px solid #999;'";
						else
							$style = "";			
						
						if(!$front_filled)
						{
							// if day does not start on an even hour then front-fill
							$timeslot_pointer = date("Y-m-d H:00:00",strtotime($timeslot));
							while(strtotime($timeslot_pointer) < strtotime($timeslot))
							{
								$timeslot_id = date('YmdHis',strtotime($timeslot_pointer));
								print("<td id='".$room->id."_$m-$timeslot_id' width='$timeslot_width' class='slot_unavailable' $style>&nbsp;</td>");
								$timeslot_pointer = date('Y-m-d H:i:s',strtotime("+".RES_PRECISION." minutes",strtotime($timeslot_pointer)));
								$front_fill_slots++;
							}
							$front_filled = true;
						}
						
						$reservation_printed = false;
						if(isset($room_section_reservations[$reservations_index]) && strcmp($room_section_reservations[$reservations_index]->id,$_GET['reschedule']))
						{
							if(strtotime($timeslot) >= strtotime($room_section_reservations[$reservations_index]->sched_start_time) && strtotime($timeslot) <= strtotime($room_section_reservations[$reservations_index]->sched_end_time))
							{
								$reservation_slot_width = floor((strtotime($room_section_reservations[$reservations_index]->sched_end_time) - strtotime($room_section_reservations[$reservations_index]->sched_start_time)) / (RES_PRECISION*60));
								
								if(!strcmp($room_section_reservations[$reservations_index]->user_id,$_SESSION['LibRooms']['UserID']) || !strcmp($user_type,'staff') || !strcmp($user_type,'admin'))
								{
									if(strcmp($room_section_reservations[$reservations_index]->checkout_time,'') && !strcmp($room_section_reservations[$reservations_index]->checkin_time,''))
									{
										if(!strcmp($user_type,'staff') || !strcmp($user_type,'admin'))
										{
											$res_actions = "<br><a href='#'>Check-in</a>";
										}
									}
									else if(strtotime($room_section_reservations[$reservations_index]->sched_end_time) > strtotime('now'))
									{
										$res_actions = "<br /><div style='text-align:center'><a href='reservation_details.php?reservation_id=".$room_section_reservations[$reservations_index]->id."'>view</a></div>";
									}
									else
										$res_actions = "";
									
									
									if(!strcmp($room_section_reservations[$reservations_index]->user_id,$_SESSION['LibRooms']['UserID']))
									{
										// logged-in user's reservation
										$class = "my_reservation";
										print("<td colspan='$reservation_slot_width' width='".($timeslot_width*$reservation_slot_width)."' align='center' id='res".$room_section_reservations[$reservations_index]->id."' class='$class' $style>" . $res_actions . "</td>");
									}
									else
									{
										if(!strcmp($room_section_reservations[$reservations_index]->status,'Checked Out'))
										{
											$class = "slot_checked_out";
											print("<td colspan='$reservation_slot_width' width='".($timeslot_width*$reservation_slot_width)."' align='center' id='res".$room_section_reservations[$reservations_index]->id."' class='$class' $style>$res_actions</td>");
										}
										else if(strcmp($room_section_reservations[$reservations_index]->checkout_time,'') && strcmp($room_section_reservations[$reservations_index]->checkin_time,''))
										{
											$class = "slot_completed_reservation";
											print("<td colspan='$reservation_slot_width' align='center' id='res".$room_section_reservations[$reservations_index]->id."' class='$class' $style>$res_actions</td>");
										}
										else
										{
											$class = "slot_reserved";
											if(strtotime($room_section_reservations[$reservations_index]->sched_start_time) - (RESERVATION_CHECKOUT_GRACE_PERIOD * 60) <= strtotime('now'))
												$class .= " checkoutable";
											print("<td colspan='$reservation_slot_width' width='".($timeslot_width*$reservation_slot_width)."' align='center' id='res".$room_section_reservations[$reservations_index]->id."' class='$class' $style>$res_actions</td>");
										}
									}
										

									$reservation_printed = true;
								}
								
								if(!$reservation_printed)
									print("<td colspan='$reservation_slot_width' width='".($timeslot_width*$reservation_slot_width)."' id='".$room->id."_$m-$timeslot_id' class='slot_reserved' $style>&nbsp;</td>");
								
								$i += ($reservation_slot_width-1);
								$reservations_index++;
								
								
								// check for next reservation. if availability to next reservation is less than min_reservation_time set class as slot_too_short
								$slot_too_short = false;
								if(isset($room_section_reservations[$reservations_index]))
								{
									$next_res_start = strtotime($room_section_reservations[$reservations_index]->sched_start_time);
									//$current_time = strtotime("-".RES_PRECISION." minutes",$j);
									//print(date('m/d/y H:ia',$j) . "<br>\n");
									$available_slots = 0;
									$t = $i+1;
									
									while(strtotime($timeslots[$t]) < strtotime($room_section_reservations[$reservations_index]->sched_start_time))
									{	
										$counter1++;
										if($counter1 > 500)
										{
											// breakout due to a reservation calendar bad state
											// send email alert
											
											break;
										}
										$available_slots++;
										$t++;
									}
									//print("available slots: $available_slots<br>\n");
									
									// todo: bug: slot_too_short causing some valid timeslots to be marked as unavailable starting times
									// there is some overlap with no_time_to_start
									// this is especially true if one room is reserved during the last available slots of the day and there is availability for the next room at the current time
									//if($available_slots < (DEFAULT_MIN_RES_LEN / RES_PRECISION))
									//	$slot_too_short = true;
								}
								else
								{
									// if availability to end of day is less than DEFAULT_MIN_RES_LEN set class as slot_too_short
									$available_slots = count($timeslots) - $i - 1;
									//print("available til eod: $available_slots [$i] " . count($timeslots) . "<br>\n");
									if($available_slots < (DEFAULT_MIN_RES_LEN / RES_PRECISION))
									{
										// day ends with too little time left for a minimum reservation
										// TODO: allow for an offset from the end of day where reservations cannot be made (e.g. all reservations must be complete 30 min before closing)
										// todo: bug: slot_too_short causing some valid timeslots to be marked as unavailable starting times
										// there is some overlap with no_time_to_start
										// this is especially true if one room is reserved during the last available slots of the day and there is availability for the next room at the current time
										//$slot_too_short = true;
									}
								}
								
								continue;
							}
							else
							{
								// check to see if there is enough time to begin a reservation before the next reservation begins
								$no_time_to_start = false;
								$next_res_start = strtotime($room_section_reservations[$reservations_index]->sched_start_time);
								$available_slots = 0;
								$t = $i+1;
								$counter2 = 0;
								while(strtotime($timeslots[$t]) <= strtotime($room_section_reservations[$reservations_index]->sched_start_time))
								{
									$counter2++;
									if($counter2 > 500)
									{
										// breakout due to a reservation calendar bad state
										// send email alert
										break;
									}
									
									$available_slots++;
									$t++;
								}
								//print("available slots: $available_slots<br>\n");
								if($available_slots < (DEFAULT_MIN_RES_LEN / RES_PRECISION))
									$no_time_to_start = true;
							}
							
						}
						
						$timeslot_id = date('YmdHis',strtotime($timeslot));
						
						if(strtotime($timeslot)<=strtotime('now') && (strcmp($user_type,'staff') && strcmp($user_type,'admin')))
							$class = "slot_historic";
						else if(strtotime($timeslot)<=strtotime(RES_PRECISION." minutes ago") && (!strcmp($user_type,'staff') || !strcmp($user_type,'admin')))
							$class = "slot_historic";
						else if(!strcmp($room->fcfs,'Yes'))
								$class = "slot_fcfs";
						else if($slot_too_short)
							$class = "slot_too_short";
						else if((count($timeslots) - $i) < (DEFAULT_MIN_RES_LEN / RES_PRECISION))
							$class = "slot_available no_time_to_start";
						else if($no_time_to_start)
							$class = "slot_available no_time_to_start";
						else
							$class = "slot_available";
							
						// add room_id to class list
						$class .= " room".$room->id."_".$m;
						
						// add column id to class list
						$class .= " col$i";
							
						print("<td id='".$room->id."_$m-$timeslot_id-$i' width='$timeslot_width' class='$class' $style>&nbsp;</td>");
					}
				
					// TODO: add back-fill of unavailable slots at the end of the day, if the day ends (including EOD offset) not on an even hour
					$num_timeslots = count($timeslots);
					while($hour_columns*(60/RES_PRECISION) > ($num_timeslots + $front_fill_slots))
					{
						print("<td class='slot_unavailable' width='$timeslot_width' style='border-left:1px solid #999;'>&nbsp;</td>");		
						$num_timeslots++;
					}
					
					print("</tr>\n");
				}
			}
		
			$hour_columns = print_column_labels($todays_hours->slots);
			print("</table><br>\n");
		}
	}

}

print("</div>\n");

require_once("includes/footer.php");

	
	
function print_column_labels($timeslots)
{
	// hours column headers
	$hour_columns = 0;
	$timeslot_width = @floor((750-(count($timeslots)*2))/count($timeslots));
	$column_width = $timeslot_width * (60/RES_PRECISION);

	$calendar_width = 860;
	$first_col_width = $calendar_width - ($timeslot_width * count($timeslots)) - (count($timeslots)*2);

	print("<thead><tr style='background-color:#FFF'><th width='$first_col_width'>&nbsp;</th>");
	// front-filling
	$even_hour_start = date('Y-m-d H:00:00',strtotime($timeslots[0]));
	while(strtotime($even_hour_start) < strtotime($timeslots[0]))
	{
		$timeslots[] = date('Y-m-d H:i:s',strtotime($even_hour_start));
		$even_hour_start = date('YmdHis',strtotime("+".RES_PRECISION." minutes",strtotime($even_hour_start)));
		
	}
	@sort($timeslots);
	
	for($i=0;$i<count($timeslots);$i+=(60/RES_PRECISION))
	{
		$timeslot = $timeslots[$i];
		$timeslot_hour = date('ga',strtotime($timeslot));
		print("<th colspan='".(60/RES_PRECISION)."'  style='border-left:1px solid #999;'>$timeslot_hour</th>");
		$hour_columns++;
	}
	
	print("</tr></thead>\n");
	
	return($hour_columns);
}	
	

function print_calendar_date_tabs($todays_hours)
{
	$get = $_GET;
	print("<div id='calendar_date_tabs' width='880'>\n");
	print("<ul>");
	for($t = strtotime(date("Y-m-d"));$t < strtotime("+".MAX_FUTURE_RES_DAYS." days",strtotime(date('Y-m-d',strtotime('now')))); $t = strtotime("+1 day",$t))
	{
		if(strtotime($_GET['selected_date']) == $t || (!isset($_GET['selected_date']) && $t == strtotime(date("Y-m-d"))))
			print("<li class='calendar_date_tab_selected'>".date("m/d/y (D)",$t)."</li>\n");
		else
		{
			$get['selected_date'] = date("Y-m-d",$t);
			$date_link = "?".http_build_query($get);
			print("<li class='calendar_date_tab'><a href='$date_link'>".date("m/d/y (D)",$t)."</a></li>\n");
		}
	}
	print("</ul>\n");
	print("</div>\n");
}
	
	
?>

