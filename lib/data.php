<?php

function categorie_exists_internal(&$db, $categorie)
{
	global $config;
	
	if($categorie == "commons")
	{
		return true;
	}
	
	if($categorie == "wikidata")
	{
		return true;
	}
	if($categorie == "bilderwunsch")
	{
		return true;
	}
	
	$categorie = $db->real_escape_string($categorie);
	$sql = "SELECT * FROM `" . $config['dbprefix'] . "config` WHERE (`online`='1' OR `online`='2') AND `key` LIKE 'source' AND `data` LIKE '".$categorie."' AND (`type` LIKE 'list' OR `type` LIKE 'external')";
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

function get_commons_foto_name_by_cat(&$db,$commonscat,$feature)
{
	global $config;
	$fotos = array();
	$commonscats_commons = array();
	$commonscats = array();
	
	// get commonscats
	$sql = "SELECT `commons_feature` FROM `" . $config['dbprefix'] . "commons_gemeide_feature` WHERE `feature` LIKE '$feature' AND `commons_gemeinde` LIKE '$commonscat'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$commonscats_commons[] = $row['commons_feature'];
	}
	$res->free();
	
	// remove wikidata
	foreach($commonscats_commons as $commonsfeature)
	{
		$sql = "SELECT count(*) AS `number` FROM `" . $config['dbprefix'] . "wikidata_external_data` WHERE `commonscat` LIKE '" . str_replace("Category:","",$commonsfeature) . "'";
		$res = $db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		if($row['number']==0)
		{
			$commonscats[] = $commonsfeature;
		}
		$res->free();
	}
	
	foreach($commonscats as $commonsfeature)
	{
		$sql = "SELECT `name` FROM `" . $config['dbprefix'] . "commons_photos` WHERE `commons_feature` LIKE '$commonsfeature' AND `commons_gemeinde` LIKE '$commonscat'";
		$res = $db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		while($row = $res->fetch_array(MYSQLI_ASSOC))
		{
			$fotos[$commonsfeature] = str_replace("File:","",$row['name']);
		}
		$res->free();
	}
	
	return $fotos;
}

function get_commons_foto_name(&$db,$commonscat,$feature)
{
	global $config;
	$foto = "";
	$sql = "SELECT `name` FROM `" . $config['dbprefix'] . "commons_photos` WHERE `commons_feature` LIKE '$feature' AND `commons_gemeinde` LIKE '$commonscat'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$foto = str_replace("File:","",$row['name']);
	}
	$res->free();
	
	return $foto;
}

