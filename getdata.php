<?php

require_once("config.inc.php");

//$DEBUG = 1;

//Verbindung auf die Datenbank aufbauen
mysql_connect($CONF_DB_host, $CONF_DB_user, $CONF_DB_pass)
	OR DIE();
//Datenbank auswäen
mysql_select_db($CONF_DB_db)
	OR DIE();

if(!isset($_GET['id'])) DIE();
if(!is_array($_GET['id'])) {
	//Der Aufruf erfolgt üie alte Notation, umwandeln in die neue:
	if($DEBUG) echo "Alter Aufruf...<br>";
	$ids = array(intval($_GET['id']));
} else {
	//Der Aufruf erfolgt üie neue Notation => Prüob alle Werte Ints sind!
	if($DEBUG) echo "Neuer Aufruf...<br>";
	$ids = array();
	foreach($_GET['id'] AS $id) {
		$ids[] = intval($id);
	}
}


if($DEBUG) echo "Läe des Messstellen-ID-Arrays: ".sizeof($ids)."<br>";


//Preprocess Polish
$pHold = FALSE;
$pNotNull = FALSE;
if(isset($_GET['polish']) && is_array($_GET['polish'])) {
	if(in_array('hold', $_GET['polish'])) {
		$pHold = TRUE;
	}
	if(in_array('notnull', $_GET['polish'])) {
		$pNotNull = TRUE;
	}
}

$searchstring = "";
for($i = 0; $i < (sizeof($ids)-1); $i++) {
	$searchstring = $searchstring."Messstelle = ".$ids[$i]." OR ";
}
$searchstring = $searchstring."Messstelle = ".$ids[sizeof($ids)-1];


if($DEBUG) echo "Suchestring der Messstellen: >$searchstring< <br>";
	
//Versuchen wir diese Messstelle in der Datenbank zu finden:
$result = mysql_query("SELECT ID, Messstelle, Beschreibung, Einheit, lastval FROM sensor WHERE $searchstring;")
	OR DIE();

if(!$result) {
	DIE();
}
if($DEBUG) echo "Habe ".mysql_num_rows($result)." IDs von Messstellen erhalten!<br>";


//Messstellendaten sichern
$stellendaten = array();
while($row = mysql_fetch_row($result)) {
	$stellendaten[] = $row;
	if($DEBUG) echo "Stelle $row[0]: $row[2]<br>";
}

$time = 24*60*60;
if(isset($_GET['time'])) $time = intval($_GET['time'])*60*60;

//Suchstring fü Daten vorbereiten:
$searchstring = "";
for($i = 0; $i < (sizeof($stellendaten)-1); $i++) {
	$searchstring = $searchstring."ID_sensor = ".$stellendaten[$i][0]." OR ";
}
$searchstring = $searchstring."ID_sensor = ".$stellendaten[sizeof($stellendaten)-1][0];


if($DEBUG) echo "Suchestring der Daten: >$searchstring< <br>";

//Daten fü Messstellen holen!
$result = mysql_query("SELECT ID_sensor, Wert, Timestamp FROM messwert WHERE ($searchstring) AND Timestamp > ".(time()-$time)." AND Wert != 85 ORDER BY Timestamp ASC;")	OR DIE();

if($DEBUG) echo "Habe ".mysql_num_rows($result)." Datenreihen geholt!<br>";

//leeres vorlagenarray erzeugen:
$larr = array('');
for($i=0; $i<sizeof($stellendaten); $i++) {
	$larr[] = '';
}

if($DEBUG) echo "Anfang der csv-Daten...";

echo "Date,";

for($i=0; $i<(count($stellendaten)-1); $i++) {
	$stelle = $stellendaten[$i];
	echo htmlentities("".$stelle[2]." [".$stelle[3]."],");
}
$stelle = $stellendaten[$i];
echo htmlentities("".$stelle[2]." [".$stelle[3]."]");
echo "\n";

$pHoldV = $larr;

while($row = mysql_fetch_row($result)) {
	// Füe Datenzeile:
	
	//polish:notNull
	if($pNotNull) {
		if($row[1] == 0) {
			continue;
		}
	}
	
	// Neue Zeile vorbereiten
	$narr = $larr;
	
	// Timestamp kopieren:
	$narr[0] = date("Y/m/d H:i:s", $row[2]);
	
	//Position der Messstelle suchen:
	for($i=0; $i<sizeof($stellendaten); $i++) {
		if($stellendaten[$i][0] == $row[0]) {
			$pos = $i;
			break;
		}
	}
	//Messwert an die richtige Stelle schreiben:
	$narr[$pos+1] = $row[1];

	//polish:hold
	if($pHold) {
		if($pHoldV[$pos+1] != $row[1]) {
			if($pHoldV[$pos+1] != NULL) {
				//echo $pHoldV[$pos+1]."\n";
				$marr = $larr;
				$marr[0] = date("Y/m/d H:i:s", $row[2]);
				$marr[$pos+1] = $pHoldV[$pos+1];

				for($i=0; $i<(sizeof($marr)-1); $i++) {
					echo $marr[$i].",";
				}
				echo $marr[$i];
				echo "\n";
				//echo ",p".$row[1].",";
			}

			$pHoldV[$pos+1] = $row[1];
			//echo $pHoldV[$pos+1]."\n";
		}
	}
	
	for($i=0; $i<(sizeof($narr)-1); $i++) {
		echo $narr[$i].",";
	}
	echo $narr[$i];
	echo "\n";
}

?>
