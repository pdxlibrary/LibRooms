<?php

require_once("config/config.inc.php");
require_once("config/strings.inc.php");
require_once("includes/Database.php");
require_once("includes/load_settings.php");
require_once("includes/load_hours.php");
require_once("includes/email_communication.php");


/* Load Reservations */
function load_reservations($options=array())
{
	global $db;
	
	$reservations = array();
	
	if(isset($options['group_by']))
	{
		if(!strcmp($options['group_by'],'status'))
		{
			$reservations["Scheduled"] = array();
			$reservations["Checked Out"] = array();
			$reservations["Completed"] = array();
			$reservations["Cancelled"] = array();
		}
	}
	
	$where = "WHERE 1";
	
	if(isset($options['id']))
		$where .= " AND `id` like '".$options['id']."'";
	
	if(isset($options['user_id']))
		$where .= " AND `user_id` like '".$options['user_id']."'";
	
	if(isset($options['status']))
	{
		if(is_array($options['status']))
		{
			$where .= " AND (`status` like '";
			$where .= implode("' OR `status` like '",$options['status']);
			$where .= "')";
		}
		else
			$where .= " AND `status` like '".$options['status']."'";
	}
	
	if(isset($options['overdue']) && !strcmp($options['overdue'],'1'))
	{
		$where .= " AND `status` like 'Checked Out' AND `sched_end_time` < '".date('Y-m-d H:i:s')."'";
	}
		
	if(isset($options['date']))
		$where .= " AND `date` like '".$options['date']."'";
	
	if(isset($options['sched_start_time']))
		$where .= " AND `sched_start_time` >= '".$options['sched_start_time']."'";
	
	if(isset($options['sched_end_time']))
		$where .= " AND `sched_end_time` < '".$options['sched_end_time']."'";
		
	if(isset($options['sched_end_time gte']))
		$where .= " AND `sched_end_time` >= '".$options['sched_end_time gte']."'";
	
		
	if(isset($options['room_id']))
		$where .= " AND `room_id` like '".$options['room_id']."'";
		
	if(isset($options['room_section']))
		$where .= " AND `room_section` like '".$options['room_section']."'";
		
	if(isset($options['active']))
		$where .= " AND `active` like '".$options['active']."'";
	else
		$where .= " AND `active` like '1'";
	
	
	if(isset($options['sort asc']))
		$sort = "ORDER BY ".$options['sort asc'];
	else if(isset($options['sort desc']))
		$sort = "ORDER BY ".$options['sort desc']." DESC";
	else
		$sort = "ORDER BY sched_start_time";
		
	if(isset($options['limit']))
		$limit = "LIMIT ".$options['limit'];
	else
		$limit = "";
		
	$select = "select * from reservations $where $sort $limit";
	//print("select: $select<br>\n");
	$res = $db->query($select);
	while($res->fetchInto($reservation))
	{
		$select = "SELECT * FROM study_rooms WHERE id like '$reservation->room_id'";
		//print("select: $select<br>\n");
		$res_room = $db->query($select);
		while($res_room->fetchInto($room))
			$reservation->room = $room;
			
		if(isset($options['group_by']))
		{
			if(!strcmp($options['group_by'],'status'))
			{
				if(!strcmp($reservation->status,'Cancelled'))
					$status = "Completed";
				else if(strcmp($reservation->status,''))
					$status = $reservation->status;
				else
					$status = "Unknown";
				$reservations[$status][] = $reservation;
			}
		}
		else
			$reservations[] = $reservation;
	}
	return($reservations);
}

function load_reservations_for_user($user_id,$options=array())
{
	global $db;
	
	$reservations = array();
	
	$where = "WHERE user_id like '$user_id'";
	
	if(isset($options['id']))
		$where .= " AND `id` like '".$options['id']."'";
	
	if(isset($options['user_id']))
		$where .= " AND `user_id` like '".$options['user_id']."'";
	
	if(isset($options['status']))
		$where .= " AND `status` like '".$options['status']."'";
		
	if(isset($options['active']))
		$where .= " AND `active` like '".$options['active']."'";
	
	
	if(isset($options['sort asc']))
		$sort = "ORDER BY ".$options['sort asc'];
	else if(isset($options['sort desc']))
		$sort = "ORDER BY ".$options['sort desc'];
	else
		$sort = "ORDER BY sched_start_time";
		
	$select = "select * from reservations $where $sort";
	//print("select: $select<br>\n");
	$res = $db->query($select);
	while($res->fetchInto($reservation))
	{
		$select = "SELECT * FROM study_rooms WHERE id like '$reservation->room_id'";
		// print("select: $select<br>\n");
		$room_res = $db->query($select);
		while($room_res->fetchInto($room))
			$reservation->room = $room;
		if(!strcmp($reservation->status,'Cancelled'))
			$status = "Completed";
		else
			$status = $reservation->status;
		$reservations[$status][] = $reservation;
	}
	
	return($reservations);
}

function load_reservation_details($id)
{
	global $db;
	
	$reservations = array();
	$select = "select * from reservations WHERE id like '$id'";
	//print("select: $select<br>\n");
	$res = $db->query($select);
	while($res->fetchInto($reservation))
	{
		// get room info
		$select = "SELECT * FROM study_rooms WHERE id like '$reservation->room_id'";
		// print("select: $select<br>\n");
		$room_res = $db->query($select);
		while($room_res->fetchInto($room))
			$reservation->room = $room;
		if(!strcmp($reservation->status,'Cancelled'))
			$status = "Completed";
		else
			$status = $reservation->status;
			
		// get status history
		$select = "SELECT * FROM status_history where reservation_id like '$id' and active like '1' order by date";
		// print("select: $select<br>\n");
		$status_res = $db->query($select);
		$reservation->status_history = array();
		while($status_res->fetchInto($status))
			$reservation->status_history[] = $status;
			
				
		// get fines
		$select = "SELECT * FROM fines where reservation_id like '$id' and active like '1' order by date_added";
		// print("select: $select<br>\n");
		$fines_res = $db->query($select);
		$reservation->fines = array();
		while($fines_res->fetchInto($fine))
		{
			$select = "SELECT * FROM fines_reductions where fine_id like '$fine->id' and active like '1' order by date_added";
			// print("select: $select<br>\n");
			$fines_reductions_res = $db->query($select);
			$fine->reductions = array();
			while($fines_reductions_res->fetchInto($fine_reduction))
			{
				$fine->reductions[] = $fine_reduction;
			}
			$reservation->fines[] = $fine;
		}
		$reservations[] = $reservation;
	}
	
	return($reservations);
}

function load_reservations_with_fines($options)
{
	global $db;
	
	$reservations = array();
	$where = "WHERE 1";
	
	if(isset($options['sched_start_time']))
		$where .= " AND `sched_start_time` >= '".$options['sched_start_time']."'";
	
	if(isset($options['sched_end_time']))
		$where .= " AND `sched_end_time` < '".$options['sched_end_time']."'";
		
	if(isset($options['active']))
		$where .= " AND `active` like '".$options['active']."'";
	
	if(isset($options['sort asc']))
		$sort = "ORDER BY ".$options['sort asc'];
	else if(isset($options['sort desc']))
		$sort = "ORDER BY ".$options['sort desc'];
	else
		$sort = "ORDER BY sched_start_time";
		
	$select = "select * from reservations $where $sort";
	// print("select: $select<br>\n");
	$res = $db->query($select);
	while($res->fetchInto($reservation))
	{
		// get fines
		$select = "SELECT * FROM fines where reservation_id like '$reservation->id' and active like '1' order by date_added";
		// print("select: $select<br>\n");
		$fines_res = $db->query($select);
		$reservation->fines = array();
		if($fines_res->numRows() == 0)
			continue;
		while($fines_res->fetchInto($fine))
		{
			$select = "SELECT * FROM fines_reductions where fine_id like '$fine->id' and active like '1' order by date_added";
			// print("select: $select<br>\n");
			$fines_reductions_res = $db->query($select);
			$fine->reductions = array();
			while($fines_reductions_res->fetchInto($fine_reduction))
			{
				$fine->reductions[] = $fine_reduction;
			}
			$reservation->fines[] = $fine;
		}
		
		$select = "SELECT * FROM study_rooms WHERE id like '$reservation->room_id'";
		//print("select: $select<br>\n");
		$res_room = $db->query($select);
		while($res_room->fetchInto($room))
			$reservation->room = $room;
			
		$reservations[] = $reservation;
	}

	return($reservations);
}