function get_commons_categorie(&$db,$town,$categorie,$town_location)
{
	global $config;
	$categorie = $db->real_escape_string($categorie);
	$town = $db->real_escape_string($town);
	$features = array();
	$list = array();
	
	// state
	$state = "";
	$sql = "SELECT `state` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE `gemeinde` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$state = $row['state'];
	$res->free();
	if ($state == "Burgenland")
	{
		$state = "|" . urlencode("Media from WikiDaheim 2021 in Austria/Burgenland");
	}
	
	// commons categorie
	$sql = "SELECT `commonscat` FROM (SELECT `article_wikipedia` AS `town` FROM `" . $config['dbprefix'] . "search` WHERE `article_wikipedia` LIKE '".$town."') AS `wp` LEFT JOIN (SELECT `commonscat`, `article` FROM `" . $config['dbprefix'] . "wikipedia_township_data`) AS `co` ON `wp`.`town` = `co`.`article`";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$commonscat = $row['commonscat'];
	$res->free();
	
	if($commonscat == "")
	{
		return $list;
	}
	
	// features
	$sql = "SELECT `feature`,`info_true`,`info_false` FROM `" . $config['dbprefix'] . "commons_photos_features` WHERE `online` = 1 OR `online` = 2";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$tmp=$row['feature'];
		$features[$tmp]['info_true'] = $row['info_true'];
		$features[$tmp]['info_false'] = $row['info_false'];
	}
	$res->free();
	
	// featurescat
	$sql = "SELECT `feature`,`alias` FROM `" . $config['dbprefix'] . "commons_photos_features_alias`";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$tmp=$row['feature'];
		if(array_key_exists($row['feature'], $features))
		{
			$features[$tmp]['featurescat'] = $row['alias'];
			$features[$tmp]['featurescat'] = $row['alias'];
		}
	}
	$res->free();
	
	// data
	$sql = "SELECT * FROM `" . $config['dbprefix'] . "commons_commonscat` WHERE (`online`='1' OR `online`='2') AND `commons_gemeinde` LIKE '".$commonscat."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		foreach($features as $feature => $feature_info)
		{
			$listelement = array();
			$listelement['category'] = $categorie;
			$complete = 0;
			
			if ($feature == "Gemeindeamt")
			{
				$listelement['name'] = "Gemeindeamt/Rathaus";
			}
			else
			{
				$listelement['name'] = $feature;
			}
			
			if(($row[$feature] == 0) || ($row[$feature] == 2))
			{
				$listelement['uploadLink'] =  "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=WikiDaheim-at-commons&captionlang=de&descriptionlang=de" . "&categories=" . urlencode($commonscat) . "|" . urlencode(str_replace("Category:","",$feature_info['featurescat'])) . $state;
				$listelement['editLink'] = "https://commons.wikimedia.org/wiki/Category:" . rawurlencode($commonscat);
				if($feature_info['info_false'] != "")
				{
					$listelement['beschreibung'] = $feature_info['info_false'];
				}
				$listelement['complete'] = false;
				
				$listelement['source']['title'] = "Commons";
				$listelement['source']['link'] = $listelement['editLink'];
				
				if($row[$feature] == 2)
				{
					$foto = get_commons_foto_name($db,$commonscat,$feature);
					if($foto != "")
					{
						$listelement['foto'] = $foto;
					}
				}
				
				$list[]=$listelement;
			}
			else if ($row[$feature] == 1)
			{
				$listelement['complete'] = true;
				$fotos = get_commons_foto_name_by_cat($db,$commonscat,$feature);
				if(is_array($fotos))
				{
					foreach($fotos as $commons => $foto)
					{
						$listelement['foto'] = $foto;
						
						$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=WikiDaheim-at-commons&captionlang=de&descriptionlang=de"."&categories=" . urlencode($commonscat) . "|" . urlencode(str_replace("Category:","",$commons)) . $state;
						$listelement['editLink'] = "https://commons.wikimedia.org/wiki/" .  rawurlencode($commons);
						if($feature_info['info_true'] != "")
						{
							$listelement['beschreibung'] = str_replace("#Category#",str_replace("Category:","",$commons),$feature_info['info_true']);
						}
						
						$listelement['source']['title'] = "Commons";
						$listelement['source']['link'] = $listelement['editLink'];
					
						$list[]=$listelement;
					}
				}
			}
		} // feature loop
	} // data loop
	$res->free();
	
	
	// wikidata
	$sql = "SELECT `feature` FROM `" . $config['dbprefix'] . "wikidata_external_category_features_query` WHERE 1";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}

	$wikidata_features = array();
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$wikidata_features[] = $db->real_escape_string($row['feature']);
	}

	$res->free();
	
	// w_features
	$w_features = array();
	$sql = "SELECT `feature`,`info_true`,`info_false` FROM `" . $config['dbprefix'] . "wikidata_external_features` WHERE `online` = 1";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$tmp=$row['feature'];
		$w_features[$tmp]['info_true'] = $row['info_true'];
		$w_features[$tmp]['info_false'] = $row['info_false'];
	}
	$res->free();
	
	// wikidata featurescat
	$wikidata_category_features = array();
	$sql = "SELECT `feature`,`alias` FROM `" . $config['dbprefix'] . "wikidata_external_category_features_alias`";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}

	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$tmp=$row['feature'];
		$wikidata_category_features[$tmp] = $row['alias'];
	}
	$res->free();
	
	
	// distance
	$distance = 1;
	$sql = "SELECT `distance` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE `gemeinde` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$distance = $row['distance'];
	}
	$res->free();
	
	$denkmalliste_town = "";
	$sql = "SELECT `gemeinde_denkmalliste` FROM `test_search` WHERE `article_wikipedia` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$denkmalliste_town = $row['gemeinde_denkmalliste'];
	}
	$res->free();
	
	$sql = "SELECT IFNULL(max(`latitude`) + (min(`latitude`) - max(`latitude`))/2, ".$town_location['latitude'].") AS `latitude`, IFNULL(max(`longitude`) + (min(`longitude`) - max(`longitude`))/2, ".$town_location['longitude'].") AS `longitude`,( 6371 * acos( cos( radians(max(`latitude`)) ) * cos( radians( min(`latitude`) ) ) * cos( radians(max(`longitude`)) - radians(min(`longitude`)) ) + sin( radians (max(`latitude`)) ) * sin( radians (min( `latitude` ) ) ) ) ) AS `distance` FROM (SELECT * FROM `" . $config['dbprefix'] ."denkmalliste_list_data`  WHERE `latitude` != 0 AND `longitude` != 0 AND (`online` = 1 OR `online` = 2) AND `gemeinde` LIKE '".$denkmalliste_town."') AS `coordinates`";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}

	$lat = $lon = 0;
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$lat = $row['latitude'];
		$lon = $row['longitude'];
		if($distance < ($row['distance'])/2)
		{
			$distance = $row['distance']/2+0.001;
		}
	}
	$res->free();
	
	foreach($wikidata_features as $wikidata_feature)
	{
		$sql = "SELECT * FROM (SELECT * , ( 6371 * acos( cos( radians(".$lat.") ) * cos( radians( `latitude` ) ) * cos( radians( `longitude` ) - radians(".$lon.") ) + sin( radians(".$lat.") ) * sin( radians( `latitude` ) ) ) ) AS `entfernung` FROM `" . $config['dbprefix'] . "wikidata_external_data` ORDER BY `entfernung`) AS `data` WHERE ";
		$sql .= "( ( (`entfernung` <= " . $distance . "  AND `place` LIKE '') OR `place` LIKE '".$town."') AND `".$wikidata_feature."` = 1)";
		$sql .= "AND (`online` = 1 OR `online` = 2)";
		$res = $db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		while($row = $res->fetch_array(MYSQLI_ASSOC))
		{
			$listelement = array();
			$complete = 0;
		
			$listelement['category'] = $categorie;
			// !!!!!!!!!!
			foreach($w_features as $feature => $feature_info)
			{
				// tkk
				if($feature=="tkk")
				{
					if($row[$feature] != "")
					{
						$listelement[$feature] = str_replace("'","",str_replace("\\","",$row[$feature]));
					}
				}
				else
				{
					$listelement[$feature] = str_replace("'","",str_replace("\\","",$row[$feature]));
					if($row[$feature] == "")
					{
						if($feature_info['info_false'] != "")
						{
							$listelement[$feature.'_info'] = $feature_info['info_false'];
						}
						$complete++;
					}
					else
					{
						if($feature_info['info_true'] != "")
						{
							$listelement[$feature.'_info'] = $feature_info['info_true'];
						}
					}
				}
			}
			
			$listelement['name'] = $listelement['sLabel'];
			if ($listelement['name'] == "")
			{
				$listelement['name'] = "Wikidata";
			}
			$listelement['beschreibung'] = $listelement['description'];
			$listelement['gemeinde'] = $town;
			
			$listelement['editLink'] = $listelement['article'];
			$listelement['article'] = str_replace(" ","_","https://www.wikidata.org/wiki/".$listelement['wikidata_id']);
			
			if($listelement['commonscat']=="")
			{
				$commonscat_feature = str_replace("Category:","",$wikidata_category_features[$wikidata_feature]);
				$listelement['uploadLink'] = "https://commons.wikimedia.org/w/index.php?title=Special:UploadWizard&campaign=WikiDaheim-at-commons&id=".$listelement['wikidata_id']."&categories=".str_replace(" ","+",$commonscat)."|".str_replace(" ","+",$commonscat_feature).$state."&descriptionlang=de&description=".$listelement['sLabel']."&caption=".urlencode($listelement['sLabel'])."&captionlang=de";
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/w/index.php?title=Special:UploadWizard&campaign=WikiDaheim-at-commons&id=".$listelement['wikidata_id']."&categories=".str_replace(" ","+",$listelement['commonscat']).$state."&descriptionlang=de&description=".$listelement['sLabel']."&caption=".urlencode($listelement['sLabel'])."&captionlang=de";
			}
			
			$listelement['source']['title'] = "Wikidata";
			$listelement['source']['link'] = $listelement['editLink'];
			
			$listelement['complete'] = false;
			$list[]=$listelement;
		}
		$res->free();
	}
	
	return $list;
}

