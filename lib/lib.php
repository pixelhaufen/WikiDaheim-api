<?php

function append_file($file,$data)
{
	$fp = fopen($file, "a");
	fputs ($fp, $data);
	fclose ($fp);
}

function umlaute($change)
{
	return str_replace(array("Ä", "Ö", "Ü", "ä", "ö", "ü", "ß", "´"), array("Ae", "Oe", "Ue", "ae", "oe", "ue", "ss", ""), $change);
}

?>