function load_calendar_reservations()
{
	global $db;
	
	$now = date('Y-m-d H:i:s',strtotime('now'));
	$select = "select * from reservations where sched_end_time > '$now' and cancelled like '0' and status not like 'Cancelled' and status not like 'Completed' and active like '1' order by room_id,date,sched_start_time";
	//print("select: $select<br>\n");
	$res = $db->query($select);
	$all_reservations = array();
	while($res->fetchInto($reservation))
	{
		$all_reservations[date('Ymd',strtotime($reservation->date))][$reservation->room_id][$reservation->room_section][] = $reservation;
	}
	//print_r($all_reservations);
	return($all_reservations);
}

function get_reservation_for_checked_out_key($key_barcode)
{
	global $db;
	$reservations = array();
	$select_reservation = "select * from reservations where key_barcode like '$key_barcode' and status like 'Checked Out' and active like '1'";
	$res_reservation = $db->query($select_reservation);
	while($res_reservation->fetchInto($reservation))
	{
		$reservations[] = $reservation;
	}
	return($reservations);
}


/* Reservation Actions */
function create_reservation($room_id,$room_section,$user_id,$date,$start_time,$end_time,$otf=null,$mobile=0)
{
	global $db;
	
	$time_conflicts = get_time_conflicts($room_id,$room_section,$user_id,$date,$start_time,$end_time,$otf);
	if(count($time_conflicts) > 0)
	{
		return($time_conflicts);
	}
	
	$limit_conflicts = get_limit_conflicts($user_id,$date,$start_time,$end_time);
	if(count($limit_conflicts) > 0)
	{
		return($limit_conflicts);
	}

	$ptype_conflicts = get_ptype_conflicts($user_id);
	if(count($ptype_conflicts) > 0)
	{
		return($ptype_conflicts);
	}
	
	$expiration_conflicts = get_expiration_conflicts($user_id);
	if(count($expiration_conflicts) > 0)
	{
		return($expiration_conflicts);
	}
	
	// submit reservation
	$date_added = date('Y-m-d H:i:s',strtotime('now'));
	$fields = array('room_id','room_section','user_id','date','sched_start_time','sched_end_time','status','active');
	$values = array($room_id,$room_section,$user_id,date('Y-m-d',strtotime($date)),date('Y-m-d H:i:s',strtotime($start_time)),date('Y-m-d H:i:s',strtotime($end_time)),'Scheduled','1');

	if($db->noDuplicate('reservations',$fields,$values))
	{
		// insert reservation
		$fields[] = "mobile";
		$values[] = $mobile;
		$fields[] = "date_added";
		$values[] = $date_added;
		$reservation_id = $db->insert('reservations',$fields,$values);
		
		update_reservation_status($reservation_id,"Scheduled");
		
		// if reservation is not an on-the-fly checkout and starts in the future, send confirmation email to patron
		if(strtotime($start_time) > strtotime('now') && $otf != 1)
			$email_res = reservation_confirmation_email($reservation_id);
	}
	else
	{
		// already submitted, do nothing
		// TODO: lookup reservation id
	}
	
	return($reservation_id);
}

function reschedule_reservation($original_reservation_id,$room_id,$room_section,$user_id,$date,$start_time,$end_time,$otf=null)
{
	global $db;
	
	$time_conflicts = get_time_conflicts($room_id,$room_section,$user_id,$date,$start_time,$end_time,$otf,$original_reservation_id);
	if(count($time_conflicts) > 0)
	{
		return($time_conflicts);
	}
	
	$limit_conflicts = get_limit_conflicts($user_id,$date,$start_time,$end_time,$original_reservation_id);
	if(count($limit_conflicts) > 0)
	{
		return($limit_conflicts);
	}
	
	$ptype_conflicts = get_ptype_conflicts($user_id);
	if(count($ptype_conflicts) > 0)
	{
		return($ptype_conflicts);
	}
	
	$expiration_conflicts = get_expiration_conflicts($user_id);
	if(count($expiration_conflicts) > 0)
	{
		return($expiration_conflicts);
	}
	
	// change reservation
	$fields = array('room_id','room_section','date','sched_start_time','sched_end_time');
	$values = array($room_id,$room_section,date('Y-m-d',strtotime($date)),date('Y-m-d H:i:s',strtotime($start_time)),date('Y-m-d H:i:s',strtotime($end_time)));
	$db->update("reservations",$fields,$values,"id like '$original_reservation_id'",$original_reservation_id,"Reservation Reschedule");

	// send confirmation email to patron
	//$email_res = reservation_confirmation_email($original_reservation_id);

	return($reservation_id);
}

function cancel_reservation($reservation_id,$user_id,$reason)
{
	global $db;
	
	// TODO: check to see if reservation has already been cancelled
	
	// TODO: double-check to see if reservation can be cancelled (not checked-out, in the future, etc...)
	
	$db->update("reservations",array("cancelled","cancellation_reason"),array("1",$reason),"id like '$reservation_id' and user_id like '$user_id'",$reservation_id,"Cancelled reservation");
	update_reservation_status($reservation_id,"Cancelled");
	// TODO: check for update errors
	
	// send confirmation email to patron
	$email_res = reservation_cancellation_email($db,$reservation_id);
	
	// TODO: check for email sending errors
	
	// TODO: if errors are found, report them
	
	return(true);
}

function checkout_key($reservation_id,$room_id,$checkout_key_barcode)
{
	global $db;
	
	// print("attempting to checkout reservation...<br>\n");
	// check to make sure key matches room
	$select_key = "select room_id,status from rooms_keys where key_barcode like '$checkout_key_barcode' and active like '1'";
	//print("key: $select_key<br>\n");
	$res_key = $db->query($select_key);
	if($res_key->numRows() > 0)
	{
		$res_key->fetchInto($key);
		if($key->room_id != $room_id)
		{
			$error_messages[] = "Key Barcode ($checkout_key_barcode) is not associated with this room";
		}
		
		if(strcmp($key->status,'Available'))
		{
			$error_messages[] = "Key Barcode ($checkout_key_barcode) is not available for checkout. Current status: $key->status";
		}
	}
	else
	{
		$error_messages[] = "The Key Barcode ($checkout_key_barcode) cannot be found.";
	}
	
	if(count($error_messages)==0)
	{
		$select_dup = "select * from reservations where id like '$reservation_id' and key_checkout_time is NULL and active like '1'";
		//print("dup: $select_dup<br>\n");
		$res_dup = $db->query($select_dup);
		if($res_dup->numRows() == 1)
		{
			// check out key to patron
			//print("checking out key...<br>\n");
			$fields = array('key_barcode','key_checkout_time','key_checkout_by');
			$values = array($checkout_key_barcode,date('Y-m-d H:i:s',strtotime('now')),$_SESSION['LibRooms']['UserID']);
			$result = $db->update('reservations',$fields,$values,"id like '$reservation_id'",$reservation_id,"Checked Out");
			// TODO: if error with update, add to error messages

			// update reservation status
			update_reservation_status($reservation_id,'Checked Out');
			
			// update key status
			update_key_status($checkout_key_barcode,'Checked Out');
		}
	}
	return($error_messages);
}

function checkin_key($reservation_id,$room_id,$checkin_key_barcode)
{
	global $db;
	
	$error_messages = array();
	
	// check to make sure key matches room
	$select_key = "select room_id from rooms_keys where key_barcode like '$checkin_key_barcode' and active like '1'";
	$res_key = $db->query($select_key);
	if($res_key->numRows() > 0)
	{
		$res_key->fetchInto($key);
		if($key->room_id != $room_id)
		{
			$error_messages[] = "Key Barcode ($checkin_key_barcode) is not associated with this room.";
		}
	}
	else
	{
		$error_messages[] = "The Key Barcode ($checkin_key_barcode) cannot be found.";
	}
	
	// check to make sure key matches reservation
	$select_key = "select room_id from reservations where id like '$reservation_id' and key_barcode like '$checkin_key_barcode' and active like '1'";
	$res_key = $db->query($select_key);
	if($res_key->numRows() == 0)
	{
		$error_messages[] = "Key Barcode ($checkin_key_barcode) is not associated with this reservation.";
	}
	
	if(count($error_messages)==0)
	{
		// check in key
		$fields = array('key_checkin_time','key_checkin_by');
		$values = array(date('Y-m-d H:i:s',strtotime('now')),$_SESSION['LibRooms']['UserID']);
		$result = $db->update('reservations',$fields,$values,"id like '$reservation_id'",$reservation_id,"Key checked-in");
		// TODO: if error with update, add to error messages
		
		if(count($error_messages)==0)
		{
			// update reservation status
			update_reservation_status($reservation_id,'Completed');
			
			// update key status
			update_key_status($checkin_key_barcode,'Available');
			
			// assign fines
			$reservations = load_reservation_details($reservation_id);
			foreach($reservations as $reservation)
				break;
			
			if(strtotime($reservation->sched_end_time)+(FINE_GRACE_PERIOD*60) < strtotime($reservation->key_checkin_time))
			{
				// key returned after the grace period, assess fine
				$num_hours_overdue = ceil((strtotime($reservation->key_checkin_time)-strtotime($reservation->sched_end_time))/3600);
				$fine_amount = $num_hours_overdue*FINE_PER_HOUR_OVERDUE;
				
				// if the fine exceeds the max fine, then just limit to the max fine
				if($fine_amount > MAX_HOURLY_FINE)
					$fine_amount = MAX_HOURLY_FINE;
					
				assign_fine($reservation_id,$fine_amount,"Room key returned late");
			}
		}
	}
	return($error_messages);
}