function get_list_categorie(&$db,$town,$categorie,$display_categorie)
{
	global $config;
	$features = array();
	$list = array();
	
	// state
	$state = "";
	$sql = "SELECT `state` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE `gemeinde` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$state = $row['state'];
	$res->free();
	if ($state == "Burgenland")
	{
		$state = "|" . urlencode("Media from WikiDaheim 2021 in Austria/Burgenland");
	}
	
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
		return $list;
	}
	
	// features
	$sql = "SELECT `feature`,`info_true`,`info_false` FROM `" . $config['dbprefix'] . $categorie . "_list_features` WHERE `online` = 1";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$tmp=$row['feature'];
		$features[$tmp]['info_true'] = $row['info_true'];
		$features[$tmp]['info_false'] = $row['info_false'];
	}
	$res->free();
	
	// head feature
	$sql = "SELECT `data` FROM `" . $config['dbprefix'] . $categorie . "_config` WHERE `key` LIKE 'head' AND `type` LIKE 'feature'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$tmp=$row['data'];
		$features[$tmp]['info_true'] = "";
		$features[$tmp]['info_false'] = "";
	}
	$res->free();
	
	// data
	$sql = "SELECT * FROM `" . $config['dbprefix'] . $categorie . "_list_data` WHERE (`online`='1' OR `online`='2') AND `gemeinde` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$listelement = array();
		$complete = 0;
		
		$listelement['category'] = $display_categorie;
		$listelement['lastUpdate'] = $row['data_update'];
		foreach($features as $feature => $feature_info)
		{
			$listelement[$feature] = str_replace("''","",str_replace("\\","",$row[$feature]));
			if($row[$feature] == "")
			{
				if($feature_info['info_false'] != "")
				{
					$listelement[$feature.'_info'] = $feature_info['info_false'];
				}
				$complete++;
			}
			else
			{
				if($feature_info['info_true'] != "")
				{
					$listelement[$feature.'_info'] = $feature_info['info_true'];
				}
			}
			
			if($feature == "artikel")
			{
				if($row[$feature] == "")
				{
					$listelement[$feature.'_url'] = "https://de.wikipedia.org/wiki/Hilfe:Neuen_Artikel_anlegen";
				}
				else
				{
					$listelement[$feature.'_url'] = "https://de.wikipedia.org/wiki/".$row[$feature];
				}
			}
		}
		
		// HARDCODED URLs
		if($categorie=="denkmalliste")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#objektid-".urlencode(str_replace("/","_",$row['objektid'])));
			
			if($row['commonscat']!="")
			{
				//$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wlm-at&fields[]=" . urlencode($row['objektid']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat']));
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wlm-at&fields[]=" . urlencode($row['objektid']) . "&captionlang=de&caption=" . urlencode($row['name']) . " (BDA-HERIS-ID: " . $row['heris'] . ")&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat'])) . $state;
			}
			else
			{
				//$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wlm-at&fields[]=" . urlencode($row['objektid']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Cultural+heritage+monuments+in+" . str_replace(" ","+",$row['gemeinde']);
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wlm-at&fields[]=" . urlencode($row['objektid']) . "&captionlang=de&caption=" . urlencode($row['name']) . " (BDA-HERIS-ID: " . $row['heris'] . ")&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Cultural+heritage+monuments+in+" . str_replace(" ","+",$row['gemeinde']) . $state;
			}
			$listelement['source']['title'] = "Denkmalliste";
			$listelement['source']['link'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']);
		}
		
		else if($categorie=="publicart")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#id-".urlencode(str_replace("/","_",$row['id'])));
			
			$region = str_replace("AT-","at-",$row['region']);
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wlpa-".urlencode($region)."&fields[]=" . urlencode($row['id']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat'])) . $state;
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wlpa-".urlencode($region)."&fields[]=" . urlencode($row['id']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) .  "&categories=Public+art+in+" . str_replace(" ","+",$row['gemeinde']) . $state;
			}
			$listelement['source']['title'] = "Kunstwerk";
			$listelement['source']['link'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']);
			
		}
		
		else if($categorie=="kellergasse")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace(array("/"," "),"_",$row['name'])));
			
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=kellergasse-at&fields[]=" . urlencode($row['region-iso']) . "&lat=" . urlencode($row['latitude']) . "&lon=" . urlencode($row['longitude']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat'])) . $state;
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=kellergasse-at&fields[]=" . urlencode($row['region-iso']) . "&lat=" . urlencode($row['latitude']) . "&lon=" . urlencode($row['longitude']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) .  "&categories=Kellergassen+in+Austria|" . str_replace(" ","+",$row['gemeinde']) . $state;
			}
			$listelement['source']['title'] = "Kellergasse";
			$listelement['source']['link'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']);
		}
		
		else if ($categorie=="naturdenkmal")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace(array("/"," "),"_",$row['id'])));
			
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-nd&fields[]=" . urlencode($row['id']) . "|" . urlencode($row['region-iso']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat'])) . $state;
			}
			else if($row['bezirk']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-nd&fields[]=" . urlencode($row['id']) . "|" . urlencode($row['region-iso']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Natural+monuments+in+" . str_replace(" ","+",$row['bezirk']) . $state;
			}
			else if($row['region-iso']=="AT-9")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-nd&fields[]=" . urlencode($row['id']) . "|" . urlencode($row['region-iso']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Natural+monuments+in+Vienna" . $state;
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-nd&fields[]=" . urlencode($row['id']) . "|" . urlencode($row['region-iso']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Natural+monuments+in+" . str_replace(" ","+",$row['gemeinde']) . $state;
			}
			$listelement['source']['title'] = "Naturdenkmal";
			$listelement['source']['link'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']);
		}
		
		else if ($categorie=="hoehle")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id'])));
			
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-hoe&fields[]=" . urlencode($row['id']) . "%7C%0A" . urlencode($row['region-iso']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat'])) . $state;
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-hoe&fields[]=" . urlencode($row['id']) . "%7C%0A" . urlencode($row['region-iso']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Protected+caves+in+" . str_replace(" ","+",$row['gemeinde']) . $state;
			}
			$listelement['source']['title'] = "Höhle";
			$listelement['source']['link'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']);
		}
		
		else if ($categorie=="landschaftsteil")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id'])));
			
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-glt&fields[]=" . urlencode($row['id']) . "%7C%0A" . urlencode($row['region-iso']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat'])) . $state;
			}
			else
			{
				if($row['region-iso'] == "AT-1")
				{
					$bundesland = "Burgenland";
				}
				else if($row['region-iso'] == "AT-5")
				{
					$bundesland = "Salzburg";
				}
				else if($row['region-iso'] == "AT-6")
				{
					$bundesland = "Styria";
				}
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-glt&fields[]=" . urlencode($row['id']) . "%7C%0A" . urlencode($row['region-iso']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Protected+landscape+elements+in+" . str_replace(" ","+",$bundesland) . $state;
			}
			$listelement['source']['title'] = "Landschaftsteil";
			$listelement['source']['link'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']);
		}
		else if ($categorie=="naturpark")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id'])));
			
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-np&fields[]=" . urlencode($row['id']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de
&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat'])) . $state;
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-np&fields[]=" . urlencode($row['id']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name'] ) . "&categories=" . str_replace("|","",$state);
			}
			$listelement['source']['title'] = "Naturpark";
			$listelement['source']['link'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']);
		}
		else if ($categorie=="naturschutzgebiet")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id'])));
			
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-nsg&fields[]=" . urlencode($row['id']) . "%7C%0A" .  urlencode($row['region-iso']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat'])) . $state;
			}
			else
			{
				if($row['region-iso'] == "AT-1")
				{
					$bundesland = "Burgenland";
				}
				else if($row['region-iso'] == "AT-2")
				{
					$bundesland = "Carinthia";
				}
				else if($row['region-iso'] == "AT-3")
				{
					$bundesland = "Lower+Austria";
				}
				else if($row['region-iso'] == "AT-4")
				{
					$bundesland = "Upper+Austria";
				}
				else if($row['region-iso'] == "AT-5")
				{
					$bundesland = "Salzburg";
				}
				else if($row['region-iso'] == "AT-6")
				{
					$bundesland = "Styria";
				}
				else if($row['region-iso'] == "AT-7")
				{
					$bundesland = "Tyrol";
				}
				else if($row['region-iso'] == "AT-8")
				{
					$bundesland = "Vorarlberg";
				}
				else if($row['region-iso'] == "AT-9")
				{
					$bundesland = "Vienna";
				}
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-nsg&fields[]=" . urlencode($row['id']) . "%7C%0A" .  urlencode($row['region-iso']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Nature+reserves+in+" . str_replace(" ","+",$bundesland) . $state;
			}
			$listelement['source']['title'] = "Naturschutzgebiet";
			$listelement['source']['link'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']);
		}
		else if ($categorie=="tdd")
		{

			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#objektid-".urlencode(str_replace("/","_",$row['objektid'])));
			
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=tdd-at&fields[]=" . urlencode($row['objektid']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat'])) . $state;
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=tdd-at&fields[]=" . urlencode($row['objektid']) . "&captionlang=de&caption=" . urlencode($row['name']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Tag+des+Denkmals+2021" . $state;
			}
			$listelement['source']['title'] = "Denkmalliste";
			$listelement['source']['link'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']);
		}
		
		if($complete == 0)
		{
			$listelement['complete'] = true;
		}
		else
		{
			$listelement['complete'] = false;
		}
		
		$list[]=$listelement;
	}
	
	return $list;
}

