<?php

require_once("../../load.php");


// TODO: input validation

if(isset($_GET['date']))
{	
	$date = date("Y-m-d",strtotime($_GET['date']));;
}

if(isset($_GET['room_id']))
{
	$room_id = $_GET['room_id'];
}

$hours = load_hours($db,$date);
pr($hours);

$available_slots = $hours->slots;

if(strtotime($available_slots[0]) < strtotime('now'))
{
	// remove hours in the past
	foreach($available_slots as $id => $slot)
	{
		if(strtotime(slot) < strtotime('now'))
			unset($available_slots[$id]);
	}
}

// remove slots that are already reserved
$options = array();
$options["status"] = array("Scheduled","Checked Out");
$options["date"] = $date;
$options["room_id"] = $room_id;
$reservations = load_reservations($options);

pr($reservations);


foreach($reservations as $reservation_id => $reservation)
{
	$timeslot_pointer = $reservation->sched_start_time;
	while(strtotime($timeslot_pointer) <= strtotime($reservation->sched_end_time))
	{
		$key = array_search($timeslot_pointer,$available_slots);
		if($key === false)
		{
			// no match
			print("no match: $timeslot_pointer");
		}
		else
		{
			// remove reserved slot
			print("removed: $key<br>\n");
			unset($available_slots[$key]);
		}
		$timeslot_pointer = date('Y-m-d H:i:s',strtotime("+".RES_PRECISION." minutes",strtotime($timeslot_pointer)));
	}
	
}

$result->available_slots = $available_slots;
pr($result);
print(json_encode($result));

?>