function update_key_status($key_barcode,$new_status)
{
	global $db;
	$res = $db->update("rooms_keys",array('status'),array($new_status),"key_barcode like '$key_barcode'",$key_barcode,"Key status updated");
	return($res);
}

function assign_fine($reservation_id,$amount,$description)
{
	global $db;
	
	// TODO: check for duplicate entry
	$fields = array('reservation_id','amount','description','resolved','date_added');
	$values = array($reservation_id,$amount,$description,"0",date("Y-m-d H:i:s"));
	$db->insert("fines",$fields,$values);
}

function update_reservation_status($reservation_id,$new_status)
{
	global $db;
	
	$res = $db->update("reservations",array('status'),array($new_status),"id like '$reservation_id'",$reservation_id,"Reservation status updated");
	$fields = array('reservation_id','status','changed_by');
	$values = array($reservation_id,$new_status,$_SESSION['LibRooms']['UserID']);
	if(strcmp($_SESSION['LibRooms']['UserID'],''))
		$sh_res = $db->insert("status_history",$fields,$values);
		
	return($res);
}





/* Room Functions */

function get_available_rooms($date,$start_time,$end_time,$room_filter,$user_id=null,$otf=0,$reschedule_reservation_id=null)
{
	global $db;
	
	$matches = array();
	
	$all_rooms = load_rooms(null,$room_filter);
	$all_room_groups = load_room_groups();
	$all_reservations = load_calendar_reservations();
	
	foreach($all_rooms as $room_group_id => $group_rooms)
	{
		foreach($group_rooms as $room_id => $room)
		{
			for($room_section_id=1;$room_section_id<=$room->max_simultaneous_reservations;$room_section_id++)
			{
				//print("<h3>room ID: $room_id</h3>\n");
				//print("time_conflicts($room_id,$room_section_id,$user_id,$date,$start_time,$end_time,$otf,$reschedule_reservation_id)<br>\n");
				$time_conflicts = get_time_conflicts($room_id,$room_section_id,$user_id,$date,$start_time,$end_time,$otf,$reschedule_reservation_id);
				$limit_conflicts = get_limit_conflicts($user_id,$date,$start_time,$end_time,$reschedule_reservation_id);
				if(count($time_conflicts) + count($limit_conflicts) ==0)
					$matches[] = $room;
			}
		}
	}
	return($matches);
}


// TODO: just use one options array for potential sort and limit data
function load_rooms($room_ids=null,$options=array())
{
	global $db;
	
	$room_limiter = "";
	if($room_ids)
	{
		if(is_array($room_ids))
		{
			foreach($room_ids as $room_id)
			{
				$room_limiter .= " AND id LIKE '$room_id'";
			}
		}
		else
		{
			$room_limiter .= "AND id like '$room_ids'";
		}
	}
	
	if(isset($options['id']))
	{
		$room_limiter .= "AND id like '".$options['id']."'";
	}
	
	if(isset($options['room_number']))
	{
		$room_number_limiter .= "AND room_number like '".$options['room_number']."'";
	}
	
	if(isset($options['out_of_order']))
	{
		$room_number_limiter .= "AND out_of_order like '".$options['out_of_order']."'";
	}
	
	if(isset($options['capacity']))
	{
		// separate capacity range into low-high thresholds
		$capacity_parts = explode("-",$options['capacity']);
		$capacity_low = $capacity_parts[0];
		$capacity_high = $capacity_parts[1];
		$room_number_limiter .= "AND capacity >= '$capacity_low' AND capacity <= '$capacity_high'";
	}
	
	if(isset($options['capacity gte']))
	{
		$room_number_limiter .= "AND capacity >= '".$options['capacity gte']."'";
	}
	
	
	if(isset($options['sort asc']))
		$sort = "ORDER BY ".$options['sort asc'];
	else if(isset($options['sort desc']))
		$sort = "ORDER BY ".$options['sort desc'];
	else
		$sort = "ORDER BY capacity,room_number";
		
	if(isset($options['limit']))
		$limit = "LIMIT ".$limit;
	else
		$limit = "";
	
	$select = "SELECT * FROM study_rooms WHERE 1 $room_limiter $room_number_limiter AND active like '1' $sort $limit";
	//print("select: $select<br>\n");
	$res = $db->query($select);
	$all_rooms = array();
	while($res->fetchInto($room))
	{
		$room->amenities = array();
		$room->keys = array();
		$room->room_number = ltrim($room->room_number,'0');
		
		$all_rooms[$room->id] = $room;
	}
	
	$room_limiter = str_replace("AND id","AND room_id",$room_limiter);
	
	$all_amenities = load_amenities();
	
	// load amenity associations
	$select = "SELECT * FROM study_rooms_amenities WHERE 1 $room_limiter AND active like '1' ORDER BY ordering,id";
	//print("select: $select<br>\n");
	$res = $db->query($select);
	while($res->fetchInto($room_amenity))
	{
		if(isset($all_rooms[$room_amenity->room_id]))
		{
			if(isset($all_amenities[$room_amenity->amenity_id]))
			{
				$amenity = $all_amenities[$room_amenity->amenity_id];
				$room_amenity->name = $amenity->name;
				$room_amenity->description = $amenity->description;
				$room_amenity->search_filter = $amenity->search_filter;
				$all_rooms[$room_amenity->room_id]->amenities[] = $room_amenity;
			}
		}
	}
	
	
	if(isset($options['amenities and']))
	{
		// remove rooms that don't match all amenity filters
		foreach($all_rooms as $room_id => $room)
		{
			foreach($options['amenities and'] as $amenity)
			{
				$a_ok = false;
				foreach($room->amenities as $ra)
				{
					if($ra->amenity_id == $amenity)
					{
						$a_ok = true;
						break;
					}
				}
				
				if($a_ok)
					continue;
				else
				{
					// amenity was not found for this room, remove room from results
					unset($all_rooms[$room_id]);
				}
			}
		}
	}
	
	
	// load images
	$select = "select * from room_images where 1 $room_limiter AND active like '1' order by ordering,id";
	$res = $db->query($select);
	while($res->fetchInto($room_image))
	{
		if(isset($all_rooms[$room_image->room_id]))
		{
			$image_location = "images/rooms/".$room_image->room_id."/".$room_image->type.$room_image->room_id."_".$room_image->id;
			switch($room_image->format)
			{
				case 'image/jpeg': $extension = "jpg"; break;
				case 'image/png': $extension = "png"; break;
				case 'image/gif': $extension = "gif"; break;
				default: $extension = str_replace("images/","",$room_image->format);
			}
			$image_location .= ".".$extension;
			$room_image->location = $image_location;
			$all_rooms[$room_image->room_id]->images[$room_image->type][] = $room_image;
		}
	}
	
	// load keys
	$select = "select * from rooms_keys where 1 $room_limiter AND active like '1'";
	$res = $db->query($select);
	while($res->fetchInto($key))
	{
		if(isset($all_rooms[$key->room_id]))
		{
			$all_rooms[$key->room_id]->keys[] = $key;
		}
	}

	$return = array();
	if(isset($options['group_by']))
	{
		$group_by = $options['group_by'];
		foreach($all_rooms as $room_id => $room)
		{
			$return[$room->$group_by][$room->id] = $room;
		}
	}
	else
	{
		$return = $all_rooms;
	}
	
	return($return);
}


function load_room_capacity_options()
{
	global $db;
	
	$select = "SELECT distinct capacity FROM study_rooms WHERE out_of_order like 'No' and active like '1' order by capacity";
	//print("select: $select<br>\n");
	$res = $db->query($select);
	$all_capacities = array();
	while($res->fetchInto($room))
	{
		$all_capacities[] = $room->capacity;
	}
	//pr($all_capacities);
	return($all_capacities);
}

