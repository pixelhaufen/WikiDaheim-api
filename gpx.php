<?php
header('Content-disposition: attachment; filename=WikiDaheim.gpx');
header('Content-type: text/xml');
//header('Access-Control-Allow-Origin: *');

require_once "config/config.php"; // config db, etc
require_once "lib/lib.php"; // config db, etc
require_once "lib/data.php"; // data
require_once "gpx/data.php"; // data

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
	$town = "";
	
	if(isset($_GET['town']))
	{
		$town = $db->real_escape_string($_GET['town']);
		if(town_exists($db, $town))
		{
			echo return_gpx_info($db,$town);
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
		echo return_gpx_info($db,$town);
	}
	else if(isset($_GET['wikidata']))
	{
		if($_GET['wikidata'] == "Q19842286"){$_GET['wikidata'] = "Q659891";} // fix mapbox bug
		$town = get_town_wikidata($db, $_GET['wikidata']);
		if(town_exists($db, $town))
		{
			echo return_gpx_info($db,$town);
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
			echo return_gpx_info($db,$town);
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