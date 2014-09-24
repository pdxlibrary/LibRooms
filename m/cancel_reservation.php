<?php

	session_start();

	require_once("config/config.inc.php");
	require_once("includes/functions.inc.php");
	$requires_authentication = true;
	require_once("includes/authentication.inc.php");
	
	$reservation_id = $_GET['reservation_id'];
	
	$reservations = api("load_reservation_details",array("id"=>$reservation_id));
	
	
	foreach($reservations as $reservation)
		break;
		
	// print_r($reservation);
	$room = $reservation->room;
	
?>

<div data-role="dialog">
	<div data-role="header">
		<h1>Cancel</h1>
	</div>
	<div data-role="content">
		<h2>Are you sure you want to cancel this reservation?</h2>
		<ul data-role="listview">
			<li>
				<table>
				<tr><td>Room:</td><td><?php print(ltrim($room->room_number,"0")); ?></td></tr>
				<tr><td>Date:</td><td><?php print(date("m/d/Y",strtotime($reservation->date))); ?></td></tr>
				<tr><td>Start:</td><td><?php print(date("g:ia",strtotime($reservation->sched_start_time))); ?></td></tr>
				<tr><td>End:</td><td><?php print(date("g:ia",strtotime($reservation->sched_end_time))); ?></td></tr>
				<tr><td>Status:</td><td><?php print($reservation->status); ?></td></tr>
				</table>
			</li>
		</ul>
		<p>
			<a href="account.php?cancel_reservation=<?php print($reservation->id); ?>" data-role="button" data-ajax="false" data-inline="true">Yes</a>
			<a href="#" data-role="button" data-rel="back" data-inline="true">No</a>
		</p>
	</div>
</div>