/* Deprecated - ToDo:this function should been replaced by get_room_current_status in the application (still used by search.php) */
function get_room_current_availability($room_id)
{
	$rooms = load_rooms(null,array('id'=>$room_id));

	foreach($rooms as $room)
		break;
	
	$room->max_gap->start_time = 0;
	$room->max_gap->end_time = 0;
	
	for($section=1;$section<=$room->max_simultaneous_reservations;$section++)
	{
		// check for current/upcoming reservations for this room
		$upcoming_reservations = load_reservations(array('room_id'=>$room_id,'room_section'=>$section,'sched_end_time gte'=>date("Y-m-d H:i:s"),'status'=>array("Scheduled","Checked Out"),'active'=>'1','limit'=>'1'));
		unset($next_reservation);
		foreach($upcoming_reservations as $next_reservation)
			break;
		
		// print("next res:\n");
		// pr($next_reservation);
		
		// check to see if any of the keys are checked out for this room
		foreach($room->keys as $key)
		{
			if(!strcmp($key->status,'Checked Out'))
			{
				// room section is checked out
				
				// load reservation
				$current_checkout = load_reservations(array('room_id'=>$room_id,'room_section'=>$section,'status'=>'Checked Out','key_barcode'=>$key->key_barcode,'active'=>'1','limit'=>'1'));

				if(strtotime($current_checkout->sched_end_time) >= strtotime('now'))
				{
					// checkout is not overdue, so it is not available for others
					continue;
				}
				break;
			}
		}
		
		if(strcmp($next_reservation->sched_start_time,'') && strtotime($next_reservation->sched_start_time) < strtotime('now'))
		{
			// there is a reservation scheduled for now
			continue;
		}
		
		// room section has some availability right now
		$current_minute = date('i');
		if($current_minute < 30)
			$current_minute = "00";
		else
			$current_minute = "30";
		$start_time = date("Y-m-d H:".$current_minute.":00");
		
		// TODO: HACK: change on the fly reservations to start at the next unit of precision, rather than the previous
		$start_time = date("Y-m-d H:i:s",strtotime("+".RES_PRECISION." minutes",strtotime($start_time)));

		if(strcmp($next_reservation->sched_start_time,''))
		{
			if(strtotime($start_time) + (DEFAULT_MAX_RES_LEN)*60 > strtotime($next_reservation->sched_start_time))
			{
				$end_time = $next_reservation->sched_start_time;
			}
			else
			{
				$end_time = date("Y-m-d H:i:s",strtotime("+ ".DEFAULT_MAX_RES_LEN." minutes",strtotime($start_time)));
			}
		}
		else
		{
			$end_time = date("Y-m-d H:i:s",strtotime("+ ".DEFAULT_MAX_RES_LEN." minutes",strtotime($start_time)));
		}
		
		// if this is the biggest gap, update max gap
		if(strtotime($end_time) - strtotime($start_time) > strtotime($room->max_gap->end_time) - strtotime($room->max_gap->start_time))
		{
			$room->max_gap->room_section = $section;
			$room->max_gap->start_time = $start_time;
			$room->max_gap->end_time = $end_time;
		}
	}

	return($room);
}

function roomHasAmenity($room_amenities,$amenity_id)
{
	//print_r($room_amenities);
	foreach($room_amenities as $amenity)
	{
		// print("comparing $amenity->amenity_id and $amenity_id<br>\n");
		if($amenity->amenity_id == $amenity_id)
			return(true);
	}
	return(false);
}

// TODO: possibly use this function to replace: function get_room_current_availability($room_id)
function get_room_current_status($room_id,$section_id=1)
{
	global $db;
	
	// print("id: $room_id<br>\n");
	
	// load the current status of a room section
	if(!strcmp($room->out_of_order,"Yes"))
	{
		$current_status = "<s5>Out of order</s5>";
	}
	else
	{
		$now = strtotime('now');
		$when_available = $now;
		
		// check to see if the room is currently checked out and/or overdue
		$select_checkout_for_room = "select * from reservations where room_id like '$room_id' and room_section like '$section_id' and status like 'Checked Out' and active like '1'";
		$res_checkout_for_room = $db->query($select_checkout_for_room);
		if($res_checkout_for_room->numRows() == 1)
		{
			$res_checkout_for_room->fetchInto($checkout_for_room);
			//print("Checkout: <br>\n");
			//pr($checkout_for_room);
			if(strtotime($checkout_for_room->sched_end_time) < strtotime('now'))
			{
				$current_status = "<s2>Overdue</s2>";
			}
			else
			{
				$current_status = "<s4>Checked Out</s4>";
				$when_available = strtotime($checkout_for_room->sched_end_time);
			}
		}
		else if($res_checkout_for_room->numRows() == 0)
		{
			// room not checked out
			$current_status = "<s1>Available</s1>";
		}
		else
		{
			$current_status = "<s6>Error: Multiple checkouts</s6>";
		}
		
		$todays_hours = load_hours($db,date("Y-m-d"));
		
		// locate scheduled reservations
		$select_next_reservations_for_room = "select * from reservations where room_id like '$room_id' and room_section like '$section_id' and (status like 'Scheduled') and sched_start_time < '$todays_hours->close_time' and active like '1' order by sched_start_time";
		$res_next_reservations_for_room = $db->query($select_next_reservations_for_room);
		$available_until = -1;
		
		while($res_next_reservations_for_room->fetchInto($next_reservation_for_room))
		{
			//if(strtotime("+".DEFAULT_MIN_RES_LEN." minutes",$when_available) <= strtotime($next_reservation_for_room->sched_start_time))
			if($when_available < strtotime($next_reservation_for_room->sched_start_time))
			{
				// there is a gap for a reservation to start at this time
				$available_until = strtotime($next_reservation_for_room->sched_start_time);
				
				if(strtotime("+".DEFAULT_MAX_RES_LEN." minutes",$when_available) < $available_until)
				{
					// reduce to max res len
					$available_until = strtotime("+".DEFAULT_MAX_RES_LEN." minutes",$when_available);
				}
				
				break;
			}
			else
			{
				// no gap big enough, try the next reservation
				$when_available = strtotime($next_reservation_for_room->sched_end_time);
			}
		}
		if($available_until == -1)
			$available_until = strtotime("+".DEFAULT_MAX_RES_LEN." minutes",$when_available);
			
		//print("avail until: ".date("H:i:s",$available_until)."<br>\n");
		
		$room->when_available_datestamp = date("Y-m-d H:i:s",$when_available);
		
		// convert when_available to a readable format
		if($when_available == $now)
			$room->when_available = "Now";
		else
			$room->when_available = date("g:ia",$when_available);
		$minutes_until_available = ceil(($when_available - strtotime('now'))/60);
		$hours_until_available = floor($minutes_until_available/60);
		
		if($hours_until_available == 0 && $minutes_until_available == 0)
			$room->when_available_countdown = "0:00";
		else if($hours_until_available == 0)
			$room->when_available_countdown = "0:".str_pad($minutes_until_available,2,"0",STR_PAD_LEFT);
		else if($hours_until_available == 1)
			$room->when_available_countdown = "1:".str_pad(($minutes_until_available % 60),2,"0",STR_PAD_LEFT)." min)";
		else
			$room->when_available_countdown = $hours_until_available.":".str_pad(($minutes_until_available % 60),2,"0",STR_PAD_LEFT);
		
		
		// convert available until to a readable format
		// print("avail til: ".date("H:i:s",$available_until)."<br>\n");
		$minutes = date("i",$available_until);
		if(($minutes % RES_PRECISION) > 0)
		{
			// round up by reservation precision
			
			$minutes = $minutes + (RES_PRECISION - ($minutes % RES_PRECISION));
			if($minutes < 60)
			{
				$room->available_until = date("g:".$minutes."a",$available_until);
				$room->available_until_datestamp = date("Y-m-d H:".$minutes.":00",$available_until);
			}
			else
			{
				$room->available_until = date("g:00a",strtotime("+1 hour",$available_until));
				$room->available_until_datestamp = date("Y-m-d H:00:00",strtotime("+1 hour",$available_until));
			}
		}
		else
		{
			$room->available_until = date("g:ia",$available_until);
			$room->available_until_datestamp = date("Y-m-d H:i:00",$available_until);
		}
		
		// print(date("g:i:s",$when_available) ." - " . date("g:i:s",strtotime($room->available_until))."<br>\n");
		$minutes_available_until = ceil((strtotime($room->available_until) - $when_available)/60);
		$hours_available_until = floor($minutes_available_until/60);
		if($hours_available_until == 0 && $minutes_available_until == 0)
			$room->available_until = "Now";
		else if($hours_available_until == 0)
			$room->available_until = date("g:ia",strtotime($room->available_until))." (".$minutes_available_until." min)";
		else if($hours_available_until == 1)
			$room->available_until = date("g:ia",strtotime($room->available_until))." (1 hour ".($minutes_available_until % 60)." min)";
		else
			$room->available_until = date("g:ia",strtotime($room->available_until))." (".$hours_available_until . " hours ".($minutes_available_until % 60) . " min)";
		
		//$room->available_until .= " (max $hours_available_until hours)";
	}	
	
	if(strcmp($room->when_available_countdown,"0:00") && !strcmp($current_status,"<s1>Available</s1>"))
		$current_status = "<s3>Reserved</s3>";
	
	$room->current_status = $current_status;
	//$room->current_checkout = $checkout_for_room;
	
	return($room);
	
}



