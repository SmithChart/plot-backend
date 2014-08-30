<?php
require_once("config.inc.php");



function getIntEnergy($messstelle, $start, $stop)
{
	global $db_hand, $DEBUG;
	$result = mysql_query("SELECT ID, Messstelle FROM sensor WHERE Messstelle = ".$messstelle." LIMIT 1;",$db_hand)
	OR DIE(mysql_error());
	

	if(mysql_num_rows($result) > 0) {
		//Wir haben eine oder mehrere Messstellen mit dieser ID gefunden.
		$row = mysql_fetch_row($result);
		if($DEBUG) echo "-> Mit ID=".$row[0]." in Datenbank gefunden.<br>";
		
		$query = "SELECT COUNT(*) FROM messwert WHERE ID_sensor = " .$row[0]." AND Timestamp > " .intval($start) . 
			" AND Timestamp < " . intval($stop) .";";
		if($DEBUG) echo "$query<br>";
		$daten = mysql_query($query)
		OR DIE(mysql_error());
		
		if(mysql_num_rows($daten) < 1) return -1;
		
		$count = mysql_fetch_row($daten);
		
		if($DEBUG) echo "-> ". $count[0] ." Datenreihen erhalten.<br>";
		
		
		
		return $count[0]/800.0;
	}
}	
	
?>