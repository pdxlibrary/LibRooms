<?php

session_start();
require_once("config/config.inc.php");
require_once("includes/Database.php");

require_once("load.php");
require_once("includes/email_communication.php");
require_once("includes/verify_access.php");
restrict_access($db,array("admin"));
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

print("<div id='PageTitle'>Edit Reservation</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Edit Reservation</div>\n");

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
		
	if(isset($_GET['override_checkout_date']) && isset($_GET['override_checkout_time']) && isset($_GET['override_checkin_date']) && isset($_GET['override_checkin_time']))
	{
		$new_key_checkout_time = date("Y-m-d H:i:s",strtotime($_GET['override_checkout_date'] . " " . $_GET['override_checkout_time']));
		$new_key_checkin_time = date("Y-m-d H:i:s",strtotime($_GET['override_checkin_date'] . " " . $_GET['override_checkin_time']));
		$fields = array("key_checkout_time","key_checkin_time");
		$values = array($new_key_checkout_time,$new_key_checkin_time);
		$db->update("reservations",$fields,$values,"id like '$reservation_id'",$reservation_id,"Manual Override for Completed Reservation Checkout/Check-in Times");
		
		// reload the reservation
		$reservations = load_reservation_details($reservation_id);
		foreach($reservations as $reservation)
			break;
		
		// unset previously set hourly fines
		$db->update("fines",array("active"),array("0"),"reservation_id LIKE '$reservation_id' AND active LIKE '1' AND description LIKE 'Room key returned late'",$reservation_id,"Hourly late fines removed by manual override of checkout/checkin times");
		
		// re-assign fines
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
		
		// reload the reservation
		$reservations = load_reservation_details($reservation_id);
		foreach($reservations as $reservation)
			break;
	}
		
	$room = $reservation->room;
	
	// lookup user by user id
	$user = get_user_by_id($reservation->user_id);

	print("<blockquote>\n");
	print("<h2>Room $room->room_number ~ ");
	$start = date('m/d/Y (g:ia',strtotime($reservation->sched_start_time));
	$end = date('g:ia)',strtotime($reservation->sched_end_time));
	print("$start-$end</h2>\n");
	print("<h2>Reserved by: $user->first_name $user->last_name</h2>\n");
	print("<h2>Current Status: $reservation->status</h2>\n");
	
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
		//pr($reservation);
		
		// allow for completed reservations to be edited by admin
		print("<div style='background-color:pink; padding:10px; border-radius:10px;'>\n");
		print("<b>ADMIN: Manually override the checkout and check-in times for this completed reservation:</b>\n");
		print("<form>\n");
		$checkout_date = date("m/d/Y",strtotime($reservation->key_checkout_time));
		$checkout_time = date("H:i:s",strtotime($reservation->key_checkout_time));
		$checkin_date = date("m/d/Y",strtotime($reservation->key_checkin_time));
		$checkin_time = date("H:i:s",strtotime($reservation->key_checkin_time));
		print("<input type='hidden' name='reservation_id' value='$reservation->id'>\n");
		print("Checkout Date/Time: <input type='text' size='8' name='override_checkout_date' value='$checkout_date'>/<input type='text' size='7' name='override_checkout_time' value='$checkout_time'><br>\n");
		print("Check-in Date/Time: <input type='text' size='8' name='override_checkin_date' value='$checkin_date'>/<input type='text' size='7' name='override_checkin_time' value='$checkin_time'><br>\n");
		print("<input type='submit' value='Override'>\n");
		print("</form>\n");
		print("</div>\n");
		
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
	
	
	print("</blockquote>\n");
	print("<br>\n");
}

require_once("includes/footer.php");	

?>