function get_room_status($room_id,$section_id,$date,$start_time,$end_time)
{
	global $db;
	
	// print("id: $room_id<br>\n");
	
	// load the current status of a room section
	if(!strcmp($room->out_of_order,"Yes"))
	{
		$room_status = "<s5>Out of order</s5>";
	}
	else
	{
		/*
		
		$now = strtotime('now');
		$when_available = $now;
		
		// check to see if the room is currently checked out and/or overdue
		$select_checkout_for_room = "select * from reservations where room_id like '$room_id' and room_section like '$section_id' and status like 'Checked Out' and sched_end_time > '$start_time' and active like '1'";
		$res_checkout_for_room = $db->query($select_checkout_for_room);
		if($res_checkout_for_room->numRows() == 1)
		{
			$res_checkout_for_room->fetchInto($checkout_for_room);
			//print("Checkout: <br>\n");
			//pr($checkout_for_room);
			if(strtotime($checkout_for_room->sched_end_time) < strtotime('now'))
			{
				$room_status = "<s2>Overdue</s2>";
			}
			else
			{
				$room_status = "<s4>Checked Out</s4>";
				$when_available = strtotime($checkout_for_room->sched_end_time);
			}
		}
		else if($res_checkout_for_room->numRows() == 0)
		{
			// room not checked out
			$room_status = "Available";
		}
		else
		{
			$room_status = "Error: Multiple checkouts";
		}
		*/
		
		$days_hours = load_hours($db,date("Y-m-d",strtotime($date)));
		
		// locate scheduled reservations
		$select_next_reservations_for_room = "select * from reservations where room_id like '$room_id' and room_section like '$section_id' and (status like 'Scheduled' OR status like 'Checked Out') and (sched_start_time <= '$end_time' OR sched_end_time > '$start_time') and active like '1' order by sched_start_time";
		print("select_next_reservations_for_room:$select_next_reservations_for_room<br>\n");
		$res_next_reservations_for_room = $db->query($select_next_reservations_for_room);
		if($res_next_reservations_for_room->numRows() == 0)
		{
			// room is available during this window
			$room_status = "Available";
		}
		else
		{
			$room_status = "Unavailable";
		}
	}

	$room->status = $room_status;
	return($room);
	
}




/* Load Room Groups */
function load_room_groups($options=array())
{
	global $db;
	
	$where = "WHERE 1";
	
	if(isset($options['sort asc']))
		$sort = "ORDER BY ".$options['sort asc'];
	else if(isset($options['sort desc']))
		$sort = "ORDER BY ".$options['sort desc'];
	else
		$sort = "ORDER BY ordering,name";
	
	
	// load room_groups
	$select = "SELECT * FROM room_groups $where AND active LIKE '1' $sort";
	//print("select: $select<br>\n");
	$res = $db->query($select);
	$all_room_groups = array();
	while($res->fetchInto($room_group))
	{
		$all_room_groups[$room_group->id] = $room_group;
	}
	return($all_room_groups);
}


/* Load Amenities */
function load_amenities($options=array())
{
	global $db;
	
	$where = "WHERE 1";
	if(isset($options['search_filter']))
	{
		$where .= " AND search_filter like '".$options['search_filter']."'";
	}
	
	if(isset($options['sort asc']))
		$sort = "ORDER BY ".$options['sort asc'];
	else if(isset($options['sort desc']))
		$sort = "ORDER BY ".$options['sort desc'];
	else
		$sort = "ORDER BY name,description";
	
	
	// load amenities
	$select = "SELECT * FROM amenities $where AND active LIKE '1' $sort";
	//print("select: $select<br>\n");
	$res = $db->query($select);
	$all_amenities = array();
	while($res->fetchInto($amenity))
	{
		$all_amenities[$amenity->id] = $amenity;
	}
	return($all_amenities);
}

/* Load Room Groups */
/*
function load_room_groups($options=array())
{
	global $db;
	
	$where = "WHERE 1";
	if(isset($options['search_filter']))
	{
		$where .= " AND search_filter like '".$options['search_filter']."'";
	}
	
	if(isset($options['sort asc']))
		$sort = "ORDER BY ".$options['sort asc'];
	else if(isset($options['sort desc']))
		$sort = "ORDER BY ".$options['sort desc'];
	else
		$sort = "ORDER BY name,description";
	
	
	// load room groups
	$select = "SELECT * FROM room_groups $where AND active LIKE '1' $sort";
	//print("select: $select<br>\n");
	$res = $db->query($select);
	$all_room_groups = array();
	while($res->fetchInto($room_group))
	{
		$all_room_groups[$room_group->id] = $room_group;
	}
	return($all_room_groups);
}
*/

/* Load Fines */
function load_fines($options=array())
{
	global $db;
	
	$where = "WHERE 1";
	
	if(isset($options['id']))
		$where .= " AND id like '".$options['id']."'";
	
	$select = "SELECT * FROM fines $where AND active LIKE '1'";
	//print("select: $select<br>\n");
	$res = $db->query($select);
	$fines = array();
	while($res->fetchInto($fine))
	{
		$select = "SELECT * FROM fines_reductions where fine_id like '$fine->id' and active like '1' order by date_added";
		// print("select: $select<br>\n");
		$fines_reductions_res = $db->query($select);
		$fine->reductions = array();
		while($fines_reductions_res->fetchInto($fine_reduction))
		{
			$fine->reductions[] = $fine_reduction;
		}
		$fines[$fine->id] = $fine;
	}
	return($fines);
}


/* Load Keys */
function get_key_by_barcode($barcode)
{
	global $db;
	$keys = array();
	$select_key = "select * from rooms_keys where key_barcode like '$barcode' and active like '1'";
	//print("select key: $select_key<br>\n");
	$res_key = $db->query($select_key);
	while($res_key->fetchInto($key))
	{
		$keys[] = $key;
	}
	return($keys);
}


/* User Functions */
function find_users_by_name_or_patron_id($query)
{
	global $db;
	
	$users = array();
	$select_users = "select * from users where (patron_id like '$query' OR barcode like '$query' OR last_name like '$query') and active like '1'";
	$res_users = $db->query($select_users);
	
	while($res_users->fetchInto($user))
	{
		// select roles
		$select_roles = "select role from roles,users_roles where users_roles.user_id like '$user->id' and roles.id = users_roles.role_id and roles.active like '1' and users_roles.active like '1' order by role";
		$res_users_roles = $db->query($select_roles);
		while($res_users_roles->fetchInto($role))
		{
			$user->roles[] = $role;
		}
		
		// select reservations
		$user->reservations = load_reservations_for_user($user->id,array('active'=>'1','sort asc'=>'date,sched_start_time'));
		
		$users[$user->id] = $user;
	}

	return($users);
}



