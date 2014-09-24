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


print("<div id='PageTitle'>Manage Amenities</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Manage Amenities</div>\n");
print("<br>\n");

$all_amenities = load_amenities();

?>


<script type="text/javascript" src="js/jquery.jeditable.js"></script>
<script type="text/javascript">
		jQuery(document).ready(function($) {
<?php
		foreach($all_amenities as $amenity)
		{
			print("\$('#amenity".$amenity->id."_name').editable('".WEB_ROOT."/save.php', {\n"); 
			print("  indicator : \"<img src='images/loading_spinner.gif'>\",\n");
			print("  tooltip   : \"Click to edit...\",\n");
			print("  submit	: \"Save\",\n");
			print("  submitdata: {table:'amenities', field:'name', row_id:'$amenity->id'},\n");
			print("  width  : '200px',\n");
			print("  style  : 'inherit'\n");
			print("});\n");
			
			print("\$('#amenity".$amenity->id."_description').editable('".WEB_ROOT."/save.php', {\n"); 
			print("  indicator : \"<img src='images/loading_spinner.gif'>\",\n");
			print("  tooltip   : \"Click to edit...\",\n");
			print("  submit	: \"Save\",\n");
			print("  submitdata: {table:'amenities', field:'description', row_id:'$amenity->id'},\n");
			print("  width  : '300px',\n");
			print("  style  : 'inherit'\n");
			print("});\n");
			
			print("\$('#amenity".$amenity->id."_search_filter').editable('".WEB_ROOT."/save.php', {\n");
			print("  data   	: '".json_encode(array("No"=>"No","Yes"=>"Yes"))."',\n");
			print("  type   	: 'select',\n");
			print("  submitdata: {table:'amenities', field:'search_filter', row_id:'$amenity->id'},\n");
			print("  indicator : \"<img src='images/loading_spinner.gif'>\",\n");
			print("  tooltip   : 'Click to edit...',\n");
			print("  submit	: 'Save',\n");
			print("  style  	: 'inherit'\n");
			print("});\n");
		}
?>			
		});
</script>

<?php


if(isset($_GET['new_amenity_name']) && strcmp(trim($_GET['new_amenity_name']),''))
{
	$new_amenity_name = $_GET['new_amenity_name'];
	$new_amenity_description = $_GET['new_amenity_description'];
	$new_amenity_search_filter = $_GET['new_amenity_search_filter'];
	
	$table = "amenities";
	$fields = array('name','description','search_filter','active');
	$values = array($new_amenity_name,$new_amenity_description,$new_amenity_search_filter,"1");
	
	if($db->noDuplicate($table,$fields,$values))
	{
		$fields[] = 'date_added';
		$values[] = date('Y-m-d H:i:s');
		$amenity_id = $db->insert($table,$fields,$values);
	}
	else
	{
		print("$new_amenity_name already exists<br>\n");
	}
	
	// reload all amenities
	$all_amenities = load_amenities();
}

print("<form>\n");
print("<input type='hidden' name='submitted' value='1'>\n");
print("<input type='hidden' name='room_id' value='$room_id'>\n");

print("<h2>Amenities</h2>\n");
print("<table width='100%' border><thead><tr><th>Amentity</th><th>Description</th><th>Search Filter</th></tr></thead>\n");
print("<tbody>\n");
foreach($all_amenities as $amenity_id => $amenity)
{
	print("<tr valign='top'>");
	print("<td><div class='editable editable_text' id='amenity".$amenity->id."_name'>$amenity->name</div></td>");
	print("<td><div class='editable editable_text' id='amenity".$amenity->id."_description'>$amenity->description</div></td>");
	print("<td><div class='editable editable_select' id='amenity".$amenity->id."_search_filter'>$amenity->search_filter</div></td></tr>\n");
}
print("</tbody></table><br>\n");

print("<hr>\n");

print("<h2>Add a New Amenity</h2>\n");
print("<table>");
print("<tr><td>Name:</td><td><input name='new_amenity_name' size='50'></td></tr>\n");
print("<tr><td>Description (optional):</td><td><input name='new_amenity_description' size='50'></td></tr>\n");
print("<tr><td>Display on Search Filter Form:</td><td><select name='new_amenity_search_filter'><option value='0'>No</option><option value='1'>Yes</option></select></td></tr>\n");
print("</table><br>\n");
print("<input type='submit' value='Add New Amenity'>\n");

print("</form>\n");


require_once("includes/footer.php");

?>