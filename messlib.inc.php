<?php
require_once("config.inc.php");


function openDB() {
	global $db_hand, $DEBUG, $CONF_DB_host, $CONF_DB_user, $CONF_DB_pass, $CONF_DB_db;
	
	//Verbindung auf die Datenbank aufbauen
	$db_hand = mysql_connect($CONF_DB_host, $CONF_DB_user, $CONF_DB_pass)
		OR DIE(mysql_error());
	//Datenbank auswäen
	mysql_select_db($CONF_DB_db)
		OR DIE(mysql_error());

	if($DEBUG) echo "Datenbankverbindung hergestellt";
	
	return $db_hand;
}


function parseIdParam($idparam) 
{
	global $db_hand, $DEBUG;
	if(!isset($idparam)) DIE("Keine Messtellen-ID gegeben");
	if(!is_array($idparam)) {
		//Der Aufruf erfolgt üie alte Notation, umwandeln in die neue:
		if($DEBUG) echo "Alter Aufruf...<br>";
		$ids = array(intval($idparam));
	} else {
		//Der Aufruf erfolgt üie neue Notation => Prüob alle Werte Ints sind!
		if($DEBUG) echo "Neuer Aufruf...<br>";
		$ids = array();
		foreach($idparam AS $id) {
			$ids[] = intval($id);
		}
	}
	
	return $ids;
}