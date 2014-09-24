<?php

session_start();
require_once("../config/config.inc.php");
require_once("../includes/Database.php");

require_once("../load.php");

require_once("../includes/verify_access.php");
restrict_access($db,array("patron","staff","admin"));

$reservation_id = $_GET['reservation_id'];

$reservations = load_reservation_details($reservation_id);
if(count($reservations)==1)
{
	foreach($reservations as $reservation)
		break;
		
	$user = get_user_by_id($reservation->user_id);
	$room = $reservation->room;
}
// pr($reservation);
// exit();
require_once("class.ezpdf.php");


class Creport extends Cezpdf
{
	var $reportContents = array();
}

$pdf = new Creport(array(7,13),'portrait');



// put a line top and bottom on all the pages
$all = $pdf->openObject();
$mainFont = './fonts/Times-Roman.afm';
$pdf->selectFont($mainFont);
$pdf->saveState();
$pdf->setStrokeColor(0,0,0,1);


$pdf->ezText("Portland State University Library");
$pdf->ezText("Study Room Check-in Receipt");
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
$pdf->ezText("Scheduled End Time:");
$pdf->ezText("    ".date("m/d/Y  g:ia",strtotime($reservation->sched_end_time)));
$pdf->ezText("");
$pdf->ezText("Key Check-in Time:");
$pdf->ezText("    ".date("m/d/Y  g:ia",strtotime($reservation->key_checkin_time)));
$pdf->ezText("");
if(count($reservation->fines) > 0)
{
	$total_fines = 0;
	foreach($reservation->fines as $fine)
	{
		$pdf->ezText("Fine: ".format2dollars($fine->amount));
		$pdf->ezText(" ".date("m/d/Y  g:ia",strtotime($fine->date_added)));
		$pdf->ezText(" ".$fine->description);
		$pdf->ezText("");
		$total_fines += $fine->amount;
	}
	$pdf->ezText("<b>Total Fines: ".format2dollars($total_fines)."</b>");
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