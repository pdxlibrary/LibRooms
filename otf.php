<?php

// create an on-the-fly checkout

session_start();

require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("load.php");
require_once("includes/load_hours.php");
require_once("includes/load_settings.php");
require_once("includes/verify_access.php");
restrict_access($db,array("staff","admin"));

$user_id = $_GET['user_id'];
$checkout_key_barcode = $_GET['checkout_key_barcode'];

// load reservation data
$selected_date = date('Y-m-d');
	
$possible_amenities = array();
if(isset($_GET['amenities']))
	$selected_amenities = $_GET['amenities'];

$room_filter = array();
$room_filter['out_of_order'] = "No";
if(isset($_GET['capacity_filter']) && strcmp($_GET['capacity_filter'],''))
	$room_filter['capacity'] = $_GET['capacity_filter'];
$room_filter['amenities and'] = $_GET['amenity_filter'];
$room_filter['group_by'] = "room_group_id";

$all_rooms = load_rooms(null,$room_filter);
$all_room_groups = load_room_groups();
$all_reservations = load_calendar_reservations();


if(isset($_GET['user_id']) && isset($_GET['checkout_key_barcode']))
{
	// forward to checkout response form
	
	$keys = get_key_by_barcode($checkout_key_barcode);
	foreach($keys as $key)
		break;
	// pr($key);
	
	$user = get_user_by_id($user_id);
	//pr($user);
	
	$rooms = load_rooms(null,array('id'=>$key->room_id));
	foreach($rooms as $room)
		break;
	// pr($room);

	$longest_availability = strtotime("now");
	$selected_section = 0;
	$selected_section_status = null;
	for($s=1;$s<=$room->max_simultaneous_reservations;$s++)
	{
		$status = get_room_current_status($room->id,$s);
		if(!strcmp($status->when_available,'Now'))
		{
			if(strtotime($status->available_until_datestamp) > $longest_availability)
			{
				$selected_section = $s;
				$selected_section_status = $status;
				$longest_availability = strtotime($status->available_until_datestamp);
			}
		}
	}
	
	if($selected_section == 0)
	{
		// no section could be found for the selected key
		display_error("Room $room->room_number is not available at this time.",array("room_id"=>$room->id,"user_id"=>$user->id));
	}
	else
	{
		// round up start time to next unit of precision
		$minutes = date("i",strtotime($selected_section_status->when_available_datestamp));
		if(($minutes % RES_PRECISION) > 0)
		{
			$minutes = $minutes + (RES_PRECISION - ($minutes % RES_PRECISION));
			if($minutes < 60)
				$start_time_stamp = date("Y-m-d H:".$minutes.":00",strtotime($selected_section_status->when_available_datestamp));
			else
				$start_time_stamp = date("Y-m-d H:".($minutes-60).":00",strtotime("+1 hour",strtotime($selected_section_status->when_available_datestamp)));
		}
		else
			$start_time_stamp = date("Y-m-d H:m:00",strtotime($selected_section_status->when_available_datestamp));
		
		$start_time = date("YmdHis",strtotime($start_time_stamp));
		$end_time = date("YmdHis",strtotime($selected_section_status->available_until_datestamp));
		
		header("location: confirm_reservation.php?requesting_patron=$user->patron_id&room_id=".$room->id."_$selected_section&selected_date=$selected_date&start_time=$start_time&end_time=$end_time&key_barcode=$checkout_key_barcode&otf=1&submitted=1");
		exit();
	}
}

require_once("includes/header.php");

?>

