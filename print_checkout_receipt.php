<?php

session_start();
require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("load.php");

require_once("includes/verify_access.php");
restrict_access($db,array("patron","staff","admin"));

$reservation_id = $_GET['reservation_id'];

$reservations = load_reservation_details($reservation_id);
$reservation = $reservations[0];
$room = $reservation->room;
$user = get_user_by_id($reservation->user_id);

//pr($reservation);
//exit();

require_once("pdf/class.ezpdf.php");


class Creport extends Cezpdf
{
	var $reportContents = array();
}

$width = 7;
$length = 9;
if(strtotime(date("Y-m-d")) == strtotime($reservation->date) && MAX_RESERVATION_HOURS_PER_DAY != 99999999)
	$length += 2;
if(strtotime(date("Y-m-d")) == strtotime($reservation->date) && MAX_RESERVATION_HOURS_PER_WEEK != 99999999)
	$length += 2;	
$pdf = new Creport(array($width,$length),'portrait');


$all = $pdf->openObject();
$mainFont = 'pdf/fonts/Times-Roman.afm';
$pdf->selectFont($mainFont);
$pdf->saveState();
$pdf->setStrokeColor(0,0,0,1);


$pdf->ezText("Portland State University Library");
$pdf->ezText("Study Room Checkout Receipt");

$pdf->ezText("");
//$pdf->ezText("Name: $user->first_name $user->last_name");

$pdf->ezText("Room: $room->room_number",15);
$pdf->ezText("",10);
$pdf->ezText("Patron ID: $user->patron_id");
$pdf->ezText("Date:   ".date('m/d/Y',strtotime($reservation->date)));
$pdf->ezText("",10);
$pdf->ezText("Key Checkout Time:");
$pdf->ezText("    ".date("m/d/Y  g:ia",strtotime($reservation->key_checkout_time)));
$pdf->ezText("");
$pdf->ezText("Return Key By:");
$pdf->ezText("    ".date("m/d/Y  g:ia",strtotime($reservation->sched_end_time)));


if(strtotime(date("Y-m-d")) == strtotime($reservation->date))
{
	$credits = get_reservation_credits($reservation->user_id,date("Y-m-d"));
	$week = "(".date('m/d/y',strtotime($credits->week_window_start))."-".date('m/d/y',strtotime("1 day ago",strtotime($credits->week_window_end))).")";
	
	if(MAX_RESERVATION_HOURS_PER_DAY != 99999999)
		$pdf->ezText("\nYou have  $credits->day_hours_remaining of ".MAX_RESERVATION_HOURS_PER_DAY." hours remaining for today.");
	if(MAX_RESERVATION_HOURS_PER_WEEK != 99999999)
		$pdf->ezText("\nYou have $credits->week_hours_remaining of ".MAX_RESERVATION_HOURS_PER_WEEK." hours remaining for the week of $week.");
}		

$pdf->restoreState();
$pdf->closeObject();
$pdf->addObject($all,'all');



if (isset($d) && $d){
  $pdfcode = $pdf->ezOutput(1);
  $pdfcode = str_replace("\n","\n<br>",htmlspecialchars($pdfcode));
  echo '<html><body>';
  echo trim($pdfcode);
  echo '</body></html>';
} else {
  $pdf->ezStream();
}
?>