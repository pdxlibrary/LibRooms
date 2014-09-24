<?php

function connect()
{
	if(!$link = mysql_connect(DB_HOST, DB_USER, DB_PASS))
	{
		$result = 0;
		print("Error connecting to MySQL Server [".DB_HOST."] with user account [".DB_USER."]!<br>\n");
	}
	else
	{
		if(!$conn = mysql_select_db(DB_NAME,$link))
		{
			print("Error selecting database<br>\n");
		}
	}
}

connect();

?>
