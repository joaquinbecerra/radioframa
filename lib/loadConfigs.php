<?php
/*Load up all configs stored in db.  The basic model is to load up all the system defaults
 from the dictionary table of configs.  Then load any 'system' level overrides that the admin
 may have made.  Lastly, load up any user preferences and priviledges (set by admin).  Each time we
 overwrite what was previously there.
 
 This could  probably be combined into a union query, but is easier to follow as 3 separate steps.
*/

//First the system defaults
$a=dosql("select name,value from configs");
if($a){
    extract($a);
    foreach($names as $i=>$name){
        j_setConfValue($name,$values[$i]);
    }
}

//Now any system overrides
$a=dosql("select c.name,p.value from configs c, preferences p where p.configID=c.configID and p.userID=-1");
if($a){
    extract($a);
    foreach($names as $i=>$name){
        j_setConfValue($name,$values[$i]);
    }
}

if(defined("UL_UID")|| defined("PASSED_UID")){//Only do this part if there's a user logged in or we got a user ID passed in (like from play.php).
    //Now any user preferences overrides.  Note that not all of these are user editable (priviledges are set by admin).
 	//We are called from a few places (like images.php) with out authenticating the user.
    $uid=(defined("UL_UID"))?UL_UID:PASSED_UID;
    $a=dosql("select c.name,p.value from configs c, preferences p where p.configID=c.configID and p.userID=".$uid);
    if($a){
        extract($a);
        foreach($names as $i=>$name){
            j_setConfValue($name,$values[$i]);
        }
    }
    
    //Do a special check to see if the user has a skin defined that no longer exists.. if so redirect to the default skin.
    $ok=(_conf("skinDir")!="");
    if($ok)$ok=is_dir(_conf("skinDir"));//assume if directory exists, then the proper files are in there...
    if(!$ok)j_setConfValue("skinDir","skins/Default");//change to default.. maybe should reset in prefs table too?
}

if(!(defined("CONF_LOAD_SHORT"))){//Skip these if flagged to do so (like from play.php)
    j_loadConfFile("lang/lang_english.php",true,true);//Load the defaults for all language vars.. It's expected that this is the superset of vars.  The others may not be quite up to date in which case the english will be subbed in.
    j_loadConfFile("lang/"._conf("langFile"),false);//Load the specified language file.
}


//Attempt to determine the site address and path if the conf.php file didn't supply one.
if(_conf("siteAddress")=="")j_setConfValue("siteAddress",get_server());
if(_conf("fullSiteAddress")=="")j_setConfValue("fullSiteAddress",j_appendToList(_conf("siteAddress"),get_root_path(),"/"));
//var_dump(_conf("fullSiteAddress"));exit;

function get_server() {//returns the servername or ip address including any alt port if defined.  This mostly goes off whatever the client requested and so should generally be cool to use.
	$protocol = 'http';
	if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') {
		$protocol = 'https';
	}
        
        //get host from passed host name or the servername vars.
        $host="";
	if(isset($_SERVER['HTTP_HOST']))$host=$_SERVER['HTTP_HOST'];
	$host=($host)?$host:$_SERVER['SERVER_NAME'];
        
        //strip off any trailing / that some browsers add on.
        if (substr($host, -1)=='/') {
		$host = substr($host, 0, strlen($host)-1);
	}
        
        //see if alt port is included already.  It seems that http_host includes and server_name does not, but google told me this is not always true..
        if(strpos($host,":")===false){
            if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']!=80 && $_SERVER['SERVER_PORT']!=443)$host.=":".$_SERVER['SERVER_PORT'];    
        }
        $baseUrl = $protocol . '://' . $host;
        
	return $baseUrl;
}
function get_root_path(){//returns any directory path to our application root.
	$path="";
	$self=explode("/",$_SERVER['PHP_SELF'],-1);//fetch out all but the file
	foreach($self as $t){
		$path=j_appendToList($path,$t,"/");
	}
	return $path;
}
?>
