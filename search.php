<?php

session_start();

require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("load.php");

// Authentication
require_once("includes/verify_access.php");
restrict_access($db,array("staff","admin"));
	
if(isset($_SESSION['LibRooms']['Roles']))
{
	if(in_array('Staff',$_SESSION['LibRooms']['Roles']))
		$user_type = "staff";
	if(in_array('Admin',$_SESSION['LibRooms']['Roles']))
		$user_type = "admin";
}

$search = $_GET['search'];

// find matching users
$users = find_users_by_name_or_patron_id($search);
if(count($users) > 1)
{
	// multile users found
	require_once("includes/header.php");
	print("<div id='PageTitle'>Search Results</div>\n");
	print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Search Results</div>\n");
	print("<br>\n");
	print("<h3>Found multiple matches for [$search]:</h3>\n");
	print("<ul>\n");
	foreach($users as $user)
	{
		print("<li><a href='user_details.php?user_id=$user->id'>$user->first_name $user->last_name</a></li>\n");
	}
	print("</ul>\n");
	require_once("includes/footer.php");
	exit();
}
else if(count($users) == 1)
{
	// one user found, forward to user details page
	foreach($users as $user)
		break;
	header("location: user_details.php?user_id=$user->id");
	exit();
}
else
{
	/*
	// check to see if the user has an account in III that needs to be synched
	$synch_result = synch_user($search);
	if(!is_array($synch_result))
	{
		// user account was found and synched
		header("location: user_details.php?user_id=$synch_result");
		exit();
	}
	*/
}

$keys = get_key_by_barcode($search);
// pr($keys);
if(count($keys) == 1)
{
	// search matches key barcode
	
	foreach($keys as $key)
		break;
	
	$checkouts = get_reservation_for_checked_out_key($key->key_barcode);
	if(count($checkouts)==1)
	{
		foreach($checkouts as $reservation)
			break;
		
		// if key is currently checked out, check the key back in
		header("location: checkin.php?checkin_key_barcode=$key->key_barcode&reservation_id=$reservation->id");
		exit();
	}
	else if(count($checkouts)==0)
	{
		if(!strcmp($key->status,'Checked Out'))
		{
			// fix status if out of synch
			// update_key_status($key->key_barcode,"Available");
			require_once("includes/header.php");
			print("<div id='PageTitle'>Search Results</div>\n");
			print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Search Results</div>\n");
			print("<br>");
			display_error("This key does not have any active checkouts, but it's status is still listed as checked out.",$key);
			require_once("includes/footer.php");
			exit();
		}
		else if(!strcmp($key->status,'Lost'))
		{
			header("location: found_key.php?key_barcode=$key->key_barcode");
			exit();
		}
	}
	else
	{
		// TODO: handle error of key checked out more than once
		require_once("includes/header.php");
		print("<div id='PageTitle'>Search Results</div>\n");
		print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Search Results</div>\n");
		print("<br>");
		display_error("Key [$key->key_barcode] has been checked out twice.",$checkouts);
		require_once("includes/footer.php");
		exit();
	}
	
	$current_status = get_room_current_availability($key->room_id);
	
	if(!strcmp($current_status->out_of_order,'Yes'))
	{
		require_once("includes/header.php");
		print("<div id='PageTitle'>Search Results</div>\n");
		print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Search Results</div>\n");
		print("<br>");
		display_error("This room cannot be checked out, as it is Out of Order.",$current_status);
		require_once("includes/footer.php");
		exit();
	}
	else if(strtotime($current_status->max_gap->end_time) - strtotime($current_status->max_gap->start_time) == 0)
	{
		require_once("includes/header.php");
		print("<div id='PageTitle'>Search Results</div>\n");
		print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Search Results</div>\n");
		print("<br>");
		display_error("This room has no availability to be checked out.",$current_status);
		// TODO: display when the room is due to be available / overdue?
		require_once("includes/footer.php");
		exit();
	}
	else
	{
		/*
		// OTF Checkouts no longer started from the search box
		// TODO: change selected date to be the current date based on the calendar rather than the actual date (hours for current day later than midnight issue)
		$selected_date = date("Y-m-d");
		$start_time = date("YmdHis",strtotime($current_status->max_gap->start_time));
		$end_time = date("YmdHis",strtotime($current_status->max_gap->end_time));
		$room = $key->room_id."_".$current_status->max_gap->room_section;
		header("location: confirm_reservation.php?selected_date=$selected_date&room_id=$room&start_time=$start_time&end_time=$end_time&key_barcode=$key->key_barcode&otf=1");
		*/
		
		header("location: room_details.php?room_id=$key->room_id");
		exit();
	}
	
	// TODO: add new specific check for this above
	if(!strcmp($current_status->status,'Reserved, yet to be checked out'))
	{
		/*
		require_once("includes/header.php");
		print("<div id='PageTitle'>Search Results</div>\n");
		print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Search Results</div>\n");
		print("Room $current_status->room_number is currently reserved for this time.<br>\n");
		require_once("includes/footer.php");
		*/
		header("location: reservation_details.php?reservation_id=".$current_status->next_reservation->id);
		exit();
	}
}



// hack to prefix with zeros for room number searching
$room_search = $search;
while(strlen($room_search) < 3)
	$room_search = "0".$room_search;
	
$rooms = load_rooms(null,array('room_number'=>$room_search));
if(count($rooms) == 1)
{
	// matching room found, redirect to room details page
	foreach($rooms as $room)
		break;
	header("location: room_details.php?room_id=$room->id");
	exit();
}

// if no matches...
require_once("includes/header.php");
print("<div id='PageTitle'>Search Results</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Search Results</div>\n");
print("<br>");
display_error("Search for [$search] found no results.");
require_once("includes/footer.php");

?>