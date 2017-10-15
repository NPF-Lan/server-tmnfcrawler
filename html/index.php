<?php
ini_set('display_errors', 1);
define('log_subfolder','npf2017');
define('log_folder', '/var/www/log/');

if(!isset($_GET['r'])){
  $runder = glob('/var/www/log/*/*', GLOB_ONLYDIR);
  if(count($runder) > 0 ){
    foreach($runder as $runde){
      $runde = substr($runde, 13);
      echo "<a href=\"?r=".$runde."\">Se ".$runde."</a><br>";
    }
  }
  else{
    echo "Ingen runder tilgængelige..";
  }
die();
}

// Vælg den runde der skal hentes fra serveren
$r = $_GET['r'];;


// Glob de logfiler der er på serveren for den runde - hvis der er flere server filer så bliver det hele kastet sammen
$logfiler = glob('/var/www/log/'.$r.'/*');

$arrays = array();
$players = array();


// Lars løkke alle filerne fra globben og hent den fra det script der ligger i /var/www/log/index.php
// Det er den gamle crawler - det er i virkeligheden bare en stor gang Regex
foreach($logfiler as $logfil){
	if(basename($logfil) == "upload.log"){
	$array2 = array();
	// Bliver leveret serialized
//	var_dump(file_get_contents('http://'.$_SERVER['SERVER_ADDR'].'/log/?log='.$r.'/'.basename($logfil)));

$url = 'http://'.$_SERVER['SERVER_ADDR'].'/log.php?log='.str_replace(log_folder,'',$logfil);

var_dump($url);

	$array = unserialize(
file_get_contents($url));


		
	unset($array['OPVARMNING.Challenge'],$array['RUNDE SLUT.Challenge']);
	if(!is_array($array)){
    $array = array();
  }
	foreach($array as $map => $playerarray){
		$key = substr($map, 9);

// Vi skal ikke tælle opvarming eller runde slut med
		if(!preg_match('/(OPVARMNING|RUNDE SLUT)/', $key)){
			// Her kaster vi det hele sammen
			if(isset($arrays[$key])){
				$arrays[$key] = array_merge($arrays[$key], $playerarray);
			}
			else{
				$arrays[$key] = $playerarray;
			}
			// Vi laver en liste over alle de players der er
			// Så vi kan løkke igennem den for at finde folks der ikke har færdiggjort banen
			foreach($playerarray as $player => $time){
				$players[] = $player;
			}
		}
	}
	
	$players = array_unique($players);
	

	
	//$arrays = array_merge($arrays, $array2);
}
}



$debug = array();
foreach($arrays as $map => $playerlist){
	$debug[$map] = array();
	$unaccountedplayers = array_flip($players);
	foreach($playerlist as $player => $time){
		unset($unaccountedplayers[$player]);
		$debug[$map][$player] = tmntimetofloat($time);
	}
	
	foreach($unaccountedplayers as $player => $wakka){
		$arrays[$map][$player] = 'dnf';
	}
}

// Har ikke brug for dem her
unset($player,$wakka);


// Sorter spillerne efter den laveste tid
foreach($arrays as $map => &$playerlist){
	uasort($playerlist, 'findvinder');
}

// Lad os lige rydde op i det her
unset($map,$playerlist);

function tmntimetofloat($tmntime){
if(is_float($tmntime)){
	return $tmntime;
}
	$return = (float)0;
	list($min,$sec,$msec) = preg_split('#[:\.]+#', $tmntime);
	
	//var_dump($tmntime, $wakka);
	
	$return += (float)$min*60;
	$return += (float)$sec;
	$return += (float)$msec/100;
	
	//var_dump($return);
	return $return;
}

function findvinder($a, $b){
	//var_dump('ab: '.$a.', '.$b);
	if($a == $b){
		return 0;
	}

	if($a == 'dnf'){
		$a = (float)99999999999;
		$b = tmntimetofloat($b);
	}
	elseif($b == 'dnf'){
		$b = (float)99999999999;
		$a = tmntimetofloat($a);
	}
	else{
		$a = tmntimetofloat($a);
		$b = tmntimetofloat($b);
	}

	/*if($a == 'dnf'){
		//var_dump($a.' < dnf ('.$b.')');
		return 1;
	}
	if($b == 'dnf'){
		return -1;
	}*/

	if($a < $b){
		//var_dump($a.' < '.$b);
		return -1;
	}
	else{
		return 1;
	}
}


$bigarray = serialize($arrays);

$total = array();
/*foreach($arrays as $map => $playerlist){
	$total[$map] = array();
	$i = 1;
	
	foreach($playerlist as $player => $time){
		if(isset($total[$map][$player])){
			$total[$map][$player] += $i;
		}
		else{
			$total[$map][$player] = $i;
		}
		
		if($time == 'dnf'){
			$total[$map][$player] = count($playerlist);
		}
		$i = $i + 1;
		unset($time,$player);
	}
}*/

//print_r($arrays);

