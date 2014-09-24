<script type="text/javascript">

// room numbers by room id
var rooms = new Array();
<?php
	// load rooms info into javascript array
	if(is_array($all_rooms))
	{
		foreach($all_rooms as $room_group_id => $room_group)
		{
			foreach($room_group as $room_index => $room)
			{
				print("rooms[$room->id] = $room->room_number;\n");
			}
		}
	}
?>

// load timeslot ids
var timeslots = new Array();
<?php
	print("var timeslots = new Array();\n");
	$timeslots = $todays_hours->slots;
	for($i=0;$i<count($timeslots);$i++)
	{
		$timeslot = $timeslots[$i];
		print("timeslots[$i] = '".date('g:ia',strtotime($timeslot))."';\n");
	}
	// append the closing time
	print("timeslots[$i] = '".date('g:ia',strtotime("+".RES_PRECISION." minutes",strtotime($timeslot)))."';\n\n");
	print("var timeslot_stamps = new Array();\n");
	for($i=0;$i<count($timeslots);$i++)
	{
		$timeslot = $timeslots[$i];
		print("timeslot_stamps[$i] = '".date('YmdHis',strtotime($timeslot))."';\n");
	}
	// append the closing time
	print("timeslot_stamps[$i] = '".date('YmdHis',strtotime("+".RES_PRECISION." minutes",strtotime($timeslot)))."';\n\n");
?>

	
// globals to track current reservation selection
var reservation_room;
var mousedown_column = "";
var mouseover_column = "";
var selection_in_progress = false;
// limit determined by max reservation length for room * fractional hours. (Ex. 2 hours * 4 slots per hour = 8)
var max_reservation_length_slots = <?php print((DEFAULT_MAX_RES_LEN/60)*(60/RES_PRECISION)); ?>;
var min_reservation_length_slots = <?php print((DEFAULT_MIN_RES_LEN/60)*(60/RES_PRECISION)); ?>;
var selected_start_time = "";
var selected_end_time = "";
var selected_room = "";
var room_id = "";

function reset_calendar() {
   //alert("reset cal called");
	// reset previous selection
	$("#tooltip").html("trying to reset cal...");
	$(".reservation_calendar td.slot_held").removeClass("slot_held").addClass("slot_available");
	$(".reservation_calendar td.slot_held_fcfs").removeClass("slot_held_fcfs").addClass("slot_fcfs");
	$(".reservation_calendar td.slot_held_light").addClass("slot_available");
	$(".reservation_calendar td.slot_held_light_fcfs").addClass("slot_fcfs");
	$(".reservation_calendar td").removeClass("slot_held_light");
	$(".reservation_calendar td").removeClass("slot_held_light_fcfs");
	$("#tooltip").toggle(false);
	selected_start_time = "";
	selected_end_time = "";
	selected_room = "";
	room_id = "";
	reservation_room = "";
	mousedown_column = "";
	mouseover_column = "";
	selection_in_progress = false;
}

