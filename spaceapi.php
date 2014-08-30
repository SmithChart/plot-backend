<?php
//Daten-Getter fuer die Hackerspace API
//Liefert den Open-Close Zustand an das Logging zurueck.

//URL zum API-File deines Hackerspaces
//TODO: Eventuell durch einen Hackerspace ersezten.
$api = file_get_contents("https://status.stratum0.org/status.json");

$j = json_decode($api,true);

if(array_key_exists('state', $j)) {
	if(array_key_exists('open',$j['state'])) {
		$open = $j['state']['open'];
		echo "$open";
		//Ablegen des Status im Logging:
		//TODO:Setze hier deine URL und deine Messstellen-ID ein.
		echo file_get_contents("http://yourhost.de/daten.php?id[]=<id>&val=".intval($open));
	} else {
		echo "Open not found";
	}
} else {
	echo "State not found";
}

?>

