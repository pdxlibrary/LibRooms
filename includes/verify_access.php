<?php

if(isset($_GET['logoff']))
{
	unset($_SESSION['LibRooms']);
	
	switch(AUTHENTICATION_METHOD)
	{
		case "CAS":
			// import phpCAS lib
			require_once('authentication/phpCAS/CAS.php');
			
			// initialize phpCAS
			phpCAS::client(SAML_VERSION_1_1,CAS_SERVER,CAS_PORT,CAS_PATH,false);

			// no SSL validation for the CAS server
			phpCAS::setNoCasServerValidation();
			
			// logout of CAS
			phpCAS::logout();
			
			break;
	}
}


function restrict_access($db,$allowed_roles)
{
	if(isset($_GET['debug']))
	{
		pr($_SESSION);
	}
	
	switch(AUTHENTICATION_METHOD)
	{
		case "CAS":
			if(!isset($_SESSION['phpCAS']['user']) || !isset($_SESSION['LibRooms']['UserID']))
			{
				// import phpCAS lib
				require_once('authentication/phpCAS/CAS.php');

				if(CAS_DEBUG)
					phpCAS::setDebug();

				// initialize phpCAS
				phpCAS::client(SAML_VERSION_1_1,CAS_SERVER,CAS_PORT,CAS_PATH,false);

				// SSL validation for the CAS server
				if(CAS_SSL_VALIDATE)
					phpCAS::setCasServerCert();
				else
					phpCAS::setNoCasServerValidation();

				// check to see if the user is alread authenticated
				if(!phpCAS::checkAuthentication())
				{
					if(!in_array("public",$allowed_roles))
					{
						// force CAS authentication
						phpCAS::forceAuthentication();
					}
					else
					{
						// public user who is not already signed in
						if(!in_array("public",$_SESSION['LibRooms']['Roles']));
							$_SESSION['LibRooms']['Roles']["public"] = "public";
					}
				}
				else
				{
					$patron = load_patron_record(phpCAS::getUser());
				}
			}
			break;
		case "ALMA":
			if(!isset($_SESSION['LibRooms']['UserID']))
			{
				require_once("authentication/alma.php");
				
				if(isset($_POST['name']) && isset($_POST['barcode']))
				{
					if(alma_authenticate($_POST['barcode']))
					{
						load_patron_record($_POST['barcode']);
						unset($_GET['login']);
						$location = "login_redirect.php?redirect=".urlencode($_SERVER['PHP_SELF']."?".http_build_query($_GET));
						//print("loc: $location<br>\n");
						header("location: $location");
						exit();
					}
					else
					{
						// require_once("login.php");
						// exit();
					}
				}	
				
				if(!in_array("public",$allowed_roles))
				{
					// force authentication
					require_once("login.php");
					exit();
				}
				else
				{
					// public user who is not already signed in
					if(!in_array("public",$_SESSION['LibRooms']['Roles']));
						$_SESSION['LibRooms']['Roles']["public"] = "public";
				}
			}
			break;
		case "III":
			if(!isset($_SESSION['LibRooms']['UserID']))
			{
				require_once("authentication/iii.php");
				
				if(isset($_POST['name']) && isset($_POST['barcode']))
				{
					if(iii_authenticate($_POST['barcode']))
					{
						load_patron_record($_POST['barcode']);
						unset($_GET['login']);
						$location = "login_redirect.php?redirect=".urlencode($_SERVER['PHP_SELF']."?".http_build_query($_GET));
						//print("loc: $location<br>\n");
						header("location: $location");
						exit();
					}
					else
					{
						require_once("login.php");
						exit();
					}
				}	
				
				if(!in_array("public",$allowed_roles))
				{
					// force authentication
					require_once("login.php");
					exit();
				}
				else
				{
					// public user who is not already signed in
					if(!in_array("public",$_SESSION['LibRooms']['Roles']));
						$_SESSION['LibRooms']['Roles']["public"] = "public";
				}
			}
			break;
	}
	
	$authorized = false;
	// pr($_SESSION['LibRooms']['Roles']);
	// pr($allowed_roles);
	if(isset($_SESSION['LibRooms']['Roles']))
	{
		// pr($_SESSION['LibRooms']['Roles']);
		foreach($_SESSION['LibRooms']['Roles'] as $role)
		{
			$role = strtolower($role);
			// pr($allowed_roles);
			// pr($role);
			if(in_array($role,$allowed_roles))
			{
				$authorized = true;
				break;
			}
		}
	}
	
	if(!$authorized && !in_array("public",$allowed_roles))
	{
		require_once("includes/header.php");
		print("<h1>You are not authorized to view this page.</h1>\n");
		require_once("includes/footer.php");
		
		/*
		// send error email
		$from = NOTICE_EMAIL_ADDRESS;
		$reply_to = NOTICE_EMAIL_ADDRESS;
		$recipient = "mflakus@pdx.edu";
		$subject = "You are not authorized to view this page";
		$body = "session: \n".json_encode($_SESSION)."<br><br>\n\nAllowed Roles:\n".json_encode($allowed_roles)."<br><br>\n\nPatron:\n".json_encode($patron)."<br><br>\n\nServer:\n".json_encode($_SERVER);
		$result = send_email(0,"Error",$from,$reply_to,$recipient,$subject,$body);
		*/
		
		exit();
	}
	
}

