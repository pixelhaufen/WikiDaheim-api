<?php
header('Access-Control-Allow-Origin: *');

require_once "config/config.php"; // config db, etc
require_once "lib/lib.php"; // config db, etc
require_once "lib/error.php"; // error
require_once "lib/structure.php"; // structure layout
require_once "lib/data.php"; // data

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
	$format = "";
	$action = "";
	$type = "";
	$town = "";
	$categories = array();

	// format
	if(isset($_GET['format']))
	{
		if($_GET['format'] == "json")
		{
			$format = "json";
		}
		else
		{
			// illegal format
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
			echo return_error_unknown_format($_GET['format']);
			return;
		}
	}
	else
	{
		// no format -> json
		$format = "json";
	}

	// action
	if(isset($_GET['action']))
	{
		if($_GET['action'] == "query")
		{
			$action = "query";
		}
		else
		{
			// illegal action
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
			echo return_error_undefined_action($_GET['action']);
			return;
		}
	}
	else
	{
		// no action
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
		echo return_error_no_action();
		return;
	}

	// type
	if(isset($_GET['type']))
	{
		if($_GET['type'] == "structure")
		{
			$type = "structure";
			echo return_structure_categories($db);
		}
		else if($_GET['type'] == "data")
		{
			$type = "data";
		
			if(isset($_GET['wiki']))
			{
				$wiki = true;
			}
			else
			{
				$wiki = false;
			}
		
			if(isset($_GET['categories']))
			{
				$categories = explode("|",$_GET['categories']);
				$categories = get_display_categories($db, $categories);
			}
		
			if(isset($_GET['town']))
			{
				$town = $_GET['town'];
				if(town_exists($db, $town))
				{
					echo return_town_info($db,$town,$wiki,$categories);
				}
				else
				{
					// town dosn't exist
					header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
					echo return_error_unknown_town($_GET['town']);
					return;
				}
			}
			else if((isset($_GET['longitude'])) && (isset($_GET['latitude'])))
			{
				$town = get_town($db, $_GET['longitude'],$_GET['latitude']);
				echo return_town_info($db,$town,$wiki,$categories);
			}
			else if(isset($_GET['wikidata']))
			{
				if($_GET['wikidata'] == "Q19842286"){$_GET['wikidata'] = "Q659891";} // fix mapbox bug
				$town = get_town_wikidata($db, $_GET['wikidata']);
				if(town_exists($db, $town))
				{
					echo return_town_info($db,$town,$wiki,$categories);
				}
				else
				{
					// town dosn't exist
					header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
					echo return_error_unknown_wikidata($_GET['wikidata']);
					return;
				}
			}
			else if(isset($_GET['gemeindekennzahl']))
			{
				$town = get_town_kennzahl($db, $_GET['gemeindekennzahl']);
				if(town_exists($db, $town))
				{
					echo return_town_info($db,$town,$wiki,$categories);
				}
				else
				{
					// town dosn't exist
					header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
					echo return_error_unknown_gemeindekennzahl($_GET['gemeindekennzahl']);
					return;
				}
			}
			else
			{
				header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
				echo return_error_unknown_town($_GET['town']);
				return;
			}
		}
		else
		{
			// illegal type
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
			echo return_error_unknown_type($_GET['type']);
			return;
		}
	}
	else
	{
		// no type
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
		echo return_error_unknown_type("");
		return;
	}	
	$db->close();
} // $db

?>