<?php

require_once("../../load.php");

// TODO: input validation

if(isset($_GET['date']) && strtotime($_GET['date']) > 0)
{	
	$date = date("Y-m-d",strtotime($_GET['date']));;
}
else
{
	$result->error = "No Date Provided";
	print(json_encode($result));
	exit();
}

$hours = load_hours($db,$date);
//pr($hours);

$available_slots = $hours->slots;
$count_slots = count($available_slots);

// remove slots in the past
if(strtotime($available_slots[0]) < strtotime('now'))
{
	foreach($available_slots as $id => $slot)
	{
		if(strtotime($slot) < strtotime('now'))
		{
			unset($available_slots[$id]);
		}
	}
}

// remove slots that are too late for a reservation that meets the minimum length requirement
$slots_to_remove = (DEFAULT_MIN_RES_LEN/RES_PRECISION)-1;
$index = $count_slots-1;
for($i=0;$i<$slots_to_remove;$i++)
{
	unset($available_slots[$index]);
	$index--;
}
sort($available_slots);

$times = array();
foreach($available_slots as $slot)
{
	$times[] = date("g:i A",strtotime($slot));
}

$result->available_slots = $available_slots;
$result->times = $times;
print(json_encode($result));

?>
