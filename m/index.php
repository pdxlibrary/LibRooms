<?php

session_start();

require_once("config/config.inc.php");
require_once("includes/functions.inc.php");

// load search filter
if(!isset($_GET['selected_date']))
{
	// select first available date, by default
	//$_GET['selected_date'] = date("Y-m-d");
}

if(isset($_GET['start_time']))
{
	$available_start_times = api("available_start_slots_for_date",array("date"=>$_GET['selected_date']));
	if(isset($_GET['end_time']))
	{
		$available_end_times = api("available_end_slots_for_date",array("date"=>$_GET['selected_date'],"start_time"=>$_GET['start_time']));
	}
}

$minimum_capacity = $_GET['minimum_capacity'];

if(isset($_GET['amenities']))
	$selected_amenities = $_GET['amenities'];

$room_filter = array();
$room_filter['out_of_order'] = "No";
if(isset($_GET['capacity_filter']) && strcmp($_GET['capacity_filter'],''))
	$room_filter['capacity'] = $_GET['capacity_filter'];
$room_filter['amenities and'] = $_GET['amenity_filter'];
$room_filter['group_by'] = "room_group_id";

// load available hours
$available_hours = api("available_hours");
// print_r($available_start_times);

$page_title = "Find a Room";
require_once(THEME."header.php");

?>

<style>
h1 {font-size:1.5em; }
.ui-field-contain>label, .ui-field-contain .ui-controlgroup-label, .ui-field-contain>.ui-rangeslider>label {margin:1em 0 0 0; }
</style>


<script>
var selected_date = "";
var selected_start_time = "";
var selected_end_time = "";

jQuery(document).ready(function($) {
	
	///$("#start_time").selectmenu("refresh");
	<?php
		
		if(isset($_GET['selected_date']) && isset($available_hours->{$_GET['selected_date']}) && isset($_GET['start_time']))
		{
			print("$('#start_time').selectmenu('enable');\n");
			if(isset($_GET['end_time'])&& in_array($_GET['start_time'],$available_start_times->available_slots))
			{
				print("$('#end_time').selectmenu('enable');\n");
				if(in_array($_GET['end_time'],$available_end_times->available_slots))
				{
					print("$('#advanced_options').show();\n");
					print("$('#find_a_room').show();\n");
				}
			}
		}
	?>
	
	$('#reservation_form').submit(function() {
		// submit search using saved params
		//document.location.href="studyrooms_search_results.php?selected_date="+selected_date+"&start_time="+selected_start_time+"&end_time="+selected_end_time;
		//return false;
	});

	$("#selected_date_select").change(function() {
		selected_date = $("#selected_date_select option:selected").val();
		// load and show possible start times
		
		$("#start_time").empty();
		//$('#start_time').append($("<option></option>").attr("value","").text("Select Start Time..."));
		$("#start_time").selectmenu('refresh', true);
		$("#start_time").selectmenu('disable');
		
		$("#end_time").empty();
		$('#end_time').append($("<option></option>").attr("value","").text("Select End Time..."));
		$("#end_time").selectmenu('refresh', true);
		$("#end_time").selectmenu('disable');

		if(selected_date != "") {
		// alert("select date changed to: "+selected_date);
			$.getJSON('<?php print(STUDYROOMS_API); ?>available_start_slots_for_date.php?date='+selected_date, function(data) {
				var items = [];
				$('#start_time').append($("<option></option>").attr("value","").text("Select Start Time..."));
				$.each(data.available_slots, function(key, val) {
					$('#start_time').append($("<option></option>").attr("value",val).text(data.times[key]));
					//alert("key: " + key + " val: " + val + " label: " + data.times[key]);
				});
				$("#start_time").selectmenu('refresh', true);
				$("#start_time").selectmenu('enable');
				
				$('#end_time').append($("<option></option>").attr("value","").text("Select End Time..."));
				$("#end_time").selectmenu('refresh', true);
				$("#end_time").selectmenu('disable');
				
				$("#advanced_options").hide();
				$("#find_a_room").hide();
			});
		} else {
			// remove the options for start time
			$("#start_time").empty();
		}

		// remove the options for end time
		$("#end_time").empty();
	});
	
	$("#start_time").change(function() {
		selected_date = $("#selected_date_select option:selected").val();
		selected_start_time = $("#start_time option:selected").val();
		// load and show possible end times
		$("#end_time").empty();
		if(selected_start_time != "") {
			$.getJSON('<?php print(STUDYROOMS_API); ?>available_end_slots_for_date.php?date='+selected_date+"&start_time="+selected_start_time, function(data) {
				var items = [];
				$('#end_time').append($("<option></option>").attr("value","").text("Select End Time..."));
				$.each(data.available_slots, function(key, val) {
					$('#end_time').append($("<option></option>").attr("value",val).text(data.times[key]));
					//alert("key: " + key + " val: " + val + " label: " + data.times[key]);
				});
				//refresh and force rebuild
				$("#end_time").selectmenu('refresh', true);
				$("#end_time").selectmenu('enable');
				
				$("#advanced_options").hide();
				$("#find_a_room").hide();
			});
		} else {
			// remove the options for end time
			$("#end_time").empty();
		}
	});
	
	$("#end_time").change(function() {
		selected_end_time = $("#end_time option:selected").val();
		if(selected_end_time != "") {
			$("#advanced_options").show();
			$("#find_a_room").show();
		} else {
			$("#advanced_options").hide();
			$("#find_a_room").hide();
		}
	});

});


</script>

<?php

