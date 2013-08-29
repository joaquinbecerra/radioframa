<?php
include_once("j_utilFuncs_dates.php");
include_once("j_utilFuncs_strings.php");
include_once("j_utilFuncs_html.php");
include_once("j_utilFuncs_conf.php");
//include_once("j_timer.php");
$ajax_printProgress=true;//default to printing of any progress statements generated for the ajax stuff... callers can set this false if there's gonna be a butload of progress statements.
function j_writeFile($filename,$content,$mode='w'){//wrapper to write a file to os.
    if($handle = fopen($filename, $mode)){
        if (fwrite($handle, $content)!==false){
            return true;
        }else echo "couldn't write to $filename";
    }else echo "couldn't open $filename";
   
    return false;
}
function ajax_progress($str){/*Outputs the passed str so that will get displayed as a progress statement by the ajax stuff.. and stripped out when the ajax stuff is finished loading it.*/
	global $ajax_printProgress;
	if($ajax_printProgress){
		echo "<j_progress>$str</j_progress>";
		flush();
	}
}

$debugStr=array();//declared globally.
function dbg($str,$debugLevel=1,$calledFrom=""){//general debug handler.
    /*Collects any debug mssgs sent to it grouping by $debugLevel.
     Output can be retrieved below in dbgOut();*/
    global $debugStr;
    if($calledFrom)$str="($calledFrom) $str";
    $debugStr[$debugLevel].=$str.CR;

}
function dbgOut($debugLevel=1,$andLower=true){//returns any collected debug strings.
    global $debugStr;
    if($andLower){//print out lower levels too.
        for($x=1;$x<=$debugLevel;$x++){
            if($debugStr[$x])$str.=CR."Debug Level $x".CR."######".CR."-".$debugStr[$x];
        }
    }else $str=$debugStr[$debugLevel];
return $str;
}
function get_j_ID($id){/*returns text containing a properly formatted id which can be parsed out by
                        parse_j_ID(javascript function in j_ajax.js)*/
    return "<!--<j_ID>$id</j_ID>-->";
}

function log_message($message,$debugLevel=1){//
    if(_conf("error_log")){
	if(_conf("log_level")>=$debugLevel){
	    //rotate logs if needed.  Hopefully this doesn't get us in trouble with multiple writers... but worst case seems to be some of the archives get
		//hosed up.  We could add a semaphore if we think this is a problem, but that's too complicated for now.
	    $log=_conf("error_log");
	    if(file_exists($log)){
		if(filesize($log)> 100*1024){//arbitrarily rotate after 100K
			if(file_exists("$log.5"))unlink("$log.5");
			if(file_exists("$log.4"))rename("$log.4","$log.5");
			if(file_exists("$log.3"))rename("$log.3","$log.4");
			if(file_exists("$log.2"))rename("$log.2","$log.3");
			if(file_exists("$log.1"))rename("$log.1","$log.2");
			rename($log,"$log.1");
		}
	    }

	    //Now write out the log
	    $space="";
	    for($i=1;$i<=$debugLevel;$i++){$space.=" ";}
	    error_log(date("D M j G:i:s T Y")."$space - ($debugLevel) ".$message."\n",3,$log);
	}
    }
}

?>
