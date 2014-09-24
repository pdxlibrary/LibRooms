<?php

session_start();

require_once("config/config.inc.php");
require_once("includes/Database.php");

require_once("load.php");

$room_id = $_GET['room_id'];
$rooms = load_rooms($room_id);
$room = $rooms[$room_id];

require_once("includes/verify_access.php");
restrict_access($db,array("admin"));

if(isset($_SESSION['LibRooms']['Roles']))
{
	if(in_array('Staff',$_SESSION['LibRooms']['Roles']))
		$user_type = "staff";
	if(in_array('Admin',$_SESSION['LibRooms']['Roles']))
		$user_type = "admin";
}


if(isset($_GET['submitted']))
{
	if(isset($_GET['new_key']) && strcmp(trim($_GET['new_key']),''))
	{
		$new_key = $_GET['new_key'];
		
		$table = "rooms_keys";
		$fields = array('key_barcode','status','active');
		$values = array($new_key,'Available','1');
		
		if($db->noDuplicate($table,$fields,$values))
		{
			$fields[] = 'room_id';
			$values[] = $room_id;
			$fields[] = 'date_added';
			$values[] = date('Y-m-d H:i:s');
			$key_id = $db->insert($table,$fields,$values);
			
			// TODO: actually check the result to make sure the db change worked
			print("<font color='green'>Key [$new_key] successfully added to room.</font><br>\n");
		}
		else
		{
			print("ERROR: Key [$new_key] already exists.<br>\n");
			// TODO: display which room this key is assigned to
		}
	}
	
	// reload room
	$rooms = load_rooms($room_id);
	$room = $rooms[$room_id];
}



if(isset($_GET['remove_key']))
{
	$remove_amenity = $_GET['remove_key'];
	$db->update('rooms_keys','active','0',"id like '$remove_amenity'");
	$rooms = load_rooms($room_id);
	$room = $rooms[$room_id];
	
	// TODO: actually check the result to make sure the db change worked
	print("<font color='green'>Key successfully removed from room.</font><br>\n");
}

print("<form>\n");
print("<input type='hidden' name='submitted' value='1'>\n");
print("<input type='hidden' name='room_id' value='$room_id'>\n");

print("<h2>Active Keys</h2>\n");

foreach($room->keys as $key)
{
	print("$key->key_barcode <a href='?room_id=$room_id&remove_key=$key->id'><img src='images/icon_remove.gif' border='0'></a><br>\n");
}

print("<hr>\n");

print("<h2>Add a New Key</h2>\n");
print("<table>");
print("<tr><td>Key Barcode:</td><td><input name='new_key' size='15'></td></tr>\n");
print("</table><br>\n");
print("<input type='submit' value='Add New Key'>\n");

print("</form>\n");

?>