function get_reservation_credits($user_id,$date)
{
	global $db;
	
	if(strtotime($date) < strtotime(date('Y-m-d')))
		$date = date('Y-m-d');
	
	/* for week */
	if(date('w',strtotime($date)) == 0)
		$window_start = date('Y-m-d',strtotime($date));
	else
		$window_start = date('Y-m-d',strtotime("last Sunday",strtotime($date)));
	$window_end = date('Y-m-d',strtotime("next Sunday",strtotime($window_start)));
	
	$select_user_reservations = "select * from reservations where user_id like '$user_id' and sched_start_time >= '$window_start' and sched_end_time < '$window_end' AND cancelled like '0' and active like '1'";
	//print("select user reservations (in res-window): $select_user_reservations<br>\n");
	$res_user_reservations = $db->query($select_user_reservations);
	
	$minutes_used_week = 0;
	while($res_user_reservations->fetchInto($reservation))
	{
		if(strcmp($reservation->key_checkout_time,'') && strcmp($reservation->key_checkin_time,''))
		{
			$minutes_used = round((strtotime($reservation->key_checkin_time) - strtotime($reservation->key_checkout_time))/60);
			$round_down = ($minutes_used % RES_PRECISION);
			$minutes_used = $minutes_used - $round_down;
			$minutes_used_week += $minutes_used;
		}
		else
			$minutes_used_week += (strtotime($reservation->sched_end_time) - strtotime($reservation->sched_start_time))/60;
	}
	
	$credits->week_hours_remaining = round(MAX_RESERVATION_HOURS_PER_WEEK - ($minutes_used_week/60),2);
	$credits->week_window_start = $window_start;
	$credits->week_window_end = $window_end;
	
	
	/* for day */
	$hours = load_hours($db,$date);
	$window_start = date('Y-m-d H:i:s',strtotime($hours->open_time));
	$window_end = date('Y-m-d H:i:s',strtotime($hours->close_time));
	
	$select_user_reservations = "select * from reservations where user_id like '$user_id' and sched_start_time >= '$window_start' and sched_end_time < '$window_end' AND cancelled like '0' and active like '1'";
	//print("select user reservations (in res-window): $select_user_reservations<br>\n");
	$res_user_reservations = $db->query($select_user_reservations);
	
	/* max reservation hours per day */
	$minutes_used_day = 0;
	$res_user_reservations = $db->query($select_user_reservations);
	while($res_user_reservations->fetchInto($reservation))
	{
		if(strcmp($reservation->key_checkout_time,'') && strcmp($reservation->key_checkin_time,''))
		{
			$minutes_used = round((strtotime($reservation->key_checkin_time) - strtotime($reservation->key_checkout_time))/60);
			$round_down = ($minutes_used % RES_PRECISION);
			$minutes_used = $minutes_used - $round_down;
			$minutes_used_day += $minutes_used;
		}
		else
			$minutes_used_day += (strtotime($reservation->sched_end_time) - strtotime($reservation->sched_start_time))/60;
	}
	
	$credits->day_hours_remaining = round(MAX_RESERVATION_HOURS_PER_DAY - ($minutes_used_day/60),2);
	
	// correct potentially negative credits
	if($credits->week_hours_remaining < 0)
		$credits->week_hours_remaining = 0;
	if($credits->day_hours_remaining < 0)
		$credits->day_hours_remaining = 0;

	return($credits);
}

function get_user_by_id($id)
{
	global $db;
	
	$users = array();
	$select_users = "select * from users where id like '$id'";
	$res_users = $db->query($select_users);
	
	while($res_users->fetchInto($user))
	{
		// select roles
		$select_roles = "select role from roles,users_roles where users_roles.user_id like '$user->id' and roles.id = users_roles.role_id and roles.active like '1' and users_roles.active like '1' order by role";
		$res_users_roles = $db->query($select_roles);
		while($res_users_roles->fetchInto($role))
		{
			$user->roles[] = $role;
		}
		
		// select reservations
		$user->reservations = load_reservations_for_user($user->id,array('active'=>'1','sort asc'=>'date,sched_start_time'));
		
		return($user);
	}
}



/* Reservation Conflict Functions */
function get_time_conflicts($room_id,$room_section,$user_id,$date,$start_time,$end_time,$otf,$reschedule_reservation_id=null)
{
	global $db;
	
	$conflicts = array();
	
	$rooms = load_rooms(null,array('id'=>$room_id));
	foreach($rooms as $room)
		break;
	
	$hours = load_hours($db,$date);
	
	// check to make sure there aren't any conflicts for the room
	$select_conflicts = "select * from reservations where room_id like '$room_id' and room_section like '$room_section' and ((sched_end_time > '".date('Y-m-d H:i:s',strtotime($start_time))."' AND sched_start_time <= '".date('Y-m-d H:i:s',strtotime($start_time))."') OR (sched_end_time >= '".date('Y-m-d H:i:s',strtotime($end_time))."' AND sched_start_time < '".date('Y-m-d H:i:s',strtotime($end_time))."') OR (sched_start_time >= '".date('Y-m-d H:i:s',strtotime($start_time))."' AND sched_end_time <= '".date('Y-m-d H:i:s',strtotime($end_time))."')) AND cancelled like '0' AND key_checkin_time is null and active like '1'";
	if($reschedule_reservation_id && $reschedule_reservation_id > 0)
		$select_conflicts .= " AND id not like '$reschedule_reservation_id'";
	//print("select: $select_conflicts<br>\n");
	$res_conflicts = $db->query($select_conflicts);
	$num_conflicts = $res_conflicts->numRows();
	if($num_conflicts > 0)
	{
		unset($conflict);
		if($num_conflicts == 1)
			$conflict->message = "The room reservation you selected conflicts with an existing room reservation."; 
		else
			$conflict->message = "The room reservation you selected conflicts with multiple existing room reservations."; 
		while($res_conflicts->fetchInto($res))
			$conflict->data[] = $res;
		$conflicts[] = $conflict;
	}
	
	// check to make sure there aren't any conflicts for the patron
	$select_conflicts = "select * from reservations where user_id like '$user_id' and (sched_end_time > '".date('Y-m-d H:i:s',strtotime($start_time))."' AND sched_start_time < '".date('Y-m-d H:i:s',strtotime($end_time))."') AND cancelled like '0' AND status not like 'Cancelled' AND status not like 'Completed' AND active like '1'";
	if($reschedule_reservation_id && $reschedule_reservation_id > 0)
		$select_conflicts .= " AND id not like '$reschedule_reservation_id'";
	//print("select: $select_conflicts<br>\n");
	$res_conflicts = $db->query($select_conflicts);
	$num_conflicts = $res_conflicts->numRows();
	if($num_conflicts > 0)
	{
		unset($conflict);
		if($num_conflicts == 1)
			$conflict->message = "An existing reservation overlaps with this reservation.";
		else
			$conflict->message = "Multiple existing reservations overlap with this reservation.";
		while($res_conflicts->fetchInto($res))
			$conflict->data[] = $res;
		$conflicts[] = $conflict;
	}
	
	// double-check the libary is not closed
	if(strtotime($start_time) < strtotime($hours->open_time) || strtotime($end_time) > strtotime($hours->close_time))
	{
		unset($conflict);
		$conflict->message = "You have selected a reservation that falls outside of the available hours for " . date('m/d/Y',strtotime($date)) . ".";
		$conflicts[] = $conflict;
	}
	
	// double-check the reservation is not for a time too far in the future
	$max_date_hours = load_hours($db,date('Y-m-d',strtotime("+".(MAX_FUTURE_RES_DAYS-1)." days",strtotime('now'))));
	if(strtotime($start_time) > strtotime($max_date_hours->close_time))
	{
		unset($conflict);
		$conflict->message = "You have selected a reservation that is too far in the future. Reservations may only be made as far as ".MAX_FUTURE_RES_DAYS." days in advance.";
		$conflicts[] = $conflict;
	}
	
	// double-check to make sure the reservation length is not greater than the max allowed
	$res_length = round((strtotime($end_time) - strtotime($start_time))/60);
	if($res_length > DEFAULT_MAX_RES_LEN && strcmp($otf,"1"))
	{
		unset($conflict);
		$conflict->message = "You have selected a reservation that exceeds the maximum allowed duration of ".DEFAULT_MAX_RES_LEN." minutes.";
		$conflicts[] = $conflict;
	}
	
	// double-check to make sure the reservation length is not shorter than the min allowed
	if($res_length < DEFAULT_MIN_RES_LEN)
	{
		if((in_array('Staff',$_SESSION['LibRooms']['Roles']) || in_array('Admin',$_SESSION['LibRooms']['Roles'])) && !strcmp($otf,"1"))
		{
			// on the fly checkouts are allowed to be shorter than the minimum reservation length
		}
		else
		{
			unset($conflict);
			$conflict->message = "You have selected a reservation that is shorter than the minimum allowed duration of ".DEFAULT_MIN_RES_LEN." minutes.";
			$conflicts[] = $conflict;
		}
	}
	
	// double-check that the end time comes after the start time
	if($res_length <= 0)
	{
		unset($conflict);
		$conflict->message = "You have selected an invalid start time or end time for your reservation.";
		$conflicts[] = $conflict;
	}
	
	// double-check reservation start time is for a time in the future (if staff/admin -> minus 1 res_precision)
	if(isset($_SESSION['LibRooms']['Roles']) && (in_array('Staff',$_SESSION['LibRooms']['Roles']) || in_array('Admin',$_SESSION['LibRooms']['Roles'])))
		$min_start_time = strtotime(RES_PRECISION . " minutes ago",strtotime('now'));
	else
		$min_start_time = strtotime('now');
	if(strtotime($start_time) < $min_start_time)
	{
		unset($conflict);
		$conflict->message = "You have selected a start time in the past.";
		$conflicts[] = $conflict;
	}
	
	
	// double-check room is not out-of-order
	if(!strcmp($room->out_of_order,'Yes'))
	{
		unset($conflict);
		$conflict->message = "The room you selected is currently out of order.";
		$conflicts[] = $conflict;
	}
	
	// confirm user has a staff or admin role before allowing first come first serve reservations
	if(isset($_SESSION['LibRooms']['Roles']) && !in_array('Staff',$_SESSION['LibRooms']['Roles']) && !in_array('Admin',$_SESSION['LibRooms']['Roles']) && !strcmp($room->fcfs,'Yes'))
	{
		unset($conflict);
		$conflict->message = "You do not have permission to reserve first come first serve rooms.";
		$conflicts[] = $conflict;
	}
	
	// if back-to-back reservations are not allowed, check to make sure this is not a back-to-back
	if(!strcmp(BACK_TO_BACK_RESERVATIONS,"No"))
	{
		$select_conflicts = "select * from reservations where user_id like '$user_id' and room_id like '$room_id' and (sched_end_time like '".date('Y-m-d H:i:s',strtotime($start_time))."' OR sched_start_time like '".date('Y-m-d H:i:s',strtotime($end_time))."') AND cancelled like '0' and active like '1'";
		if($reschedule_reservation_id && $reschedule_reservation_id > 0)
			$select_conflicts .= " AND id not like '$reschedule_reservation_id'";
		//print("select: $select_conflicts<br>\n");
		$res_conflicts = $db->query($select_conflicts);
		$num_conflicts = $res_conflicts->numRows();
		if($num_conflicts > 0)
		{
			unset($conflict);
			$conflict->message = "Back to back reservations by the same patron for the same room are not allowed."; 
			while($res_conflicts->fetchInto($res))
				$conflict->data[] = $res;
			$conflicts[] = $conflict;
		}
	}
	
	return($conflicts);
}

