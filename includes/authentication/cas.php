<?php

function cas_patron_record()
{
	$patron = new stdClass();
	
	$patron->univ_id = $_SESSION['phpCAS']['attributes']['UID'];
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
	
	return($patron);
}

?>