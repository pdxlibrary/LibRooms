<?php

session_start();

require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("includes/verify_access.php");
restrict_access($db,array("admin"));

require_once("load.php");

if(isset($_GET['add_new_room']))
{
	// create a new room and forward to the edit screen
	$fields = array('out_of_order','date_added');
	$values = array('No',date('Y-m-d H:i:s'));
	$new_room_id = $db->insert("study_rooms",$fields,$values);
	header("location: room_details.php?room_id=$new_room_id");
}

$rooms = load_rooms(null,array('sort asc'=>'room_number'));
$all_amenities = load_amenities();



if(isset($_SESSION['LibRooms']['Roles']))
{
	if(in_array('Staff',$_SESSION['LibRooms']['Roles']))
		$user_type = "staff";
	if(in_array('Admin',$_SESSION['LibRooms']['Roles']))
		$user_type = "admin";
}

require_once("includes/header.php");


?>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		
	});
</script>

<?php

print("<div id='PageTitle'>Manage Rooms</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Manage Rooms</div>\n");

print("<br>\n");

print("<form><input style='float:right' type='submit' name='add_new_room' value='+ Add New Room'></form><br>\n");

print("<table width='100%' border><tr><th>Room Number</th><th>Capacity</th><th>First-Come First-Serve</th><th>Status</th><th>&nbsp;</th></tr>\n");
foreach($rooms as $room)
{
	if(!strcmp($room->out_of_order,'Yes'))
		$status = "<td align='center' bgcolor='red' style='color:#FFF'>Out of Order</td>\n";
	else
		$status = "<td align='center' bgcolor='green' style='color:#FFF'>Good</td>\n";
	print("<tr><td><a href='room_details.php?room_id=$room->id'>Room $room->room_number</a></td><td align='center'>$room->capacity</td><td align='center'>$room->fcfs</td>$status<td><a href='room_details.php?room_id=$room->id'>Edit</a></td></tr>\n");
}
print("</table>\n");



require_once("includes/footer.php");

?>

