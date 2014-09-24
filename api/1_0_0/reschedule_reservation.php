<?php

require_once("../../load.php");

$original_reservation_id 	= $_GET['original_reservation_id'];
$room_id 					= $_GET['room_id'];
$room_section 				= $_GET['room_section'];
$user_id 					= $_GET['user_id'];
$selected_date 				= $_GET['selected_date'];
$start_time 				= $_GET['start_time'];
$end_time 					= $_GET['end_time'];

print(json_encode(reschedule_reservation($original_reservation_id,$room_id,$room_section,$user_id,$selected_date,$start_time,$end_time)));


?>