function update_selected_range()
{
	var start_col;
	var end_col;
	var class_name;
	var room_parts = reservation_room.split("_");
	room_id = room_parts[0];
	mouseover_column = parseInt(mouseover_column);
	mousedown_column = parseInt(mousedown_column);
	
	//alert("mo: " + mouseover_column + " md: " + mousedown_column);
	if(mouseover_column >= mousedown_column)
	{
		// left to right selection
		start_col = mousedown_column;
		end_col = mouseover_column;
		if(end_col - start_col + 1 > max_reservation_length_slots)
		{
			// limit selection to only global max reservation length setting
			end_col = parseInt(start_col) + max_reservation_length_slots - 1;
		}

		for(i=start_col;i<=end_col;i++)
		{
			// check to see if this range collides with an existing reservation
			class_name = ".room"+reservation_room+".col"+i;
			//alert("id: " + class_name);
			if($(class_name).length == 0)
			{
				end_col = i-1;
				//alert("max col limited to: " + i);
				break;
			}
		}
	}
	else
	{
		// right to left selection
		end_col = mousedown_column;
		start_col = mouseover_column;
		if(end_col - start_col + 1 > max_reservation_length_slots)
		{
			// limit selection to only global max reservation length setting
			start_col = parseInt(end_col) - max_reservation_length_slots + 1;
		}
		
		for(i=end_col;i>=start_col;i--)
		{
			// check to see if this range collides with an existing reservation
			class_name = ".room"+reservation_room+".col"+i;
			//alert("id: " + class_name);
			if($(class_name).length == 0)
			{
				start_col = i+1;
				//alert("max col limited to: " + i);
				break;
			}
		}
	}
	
	
		
	$(".room"+reservation_room).removeClass("slot_held");
	for(i=start_col;i<=end_col;i++)
	{
		class_name = ".room"+reservation_room+".col"+i;
		//alert("class name: " + class_name);
		$(class_name).addClass("slot_held");
		//$(".room"+reservation_room+".col"+i).addClass("slot_held");
		//alert("done highlighting");
	}
	
	if(selection_in_progress == true)
	{
		if(parseInt(mouseover_column) < parseInt(end_col))
			var pos = $(".room"+reservation_room+".col"+start_col).offset();
		else
			var pos = $(".room"+reservation_room+".col"+end_col).offset();
		var tooltip_width = $("#tooltip").width();
		var cell_width = $(".room"+reservation_room+".col"+end_col).width();
		var end_col_plus_one = parseInt(end_col) + 1;
		var container_pos = $("#calendar_container").offset();
		$("#tooltip").css( { "position":"absolute", "left": (pos.left + (cell_width + cell_width/3) - (tooltip_width/2) - container_pos.left) + "px", "top":pos.top-400 + "px" } );
		// $("#tooltip").css( { "position":"absolute", "left": (pos.left - (cell_width/2) - (tooltip_width/2)) + "px", "top":pos.top-104 + "px" } );
		$("#tooltip").html("<a href='javascript:reset_calendar()'><img style='margin-top:-12px; margin-right:-12px; float:right' src='images/close_window.png'></a><b>Room "+rooms[room_id]+"</b><br><img src='images/checkmark.gif' align='left' /> Start Time: "+timeslots[start_col]+"<br><img src='images/checkmark.gif' align='left' /> End Time: "+timeslots[end_col_plus_one]+"<br>");
		$("#tooltip").show();
	}
	else if((selection_in_progress == false && (end_col - start_col + 1) >= min_reservation_length_slots))
	{
		// only show the tool tip if the length of the reservation does not violate the global min resevation length setting (when done selecting)
		if(parseInt(mouseover_column) < parseInt(end_col))
			var pos = $(".room"+reservation_room+".col"+start_col).offset();
		else
			var pos = $(".room"+reservation_room+".col"+end_col).offset();
		var tooltip_width = $("#tooltip").width();
		var cell_width = $(".room"+reservation_room+".col"+end_col).width();
		var end_col_plus_one = parseInt(end_col) + 1;
		var container_pos = $("#calendar_container").offset();
		$("#tooltip").css( { "position":"absolute", "left": (pos.left + (cell_width + cell_width/3) - (tooltip_width/2) - container_pos.left) + "px", "top":pos.top-400 + "px" } );
		<?php
			if(strcmp($_GET['reschedule'],''))
				print("\$(\"#tooltip\").html(\"<a href='javascript:reset_calendar()'><img style='margin-top:-12px; margin-right:-12px; float:right' src='images/close_window.png'></a><b>Room \"+rooms[room_id]+\"</b><br><img src='images/checkmark.gif' align='left' /> Start Time: \"+timeslots[start_col]+\"<br><img src='images/checkmark.gif' align='left' /> End Time: \"+timeslots[end_col_plus_one]+\"<br><a href='".WEB_ROOT."reschedule_reservation.php?selected_date=$todays_hours->date&reschedule=".$_GET['reschedule']."&room_id=\"+reservation_room+\"&start_time=\"+timeslot_stamps[start_col]+\"&end_time=\"+timeslot_stamps[end_col_plus_one]+\"'><center><div style='margin-top:5px; padding:3px; background-color:#666; border-radius:4px; font-size:11px'>Reschedule Now!</div></center></a>\");\n");
			else
				print("\$(\"#tooltip\").html(\"<a href='javascript:reset_calendar()'><img style='margin-top:-12px; margin-right:-12px; float:right' src='images/close_window.png'></a><b>Room \"+rooms[room_id]+\"</b><br><img src='images/checkmark.gif' align='left' /> Start Time: \"+timeslots[start_col]+\"<br><img src='images/checkmark.gif' align='left' /> End Time: \"+timeslots[end_col_plus_one]+\"<br><a href='".WEB_ROOT."confirm_reservation.php?selected_date=$todays_hours->date&room_id=\"+reservation_room+\"&start_time=\"+timeslot_stamps[start_col]+\"&end_time=\"+timeslot_stamps[end_col_plus_one]+\"'><center><div style='margin-top:5px; padding:3px; background-color:#666; border-radius:4px; font-size:11px'>Confirm Reservation!</div></center></a>\");\n");
		?>
		$("#tooltip").show();
	}
	else
		reset_calendar();
}