<script language="JavaScript" src="js/jquery.ui.widget.js"></script>
<script language="JavaScript" src="js/jquery.ui.dialog.js"></script>
<script language="JavaScript" src="js/jquery.ui.core.js"></script>
<script language="JavaScript" src="js/jquery.ui.position.js"></script>
<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.11/themes/base/jquery-ui.css" type="text/css" media="all" />
<script type="text/javascript" language="javascript" src="js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf-8">

	// turn off caching for ajax calls, fixes IE caching issue
	jQuery.ajaxSetup({ cache: false });

	jQuery(document).ready(function($)
	{
		var otf_table_available = $('#otf_table_available').dataTable({
			"bJQueryUI": true,
			"iDisplayLength": 50,
			"sPaginationType": "full_numbers",
			"aaSorting": [[ 3, "asc" ]],
			"aoColumns": [
				{ },
				{ },
				{ },
				{ },
				{ }
			]
		});
		
		var otf_table_not_available = $('#otf_table_not_available').dataTable({
			"bJQueryUI": true,
			"iDisplayLength": 50,
			"sPaginationType": "full_numbers",
			"aaSorting": [[ 4, "asc" ]],
			"aoColumns": [
				{ },
				{ },
				{ },
				{ },
				{ },
				{ }
			]
		});
		
		$("#otf_table_wrapper tbody tr").mouseover(function() {
			$(this).children("td").each(function() {
				$(this).css("background-color","#FF0");
			});
		})
		
		$("#otf_table_wrapper tbody tr").mouseout(function() {
			$(this).children("td").each(function() {
				$(this).css("background-color","");
			});
		})

	});

</script>
<link rel="stylesheet" href="css/jquery-ui.css" type="text/css" media="all" />
<link rel="stylesheet" href="css/results_table.css" type="text/css" media="all" />
<style>
.dataTable td {padding:0;}
.dataTables_wrapper .ui-state-default {padding:0; font-size:13px; font-weight:bold;}
#otf_table_wrapper td {text-align:center; font-size:11px;}
.datatables_wrapper .ui-state-default {padding-left:8px; padding-right:8px;}
</style>


<div id='PageTitle'>Create an On-The-Fly Checkout</div>
<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='/studyrooms'>Reserve a Study Room</a> &raquo; Create an On-The-Fly Checkout</div>

<?php 
	require_once("includes/calendar_filter_form.php");
	
	print("<br>\n");
	
	print("<form action='otf.php'>\n");
	print("<input type='hidden' name='user_id' value='$user_id'>\n");
	print("<div style='padding-left:20px; font-weight:bold'>Key Barcode: <input type='text' id='checkout_key_barcode' name='checkout_key_barcode'><input type='submit' value='Go'></div>\n");
	print("</form>\n");
	
	// display table of rooms, with availability 
	// pr($all_rooms);
	print("<table id='otf_table_available' width='850' border='1'>\n");
	print("<thead><tr><th>Room#</th><th>Capacity</th><th>Room Group</th><th>Current Status</th><th>Max Checkout Time</th></tr></thead>\n");
	print("<tbody>\n");
	foreach($all_rooms as $room_group_id => $room_group)
	{
		foreach($room_group as $room_id => $room)
		{
			for($s=1;$s<=$room->max_simultaneous_reservations;$s++)
			{
				$status = get_room_current_status($room->id,$s);
				if(!strcmp($status->current_status,'<s1>Available</s1>'))
					print("<tr><td>$room->room_number</td><td>$room->capacity</td><td>".$all_room_groups[$room->room_group_id]->name."</td><td>$status->current_status</td><td>$status->available_until</td></tr>\n");
			}
		}
	}
	print("</tbody></table>\n");
	
	print("<br>\n");
	
	print("<h2>Currently Unavailable Rooms</h2>\n");
	print("<table id='otf_table_not_available' width='850' border='1'>\n");
	print("<thead><tr><th>Room#</th><th>Capacity</th><th>Room Group</th><th>Current Status</th><th>When Available</th><th>Max Checkout Time</th></tr></thead>\n");
	print("<tbody>\n");
	foreach($all_rooms as $room_group_id => $room_group)
	{
		foreach($room_group as $room_id => $room)
		{
			for($s=1;$s<=$room->max_simultaneous_reservations;$s++)
			{
				$status = get_room_current_status($room->id,$s);
				if(strcmp($status->current_status,'<s1>Available</s1>'))
					print("<tr><td>$room->room_number</td><td>$room->capacity</td><td>".$all_room_groups[$room->room_group_id]->name."</td><td>$status->current_status</td><td><span title='".strtotime($status->when_available)."'>$status->when_available</span></td><td>$status->available_until</td></tr>\n");
			}
		}
	}
	print("</tbody></table>\n");
	
?>