<?php

$query_string = $_SERVER['QUERY_STRING'];
$query_string = str_replace("&login","",$query_string);
$query_string = str_replace("&logoff","",$query_string);

if(strcmp($_SESSION['LibRooms']['UserID'],''))
{
	print("<div style='float:right; text-algin:right;'>".$_SESSION['LibRooms']['FirstName']." " .$_SESSION['LibRooms']['LastName']. " | <a href='reservation_calendar.php'>New Reservation</a> | <a href='my_reservations.php'>My Reservations</a> | <a href='http://library.pdx.edu/study-spaces-computers/study-rooms-faqs/' target='_blank'>FAQ</a> | <a href='".$_SERVER['PHP_SELF']."?$query_string&logoff'>Logoff</a>\n");
	// TODO: possibly add patron's remaining available credits for reservations and checkouts for today and the current week
	//print("<div style='padding-top:5px; font-size:12px; color:#999; text-align:right;'><i>Your remaining credits: <a href='#'>3 Hours</a>(today)/<a href='#'>9 Hours</a>(this week)</i></div>\n");
	print("</div>\n");
}
else
{
	print("<span style='float:right'><a href='".$_SERVER['PHP_SELF']."?$query_string&login'>Login</a> | <a href='http://library.pdx.edu/study-spaces-computers/study-rooms-faqs/' target='_blank'>FAQ</a></span>\n");
}

?>