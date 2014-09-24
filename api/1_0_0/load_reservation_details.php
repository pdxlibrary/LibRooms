<?php

require_once("../../load.php");

$id = $_GET['id'];

print(json_encode(load_reservation_details($id)));


?>