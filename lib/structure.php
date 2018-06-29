<?php

function return_structure_categories(&$db)
{
	global $config;
	
	$sql = "SELECT `active`.`data` AS `data` FROM
		(SELECT `data` FROM `" . $config['dbprefix'] . "config` WHERE (`type` LIKE 'request' OR `type` LIKE 'list' OR `type` LIKE 'commons' OR `type` LIKE 'external') AND `key` LIKE 'display' AND (`online` = 1 OR `online` = 2)) AS `active`
		LEFT JOIN
		(SELECT `wiki`, `data` FROM `" . $config['dbprefix'] . "source_config` WHERE `key` LIKE 'priority') AS `priority`
		ON `active`.`data` = `priority`.`wiki`
		ORDER BY `priority`.`data` DESC";
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	$categories_data = array();
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$categories_data[$row['data']]['name'] = $row['data'];
	}
	
	$res->free();
	
	foreach($categories_data as $key => $categorie)
	{
		// logo
		$sql = "SELECT `data` FROM  `" . $config['dbprefix'] . "source_config` WHERE  `key` LIKE  'logo' AND `wiki` LIKE '".$key."'";
		$res = $db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$categories_data[$key]['logo'] = $row['data'];
		$res->free();
		
		// title
		$sql = "SELECT `data` FROM  `" . $config['dbprefix'] . "source_config` WHERE  `key` LIKE  'title' AND `wiki` LIKE '".$key."'";
		$res = $db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$categories_data[$key]['title'] = $row['data'];
		$res->free();
		
		// color
		$sql = "SELECT `data` FROM  `" . $config['dbprefix'] . "source_config` WHERE  `key` LIKE  'color' AND `wiki` LIKE '".$key."'";
		$res = $db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$categories_data[$key]['color'] = $row['data'];
		$res->free();
		
		// priority
		$sql = "SELECT `data` FROM  `" . $config['dbprefix'] . "source_config` WHERE  `key` LIKE  'priority' AND `wiki` LIKE '".$key."'";
		$res = $db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$categories_data[$key]['priority'] = $row['data'];
		$res->free();
		
		// icon
		$sql = "SELECT `data` FROM  `" . $config['dbprefix'] . "source_config` WHERE  `key` LIKE  'icon' AND `wiki` LIKE '".$key."'";
		$res = $db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$categories_data[$key]['icon'] = $row['data'];
		$res->free();
		
		// marker
		$sql = "SELECT `data` FROM  `" . $config['dbprefix'] . "source_config` WHERE  `key` LIKE  'marker' AND `wiki` LIKE '".$key."'";
		$res = $db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$categories_data[$key]['marker'] = $row['data'];
		$res->free();
		
		
		// editLinkText
		$sql = "SELECT `data` FROM  `" . $config['dbprefix'] . "source_config` WHERE  `key` LIKE  'linktext' AND `wiki` LIKE '".$key."'";
		$res = $db->query($sql);
		
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$categories_data[$key]['editLinkText'] = $row['data'];
		$res->free();
	}
	
	$categories = array();
	foreach($categories_data as $key => $categorie)
	{
		$categories[] = $categorie;
	}
	
	return json_encode($categories);
}

?>