jQuery(document).ready(function($) {
	
	
	<?php
		if(CAL_REFRESH_ON_IDLE_SEC > 0)
		{
			// auto-refresh calendar, if idle
			print("$(document).idle({ \n");
			print("	onIdle: function() { \n");
			print("		location.reload(); \n");
			print("	}, \n");
			print("	idle: ".(CAL_REFRESH_ON_IDLE_SEC*1000)." \n");
			print("}) \n");
		}
	?>
	

	$(".reservation_calendar tr:even").addClass("even");
	$(".reservation_calendar tr:odd").addClass("odd");
	
	// pressing esc resets the calendar and removes any selections
	$(document).keyup(function(e) {
		if (e.keyCode == 27) {
			reset_calendar();
		}
	});
	
	
	var on_cal = false;


	$("td").mouseover(function() {
		if($(this).hasClass("slot_held_light") || $(this).hasClass("slot_held_light_fcfs"))
		{
			var hover_end_time_id = $(this).attr("id");
			var hover_end_time_parts = hover_end_time_id.split("-");
			var hover_room = hover_end_time_parts[0];
			var room_parts = hover_room.split("_");
			var hover_room_id = room_parts[0];
			var hover_end_time = hover_end_time_parts[1];
			
			// add res_presision to the time to get the ending time of the selected reservation
			var year = hover_end_time.substring(0,4);
			var month = hover_end_time.substring(4,6);
			var day = hover_end_time.substring(6,8);
			var hour = hover_end_time.substring(8,10);
			var min = hover_end_time.substring(10,12);
			var sec = hover_end_time.substring(12,14);
			//alert(year+"-"+month+"-"+day+"-"+hour+"-"+min+"-"+sec);
			var newDateObj = new Date();
			var oldDateObj = new Date(year,month,day,hour,min,sec);
			newDateObj.setTime(oldDateObj.getTime() + (<?php print(RES_PRECISION); ?> * 60 * 1000));
			var hour = newDateObj.getHours();
			var min = newDateObj.getMinutes().toString();
			if(min.length == 1) min = "0"+min;

			hover_end_time_display = hour;
			
			if(hour >= 24)
			{
				if(hour > 24)
					hour -= 24;
				else
					hour -= 12;
				hover_end_time_display = hour.toString() + ":" + min + "am";
			}
			else if(hour >= 12)
			{
				if(hour > 12)
					hour -= 12;
				hover_end_time_display = hour.toString() + ":" + min + "pm";
			}
			else if(hour == 0)
			{
				hour = 12;
				hover_end_time_display = "12:" + min + "am";
			}
			else
			{
				hover_end_time_display = hour.toString() + ":" + min + "am";
			}
			$("#tooltip").html("<a href='javascript:reset_calendar()'><img style='margin-top:-12px; margin-right:-12px; float:right' src='images/close_window.png'></a><b>Room "+rooms[hover_room_id]+"</b><br><img src='images/checkmark.gif' align='left' /> Start Time: "+selected_start_time_display+"<br>End Time: <i>"+hover_end_time_display+"</i><br><br><center>Select End Time</center>");
		}
	});
	


/* draggable select javascript */
	$(".reservation_calendar").mousedown(function() {
		return false;
	});
<?php
if(!strcmp($user_type,'staff') || !strcmp($user_type,'admin'))
	print("$('td.slot_available,td.slot_fcfs').mousedown(function() {\n");
else
	print("$('td.slot_available').mousedown(function() {\n");
?>
		if($(this).hasClass("slot_held_light") || $(this).hasClass("slot_held_light_fcfs"))
		{
			// do nothing
		}
		else
		{
			reset_calendar();
			//alert($(this).attr("id"));
			$(this).addClass("slot_held");
			var id_parts = $(this).attr("id").split("-");
			reservation_room = id_parts[0];
			//alert("room: " + reservation_room);
			mousedown_column = id_parts[2];
			selection_in_progress = true;
			return false; // prevent text selection
		}
		
	});

	$("td.slot_available,td.slot_fcfs").mouseover(function() {
		if(selection_in_progress)
		{
		
			if($(this).hasClass("slot_held_light") || $(this).hasClass("slot_held_light_fcfs"))
			{
				// do nothing
			}
			else
			{
				var id_parts = $(this).attr("id").split("-");
				var col = id_parts[2];
				//alert("col: "+col);
				mouseover_column = col;
				update_selected_range();
			}
		}
	});
	// mouseup anywhere on/off the page	
	$(window).mouseup(function() {
		if(selection_in_progress == true)
		{
			selection_in_progress = false;
			update_selected_range();
		}
	});
	
	// mouseup anywhere on the page	(for IE to work better)
	$(document).mouseup(function() {
		if(selection_in_progress == true)
		{
			selection_in_progress = false;
			update_selected_range();
		}
	});
	
	
	
	$('td.slot_historic').mousedown(function() {
		var currentTime = new Date()
		var hours = currentTime.getHours()
		var minutes = currentTime.getMinutes()
		if (minutes < 10){
			minutes = "0" + minutes
		}
		
		if(hours > 11)
		{
			if(hours > 12)
				hours = hours - 12;
			alert("Current time is: "+hours+":"+minutes+"pm\nPlease select an upcoming time for your reservation.");
		}
		else
		{
			alert("Current time is: "+hours+":"+minutes+"am\nPlease select an upcoming time for your reservation.");
		}
		//$("#tooltip").html("<a href='javascript:reset_calendar()'><img style='margin-top:-12px; margin-right:-12px; float:right' src='images/close_window.png'></a>Current time is: select a time in the future</center>");
		//$("#tooltip").show();
	});
	
	// block IE from highlighting table cells when selecting
	$("td").bind("selectstart", function () {
      return false;
    })


	
	$("td.slot_available").click(function() {
		
		if($(this).hasClass("slot_held_light"))
		{
			// TODO: account for RES_PRECISION == MIN_RES_LEN_MIN (allow end_time slot to be start_time slot in that case)
		
			$(this).addClass("slot_held");
			var prev = $(this).prev();
			while($(prev).hasClass('slot_held_light'))
			{
				$(prev).addClass("slot_held");
				prev = $(prev).prev();
			}
			
			$(".reservation_calendar td.slot_held_light").removeClass("slot_held_light").addClass("slot_available");
			
			var selected_end_time_id = $(this).attr("id");
			var selected_end_time_parts = selected_end_time_id.split("-");
			selected_room = selected_end_time_parts[0];
			var room_parts = selected_room.split("_");
			room_id = room_parts[0];
			selected_end_time = selected_end_time_parts[1];
			
			// add res_presision to the time to get the ending time of the selected reservation
			var year = selected_end_time.substring(0,4);
			var month = selected_end_time.substring(4,6);
			var month_index = parseInt(month) - 1;
			//alert(month_index);
			var day = selected_end_time.substring(6,8);
			var hour = selected_end_time.substring(8,10);
			var min = selected_end_time.substring(10,12);
			var sec = selected_end_time.substring(12,14);
			//alert(year+"-"+month+"-"+day+"-"+hour+"-"+min+"-"+sec);
			var newDateObj = new Date();
			var oldDateObj = new Date(year,month_index,day,hour,min,sec);
			newDateObj.setTime(oldDateObj.getTime() + (<?php print(RES_PRECISION); ?> * 60 * 1000));
			//alert(oldDateObj.toString());
			//alert(newDateObj.toString());
			var year = newDateObj.getFullYear();
			var month_index = newDateObj.getMonth();
			var month = (month_index + 1).toString();
			if(month.length == 1) month = "0"+month;
			var day = newDateObj.getDate().toString();
			if(day.length == 1) day = "0"+day;
			var hour = newDateObj.getHours().toString();
			if(hour.length == 1) hour = "0"+hour;
			var min = newDateObj.getMinutes().toString();
			if(min.length == 1) min = "0"+min;
			var sec = newDateObj.getSeconds().toString();
			if(sec.length == 1) sec = "0"+sec;
			//alert(year+"-"+month+"-"+day+"-"+hour+"-"+min+"-"+sec);
			selected_end_time = year.toString()+month.toString()+day.toString()+hour.toString()+min.toString()+sec.toString();
			selected_end_time_display = hour;
			if(selected_end_time_display >= 24)
			{
				if(selected_end_time_display > 24)
					selected_end_time_display -= 24;
				else
					selected_end_time_display -= 12;
				selected_end_time_display = selected_end_time_display.toString() + ":" + min.toString() + "am";
			}
			else if(selected_end_time_display >= 12)
			{
				if(selected_end_time_display > 12)
					selected_end_time_display -= 12;
				selected_end_time_display = selected_end_time_display.toString() + ":" + min.toString() + "pm";
			}
			else if(selected_end_time_display == 0)
			{
				selected_end_time_display = "12:" + min.toString() + "am";
			}
			else
			{
				selected_end_time_display = selected_end_time_display.toString() + ":" + min.toString() + "am";
			}
			<?php
				if(strcmp($_GET['reschedule'],''))
					print("\$(\"#tooltip\").html(\"<a href='javascript:reset_calendar()'><img style='margin-top:-12px; margin-right:-12px; float:right' src='images/close_window.png'></a><b>Room \"+rooms[room_id]+\"</b><br><img src='images/checkmark.gif' align='left' /> Start Time: \"+selected_start_time_display+\"<br><img src='images/checkmark.gif' align='left' /> End Time: \"+selected_end_time_display+\"<br><a href='".WEB_ROOT."reschedule_reservation.php?selected_date=$todays_hours->date&reschedule=".$_GET['reschedule']."&room_id=\"+selected_room+\"&start_time=\"+selected_start_time+\"&end_time=\"+selected_end_time+\"'><center><div style='margin-top:5px; padding:3px; background-color:#666; border-radius:4px; font-size:11px'>Reschedule Now!</div></center></a>\");\n");
				else
					print("\$(\"#tooltip\").html(\"<a href='javascript:reset_calendar()'><img style='margin-top:-12px; margin-right:-12px; float:right' src='images/close_window.png'></a><b>Room \"+rooms[room_id]+\"</b><br><img src='images/checkmark.gif' align='left' /> Start Time: \"+selected_start_time_display+\"<br><img src='images/checkmark.gif' align='left' /> End Time: \"+selected_end_time_display+\"<br><a href='".WEB_ROOT."confirm_reservation.php?selected_date=$todays_hours->date&room_id=\"+selected_room+\"&start_time=\"+selected_start_time+\"&end_time=\"+selected_end_time+\"'><center><div style='margin-top:5px; padding:3px; background-color:#666; border-radius:4px; font-size:11px'>Confirm Reservation!</div></center></a>\");\n");
			?>
		}
		else if($(this).hasClass("no_time_to_start"))
		{
			// there is not enough time to satisfy the minimum reservation length with a start_time at this slot
			alert("There is not enough time to satisfy the minimum reservation length of (<?php print(DEFAULT_MIN_RES_LEN); ?> minutes) with a starting time at this slot.");
			reset_calendar();
		}
		else
		{
			// reset previous selection
			$(".reservation_calendar td.slot_held").removeClass("slot_held").addClass("slot_available");
			$(".reservation_calendar td.slot_held_fcfs").removeClass("slot_held_fcfs").addClass("slot_fcfs");
			$(".reservation_calendar td.slot_held_light").addClass("slot_available");
			$(".reservation_calendar td.slot_held_light_fcfs").addClass("slot_fcfs");
			$(".reservation_calendar td").removeClass("slot_held_light");
			$(".reservation_calendar td").removeClass("slot_held_light_fcfs");
			
			var slot_count = 1;
			var selected_start_time_id = $(this).attr("id");
			var selected_start_time_parts = selected_start_time_id.split("-");
			selected_room = selected_start_time_parts[0];
			var room_parts = selected_room.split("_");
			room_id = room_parts[0];
			selected_start_time = selected_start_time_parts[1];
			selected_start_time_display = selected_start_time.substr(8,2);
			if(selected_start_time_display >= 24)
			{
				if(selected_start_time_display > 24)
					selected_start_time_display -= 24;
				else
					selected_start_time_display -= 12;
				selected_start_time_display = selected_start_time_display.toString() + ":" + selected_start_time.substr(10,2) + "am";
			}
			else if(selected_start_time_display >= 12)
			{
				if(selected_start_time_display > 12)
					selected_start_time_display -= 12;
				selected_start_time_display = selected_start_time_display + ":" + selected_start_time.substr(10,2) + "pm";
			}
			else if(selected_start_time_display == 0)
			{
				selected_start_time_display = "12:" + selected_start_time.substr(10,2) + "am";
			}
			else
			{
				selected_start_time_display = selected_start_time_display.toString() + ":" + selected_start_time.substr(10,2) + "am";
			}
			$(this).addClass("slot_held");

			var next = $(this).next();
			
			for(var j=1;j<max_reservation_length_slots;j++)
			{
				if( $(next).hasClass('slot_available'))
				{
					$(next).addClass("slot_held_light");
					$(next).removeClass("slot_available");
					next = $(next).next();
					end_id = $(next).attr("id");
				}
				else
					break;
				
				slot_count++;
			}
			
		
			// show tool tip
			var pos = $(this).offset();
			var width = $("#tooltip").width();
			// console.log("left: " + pos.left + " top: " + pos.top);
			var container_pos = $("#calendar_container").offset();
			$("#tooltip").css( { "position":"absolute", "left": (pos.left - container_pos.left) + "px", "top":pos.top-400 + "px" } );
			// $("#tooltip").css( { "position":"absolute", "left": (pos.left + 8 - (width/2)) + "px", "top":pos.top-104 + "px" } );
			$("#tooltip").html("<a href='javascript:reset_calendar()'><img style='margin-top:-12px; margin-right:-12px; float:right' src='images/close_window.png'></a><b>Room "+rooms[room_id]+"</b><br><img src='images/checkmark.gif' align='left' /> Start Time: "+selected_start_time_display+"<br><br><br><center>Select End Time</center>");
			$("#tooltip").show();
		}
	});
	
