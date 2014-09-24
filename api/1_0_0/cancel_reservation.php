<?php

require_once("../../load.php");

$reservation_id = $_GET['reservation_id'];
$user_id = $_GET['user_id'];
$reason = $_GET['reason'];

print(json_encode(cancel_reservation($reservation_id,$user_id,$reason)));


?>