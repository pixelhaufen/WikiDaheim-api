<?php
header('Access-Control-Allow-Origin: *');

require_once "config/config.php"; // config db, etc
require_once "lib/error.php"; // error
require_once "lib/feedback_data.php"; // feedback data

// mysql
$db = new mysqli($config['dbhost'], $config['dbuser'], $config['dbpassword'], $config['dbname']);


if ($db->connect_error)
{
	// log error
	if($config['log'] > 0)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t error \t db connect_error \t main()");
	}
	
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	echo return_error();
}
else
{
	if(isset($_GET['token']))
	{
		$token = $db->real_escape_string($_GET['token']);
		if($token == "new")
		{
			echo return_new_token($db);
		}
		else if(validate_key($db,$token))
		{
			if(isset($_GET['subject']))
			{
				if(isset($_GET['message']))
				{
					$subject = $db->real_escape_string($_GET['subject']);
					$message = $db->real_escape_string($_GET['message']);
					if(save_feedback($db,$subject,$message,$token))
					{
						echo json_encode(array('OK' => array('code' => 'save','info' => 'Message saved',),));
						return;
					}
					else
					{
						header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
						echo return_error_feedback_faild();
					}
				}
				else
				{
					// illegal message
					header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
					echo return_error_illegal_feedback("message");
					return;
				}
			}
			else
			{
				// illegal subject
				header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
				echo return_error_illegal_feedback("subject");
				return;
			}
		}
		else
		{
			// illegal token
			header($_SERVER["SERVER_PROTOCOL"]." 451 Unavailable For Legal Reasons");
			echo return_error_unknown_key($token);
			return;
		}
	}
	else
	{
		// no token
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
		echo return_error_unknown_request();
		return;
	}	
	$db->close();
} // $db

?>