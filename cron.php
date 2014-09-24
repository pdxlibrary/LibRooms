<?php

// complete scheduled tasks

session_start();
require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("includes/email_communication.php");
require_once("load.php");
require_once("includes/load_settings.php");

// cancel reservations that have not been checked out with a scheduled start time greater than RES_CANCELATION_GRACE_PERIOD minutes ago
$cutoff = date('Y-m-d H:i:s',strtotime("-".RES_CANCELATION_GRACE_PERIOD." minutes"));
$select = "select * from reservations where status like 'Scheduled' and sched_start_time < '$cutoff' and cancelled like '0' and active like '1'";
$res = $db->query($select);
while($res->fetchInto($reservation))
{
	cancel_reservation($reservation->id,$reservation->user_id,"Automatically Cancelled - No Show");
}


// send overdue notices
$options = array();
$options['active'] = 1;
$options['overdue'] = 1;
$reservations = load_reservations($options);
foreach($reservations as $reservation)
{
	if(strtotime($reservation->sched_end_time) + (FINE_GRACE_PERIOD*60) <= strtotime('now'))
	{
		// fine grace period exceeded
		
		// only send one overdue notice per day
		$notice_threshold = date("Y-m-d H:i:s",strtotime("12 hours ago"));
		$select_overdue_notices_sent = "select * from email_log where reservation_id like '$reservation->id' and type like 'Overdue Notice' and date_added >= '$notice_threshold' and active like '1'";
		$res_overdue_notices_sent = $db->query($select_overdue_notices_sent);
		if($res_overdue_notices_sent->numRows() == 0)
		{
			overdue_email($db,$reservation->id);
		}
	}
}



// sync hours
$xml_result = file_get_contents("http://library.pdx.edu/api/rest/hours/get_hours.php?num_days=".MAX_FUTURE_RES_DAYS);
//print("http://library.pdx.edu/api/rest/hours/get_hours.php?num_days=".MAX_FUTURE_RES_DAYS);
$xml = new SimpleXMLElement($xml_result);

$fields = array();
$fields["date"] = "Date";
$fields["open_time"] = "OpenTime";
$fields["close_time"] = "CloseTime";
$fields["closed"] = "Closed";
$fields["closure_reason"] = "ClosureReason";

$all_hours = array();


foreach ($xml->Days->Day as $day)
{
	foreach($fields as $db_field => $xml_field)
		$day_obj->$db_field = ((string)$day->$xml_field);
	
	$all_hours[] = $day_obj;
	unset($day_obj);
}

foreach($all_hours as $hours)
{
	if(strtotime($hours->close_time) < strtotime($hours->date . " 6:00am")) // assume we won't close so early, so it must be the next day
	{
		// close after midnight
		$tomorrow = date('Y-m-d',strtotime("tomorrow",strtotime($hours->date)));
		$close_time = date('Y-m-d H:i:s',strtotime("+1 day",strtotime($hours->close_time)));
	}
	else
		$close_time = $hours->close_time;

	$existing_hours_res = load_hours_for_date($hours->date);
	if(count($existing_hours_res) == 0)
	{
		// hours for this day have never been loaded --> insert
		$fields = array('date','open_time','close_time','closed','closure_reason','date_added');
		$values = array($hours->date,$hours->open_time,$close_time,$hours->closed,$hours->closure_reason,date('Y-m-d H:i:s',strtotime('now')));
		$db->insert("hours",$fields,$values);
	}
	else if(count($existing_hours_res) == 1)
	{
		foreach($existing_hours_res as $existing_hours)
			break;
			
		// TODO: if time change conflicts with any reservations, do something: notify staff/admin, notify patron, automatically change/cancel reservation
		
		// hours exist already, if they are different --> update
		if(strcmp($existing_hours->open_time,$hours->open_time))
			$db->update("hours","open_time",$hours->open_time,"id like '$existing_hours->id'",$existing_hours->id,"Opening time changed");
		if(strcmp($existing_hours->close_time,$close_time))
			$db->update("hours","close_time",$close_time,"id like '$existing_hours->id'",$existing_hours->id,"Closing time changed");
		if(strcmp($existing_hours->closed,$hours->closed))
			$db->update("hours","closed",$hours->closed,"id like '$existing_hours->id'",$existing_hours->id,"Closure status changed");
		if(strcmp($existing_hours->closure_reason,$hours->closure_reason))
			$db->update("hours","closure_reason",$hours->closure_reason,"id like '$existing_hours->id'",$existing_hours->id,"Closure reason changed");
	}
}


?>