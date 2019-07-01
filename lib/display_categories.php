<?php

function categorie_exists(&$db, $categorie)
{
	global $config;
	
	if($categorie == "commons")
	{
		return true;
	}
	
	if($categorie == "request")
	{
		return true;
	}
	
	$categorie = $db->real_escape_string($categorie);
	$sql = "SELECT * FROM `" . $config['dbprefix'] . "config` WHERE (`online`='1' OR `online`='2') AND `key` LIKE 'display' AND `data` LIKE '".$categorie."' AND  `type` LIKE 'list'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	$erg = $res->num_rows;
	$res->free();
	if($erg == 1)
	{
		return true;
	}
	
	return false;
}

function get_display_categories(&$db, $display_categories)
{
	global $config;
	$categories = array();
	
	foreach($display_categories as $categorie)
	{
		if(categorie_exists($db, $categorie))
		{
			$categorie = $db->real_escape_string($categorie);
			$sql = "SELECT `data` FROM `" . $config['dbprefix'] . "source_config` WHERE `key` LIKE 'display' AND `wiki` LIKE '".$categorie."'";
			$res = $db->query($sql);
			if($config['log'] > 2)
			{
				append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
			}
			
			// TODO remove
			if($categorie != "request"){
				//end TODO
			while($row = $res->fetch_array(MYSQLI_ASSOC))
			{
				$cat = $row['data'];
				$categories[$cat] = $categorie;
			}
			// TODO remove
			}
			else
			{
				$categories['wikidata'] = "request";
				$categories['bilderwunsch'] = "request";
			}
			//end TODO
	
			$res->free();
		}
		else
		{
			$categories[$categorie] = $categorie;
		}
	}
	return $categories;
}

function get_all_categories(&$db)
{
	global $config;
	
	$sql = "SELECT `data` FROM `" . $config['dbprefix'] . "config` WHERE (`type` LIKE 'request' OR `type` LIKE 'list' OR `type` LIKE 'external') AND `key` LIKE 'display' AND (`online` = 1 OR `online` = 2)"; // OR `type` LIKE 'commons'
	$res = $db->query($sql);
	
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	$categories_data = array();
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$categories_data[] = $row['data'];
	}
	
	$res->free();
	
	return get_display_categories($db, $categories_data);
}

?>