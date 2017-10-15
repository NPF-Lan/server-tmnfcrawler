<?php
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: text/plain");

set_include_path(get_include_path() . PATH_SEPARATOR . './lib/');
include('tmlogcrawler.class.php');
//include('open-flash-chart-object.php');
//include('php-ofc-library/open-flash-chart.php');

$file = $_GET['log'];


$file = '/var/www/log/'.$file;

if(!preg_match('/(\.txt|\.log)$/',$file) || !is_file($file) || preg_match('/\.\./', $file)){
  die($file. ' is not a file');
}

$tm = new tmLogCrawler();
$tm->crawl($file);

//header('Content-Length: '.filesize($file));

$times = $tm->besttimes();

foreach($times as $level => $playernames){
	$bigarray[$level] = $playernames;
  //printf('Level,"%s",""'."\r\n", str_replace('"','""',$level));
  $c = 1;
  if(is_array($playernames) && count($playernames) > 0){
    foreach($playernames as $player => $time){
    	
      //printf('"%s",%s,%s'."\r\n",str_replace('"','""',$player),$time,($c <= 10) ? current($points) : '""');
    }
    //echo '"","",""'."\r\n";
  }
}

echo serialize($bigarray);

?>
