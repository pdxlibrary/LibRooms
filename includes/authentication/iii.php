<?php

function iii_authenticate($patron_id)
{
	$result = new stdClass();
	$result->error = false;
	
	$patron = get_patron_api_record($patron_id);
	
	if(!strcmp($patron["UNIQUE ID"],''))
	{
		// no user found for barcode entered
		$result->error = true;
		$result->error_messages[] = "Patron [$patron_id] could not be found in the III system.";
	}
	
	return($result);
}


function iii_patron_record($patron_id)
{
	$patron = new stdClass();	
	
	// use the patron id to lookup their info from III
	$record = get_patron_api_record($patron_id);

	if(!strcmp($record["UNIQUE ID"],''))
	{
		// no user found for barcode entered
		$patron->error = true;
		$patron->error_messages[] = "Patron [$patron_id] could not be found in the III system.";
	}
	else
	{
		$patron->univ_id = $record["UNIQUE ID"];
		$patron->barcode = $record["UNIQUE ID"];
		
		$full_name = $record['PATRN NAME'];
		$name_parts = explode(",",$full_name);
		$patron->last_name = $name_parts[0];
		$patron->first_name = trim($name_parts[1]);
		
		$expiration_date_parts = explode("-",$record['EXP DATE']);
		// hack to fix III's two-digit year date (Y2K issue)
		$expiration_date_parts[2] = "20".$expiration_date_parts[2];
		$patron->expiration_date = date('Y-m-d',strtotime(implode("/",$expiration_date_parts)));
		
		$patron->email = $record["EMAIL ADDR"];
		
		$patron->ptypes[] = $record["P TYPE"];
	}
	
	return($patron);
}


function get_patron_api_record($barcode)
{
	// check patronapi for millennium account info
	$apiurl = III_PATRON_API."$barcode/dump";
	
	// uncomment the line below to use the failover PatronAPI
	//$apiurl = "http://digital.lib.pdx.edu/PATRONAPI/$barcode/dump";
	
	$original = "";
	$handle = @fopen($apiurl, "r");
	if ($handle) {
	    while (!feof($handle)) {
	        $line = fgets($handle, 4096);
			//print("line: [$line]<br>\n");
			$open_pos = strpos($line,'[');
			$close_pos = strpos($line,']=');
			$var = substr($line,0,$open_pos);
			$val = trim(substr($line,$close_pos+2,-5));
			$val = str_replace("<","",$val);
			$original .= $line;
	        $record[$var]=$val;
	    }
	    fclose($handle);
		//print_r($record);
	}
	
	if(!isset($record['UNIQUE ID']))
	{
		return(false);
	}
	
	//print("<pre>\n");
	//print("record\n");
	//print_r($record);
	//print("</pre>\n");
	return($record);
}


?>