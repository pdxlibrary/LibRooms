<?php

// TODO: merge with my_reservations.php

session_start();
require_once("config/config.inc.php");
require_once("includes/Database.php");


require_once("load.php");

require_once("includes/verify_access.php");
restrict_access($db,array("staff","admin"));

if(isset($_GET['selected_date']))
{
	$selected_date = $_GET['selected_date'];
	$selected_date = date('Y-m-d',strtotime($selected_date));
}
else
	$selected_date = date('Y-m-d');
	
$scheduled_reservations = load_reservations(array('status'=>"Scheduled",'active'=>'1'));
$checked_out_reservations = load_reservations(array('status'=>"Checked Out",'active'=>'1'));
$completed_reservations = load_reservations(array('status'=>array("Cancelled","Completed"),'limit'=>50,'active'=>'1','sort desc'=>'sched_start_time'));


// KEY CHECK-IN
if(isset($_GET['reservation_id']) && isset($_GET['room_id']) && isset($_GET['checkin_key_barcode']))
{
	// TODO: check to make sure key hasn't already been checked in (reload issue)
	$checkin_result = checkin_key($_GET['reservation_id'],$_GET['room_id'],$_GET['checkin_key_barcode']);
	
	// reload user
	$users = find_users_by_name_or_patron_id($_GET['search']);
	foreach($users as $user)
			break;
	
	$message = "Successfully checked-in key for $user->first_name $user->last_name.";
}

// KEY CHECKOUT
if(isset($_GET['reservation_id']) && isset($_GET['room_id']) && isset($_GET['checkout_key_barcode']))
{
	// TODO: check to make sure key hasn't already been checked out (reload issue)
	$checkout_result = checkout_key($_GET['reservation_id'],$_GET['room_id'],$_GET['checkout_key_barcode']);
	
	// reload user
	$users = find_users_by_name_or_patron_id($_GET['search']);
	foreach($users as $user)
			break;

	$message = "Successfully checked out key to $user->first_name $user->last_name.";
}


require_once("includes/header.php");

?>

<script type="text/javascript" language="javascript" src="js/jquery.ui.core.js"></script>
<script type="text/javascript" language="javascript" src="js/jquery.ui.datepicker.js"></script>
<script type="text/javascript" language="javascript" src="js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf-8">

	// turn off caching for ajax calls, fixes IE caching issue
	jQuery.ajaxSetup({ cache: false });

	jQuery(document).ready(function($)
	{
		$("#selected_date").datepicker({
			onSelect: function(dateText, inst) {$("#selected_date_form").submit();}
		});
		
		
		var checked_out_reservations_table = $('#checked_out_reservations_table').dataTable({
			"bJQueryUI": true,
			"sPaginationType": "full_numbers",
			"aaSorting": [[ 0, "asc" ]],
			"aoColumns": [
				{ "sWidth": "100px"},
				{ "sWidth": "150px"},
				{ "bSortable": false, "sWidth": "100px" },
				{ "bSortable": false, "sWidth": "100px" },
				{ "bSortable": false, "sWidth": "100px" },
				{ "sWidth": "100px" },
				{ "sWidth": "100px" },
				{ "bSortable": false, "sWidth": "50px" }
			]
		});
		
		var upcoming_reservations_table = $('#upcoming_reservations_table').dataTable({
			"bJQueryUI": true,
			"sPaginationType": "full_numbers",
			"aaSorting": [[ 0, "asc" ]],
			"aoColumns": [
				{ "sWidth": "100px"},
				{ "sWidth": "150px"},
				{ "bSortable": false, "sWidth": "100px" },
				{ "bSortable": false, "sWidth": "100px" },
				{ "sWidth": "100px" },
				{ "bSortable": false, "sWidth": "50px" }
			]
		});
		
		var reservation_history_table = $('#reservation_history_table').dataTable({
			"bJQueryUI": true,
			"sPaginationType": "full_numbers",
			"aaSorting": [[ 0, "desc" ]],
			"aoColumns": [
				{ "sWidth": "100px"},
				{ "sWidth": "150px"},
				{ "bSortable": false, "sWidth": "100px" },
				{ "bSortable": false, "sWidth": "100px" },
				{ "bSortable": false, "sWidth": "100px" },
				{ "bSortable": false, "sWidth": "100px" },
				{ "sWidth": "100px" },
				{ "sWidth": "100px" },
				{ "bSortable": false, "sWidth": "50px" }
			]
		});
	});
</script>
<link rel="stylesheet" href="css/jquery-ui.css" type="text/css" media="all" />
<link rel="stylesheet" href="css/results_table.css" type="text/css" media="all" />
<style>
.dataTable td {padding:0;}
.dataTable th {padding:0;}
</style>

<?php

print("<div id='PageTitle'>Manage Reservations</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Manage Reservations</div>\n");

print("<br>\n");


print("<div style='font-weight:bold; color:green;'>$message</div><br>\n");



