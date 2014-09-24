<?php

	session_start();
	
	if(isset($_GET['debug']))
		print_r($_SESSION);

	require_once("config/config.inc.php");
	require_once("includes/functions.inc.php");
	$requires_authentication = true;
	require_once("includes/authentication.inc.php");
	
	if(isset($_GET['cancel_reservation']))
	{
		$cancel_result = api("cancel_reservation",array("reservation_id"=>$_GET['cancel_reservation'],"user_id"=>$_SESSION['LibRooms']['UserID'],"reason"=>"Manually cancelled by patron"));
		$_SESSION['LibRooms']['FlashMessage'] = "Your reservation has been successfully cancelled";
		header("location: account.php");
		exit();
	}
	
		
	$page_title = "My Account";
	require_once(THEME."header.php");

	// load user object
	$user = api("get_user_by_id",array("id"=>$_SESSION['LibRooms']['UserID']));
	// print_r($user);
?>
	<div data-theme="a" data-content-theme="a">
		<h3>Currently Checked Out</h3>	
<?php
	if(count($user->reservations->{"Checked Out"}) > 0)
	{
		// display upcoming reservations
		print("<ul data-role='listview' data-inset='true'>\n");
		foreach($user->reservations->{"Checked Out"} as $reservation)
		{
			$label = "Room ".ltrim($reservation->room->room_number,"0")." - (".$reservation->room->capacity." chairs)<br />".date("m/d/y (g:ia",strtotime($reservation->sched_start_time))." - ".date("g:ia",strtotime($reservation->sched_end_time)).")";

			// check if the room is overdue
			print("<li>");
			if(strtotime($reservation->sched_end_time) < strtotime("now"))
				print("<a href='reservation_detail.php?reservation_id=$reservation->id' data-ajax='false' data-theme='b'><img src='".THEME."images/alert.png' alt='overdue' class='ui-li-icon' style='max-width:24px; max-height:24px;'>$label</a>\n");
			else
				print("<a href='reservation_detail.php?reservation_id=$reservation->id' data-ajax='false'>$label</a>\n");
			print("</li>");
		}
		print("</ul>\n");
	}
	else
	{
		print("You do not have any rooms currently checked out.");
	}
?>
	</div>
	
	<div data-theme="a" data-content-theme="a">
		<h3>Upcoming Reservations</h3>	
<?php
	
	if(count($user->reservations->Scheduled) > 0)
	{
		// display upcoming reservations
		print("<ul data-role='listview' data-inset='true'>\n");
		foreach($user->reservations->Scheduled as $reservation)
		{
			$label = "Room ".ltrim($reservation->room->room_number,"0")." - (".$reservation->room->capacity." chairs)<br />".date("m/d/y (g:ia",strtotime($reservation->sched_start_time))." - ".date("g:ia",strtotime($reservation->sched_end_time)).")";
			print("<li><a href='reservation_detail.php?reservation_id=$reservation->id' data-ajax='false'>$label</a></li>\n");
		}
		print("</ul>\n");
	}
	else
	{
		print("You do not have any upcoming reservations.");
	}
?>
	</div>

	<hr />
	<p>
		<a href="index.php?logoff" data-role="button" data-ajax="false">Logoff</a>
	</p>
	
<?php
	require_once(THEME."footer.php");
?>