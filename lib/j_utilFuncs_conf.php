<?php
/*Load a conf file and provide means of fetching values.
    Uses same syntax as php.ini (";" is comment)
	value pairs are like:  
user = john
pass = myfoot


Conf Files should start with:
;<?php exit();?>

so they aren't web readable.
*/
$j_confVariables=array();
$j_confVariables_master=array();//This holds the master (english) list.  We refer to this if the supplemental lang file is hosed up or incomplete.
function j_loadConfFile($file,$exitOnError=true,$markAsMasterList=false){//Returns number of variables read, 0 if none.  you can mark this file as the master superset.
    global $j_confVariables,$j_confVariables_master;
    if(is_file($file)){
//	if(_conf("php-locale")!=""){//set the charset locale if needed.
	//    //echo $file;
	  //  log_message("Using alternate php-locale of '"._conf("php-locale")."'",2);
	    //setlocale(LC_CTYPE, _conf("php-locale"));	
	//}	
        $tArray=parse_ini_file($file);
	//if($file=="lang/lang_francais.php"){var_dump($tArray);exit;}
	if($tArray){
	    $j_confVariables=array_merge($j_confVariables,$tArray);
	    if($markAsMasterList)$j_confVariables_master=$j_confVariables;  
	}else{
	    echo "error parsing file: $file.";
	    if($exitOnError)exit;
	}
    }else{
        echo "Error in ".$_SERVER['SCRIPT_NAME']."(".$_SERVER['SCRIPT_FILENAME'].").  Conf File '$file' does not exist in this directory!";
        if($exitOnError)exit;
    }
    return count($j_confVariables);
}
function _conf($key,$getFromMasterList=false){/*Returns value for key.  If none defined, returns
                      empty string.  If $getFromMasterList is passed true, then we fetch out of the master list (if defined)*/
    $ret="";
    global $j_confVariables,$j_confVariables_master;
    if($getFromMasterList & count($j_confVariables_master)>0){
	if(array_key_exists($key,$j_confVariables_master)){
	    $ret=$j_confVariables_master[$key];
	}
    }else{
	if(array_key_exists($key,$j_confVariables)){
	    $ret=$j_confVariables[$key];
	}
    }
    return $ret;
}
function j_setConfValue($key,$val){
    global $j_confVariables;
    $j_confVariables[$key]=$val;
}
?>
