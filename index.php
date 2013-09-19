<?php
require_once("lib/dbLogin.php");
$doWhatOverride="";

//echo "user: "._conf("username")." db:"._conf("defaultDB");
//Verify that the users table has been created.. if not then send to dbUpdate for initial install.
//We'll just do this on the initial page load to reduce server queries.  Even if someone trys to be
//sneaky, they'll just get any error if the users table doesn't yet exist anyway so there shouldn't be
//any harm in this assumption.
if(empty($_REQUEST['doWhat'])){ ### in l: $_REQUEST['doWhat']==""ieu of 
    if(dosql("SHOW TABLES FROM "._conf("defaultDB")." LIKE 'users'",0)==false){
        $currDbVersion=0;
        require_once("lib/dbUpdate.php");
    }
}

//Do the user login (unless this is a demo system, then we'll bypass)
if(_conf("isDemoSystem")){
    define(UL_UID,2);
    define(UL_ISADMIN,0);
    $noPlay="Sorry, you can't play any songs in demo mode";
}elseif(_conf("autoLoginUser") && is_numeric(_conf("autoLoginUser"))){//Preset user to bypass logins
    define(UL_UID,_conf("autoLoginUser"));
    define(UL_ISADMIN,0);
    define(UL_HIDE_PREFS,1);
}else{
	require_once("lib/UL_userLogin.php");UL_checkAuth(_conf("defaultDB"));
	//load all configs, both system and user overrides.
}

require_once("lib/loadConfigs.php");
//Check to see if updates are needed (only when admin logs in though).  These would be any subsequent updates  after the initial handled above.
//Same inital page filter as above.
$currDbVersion=_conf("dbVerNum");
if(UL_ISADMIN && $_REQUEST['doWhat']==""){
    //$currDbVersion=_conf("dbVerNum");
    if($currDbVersion=="")$currDbVersion=0;//This may never actually happen.
    require_once("lib/dbUpdate.php");
    
	//Do some date math for logic below...
    $n=getdate();
    $today=$n['yday'];
    if(_conf("catalogLastUpdatedDate") && _conf("catalogLastUpdatedDate")>0){
        $n=getdate(_conf("catalogLastUpdatedDate"));
        $lastUpdatedDay=$n['yday'];
    }else $lastUpdatedDay=-1;

    $lastOptDate=(_conf("lastOptimizeDate"))?_conf("lastOptimizeDate"):0;

    if(_conf("mainCatalogFilePath")==""){//if this is a new install, send right to the syst configs screen
	//Actually this doesn't work.  The whole layout isn't set up yet, and so can't goto the admin page.. needs to happen thru js.  I'll leave this in (because it should skip below anyway) for later work.  Probably can set up a setTimeout in layout/head_include.php to fire an admin click event.
	$doWhatOverride="admin";
    }else{//Check to see if there are any backgrounded update jobs todo
	    //Note that only 1 of these should run at any given time if they are backgrounded processes.
	    // all quotes should be escaped doubles (thing that actually calls this (in layout/head_include.php) wraps in '').
	    // and no trailing semi-colon (it's passed to setTimeout).
	
	    $dbUpdate_queuedJob="";
	    //Check to see if we are to do the one time image pre-cache.
	    if(_conf("autoUpdateImgCaches")==1){
	        $dbUpdate_queuedJob="prog_startActionWithProgressBar(\"doWhat=cacheAlbumArt\",\"\")";    
	    }elseif(_conf("autoOptimizeTables") && ((time()-$lastOptDate)> (86000*30)) ){//once a month or so
	        $dbUpdate_queuedJob="prog_startActionWithProgressBar(\"doWhat=optimizeTables\",\"\")";
	    }elseif(_conf("autoUpdateCatalog") && ($today>$lastUpdatedDay) ){//this should be last as it's run every day and so we'll skip it when any of the above are queued up.  Also note this won't run on jan 1..  hangover anyway.
	            $dbUpdate_queuedJob="prog_startActionWithProgressBar(\"doWhat=updateCatalog\",\"\")";
	    }
    }
}

//If the currDbVersion is still 0, then bail with error.
if($currDbVersion==0){echo "Sorry, this database has not been configured yet.";exit;}

require_once("lib/content_functions.php");



