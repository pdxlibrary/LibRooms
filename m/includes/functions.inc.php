<?php

session_start();

function api($function,$params=array(),$return_type=false)
{
	$param_strings = array();
	if(is_array($params))
	{
		foreach($params as $var => $val)
		{
			if(is_array($val))
			{
				$param_strings[] = $var."=".urlencode(json_encode($val));
			}
			else
				$param_strings[] = $var."=".urlencode(urldecode($val));
		}
	}
	$query_string = implode("&",$param_strings);
	$api_query = STUDYROOMS_API.$function.".php?".$query_string;
	// print("<hr>");
	// print("api query: $api_query<br>\n");
	$result = json_decode(file_get_contents($api_query),$return_type);
	// print_r($result);
	// print_r($_SESSION);
	// print("<hr>\n");
	return($result);
}

?>