<?php

function get_patron_api_record($barcode)
{
	// check patronapi for millennium account info
	$apiurl = "http://vikat.pdx.edu:4500/PATRONAPI/$barcode/dump";
	
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
		// error: no unique id set for this user
	}
	
	//print("<pre>\n");
	//print("record\n");
	//print_r($record);
	//print("</pre>\n");
	return($record);
}

?>