<?php

	session_start();

	require_once("config/config.inc.php");
	require_once("includes/functions.inc.php");

	$page_title = "Success!";
	require_once(THEME."header.php");
	
	if(isset($_GET['reservation_id']))
	{
		$reservation_id = $_GET['reservation_id'];
		print("<h1>Room Reserved!</h1>\n");
		print("<h3>You will receive an email confirmation shortly. Please bring a photo ID to the circulation desk on the first floor of the library to checkout the key for your reserved study room.</h3>\n");
		print("<a href='account.php' data-role='button' data-ajax='false'>View My Reservations</a>\n");
		print("<a href='./' data-role='button' data-ajax='false'>Make Another Reservation</a>\n");
	}
	else
	{
		// unknown reservation id
		print("<h1>ERROR: Unknown Reservation ID</h1>\n");
	}


	require_once(THEME."footer.php");

?>
