<?php
/*
Importierer
Importiert Daten aus einer CSV-Datei in eine Messstelle.
Die Messstelle muss vorher in der Datenbank vorhanden sein.

Aufruf ueber die CLI:
php import.php <datei.txt> <messstelle>
- <datei.txt>  CSV-File aus der export.php
- <messstelle> Messstellen-ID, in die Importiert werden soll

*/



require_once("config.inc.php");

//Verbindung auf die Datenbank aufbauen
$dbh = mysql_connect($CONF_DB_host, $CONF_DB_user, $CONF_DB_pass)
OR DIE();
//Datenbank auswäen
mysql_select_db($CONF_DB_db)
OR DIE();

if(!isset($argv[1])) DIE("Eingabe-File nicht gesetzt.");
$fn = $argv[1];

if(!isset($argv[2])) DIE("ID-Parameter nicht gesetzt.");
$id = intval($argv[2]);

$force = false;
if(isset($argv[3])) {
	if($argv[3] == "force") {
		$force = true;
	}	
}


//Versuchen wir diese Messstelle in der Datenbank zu finden:
$result = mysql_query("SELECT ID, Messstelle, Beschreibung, Einheit, lastval, Multiplikator, Offset, Epsilon, Timeout FROM sensor WHERE Messstelle = $id LIMIT 1;")
OR DIE();

if(!$result) {
DIE();
}
//echo "Habe ".mysql_num_rows($result)." IDs von Messstellen erhalten!\n";

if(mysql_num_rows($result) == 0) {
	echo "Angegebene Messstelle wurde in der Datenbank nicht gefunden.";
	DIE();
}
echo "Messstelle gefunden\n";

$messstelle = mysql_fetch_row($result);

$fh = fopen($fn,"r") OR DIE("CSV-Datei konnte nicht geoeffnet werden");
echo "CSV-Datei geoeffnet\n";

$lines = array();
while(1) {
	if(($line = fgets($fh)) == FALSE) {
		break;
	}
	$line = trim($line);
	$la = explode(",", $line);
	if(count($la) != 2) {
		DIE("Miss-Formatierte Zeile in CSV-Datei");
	}
	$la[0] = intval(trim($la[0]));
	$la[1] = floatval(trim($la[1]));

	array_push($lines,$la);
}
echo count($lines) . " Zeilen aus der CSV-Datei gelesen\n";

$n = 0;
echo "Fuege in Datenbank ein";
for($i=0; $i<count($lines); $i++) {
	$result = mysql_query("INSERT INTO messwert (ID_sensor, Wert, Timestamp) VALUES ('".$messstelle[0]."', '".$lines[$i][1]."', '".$lines[$i][0]."')");
	if($result == FALSE) DIE("Fehler beim Einfuegen:\n".mysql_error($dbh));
	if(($i%1000)==0) echo ".";
	$n++;
}
echo "\nAbgeschlossen\n";
echo "$n Zeilen in die Datenbank eingefuegt\n";
?>

