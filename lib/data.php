<?php

function get_town($db, $lon, $lat)
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

function get_town_wikidata($db, $wikidata)
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

function get_town_kennzahl($db, $kennzahl)
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

function town_exists($db, $town)
{
	global $config;
	
	$town = $db->real_escape_string($town);
	$sql = "SELECT `article_wikipedia` FROM  `" . $config['dbprefix'] . "search` WHERE  `article_wikipedia` LIKE  '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	$erg = $res->num_rows;
	$res->close();
	
	if($erg == 1)
	{
		return true;
	}
	
	return false;
}

function categorie_exists($db, $categorie)
{
	global $config;
	
	if($categorie == "commons")
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
	$res->close();
	if($erg == 1)
	{
		return true;
	}
	
	return false;
}

function categorie_exists_internal($db, $categorie)
{
	global $config;
	
	if($categorie == "commons")
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
	$res->close();
	if($erg == 1)
	{
		return true;
	}
	
	return false;
}

function get_display_categories($db, $display_categories)
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
			
			while($row = $res->fetch_array(MYSQLI_ASSOC))
			{
				$cat = $row['data'];
				$categories[$cat] = $categorie;
			}
	
			$res->close();
		}
		else
		{
			$categories[$categorie] = $categorie;
		}
	}
	
	return $categories;
}

function get_commons_foto_name_by_cat($db,$commonscat,$feature)
{
	global $config;
	$fotos = array();
	$commonscats = array();
	$sql = "SELECT `commons_feature` FROM `" . $config['dbprefix'] . "commons_gemeide_feature` WHERE `feature` LIKE '$feature' AND `commons_gemeinde` LIKE '$commonscat'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$commonscats[] = $row['commons_feature'];
	}
	$res->close();
	
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
		$res->close();
	}
	
	return $fotos;
}

function get_commons_foto_name($db,$commonscat,$feature)
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
	$res->close();
	
	return $foto;
}

function get_commons_categorie($db,$town,$categorie)
{
	global $config;
	$categorie = $db->real_escape_string($categorie);
	$town = $db->real_escape_string($town);
	$features = array();
	$list = array();
	
	// commons categorie
	$sql = "SELECT `commonscat` FROM (SELECT `article_wikipedia` AS `town` FROM `" . $config['dbprefix'] . "search` WHERE `article_wikipedia` LIKE '".$town."') AS `wp` LEFT JOIN (SELECT `commonscat`, `article` FROM `" . $config['dbprefix'] . "wikipedia_township_data`) AS `co` ON `wp`.`town` = `co`.`article`";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$commonscat = $row['commonscat'];
	$res->close();
	
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
	$res->close();
	
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
	$res->close();
	
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
				$listelement['uploadLink'] =  "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=WikiDaheim-at-commons&descriptionlang=de&categories=" . urlencode($commonscat) . "|" . urlencode(str_replace("Category:","",$feature_info['featurescat']));
				$listelement['editLink'] = "https://commons.wikimedia.org/wiki/Category:" . rawurlencode($commonscat);
				if($feature_info['info_false'] != "")
				{
					$listelement['beschreibung'] = $feature_info['info_false'];
				}
				$listelement['complete'] = false;
				
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
					
						$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=WikiDaheim-at-commons&descriptionlang=de&categories=" . urlencode($commonscat) . "|" . urlencode(str_replace("Category:","",$commons));
						$listelement['editLink'] = "https://commons.wikimedia.org/wiki/" .  rawurlencode($commons);
						if($feature_info['info_true'] != "")
						{
							$listelement['beschreibung'] = str_replace("#Category#",str_replace("Category:","",$commons),$feature_info['info_true']);
						}
					
						$list[]=$listelement;
					}
				}
			}
		} // feature loop
	} // data loop
	$res->close();
	
	return $list;
}

