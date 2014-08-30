<?php
/*
Daten-Senke fuer Langzeit Logging

Diese Datei nimmt fuer eine oder mehrere Messstellen Daten entgegen und legt diese in der Datenbank ab.
Die Daten werden mit dem aktuellen Zeitstempel in die Datenbank geschrieben.
Vor dem Schreiben der Daten wird eine Umrechnung (DB-Felder Multiplikator und Offset) durchgefuert.
Fuer den umgerechneten Wert wird geprueft, ob es die Aenderung zum Schreiben ausreichend gross ist. Mehr Informationen zum Gateing gibts in der Readme.

Aufruf der Datei:

Ohne Passwort:
daten.php?id[]=<id1>&val[]=<val1>&id[]=<id2>&val=<val2> ...

Mit Passwort:
daten.php?id[]=<id1>&val[]=<val1>&id[]=<id2>&val=<val2> ... &password=<pass>


*/





//Sensoren-Auswertung
//
//Speicher eingehende Messwerte in der Datenabnk

require_once("config.inc.php");

//Bevor wir die eingehenden Daten in die Datenbank schreiben wollen
//wir sie mal auf Plausibilität prüfen!

if(!isset($_GET['id']) || !isset($_GET['val'])) {
	//ID oder VAL wurde uns nicht mitgeteilt!
	DIE("ID oder VAL nicht übermittelt!");
}

//Wir haben für beide Einträge Daten erhalten
$ids = $_GET['id'];
$values = $_GET['val'];

if(count($ids) != count($values)) {
	DIE("Ungleich viele IDs und Werte bekommen");
}

//Sicherheitshalber beide Arrays noch mal in ihren jeweiligen
//nativen Datentyp umwandeln
foreach($ids AS $id) $id = intval($id);
foreach($values AS $value) $value = floatval($value);


if(isset($_GET['password'])) {
	//Der Aufruf geschieht mit Password. 
	$GETpassword = $_GET['password'];
} else {
	//Der Aufruf geschieht ohne Password. Es wird ein leeres Passwort angenommen
	echo " #### ACHTUNG! Der Aufruf geschieht ohne Passwort! Dies ist ein potentielles Sicherheitsrisio, wenn diese Datei aus dem Internet erreichbar ist! ####<br>";
	$GETpassword = "";
}



if($DEBUG) echo "Habe ".count($ids)." IDs und Messwerte bekommen<br>";


//Verbindung auf die Datenbank aufbauen
mysql_connect($CONF_DB_host, $CONF_DB_user, $CONF_DB_pass)
	OR DIE("Konnte nicht zur DB verbinden");
//Datenbank auswählen
mysql_select_db($CONF_DB_db)
	OR DIE("Konnte die Datenbank nicht auswählen!");
if($DEBUG) echo "Zur Datenbank verbunden<br>";

//Für jeden erhaltenen Messwert Prüfen, ob diese Messstelle in der Datenbank vorhanden ist. Falls ja den Messwert entsprechen umrechnen und einfügen!
$i = 0;
$timestamp = time();
for($i=0; $i<count($ids);$i++) {
	echo "<br>Für Messstelle: ".$ids[$i]."<br>";
	//Versuchen wir diese Messstelle in der Datenbank zu finden:
	$result = mysql_query("SELECT ID, Messstelle, Multiplikator, Offset, Epsilon, Timeout, Password FROM sensor WHERE Messstelle = ".$ids[$i].";")
		OR DIE(mysql_error());
	

	if(mysql_num_rows($result) > 0) {
		//Wir haben eine oder mehrere Messstellen mit dieser ID gefunden.
		//Wir würden aber eh nur die erste nehmen ;)
		
		$row = mysql_fetch_row($result);
		echo "-> Mit ID=".$row[0]." in Datenbank gefunden.<br>";

		if($values[$i] == 85) {
			echo "-> Messwert war 85. Aufnahme in Datenbank verweigert.<br>";
			continue;
		}

		if($row[6] == $GETpassword) 
		{
			echo "-> Passwort OK, oder für diese Messstelle ist kein Passwort definiert!<br>";
			echo "-> Übermittelter Wert ".$values[$i]."<br>";
			$angepassterWert = $values[$i]*$row[2] + $row[3];
			echo "-> Umgerechneter Wert ".$angepassterWert."<br>";

			//Schreiben des letzten Wertes in die Datenbank für den Sensor
			$update = mysql_query("UPDATE sensor SET lastval = '".$angepassterWert."' WHERE ID = '".$row[0]."';");
			if(!$update) echo mysql_error();


			echo "-> Epsilon (Auflösung, bzw hier minimale Änderung) der Messstelle: ".$row[4]."<br>";
			//Den letzten Wert aus der Datenbank holen um ihn vergleichen zu können
			$letzterWert =  mysql_query("SELECT `Wert`,`Timestamp`  FROM `messwert` WHERE `ID_sensor`=".$row[0]." ORDER BY `Timestamp` DESC LIMIT 0,1;");
			$schreiben = false;
			if($letzterWert) {
				//Es gibt einen letzten Wert!
				echo "-> Letzten Wert gefunden!<br>";
				$letzterWert = mysql_fetch_row($letzterWert);
				
				if(abs($letzterWert[0]-$angepassterWert)>=$row[4]) {
					$schreiben = true;
					echo "-> Änderung zu klein zum Schreiben!<br>";
				}

				if(($timestamp-$letzterWert[1]) > $row[5]) {
					$schreiben = true;
					echo "-> Timeout zwingt uns zum Schreiben!<br>";
				}
			} else {
				echo "-> Keinen Letzten Wert in der DB gefunden. Wir schreiben auf jeden Fall!<br>";
			}

			if($schreiben) {
				//Fügen wir den Übermittelten Wert in die Datenbank ein!
				mysql_query("INSERT INTO messwert (ID_sensor, Timestamp, Wert) VALUES (".$row[0].",".$timestamp.",".$angepassterWert.");")
					OR DIE(mysql_error());

				echo "-> Affected Rows beim Einfügen: ".mysql_affected_rows()."<br>";
			}
		} 
		else
		{
			echo "-> Das übermittelte Passwort passt nicht zur Messstelle! Tue nichts...<br>";
		}
	} else {
		echo "-> Nicht in Datenbank gefunden!<br>";
	}
}
echo "<br> Fertig!<br>";
?>
