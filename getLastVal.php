<?php

/*
Ausgabe Filter
Liefert fuer eine Messstelle den letzten Wert, sowie dessen Beschreibung als JSON zurueck.

Aufruf:
getLastVal.php?id=<id>


*/

require_once("config.inc.php");
//require_once("getLastVal.inc.php");

$DEBUG = 0;

//Verbindung auf die Datenbank aufbauen
$db_hand=mysql_connect($CONF_DB_host, $CONF_DB_user, $CONF_DB_pass)
	OR DIE();
//Datenbank auswäen
mysql_select_db($CONF_DB_db)
	OR DIE();

$messstelle = intval($_GET['id']);

$result = mysql_query("SELECT ID, Messstelle, Multiplikator, Offset, Epsilon, Timeout, Beschreibung, Einheit  FROM sensor WHERE Messstelle = ".$messstelle.";",$db_hand);

$current= array("Timestamp"=>0, "ForHuman"=>"", "Wert"=>0, "valid"=>FALSE);
$min = array("Timestamp"=>0, "ForHuman"=>"", "Wert"=>0, "valid"=>FALSE);
$max = array("Timestamp"=>0, "ForHuman"=>"", "Wert"=>0, "valid"=>FALSE);
$isValid = FALSE;


header('Content-type: application/json');

    

if(mysql_num_rows($result) > 0) {
	//Wir haben eine oder mehrere Messstellen mit dieser ID gefunden.
	//Wir w�rden aber eh nur die erste nehmen ;)
	$isValid = TRUE;
	
	$row = mysql_fetch_row($result);
	if($DEBUG) echo "-> Mit ID=".$row[0]." in Datenbank gefunden.<br>";

	$res = mysql_query("SELECT Wert, Timestamp FROM messwert WHERE ID_sensor = ".intval($row[0])." ORDER BY Timestamp DESC LIMIT 1;");
	if(mysql_num_rows($res) > 0) {
		$res = mysql_fetch_row($res);
		$current['Timestamp'] = $res[1];
		$current['ForHuman'] = date("Y/m/d H:i:s", $res[1]);
		$current['Wert'] = $res[0];
		$current['valid'] = TRUE;
	}

	$res = mysql_query("SELECT Wert, Timestamp FROM messwert WHERE ID_sensor = ".intval($row[0])." AND Wert = (SELECT MAX(Wert) FROM messwert WHERE ID_sensor = ".intval($row[0]).") ORDER BY Timestamp DESC LIMIT 1;");
	if(mysql_num_rows($res) > 0) {
		$res = mysql_fetch_row($res);
		$max['Timestamp'] = $res[1];
		$max['ForHuman'] = date("Y/m/d H:i:s", $res[1]);
		$max['Wert'] = $res[0];
		$max['valid'] = TRUE;
	}
	$res = mysql_query("SELECT Wert, Timestamp FROM messwert WHERE ID_sensor = ".intval($row[0])." AND Wert = (SELECT MIN(Wert) FROM messwert WHERE ID_sensor = ".intval($row[0]).") ORDER BY Timestamp DESC LIMIT 1;");
	if(mysql_num_rows($res) > 0) {
		$res = mysql_fetch_row($res);
		$min['Timestamp'] = $res[1];
		$min['ForHuman'] = date("Y/m/d H:i:s", $res[1]);
		$min['Wert'] = $res[0];
		$min['valid'] = true;
	}	


}
$json = array("Beschreibung"=>$row[6], "Einheit"=>$row[7],"MessstelleGefunden"=>$isValid,"LetzterWert"=>$current, "Min"=>$min, "Max"=>$max);
echo json_encode($json);

