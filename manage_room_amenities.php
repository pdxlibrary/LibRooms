<?php

session_start();

require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("load.php");

$room_id = $_GET['room_id'];
$rooms = load_rooms($room_id);
$room = $rooms[$room_id];
$all_amenities = load_amenities();

require_once("includes/verify_access.php");
restrict_access($db,array("staff","admin"));


if(isset($_GET['submitted']))
{
	//print("submitted<br>\n");
	//print_r($_GET);
	foreach($all_amenities as $amenity_id => $amenity)
	{
		$var = "amenity".$amenity_id;
		$val = $_GET[$var];
		//print("val:var - $val:$var<br>\n");
		if(!strcmp($val,'on'))
		{
			//print("$amenity->name selected<br>\n");
			//print("<pre>\n");
			//print_r($amenity);
			//print_r($room);
			//print("</pre>\n");
			if(roomHasAmenity($room->amenities,$amenity->id))
			{
				// don't need to do anything
				//print("$amenity->name already attached to room<br>\n");
			}
			else
			{
				// attach amenity to room
				$fields = array("room_id","amenity_id","date_added");
				$values = array($room->id,$amenity->id,date("Y-m-d H:i:s"));
				$db->insert("study_rooms_amenities",$fields,$values);
			}
		}
		else
		{
			if(roomHasAmenity($room->amenities,$amenity->id))
			{
				// remove previous attachment
				$db->update("study_rooms_amenities","active",0,"room_id like '$room->id' and amenity_id like '$amenity->id'");
			}
		}
	}
	
	if(isset($_GET['new_amenity_name']) && strcmp(trim($_GET['new_amenity_name']),''))
	{
		$new_amenity_name = $_GET['new_amenity_name'];
		$new_amenity_description = $_GET['new_amenity_description'];
		
		$table = "amenities";
		$fields = array('name','description');
		$values = array($new_amenity_name,$new_amenity_description);
		
		if($db->noDuplicate($table,$fields,$values))
		{
			$fields[] = 'date_added';
			$values[] = date('Y-m-d H:i:s');
			$amenity_id = $db->insert($table,$fields,$values);
			
			// print("new amenity added --> $amenity_id<br>\n");
			
			// attach amenity to room
			$fields = array("room_id","amenity_id","date_added");
			$values = array($room->id,$amenity_id,date("Y-m-d H:i:s"));
			$db->insert("study_rooms_amenities",$fields,$values);
		}
		else
		{
			print("$new_amenity_name already exists<br>\n");
		}
		
		// reload all amenities
		$all_amenities = load_amenities();
	}
	
	print("<font color='green'>All Room Amenities Successfully Saved.</font><br>\n");
	
	// reload room
	$rooms = load_rooms($room_id);
	$room = $rooms[$room_id];
}


if(isset($_GET['remove_amenity']))
{
	$remove_amenity = $_GET['remove_amenity'];
	$db->update('study_rooms_amenities','active','0',"id like '$remove_amenity'");
	$rooms = load_rooms($room_id);
	$room = $rooms[$room_id];
}
print("<form>\n");
print("<input type='hidden' name='submitted' value='1'>\n");
print("<input type='hidden' name='room_id' value='$room_id'>\n");

print("<h2>Amenities</h2>\n");

$per_row = 1;
$in_row = 0;
$width = floor(100/$per_row);
print("<table>\n");
foreach($all_amenities as $amenity_id => $amenity)
{
	if($in_row == 0)
	{
		print("<tr valign='top'>");
	}
	// print("$amenity->name ($amenity->id)<br>\n");
	if(roomHasAmenity($room->amenities,$amenity->id))
		$checked = "checked";
	else
		$checked = "";
	
	
	print("<td width='$width%'><input type='checkbox' name='amenity$amenity_id' $checked><b>$amenity->name</b>");
	
	if(strcmp($amenity->description,''))
		print(" - $amenity->description");

	print("</td>");
	
	$in_row++;
	if($in_row >= $per_row)
	{
		print("</tr>\n");
		$in_row = 0;
	}
}
if($in_row > 0)
{
	while($in_row < $per_row)
	{
		print("<td>&nbsp;</td>\n");
		$in_row++;
	}
}
print("</table><br>\n");
print("<input type='submit' value='Save'>\n");

print("<hr>\n");

print("<h2>Add a New Amenity</h2>\n");
print("<table>");
print("<tr><td>Name:</td><td><input name='new_amenity_name' size='50'></td></tr>\n");
print("<tr><td>Description (optional):</td><td><input name='new_amenity_description' size='50'></td></tr>\n");
print("</table><br>\n");
print("<input type='submit' value='Add New Amenity'>\n");

print("</form>\n");

?>