function get_request_categorie(&$db,$town,$categorie,$display_categorie,$town_location)
{
	global $config;
	$features = array();
	$list = array();
	
	if ($town == "")
	{
		return $list;
	}
	
	// state
	$state = "";
	$sql = "SELECT `state` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE `gemeinde` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$state = $row['state'];
	$res->free();
	if ($state == "Burgenland")
	{
		$state = "|" . urlencode("Media from WikiDaheim 2021 in Austria/Burgenland");
	}
	
	// commons categorie
	$sql = "SELECT `commonscat` FROM (SELECT `article_wikipedia` AS `town` FROM `" . $config['dbprefix'] . "search` WHERE `article_wikipedia` LIKE '".$town."') AS `wp` LEFT JOIN (SELECT `commonscat`, `article` FROM `" . $config['dbprefix'] . "wikipedia_township_data`) AS `co` ON `wp`.`town` = `co`.`article`";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}

	$row = $res->fetch_array(MYSQLI_ASSOC);
	$commonscat = $row['commonscat'];
	$res->free();
	
	// features
	$sql = "SELECT `feature`,`info_true`,`info_false` FROM `" . $config['dbprefix'] . $categorie . "_external_features` WHERE `online` = 1";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$tmp=$row['feature'];
		$features[$tmp]['info_true'] = $row['info_true'];
		$features[$tmp]['info_false'] = $row['info_false'];
	}
	$res->free();
	
	
	$distance = 1;
	$sql = "SELECT `distance` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE `gemeinde` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$distance = $row['distance'];
	}
	$res->free();
	
	$denkmalliste_town = "";
	$sql = "SELECT `gemeinde_denkmalliste` FROM `test_search` WHERE `article_wikipedia` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$denkmalliste_town = $row['gemeinde_denkmalliste'];
	}
	$res->free();
	
	$sql = "SELECT IFNULL(max(`latitude`) + (min(`latitude`) - max(`latitude`))/2, ".$town_location['latitude'].") AS `latitude`, IFNULL(max(`longitude`) + (min(`longitude`) - max(`longitude`))/2, ".$town_location['longitude'].") AS `longitude`,( 6371 * acos( cos( radians(max(`latitude`)) ) * cos( radians( min(`latitude`) ) ) * cos( radians(max(`longitude`)) - radians(min(`longitude`)) ) + sin( radians (max(`latitude`)) ) * sin( radians (min( `latitude` ) ) ) ) ) AS `distance` FROM (SELECT * FROM `" . $config['dbprefix'] ."denkmalliste_list_data`  WHERE `latitude` != 0 AND `longitude` != 0 AND (`online` = 1 OR `online` = 2) AND `gemeinde` LIKE '".$denkmalliste_town."') AS `coordinates`";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}

	$lat = $lon = 0;
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$lat = $row['latitude'];
		$lon = $row['longitude'];
		if($distance < ($row['distance'])/2)
		{
			$distance = $row['distance']/2+0.001;
		}
	}
	$res->free();
	
	$sql = "SELECT * FROM (SELECT * , ( 6371 * acos( cos( radians(".$lat.") ) * cos( radians( `latitude` ) ) * cos( radians( `longitude` ) - radians(".$lon.") ) + sin( radians(".$lat.") ) * sin( radians( `latitude` ) ) ) ) AS `entfernung` FROM `" . $config['dbprefix'] . $categorie . "_external_data` ORDER BY `entfernung`) AS `data` WHERE ";
	if ($categorie != "wikidata")
	{
		$sql .= "`entfernung` <= " . $distance . " AND (`online` = 1 OR `online` = 2)";
	}
	else
	{
		$sql .= "( (`entfernung` <= " . $distance . " AND `place` LIKE '') OR `place` LIKE '".$town."') ";
		$sql .= "AND (`online` = 1 OR `online` = 2)";
		
		$wikidata_sql = "SELECT `feature` FROM `" . $config['dbprefix'] . "wikidata_external_category_features_query` WHERE 1";
		$res = $db->query($wikidata_sql);
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		while($row = $res->fetch_array(MYSQLI_ASSOC))
		{
			$wikidata_features = $db->real_escape_string($row['feature']);
			$sql .= " AND `".$wikidata_features."` = 0";
		}

		$res->free();
	}
	
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$listelement = array();
		$complete = 0;
		
		$listelement['category'] = $display_categorie;
		
		foreach($features as $feature => $feature_info)
		{
			//tkk
			if($feature=="tkk")
			{
				if($row[$feature] != "")
				{
					$listelement[$feature] = str_replace("'","",str_replace("\\","",$row[$feature]));
				}
			}
			else
			{
				$listelement[$feature] = str_replace("'","",str_replace("\\","",$row[$feature]));
				if($row[$feature] == "")
				{
					if($feature_info['info_false'] != "")
					{
						$listelement[$feature.'_info'] = $feature_info['info_false'];
					}
				}
				else
				{
					if($feature_info['info_true'] != "")
					{
						$listelement[$feature.'_info'] = $feature_info['info_true'];
					}
				}
			}
		}
		
		// HARDCODED stuff
		if ($categorie == "bilderwunsch")
		{
			$listelement['beschreibung'] = $listelement['article'];
			$listelement['name'] = $listelement['description'];
			if ($listelement['name'] == "")
			{
				$listelement['name'] = "Bilderwunsch";
			}
			$listelement['gemeinde'] = $town;
			
			$listelement['uploadLink'] = "https://commons.wikimedia.org/w/index.php?title=Special:UploadWizard&campaign=WikiDaheim-at-bw&id=".urlencode($listelement['article'])."&description=".urlencode($listelement['description'])."&categories=".str_replace(" ","+",$commonscat).$state."&descriptionlang=de&caption=".urlencode($listelement['description'])."&captionlang=de";
			
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$listelement['article']);
			$listelement['article'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$listelement['article']);
			
			$listelement['source']['title'] = "Bilderwunsch";
			$listelement['source']['link'] = $listelement['article'];
		}
		
		if ($categorie == "wikidata")
		{
			$listelement['name'] = $listelement['sLabel'];
			if ($listelement['name'] == "")
			{
				$listelement['name'] = "Wikidata";
			}
			$listelement['beschreibung'] = $listelement['description'];
			$listelement['gemeinde'] = $town;
			
			$listelement['editLink'] = $listelement['article'];
			$listelement['article'] = str_replace(" ","_","https://www.wikidata.org/wiki/".$listelement['wikidata_id']);
			
			$campain = "campaign=WikiDaheim-at-wd&id=".$listelement['wikidata_id'];
			// TKK
			if($listelement['tkk']!="")
			{
				$campain = "campaign=TKK&id=".$listelement['tkk'];
			}
			
			if($listelement['commonscat']=="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/w/index.php?title=Special:UploadWizard&".$campain."&categories=".str_replace(" ","+",$commonscat).$state."&descriptionlang=de&description=".$listelement['sLabel']."&caption=".urlencode($listelement['sLabel'])."&captionlang=de";
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/w/index.php?title=Special:UploadWizard&".$campain."&categories=".str_replace(" ","+",$listelement['commonscat']).$state."&descriptionlang=de&description=".$listelement['sLabel']."&caption=".urlencode($listelement['sLabel'])."&captionlang=de";
			}
			
			$listelement['source']['title'] = "Wikidata";
			$listelement['source']['link'] = $listelement['editLink'];
		}
		
		$listelement['complete'] = false;
		$list[]=$listelement;
	}
	
	return $list;
}