function synch_user($patron_id)
{
	global $db;
	
	$patron = new stdClass();
	switch(PATRON_RECORD_SOURCE)
	{
		case 'ALMA': 
			require_once("authentication/alma.php");
			$patron = alma_patron_record($patron_id);
			break;
		case 'CAS':
			require_once("authentication/cas.php");
			$patron = cas_patron_record($patron_id);
			break;
		case 'III':
			require_once("authentication/iii.php");
			$patron = iii_patron_record($patron_id);
			break;
	}
	
	if($patron->error)
	{
		// send error email
		$from = NOTICE_EMAIL_ADDRESS;
		$reply_to = NOTICE_EMAIL_ADDRESS;
		$recipient = "libsys@lists.pdx.edu";
		$recipient = "mflakus@pdx.edu";
		$subject = "Study Rooms Authentication Error for Patron ID: $patron_id";
		$body = PATRON_RECORD_SOURCE." ERROR: " . implode(", ",$patron->error_messages) ."<br><br>\nCAS ID: ".$_SESSION['phpCAS']['user']."<br><br>\nsession: <br><br>\n".json_encode($_SESSION)."<br><br>\nValues:\n".json_encode($values)."<br><br>\nPatron:\n".json_encode($patron)."<br>\nServer:\n".json_encode($_SERVER);
		$result = send_email(0,"Error",$from,$reply_to,$recipient,$subject,$body);
	}
	else
	{
		// add user to database if not already present
		$select_user = "select * from users where patron_id like '".$patron->univ_id."' AND active like '1'";
		$res_user = $db->query($select_user);
		
		$fields = array("first_name","last_name","patron_id","barcode","email","ptype","expiration_date");
		$values = array($patron->first_name,$patron->last_name,$patron->univ_id,$patron->barcode,$patron->email,json_encode($patron->ptypes),$patron->expiration_date);
		
		if($res_user->numRows() == 1)
		{
			// found user in database

			// update user info in database
			$res_user->fetchInto($user);
			$db->update("users",$fields,$values,"id like '$user->id'",$user->id,"Synched User Account");
		}
		else
		{
			// try to find user by barcode
			$select_user = "select * from users where barcode like '".$patron->barcode."' AND active like '1'";
			$res_user = $db->query($select_user);
			if($res_user->numRows() >= 1)
			{
				// found user in database

				// update user info in database
				$res_user->fetchInto($user);
				
				if(!strcmp($patron->univ_id,''))
				{
					// hack: use barcode for patron_id, if no patron_id exists
					$values[2] = $values[3];
					$patron->univ_id = $patron->barcode;
				}
				
				$db->update("users",$fields,$values,"id like '$user->id'",$user->id,"Synched User Account");
			}
			else
			{
				// add user to database
				$fields[] = "date_added";
				$values[] = date("Y-m-d H:i:s",strtotime('now'));
				$new_user_id = $db->insert("users",$fields,$values);
				$res_user = $db->query($select_user);
				$res_user->fetchInto($user);
			}
		}
	}
	
	return($patron);
}

