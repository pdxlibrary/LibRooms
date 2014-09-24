<?php
	require_once("includes/header.php");
?>

<style>
.error {color:red; font-weight:bold;}
</style>


<div id="PSUContent">
<div id='PageTitle'>Study Rooms Reservation Tool</div>
<div class='breadcrumb'><a href='http://library.pdx.edu'>Home</a> &raquo; <a href='index.php'>Reserve a Study Room</a> &raquo; Login</div>
<br>
<h2>Login:</h2>
<?php
	$form_url = $_SERVER['PHP_SELF'];
	if(strcmp($_SERVER['QUERY_STRING'],''))
		$form_url .= "?" . str_replace("logoff","",$_SERVER['QUERY_STRING']);
	print("<form action='$form_url' method='POST'>\n");

	if(count($error_messages) > 0)
	{
		display_errors($error_messages,$_POST);
	}
	
?>

<table border="0">
<tr><td>Last Name:</td><td><input type="text" name="name"></td></tr>
<tr><td>PSU ID# or Barcode:</td><td><input type="password" name="barcode"></td></tr>
<tr><td colspan="2"><input type="Submit" value="Login"></td></tr>
</table>
</form>
</div>


<?php
	require_once("includes/footer.php");
?>
