<?php
// error functions

function return_error()
{
	$error = array
	(
		'error' => array
		(
			'code' => 'unknown_error',
			'info' => 'Sorry, something went completely wrong.',
		),
	);
	
	return json_encode($error);
}


function return_error_no_action()
{
	$error = array
	(
		'error' => array
		(
			'code' => 'no_action',
			'info' => 'Parameter "action" not defined.',
		),
	);
	
	return json_encode($error);
}

function return_error_undefined_action($info)
{
	$error = array
	(
		'error' => array
		(
			'code' => 'unknown_action',
			'info' => 'Unrecognized value for parameter "action":'.$info.'.',
		),
	);
	
	return json_encode($error);
}

function return_error_unknown_categorie($info)
{
	$error = array(array
	(
		'error' => array
		(
			'code' => 'unknown_categorie',
			'info' => 'Unrecognized value for parameter "categorie":'.$info.'.',
		),
	));
	
	return $error;
}

function return_error_unknown_format($info)
{
	$error = array
	(
		'error' => array
		(
			'code' => 'unknown_format',
			'info' => 'Unrecognized value for parameter "format":'.$info.'.',
		),
	);
	
	return json_encode($error);
}

function return_error_unknown_type($info)
{
	$error = array
	(
		'error' => array
		(
			'code' => 'unknown_type',
			'info' => 'Unrecognized value for parameter "type":'.$info.'.',
		),
	);
	
	return json_encode($error);
}

function return_error_unknown_town($info)
{
	$error = array
	(
		'error' => array
		(
			'code' => 'unknown_town',
			'info' => 'Unrecognized value for parameter "town":'.$info.'.',
		),
	);
	
	return json_encode($error);
}

function return_error_unknown_wikidata($info)
{
	$error = array
	(
		'error' => array
		(
			'code' => 'unknown_wikidata',
			'info' => 'Unrecognized value for parameter "wikidata":'.$info.'.',
		),
	);
	
	return json_encode($error);
}

function return_error_unknown_gemeindekennzahl($info)
{
	$error = array
	(
		'error' => array
		(
			'code' => 'unknown_wikidata',
			'info' => 'Unrecognized value for parameter "gemeindekennzahl":'.$info.'.',
		),
	);
	
	return json_encode($error);
}

?>