function get_limit_conflicts($user_id,$date,$start_time,$end_time,$reschedule_reservation_id=null)
{
	global $db;
	
	$errors = array();
	
	// check to make sure that this reservation will not exceed any limits for the user
	
	/* max reservations per week */
    if(date('w',strtotime($date)) == 0)
		$window_start = date('Y-m-d',strtotime($date));
	else
		$window_start = date('Y-m-d',strtotime("last Sunday",strtotime($date)));
	$window_end = date('Y-m-d',strtotime("next Sunday",strtotime($window_start)));
	
	$select_user_reservations = "select * from reservations where user_id like '$user_id' and sched_start_time >= '$window_start' and sched_end_time < '$window_end' AND cancelled like '0' and active like '1'";
	if($reschedule_reservation_id && $reschedule_reservation_id > 0)
		$select_user_reservations .= " AND id not like '$reschedule_reservation_id'";
	//print("select user reservations (in res-window): $select_user_reservations<br>\n");
	$res_user_reservations = $db->query($select_user_reservations);
	$num_user_reservations = $res_user_reservations->numRows();
	if($num_user_reservations >= MAX_RESERVATIONS_PER_WEEK)
	{
		unset($error);
		$error->message = "You have exceeded the maximum number of allowed reservations per week. Max: ".MAX_RESERVATIONS_PER_WEEK." reservations Sunday-Saturday."; 
		while($res_user_reservations->fetchInto($res))
			$error->data[] = $res;
		$errors[] = $error;
	}
	
	$credits = get_reservation_credits($user_id,$date);
	
	/* max reservation hours per week */
	if(((MAX_RESERVATION_HOURS_PER_WEEK*60)-($credits->week_hours_remaining*60)) + ((strtotime($end_time) - strtotime($start_time))/60) > MAX_RESERVATION_HOURS_PER_WEEK*60)
	{
		unset($error);
		$error->message = "You have exceeded the maximum number of allowed reservation hours per week. Max: ".MAX_RESERVATION_HOURS_PER_WEEK." reservation hours/week."; 
		while($res_user_reservations->fetchInto($res))
			$error->data[] = $res;
		$errors[] = $error;
	}
	
	
	/* max reservations per day */
	$hours = load_hours($db,$date);
	$window_start = date('Y-m-d H:i:s',strtotime($hours->open_time));
	$window_end = date('Y-m-d H:i:s',strtotime($hours->close_time));
	
	$select_user_reservations = "select * from reservations where user_id like '$user_id' and sched_start_time >= '$window_start' and sched_end_time < '$window_end' AND cancelled like '0' and active like '1'";
	if($reschedule_reservation_id && $reschedule_reservation_id > 0)
		$select_user_reservations .= " AND id not like '$reschedule_reservation_id'";
	//print("select user reservations (in res-window): $select_user_reservations<br>\n");
	$res_user_reservations = $db->query($select_user_reservations);
	$num_user_reservations = $res_user_reservations->numRows();
	if($num_user_reservations >= MAX_RESERVATIONS_PER_DAY)
	{
		unset($error);
		$error->message = "You have exceeded the maximum number of allowed reservations per day. Max: ".MAX_RESERVATIONS_PER_DAY." reservations/day."; 
		while($res_user_reservations->fetchInto($res))
			$error->data[] = $res;
		$errors[] = $error;
	}
	
	/* max reservation hours per day */	
	if((((MAX_RESERVATION_HOURS_PER_DAY*60)-($credits->day_hours_remaining*60)) + ((strtotime($end_time) - strtotime($start_time))/60)) > MAX_RESERVATION_HOURS_PER_DAY*60)
	{
		unset($error);
		$error->message = "You have exceeded the maximum number of allowed reservation hours per day. Max: ".MAX_RESERVATION_HOURS_PER_DAY." reservation hours/day."; 
		while($res_user_reservations->fetchInto($res))
			$error->data[] = $res;
		$errors[] = $error;
	}

	return($errors);
}


function get_ptype_conflicts($user_id)
{
	global $db;
	$conflicts = array();
	
	$user = get_user_by_id($user_id);
	
	$allowed_ptypes = explode(",",ALLOWED_PTYPES);
	
	if(count(array_intersect(json_decode($user->ptype), $allowed_ptypes))==0)
	{
		$conflict = new stdClass();
		$conflict->message = "Invalid patron type ($user->ptype). Reservations are not allowed for your patron type."; 
		$conflicts[] = $conflict;
	}
	
	return($conflicts);
}

function get_expiration_conflicts($user_id)
{
	global $db;
	$conflicts = array();
	
	$user = get_user_by_id($user_id);
	
	if(strtotime($user->expiration_date) < strtotime(date("Y-m-d")))
	{
		$conflict = new stdClass();
		$conflict->message = "Account is expired."; 
		$conflicts[] = $conflict;
	}

	return($conflicts);
}


/* Calendar Functions */
function load_hours_for_date($date)
{
	global $db;
	
	$where = "WHERE date like '$date'";
	$sort = "ORDER BY date asc";
	
	$select = "SELECT * FROM hours $where AND active LIKE '1' $sort";
	//print("select: $select<br>\n");
	$res = $db->query($select);
	$all_hours = array();
	while($res->fetchInto($hours))
	{
		$all_hours[$hours->id] = $hours;
	}
	return($all_hours);
}