function get_external_categorie($db,$town,$categorie,$display_categorie)
{
	global $config;
	$features = array();
	$list = array();
	
	// town for categorie
	$sql = "SELECT `gemeinde_".$categorie."` AS `town` FROM `" . $config['dbprefix'] . "search` WHERE `article_wikipedia` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$town = $row['town'];
	$res->close();
	
	if ($town == "")
	{
		return $list;
	}
	
	// features
	$sql = "SELECT `feature`,`info_true`,`info_false` FROM `" . $config['dbprefix'] . $categorie . "_extern_features` WHERE `online` = 1";
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
	$res->close();
	
	// data
	$sql = "SELECT * FROM `" . $config['dbprefix'] . $categorie . "_extern_data` WHERE (`online`='1' OR `online`='2') AND `gemeinde` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$listelement = "";
		$complete = 0;
		
		$listelement['category'] = $display_categorie;
//		$listelement['lastUpdate'] = $row['data_update'];
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
		}
		
		// HARDCODED stuff
		if ($categorie == "friedhoefe")
		{
			if ($listelement['name'] == "")
			{
				$listelement['name'] = "Friedhof";
			}
			
			$listelement['beschreibung'] = "An diesen Koordinaten befindet sich ein Friedhof.";
			/*if($row["wikidata"] != "")
			{
				$listelement['editLink'] = "https://www.wikidata.org/wiki/".$row["wikidata"];
			}
			else
			{
				$listelement['editLink'] = "https://de.wikipedia.org/wiki/Wikipedia:WikiDaheim";
			}*/
			$listelement['uploadLink'] = "https://commons.wikimedia.org/w/index.php?title=Special:UploadWizard&campaign=WikiDaheim-at-cemeteries&categories=".str_replace(" ","+",$row['gemeinde'])."|Cemeteries_in_Austria_by_state&descriptionlang=de"; 
		}
		
		$listelement['complete'] = false;
		
		$list[]=$listelement;
	}
	
	return $list;
}

function get_list_categorie($db,$town,$categorie,$display_categorie)
{
	global $config;
	$features = array();
	$list = array();
	
	// town for categorie
	$sql = "SELECT `gemeinde_".$categorie."` AS `town` FROM `" . $config['dbprefix'] . "search` WHERE `article_wikipedia` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$town = $row['town'];
	$res->close();
	
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
	$res->close();
	
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
	$res->close();
	
	// data
	$sql = "SELECT * FROM `" . $config['dbprefix'] . $categorie . "_list_data` WHERE (`online`='1' OR `online`='2') AND `gemeinde` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$listelement = "";
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
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wlm-at&id=" . urlencode($row['objektid']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat']));
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wlm-at&id=" . urlencode($row['objektid']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Cultural+heritage+monuments+in+" . str_replace(" ","+",$row['gemeinde']);
			}
		}
		
		else if($categorie=="publicart")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#id-".urlencode(str_replace("/","_",$row['id'])));
			
			$region = str_replace("AT-","at-",$row['region']);
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wlpa-".urlencode($region)."&id=" . urlencode($row['id']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat']));
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wlpa-".urlencode($region)."&id=" . urlencode($row['id']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Public+art+in+" . str_replace(" ","+",$row['gemeinde']);
			}
			
		}
		
		else if($categorie=="kellergasse")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace(array("/"," "),"_",$row['name'])));
			
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=kellergasse-at&id=" . urlencode($row['region-iso']) . "&lat=" . urlencode($row['latitude']) . "&lon=" . urlencode($row['longitude']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat']));
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=kellergasse-at&id=" . urlencode($row['region-iso']) . "&lat=" . urlencode($row['latitude']) . "&lon=" . urlencode($row['longitude']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Kellergassen+in+Austria|" . str_replace(" ","+",$row['gemeinde']);
			}
		}
		
		else if ($categorie=="naturdenkmal")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id'])));
			
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-nd&id=" . urlencode($row['id']) . "|" . urlencode($row['region-iso']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat']));
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-nd&id=" . urlencode($row['id']) . "|" . urlencode($row['region-iso']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Natural+monuments+in+" . str_replace(" ","+",$row['gemeinde']);
			}
		}
		
		else if ($categorie=="hoehle")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id'])));
			
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-hoe&id=" . urlencode($row['id']) . "%7C%0A" . urlencode($row['region-iso']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat']));
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-hoe&id=" . urlencode($row['id']) . "%7C%0A" . urlencode($row['region-iso']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Protected+caves+in+" . str_replace(" ","+",$row['gemeinde']);
			}
		}
		
		else if ($categorie=="landschaftsteil")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id'])));
			
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-glt&id=" . urlencode($row['id']) . "%7C%0A" . urlencode($row['region-iso']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat']));
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
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-glt&id=" . urlencode($row['id']) . "%7C%0A" . urlencode($row['region-iso']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Protected+landscape+elements+in+" . str_replace(" ","+",$bundesland);
			}
		}
		else if ($categorie=="naturpark")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id'])));
			
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-np&id=" . urlencode($row['id']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat']));
			}
			else
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-np&id=" . urlencode($row['id']) . "&descriptionlang=de&description=" . urlencode($row['name']);
			}
		}
		else if ($categorie=="naturschutzgebiet")
		{
			$listelement['editLink'] = str_replace(" ","_","https://de.wikipedia.org/wiki/".$row['article']."#".urlencode(str_replace("/","_",$row['id'])));
			
			if($row['commonscat']!="")
			{
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-nsg&id=" . urlencode($row['id']) . "%7C%0A" .  urlencode($row['region-iso']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=" . urlencode(str_replace("&quot;","\"",$row['commonscat']));
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
				$listelement['uploadLink'] = "https://commons.wikimedia.org/wiki/special:uploadWizard?campaign=wle-at-nsg&id=" . urlencode($row['id']) . "%7C%0A" .  urlencode($row['region-iso']) . "&descriptionlang=de&description=" . urlencode($row['name']) . "&categories=Nature+reserves+in+" . str_replace(" ","+",$bundesland);
			}
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

function get_wiki_categorie($db,$town,$categorie,$display_categorie)
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
	$res->close();
	
	if($type == "commons")
	{
		return get_commons_categorie($db,$town,$categorie);
	}
	else if($type == "external")
	{
		return get_external_categorie($db,$town,$categorie,$display_categorie);
	}
	else if($type == "list")
	{
		return get_list_categorie($db,$town,$categorie,$display_categorie);
	}
	
	$list = array();
		
	return $list;
}

