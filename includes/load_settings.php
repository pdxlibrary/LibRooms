<?php

function load_settings($options=array())
{
	global $db;
	
	$where = "WHERE 1";
	
	if(isset($options['sort asc']))
		$sort = "ORDER BY ".$options['sort asc'];
	else if(isset($options['sort desc']))
		$sort = "ORDER BY ".$options['sort desc'];
	else
		$sort = "ORDER BY ordering";
	
	
	// load settings
	$select = "SELECT * FROM settings $where AND active LIKE '1' $sort";
	//print("select: $select<br>\n");
	$res = $db->query($select);
	$all_settings = array();
	while($res->fetchInto($setting))
	{
		$all_settings[$setting->id] = $setting;
	}
	return($all_settings);
}


$settings = load_settings();
foreach($settings as $setting)
{
	switch($setting->name)
	{
		case 'MAX_RESERVATIONS_PER_WEEK':
		case 'MAX_RESERVATIONS_PER_DAY':
		case 'MAX_RESERVATION_HOURS_PER_WEEK':
		case 'MAX_RESERVATION_HOURS_PER_DAY':
			if($setting->value == 0)
				define($setting->name,99999999);
			else
				define($setting->name,$setting->value);
			break;
		default:
			define($setting->name,$setting->value);
	}
	
}


?>