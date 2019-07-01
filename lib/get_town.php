<?php

function get_town(&$db, $lon, $lat)
{
	global $config;
	
	$lat = $db->real_escape_string($lat);
	$lon = $db->real_escape_string($lon);

	$sql = "SELECT gemeinde, ( 6371 * acos( cos( radians(".$lat.") ) * cos( radians( `latitude` ) ) * cos( radians( `longitude` ) - radians(".$lon.") ) + sin( radians(".$lat.") ) * sin( radians( `latitude` ) ) ) ) AS entfernung FROM " . $config['dbprefix'] . "gemeinde_geo ORDER BY entfernung LIMIT 0 , 1";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$gemeinde = $row['gemeinde'];
	
	return $gemeinde;
}

function get_town_wikidata(&$db, $wikidata)
{
	global $config;
	
	$gemeinde = $wikidata;
	$wikidata = $db->real_escape_string($wikidata);
	$sql = "SELECT `gemeinde` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE `wikidata` LIKE '$wikidata'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}

	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$gemeinde = $row['gemeinde'];
	}
	return $gemeinde;
}

function get_town_kennzahl(&$db, $kennzahl)
{
	global $config;
	
	$gemeinde = $kennzahl;
	$kennzahl = $db->real_escape_string($kennzahl);
	$sql = "SELECT `gemeinde` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE `gemeindekennzahl` LIKE '$kennzahl'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}

	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$gemeinde = $row['gemeinde'];
	}
	return $gemeinde;
}

?>