function get_wiki_categorie(&$db,$town,$categorie,$display_categorie,$town_location)
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
	
	if($type == "commons")
	{
		return get_commons_categorie($db,$town,$categorie,$town_location);
	}
	else if($type == "list")
	{
		return get_list_categorie($db,$town,$categorie,$display_categorie);
	}
	else if($type == "request")
	{
		return get_request_categorie($db,$town,$categorie,$display_categorie,$town_location);
	}
	
	$list = array();
	
	return $list;
}

function get_wiki_data(&$db,$town,$township="wikipedia")
{
	global $config;
	
	// features
	$sql = "SELECT `feature`,`info_true`,`info_false` FROM `" . $config['dbprefix'] . $township . "_township_features` WHERE `online` = 1";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$tmp=$row['feature'];
		$features[$tmp]['info_true'] = $row['info_true'];
		$features[$tmp]['info_false'] = $row['info_false'];
	}
	$res->free();
	
	// data
	$sql = "SELECT * FROM `" . $config['dbprefix'] . $township . "_township_data` WHERE (`online`='1' OR `online`='2') AND `article` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$wikielement = array();
		
	foreach($features as $feature => $feature_info)
	{
		$wikielement['name'] = $feature;
		$db_feature = umlaute($feature);
		if(!$row[$db_feature])
		{
			$wikielement['inArticle'] = false;
			if($feature_info['info_false'] != "")
			{
				$wikielement['statusText'] = $feature_info['info_false'];
			}
		}
		else
		{
			$wikielement['inArticle'] = true;
			if($feature_info['info_true'] != "")
			{
				$wikielement['statusText'] = $feature_info['info_true'];
			}
		}
		$wikielement['editLink'] = "https://de.wikipedia.org/w/index.php?title=".rawurlencode($town)."&action=edit";
		$wikielements[]=$wikielement;
	}
	
	// gemeindekennzahl TODO remove
	$gemeindekennzahl = "";
	$sql = "SELECT `gemeindekennzahl` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE `gemeinde` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$gemeindekennzahl = $row['gemeindekennzahl'];
	}
	$res->free();
	// gemeindekennzahl TODO end remove
	
	$wiki = array();
	
	$wiki['source'] = $town;
	$wiki['editLink'] = "https://de.wikipedia.org/w/index.php?title=".rawurlencode($town)."&action=edit";
	$wiki['sections'] = $wikielements;
	
	// gemeindekennzahl TODO remove
	if($gemeindekennzahl != "")
	{
		$wiki['gemeindekennzahl'] = $gemeindekennzahl;
	}
	// gemeindekennzahl TODO end remove
	
	return $wiki;
}

