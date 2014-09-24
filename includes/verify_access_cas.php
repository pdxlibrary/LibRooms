<?php

	session_start();
	require_once("includes/CAS.php");
	
	if (isset($_GET['logout']))
	{
		unset($_SESSION['session_username']);
		phpCAS::client(CAS_VERSION_2_0,'sso.pdx.edu',443,'/cas',false);
		phpCAS::logout();
	}
	
	if(isset($_SESSION['session_username']))
	{
		// user is logged in
	}
	else if(isset($_GET['login']))
	{
		// authenticate with PSU Central Authentication Server (CAS)
	
		// initialize phpCAS
		phpCAS::client(CAS_VERSION_2_0,'sso.pdx.edu',443,'/cas',false);

		// no SSL validation for the CAS server
		phpCAS::setNoCasServerValidation();

		// force CAS authentication
		phpCAS::forceAuthentication();

		// at this step, the user has been authenticated by the CAS server
		// and the user's login name can be read with phpCAS::getUser().

		$uid = phpCAS::getUser();
		

		//print("Password Approved");
		$_SESSION['session_username'] = $uid;

	}
	else
	{
		if(strcmp($_SERVER['QUERY_STRING'],''))
			print("<table height='100%' width='100%'><tr valign='center'><td align='center'><h3>Portland State Library<br>Study Room Reservation Admin</h3><input type='button' value='LOGON NOW!' onClick='document.location.href=\"?".$_SERVER['QUERY_STRING']."&login\"'></td></tr></table>\n");
		else
			print("<table height='100%' width='100%'><tr valign='center'><td align='center'><h3>Portland State Library<br>Study Room Reservation Admin</h3><input type='button' value='LOGON NOW!' onClick='document.location.href=\"?login\"'></td></tr></table>\n");
			
		exit();	
	}
	
?>
