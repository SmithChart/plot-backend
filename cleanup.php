<?
/*
Dieses Script entfernt fuer alle angegebenen Datenreihen die Daten aelter als einen Grenzwert an Tagen aus der DB und schreibt sie vorher in ein CSV-File.

Wird dieses Script z.B. täglich aufgerufen, so entstehen CSV-Dateien die jeweils einen Tag historie enthalten.

*/

// Verzeichnis, reltiv zum Path des Script, in das die Exporte gelegt werden sollen.
$dir = "export";

// Anzahl an Tagen, die nach durchlauf des Scripts noch in der DB stehen.
$keep_days = 31;

//IDs, die vom Script behandelt werden sollen
$ids = array(3,4,5,6,10,11,12,14,20,21,22,23,24,25,30,31,100,101,402,500,501,502,503,503,505,504,506,1251,1252,1301,1302,2000,2001,2002,2003);

$keepAfter= time()-$keep_days*24*60*60; //In Sekunden umwandeln
require_once("config.inc.php");

//Verbindung auf die Datenbank aufbauen
mysql_connect($CONF_DB_host, $CONF_DB_user, $CONF_DB_pass)
OR DIE();
//Datenbank auswäen
mysql_select_db($CONF_DB_db)
OR DIE();

foreach($ids as $id) {
	echo "Bearbeite $id\n";

	//Versuchen wir diese Messstelle in der Datenbank zu finden:
	$result = mysql_query("SELECT ID, Messstelle, Beschreibung, Einheit, lastval, Multiplikator, Offset, Epsilon, Timeout FROM sensor WHERE Messstelle = $id LIMIT 1;");

	if(!$result) {
		echo "Messstelle $id nicht gefunden. Fahre mit nächster ID fort...\n";
		continue;
	}

	echo "Habe Messstelle gefunden\n";

	$messstelle = mysql_fetch_row($result);

	$handle = NULL;

	echo "Lese Daten ";
	$lastTs = 0;
	$num = 0;
	while(1) {
	$result = mysql_query("SELECT Wert, Timestamp FROM messwert WHERE ID_sensor = ".$messstelle[0]." AND Timestamp > $lastTs AND Timestamp < $keepAfter ORDER BY Timestamp ASC LIMIT 1000") OR DIE("Fehler in Subabfrage: \n".mysql_error()."\n");
		
		echo ".";
		$num +=mysql_num_rows($result);
		if(mysql_num_rows($result) == 0) break;
		
		
		while($row = mysql_fetch_row($result)) {
			if($handle == NULL) {
				$handle = fopen($dir."/".$messstelle[1]."_".date("Ymd",$row[1]).".txt","w+");
				fwrite($handle,"#Messstelle: $messstelle[1]\n");
				fwrite($handle,"#Beschreibung: $messstelle[2]\n");
				fwrite($handle,"#Einheit: $messstelle[3]\n");
				fwrite($handle,"#Multiplikator: $messstelle[5]\n");
				fwrite($handle,"#Offset: $messstelle[6]\n");
				fwrite($handle,"#Epsilon: $messstelle[7]\n");
				fwrite($handle,"#Timeout: $messstelle[8]\n");
				fwrite($handle,"\n");
			}
			fwrite($handle,"$row[1], $row[0]\n");
			$lastTs = $row[1];
		}
	}
	if($handle != NULL) fclose($handle);
	echo "\nHabe $num Zeilen exportiert\n";

	$result = mysql_query("DELETE FROM messwert WHERE ID_sensor = ".$messstelle[0]." AND Timestamp < $keepAfter");

	if(!$result) echo "Löschen fehlgeschlagen :o \n";

	echo "Habe ".mysql_affected_rows()." Zeilen gelöscht\n";
		
	echo "\n";
}

?>
