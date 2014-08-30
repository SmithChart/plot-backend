<?php

/*
Daten-Ausgabe Filer
(Vergleiche getdata.php)
Integriert die Energie des Stratum 0 Smart-Meters fuer jeweils einen Tag auf.

Nimmt an, dass fuer jede Zeile in der Datenbank 1/800kWh verbraucht wurde. (Eingeschaft des Smartmeters)

Ermittelt Tageweise die verbrauchte Energie und liefrt dies als CSV-Datei zurueck.
Es kann je nur eine Messstelle ausgewertet werden:

Aufruf:
energy.php?id=<id>

*/

require_once("config.inc.php");
require_once("energy.inc.php");
require_once("messlib.inc.php");

openDB();
	
$ids = parseIdParam($_GET['id']);


if($DEBUG) echo "Läe des Messstellen-ID-Arrays: ".sizeof($ids)."<br>";

echo "Date,Energy [kWh]\n";

$objDateTime = new DateTime('NOW');

$objDateTime->setTime(0,0,0);

$objDateTime->setTimestamp($objDateTime->getTimestamp()+24*60*60);

do {
	$energy = getIntEnergy($ids[0], $objDateTime->getTimestamp()-24*60*60,$objDateTime->getTimestamp());
	if($energy > 0) {
		echo "".date("Y/m/d H:i:s", $objDateTime->getTimestamp()-24*60*60).", " .$energy."\n";
	}
	
	$objDateTime->setTimestamp($objDateTime->getTimestamp()-24*60*60);
}
while($energy > 0);


?>
