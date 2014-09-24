<?php

	session_start();

	require_once("config/config.inc.php");
	require_once("includes/functions.inc.php");
	$requires_authentication = true;
	require_once("includes/authentication.inc.php");
		
	$page_title = "Detail";
	require_once(THEME."header.php");
	
	$reservation_id = $_GET['reservation_id'];
	
	$reservations = api("load_reservation_details",array("id"=>$reservation_id));
	
	
	foreach($reservations as $reservation)
		break;
		
	// print_r($reservation);
	$room = $reservation->room;
	
	?>
	<ul data-role="listview">
		<li>
			<table style="color:#FFF; font-size:24px; font-weight:bold;">
			<tr><td>Room:</td><td><?php print(ltrim($room->room_number,"0")); ?></td></tr>
			<tr><td>Date:</td><td><?php print(date("m/d/Y",strtotime($reservation->date))); ?></td></tr>
			<tr><td>Start:</td><td><?php print(date("g:ia",strtotime($reservation->sched_start_time))); ?></td></tr>
			<tr><td>End:</td><td><?php print(date("g:ia",strtotime($reservation->sched_end_time))); ?></td></tr>
			<tr><td>Status:</td><td><?php print($reservation->status); ?></td></tr>
			</table>
		</li>
	</ul>
	<p style="text-align:center">
	<?php
		// if reservation has a status of scheudled, then it can be cancelled or rescheduled
		if(!strcmp($reservation->status,'Scheduled'))
		{
			print("<a href='cancel_reservation.php?reservation_id=$reservation->id' data-role='button' data-inline='true' data-icon='delete' data-rel='dialog'>Cancel</a>");
			print("<a href='index.php?reservation_id=$reservation->id' data-role='button' data-inline='true' data-icon='back' data-rel='dialog' data-ajax='false'>Reschedule</a>");
		}	
	?>
		
	</p>

	<?php
	
	require_once(THEME."footer.php");
	

?>