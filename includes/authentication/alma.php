<?php

function alma_authenticate($patron_id)
{
	$result = new stdClass();
	$result->error = false;
	
	$patron = alma_patron_record($patron_id);
	
	// print("patron:");
	// print_r($patron);
	
	if($patron->error)
	{
		// no user found for barcode entered
		$result->error = true;
		$result->error_messages = $patron->error_messages;
	}
	
	return($result);
}

function alma_patron_record($patron_id)
{
	$patron = new stdClass();
	
	// lookup patron information via alma api
	$soap_params = Array(
				'login'     => 'AlmaSDK-' . ALMA_API_USER . '-institutionCode-' . ALMA_API_INST,
				'password'  => ALMA_API_PASS,
				'trace'     => true,
				'exception' => true
			);
	$client = new SoapClient(ALMA_API_WSDL, $soap_params);

	try
	{
		// hack to alter patron id to work with odd patron load choice of all caps
		$patron_id = strtoupper($patron_id);
	
		$params = array('arg0' => $patron_id);
		$alma_user_xml = $client->getUserDetails($params);
		
		// TODO: user namespace instead of str_replace
		$alma_user_obj = @new SimpleXMLElement(str_replace("xb:","",$alma_user_xml->SearchResults));
		if(!strcmp(((string)$alma_user_obj->errorsExist),'true'))
		{
			// error retrieving patron info
			foreach($alma_user_obj->errorList as $error)
			{
				$patron->error = true;
				$patron->error_messages[] = ((string)$error->error->errorMessage);
			}
		}
		else
		{
			$user_record = $alma_user_obj->result->userRecord;
			$userDetails = $user_record->userDetails;
			$userAddressList = $user_record->userAddressList;
			$userIdentifiers = $user_record->userIdentifiers;
			
			// print_r($user_record);
			
			// get patron's university id
			foreach($userIdentifiers->userIdentifier as $uid)
			{
				if(isset($_REQUEST['debug'])) { print("uid: "); pr($uid); }
				// TODO: what happens if the user has multiple user ids
				// NOTE: should this also check against other types of ids other than external?
				if(!strcmp(((string)$uid->type),'EXTERNAL') || !strcmp(((string)$uid->type),'UNIV_ID'))
					$patron->univ_id = strtolower(((string)$uid->value));
			}			
			
			// load primary id as barcode
			$patron->barcode = ((string)$userDetails->userName);
			
			
			$patron->first_name = ((string)$userDetails->firstName);
			$patron->last_name = ((string)$userDetails->lastName);
			
			// TODO: check to see what happens if the user has multiple email addresses (select only preferred?)
			$patron->email = ((string)$userAddressList->userEmail->email);
			
			// hack to remove junk data in implementation load of patron data
			// $patron->email = str_replace("SCRUBBED_","",$patron->email);
			
			// get patron's expiration date
			// $patron->expiration_date = date("Y-m-d H:i:s",strtotime("+6 months"));	// hard-code expiration date to 6 months out (expiration date broken in alma)
			$patron->expiration_date = ((string)$userDetails->expiryDate);
			
			// get patron's type
			$patron->ptypes[] = ((string)$userDetails->userGroup);

			if(isset($_SESSION['phpCAS']['attributes']['EDU_PERSON_AFFILIATIONS']))
			{
				// if cas is used for the authentication method, go ahead and add patron types from that source as well
				foreach($_SESSION['phpCAS']['attributes']['EDU_PERSON_AFFILIATIONS'] as $type)
				{
					$patron->ptypes[] = $type;
				}
			}
			
			//pr($patron);
			//exit();
		}
	}
	catch (SoapFault $e)
	{
		// ALMA Authenication is DOWN
		
		// hack: send email alert
		
		// hack: use CAS as backup
		$patron = new stdClass();
		$patron->univ_id = strtolower($_SESSION['phpCAS']['attributes']['UID']);
		$patron->barcode = $_SESSION['phpCAS']['attributes']['UID'];
		$patron->first_name = $_SESSION['phpCAS']['attributes']['GIVEN_NAME'];
		$patron->last_name = $_SESSION['phpCAS']['attributes']['SN'];
		$patron->email = $_SESSION['phpCAS']['attributes']['MAIL'];
		$patron->expiration_date = date("Y-m-d H:i:s",strtotime("+6 months"));	// hard-code expiration date to 6 months out (expiration date not available from CAS)
		$patron->ptypes = array();
		foreach($_SESSION['phpCAS']['attributes']['EDU_PERSON_AFFILIATIONS'] as $type)
		{
			$patron->ptypes[] = $type;
		}
	}
	
	return($patron);
}

?>