/*This is the main switchboard for the site.  All calls come thru here and are dispatched to whatever function needs
to handle it.  Any returned $html is then sent back to the browser.  This could be the initial page or an ajax call.
Anything returned is fully formed HTML, although it may just be a snippet like a table or select box.
*/
//Pull out and clean/escape any request vars we're interested in...

### Alain init values, then isset, and 'filter' reserved word in Ajax replaced by 'fltering'
$doWhat="";
$type="";
$filtering="";
$id="";
$maxDivHeight="";
$passedUID="";
$authKey="";
$first="";
$playlistID="";
$msg='';

if(isset($_REQUEST['doWhat'])) $doWhat=scrubTextIn($_REQUEST['doWhat'],1);//Strict 'word' only filter
if(isset($_REQUEST['type'])) $type=scrubTextIn($_REQUEST['type'],1);
### $filter=scrubTextIn($_REQUEST['filter'],1);
if(isset($_REQUEST['filtering'])) $filter=scrubTextIn($_REQUEST['filtering'],1);
if(isset($_REQUEST['id'])) $id=scrubTextIn($_REQUEST['id'],1);
if(isset($_REQUEST['maxDiHeight'])) $maxDivHeight=scrubTextIn($_REQUEST['maxDivHeight'],1);
if(isset($_REQUEST['uid'])) $passedUID=scrubTextIn($_REQUEST['uid'],2);//need to be able to pass minus sign..
if(isset($_REQUEST['authKey'])) $authKey=scrubTextIn($_REQUEST['authKey'],1);
if(isset($_REQUEST['first'])) $first=scrubTextIn($_REQUEST['first'],1);
if(isset($_REQUEST['playlistID'])) $playlistID=scrubTextIn($_REQUEST['playlistID'],1);
if(isset($_REQUEST['chattime'])) $chattime=scrubTextIn($_REQUEST['chattime'],1);
if(isset($_REQUEST['msg'])) $msg=mysql_real_escape_string ($_REQUEST['msg']);

### fin Alain
$playListType="";


