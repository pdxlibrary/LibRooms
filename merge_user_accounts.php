<?php


session_start();
require_once("config/config.inc.php");
require_once("includes/Database.php");


require_once("load.php");

require_once("includes/verify_access.php");
restrict_access($db,array("staff","admin"));

$reservation_id = $_GET['reservation_id'];
$user_id = $_GET['user_id'];

require_once("includes/header.php");


if(isset($_GET['user_id']))
{
	// lookup user by user id
	$users = find_users_by_name_or_patron_id($_GET['user_id']);
	
	$earliest_added_id = null;
	$earliest_added_datestamp = null;
	
	if(count($users) > 1)
	{
		print("<br><h1>Multiple Accounts Found for User: ".count($users)." Accounts to Merge</h1>");
		foreach($users as $user)
		{
			if(strtotime($user->date_added) < $earliest_added_datestamp || $earliest_added_datestamp == null)
			{
				$earliest_added_datestamp = strtotime($user->date_added);
				$earliest_added_id = $user->id;
			}
			print("$user->last_name, $user->first_name - Created: ".date("m/d/Y",strtotime($user->date_added))."<br>\n");
		}
	}
	
	// print("earliest_added_id: $earliest_added_id<br>\n");
	// print("earliest_added_datestamp: ".date("m/d/Y",$earliest_added_datestamp)."<br>\n");
	
	foreach($users as $user)
	{
		// update user_id to oldest user_id in relevant tables
		$db->update("transaction_log",array("user_id"),array($earliest_added_id),"user_id like '$user->id'",$user->id,"Merged account with $earliest_added_id");
		$db->update("reservations",array("user_id"),array($earliest_added_id),"user_id like '$user->id'",$user->id,"Merged account with $earliest_added_id");
		$db->update("users_roles",array("user_id"),array($earliest_added_id),"user_id like '$user->id'",$user->id,"Merged account with $earliest_added_id");
		
		// deactivate the merged IDs
		if(strcmp($user->id,$earliest_added_id))
			$db->update("users",array("active"),array("0"),"id like '$user->id'",$user->id,"Merged account with $earliest_added_id");
	}
	
	print("<h2>Accounts Merged.</h2>\n");
	
}
	
require_once("includes/footer.php");

?>