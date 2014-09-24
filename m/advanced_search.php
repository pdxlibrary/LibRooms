<?php
	
	session_start();

	require_once("config/config.inc.php");
	require_once("includes/functions.inc.php");
	
	$page_title = "Advanced Search";
	require_once(THEME."header.php");

	if(!isset($_GET['selected_date']))
	{
		// select first available date, by default
		//$_GET['selected_date'] = date("Y-m-d");
	}

	if(!isset($_GET['start_time']))
	{
		// set first available start time for selected date, by default
		$available_start_times = json_decode(file_get_contents(STUDYROOMS_API."available_start_slots_for_date.php?date=".$_GET['selected_date']));
		
		// set default start time to next available time
		//if(isset($available_start_times->available_slots[0]))
		//	$_GET['start_time'] = $available_start_times->available_slots[0];
	}

	if(!isset($_GET['end_time']))
	{
		// set latest available end time for selected date/start time, by default
		$available_end_times = json_decode(file_get_contents(STUDYROOMS_API."available_end_slots_for_date.php?date=".$_GET['selected_date']."&start_time=".urlencode($_GET['start_time'])));
		$last_index = count($available_end_times->available_slots) - 1;
		
		// set default end time to next available time
		//if(isset($available_end_times->available_slots[$last_index]))
		//	$_GET['end_time'] = $available_end_times->available_slots[$last_index];
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

	$available_hours = json_decode(file_get_contents(STUDYROOMS_API."available_hours.php"));

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

	$('#reservation_form').submit(function() {
		// submit search using saved params
		//document.location.href="studyrooms_search_results.php?selected_date="+selected_date+"&start_time="+selected_start_time+"&end_time="+selected_end_time;
		//return false;
	});

	$("#selected_date_select").change(function() {
		selected_date = $("#selected_date_select option:selected").val();
		// load and show possible start times
		$("#find_a_room").hide();
		$("#start_time_div").hide();
		$("#start_time").empty();
		if(selected_date != "") {
		// alert("select date changed to: "+selected_date);
			$.getJSON('<?php print(STUDYROOMS_API); ?>available_start_slots_for_date.php?date='+selected_date, function(data) {
				var items = [];
				$('#start_time').append($("<option></option>").attr("value","").text("Select Start Time..."));
				$.each(data.available_slots, function(key, val) {
					$('#start_time').append($("<option></option>").attr("value",val).text(data.times[key]));
					//alert("key: " + key + " val: " + val + " label: " + data.times[key]);
				});
				$("#start_time_div").show();
			});
		} else {
			// remove the options for end time
			$("#start_time_div").hide();
			$("#start_time").empty();
		}

		// remove the options for end time
		$("#end_time_div").hide();
		$("#end_time").empty();
	});
	
	$("#start_time").change(function() {
		selected_start_time = $("#start_time option:selected").val();
		// load and show possible end times
		$("#find_a_room").hide();
		$("#end_time_div").hide();
		$("#end_time").empty();
		if(selected_start_time != "") {
			$.getJSON('<?php print(STUDYROOMS_API); ?>available_end_slots_for_date.php?date='+selected_date+"&start_time="+selected_start_time, function(data) {
				var items = [];
				$('#end_time').append($("<option></option>").attr("value","").text("Select End Time..."));
				$.each(data.available_slots, function(key, val) {
					$('#end_time').append($("<option></option>").attr("value",val).text(data.times[key]));
					//alert("key: " + key + " val: " + val + " label: " + data.times[key]);
				});
				$("#end_time_div").show();
			});
		} else {
			// remove the options for end time
			$("#end_time_div").hide();
			$("#end_time").empty();
		}
	});
	
	$("#end_time").change(function() {
		selected_end_time = $("#end_time option:selected").val();
		if(selected_end_time != "") {
			$("#find_a_room").show();
		} else {
			$("#find_a_room").hide();
		}
	});

});


</script>



			<form id="reservation_form" name="reservation_form" action="studyrooms_search_results.php" target="_self">
			<div id="list">
			<div style="text-align:center">
				<h1>Reserve a Study Room</h1>
			</div>

				<div data-role="navbar">
				<ul>
					<li><a href="#" class="ui-btn-active">Search by Time</a></li>
					<li><a href="#">Search by Room #</a></li>
				</ul>
			</div
				
				<?php /***** DATE *****/ ?>

				<div data-role="fieldcontain">
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
				<div id="start_time_div" data-role="fieldcontain" style="display:none">
				<select id="start_time" name="start_time" >
				<option value="">Select Start Time...</option>
				<?php
					if(isset($_GET['selected_date']))
					{
						foreach($available_start_times->available_slots as $index => $slot)
						{
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
				<div id="end_time_div" data-role="fieldcontain" style="display:none">
				<select id="end_time" name="end_time" >
				<option value="">Select End Time...</option>
				<?php
				if(isset($_GET['selected_date']) && isset($_GET['start_time']))
				{
					//$end_times = json_decode(file_get_contents(STUDYROOMS_API."available_end_slots_for_date.php?date=".$_GET['selected_date']."&start_time=".urlencode($_GET['start_time'])));
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
				
				<?php /***** FIND A ROOM *****/ ?>
				<div id="find_a_room" style="display:none">
					<input type="submit" value="Find a Room">
				</div>
			</form>

<?php
	require_once(THEME."footer.php");
?>