define ("BROWSER_TYPE","normal");//default to 'normal' browser.. we can add sm screen support in the future.
//var_dump($doWhat);
switch ($doWhat){
    case ""://default case on initial load.. send out the header,footer and default content
        $html=getDefaultContent();
        //if(BROWSER_TYPE=="normal")include("layout/"._conf("normalLayoutTemplate"));
	include(_conf("skinDir")."/layout_template.php");
        break;
    case "home":
        echo getDefaultContent();
        break;
    case "nowPlaying":
        echo getNowPlaying($id);
        break;
    case "rf_nowPlaying":
        echo rf_getNowPlaying($id);
        break;
    case "rf_updatenowPlaying":
        echo rf_updateNowPlaying($id);
        break;
    case "getRandomBrowsingTabObj":
	echo getRandomBrowsingTabObj();
	break;
    case "doSearch":
        $searchVal=scrubTextIn($_REQUEST['search_val'],2);//Loose filtering.
        $searchType=scrubTextIn($_REQUEST['search_type'],1);
        echo doSearch($searchVal,$searchType);
        break;
    case "rf_doSearch":
        $searchVal=scrubTextIn($_REQUEST['search_val'],2);//Loose filtering.
        echo rf_doSearch($searchVal);
        break;
/*Browsing Stuff*/
    case "getRandomList";
        echo getRandomList($type);
        break;
    case "getLetterSelects":
        echo getLetterSelects($type,$id);
        break;
    case "getBrowsePage":
        $selectedID=($id)?$id:"";
        if($filter=="" && $selectedID==""){
            $filter="A";

            if((_conf("randomBrowse")!="") && ($type=="artist" || $type=="album" || $type=="genre")){// browse mode (random or last selected
		if(_conf("randomBrowse")){//do random
	                $name=($type=="genre")?"description":"name";
        	        $table=$type."s";//Only works because the 3 currently supported types (artist,album,genre) follow this table syntax
                	$idCol=$type."ID";//ditto.

	                //$selectedID=dosql("select $idCol from $table order by rand() limit 1",0);
			//That was slow and not very random, so we'll try this for a bit.
			$offset=dosql("select floor(rand() * count(*)) from $table",0);
			$selectedID=dosql("select $idCol from $table limit $offset,1",0);
		}else{//last selected
        		$id=getLastSelectedBrowseItem($type);//returns false if not set yet.
                        //var_dump($id);exit;
			if($id)$selectedID=$id;
                        else{
                            //First time thru pick a random one
                            $selectedID=dosql("select min(".$type."ID) from ".$type."s",0);
                        }
		}
            }

        }
        echo getBrowsePage($type,$filter,$selectedID);
        break;
    case "getAlbumListForArtist":
        echo getAlbumListForArtist($id,$maxDivHeight);
        break;
    case "getAlbumDetail":
        echo getAlbumDetail($id);
        break;
    case "getAlbumsForGenre":
        echo getAlbumListForGenre($id,$maxDivHeight);
        break;
    case "showAlbumSongs":
        echo showAlbumSongs($id);
        break;
    case "getAlbumArtSearchHTML":
        echo getCoverArtSearchLinks(scrubTextIn($_REQUEST['albArtSearch_mode']),scrubTextIn($_REQUEST['albArtSearch_artName']),scrubTextIn($_REQUEST['albArtSearch_albName']));
        break;
    case "getAlbumArtImportHTML":
        echo getAlbumArtImportHTML($id);    
        break;
    case "submitAlbumArtURL":
        echo submitAlbumArtURL($id,scrubTextIn($_REQUEST['albumArtURL'],2));
        break;
    
    case "getPlaylistDetail":
        require_once("lib/playlist_functions.php");
        echo getPlaylistDetail($id);
        break;
    case "rf_getPlaylistDetail":
        require_once("lib/playlist_functions.php");
       // header('Content-Type: application/json');  
        echo rf_getPlaylistDetail();
        break;
    case "rf_getPlaylistAdmin":
        require_once("lib/playlist_functions.php");
       // header('Content-Type: application/json');  
        echo rf_getPlaylistAdmin();
        break;
    case "rf_upPlaylistItem":
        require_once("lib/playlist_functions.php");
       // header('Content-Type: application/json');  
        echo rf_upPlaylistItem($id);
        break;
    case "rf_downPlaylistItem":
        require_once("lib/playlist_functions.php");
       // header('Content-Type: application/json');  
        echo rf_downPlaylistItem($id);
        break;
    case "rf_sendMessages":
        
        echo rf_sendMessages($msg);
        break;
    case "rf_getMessages":
        
        echo rf_getMessages($id);
        break;
    case "getDivContent":
        echo $_REQUEST['content'];
        break;
    
    
/*Admin*/
    case "optimizeTables":
        if(UL_ISADMIN){
            require_once("lib/admin_functions.php");
            optimizeTables();
### delete orphans (can also be called from inside function optimizeTables() or a new 'doWhat'
			require_once("lib/catalogMaint.php");
			cat_cleanUpOrphans(true);
        }
        break;
    case "admin":
        if(UL_ISADMIN){
            require_once("lib/admin_functions.php");
            echo getAdminPage();
        }
        break;
    case "storeAllAblumArt":
	if(UL_ISADMIN){
            require_once("lib/image_funcs.php");
	    echo storeAllAlbumArt();
	}
	break;
    case "admin_catalog":
        if(UL_ISADMIN){
            require_once("lib/admin_functions.php");
            echo getCatalogPage();
        }
        break;
    case "admin_users":
        if(UL_ISADMIN){
            require_once("lib/admin_functions.php");
            echo getAdminUsersPage();
        }
        break;
    case "admin_privs":
        if(UL_ISADMIN){
            require_once("lib/admin_functions.php");
            echo admin_getUserPrivTab();
            //echo editConfigs(1,$id);
        }
        break;
    case "admin_a_user_privs":
        if(UL_ISADMIN){
            require_once("lib/admin_functions.php");
            echo editConfigs(1,$id);
        }
        break;
    case "updateCatalog":
	if(UL_ISADMIN){
		require_once("lib/catalogMaint.php");
		log_message("beginning catalog update");
		echo cat_doSync();
                log_message("Ending catalog update");
	}
	break;
    
    case "deleteOrphans":
	if(UL_ISADMIN){
	    require_once("lib/catalogMaint.php");
	    log_message("beginning orphan search");
	    echo cat_cleanUpOrphans(true);
	    log_message("Ending orphan search");
	    
	}
	break;
   case "updateCatalogAll":
        if(UL_ISADMIN){
                require_once("lib/catalogMaint.php");
                log_message("beginning catalog update");
                echo cat_doSync(true);
                log_message("Ending catalog update");
        }
        break;

    case "getAdminStats":
        if(UL_ISADMIN){
            require_once("lib/admin_functions.php");
            echo getAdminStats();
        }
        break;
   case "clearDB":
	if(UL_ISADMIN){
		require_once("lib/catalogMaint.php");
		cat_clearDB();
		echo "All Gone :)";
	}
	break;
    case "editSystemConfigs":
        if(UL_ISADMIN){
            require_once("lib/admin_functions.php");
            echo editConfigs(0);
        }
        break;
    case "getVersionUpdateStatus":
        if(UL_ISADMIN){
            require_once("lib/admin_functions.php");
            echo getVersionUpdateInfo();
        }
        break;
    case "updateConfig":
        //Note we do not require admin rights here (because it may be user prefs), although if config is a system config, we will check for admin later
        require_once("lib/admin_functions.php");
	
        echo updateConfig($id,scrubTextIn($_REQUEST['value'],2),$passedUID);//note we do a 'looser' scrubing, allowing punctuation.  Not sure if this is a good idea, but we need to be allowed to accept filenames and such.  All text is excaped before being inserted, so -should- be safe.
        break;    
    
    case "editPreferences":
        require_once("lib/admin_functions.php");
        echo "<div class='prefWrapper'>".editConfigs(2)."</div>";
        break;
    case "editPreferenceDefaults":
	if(UL_ISADMIN){
	        require_once("lib/admin_functions.php");
	        echo "<div class='prefWrapper'>".editConfigs(2,-1)."</div>";
	}
        break;
    
    case "editPrivileges"://Not yet implemented.. this will need to be able to select a user to apply to...
        require_once("lib/admin_functions.php");
        echo "<div class='prefWrapper'>".editConfigs(1)."</div>";
        break;
    case "devAdmin":
        if(UL_ISADMIN && _conf("showDevAdmin")==1){
	        require_once("lib/admin_functions.php");
	        echo admin_getDevAdminPage();
	}
        break;
    case "devAdmin_genConfigInsert":
        if(UL_ISADMIN && _conf("showDevAdmin")==1){
	        require_once("lib/admin_functions.php");
	        echo devAdmin_genConfigInsert();
	}
        break;
    case "devAdmin_insertConfig":
        if(UL_ISADMIN && _conf("showDevAdmin")==1){
	        require_once("lib/admin_functions.php");
	        echo devAdmin_insertConfig();
	}
        break;
    case "admin_showLogs":
	if(UL_ISADMIN){
	        require_once("lib/admin_functions.php");
	        echo admin_showLogs();
	}
        break;
/*Play stuff*/
//sendPlaylist($songs=array(),$albumID="",$playlistID="",$artistID="",$genreID="",$fileType="m3u")
    case "playAlbum":
	if(!(_conf("isDemoSystem"))){
        	require_once("lib/playlist_functions.php");
		if(!$playListType)$playListType=(isset($_REQUEST['playListType']))?scrubTextIn($_REQUEST['playListType']):_conf("playListType");
        	sendPlaylist(array(),$id,"","","",$playListType);
	}else echo $noPlay;
        break;
    case "playPlaylist":
	if(!(_conf("isDemoSystem"))){
	        require_once("lib/playlist_functions.php");
		if(!$playListType)$playListType=(isset($_REQUEST['playListType']))?scrubTextIn($_REQUEST['playListType']):_conf("playListType");
        	sendPlaylist(array(),"",$id,"","",$playListType,true);
        	//sendPlaylist(array(),"",$id);
	}else echo $noPlay;
        break;
    case "playPlaylistInOrder":
	if(!(_conf("isDemoSystem"))){
	        require_once("lib/playlist_functions.php");
		if(!$playListType)$playListType=(isset($_REQUEST['playListType']))?scrubTextIn($_REQUEST['playListType']):_conf("playListType");
        	sendPlaylist(array(),"",$id,"","",$playListType,false);
        	//sendPlaylist(array(),"",$id);
	}else echo $noPlay;
        break;
    case "playArtist":
	if(!(_conf("isDemoSystem"))){
	        require_once("lib/playlist_functions.php");
                if(!$playListType)$playListType=(isset($_REQUEST['playListType']))?scrubTextIn($_REQUEST['playListType']):_conf("playListType");
        	sendPlaylist(array(),"","",$id,"",$playListType);
		//sendPlaylist(array(),"","",$id);
	}else echo $noPlay;
        break;
    case "playGenre":
	if(!(_conf("isDemoSystem"))){
	        require_once("lib/playlist_functions.php");
		if(!$playListType)$playListType=(isset($_REQUEST['playListType']))?scrubTextIn($_REQUEST['playListType']):_conf("playListType");
        	sendPlaylist(array(),"","","",$id,$playListType);
        	//sendPlaylist(array(),"","","",$id);
	}else echo $noPlay;
        break;
    
    case "playSong":
	if(!(_conf("isDemoSystem"))){
	        require_once("lib/playlist_functions.php");
        	if(!$playListType)$playListType=(isset($_REQUEST['playListType']))?scrubTextIn($_REQUEST['playListType']):_conf("playListType");
        	sendPlaylist(array($id),"","","","",$playListType);
		//sendPlaylist(array($id));
	}else echo $noPlay;
        break;
    case "openFlashPlayer":
		if(!(_conf("isDemoSystem"))){
	        require_once("lib/playlist_functions.php");
		$doWhatNext=scrubTextIn($_REQUEST['doWhatNext'],1);//Strict 'word' only filter
                echo openFlashPlayer($doWhatNext,$id);
                /*switch(_conf("playMethod")){
                    case "1"://default xspf flash player.
                        echo openFlashPlayer($doWhatNext,$id);
                        break;
                    case "2"://embedded jw flash player
                        echo openJWPlayer($doWhatNext,$id,false);
                        break;
                    case "3"://external window jw flash player.
                        echo openJWPlayer($doWhatNext,$id,true);
                        break;
                    default:
                        echo "Player not yet supported";
                        break;
                }*/
	}else echo $noPlay;
	break;
  
/*playlist stuff*/
    case "downloadPlaylist":
        if(!(_conf("isDemoSystem"))){
	        require_once("lib/playlist_functions.php");
        	sendPlaylist(array(),"",$id,"","","tar.gz");
	}else echo $noPlay;
        break;
    case "loadPlaylist":
        require_once("lib/playlist_functions.php");
        echo loadPlaylist($playlistID,$type,$id,true);
        break;
    case "refreshPlaylist"://Not actually called yet, but will be called from various places to make sure current list is loaded.  Just added now to diff from above one.
        require_once("lib/playlist_functions.php");
        echo loadPlaylist($playlistID,$type,$id,false);
        break;
    case "addToPlaylist":
        require_once("lib/playlist_functions.php");
        echo addItemToPlaylist($type,$id,true);
        break;
    case "rf_addToPlaylist":
        require_once("lib/playlist_functions.php");
        echo addItemToPlaylist('song',$id,true);
        break;
    case "savePlaylist":
        require_once("lib/playlist_functions.php");
        $pl_name=scrubTextIn($_REQUEST['pl_name'],2);//allow some whitespace and punctuation.
        $pl_public=scrubTextIn($_REQUEST['pl_public']);
        $pl_public=($pl_public)?"1":"0";//clean up whatever has been passed (if at all) for insert into an int field.
        echo updatePlaylist($pl_name,$pl_public);
        break;
    case "deletePlaylistItem":
        require_once("lib/playlist_functions.php");
        echo deletePlaylistItem($id);
        break;
    case "rf_deletePlaylistItem":
        require_once("lib/playlist_functions.php");
        echo rf_deletePlaylistItem($id);
        break;
    case "rf_clearPlaylist":
        require_once("lib/playlist_functions.php");
        echo rf_clearPlaylist();
        break;
    case "deletePlaylist":
        require_once("lib/playlist_functions.php");
        echo deletePlaylist($id);
        break;
    
/*image stuff*/

/*This logic moved into the /images.php file for faster loading..
    case "getImage":
        require_once("lib/image_funcs.php");
        getImage($type,$id);
        break;
*/
    case "cacheAlbumArt":
        if(UL_ISADMIN){
            //Delete any caches there now and then start over..
            dosql("update albums set jpgThmImgData=null,jpgLgThmImgData=null");    
            require_once("lib/image_funcs.php");
            createThmsForAll(true);
        }
        break;
  
/*Progress Bar*/
    case "getProgress":
        //var_dump(prog_getProgress());exit;
	echo prog_getProgress($first);
        break;
    case "abortProgress":
        prog_abort();
        break;
}



?>
