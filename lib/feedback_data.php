<?php

function return_new_token(&$db)
{
	global $config;
	$token = array();
	$time = time() + 30;
	$token["token"]=sha1(bin2hex(openssl_random_pseudo_bytes(10)).$time.$config['salt']);
	
	$sql = "INSERT INTO `" . $config['dbprefix'] . "feedback`(`token`, `open`, `time`, `mail`, `subject`, `message`) VALUES ('".$token["token"]."',1,".$time.",0,'','')";
	$res = $db->query($sql);
	if($config['log'] > 2)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t para \t sql: \t ".$sql);
	}
	
	return json_encode($token);
}

function validate_key(&$db,$token)
{
	$time = time();
	$sql = "SELECT count(`open`)AS 'ok' FROM `test_feedback` WHERE ".$time." >= `time` AND `token` LIKE '".$token."' AND `open` = 1";
	$res = $db->query($sql);
	
	while($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		if($row['ok'] == 1)
		{
			return true;
		}
	}
	return false;
}

function save_feedback(&$db,$subject,$message,$token)
{
	$sql = "UPDATE `test_feedback` SET `open`=0,`mail`=0,`subject`='".$subject."',`message`='".$message."' WHERE `token` LIKE '".$token."'";
	$res = $db->query($sql);
	
	return true;
}

?>