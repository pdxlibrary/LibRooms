<?php


if(MAX_RESERVATION_HOURS_PER_DAY == 99999999 && MAX_RESERVATION_HOURS_PER_WEEK == 99999999)
{
	// there are no credit hour limits, don't show the widget
}
else
{
	print("<b>Available Reservation Hours</b>\n");
	print("<table cellspacing=5 style='width:214px; border:1px dotted #000; padding:4px; border-radius:5px;'>\n");


	if(isset($_SESSION['LibRooms']['Roles']) && (in_array('Admin',$_SESSION['LibRooms']['Roles']) || in_array('Staff',$_SESSION['LibRooms']['Roles'])) && strcmp($user_id,''))
	{
		if(!strcmp($selected_date,''))
			$selected_date = date("Y-m-d");
			
		$user = get_user_by_id($user_id);
		$credits = get_reservation_credits($user_id,$selected_date);
		
		if(strtotime($selected_date) == strtotime(date('Y-m-d')))
			$day = "today";
		else
			$day = "on " . date('m/d/Y',strtotime($selected_date));
		
		if(strtotime($credits->week_window_start) > strtotime(date('Y-m-d')))
			$week = "for the week of (".date('m/d',strtotime($credits->week_window_start))."-".date('m/d',strtotime("1 day ago",strtotime($credits->week_window_end))).")";
		else
			$week = "this week";
		
		if(MAX_RESERVATION_HOURS_PER_DAY != 99999999)
			print("<tr style='height:50px'><td align='center' style='background-color:#666; border-radius:10px; color:#FFF; padding:8px;'><div style='font-size:24px'>$credits->day_hours_remaining</div><div style='font-size:12px'>(day)</div></td><td style='font-size:10px'>$user->first_name $user->last_name has $credits->day_hours_remaining of ".MAX_RESERVATION_HOURS_PER_DAY." hours remaining $day.</td></tr>\n");
		if(MAX_RESERVATION_HOURS_PER_WEEK != 99999999)
			print("<tr style='height:50px'><td align='center' style='background-color:#666; border-radius:10px; color:#FFF; padding:8px;'><div style='font-size:24px'>$credits->week_hours_remaining</div><div style='font-size:12px'>(week)</div></td><td style='font-size:10px'>$user->first_name $user->last_name has $credits->week_hours_remaining of ".MAX_RESERVATION_HOURS_PER_WEEK." hours remaining $week.</td></tr>\n");
		
	}
	else if(strcmp($_SESSION['LibRooms']['UserID'],''))
	{
		if(!strcmp($selected_date,''))
			$selected_date = date("Y-m-d");
			
		$credits = get_reservation_credits($_SESSION['LibRooms']['UserID'],$selected_date);
		
		if(strtotime($selected_date) == strtotime(date('Y-m-d')))
			$day = "today";
		else
			$day = "on " . date('m/d/Y',strtotime($selected_date));
		
		if(strtotime($credits->week_window_start) > strtotime(date('Y-m-d')))
			$week = "for the week of (".date('m/d',strtotime($credits->week_window_start))."-".date('m/d',strtotime("1 day ago",strtotime($credits->week_window_end))).")";
		else
			$week = "this week";
		
		if(MAX_RESERVATION_HOURS_PER_DAY != 99999999)
			print("<tr style='height:50px'><td align='center' style='background-color:#666; border-radius:10px; color:#FFF; padding:8px;'><div style='font-size:24px'>$credits->day_hours_remaining</div><div style='font-size:12px'>(day)</div></td><td style='font-size:10px'>You have $credits->day_hours_remaining of ".MAX_RESERVATION_HOURS_PER_DAY." hours remaining $day.</td></tr>\n");
		if(MAX_RESERVATION_HOURS_PER_WEEK != 99999999)
			print("<tr style='height:50px'><td align='center' style='background-color:#666; border-radius:10px; color:#FFF; padding:8px;'><div style='font-size:24px'>$credits->week_hours_remaining</div><div style='font-size:12px'>(week)</div></td><td style='font-size:10px'>You have $credits->week_hours_remaining of ".MAX_RESERVATION_HOURS_PER_WEEK." hours remaining $week.</td></tr>\n");
		
		print("<tr><td align='center' colspan='2' style='font-size:10px'><a href='http://library.pdx.edu/study-spaces-computers/study-rooms-faqs/' target='_blank'>More about Reservation Limits</a>\n");
	}
	else
	{
		print("<tr style='height:50px'><td align='center' style='font-size:13px;'><a href='?login'>Login</a> to view your available reservation hours.</td></tr>\n");
		print("<tr><td align='center' colspan='2' style='font-size:10px'><a href='http://library.pdx.edu/study-spaces-computers/study-rooms-faqs/' target='_blank'>More about Reservation Limits</a>\n");
	}

	
	print("</table>\n");
}

?>

