<?php

session_start();
require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("load.php");
require_once("includes/load_settings.php");

require_once("includes/verify_access.php");
restrict_access($db,array("admin"));



require_once("includes/header.php");

$settings = load_settings();

?>

<script type="text/javascript" src="js/jquery.jeditable.js"></script>
<script type="text/javascript">
		jQuery(document).ready(function($) {
<?php
		foreach($settings as $setting)
		{
			print("\$('#setting$setting->id').editable('".WEB_ROOT."/save.php', {\n"); 
			print("  indicator : \"<img src='images/loading_spinner.gif'>\",\n");
			print("  tooltip   : \"Click to edit...\",\n");
			print("  submit	: \"Save\",\n");
			print("  submitdata: {table:'settings', field:'value', row_id:'$setting->id'},\n");
			print("  width  : '100px',\n");
			print("  style  : 'text-align:right'\n");
			print("});\n");
		}
?>			
		});
</script>


<?php


print("<div id='PageTitle'>Manage Settings</div>\n");
print("<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Manage Settings</div>\n");
print("<br>\n");


print("<form>\n");

print("<h2>Settings</h2>\n");


print("<table border><tr><th>Setting</th><th width='180'>Value</th></tr>\n");
foreach($settings as $setting)
{
	print("<tr><td><b>$setting->name</b><br><i>$setting->description</i></td><td><div id='setting$setting->id' class='editable editable_text' style='text-align:right'>$setting->value</div></td></tr>\n");
}
print("</table>\n");

// TODO: possibly add email templates

require_once("includes/footer.php");

?>