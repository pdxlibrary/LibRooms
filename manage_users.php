<?php

session_start();
require_once("config/config.inc.php");
require_once("config/strings.inc.php");

require_once("includes/Database.php");

require_once("load.php");
require_once("includes/load_hours_data.php");
require_once("includes/load_rooms.php");
require_once("includes/error_checking.php");

require_once("includes/verify_access.php");
restrict_access($db,array("admin"));

if(isset($_GET['create_user']))
{
	// create a new user account for patron id: $_GET['find_user']
	$find_user = $_GET['find_user'];
	$synch_result = synch_user($find_user);
}

if(isset($_GET['remove_role']))
{
	$remove_role = $_GET['remove_role'];
	$db->update("users_roles","active","0","id like '$remove_role'");
}

if(isset($_GET['add_role']))
{
	// add new admin
	$fields = array("user_id","role_id","active");
	$values = array($_GET['user_id'],$_GET['role_id'],"1");
	if($db->noDuplicate("users_roles",$fields,$values))
	{
		$fields[] = "date_added";
		$values[] = date("Y-m-d H:i:s");
		$db->insert("users_roles",$fields,$values);
	}
}

if(isset($_GET['patron_id']))
{
	$patron_id = $_GET['patron_id'];

	// lookup user_id from patron_id
	$select_user = "select * from users where patron_id like '$patron_id' AND active like '1'";
	$res_user = $db->query($select_user);
	if($res_user->numRows() > 0)
	{
		$res_user->fetchInto($user);
		$user_id = $user->id;
		//print_r($user);
	}
}



require_once("includes/header.php");

print("<style>\n");
print("#PSUContent td {font-size:12px;} \n");
print("</style>\n");

print("<div id='PageTitle'>Manage Users</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Manage Users</div>\n");

print("<br>\n");

print("<table width='100%'><tr valign='top'><td width='50%'>");

print("<h2>Admin Users</h2>\n");
$select_admin_users = "select users.id,first_name,last_name,patron_id,email,role,users_roles.id as users_roles_id from users,users_roles,roles where role like 'Admin' and users.id = users_roles.user_id and roles.id = users_roles.role_id and users.active like '1' and users_roles.active like '1' and roles.active like '1'";
$res_users = $db->query($select_admin_users);
if($res_users->numRows() > 0)
{
	print("<table border width='100%'><tr><th>Name</th><th>Email</th><th>&nbsp;</th></tr>\n");
	while($res_users->fetchInto($user))
		print("<tr><td>$user->first_name $user->last_name</td><td>$user->email</td><td><a href='?remove_role=$user->users_roles_id'><img src='images/icon_remove.gif' border='0'></a></tr>\n");
	print("</table>\n");
}
else
{
	// this state should never be reached
	print("There are no admin users.");
}

print("<br>\n");

print("<h2>Circulation Users</h2>\n");
$select_admin_users = "select users.id,first_name,last_name,patron_id,email,role,users_roles.id as users_roles_id from users,users_roles,roles where role like 'Staff' and users.id = users_roles.user_id and roles.id = users_roles.role_id and users.active like '1' and users_roles.active like '1' and roles.active like '1'";
$res_users = $db->query($select_admin_users);
if($res_users->numRows() > 0)
{
	print("<table border width='100%'><tr><th>Name</th><th>Email</th><th>&nbsp;</th></tr>\n");
	while($res_users->fetchInto($user))
		print("<tr><td>$user->first_name $user->last_name</td><td>$user->email</td><td><a href='?remove_role=$user->users_roles_id'><img src='images/icon_remove.gif' border='0'></a></tr>\n");
	print("</table>\n");
}
else
{
	print("There are no circulation users.");
}

print("</td><td align='right'>\n");

// form for finding and adding a user as a special user
print("<form>\n");
print("Last Name or ".PATRON_ID_LABEL.": <input type='text' name='find_user' value='$find_user'>\n");
print("<input type='submit' value='Find User'>\n");
print("</form>\n");

if(isset($_GET['find_user']))
{
	$find_user = $_GET['find_user'];
	$found_users = find_users_by_name_or_patron_id($fine_user);
	
	//print_r($found_users);
	
	
	// TODO: use find_users_by_name_or_patron_id($find_user) rather than doing the query here, so the full user object can be returned, including role details. Then remove existing roles from options
	$select_users = "select users.id,first_name,last_name,patron_id,email,role from users,users_roles,roles where (patron_id like '$find_user' OR last_name like '$find_user') and role like 'Admin' and users.id = users_roles.user_id and roles.id = users_roles.role_id and users.active like '1' and users_roles.active like '1' and roles.active like '1'";
	$select_users = "select * from users where patron_id like '$find_user' OR barcode like '$find_user' OR last_name like '$find_user' and active like '1'";
	$res_users = $db->query($select_users);
	if($res_users->numRows() > 0)
	{
		print("<form>\n");
		print("<table border><tr><th>Name</th><th>".PATRON_ID_LABEL."</th></tr>\n");
		while($res_users->fetchInto($user))
		{
			print("<tr><td>$user->first_name $user->last_name</td><td>$user->patron_id</td>");
			print("<td>\n");
			print("<form>\n");
			print("<input type='hidden' name='add_role' value='1'>\n");
			print("<input type='hidden' name='user_id' value='$user->id'>\n");
			print("<select name='role_id'><option value=''>Select Role</option>\n");
			$select_roles = "select * from roles where role not like 'Patron' and active like '1' order by role";
			$res_users = $db->query($select_roles);
			while($res_users->fetchInto($role))
			{
				print("<option value='$role->id'>$role->role</option>\n");
			}
			print("</select>\n");
			print("<input type='submit' value='Add Role'>\n");
			print("</form>\n");
			print("</td>\n");
			print("</tr>\n");
		}
		print("</table>\n");
		print("</form>\n");
	}
	else
	{
		// TODO: if user has never logged-in, then they may need to be added via an api call to III
		print("No users found matching that search.<br>\n");
		
		if(is_numeric($find_user) && strlen(find_user)==9 && !strcmp(substr($find_user,0,1),'9'))
		{
			// use the patron id to lookup their info from III
			require_once("includes/patron_api.php");
			
			$record = load_patron_record($find_user);
		
			if(!strcmp($record["UNIQUE ID"],''))
			{
				// no user found for barcode entered
				$error_messages[] = "Patron [$find_user] could not be found in the III system.";
			}
			else
			{
				$full_name = $record['PATRN NAME'];
				$name_parts = explode(",",$full_name);
				$ln = $name_parts[0];
				$fn = trim($name_parts[1]);
				$email = $record['EMAIL ADDR'];
				
				print("<br>\n");
				print("Patron [$find_user] does not have a local account yet.<br><br>\n");
				print("<b>Would you like to load their information from III?</b><br>\n");
				print("<table>");
				print("<tr><td>Name:</td><td>$full_name</td></tr>\n");
				print("<tr><td>Barcode:</td><td>$find_user</td></tr>\n");
				print("<tr><td>Email:</td><td>$email</td></tr>\n");
				print("</table>");
				print("<form>\n");
				print("<input type='hidden' name='find_user' value='$find_user'>\n");
				print("<input type='submit' name='create_user' value='Create User Account'>\n");
				print("</form>\n");
			}
		}
	}
}


print("</td></tr></table>\n");
	
require_once("includes/footer.php");

?>