function load_patron_record($patron_id)
{
	global $db;
	
	// load patron's record (already authenticated)
	
	$patron = synch_user($patron_id);
	
	
	// Account Error Checks
	
	// allowed patron types
	$allowed_ptypes = explode(",",ALLOWED_PTYPES);
	for($i=0;$i<count($allowed_ptypes);$i++)
		$allowed_ptypes[$i] = trim($allowed_ptypes[$i]);
		
	$select_admin_roles = "select role from roles,users_roles,users where (role like 'Admin' OR role like 'Staff') AND users.patron_id like '$patron->univ_id' AND users.id = users_roles.user_id AND roles.id = users_roles.role_id AND roles.active like '1' AND users_roles.active like '1' AND users.active like '1'";
	$res_admin_roles = $db->query($select_admin_roles);

	if(count($patron->error_messages) > 0)
	{
		// errors propagated from authentication layer
	}
	else if(strtotime($patron->expiration_date) < strtotime(date('Y-m-d')))
	{
		// patron is expired
		$patron->error = true;
		$patron->error_messages[] = "Expired patron account.";
	}
	else if(count($allowed_ptypes) > 0 && count(array_intersect($patron->ptypes, $allowed_ptypes))==0 && $res_admin_roles->numRows() == 0)
	{
		// non-valid patron type
		$patron->error = true;
		$patron->error_messages[] = "Invalid patron account type. (".implode(", ",$patron->ptypes).")";
	}
	else
	{
		// successful logon

		// add user to database if not already present
		$select_user = "select * from users where patron_id like '".$patron->univ_id."' AND active like '1'";
		$res_user = $db->query($select_user);
		
		$fields = array("first_name","last_name","patron_id","barcode","email","ptype","expiration_date");
		$values = array($patron->first_name,$patron->last_name,$patron->univ_id,$patron->barcode,$patron->email,json_encode($patron->ptypes),$patron->expiration_date);
		
		if($res_user->numRows() == 1)
		{
			// found user in database

			// update user info in database
			$res_user->fetchInto($user);
			$db->update("users",$fields,$values,"id like '$user->id'",$user->id,"Synched User Account");
		}
		else
		{
			// try to find user by barcode
			$select_user = "select * from users where barcode like '".$patron->barcode."' AND active like '1'";
			$res_user = $db->query($select_user);
			if($res_user->numRows() == 1)
			{
				// found user in database

				// update user info in database
				$res_user->fetchInto($user);
				$db->update("users",$fields,$values,"id like '$user->id'",$user->id,"Synched User Account");
				
				// reload patron record after updating patron record (needed to fix univ_id when not current)
				load_patron_record(phpCAS::getUser());
			}
			else
			{
				// add user to database
				$fields[] = "date_added";
				$values[] = date("Y-m-d H:i:s",strtotime('now'));
				$new_user_id = $db->insert("users",$fields,$values);
				$res_user = $db->query($select_user);
				$res_user->fetchInto($user);
			}
		}
		
		// load user info into session
		$_SESSION['LibRooms']['UserID'] = $user->id;
		$_SESSION['LibRooms']['PatronID'] = $user->patron_id;
		$_SESSION['LibRooms']['Barcode'] = $user->barcode;
		$_SESSION['LibRooms']['FirstName'] = $user->first_name;
		$_SESSION['LibRooms']['LastName'] = $user->last_name;
		$_SESSION['LibRooms']['Email'] = $user->email;
		
		// add roles to session
		$select_user_roles = "select role from roles,users_roles,users where users.patron_id like '".$_SESSION['LibRooms']['PatronID']."' AND users.id = users_roles.user_id AND roles.id = users_roles.role_id AND roles.active like '1' AND users_roles.active like '1' AND users.active like '1'";
		$res_user_roles = $db->query($select_user_roles);
		$roles = array();
		// TODO: HACK: patron role should be more gracefully added rather than hard-coded
		$roles[] = "Patron";
		while($res_user_roles->fetchInto($roles_obj))
		{
			$roles[] = $roles_obj->role;
		}
		
		// add admin roles
		while($res_admin_roles->fetchInto($roles_obj))
		{
			if(!in_array($roles_obj->role,$roles))
				$roles[] = $roles_obj->role;
		}
		
		$_SESSION['LibRooms']['Roles'] = $roles;
		
		
		// redirect to the page the user was trying to get to
		unset($_GET['login']);
		//print("location: ".$_SERVER['PHP_SELF']."?".http_build_query($_GET));
		$location = "login_redirect.php?redirect=".urlencode($_SERVER['PHP_SELF']."?".http_build_query($_GET));
		header("location: $location");
		exit();
	}
	
	//pr($patron);
	return($patron);
}

?>
