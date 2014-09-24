<?php

session_start();
require_once("config/config.inc.php");
require_once("config/strings.inc.php");
require_once("includes/Database.php");
require_once("load.php");

require_once("includes/verify_access.php");
restrict_access($db,array("admin"));

require_once("includes/header.php");

$room_id = $_GET['room_id'];

print("<div id='PageTitle'>Delete Room</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Delete Room</div>\n");
print("<br>\n");


$rooms = load_rooms(array($room_id));
if(count($rooms) == 0)
{
	display_error("Room could not be found.",array("room_id"=>$room_id));
}
else if(count($rooms) > 1)
{
	display_error("Multiple rooms with the same id.",$rooms);
}
else
{
	
	foreach($rooms as $room)
		break;
	
	// make sure there are no reservations set for this room
	$active_reservations = load_reservations(array('room_id'=>$room->id,'status'=>array('Scheduled','Checked Out')));
	if(count($active_reservations) > 0)
	{
		display_error("This room has active reservations that must be completed, cancelled or rescheduled before the room can be deleted.",$active_reservations);
	}
	else
	{
		$db->update("study_rooms","active","0","id like '$room_id'",$room_id,"Room deleted");
		// TODO: check result to make sure the update was successful
		print("<h3>Room $room->room_number has been successfully deleted.</h3>\n");
	}

}



require_once("includes/footer.php");

?>