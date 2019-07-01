<?php

function town_exists(&$db, $town)
{
	global $config;
	
	$sql = "SELECT `article_wikipedia` FROM  `" . $config['dbprefix'] . "search` WHERE  `article_wikipedia` LIKE  '".$town."'";
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

?>