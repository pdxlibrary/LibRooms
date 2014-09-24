<?php

// TODO: possibly merge with user_details.php

session_start();
require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("load.php");
require_once("includes/verify_access.php");
restrict_access($db,array("patron"));

$user_id = $_SESSION['LibRooms']['UserID'];

if(isset($_SESSION['LibRooms']['UserID']))
{
	// lookup user by user id
	$user = get_user_by_id($_SESSION['LibRooms']['UserID']);
}

require_once("includes/header.php");

?>

<script type="text/javascript" language="javascript" src="js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf-8">

	// turn off caching for ajax calls, fixes IE caching issue
	jQuery.ajaxSetup({ cache: false });

	jQuery(document).ready(function($)
	{
		var upcoming_reservations_table = $('#upcoming_reservations_table').dataTable({
			"bJQueryUI": true,
			"sPaginationType": "full_numbers",
			"aaSorting": [[ 0, "asc" ]],
			"aoColumns": [
				{ "sWidth": "150px"},
				{ "bSortable": false, "sWidth": "200px" },
				{ "bSortable": false, "sWidth": "200px" },
				{ "sWidth": "100px" },
				{ "bSortable": false, "sWidth": "50px" }
			]
		});
		
		var reservation_history_table = $('#reservation_history_table').dataTable({
			"bJQueryUI": true,
			"sPaginationType": "full_numbers",
			"aaSorting": [[ 0, "desc" ]],
			"aoColumns": [
				{ "sWidth": "150px"},
				{ "bSortable": false, "sWidth": "150px" },
				{ "bSortable": false, "sWidth": "150px" },
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

print("<div id='PageTitle'>My Reservations</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; My Reservations</div>\n");

print("<div style='float:right; background-color:#FFF'>\n");
require_once("includes/available_credits_widget.php");
print("</div>\n");

print("<br>\n");

if(isset($_GET['debug']))
	pr($user);

// get current checkouts
print("<h2>Currently Checked Out</h2>\n");
if(isset($user->reservations["Checked Out"]))
{
	/*
	$checkouts = $user->reservations["Checked Out"];
	foreach($checkouts as $checkout)
	{
		$room = $checkout->room;
		$date = date('m/d/Y',strtotime($checkout->date));
		$start = date('g:ia',strtotime($checkout->sched_start_time));
		$end = date('g:ia',strtotime($checkout->sched_end_time));
		print("<div id='checked_out_key' style='margin-left:30px;'>CHECKED OUT KEY:\n");
		print("$date ($start-$end) for Room $room->room_number\n");
		//print("<b><a href='print_checkout_receipt.php?reservation_id=$checkout->id' target='_blank'>View/Print Receipt</a></b>");
		print("</div>\n");
		print("<br>\n");
	}
	*/
	
	$checkouts = $user->reservations["Checked Out"];
	foreach($checkouts as $checkout)
	{
		print_checkin_form($checkout,$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']);
	}
}
else
	print("<h3 style='margin-left:30px;'>No checkouts at this time.</h3><br>\n");


// get next reservation info
print("<h2>Scheduled Upcoming Reservations</h2>\n");
if(isset($user->reservations["Scheduled"]))
{
	$first = true;
	$upcoming = $user->reservations["Scheduled"];
	foreach($upcoming as $reservation)
	{
		//pr($reservation);
		$room = $reservation->room;
		$date = date('m/d/Y',strtotime($reservation->date));
		if(strtotime($date) <= strtotime('now'))
			$date = "Today";
		$start = date('g:ia',strtotime($reservation->sched_start_time));
		$end = date('g:ia',strtotime($reservation->sched_end_time));
		
		// TODO: highlight next reservation
		/*
		print("<div id='next_reservation'>NEXT RESERVATION:\n");
		print("$date ($start-$end) in Room $room->room_number\n");
		$starts_in_secs = strtotime($reservation->sched_start_time) - strtotime('now');
		$starts_in_hours = $starts_in_secs % 3600;
		$starts_in_minutes = $starts_in_secs % 60;
		if($start_in_seconds < 0)
			print("<br>Reservation starts  from now - <a href='reservation_details.php?reservation_id=$reservation->id'>Details</a>");
		else(
			print("<br>Reservation starts  from now - <a href='reservation_details.php?reservation_id=$reservation->id'>Details</a>");
		print("</div>\n");
		*/
		break;
	}

	print("<br><table id='upcoming_reservations_table' width='850' border='0'>");
	print("<thead><tr>");
	print("<th>Date</th>");
	print("<th>Scheduled Start Time</th>");
	print("<th>Scheduled End Time</th>");
	print("<th>Room</th>");
	print("<th>&nbsp;</th>");
	print("</tr></thead>");
	print("<tbody>\n");
	foreach($upcoming as $reservation)
	{
		print("<tr>\n");
		$room = $reservation->room;
		
		$date = date('m/d/Y',strtotime($reservation->date));
		$start = date('g:ia',strtotime($reservation->sched_start_time));
		$end = date('g:ia',strtotime($reservation->sched_end_time));
		
		print("<td align='center'>$date</td>");
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
print("<h2>Reservation History</h2>\n");
if(isset($user->reservations['Completed']))
{
	$completed	= $user->reservations['Completed'];

	print("<br><table id='reservation_history_table' width='850' border='0'>");
	print("<thead><tr>");
	print("<th>Date</th>");
	print("<th>Scheduled Start Time</th>");
	print("<th>Scheduled End Time</th>");
	print("<th>Checkout Time</th>");
	print("<th>Check-in Time</th>");
	print("<th>Room</th>");
	print("<th>Status</th>");
	print("<th>&nbsp;</th>");
	print("</tr></thead>");
	print("<tbody>");
	foreach($completed as $reservation)
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
	print("<h3 style='margin-left:30px'>You do not have any reservation history.</h3><br>\n");



	
require_once("includes/footer.php");

?>