/*foreach($arrays as $map => $playerlist){
	$total[$map] = array();
	$i = 1;
	//reset($playerlist);
	//uasort($playerlist, 'findvinder');
	//reset($playerlist);
	$player = current($playerlist);
	do{
		$total[$map][key($playerlist)] = $i;
		if($player == 'dnf'){
			$total[$map][key($playerlist)] = count($playerlist);
		}
		next($playerlist);
		++$i;
	}while($player = current($playerlist));
	unset($playerlist);
}*/
unset($map,$playerlist);
foreach($arrays as $map => $playerlist){
	$total[$map] = array();
	$i = 1;
	
	foreach($playerlist as $player => $time){
		if($time == 'dnf'){
			$total[$map][$player] = count($playerlist);
		}
		else{
			$total[$map][$player] = $i;
		}
		$i++;
	}
}


//print_r($arrays);

$grandtotal = array();

$playerpoints = array();

//$bigarray = serialize($arrays);

foreach($total as $map => $playerlist){
	foreach($playerlist as $player => $points){
		if(isset($playerpoints[$player])){
			$points += $playerpoints[$player];
		}
		//$times = array_flip($arrays[$map]);

		$grandtotal[$player] = $points;
		$playerpoints[$player] = $points;
	}
}

asort($grandtotal);

$total['grandtotal'] = $grandtotal;

$arrUserScores = array();
$arrTracks = array();

/*foreach($total as $strTrack => $arrPlayers){
	
}*/

//print_r($total);

foreach($total as $strTrack=>$arrPlayers){
  foreach($total['grandtotal'] as $strName=>$strScore){
    $arrTracks[$strTrack] = $strTrack;
    if($strTrack == "grandtotal"){
      $arrUserScores[$strName]['score'] = $strScore;
    }
    else{
      $arrUserScores[$strName]['tracks'][$strTrack]['score'] = $arrPlayers[$strName];
    }
  }
}

$myarray = unserialize($bigarray);

foreach($total as $strTrack=>$arrPlayers){
	if($strTrack != 'grandtotal'){
	  foreach($arrPlayers as $strName=>$strTime){
	    $arrUserScores[$strName]['tracks'][$strTrack]['time'] = $arrays[$strTrack][$strName];
	  }
	}
}
unset($arrTracks['grandtotal']);
//print_r($arrUserScores);
$color1 = "white";
$color2 = "#DAE7E7";

$redcolor1 = "#FFE0DD";
$redcolor2 = "#FFC6BF";

$color = $color1;
?>
<table style="cell-spacing:0.2em;border-spacing:0px;margin-left:auto;margin-right:auto;border:1px solid black;">
  <tr style="background-color:#5C8A8A;color:white;font-weight:bold;">
    <td style="text-align:center;width: 85px;border-bottom:2px solid white;">Score</td>
    <td style="text-align:center;width: 215px;border-bottom:2px solid white;">Name</td>
<?php
  foreach($arrTracks as $track){
  $track = str_replace('.Challenge', '', $track);
  ?>
    <td style="text-align:center;width: 250px;border-bottom:2px solid white;border-left:#99A2A2 2px dashed;" colspan="2"><?=$track;?></td><?php
  }
?>
  </tr>
<?php
foreach($arrUserScores as $strPlayer=>$arrPlayer){
$color = ($color1 == $color)?$color2:$color1;
$redcolor = ($color1 == $color)?$redcolor1:$redcolor2;

?>
  <tr style="background-color:<?=$color;?>;">
    <td style="text-align:center;"><?=$arrPlayer['score'];?></td>
    <td style="font-weight:bold;"><?=$strPlayer;?></td>
<?php
  foreach($arrTracks as $track){
  	if($arrPlayer['tracks'][$track]['time'] == 'dnf'){
  		?>
    <td style="background-color: <?=$redcolor;?>;padding: 0.3em;padding-left:0; border-spacing-left: 0;font-weight:bold;text-align:center;border-left:#99A2A2 2px dashed;width: 60px;"><?=$arrPlayer['tracks'][$track]['score'];?> pts.</td>
    <td style="background-color: <?=$redcolor;?>;padding: 0.3em;font-style:italic;text-align:center;width: 60px;"><?=strtoupper($arrPlayer['tracks'][$track]['time']);?></td><?php
  	}
  	else{
  ?>
    <td style="padding: 0.3em;padding-left:0; border-spacing-left: 0;font-weight:bold;text-align:center;border-left:#99A2A2 2px dashed;width: 60px;"><?=$arrPlayer['tracks'][$track]['score'];?> pts.</td>
    <td style="padding: 0.3em;font-weight:bold;text-align:center;width: 60px;"><?=$arrPlayer['tracks'][$track]['time'];?></td><?php
  }}
?>
  </tr>
<?php
}

?>
</table>
<pre>
<?php

/*print_r($arrays);
echo "<hr>";
print_r($total);
echo "<hr>";
print_r($arrUserScores);*/
?>
