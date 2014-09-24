<?php

function load_hours($db,$selected_date="")
{
	// TODO: validate the format of the selected_date input

	if(strcmp($selected_date,''))
	{
		$select_todays_hours = "select * from hours where date like '$selected_date' AND active like '1' ORDER BY id DESC LIMIT 1";
	}
	else
	{
		$now = date('Y-m-d H:i:s',strtotime('now'));
		$select_todays_hours = "select * from hours where open_time <= '$now' AND close_time >= '$now' AND active like '1' ORDER BY id DESC LIMIT 1";
	}
	
	//print("select: $select_todays_hours<br>\n");
	$res = $db->query($select_todays_hours);
	if($res->numRows() == 1)
	{
		// hours located
		$res->fetchInto($hours);
		
		// determine the number of timeslots for this day
		$slots = array();
		if($hours->closed == 0)
		{
			for($i=strtotime($hours->open_time);$i<strtotime($hours->close_time);$i+=(RES_PRECISION*60))
			{
				$slot = date("Y-m-d H:i:s",$i);
				$slots[] = $slot;
			}
			$hours->slots = $slots;
		}
		
		return($hours);
	}
	else
		return(false);
}

?>

