<?php

session_start();

if(isset($_GET['debug']))
	print_r($_SESSION);

require_once("config/strings.inc.php");

print("<title>Reserve a Study Room | Portland State Library</title>\n");

?>

<meta http-equiv="X-UA-Compatible" content="IE=8" >

<?php

/*	WORDPRESS HEADER */
print(file_get_contents("http://library.pdx.edu/includes/psu_library_header_wp.inc.php"));

// adjustments
?>
<style>
#main {display:inline;}
.hentry {padding: 0 47px 30px 47px;}
#search {font-size: 15px;margin: 0;max-width: 100%;vertical-align: middle;}
#admin_toolbar > tbody > tr > td {text-align:center;}
#PageTitle {font-size:32px}
#requesting_patron {height:32px;}
input[type=text] {height:32px; padding: 0 10px;}
input[type=submit] {height:32px; padding: 0 10px;}
h2 {font-size:20px}
* {box-sizing: inherit;}
body {line-height:1.25;}
table {border-collapse: inherit;}
.entry-content {max-width:100%;}
</style>

<?php
/*	/WORDPRESS HEADER */




$page = str_replace(WEB_ROOT,"",$_SERVER['PHP_SELF']);

if(is_array($_SESSION['LibRooms']['Roles']))
{
	if(in_array('Staff',$_SESSION['LibRooms']['Roles']) || in_array('Admin',$_SESSION['LibRooms']['Roles']))
	{
		print("<center><form action='search.php'>\n");
		print("<table id='admin_toolbar' style='border:1px solid #444;'><tr>\n");
		
		print("<td>Search:<input id='search' size='18' type='text' name='search'><input type='submit' value='Go'></td>\n");
		
		//print("<td align='center' style='border-right:1px dotted #FFF;'><a href='quick_res.php'>QuickRes</a></td>\n");
		print("<td align='center' style='border-right:1px dotted #FFF;'><a href='manage_reservations.php'>Reservations</a></td>\n");
		print("<td align='center' style='border-right:1px dotted #FFF;'><a href='manage_rooms.php'>Rooms</a></td>\n");
		print("<td align='center' style='border-right:1px dotted #FFF;'><a href='manage_room_groups.php'>Room Groups</a></td>\n");
		print("<td align='center' style='border-right:1px dotted #FFF;'><a href='manage_amenities.php'>Amenities</a></td>\n");
		print("<td align='center' style='border-right:1px dotted #FFF;'><a href='manage_users.php'>Users</a></td>\n");
		print("<td align='center' style='border-right:1px dotted #FFF;'><a href='manage_settings.php'>Settings</a></td>\n");
		print("<td align='center'><a href='reports.php'>Reports</a></td>\n");
		//print("<td align='center'><a href='#'>Fines</a></td>\n");
		print("</tr></table></form>\n");
		print("</center>\n");
	}
}


if(!strcmp($page,'reservation_calendar.php'))
{
	print("<script type='text/javascript' src='https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'></script>\n");
	print("<script type='text/javascript' src='js/jquery.idle.js'></script>\n");
	require_once("includes/calendar_js.php");
	print("<link rel='stylesheet' type='text/css' href='".WEB_ROOT."css/calendar.css' media='all'>\n");
	print("<!--[if IE]>\n");
	print("<link rel='stylesheet' type='text/css' href='".WEB_ROOT."css/calendar-ie.css' media='all'>\n");
	print("<![endif]-->\n");
}

print("<link rel='stylesheet' type='text/css' href='".WEB_ROOT."css/base.css' media='all'>\n");

?>

<script>
jQuery(document).ready(function($) {
	// set focus to the patron id input field
	if($("#requesting_patron").length != 0)
		$("#requesting_patron").focus();
	else if($("#checkin_key_barcode").length != 0)
		$("#checkin_key_barcode").focus();
	else if($("#checkout_key_barcode").length != 0)
		$("#checkout_key_barcode").focus();
	else if($("#search").length != 0)
		$("#search").focus();
});
</script>

<?php

print("<div id='LibRooms'>\n");
print("<div id='PSUContent'>\n");


require_once("includes/nav_links.php");


?>
