<?php


session_start();

require_once("config/config.inc.php");
require_once("config/strings.inc.php");
require_once("includes/Database.php");

// TODO: move to config file
define("ROOM_IMAGE_MAX_WIDTH",250);
define("ROOM_IMAGE_MAX_HEIGHT",250);
define("ROOM_MAP_MAX_WIDTH",250);
define("ROOM_MAP_MAX_HEIGHT",250);

require_once("load.php");

$room_id = $_GET['room_id'];
$rooms = load_rooms($room_id);
$room = $rooms[$room_id];
$all_amenities = load_amenities();

require_once("includes/verify_access.php");
if(isset($_GET['login']) && !isset($_SESSION['LibRooms']['UserID']))
	restrict_access($db,array("patron"));
else
	restrict_access($db,array("public","patron"));

if(isset($_SESSION['LibRooms']['Roles']))
{
	if(in_array('Staff',$_SESSION['LibRooms']['Roles']))
		$user_type = "staff";
	if(in_array('Admin',$_SESSION['LibRooms']['Roles']))
		$user_type = "admin";
}

if(isset($_GET['remove_amenity']))
{
	$remove_amenity = $_GET['remove_amenity'];
	$db->update('study_rooms_amenities','active','0',"id like '$remove_amenity'");
	$rooms = load_rooms($room_id);
	$room = $rooms[$room_id];
}

require_once("includes/header.php");

$amenity_options = array();
foreach($all_amenities as $amenity_id => $amenity)
{
	if(strcmp($amenity->description,''))
		$amenity_options[] = $amenity->name." - ".$amenity->description;
	else
		$amenity_options[] = $amenity->name;
}



?>
<script language="JavaScript" src="js/jquery.ui.widget.js"></script>
<script language="JavaScript" src="js/jquery.ui.dialog.js"></script>
<script language="JavaScript" src="js/jquery.ui.core.js"></script>
<script language="JavaScript" src="js/jquery.ui.position.js"></script>
<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.11/themes/base/jquery-ui.css" type="text/css" media="all" />

<script type="text/javascript" src="includes/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>
<script type="text/javascript" src="includes/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<link rel="stylesheet" type="text/css" href="includes/fancybox/jquery.fancybox-1.3.4.css" media="screen" />
<script type="text/javascript">
		jQuery(document).ready(function($) {
		<?php
		/*
			foreach($room->images as $image_type => $images)
			{
				foreach($images as $image)
				{
					print("\$(\"#image$image->id\").fancybox();\n");
					break;
				}
				foreach($images as $image)
				{
					print("\$(\"#thumbnail$image->id\").fancybox();\n");
					
				}
			}
		*/
		?>
		
		
			$("a[rel=room]").fancybox({
				'transitionIn'		: 'none',
				'transitionOut'		: 'none',
				'titlePosition' 	: 'over',
				'titleFormat'       : function(title, currentArray, currentIndex, currentOpts) {
					return '<span id="fancybox-title-over">Image ' +  (currentIndex + 1) + ' / ' + currentArray.length + ' ' + title + '</span>';
				}
			});
			
			$("a[rel=map]").fancybox({ });
			
			$( "#amenities_dialog" ).dialog({
				width: 1000,
				height: 800,
				autoOpen: false,
				modal: true,
				closeText: 'hide',
				beforeClose: function(event, ui) { document.location.href='room_details.php?room_id=<?php print($room_id); ?>'; }
			});
			
			$( "#keys_dialog" ).dialog({
				width: 400,
				height: 350,
				autoOpen: false,
				modal: true,
				closeText: 'hide',
				beforeClose: function(event, ui) { document.location.href='room_details.php?room_id=<?php print($room_id); ?>'; }
			});
		});
		
		
		
		function open_amenities_dialog()
		{
			$("#amenities_dialog").dialog('open');
		}
		
		function close_amenities_dialog()
		{
			$("#amenities_dialog").dialog("close");
		}
		
		function open_keys_dialog()
		{
			$("#keys_dialog").dialog('open');
		}
		
		function close_keys_dialog()
		{
			$("#keys_dialog").dialog("close");
		}

</script>

