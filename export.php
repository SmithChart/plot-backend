<?php
/*
Exporter.
Exportiert eine komplette Messtelle in eine .csv-Datei


*/

$dir = "backup";


require_once("config.inc.php");

//Verbindung auf die Datenbank aufbauen
mysql_connect($CONF_DB_host, $CONF_DB_user, $CONF_DB_pass)
OR DIE();
//Datenbank auswäen
mysql_select_db($CONF_DB_db)
OR DIE();

if(!isset($argv[1])) DIE("ID-Parameter nicht gesetzt.");

$id = intval($argv[1]);


echo "Schreibe alle Daten für ID $id nach >$dir<\n";

//Versuchen wir diese Messstelle in der Datenbank zu finden:
$result = mysql_query("SELECT ID, Messstelle, Beschreibung, Einheit, lastval, Multiplikator, Offset, Epsilon, Timeout FROM sensor WHERE Messstelle = $id LIMIT 1;")
OR DIE();

if(!$result) {
DIE();
}
echo "Habe ".mysql_num_rows($result)." IDs von Messstellen erhalten!\n";

$messstelle = mysql_fetch_row($result);

$infoh = fopen($dir."/".$messstelle[1]."_info.txt","w+");
fwrite($infoh,"Messstelle: $messstelle[1]\n");
fwrite($infoh,"Beschreibung: $messstelle[2]\n");
fwrite($infoh,"Einheit: $messstelle[3]\n");
fwrite($infoh,"Multiplikator: $messstelle[5]\n");
fwrite($infoh,"Offset: $messstelle[6]\n");
fwrite($infoh,"Epsilon: $messstelle[7]\n");
fwrite($infoh,"Timeout: $messstelle[8]\n");
fclose($infoh);


$fh = fopen($dir."/".$messstelle[1].".txt", "w+");
$lastTS = 0;
$num = 0;
echo "Schreibe ";
while(1) {
$result = mysql_query("SELECT Wert, Timestamp FROM messwert WHERE ID_sensor = $messstelle[0] AND Timestamp > $lastTS ORDER BY Timestamp ASC LIMIT 1000") OR DIE("Fehler in Subabfrage: \n".mysql_error()."\n");
	
	echo ".";
	$num +=mysql_num_rows($result);
	if(mysql_num_rows($result) == 0) break;

	while($row = mysql_fetch_row($result)) {
		fwrite($fh,"$row[1], $row[0]\n");
		$lastTS = $row[1];
	}

}
fclose($fh);
echo " Abgeschlossen\n";
echo "$num Zeilen geschrieben";
?>

