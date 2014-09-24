<?php

session_start();
require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("load.php");

require_once("includes/verify_access.php");
restrict_access($db,array("admin","staff"));

if(isset($_SESSION['LibRooms']['Roles']))
{
	if(in_array('Staff',$_SESSION['LibRooms']['Roles']))
		$user_type = "staff";
	if(in_array('Admin',$_SESSION['LibRooms']['Roles']))
		$user_type = "admin";
}


require_once("includes/header.php");


print("<div id='PageTitle'>Manage Room Groups</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Manage Room Groups</div>\n");
print("<br>\n");

$all_room_groups = load_room_groups();


if(isset($_GET['new_room_group_name']) && strcmp(trim($_GET['new_room_group_name']),''))
{
	$new_room_group_name = $_GET['new_room_group_name'];
	$new_room_group_description = $_GET['new_room_group_description'];
	
	$table = "room_groups";
	$fields = array('name','description','active');
	$values = array($new_room_group_name,$new_room_group_description,"1");
	
	if($db->noDuplicate($table,$fields,$values))
	{
		$fields[] = 'date_added';
		$values[] = date('Y-m-d H:i:s');
		$room_group_id = $db->insert($table,$fields,$values);
	}
	else
	{
		print("$new_room_group_name already exists<br>\n");
	}
	
	// reload all room_groups
	$all_room_groups = load_room_groups();
}

?>


<script type="text/javascript" src="js/jquery.jeditable.js"></script>
<script type="text/javascript">
		jQuery(document).ready(function($) {
<?php
		foreach($all_room_groups as $room_group)
		{
			print("\$('#room_group".$room_group->id."_name').editable('".WEB_ROOT."/save.php', {\n"); 
			print("  indicator : \"<img src='images/loading_spinner.gif'>\",\n");
			print("  tooltip   : \"Click to edit...\",\n");
			print("  submit	: \"Save\",\n");
			print("  submitdata: {table:'room_groups', field:'name', row_id:'$room_group->id'},\n");
			print("  width  : '200px',\n");
			print("  style  : 'inherit'\n");
			print("});\n");
			
			print("\$('#room_group".$room_group->id."_description').editable('".WEB_ROOT."/save.php', {\n"); 
			print("  indicator : \"<img src='images/loading_spinner.gif'>\",\n");
			print("  tooltip   : \"Click to edit...\",\n");
			print("  submit	: \"Save\",\n");
			print("  submitdata: {table:'room_groups', field:'description', row_id:'$room_group->id'},\n");
			print("  width  : '400px',\n");
			print("  style  : 'inherit'\n");
			print("});\n");
			
			print("\$('#room_group".$room_group->id."_ordering').editable('".WEB_ROOT."/save.php', {\n"); 
			print("  indicator : \"<img src='images/loading_spinner.gif'>\",\n");
			print("  tooltip   : \"Click to edit...\",\n");
			print("  submit	: \"Save\",\n");
			print("  submitdata: {table:'room_groups', field:'ordering', row_id:'$room_group->id'},\n");
			print("  width  : '50px',\n");
			print("  style  : 'inherit'\n");
			print("});\n");
		}
?>			
		});
</script>

<?php

print("<form>\n");
print("<input type='hidden' name='submitted' value='1'>\n");
print("<input type='hidden' name='room_id' value='$room_id'>\n");

print("<h2>Room Groups</h2>\n");
print("<table width='100%' border><thead><tr><th>ID</th><th>Group</th><th>Description</th><th>Ordering</th></tr></thead>\n");
print("<tbody>\n");
foreach($all_room_groups as $room_group_id => $room_group)
{
	print("<tr valign='top'>");
	print("<td>$room_group_id</td>");
	print("<td><div class='editable editable_text' id='room_group".$room_group->id."_name'>$room_group->name</div></td>");
	print("<td><div class='editable editable_text' id='room_group".$room_group->id."_description'>$room_group->description</div></td>");
	print("<td><div class='editable editable_text' id='room_group".$room_group->id."_ordering'>$room_group->ordering</div></td>");
	print("</tr>\n");
}
print("</tbody></table><br>\n");

print("<hr>\n");

print("<h2>Add a New Room Group</h2>\n");
print("<table>");
print("<tr><td>Name:</td><td><input name='new_room_group_name' size='50'></td></tr>\n");
print("<tr><td>Description (optional):</td><td><input name='new_room_group_description' size='50'></td></tr>\n");
print("</table><br>\n");
print("<input type='submit' value='Add New Room Group'>\n");

print("</form>\n");


require_once("includes/footer.php");

?>