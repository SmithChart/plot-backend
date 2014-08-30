<?php
$clients_s = file("http://www.freifunk-bs.de:8888/clientcount");
if(count($clients_s) == 1) {
	echo "Clients: $clients_s[0]<br>\n";
	$ret = file("http://stuff.tinyhost.de/mess/daten.php?id[]=500&val[]=".intval($clients_s[0])."&password=ffbsftw");
	foreach($ret as $i) {
		echo "$i\n";
	}
}

$nodes = file_get_contents("http://freifunk-bs.de:8888/nodes.json");

echo "Knoten: ".count(json_decode($nodes,true));

$ret = file_get_contents("http://stuff.tinyhost.de/mess/daten.php?id[]=501&val[]=".count(json_decode($nodes,true))."&password=ffbsftw");
echo "$ret";

$js = json_decode($nodes,true);

$aup = 0;
$exp = 0;
$sta = 0;
$glu = 0;
$wifi = 0;

foreach($js as $node) {
        if(array_key_exists('autoupdates', $node)) {
        	if($node['autoupdates'] == "true") {
        		$aup++;
        	}
        }
	if(array_key_exists('branch',$node)) {
		if($node['branch'] == "stable") {
			$sta++;
		}
		if($node['branch'] == "experimental") {
			$exp++;
		}
	}
	if(array_key_exists('firmware', $node)) {
		if(substr($node['firmware'],0,5) == "gluon") {
			$glu++;
		}
	}
	if(array_key_exists('wifi', $node)) {
		if($node['wifi'] !== NULL) {
			if(array_key_exists('client_count', $node['wifi'])) {
				$wifi += intval($node['wifi']['client_count']);
			}
		}
	}
}
echo "Router mit Auto-Updates: $aup";
echo "Router im Stable-Branch: $sta";
echo "Router im Experimental-Branch: $exp";
echo "Router mit Gluon: $glu";
$ret = file_get_contents("http://stuff.tinyhost.de/mess/daten.php?id[]=502&val[]=".$aup."&password=ffbsftw");
echo "$ret";
$ret = file_get_contents("http://stuff.tinyhost.de/mess/daten.php?id[]=503&val[]=".$sta."&password=ffbsftw");
echo "$ret";
$ret = file_get_contents("http://stuff.tinyhost.de/mess/daten.php?id[]=504&val[]=".$exp."&password=ffbsftw");
echo "$ret";
$ret = file_get_contents("http://stuff.tinyhost.de/mess/daten.php?id[]=505&val[]=".$glu."&password=ffbsftw");
echo "$ret";
echo "Wifi-Clients nach dem Doom-Hack: $wifi";
$ret = file_get_contents("http://stuff.tinyhost.de/mess/daten.php?id[]=506&val[]=".$wifi."&password=ffbsftw");
echo "$ret";
?>
