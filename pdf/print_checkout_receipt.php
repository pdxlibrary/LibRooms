<?php

session_start();
require_once("../config/config.inc.php");
require_once("../includes/Database.php");

require_once("../includes/load_hours_data.php");
require_once("../includes/load_rooms.php");
require_once("../includes/error_checking.php");

require_once("../includes/verify_access.php");
restrict_access($db,array("patron","staff","admin"));

$reservation_id = $_GET['reservation_id'];

// lookup reservation info
$select_reservation = "select * from reservations where id like '$reservation_id' and active like '1'";
//print("select: $select_reservation<br>\n");
$res_reservation = $db->query($select_reservation);
if($res_reservation->numRows() == 1)
{
	$res_reservation->fetchInto($reservation);
}
//print_r($reservation);
//exit();

// lookup user info
$select_user = "select * from users where id like '$reservation->user_id' AND active like '1'";
$res_user = $db->query($select_user);
if($res_user->numRows() > 0)
{
	$res_user->fetchInto($user);
}

$room = $all_rooms[$reservation->room_id];


require_once("class.ezpdf.php");


class Creport extends Cezpdf
{
	var $reportContents = array();
}

$pdf = new Creport(array(7,9),'portrait');



$all = $pdf->openObject();
$mainFont = './fonts/Times-Roman.afm';
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