<style type="text/css">
.wraptocenter {
    display: table-cell;
    text-align: center;
    vertical-align: middle;
    width: 50px;
    height: 50px;
}
.wraptocenter * {
    vertical-align: middle;
}
/*\*//*/
.wraptocenter {
    display: block;
}
.wraptocenter span {
    display: inline-block;
    height: 100%;
    width: 1px;
}
/**/
</style>
<!--[if lt IE 8]>
<style>
.wraptocenter span {
    display: inline-block;
    height: 100%;
}
</style>
<![endif]-->



<?php

if(!strcmp($user_type,'admin'))
{

?>
<script type="text/javascript" src="js/jquery.jeditable.js"></script>
<script type="text/javascript">
		jQuery(document).ready(function($) {
			
			$(".editable_text").editable("<?php print(WEB_ROOT); ?>/save.php", { 
			  indicator : "<img src='images/loading_spinner.gif'>",
			  submitdata: {table: 'study_rooms',row_id: "<?php print($room_id); ?>"},
			  tooltip   : "Click to edit...",
			  submit	: "Save",
			  style  	: "inherit"
			});
			
			$(".editable_fcfs").editable("<?php print(WEB_ROOT); ?>/save.php", {
			  data   	: '<?php print  json_encode(array("No"=>"No","Yes"=>"Yes")); ?>',
			  type   	: 'select',
			  submitdata: {table: 'study_rooms',row_id: "<?php print($room_id); ?>"},
			  indicator : "<img src='images/loading_spinner.gif'>",
			  tooltip   : "Click to edit...",
			  submit	: "Save",
			  style  	: "inherit"
			});
			
			$(".editable_out_of_order").editable("<?php print(WEB_ROOT); ?>/save.php", {
			  data   	: '<?php print  json_encode(array("No"=>"No","Yes"=>"Yes")); ?>',
			  type   	: 'select',
			  submitdata: {table: 'study_rooms',row_id: "<?php print($room_id); ?>"},
			  indicator : "<img src='images/loading_spinner.gif'>",
			  tooltip   : "Click to edit...",
			  submit	: "Save",
			  style  	: "inherit"
			});
			
			<?php
				$all_room_groups = load_room_groups();
				$room_group_options = array();
				foreach($all_room_groups as $room_group)
				{
					$room_group_options[$room_group->id] = $room_group->name;
				}
			?>
			$(".editable_room_group").editable("<?php print(WEB_ROOT); ?>/save.php", {
			  data   	: '<?php print  json_encode($room_group_options); ?>',
			  type   	: 'select',
			  submitdata: {table: 'study_rooms',row_id: "<?php print($room_id); ?>"},
			  indicator : "<img src='images/loading_spinner.gif'>",
			  tooltip   : "Click to edit...",
			  submit	: "Save",
			  style  	: "inherit"
			});
		});
		
		function openWindow(url,window_name,width,height)
		{
			if(width > 0 && height > 0)
			{
				window_size = "width="+width+", height="+height;
			}
			window.open(url, window_name, window_size);
		}
		
</script>

<?php

} // end of admin section to include jeditable javascript pieces



print("<div id='PageTitle'>Room Details - $room->room_number</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Room Details - $room->room_number</div>\n");

