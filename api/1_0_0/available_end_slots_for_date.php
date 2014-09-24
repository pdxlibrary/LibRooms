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

if(isset($_GET['start_time']))
{
	$start_time = $_GET['start_time'];
}

$hours = load_hours($db,$date);
//pr($hours);

$available_slots = $hours->slots;
$count_slots = count($available_slots);

// add the end time at the end of the day to the list
$available_slots[] = date("Y-m-d H:i:s",strtotime("+".RES_PRECISION." minutes",strtotime($available_slots[$count_slots-1])));
$count_slots++;

if(isset($_GET['start_time']))
{
	$start_time = $_GET['start_time'];
	// remove slots earlier than start time + minimum reservation length
	if(strtotime($available_slots[0]) < strtotime("+".DEFAULT_MIN_RES_LEN." minutes",strtotime($start_time)))
	{
		foreach($available_slots as $id => $slot)
		{
			if(strtotime($slot) < strtotime("+".DEFAULT_MIN_RES_LEN." minutes",strtotime($start_time)))
			{
				unset($available_slots[$id]);
			}
		}
	}
}
else
{
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
}

sort($available_slots);

// remove slots longer than the max reservation time
$max_len_slots = DEFAULT_MAX_RES_LEN / RES_PRECISION;
$available_slots = array_slice($available_slots,0,($max_len_slots-1));


$times = array();
foreach($available_slots as $slot)
{
	$times[] = date("g:i A",strtotime($slot));
}

$result->available_slots = $available_slots;
$result->times = $times;
print(json_encode($result));

?>
