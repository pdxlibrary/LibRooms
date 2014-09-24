<?php

$select = "select * from study_rooms where active like '1' order by capacity,room_number";
$res = $db->query($select);
$all_rooms = array();
while($res->fetchInto($room))
{
    $all_rooms[$room->id] = $room;
}

// TODO: Add amenities to room objects


?>