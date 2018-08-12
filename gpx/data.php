<?php

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

function get_gpx_list_categorie(&$db,$town,$categorie,$display_categorie)
{
	global $config;
	
	// town for categorie
	$sql = "SELECT `gemeinde_".$categorie."` AS `town` FROM `" . $config['dbprefix'] . "search` WHERE `article_wikipedia` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$town = $row['town'];
	$res->free();
	
	if ($town == "")
	{
		return;
	}
	
	// data
	$sql = "SELECT * FROM `" . $config['dbprefix'] . $categorie . "_list_data` WHERE (`online`='1' OR `online`='2') AND `gemeinde` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		if(($row['latitude']!="")&&($row['longitude']!=""))
		{
			echo ' <wpt lat="'.$row['latitude'].'" lon="'.$row['longitude'].'">'."\n";
			echo "  <ele>0</ele>\n";
			echo '  <name><![CDATA['.$row['name'].']]></name>'."\n";
			echo '  <desc><![CDATA['.html_entity_decode(strip_tags($row['beschreibung'])).']]></desc>'."\n";
/*			if($categorie=="denkmalliste")
			{
				echo '<link><![CDATA['.str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#objektid-".urlencode(str_replace("/","_",$row['objektid']))).']]></link>';
			}
			else if($categorie=="publicart")
			{
				echo '<link><![CDATA['.str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#id-".urlencode(str_replace("/","_",$row['id']))).']]></link>';
			}
			else if($categorie=="kellergasse")
			{
				echo '<link><![CDATA['.str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace(array("/"," "),"_",$row['name']))).']]></link>';
			}
			else if ($categorie=="naturdenkmal")
			{
				echo '<link><![CDATA['.str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id']))).']]></link>';
			}
			else if ($categorie=="hoehle")
			{
				echo '<link><![CDATA['.str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id']))).']]></link>';
			}
			else if ($categorie=="landschaftsteil")
			{
				echo '<link><![CDATA['.str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id']))).']]></link>';
			}
			else if ($categorie=="naturpark")
			{
				echo '<link><![CDATA['.str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id']))).']]></link>';
			}
			else if ($categorie=="naturschutzgebiet")
			{
				echo '<link><![CDATA['.str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id']))).']]></link>';
			}
			echo "\n";*/
			echo ' </wpt>'."\n";
		}
	}
	
	$res->free();
}

function get_gpx_request_categorie(&$db,$town,$categorie,$display_categorie)
{
	global $config;
	
	// data
	$sql = "SELECT * FROM `" . $config['dbprefix'] . $categorie . "_external_data` WHERE (`online`='1' OR `online`='2') AND `gemeinde` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		echo ' <wpt lat="'.$row['latitude'].'" lon="'.$row['longitude'].'">'."\n";
		echo "  <ele>0</ele>\n";
		echo '  <name><![CDATA['.$row['name'].']]></name>'."\n";
		echo '  <desc><![CDATA['.html_entity_decode(strip_tags($row['description'])).']]></desc>'."\n";
//		echo '<link><![CDATA['.$row['article'].']]></link>'."\n";
		echo ' </wpt>'."\n";
	}
	
	$res->free();
}

function get_gpx_categorie(&$db, $town, $categorie, $display_categorie)
{
	global $config;
	$categorie = $db->real_escape_string($categorie);
	$display_categorie = $db->real_escape_string($display_categorie);
	$town = $db->real_escape_string($town);
	
	$sql = "SELECT `type` FROM `" . $config['dbprefix'] . "config` WHERE `key` LIKE 'source' AND `data` LIKE '".$categorie."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$type = $row['type'];
	$res->free();
	
	if($type == "list")
	{
		return get_gpx_list_categorie($db,$town,$categorie,$display_categorie);
	}
	else if($type == "request")
	{
		return get_gpx_request_categorie($db,$town,$categorie,$display_categorie);
	}
}

function return_gpx_info(&$db,$town)
{
	echo '<?xml version="1.0" standalone="yes"?>
<gpx xmlns="http://www.topografix.com/GPX/1/1" creator="WIKIDAHEIM.AT" version="1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">
 <metadata>
  <name><![CDATA[WikiDaheim]]></name>
  <desc><![CDATA[WikiDaheim als GPX]]></desc>
 </metadata>'."\n";
 
 
	$categories = get_all_categories($db);
	
	foreach($categories as $categorie => $display_categorie)
	{
		get_gpx_categorie($db, $town, $categorie, $display_categorie);
	}
	
	echo '</gpx>';
}

?>