function get_wiki_data($db,$town,$township="wikipedia")
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
	$res->close();
	
	// data
	$sql = "SELECT * FROM `" . $config['dbprefix'] . $township . "_township_data` WHERE (`online`='1' OR `online`='2') AND `article` LIKE '".$town."'";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	$row = $res->fetch_array(MYSQLI_ASSOC);
	$wikielement = "";
		
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
	
	// gemeindekennzahl
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
	$res->close();
	
	
	$wiki = array();
	
	$wiki['source'] = $town;
	$wiki['editLink'] = "https://de.wikipedia.org/w/index.php?title=".rawurlencode($town)."&action=edit";
	$wiki['sections'] = $wikielements;
	
	if($gemeindekennzahl != "")
	{
		$wiki['gemeindekennzahl'] = $gemeindekennzahl;
	}
	
	return $wiki;
}

function return_town_info($db, $town, $wiki, $categories)
{
	global $config;
	$error = 0;
	$town_info = array();
	
	if($wiki)
	{
		$town_info = array
		(
			'articles' => array(),
			'categories' => array(),
		);
		
		$town_info['name'] = $town;
		
		// geo
		$sql = "SELECT `latitude`, `longitude` FROM `" . $config['dbprefix'] . "gemeinde_geo` WHERE `gemeinde` LIKE '".$town."'";
		$res = $db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		
		$town_info['location'] = array(
					'latitude' => $row['latitude'],
					'longitude' => $row['longitude'],
				);
		$res->close();
		
		// commons
		$sql = "SELECT `commonscat` FROM `" . $config['dbprefix'] . "wikipedia_township_data` WHERE `article` LIKE '".$town."'";
		$res = $db->query($sql);
		if($config['log'] > 2)
		{
			append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
		}
		
		$row = $res->fetch_array(MYSQLI_ASSOC);
		$town_info['commonscat'] = $row['commonscat'];
		$res->close();
		
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
			$res->close();
		}
		
		$town_info['articles'][] = get_wiki_data($db,$town/*,$township*/);
		
	}
	else
	{
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
			$town_info_categories = array_merge($town_info_categories,get_wiki_categorie($db, $town, $categorie, $display_categorie));
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