// HTML Helper Functions
function print_checkin_form($reservation,$referrer="")
{
	$room = $reservation->room;
	$date = date('m/d/Y',strtotime($reservation->date));
	if(strtotime($date) == strtotime(date('m/d/Y')))
		$date = "Today";
	$start = date('g:ia',strtotime($reservation->sched_start_time));
	$end = date('g:ia',strtotime($reservation->sched_end_time));
	
	if(isset($_SESSION['LibRooms']['Roles']))
	{
		if(in_array('Staff',$_SESSION['LibRooms']['Roles']))
			$user_type = "staff";
		if(in_array('Admin',$_SESSION['LibRooms']['Roles']))
			$user_type = "admin";
	}
	
	if(!strcmp($user_type,'admin') || !strcmp($user_type,'staff'))
	{
		print("<script>\n");
		print("function confirm_lost_key(key)\n");
		print("{\n");
		print("	var r=confirm('Are you sure you want to report this key lost?');\n");
		print("	if(r==true) document.location.href='lost_key.php?key_barcode='+key; \n");
		print("}\n");
		print("</script>\n");
		
		print("<div id='checkin_reservation_form'>\n");
		print("<form action='checkin.php' style=''>\n");
		print("<input type='hidden' name='reservation_id' value='$reservation->id'>\n");
		print("<input type='hidden' name='search' value='$user->patron_id'>\n");
		print("<input type='hidden' name='room_id' value='$room->id'>\n");
		print("$date ($start-$end) in Room $room->room_number");
		//print("<br>Key: <input id='checkin_key_barcode' type='text' size='15' name='checkin_key_barcode' value=''> <input type='submit' value='Check-In'>\n");
		print("<input type='button' id='lost_key_button' name='lost_key' value='Lost Key' onClick='confirm_lost_key($reservation->key_barcode)'>\n");
		print("<br><b><a href='print_checkout_receipt.php?reservation_id=$reservation->id' target='_blank'>View/Print Checkout Receipt</a></b>");
		print("</form><br>\n");
		print("</div>\n");
	}
	else
	{
		print("<div id='checkin_reservation_form'>\n");
		print("$date ($start-$end) in Room $room->room_number");
		//print("<br><b><a href='print_checkout_receipt.php?reservation_id=$reservation->id' target='_blank'>View/Print Checkout Receipt</a></b>");
		if(strtotime($reservation->sched_end_time) < strtotime('now'))
			print("<br><b>NOTICE: ROOM CHECKOUT OVERDUE!</b>\n");
		print("<br>\n");
		print("</div>\n");

	}
	
}

function print_checkout_form($reservation,$referrer)
{
	$room = $reservation->room;
	$date = date('m/d/Y',strtotime($reservation->date));

	if(strtotime($date) == strtotime(date('m/d/Y')))
		$date = "Today";
	$start = date('g:ia',strtotime($reservation->sched_start_time));
	$end = date('g:ia',strtotime($reservation->sched_end_time));
	
	
	print("<div id='checkout_reservation_form'>\n");
	
	// TODO: may not need referrer... just send to user's details page
	//print("<input type='hidden' name='referrer' value='$referrer'>\n");
	print("Upcoming Reservation:\n");
	print("$date ($start-$end) in Room $room->room_number\n");
	
	if(in_array('Staff',$_SESSION['LibRooms']['Roles']) || in_array('Admin',$_SESSION['LibRooms']['Roles']))
	{
		print("<form action='checkout.php' style='margin-left:30px'>\n");
		print("<input type='hidden' name='reservation_id' value='$reservation->id'>\n");
		print("<input type='hidden' name='room_id' value='$room->id'>\n");
		print("<input type='hidden' name='user_id' value='$reservation->user_id'>\n");
	
		// if reservation starts within one unit of precision, then show checkout form
		if(strtotime($reservation->sched_start_time) < (strtotime('now') + (RES_PRECISION*60)))
		{
			print("<br>Key: <input id='checkout_key_barcode' type='text' size='15' name='checkout_key_barcode' value=''> <input type='submit' value='Check Out Key'><br>\n");
			
			// TODO: check to see if room is currently in use
			/*
			$reservations = load_reservations(array('room_id'=>$room->id,'status'=>"Checked Out",'active'=>"1"));
			if(count($reservations) > 0)
			{
				foreach($reservations["Checked Out"] as $reservation)
					break;
				print("<b>Note: Room is currently checked out until ".date("g:ia",strtotime($reservation->sched_end_time))."</b><br>\n");
			}
			*/
		}
		else
		{
			print("<br>Reservation starts more than " . RES_PRECISION . " minutes from now.<br>Checkout not allowed.");
		}
		print("</form>\n");
	}	
	
	print("<center><table><tr><td>\n");
	print("<button onClick='document.location.href=\"reservation_details.php?reservation_id=$reservation->id\";'>Reservation Details</button>\n");
	print("</td><td>\n");
	print("<form action='cancel_reservation.php'>\n");
	print("<input type='hidden' name='reservation_id' value='$reservation->id'>\n");
	print("<input type='submit' value='Cancel Reservation' type='submit'>\n");
	print("</form>\n");
	print("</td><td>\n");
	print("<form action='reservation_calendar.php'>\n");
	print("<input type='hidden' name='reschedule' value='$reservation->id'>\n");
	print("<input type='hidden' name='selected_date' value='$reservation->date'>\n");
	print("<input type='submit' value='Reschedule Reservation' type='submit'>\n");
	print("</form>\n");
	print("</td></tr></table></center>\n");
	
	print("</div>\n");
}

function print_fines_table($reservation)
{
	global $user_type;
	
	if(count($reservation->fines) > 0)
	{
		// check to see if total fines are greater than 0. if not, then don't show to non-admin users
		$total_fines = 0;
		foreach($reservation->fines as $fine)
		{
			$net_fine_amount = $fine->amount;
			if(count($fine->reductions) > 0)
			{
				foreach($fine->reductions as $reduction)
				{
					$net_fine_amount -= $reduction->amount;
				}
			}
			$total_fines += $net_fine_amount;
		}
		
		if($total_fines > 0 || !strcmp($user_type,'admin'))
		{
			print("<div class='fines_table'><h2>Fines</h2>\n");
			print("<table id='fines_table' width='100%' border><thead><tr><th>Date</th><th>Description</th><th>Amount</th></tr></thead>");
			print("<tbody>\n");
			
			$total_fines = 0;
			foreach($reservation->fines as $fine)
			{
				$date = date('m/d/Y g:ia',strtotime($fine->date_added));
				
				$net_fine_amount = $fine->amount;
				
				if(count($fine->reductions) > 0)
				{
					foreach($fine->reductions as $reduction)
					{
						$net_fine_amount -= $reduction->amount;
						$reductions .= "<div style='color:red'>-".format2dollars($reduction->amount)." reduction</div>";
					}
					$reductions .= "<div style='font-weight:bold'>".format2dollars($net_fine_amount)."</div>\n";
				}
				else
					$reductions = "";
					
				$total_fines += $net_fine_amount;
				$amount = format2dollars($fine->amount);
				
				print("<tr><td width='170'>$date</td><td>$fine->description</td><td width='100' align='right'>$amount ");
				if(!strcmp($user_type,'admin') || !strcmp($user_type,'staff'))
				{
					print("(<a href='javascript:open_reduce_fine_dialog($fine->id)'>reduce)</a>");
				}
				print("$reductions</td></tr>\n");
			}
			$total_fines = format2dollars($total_fines);
			print("<tr><td>&nbsp;</td><td align='right'><b>TOTAL</b></td><td align='right'><b>$total_fines</b></td></tr>\n");
			print("</tbody></table>");
			print("</div>\n");
		}
	}
}

/* Reports */
function get_summary_report_date($start_date,$end_date)
{
	global $db;
	
		
	// Total Reservations & Checkouts = All reservations regardless of status
	// Total Returned Checkouts
		// On-The-Fly Checkouts
		// Reserved by Patron
	// Checked Out Now
	// Total Upcoming Reservations
	// Total Cancellations
		// No shows
		// Cancelled by Staff
		// Cancelled by Patron
	
	// Total Overdue Checkins
	
	// Average Checkouts/Day by Room & Room Group
	// Average Hourly Usage/Day by Room & Room Group
}


/* Misc Helper Functions */
function format2dollars($num)
{
	$num = number_format($num,2);
	$num = "\$".$num;
	return($num);
}

function display_errors($errors,$extra_info="")
{
	if(is_array($errors))
	{
		foreach($errors as $error_id => $error)
		{
			display_error($error,$extra_info);
		}
	}
}

function display_error($error,$extra_info="")
{
	print("<div class='error'>Error: $error</div>\n");
	
	@log_error($error,$extra_info);
}

function log_error($error,$extra_info="")
{
	// log error
	$filename = "errors.txt";
	if(is_writable($filename))
	{
		$handle = fopen($filename, 'a');
		$entry = date('m/d/Y g:i:sa',strtotime('now')) . " | " . $error . " | " . json_encode($extra_info) . " | " . $_SESSION["LibRooms']['FirstName"] . " " . $_SESSION["LibRooms']['LastName"] . "\n";
		fwrite($handle, $entry);
		fclose($handle);
	}
}

function pr($obj)
{
	print("<pre>\n");
	print_r($obj);
	print("</pre>\n");
}

?>