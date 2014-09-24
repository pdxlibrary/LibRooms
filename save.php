<?php

session_start();

require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("load.php");

// Authentication
require_once("includes/verify_access.php");
restrict_access($db,array("admin"));

$id = $_POST['id'];
if(isset($_POST['field']))
	$field = $_POST['field'];
else
	$field = $id;
	
$value = $_POST['value'];
$table = $_POST['table'];
$row_id = $_POST['row_id'];
$reservation_id = $_POST['reservation_id'];

$out = "";
foreach($_POST as $var => $val)
{
	$out .= $var.":".$val."\n";
}


// display the formatted saved value
if(!strcmp($table,'study_rooms') && !strcmp($field,'room_group_id'))
{
	$all_room_groups = load_room_groups();
	$room_group_options = array();
	foreach($all_room_groups as $room_group)
	{
		$room_group_options[$room_group->id] = $room_group->name;
	}
	print($room_group_options[$value]);
}
else
	print($value);

// complete the update
if(isset($_POST['row_id']))
	$db->update($table,$field,$value,"id like '$row_id'");
else if(isset($_POST['reservation_id']))
	$db->update($table,$field,$value,"reservation_id like '$reservation_id'");

?>