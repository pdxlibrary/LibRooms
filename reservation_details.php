<?php

session_start();
require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("load.php");
require_once("includes/load_settings.php");
require_once("includes/email_communication.php");

require_once("includes/verify_access.php");
restrict_access($db,array("patron"));

if(isset($_SESSION['LibRooms']['Roles']))
{
	if(in_array('Staff',$_SESSION['LibRooms']['Roles']))
		$user_type = "staff";
	if(in_array('Admin',$_SESSION['LibRooms']['Roles']))
		$user_type = "admin";
}

$reservation_id = $_GET['reservation_id'];

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
		var status_history_table = $('#status_history_table').dataTable({
			"bJQueryUI": true,
			"sPaginationType": "full_numbers",
			"aaSorting": [[ 0, "asc" ]],
			"aoColumns": [
				{ },
				{ "bSortable": false},
				{ "bSortable": false}
			]
		});
		
		var fines_table = $('#fines_table').dataTable({
			"bJQueryUI": true,
			"sPaginationType": "full_numbers",
			"aaSorting": [[ 0, "desc" ]],
			"aoColumns": [
				{ "bSortable": false},
				{ "bSortable": false},
				{ "bSortable": false}
			]
		});
		
		<?php
			// reduce fine feature
			if(!strcmp($user_type,'admin') || !strcmp($user_type,'staff'))
			{
		?>
				$( "#reduce_dialog" ).dialog({
					width: 600,
					height: 550,
					autoOpen: false,
					modal: true,
					closeText: 'hide',
					beforeClose: function(event, ui) { document.location.href='?<?php print($_SERVER['QUERY_STRING']); ?>'; }
				});
		<?php
			}
		?>
		
	});
	
	
<?php
	// reduce fine feature
	if(!strcmp($user_type,'admin') || !strcmp($user_type,'staff'))
	{
?>	
		function open_reduce_fine_dialog(fine_id)
		{
			$("#reduce_dialog").html("<iframe style='border: 0px;' SRC='fine_reduction.php?fine_id="+fine_id+"' width='100%' height='100%'></iframe>");
			$("#reduce_dialog").dialog('open');
		}
		
		function close_reduce_fine_dialog()
		{
			$("#reduce_dialog").dialog("close");
			$("#reduce_dialog").html("");
		}
<?php
	}
?>

</script>
<link rel="stylesheet" href="css/jquery-ui.css" type="text/css" media="all" />
<link rel="stylesheet" href="css/results_table.css" type="text/css" media="all" />
<style>
.dataTable td {padding:0;}
.dataTable th {padding:0;}
</style>

<?php

if(!strcmp($user_type,'admin') || !strcmp($user_type,'staff'))
{
	// reduce fine popup
	print("<div id='reduce_dialog' title='Reduce Fine'>\n</div>\n");
}

print("<div id='PageTitle'>Reservation Details</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Reservation Details</div>\n");

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
	$room = $reservation->room;
	if(!strcmp($user_type,'admin') || !strcmp($user_type,'staff'))
		$user_id = $reservation->user_id;
	else
		$user_id = $_SESSION['LibRooms']['UserID'];

	// lookup user by user id
	$user = get_user_by_id($user_id);
	
	print("<blockquote>\n");
	print("<h2>Room $room->room_number ~ ");
	$start = date('m/d/Y (g:ia',strtotime($reservation->sched_start_time));
	$end = date('g:ia)',strtotime($reservation->sched_end_time));
	print("$start-$end</h2>\n");
	print("<h2>Reserved by: $user->first_name $user->last_name</h2>\n");
	print("<h2>Current Status: $reservation->status");
	if(!strcmp($reservation->status,'Checked Out') && !strcmp($user_type,'admin'))
		print(" (Key: $reservation->key_barcode)");
	print("</h2>\n");
	
	print("</blockquote>\n");
	if(!strcmp($reservation->status,'Scheduled'))
	{
		print_checkout_form($reservation,"reservation_details.php?reservation_id=$reservation->id");
	}
	else if(!strcmp($reservation->status,'Checked Out'))
	{
		if(!strcmp($user_type,'admin') || !strcmp($user_type,'staff'))
			print_checkin_form($reservation,$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']);
	}
	else if(!strcmp($reservation->status,'Completed'))
	{
		if(!strcmp($user_type,'admin'))
			print("<a class='editable' style='margin-left: 40px;' href='edit_reservation.php?reservation_id=$reservation->id'>Edit Completed Reservation</a><br>\n");
	}
	
	print("<br>\n");
	
	if(!strcmp($reservation->status,'Cancelled'))
	{
		print("<div class='cancellation_block'>\n");
		print("<h2>Cancellation Details</h2>\n");
		foreach($reservation->status_history as $sh)
		{
			if(!strcmp($sh->status,'Cancelled'))
				break;
		}
		print("<blockquote>\n");
		print("Date Cancelled: " . date('m/d/Y g:i:sa',strtotime($sh->date)) . "<br>\n");
		if(!strcmp($user_type,'admin') || !strcmp($user_type,'staff') || $sh->changed_by == $_SESSION['LibRooms']['UserID'])
		{
			$user = get_user_by_id($sh->changed_by);
			$cancelled_by = $user->first_name . " " . $user->last_name;
		}
		else
			$cancelled_by = "PSU Staff";
		print("Cancelled By: $cancelled_by<br>\n");
		if(strcmp($reservation->cancellation_reason,''))
			print("Cancellation Reason: $reservation->cancellation_reason<br>\n");
		print("</blockquote>\n");
		print("</div><br>\n");
	}
	
	
	
	print_fines_table($reservation);
	print("<br>\n");
		
	print("<h2>Status History</h2>\n");
	print("<br><table id='status_history_table' width='850' border='1'>");
	print("<thead><tr>");
	print("<th>Date</th>");
	print("<th>Status</th>");
	print("<th>Changed By</th>");
	print("</tr></thead>");
	print("<tbody>\n");
	foreach($reservation->status_history as $sh)
	{
		print("<tr>\n");
		$date = date('m/d/Y g:i:sa',strtotime($sh->date));
		if(!strcmp($user_type,'admin') || !strcmp($user_type,'staff') || $sh->changed_by == $_SESSION['LibRooms']['UserID'])
		{
			$user = get_user_by_id($sh->changed_by);
			$changed_by = $user->first_name . " " . $user->last_name;
		}
		else
			$changed_by = "PSU Staff";
		
		print("<td align='center'>$date</td>");
		print("<td align='center'>$sh->status</td>");
		print("<td align='center'>$changed_by</td>");
		print("</tr>\n");
	}
	print("</tbody></table><br><br>\n");
	
	
	print("<br>\n");
	
	
	// pr($reservation);
	
	
}

require_once("includes/footer.php");	

?>