<?php
if(!strcmp($user_type,'staff') || !strcmp($user_type,'admin'))
{
?>
	

	$("td.slot_fcfs").click(function() {
		
		
		if($(this).hasClass("slot_held_light_fcfs"))
		{
			// TODO: account for RES_PRECISION == MIN_RES_LEN_MIN (allow end_time slot to be start_time slot in that case)
		
			$(this).addClass("slot_held_fcfs").removeClass("slot_held_light_fcfs");
			var prev = $(this).prev();
			while($(prev).hasClass('slot_held_light_fcfs'))
			{
				$(prev).addClass("slot_held_fcfs").removeClass("slot_held_light_fcfs");
				prev = $(prev).prev();
			}
			
			$(".reservation_calendar td.slot_held_light_fcfs").removeClass("slot_held_light_fcfs").addClass("slot_fcfs");
			
			var selected_end_time_id = $(this).attr("id");
			var selected_end_time_parts = selected_end_time_id.split("-");
			selected_room = selected_end_time_parts[0];
			var room_parts = selected_room.split("_");
			room_id = room_parts[0];
			selected_end_time = selected_end_time_parts[1];
			
			// add res_presision to the time to get the ending time of the selected reservation
			var year = selected_end_time.substring(0,4);
			var month = selected_end_time.substring(4,6);
			var day = selected_end_time.substring(6,8);
			var hour = selected_end_time.substring(8,10);
			var min = selected_end_time.substring(10,12);
			var sec = selected_end_time.substring(12,14);
			//alert(year+"-"+month+"-"+day+"-"+hour+"-"+min+"-"+sec);
			var newDateObj = new Date();
			var oldDateObj = new Date(year,month,day,hour,min,sec);
			newDateObj.setTime(oldDateObj.getTime() + (<?php print(RES_PRECISION); ?> * 60 * 1000));
			//alert(oldDateObj.toString());
			//alert(newDateObj.toString());
			var year = newDateObj.getFullYear();
			var month = newDateObj.getMonth().toString();
			if(month.length == 1) month = "0"+month;
			var day = newDateObj.getDate().toString();
			if(day.length == 1) day = "0"+day;
			var hour = newDateObj.getHours().toString();
			if(hour.length == 1) hour = "0"+hour;
			var min = newDateObj.getMinutes().toString();
			if(min.length == 1) min = "0"+min;
			var sec = newDateObj.getSeconds().toString();
			if(sec.length == 1) sec = "0"+sec;
			//alert(year+"-"+month+"-"+day+"-"+hour+"-"+min+"-"+sec);
			selected_end_time = year.toString()+month.toString()+day.toString()+hour.toString()+min.toString()+sec.toString();
			selected_end_time_display = hour;
			if(selected_end_time_display > 12)
			{
				selected_end_time_display -= 12;
				selected_end_time_display = selected_end_time_display.toString() + ":" + min.toString() + "pm";
			}
			else
			{
				selected_end_time_display = selected_end_time_display.toString() + ":" + min.toString() + "am";
			}
			
			<?php
				if(strcmp($_GET['reschedule'],''))
					print("\$(\"#tooltip\").html(\"<a href='javascript:reset_calendar()'><img style='margin-top:-12px; margin-right:-12px; float:right' src='images/close_window.png'></a><b>Room \"+rooms[room_id]+\"</b><br><img src='images/checkmark.gif' align='left' /> Start Time: \"+selected_start_time_display+\"<br><img src='images/checkmark.gif' align='left' /> End Time: \"+selected_end_time_display+\"<br><a href='".WEB_ROOT."reschedule_reservation.php?selected_date=$todays_hours->date&reschedule=".$_GET['reschedule']."&room_id=\"+selected_room+\"&start_time=\"+selected_start_time+\"&end_time=\"+selected_end_time+\"'><center><div style='margin-top:5px; padding:3px; background-color:#666; border-radius:4px; font-size:11px'>Reschedule Now!</div></center></a>\");\n");
				else
					print("\$(\"#tooltip\").html(\"<a href='javascript:reset_calendar()'><img style='margin-top:-12px; margin-right:-12px; float:right' src='images/close_window.png'></a><b>Room \"+rooms[room_id]+\"</b><br><img src='images/checkmark.gif' align='left' /> Start Time: \"+selected_start_time_display+\"<br><img src='images/checkmark.gif' align='left' /> End Time: \"+selected_end_time_display+\"<br><a href='".WEB_ROOT."confirm_reservation.php?selected_date=$todays_hours->date&room_id=\"+selected_room+\"&start_time=\"+selected_start_time+\"&end_time=\"+selected_end_time+\"'><center><div style='margin-top:5px; padding:3px; background-color:#666; border-radius:4px; font-size:11px'>Confirm Reservation!</div></center></a>\");\n");
			?>
		}
		else if($(this).hasClass("no_time_to_start"))
		{
			// there is not enough time to satisfy the minimum reservation length with a start_time at this slot
			alert("there is not enough time to satisfy the minimum reservation length with a start_time at this slot");
			reset_calendar();
		}
		else
		{
			// reset previous selection
			$(".reservation_calendar td.slot_held").removeClass("slot_held").addClass("slot_available");
			$(".reservation_calendar td.slot_held_fcfs").removeClass("slot_held_fcfs").addClass("slot_fcfs");
			$(".reservation_calendar td.slot_held_light").addClass("slot_available");
			$(".reservation_calendar td.slot_held_light_fcfs").addClass("slot_fcfs");
			$(".reservation_calendar td").removeClass("slot_held_light");
			$(".reservation_calendar td").removeClass("slot_held_light_fcfs");
			
			var slot_count = 1;
			var selected_start_time_id = $(this).attr("id");
			var selected_start_time_parts = selected_start_time_id.split("-");
			selected_room = selected_start_time_parts[0];
			var room_parts = selected_room.split("_");
			room_id = room_parts[0];
			selected_start_time = selected_start_time_parts[1];
			selected_start_time_display = selected_start_time.substr(8,2);
			if(selected_start_time_display > 12)
			{
				selected_start_time_display -= 12;
				selected_start_time_display = selected_start_time_display + ":" + selected_start_time.substr(10,2) + "pm";
			}
			else if(selected_start_time_display == 0)
			{
				selected_start_time_display = "12:" + selected_start_time.substr(10,2) + "am";
			}
			else
			{
				selected_start_time_display = selected_start_time_display.toString() + ":" + selected_start_time.substr(10,2) + "am";
			}
			$(this).removeClass("slot_fcfs").addClass("slot_held_fcfs");

			var next = $(this).next();
			
			for(var j=1;j<max_reservation_length_slots;j++)
			{
				if( $(next).hasClass('slot_fcfs'))
				{
					$(next).addClass("slot_held_light_fcfs");
					$(next).removeClass("slot_fcfs");
					next = $(next).next();
					end_id = $(next).attr("id");
				}
				else
					break;
				
				slot_count++;
			}
			
		
			// show tool tip
			var pos = $(this).offset();
			var width = $("#tooltip").width();
			// console.log("left: " + pos.left + " top: " + pos.top);
			var container_pos = $("#calendar_container").offset();
			$("#tooltip").css( { "position":"absolute", "left": (pos.left - container_pos.left) + "px", "top":pos.top-400 + "px" } );
			// $("#tooltip").css( { "position":"absolute", "left": (pos.left + 8 - (width/2)) + "px", "top":pos.top-104 + "px" } );
			$("#tooltip").html("<a href='javascript:reset_calendar()'><img style='margin-top:-12px; margin-right:-12px; float:right' src='images/close_window.png'></a><b>Room "+rooms[room_id]+"</b><br><img src='images/checkmark.gif' align='left' /> Start Time: "+selected_start_time_display+"<br><br><br><center>Select End Time</center>");
			$("#tooltip").show();

		}
	});
<?php
}
?>

		
<?php
if(!strcmp($user_type,'staff') || !strcmp($user_type,'admin'))
{
?>
	$("td.slot_reserved").click(function() {

	});
	
	$("td.slot_checked_out").click(function() {
	
		// reset previous selection
		$(".reservation_calendar td.slot_held").removeClass("slot_held").addClass("slot_available");
		$(".reservation_calendar td.slot_held_fcfs").removeClass("slot_held_fcfs").addClass("slot_fcfs");
		$(".reservation_calendar td.slot_held_light").addClass("slot_available");
		$(".reservation_calendar td.slot_held_light_fcfs").addClass("slot_fcfs");
		$(".reservation_calendar td").removeClass("slot_held_light");
		$(".reservation_calendar td").removeClass("slot_held_light_fcfs");
			
		var reservation = $(this).attr("id");
		var reservation_parts = reservation.split("_");
		var reservation_id =  reservation_parts[0];
		var patron_name = decodeURIComponent(reservation_parts[1]);
				
		// show tool tip
		//var pos = $(this).offset();
		//var width = $("#tooltip").width();
		//alert("pos: " + pos.left + " " + pos.right + " width: " + width);
		//console.log("left: " + pos.left + " top: " + pos.top);
		//$("#tooltip").css( { "position":"absolute", "left": (pos.left + 8 - (width/2)) + "px", "top":pos.top-104 + "px" } );
		//$("#tooltip").html("<a href='javascript:reset_calendar()'><img style='margin-top:-12px; margin-right:-12px; float:right' src='images/close_window.png'></a><center><b>"+patron_name+"</b><br><br><a href='<?php print(WEB_ROOT.$user_type); ?>/reservations/checkin/"+reservation_id+"'>CHECK-IN KEY</a></center>");
		//$("#tooltip").show();
	});
	
	
	$("td.slot_completed_reservation").click(function() {
	
		// reset previous selection
		$(".reservation_calendar td.slot_held").removeClass("slot_held").addClass("slot_available");
		$(".reservation_calendar td.slot_held_fcfs").removeClass("slot_held_fcfs").addClass("slot_fcfs");
		$(".reservation_calendar td.slot_held_light").addClass("slot_available");
		$(".reservation_calendar td.slot_held_light_fcfs").addClass("slot_fcfs");
		$(".reservation_calendar td").removeClass("slot_held_light");
		$(".reservation_calendar td").removeClass("slot_held_light_fcfs");
			
		var reservation = $(this).attr("id");
		var reservation_parts = reservation.split("_");
		var reservation_id =  reservation_parts[0];
		var patron_name = decodeURIComponent(reservation_parts[1]);
				
		// show tool tip
		var pos = $(this).offset();
		var width = $("#tooltip").width();
		//alert("pos: " + pos.left + " " + pos.right + " width: " + width);
		var container_pos = $("#calendar_container").offset();
		$("#tooltip").css( { "position":"absolute", "left": (pos.left - container_pos.left) + "px", "top":pos.top-400 + "px" } );
		// $("#tooltip").css( { "position":"absolute", "left": (pos.left + 8 - (width/2)) + "px", "top":pos.top-104 + "px" } );
		//http://lib6.lib.pdx.edu/flakus/sr/reservations/cancel/315
		$("#tooltip").html("<a href='javascript:reset_calendar()'><img style='margin-top:-12px; margin-right:-12px; float:right' src='images/close_window.png'></a><center><b>"+patron_name+"</b><br><br>Reservation Completed</center>");
		
		$("#tooltip").show();
	});
	
<?php
}
?>

	$("td.slot_too_short").click(function() {
		alert("timeslot too short");
	});
	
	//$("body").click(function() {
	//	if(on_cal==false)
	//	{
	//		alert("clear selections");
	//	}
	//});

});
</script>