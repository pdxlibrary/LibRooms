<?php
	
	session_start();

	require_once("config/config.inc.php");
	require_once("includes/functions.inc.php");
	
	// load room filter with advanced search filters and amenity options
	$room_filter = array();
	$room_filter['out_of_order'] = "No";
	$room_filter['capacity gte'] = $_GET['capacity_gte'];
	$room_filter['amenities and'] = $_GET['amenity_filter'];
	$room_filter['group_by'] = "room_group_id";

	// load rooms
	$all_rooms = api("load_rooms",array("room_filter"=>$room_filter));
	$all_room_groups = api("load_room_groups");
	
	//print_r($all_rooms);
	
	// load reservations
	$all_reservations = api("load_calendar_reservations",null,true);


	$page_title = "Search Results";
	require_once(THEME."header.php");

	if(isset($_GET['selected_date']) && strcmp($_GET['selected_date'],'') && isset($_GET['start_time']) && strcmp($_GET['start_time'],'') && isset($_GET['end_time']) && strcmp($_GET['end_time'],''))
	{
		// try to find available matching rooms
		
		/*
		TODO: Finish simpler version...
		$options = array();
		$options["user_id"] = $_SESSION['LibRooms']['UserID'];
		$options["selected_date"] = $_GET['selected_date'];
		$options["start_time"] = $_GET['start_time'];
		$options["end_time"] = $_GET['end_time'];
		$options["otf"] = 0;
		$options["reschedule_reservation"] = $_GET['reschedule_reservation'];
		$options["capacity_gte"] = $_GET['capacity_gte'];
		$options["amenity_filter"] = $_GET['amenity_filter'];
		$matches = api("get_available_rooms",$options);
		foreach($matches as $match)
		{
			$matching_reservations[] ="<li><a data-ajax='false' style='font-weight: normal;' href='confirm_reservation.php?reschedule_id=".$_GET['reschedule_id']."&selected_date=".$_GET['selected_date']."&room_id=".$match->id."_".$match->section_id."&start_time=".date("YmdHis",strtotime($_GET['start_time']))."&end_time=".date("YmdHis",strtotime($_GET['end_time']))."'><div><img src='../".str_replace("images/rooms/$match->id/","thumbnails/",$match->images->room[0]->location)."' align='left' style='width:50px; height:40px; padding-right:5px;'><b>Room ".ltrim($match->room_number,"0")."</b> - $match->capacity chairs<br />".date("m/d/y",strtotime($_GET['selected_date'])).", ".date("g:ia",strtotime($_GET['start_time']))."-".date("g:ia",strtotime($_GET['end_time']))."</div></a></li>";
		}
		*/
		
		$matching_reservations = array();

		// for each matching room (capacity and amenity filtered), check each room for exact availability
		foreach($all_room_groups as $room_group_id => $room_group)
		{
			if(isset($all_rooms->{$room_group_id}))
			{
				foreach($all_rooms->{$room_group_id} as $room_id => $room)
				{
					$room_result_printed = false;
					for($room_section_id=1;$room_section_id<=$room->max_simultaneous_reservations;$room_section_id++)
					{
						// print("checking room... $room->id...<br>\n");
						$conflicts = array();
						$options = array();
						$options["room_id"] = $room_id;
						$options["room_section_id"] = $room_section_id;
						$options["user_id"] = $_SESSION['LibRooms']['UserID'];
						$options["selected_date"] = $_GET['selected_date'];
						$options["start_time"] = $_GET['start_time'];
						$options["end_time"] = $_GET['end_time'];
						$options["otf"] = 0;
						$options["reschedule_reservation"] = $reschedule_reservation_id;
						$conflicts = api("get_time_conflicts",$options);
						
						if(count($conflicts) == 0 && !$room_result_printed)
						{
							$room_result_printed = true;
							$matching_reservations[] ="<li><a data-ajax='false' style='font-weight: normal;' href='confirm_reservation.php?reschedule_id=".$_GET['reschedule_id']."&selected_date=".$_GET['selected_date']."&room_id=".$room->id."_".$room_section_id."&start_time=".date("YmdHis",strtotime($_GET['start_time']))."&end_time=".date("YmdHis",strtotime($_GET['end_time']))."'><div><img src='../".str_replace("images/rooms/$room->id/","thumbnails/",$room->images->room[0]->location)."' align='left' style='width:50px; height:40px; padding-right:5px;'><b>Room ".ltrim($room->room_number,"0")."</b> - $room->capacity chairs<br />".date("m/d/y",strtotime($_GET['selected_date'])).", ".date("g:ia",strtotime($_GET['start_time']))."-".date("g:ia",strtotime($_GET['end_time']))."</div></a></li>";	
						}
						else
						{
							//print_r($conflicts);
						}
					}
				}
			}
		}

?>
		
		<ul data-role="listview" data-inset="true" data-dividertheme="b">
		<li data-role="list-divider" style="padding:0">
			<div class="ui-bar ui-grid-a" style="padding-right:0">
			   <div class="ui-block-a" style="margin-top:8px; font-size:20px; width:60%">Matching Rooms</div>
			   <div class="ui-block-b" style="text-align:right; width:40%"><a href="index.php?<?php print($_SERVER['QUERY_STRING']); ?>" data-ajax="false" data-role="button" data-mini="true" data-inline="true" data-theme="a">Refine</a></div>
			</div>
		</li>
<?php		
		if(count($matching_reservations) > 0)
		{
			foreach($matching_reservations as $matching_reservation)
			{
				print($matching_reservation);
			}
		}
		else
		{
			print("<li><h3><table><tr><td style='color:#FFF'>Sorry, no rooms are available for the criteria you selected.</td></tr></table><br /><a data-role='button' data-ajax='false' href='index.php?".$_SERVER['QUERY_STRING']."'>Alter the Search...</a></h3></li>");
		}
?>
		</ul>
		

		
<?php
		/* Show near matches with amenities removed */
		if(count($matching_reservations) == 0)
		{
			$matching_reservations_no_amenities = array();
			
			unset($room_filter['amenities and']);
			$all_rooms = api("load_rooms",array("room_filter"=>$room_filter));

			// for each matching room (capacity and amenity filtered), check each room for exact availability
			foreach($all_room_groups as $room_group_id => $room_group)
			{
				if(isset($all_rooms->{$room_group_id}))
				{
					foreach($all_rooms->{$room_group_id} as $room_id => $room)
					{
						$room_result_printed = false;
						for($room_section_id=1;$room_section_id<=$room->max_simultaneous_reservations;$room_section_id++)
						{
							// print("checking room... $room->id...<br>\n");
							$conflicts = array();
							$options = array();
							$options["room_id"] = $room_id;
							$options["room_section_id"] = $room_section_id;
							$options["user_id"] = $_SESSION['LibRooms']['UserID'];
							$options["selected_date"] = $_GET['selected_date'];
							$options["start_time"] = $_GET['start_time'];
							$options["end_time"] = $_GET['end_time'];
							$options["otf"] = 0;
							$options["reschedule_reservation"] = $reschedule_reservation_id;
							$conflicts = api("get_time_conflicts",$options);
							
							if(count($conflicts) == 0 && !$room_result_printed)
							{
								$room_result_printed = true;
								$matching_reservations_no_amenities[] ="<li><a data-ajax='false' style='font-weight: normal;' href='confirm_reservation.php?reschedule_id=".$_GET['reschedule_id']."&selected_date=".$_GET['selected_date']."&room_id=".$room->id."_".$room_section_id."&start_time=".date("YmdHis",strtotime($_GET['start_time']))."&end_time=".date("YmdHis",strtotime($_GET['end_time']))."'><div><img src='../".str_replace("images/rooms/$room->id/","thumbnails/",$room->images->room[0]->location)."' align='left' style='width:50px; height:40px; padding-right:5px;'><b>Room ".ltrim($room->room_number,"0")."</b> - $room->capacity chairs<br />".date("m/d/y",strtotime($_GET['selected_date'])).", ".date("g:ia",strtotime($_GET['start_time']))."-".date("g:ia",strtotime($_GET['end_time']))."</div></a></li>";	
							}
							else
							{
								//print_r($conflicts);
							}
						}
					}
				}
			}
			
			if(count($matching_reservations_no_amenities) > 0)
			{	
?>
				<ul data-role="listview" data-inset="true" data-dividertheme="b">
					<li data-role="list-divider" style="padding:0">
						<div class="ui-bar" style="padding-right:0">
							<div style="font-size:20px;">Near Matches...</div>Desired Amenities Removed
						</div>
					</li>
<?php
				foreach($matching_reservations_no_amenities as $matching_reservation)
				{
					print($matching_reservation);
				}
				print("</ul>\n");
			}			
		}
		
		/* Show near matches with shorter durations */
		if(count($matching_reservations) == 0)
		{
			$matching_reservations_shorter = array();
			
			$room_filter['amenities and'] = $_GET['amenity_filter'];
			$all_rooms = api("load_rooms",array("room_filter"=>$room_filter));

			// for each matching room (capacity and amenity filtered), check each room for exact availability
			foreach($all_room_groups as $room_group_id => $room_group)
			{
				if(isset($all_rooms->{$room_group_id}))
				{
					foreach($all_rooms->{$room_group_id} as $room_id => $room)
					{
						$room_result_printed = false;
						for($room_section_id=1;$room_section_id<=$room->max_simultaneous_reservations;$room_section_id++)
						{
							$start_time = strtotime($_GET['start_time']);
							
							// TODO: remove hard-coded times and use loaded precision settings for main study rooms app. Possibly load config settings as a session variable.
							for($end_time = strtotime("-30 mins",strtotime($_GET['end_time']));$end_time - $start_time >= 3600;$end_time = strtotime("-30 mins",$end_time))
							{								
								$conflicts = array();
								$options = array();
								$options["room_id"] = $room_id;
								$options["room_section_id"] = $room_section_id;
								$options["user_id"] = $_SESSION['LibRooms']['UserID'];
								$options["selected_date"] = $_GET['selected_date'];
								$options["start_time"] = $_GET['start_time'];
								$options["end_time"] = date("Y-m-d H:i:s",$end_time);
								$options["otf"] = 0;
								$options["reschedule_reservation"] = $reschedule_reservation_id;
								$conflicts = api("get_time_conflicts",$options);
								
								if(count($conflicts) == 0 && !$room_result_printed)
								{
									$room_result_printed = true;
									$matching_reservations_shorter[] ="<li><a data-ajax='false' style='font-weight: normal;' href='confirm_reservation.php?reschedule_id=".$_GET['reschedule_id']."&selected_date=".$_GET['selected_date']."&room_id=".$room->id."_".$room_section_id."&start_time=".date("YmdHis",strtotime($_GET['start_time']))."&end_time=".date("YmdHis",$end_time)."'><div><img src='../".str_replace("images/rooms/$room->id/","thumbnails/",$room->images->room[0]->location)."' align='left' style='width:50px; height:40px; padding-right:5px;'><b>Room ".ltrim($room->room_number,"0")."</b> - $room->capacity chairs<br />".date("m/d/y",strtotime($_GET['selected_date'])).", ".date("g:ia",strtotime($_GET['start_time']))."-".date("g:ia",$end_time)."</div></a></li>";	
								}
								else
								{
									//print_r($conflicts);
								}
							}
						}
					}
				}
			}
			
			if(count($matching_reservations_shorter) > 0)
			{	
?>
				<ul data-role="listview" data-inset="true" data-dividertheme="b">
					<li data-role="list-divider" style="padding:0">
						<div class="ui-bar" style="padding-right:0">
							<div style="font-size:20px;">Near Matches...</div>Shorter Duration
						</div>
					</li>
<?php
				foreach($matching_reservations_shorter as $matching_reservation)
				{
					print($matching_reservation);
				}
				print("</ul>\n");
			}			
		}
	}

	require_once(THEME."footer.php");

?>