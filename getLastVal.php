<?php

/*
Ausgabe Filter
Liefert fuer eine Messstelle den letzten Wert, sowie dessen Beschreibung als JSON zurueck.

Aufruf:
getLastVal.php?id=<id>


*/

require_once("config.inc.php");
require_once("getLastVal.inc.php");

//$DEBUG = 1;

//Verbindung auf die Datenbank aufbauen
$db_hand=mysql_connect($CONF_DB_host, $CONF_DB_user, $CONF_DB_pass)
	OR DIE();
//Datenbank auswäen
mysql_select_db($CONF_DB_db)
	OR DIE();

$messstelle = intval($_GET['id']);

$result = mysql_query("SELECT ID, Messstelle, Multiplikator, Offset, Epsilon, Timeout, Beschreibung, Einheit  FROM sensor WHERE Messstelle = ".$messstelle.";",$db_hand)
        OR DIE(mysql_error());
    

        if(mysql_num_rows($result) > 0) {
                //Wir haben eine oder mehrere Messstellen mit dieser ID gefunden.
                //Wir w�rden aber eh nur die erste nehmen ;)
                $row = mysql_fetch_row($result);
                if($DEBUG) echo "-> Mit ID=".$row[0]." in Datenbank gefunden.<br>";
    
                $wert = mysql_query("SELECT lastval,Einheit,Beschreibung FROM sensor WHERE ID = '".$row[0]."';",$db_hand);
                if(!$wert) echo mysql_error();
                $wert = mysql_fetch_row($wert);
    
		
   		$json = array("Beschreibung"=>$row[6], "Einheit"=>$row[7], "Wert"=>intval($wert[0]));
//		var_dump($json);
		echo json_encode($json);
        }

