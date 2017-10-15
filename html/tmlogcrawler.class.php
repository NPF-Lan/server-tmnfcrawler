<?php
/***************

Lavet til NPF #11 - 2k10 Alt credit forbeholdt til mig XD

Tak for et fedt LAN - som altid

*/
class tmLogCrawler{
  var $times = array();
  var $realtimes = array();
  
  // NmB: denne gang til NPF igen XD lad mig se om jeg kan lade være med at smide det væk...
  function tmLogCrawler(){
    
  }
  
  public function stripColors($input, $for_tm = true) {

	return
		//Replace all occurrences of a null character back with a pair of dollar
		//signs for displaying in TM, or a single dollar for log messages etc.
		str_replace("\0", ($for_tm ? '$$' : '$'),
			//Replace links (introduced in TMU)
			preg_replace(
				'/
				#Strip TMF H, L & P links by stripping everything between each square
				#bracket pair until another $H, $L or $P sequence (or EoS) is found;
				#this allows a $H to close a $L and vice versa, as does the game
				\\$[hlp](.*?)(?:\\[.*?\\](.*?))*(?:\\$[hlp]|$)
				/ixu',
				//Keep the first and third capturing groups if present
				'$1$2',
				//Replace various patterns beginning with an unescaped dollar
				preg_replace(
					'/
					#Match a single dollar sign and any of the following:
					\\$
					(?:
						#Strip color codes by matching any hexadecimal character and
						#any other two characters following it (except $)
						[0-9a-f][^$][^$]
						#Strip any incomplete color codes by matching any hexadecimal
						#character followed by another character (except $)
						|[0-9a-f][^$]
						#Strip any single style code (including an invisible UTF8 char)
						#that is not an H, L or P link or a bracket ($[ and $])
						|[^][hlp]
						#Strip the dollar sign if it is followed by [ or ], but do not
						#strip the brackets themselves
						|(?=[][])
						#Strip the dollar sign if it is at the end of the string
						|$
					)
					#Ignore alphabet case, ignore whitespace in pattern & use UTF-8 mode
					/ixu',
					//Replace any matches with nothing (i.e. strip matches)
					'',
					//Replace all occurrences of dollar sign pairs with a null character
					str_replace('$$', "\0", $input)
				)
			)
		)
	;
}  // stripColors
  
  // Belly of the beast - Contains the regex - foreach line in the log, applies the regex
  public function crawl($logfile){
    $fp    = fopen($logfile, 'r');
    
    $timeregex  = '/<time>\s\[([^\(]*)\s\(([^\)]*)\)\]\s((\d{0,2}):(\d{1,2})\.(\d+))/'; 
    //$levelregex = '/(\d{2}:\d{2}:\d{2})\]\sLoading\schallenge\s([\w\d\W\D.\s\-_`´#]*)\.Gbx\s\((\w{27})\)\.\.\./'; 
    $levelregex = '/(\d{2}:\d{2}:\d{2})\]\sLoading\s(challenge|map)\s(.*)\.[GgBbXx]{3}/';
    
    while(!feof($fp)){
      $line = fgets($fp, 10000);
      if(preg_match_all($timeregex, $line, $matches)){
      	//var_dump($matches);
        $this->addtime($matches,$level);
      }
      if(preg_match_all($levelregex, str_replace('%','$',$line), $matches)){
        //var_dump($matches);
        //$this->addtime($matches);
        $level = $this->stripColors($matches[1][0].' '.$matches[3][0],false);
      }
      
      //var_dump($line);
    }
    
    fclose($fp);
  }
  
  // picks out the index 0 time, and sorts the array
  public function besttimes(){
    $times = $this->times;
    
    //var_dump($times);
    
    foreach($times as $level => $levelset){
      foreach($levelset as $playername => $timeset){
        $times[$level][$playername] = $times[$level][$playername][0];
        $times[$level][$playername] = current($this->realtimes[$level][$playername]); //current(array_keys($this->realtimes[$level][$playername]));
        //var_dump($this->realtimes[$level][$playername]);
      }
      asort($times[$level]);
    }
    
    //var_dump($times);
    
    //var_dump($times, $this->realtimes);
    
    //usort($times,array('tmLogCrawler','sortplayertimes'));
    
    return $times;
  }
  
  // Adds their time to the time array, for later handling
  public function addtime($array,$level){
    $times = &$this->times;
    
    //var_dump($times);
    
    $times[$level][$array[1][0]][] = floatval(((float)$array[4][0]*60)+(float)$array[5][0]+(float)($array[6][0]/100));
  	$this->realtimes[$level][$array[1][0]][] = $array[3][0];
    
    //usort($times[$level][$array[1][0]], array('tmLogCrawler', 'sorttime'));
    
    //$times
    
    //echo $times;
    //var_dump($array);
    
    asort($this->realtimes[$level][$array[1][0]]);
    asort($times[$level][$array[1][0]]);
  }
}

?>
