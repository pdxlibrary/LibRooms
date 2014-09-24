<?php

require_once("Mail.php");
require_once("Mail/mime.php");

define("EMAIL_ON",true);

function reservation_confirmation_email($reservation_id)
{
	global $db;
	
	// use reservation_id to lookup email address and reservation info
	$reservations = load_reservation_details($reservation_id);
	foreach($reservations as $reservation)
		break;
		
	$user = get_user_by_id($reservation->user_id);
	
	$rooms = load_rooms(null,array('id'=>$reservation->room_id));
	foreach($rooms as $room)
		break;
	
	$from = NOTICE_EMAIL_ADDRESS;
	$reply_to = NOTICE_EMAIL_ADDRESS;
	$recipient = $user->email;
	
	$subject = "PSU Library - Study Room Reservation Confirmation";
	
	// check to see if there is an override template for this room or room group
	if(file_exists("http://".$_SERVER["HTTP_HOST"].WEB_ROOT."email_templates/rooms/$room->room_id/reservation_confirmation.txt"))
		$template = file_get_contents("http://".$_SERVER["HTTP_HOST"].WEB_ROOT."email_templates/rooms/$room->room_id/reservation_confirmation.txt");
	else if(file_exists("http://".$_SERVER["HTTP_HOST"].WEB_ROOT."email_templates/room_groups/$room->room_group_id/reservation_confirmation.txt"))
		$template = file_get_contents("http://".$_SERVER["HTTP_HOST"].WEB_ROOT."email_templates/room_groups/$room->room_group_id/reservation_confirmation.txt");
	else
		$template = file_get_contents("http://".$_SERVER["HTTP_HOST"].WEB_ROOT."email_templates/reservation_confirmation.txt");

	$body = set_template_tokens($template,$reservation,$user,$room);
	
	$result = send_email($reservation->id,"Reservation Confirmation",$from,$reply_to,$recipient,$subject,$body);
	
	return($result);
}


function reservation_cancellation_email($db,$reservation_id)
{
	// use reservation_id to lookup email address and reservation info
	$reservations = load_reservation_details($reservation_id);
	foreach($reservations as $reservation)
		break;
		
	$user = get_user_by_id($reservation->user_id);
	
	$rooms = load_rooms(null,array('id'=>$reservation->room_id));
	foreach($rooms as $room)
		break;
	
	$from = NOTICE_EMAIL_ADDRESS;
	$reply_to = NOTICE_EMAIL_ADDRESS;
	$recipient = $user->email;
	
	$subject = "PSU Library - Study Room Reservation Cancellation";
	$template = set_template_tokens(file_get_contents("http://".$_SERVER["HTTP_HOST"].WEB_ROOT."email_templates/reservation_cancellation.txt"),$reservation,$user,$room);
	
	$result = send_email($reservation->id,"Reservation Cancellation",$from,$reply_to,$recipient,$subject,$template);
	
	return($result);
}


function overdue_email($db,$reservation_id)
{
	// use reservation_id to lookup email address and reservation info
	$reservations = load_reservation_details($reservation_id);
	foreach($reservations as $reservation)
		break;
		
	$user = get_user_by_id($reservation->user_id);
	
	$rooms = load_rooms(null,array('id'=>$reservation->room_id));
	foreach($rooms as $room)
		break;

	$from = NOTICE_EMAIL_ADDRESS;
	$reply_to = NOTICE_EMAIL_ADDRESS;
	$recipient = $user->email;
	
	$subject = "PSU Library - Study Room Overdue Notice";
	$template = set_template_tokens(file_get_contents("http://".$_SERVER["HTTP_HOST"].WEB_ROOT."email_templates/overdue.txt"),$reservation,$user,$room);
	
	$result = send_email($reservation->id,"Overdue Notice",$from,$reply_to,$recipient,$subject,$template);
	
	return($result);
}

function lost_key_email($db,$reservation_id)
{
	// use reservation_id to lookup email address and reservation info
	$reservations = load_reservation_details($reservation_id);
	foreach($reservations as $reservation)
		break;
		
	$user = get_user_by_id($reservation->user_id);
	
	$rooms = load_rooms(null,array('id'=>$reservation->room_id));
	foreach($rooms as $room)
		break;

	$from = NOTICE_EMAIL_ADDRESS;
	$reply_to = NOTICE_EMAIL_ADDRESS;
	$recipient = $user->email;
	
	
	$subject = "PSU Library - Study Room Overdue FINAL Notice";
	$template = set_template_tokens(file_get_contents("http://".$_SERVER["HTTP_HOST"].WEB_ROOT."email_templates/lost_key.txt"),$reservation,$user,$room);
	
	$result = send_email($reservation->id,"Lost Key",$from,$reply_to,$recipient,$subject,$template);
	
	return($result);
}

function set_template_tokens($template,$reservation,$user=null,$room=null)
{
	$template = str_replace("{reservation_calendar_url}","http://".$_SERVER["HTTP_HOST"].WEB_ROOT,$template);
	$template = str_replace("{room_id}",$reservation->id,$template);
	$template = str_replace("{room_number}",$room->room_number,$template);
	$template = str_replace("{patrons_first_name}",$user->first_name,$template);
	$template = str_replace("{patrons_last_name}",$user->last_name,$template);
	$template = str_replace("{reservation_id}",$reservation->id,$template);
	$template = str_replace("{reservation_date}",date('m/d/Y',strtotime($reservation->date)),$template);
	$template = str_replace("{reservation_start_time}",date('g:ia',strtotime($reservation->sched_start_time)),$template);
	$template = str_replace("{reservation_end_time}",date('g:ia',strtotime($reservation->sched_end_time)),$template);
	if(strcmp($reservation->cancellation_reason,''))
		$template = str_replace("{cancellation_reason}","Cancellation Reason: ".$reservation->cancellation_reason,$template);
	$template = str_replace("\n","<br>",$template);
	
	return($template);
}

function send_email($reservation_id,$type,$from,$reply_to,$recipient,$subject,$message,$html_message="")
{
	global $db;
	
	if(!strcmp(trim($recipient),''))
	{
		$recipient = "mflakus@pdx.edu";
		$subject = $subject;
	}
	
	$headers = array('To'=>$recipient,'From'=> $from,'Return-Path'=> $reply_to,'Subject'=> $subject);

	$html_body = $message;
	$body = strip_tags(nl2br($html_body));
	
	$mime = new Mail_mime(PHP_EOL);
	$mime->setTXTBody($body);
	$mime->setHTMLBody($html_body);
	$body = $mime->get();
	$headers = $mime->headers($headers);

	// TODO: add config.php setting for selecting and configuring mail settings
	
	// smtp parameters
	$params['host'] = SMTP_HOST;

	// Create the mail object using the Mail::factory method
	$mail =& Mail::factory('smtp', $params);
	if(EMAIL_ON)
		$mail->send($recipient, $headers,  $body);
	
	// TODO: check for error when sending email
	
	
	// Log email sent to patron
	$fields = array('reservation_id','type','recipient','subject','body','date_added');
	$values = array($reservation_id,$type,$recipient,$subject,$message,date("Y-m-d H:i:s"));
	$db->insert("email_log",$fields,$values);
	
	return(true);

}

?>