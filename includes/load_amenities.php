<?php

$select = "select * from amenities where active like '1' order by name";
$res = $db->query($select);
$all_amenities = array();
while($res->fetchInto($amenity))
{
    $all_amenities[$amenity->id] = $amenity;
}


$select = "select * from study_rooms_amenities where active like '1' order by id";
$res = $db->query($select);
$all_room_amenities = array();
while($res->fetchInto($room_amenity))
{
    $all_room_amenities[$room_amenity->room_id][] = $all_amenities[$room_amenity->amenity_id];
}

?>