if(isset($_GET['reservation_id']))
{
	// load rescheduling reservation
	$reservations = api("load_reservation_details",array("id"=>$_GET['reservation_id']));
	
	
	foreach($reservations as $reservation)
		break;
		
	// print_r($reservation);
	$room = $reservation->room;
	
	?>
	<ul data-role="listview">
		<li>
			<table style="color:#FFF; font-weight:bold;">
			<tr><td colspan="2">Rescheduling Reservation...</td></tr>
			<tr><td>Room:</td><td><?php print(ltrim($room->room_number,"0")); ?></td></tr>
			<tr><td>Date:</td><td><?php print(date("m/d/Y",strtotime($reservation->date))); ?></td></tr>
			<tr><td>Start:</td><td><?php print(date("g:ia",strtotime($reservation->sched_start_time))); ?></td></tr>
			<tr><td>End:</td><td><?php print(date("g:ia",strtotime($reservation->sched_end_time))); ?></td></tr>
			</table>
		</li>
	</ul>
	<br />
	<?php
}

?>


<form id="reservation_form" name="reservation_form" action="search_results.php" target="_self">
<?php
	if(isset($_GET['reservation_id']))
	{
		print("<input type='hidden' name='reschedule_id' value='".$_GET['reservation_id']."'>\n");
	}
?>
<div id="list">

	<?php /***** DATE *****/ ?>

	<div data-role="fieldcontain" class="ui-hide-label">
	<select id="selected_date_select" name="selected_date" >
		<option value="">Select Date for Reservation...</option>
		<?php
			//print_r($available_hours);
			foreach($available_hours as $date => $day_hours)
			{
				$date_string = date("D m/d/Y",strtotime($date));
				if(count($day_hours) > 0 && strcmp($day_hours->closed,'1'))
				{
					if(!strcmp($_GET['selected_date'],$date))
						print("<option value='$date' selected>$date_string</option>\n");
					else
						print("<option value='$date'>$date_string</option>\n");
				}
				else
					print("<option value='$date' disabled='disabled'>$date_string</option>\n");
			}
		?>
	
	</select>
	</div>
	
	
	<?php /***** START TIME *****/ ?>
	<div id="start_time_div" data-role="fieldcontain" class="ui-hide-label">
	<select id="start_time" name="start_time" disabled>
	<option value="">Select Start Time...</option>
	<?php
		if(isset($_GET['selected_date']))
		{		
			foreach($available_start_times->available_slots as $index => $slot)
			{
				//print("$slot vs. ".$_GET['start_time']."<br>\n");
				if(!strcmp($_GET['start_time'],$slot))
					print("<option value='$slot' selected>".date("g:ia",strtotime($slot))."</option>\n");
				else
					print("<option value='$slot'>".date("g:ia",strtotime($slot))."</option>\n");
			}
		}
	?>
	</select>
	</div>

	<?php /***** END TIME *****/ ?>
	<div id="end_time_div" data-role="fieldcontain"  class="ui-hide-label">
	<select id="end_time" name="end_time" disabled>
	<option value="">Select End Time...</option>
	<?php
	if(isset($_GET['selected_date']) && isset($_GET['start_time']))
	{
		foreach($available_end_times->available_slots as $index => $slot)
		{
			if(!strcmp($_GET['end_time'],$slot))
				print("<option value='$slot' selected>".date("g:ia",strtotime($slot))."</option>\n");
			
			else
				print("<option value='$slot'>".date("g:ia",strtotime($slot))."</option>\n");
		}
	}
	?>
	</select>
	</div>
	
	<div id="advanced_options" data-role="collapsible" data-theme="a" data-content-theme="a" style="display:none">
		<h3>Advanced Options</h3>
		<div data-role="fieldcontain">
			<fieldset data-role="controlgroup">
			   <legend>Amenities</legend>
			   
<?php
	$amenity_options = api("load_amenities",array("search_filter"=>"Yes"));
	foreach($amenity_options as $option)
	{
		if(isset($_GET['amenity_filter']))
		{
			if(in_array($option->id,$_GET['amenity_filter']))
				$checked = "checked";
			else
				$checked = "";
		}
		print("<label for=\"amenity$option->id\">$option->name</label>\n");
		print("<input type=\"checkbox\" name=\"amenity_filter[]\" id=\"amenity$option->id\" value=\"$option->id\" class=\"custom\" $checked />\n");
		
	}
?>
			</fieldset>
			<hr>
			<fieldset data-role="controlgroup">
			   <legend># of Chairs</legend>
			   <select id="capacity_gte" name="capacity_gte">
				<?php
					$capacity_options = api("load_room_capacity_options");
					for($i=0;$i<count($capacity_options);$i++)
					{
						$option = $capacity_options[$i];
						
						if(!strcmp($_GET['capacity_gte'],$option))
							$selected = "selected";
						else
							$selected = "";
						if($i<(count($capacity_options)-1))
							print("<option value='$option' $selected>$option or more</option>\n");
						else
							print("<option value='$option' $selected>$option</option>\n");
					}
				?>
				</select>
			</fieldset>
		</div>
	</div>
	<hr />
	<?php /***** FIND A ROOM *****/ ?>
	<div id="find_a_room" style="display:none;">
		<button id="find_a_room_button" data-theme="b" style="font-size:18px;">Find a Room</button>
	</div>

	<!--
	<ul data-role="listview" data-inset="true" data-dividertheme="a"> 
		 <li data-role="list-divider">Advanced Search</li> 
		 <li><a href="#">Find a specific room</a></li> 
	</ul>
	-->
</form>
</div> <!-- end of list -->

<?php
	require_once(THEME."footer.php");
?>