// get current checkouts
print("<h2>Currently Checked Out</h2>\n");
if(count($checked_out_reservations) > 0)
{
	print("<br><table id='checked_out_reservations_table' width='850' border='0'>");
	print("<thead><tr>");
	print("<th>Date</th>");
	print("<th>Reserved By</th>");
	print("<th>Scheduled Start Time</th>");
	print("<th>Scheduled End Time</th>");
	print("<th>Checkout Time</th>");
	print("<th>Room</th>");
	print("<th>Status</th>");
	print("<th>&nbsp;</th>");
	print("</tr></thead>");
	print("<tbody>");
	foreach($checked_out_reservations as $reservation)
	{
		print("<tr class='row_$reservation->status'>\n");
		$room = $reservation->room;
		
		$date = date('m/d/Y',strtotime($reservation->date));
		$sched_start = date('g:ia',strtotime($reservation->sched_start_time));
		$sched_end = date('g:ia',strtotime($reservation->sched_end_time));
		if($reservation->key_checkout_time)
			$key_checkout = date('g:ia',strtotime($reservation->key_checkout_time));
		else
			$key_checkout = "";
			
		print("<td align='center'>$date</td>");
		$user = get_user_by_id($reservation->user_id);
		print("<td align='center'>$user->first_name $user->last_name</td>");
		print("<td align='center'>$sched_start</td>");
		print("<td align='center'>$sched_end</td>");
		print("<td align='center'>$key_checkout</td>");
		print("<td align='center'>$room->room_number</td>");
		print("<td align='center'>$reservation->status</td>");
		print("<td align='center'><a href='reservation_details.php?reservation_id=$reservation->id'>Details</a></td>\n");
		print("</tr>\n");
	}
	print("</tbody></table><br><br>\n");
}
else
	print("<h3 style='margin-left:30px;'>No checkouts at this time.</h3><br>\n");

display_errors($error_messages['checkin_key_barcode']);


// get next reservation info
print("<h2>Scheduled Upcoming Reservations</h2>\n");
if(count($scheduled_reservations) > 0)
{
	print("<br><table id='upcoming_reservations_table' width='850' border='0'>");
	print("<thead><tr>");
	print("<th>Date</th>");
	print("<th>Reserved By</th>");
	print("<th>Scheduled Start Time</th>");
	print("<th>Scheduled End Time</th>");
	print("<th>Room</th>");
	print("<th>&nbsp;</th>");
	print("</tr></thead>");
	print("<tbody>\n");
	foreach($scheduled_reservations as $reservation)
	{
		print("<tr>\n");
		$room = $reservation->room;
		
		$date = date('m/d/Y',strtotime($reservation->date));
		$start = date('g:ia',strtotime($reservation->sched_start_time));
		$end = date('g:ia',strtotime($reservation->sched_end_time));
		
		print("<td align='center'>$date</td>");
		$user = get_user_by_id($reservation->user_id);
		print("<td align='center'>$user->first_name $user->last_name</td>");
		print("<td align='center'>$start</td>");
		print("<td align='center'>$end</td>");
		print("<td align='center'>$room->room_number</td>");
		print("<td align='center'><a href='reservation_details.php?reservation_id=$reservation->id'>Details</a></td>\n");
		print("</tr>\n");
	}
	print("</tbody></table><br><br>\n");
}
else
	print("<h3 style='margin-left:30px'>No upcoming room reservations scheduled at this time.</h3><br>\n");

	
// reservation history
print("<h2>Completed Reservations</h2>\n");
if(count($completed_reservations) > 0)
{
	print("<br><table id='reservation_history_table' width='850' border='0'>");
	print("<thead><tr>");
	print("<th>Date</th>");
	print("<th>Reserved By</th>");
	print("<th>Scheduled Start Time</th>");
	print("<th>Scheduled End Time</th>");
	print("<th>Checkout Time</th>");
	print("<th>Check-in Time</th>");
	print("<th>Room</th>");
	print("<th>Status</th>");
	print("<th>&nbsp;</th>");
	print("</tr></thead>");
	print("<tbody>");
	foreach($completed_reservations as $reservation)
	{
		print("<tr class='row_$reservation->status'>\n");
		$room = $reservation->room;
		
		$date = date('m/d/Y',strtotime($reservation->date));
		$sched_start = date('g:ia',strtotime($reservation->sched_start_time));
		$sched_end = date('g:ia',strtotime($reservation->sched_end_time));
		if($reservation->key_checkout_time)
			$key_checkout = date('g:ia',strtotime($reservation->key_checkout_time));
		else
			$key_checkout = "";
		if($reservation->key_checkin_time)
			$key_checkin = date('g:ia',strtotime($reservation->key_checkin_time));
		else
			$key_checkin = "";
			
		print("<td align='center'>$date</td>");
		$user = get_user_by_id($reservation->user_id);
		print("<td align='center'>$user->first_name $user->last_name</td>");
		print("<td align='center'>$sched_start</td>");
		print("<td align='center'>$sched_end</td>");
		print("<td align='center'>$key_checkout</td>");
		print("<td align='center'>$key_checkin</td>");
		print("<td align='center'>$room->room_number</td>");
		print("<td align='center'>$reservation->status</td>");
		print("<td align='center'><a href='reservation_details.php?reservation_id=$reservation->id'>Details</a></td>\n");
		print("</tr>\n");
	}
	print("</tbody></table>\n");
}
else
	print("<h3 style='margin-left:30px'>There is no reservation history for today.</h3><br>\n");



	
require_once("includes/footer.php");

?>