function return_town_info(&$db, $town, $wiki, $categories)
{
	global $config;
	$error = 0;
	$town_info = array();
	$town_location = array();
	
	if($wiki)
	{
		$town_info = array
		(
			'articles' => array(),
			'categories' => array(),
		);
		
		$town_info['name'] = $town;
		
		// geo
		$sql = "SELECT `latitude`, `longitude`, `gemeindekennzahl`, `wikidata` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE `gemeinde` LIKE '".$town."'";
		$res = $db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		
		$town_info['location'] = array(
					'latitude' => $row['latitude'],
					'longitude' => $row['longitude']
				);
		$town_location = array(
					'latitude' => $row['latitude'],
					'longitude' => $row['longitude']
				);
		
		if($row['gemeindekennzahl'] != "")
		{
			$town_info['gemeindekennzahl'] = $row['gemeindekennzahl'];
		}
		
		if($row['wikidata'] != "")
		{
			$town_info['wikidata'] = $row['wikidata'];
			$town_info['GPX'] = "https://api.wikidaheim.at/gpx.php?wikidata=".$row['wikidata'];
		}
		else
		{
			$town_info['GPX'] = "https://api.wikidaheim.at/gpx.php?town=".$town;
		}
		$res->free();
		
		
		// commons
		$sql = "SELECT `commonscat` FROM `" . $config['dbprefix'] . "wikipedia_township_data` WHERE `article` LIKE '".$town."'";
		$res = $db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$town_info['commonscat'] = $row['commonscat'];
		$res->free();
		
		// state
		$sql = "SELECT `state` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE `gemeinde` LIKE '".$town."'";
		$res = $db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$state = $row['state'];
		$res->free();
		if ($state == "Burgenland")
		{
			$town_info['commonscat'] .= "|" . urlencode("Media from WikiDaheim 2021 in Austria/Burgenland");
		}
		
		
		if($row['commonscat'] != "")
		{
			$sql = "SELECT `name` FROM `" . $config['dbprefix'] . "commons_photos` WHERE  `commons_gemeinde` LIKE  '" . $row['commonscat'] . "' ORDER BY `data_update` DESC LIMIT 0 , 6";
			$res = $db->query($sql);
			if($config['log'] > 2)
			{
				append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
			}
			
			$foto_loop = 0;
			while($row = $res->fetch_array(MYSQLI_ASSOC))
			{
				$town_info['foto_'.$foto_loop] = $row['name'];
				$foto_loop++;
			}
			$res->free();
		}
		
		$town_info['articles'][] = get_wiki_data($db,$town/*,$township*/);
		
	}
	else
	{
		// geo
		$sql = "SELECT `latitude`, `longitude` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE `gemeinde` LIKE '".$town."'";
		$res = $db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		
		$town_location = array(
					'latitude' => $row['latitude'],
					'longitude' => $row['longitude']
				);
				
		$res->free();
		
		$town_info = array
		(
			'categories' => array(),
		);
	}
	
	$town_info_categories = array();
	foreach($categories as $categorie => $display_categorie)
	{
		if(categorie_exists_internal($db, $categorie))
		{
			$town_info_categories = array_merge($town_info_categories,get_wiki_categorie($db, $town, $categorie, $display_categorie,$town_location));
		}
		else
		{
			$error++;
			$town_info_categories = array_merge($town_info_categories,return_error_unknown_categorie($categorie));
		}
	}
	$town_info['categories'] = $town_info_categories;
	
	if($error != 0)
	{
		header($_SERVER["SERVER_PROTOCOL"]." 400 Not Found");
	}

	return json_encode($town_info);
}

?>