print("<br>\n");

	if(isset($_GET['debug']))
	{
		print("<pre>\n");
		print_r($room);
		print_r($all_amenities);
		print("</pre>\n");
	}
	
	print("<table width='100%'><tr valign='top'><td rowspan='2'>\n");
	
	print("<table>\n");
	
	// room number
	if(!strcmp($user_type,'admin')) $class = "editable editable_text";
	else							$class = "";
	print("<tr><td><b>Room Number:</b></td><td><div class='$class' id='room_number'>$room->room_number</div></td></tr>");
	
	// capacity
	if(!strcmp($user_type,'admin')) $class = "editable editable_text";
	else							$class = "";
	print("<tr><td><b>Capacity:</b></td><td><div class='$class' id='capacity'>$room->capacity</div></td></tr>");
	
	// first come, first serve
	if(!strcmp($room->fcfs,'Yes') || !strcmp($user_type,'admin'))
	{
		if(!strcmp($user_type,'admin'))
		{
			print("<tr><td><b>First-Come, First-Serve:</b></td><td><div class='editable editable_fcfs' id='fcfs'>$room->fcfs</div></td></tr>");
		}
		else
		{
			print("<tr><td colspan='2'><b>First Come First Serve</b><br>".FCFS_NOTE."</td></tr>");
		}
	}
	
	// out_of_order
	if(!strcmp($room->out_of_order,'Yes') || !strcmp($user_type,'admin'))
	{
		if(!strcmp($user_type,'admin'))
		{
			print("<tr><td><b>Out-of-Order:</b></td><td><div class='editable editable_out_of_order' id='out_of_order'>$room->out_of_order</div></td></tr>");
		}
		else
			print("<tr><td colspan='2' style='color:red; font-weight:bold;'>This Room is Temporarily Out of Order</td></tr>");
	}
	
	if(!strcmp($user_type,'admin'))
	{
		print("<tr><td><b>Max Simultaneous Reservations:</b></td><td><div class='editable editable_text' id='max_simultaneous_reservations'>$room->max_simultaneous_reservations</div></td></tr>");
		
		print("<tr><td colspan='2'><b>Room Group:</b></td></tr>\n");
		print("<tr><td colspan='2' align='right'><div class='editable editable_room_group' id='room_group_id'>".$room_group_options[$room->room_group_id]."</div></td></tr>");
	}
	
	print("</table>\n");
	print("<br>\n");
	
	if(isset($room->amenities) && (count($room->amenities) > 0 || !strcmp($user_type,'admin')))
	{
		print("<h2>Amenities</h2>\n");
		print("<ul>\n");
		foreach($room->amenities as $amenity)
		{
			print("<li>$amenity->name");
			//if(!strcmp($user_type,'admin'))
				// print("<a href='room_details.php?room_id=$room_id&remove_amenity=$amenity->id'><img src='images/icon_remove.gif' alt='Remove amenity from this room' align='left'></a>");
			if(strcmp($amenity->description,''))
				print(" - $amenity->description");
			print("</li>\n");
		}
		print("</ul>\n");
		if(!strcmp($user_type,'admin'))
		{
			print("<a class='editable' href='javascript:open_amenities_dialog()'>Manage Amenities</a>\n");
		}
		
	}
	
	if(isset($room->keys) && !strcmp($user_type,'admin'))
	{
		print("<br><br><h2>Keys</h2>\n");
		print("<ul>\n");
		foreach($room->keys as $key)
		{
			print("<li>$key->key_barcode</li>\n");
		}
		print("</ul>\n");
		print("<a class='editable' href='javascript:open_keys_dialog()'>Manage Keys</a>\n");
	}
	
	print("</td><td style='border:1px solid #99F; border-radius:10px; background-color:#ebebeb;' width='".(ROOM_IMAGE_MAX_WIDTH+10)."'>\n");
	
		display_room_images($room,'room',ROOM_IMAGE_MAX_WIDTH,ROOM_IMAGE_MAX_HEIGHT);
	
	print("</td><td style='border:1px solid #99F; border-radius:10px; background-color:#ebebeb;' width='".(ROOM_IMAGE_MAX_WIDTH+10)."'>\n");
	
		display_room_images($room,'map',ROOM_MAP_MAX_WIDTH,ROOM_MAP_MAX_HEIGHT);

	print("</td></tr>\n");
	
	print("<tr><td align='center'>");
	
		if(!strcmp($user_type,'admin'))
			print("<a class='editable' href='javascript:openWindow(\"manage_images.php?type=room&room_id=$room_id\",\"Manage Images\",\"950\",\"800\")'>Manage Room Images</a>\n");
		
	print("</td><td align='center'>\n");
	
		if(!strcmp($user_type,'admin'))
			print("<a class='editable' href='javascript:openWindow(\"manage_images.php?type=map&room_id=$room_id\",\"Manage Images\",\"950\",\"800\")'>Manage Map Images</a>\n");
	
	print("</td></tr></table>\n");
	
	if(!strcmp($user_type,'admin'))
	{
		print("<br>\n");
		print("<a href='delete_room.php?room_id=$room_id' style='color:#fff; background-color:red; padding:5px; border-radius:5px;'>Delete this Room</a><br>\n");
		
		
		print("<div id='amenities_dialog' title='Manage Room Amenities'>\n");
		print("<iframe style='border: 0px;' SRC='manage_room_amenities.php?room_id=$room_id' width='100%' height='100%'></iframe>\n");
		print("</div>\n");
		
		print("<div id='keys_dialog' title='Manage Keys for Room $room->room_number'>\n");
		print("<iframe style='border: 0px;' SRC='manage_room_keys.php?room_id=$room_id' width='100%' height='100%'></iframe>\n");
		print("</div>\n");
	}
	
