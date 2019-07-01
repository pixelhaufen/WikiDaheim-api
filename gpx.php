<?php
header('Content-type: application/gpx+xml');

require_once "config/config.php"; // config db, etc
require_once "lib/lib.php"; // config db, etc
require_once "lib/get_town.php"; // get town
require_once "lib/town_exists.php"; // get town
require_once "lib/display_categories.php"; // display categories
require_once "lib/gpx.php"; // gpx

// mysql
$db = new mysqli($config['dbhost'], $config['dbuser'], $config['dbpassword'], $config['dbname']);

if ($db->connect_error)
{
	// log error
	if($config['log'] > 0)
	{
		append_file("log/api.txt","\n".date(DATE_RFC822)." \t error \t db connect_error \t gpx_main()");
	}
	
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
	echo "unknown_error";
}
else
{
	if(isset($_GET['categories']))
	{
		$categories = explode("|",$_GET['categories']);
		$categories = get_display_categories($db, $categories);
	}
	else
	{
		$categories = get_all_categories($db);
	}
	
	$town = "";
	
	if(isset($_GET['town']))
	{
		$town = $db->real_escape_string($_GET['town']);
		if(town_exists($db, $town))
		{
			header('Content-disposition: attachment; filename=WikiDaheim_'.str_replace(" ","_",$town).'.gpx');
			echo return_gpx_info($db,$town,$categories);
		}
		else
		{
			// town dosn't exist
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
			echo "unknown_town";
			return;
		}
	}
	else if((isset($_GET['longitude'])) && (isset($_GET['latitude'])))
	{
		$town = get_town($db, $_GET['longitude'],$_GET['latitude']);
		header('Content-disposition: attachment; filename=WikiDaheim_'.str_replace(" ","_",$town).'.gpx');
		echo return_gpx_info($db,$town,$categories);
	}
	else if(isset($_GET['wikidata']))
	{
		if($_GET['wikidata'] == "Q19842286"){$_GET['wikidata'] = "Q659891";} // fix mapbox bug
		$town = get_town_wikidata($db, $_GET['wikidata']);
		if(town_exists($db, $town))
		{
			header('Content-disposition: attachment; filename=WikiDaheim_'.str_replace(" ","_",$town).'.gpx');
			echo return_gpx_info($db,$town,$categories);
		}
		else
		{
			// town dosn't exist
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
			echo "unknown_wikidata";
			return;
		}
	}
	else if(isset($_GET['gemeindekennzahl']))
	{
		$town = get_town_kennzahl($db, $_GET['gemeindekennzahl']);
		if(town_exists($db, $town))
		{
			header('Content-disposition: attachment; filename=WikiDaheim_'.str_replace(" ","_",$town).'.gpx');
			echo return_gpx_info($db,$town,$categories);
		}
		else
		{
			// town dosn't exist
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
			echo "unknown_gemeindekennzahl";
			return;
		}
	}
	else
	{
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
		echo "unknown_town";
		return;
	}
	
	$db->close();
} // $db

?>