require_once("includes/footer.php");


function display_room_images($room,$type,$max_width,$max_height)
{
	$thumbnail_max_width = 60;
	$thumbnail_max_height = 60;
	
	if(is_array($room->images[$type]))
	{
		foreach($room->images[$type] as $image)
		{
			print("<div class='wraptocenter' style='background-color:#EBEBEB; vertical-align: middle; width:".($max_width+10)."px; height:".($max_height+10)."px;'><span></span>\n");
			if($image->width > 0 && $image->height > 0)
			{
				if($max_height > ($max_width * $image->height / $image->width))
				{
					// constrain width, if it's greater than max
					if($image->width > $max_width)
						print("<a id='image$image->id' href=\"javascript:jQuery('#thumbnail$image->id').trigger('click');\"><img src='$image->location' width='$max_width' style='width:$max_width; border-radius:10px;'></a>");
					else
						print("<a id='image$image->id' href=\"javascript:jQuery('#thumbnail$image->id').trigger('click');\"><img src='$image->location' style='border-radius:10px;'></a>");
				}
				else
				{
					// constrain height, if it's greater than max
					if($image->height > $max_height)
						print("<a id='image$image->id' href=\"javascript:jQuery('#thumbnail$image->id').trigger('click');\"><img src='$image->location' height='$max_height' style='height:$max_height; border-radius:10px;'></a>");
					else
						print("<a id='image$image->id' href=\"javascript:jQuery('#thumbnail$image->id').trigger('click');\"><img src='$image->location' style='border-radius:10px;'></a>");
				}
			}
			else
			{
				print("<a id='image$image->id' href=\"javascript:jQuery('#thumbnail$image->id').trigger('click');\"><img src='$image->location' style='border-radius:10px;'></a>");
			}
			print("</div>\n");
			break;
		}
		
		$per_row = 4;
		$in_row = 0;
		
		print("<table class='image_thumbnails' style='padding:0; width:100%'>\n");
		foreach($room->images[$type] as $image)
		{
			if(count($room->images[$type]) > 0)
			{
				if($in_row == 0)
				{
					print("<tr valign='middle'>");
				}
				
				//print("<div class='image_thumbnail' style='text-align: center; border: 1px solid #333333; margin:3px; float:left; width:".($thumbnail_max_width+2)."px; height:".($thumbnail_max_height+2)."px;'>\n");
				print("<td align='center' width='".(100/$per_row)."%' style='padding:0; background-color:#CCC'><div class='wraptocenter'><span></span>\n");
				if($image->width > 0 && $image->height > 0)
				{
					if($thumbnail_max_height > ($thumbnail_max_width * $image->height / $image->width))
					{
						// constrain width, if it's greater than max
						if($image->width > $thumbnail_max_width)
							print("<a id='thumbnail$image->id' rel='$type' class='thumbnail' href='$image->location'><img src='$image->location' width='$thumbnail_max_width' style=''></a>");
						else
							print("<a id='thumbnail$image->id' rel='$type' class='thumbnail' href='$image->location'><img src='$image->location' style=''></a>");
					}
					else
					{
						// constrain height, if it's greater than max
						if($image->height > $thumbnail_max_height)
							print("<a id='thumbnail$image->id' rel='$type' class='thumbnail' href='$image->location'><img src='$image->location' height='$thumbnail_max_height' style=''></a>");
						else
							print("<a id='thumbnail$image->id' rel='$type' class='thumbnail' href='$image->location'><img src='$image->location' style=''></a>");
					}
				}
				else
				{
					print("<a id='thumbnail$image->id' rel='$type' class='thumbnail' href='$image->location'><img src='$image->location' style=''></a>");
				}
				print("</div></td>\n");
				
				$in_row++;
				if($in_row >= $per_row)
				{
					print("</tr>\n");
					$in_row = 0;
				}
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
		print("</table>\n");
	}
	print("</div>\n");
}

?>

