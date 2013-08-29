<?php
require_once('./lib/getid3/getid3/getid3.php');
require_once("./lib/admin_functions.php");
set_time_limit(0);//Don't let this time out.

//Various functions to maintain the music db...

function cat_getDirectories($dir,$luDate){
    /*find all directories to process for passed root Directory.*/
    $dirsToDo=array();
    if(is_readable($dir)){
	    if(is_dir($dir)){
		if($dh=opendir($dir)){
		    $processDir=false;
		    while(($file = readdir($dh))!== false){
			if(stripos($file,".")!==0){//all files that don't start with a . (includes dir files . & .. and hidden files .asdf)if($file!= "." && $file!= ".."){
			    $fullPath=$dir."/".$file;
			    if(is_readable($fullPath)){
				    if(is_dir($fullPath)){//Recurse through the directory tree...
					$dirsToDo=array_merge($dirsToDo,cat_getDirectories($fullPath,$luDate));
				    }else{
					if(filemtime($fullPath)>$luDate || filectime($fullPath)>$luDate)$processDir=true;//Only process this dir if atleast one file has been modified since we last checked.  Note we check ctime too, to catch files that were just moved in but actually created earlier.
			    	}
			    }else log_message("Error opening $fullPath.  The user the web server runs under doesn't appear to have read permissions.");
			}
		    }
###
### Process Directories where dirname changed or with moved in or moved-out files. Nice trick
			if(filemtime("$dir/.")>$luDate || filectime("$dir/.")>$luDate) {$processDir=true;}
###
		    if($processDir){//atleast 1 file to process
			$dirsToDo[]=$dir;
			log_message("Queuing $dir for processing",3);
		    }
		}else log_message("Error, couldn't open '$dir'");
	    }else log_message("Error, passed '$dir' isn't a directory");
    }else log_message("Error opening $dir.  The user the web server runs under does not appear to have permissions to this directory.");
    return $dirsToDo;
}
function cat_doSync($forceAll=false){//Main wrapper for catalog update.  
    $ret=array('songsUpdated'=>0,'artistsUpdated'=>0,'genresUpdated'=>0,'albumsUpdated'=>0,'albumArtUpdated'=>0);
    
    $rootDirs=array(_conf("mainCatalogFilePath"));
    if($forceAll) $lastUpdatedDates=array(0);
    else $lastUpdatedDates=array(_conf("catalogLastUpdatedDate"));
    
    prog_initProgressbar(_conf("lang_updatingCatalogTitle"),_conf("lang_recursingDirTree"),100);
    foreach($rootDirs as $i=>$rootDir){
        $rootDir=rtrim($rootDir,'\/');//strip any trailing / so we have a uniform starting path.
        
        $luDate=($lastUpdatedDates[$i]!="")?$lastUpdatedDates[$i]:0;
		$dirsToDo=cat_getDirectories($rootDir,$luDate);
	
		shuffle($dirsToDo);//randomize the order of processing.. There's no good reason to do this.  It could help with server's that have a problem import file, but the way we are currently architected, won't help because we process all or none.  But it doesn't hurt and may help if we change the algorthim later.
    
		if(count($dirsToDo)>0){
			prog_initProgressbar(_conf("lang_updatingCatalogTitle"),"Processing ".count($dirsToDo)." directories.",count($dirsToDo));
			log_message("Processing ".count($dirsToDo)." directories");
		}else prog_stopProgressBar(_conf("lang_noWorkToDo"));
		$x=0;
		foreach($dirsToDo as $dir){
			$x++;
			$subRet=cat_processDir($dir);
			$ret['songsUpdated']+=$subRet['songsUpdated'];
			$ret['artistsUpdated']+=$subRet['artistsUpdated'];
			$ret['genresUpdated']+=$subRet['genresUpdated'];
			$ret['albumsUpdated']+=$subRet['albumsUpdated'];
			$ret['albumArtUpdated']+=$subRet['albumArtUpdated'];
			$a=explode("/",$dir);//pop off the name of the directory for status message.
			prog_updateProgress(1,array_pop($a)."<br><span class='smalItal'>($x of ".count($dirsToDo).")</span>");
			if(prog_checkAbort())break 2;//break both foreach loops
		}
#	updateConfig(20,time(),-1);//set the last updated date
    }
### Alain    
    $html="";
    $html.=_conf("lang_catalogUpdateComplete")."<br>";
    $html.=_conf("lang_songs").": ".$ret["songsUpdated"]." ";
    $html.=_conf("lang_artists").": ".$ret["artistsUpdated"]." ";
    $html.=_conf("lang_genres").": ".$ret["genresUpdated"]." ";
    $html.=_conf("lang_albums").": ".$ret["albumsUpdated"]." ";
    $html.=_conf("lang_albumArt").": ".$ret["albumArtUpdated"]." ";
###
#    $html.="<br>"._conf("lang_songs")." deleted: $deletedSongs"; ### if($forceAll)
    log_message($html);
	
    if(!prog_checkAbort()){
### to clean orphans on partial update and some reordering in event due to autonomization of function cat_cleanUpOrphans
# 	prog_updateProgress(1,_conf("lang_cleaningOrphans"));
#	$deletedSongs=
	cat_cleanUpOrphans($forceAll,$dirsToDo);//clean up any orphans we may have just created.
    }
### only set last updated date if not aborted by user
	if(!prog_checkAbort()){updateConfig(20,time(),-1);//set the last updated date
    }else{log_message(_conf("lang_Process_CancelledByUser"));}
	
	if(!prog_checkAbort()){
	require_once("image_funcs.php");
 	prog_updateProgress(1,_conf("lang_precaching_albumart_thms"));
	createThmsForAll();//Precache album art thumb images
    }
    prog_stopProgressBar($html);
}
function cat_clearDB(){
	//debug/dev function
	dosql("delete from albums");
	dosql("delete from albums_songs");
	dosql("delete from artists");
	dosql("delete from artists_songs");
	dosql("delete from genres_songs");
	dosql("delete from genres");
	dosql("delete from songs");
        dosql("update catalogs set lastUpdatedDate=null");
}
function cat_processDir($dir){//Read a directory and process each file (update/add)
    //If the directory appears to be a single album, check for the albumart too.
    //Open passed directory and parse the contents.. add if new, update if existing and changed.
    //Returns an array with # songs, artists, genres, albums updated/inserted.
    log_message("current memory is ".memory_get_usage()/1000,3);
    $ret=array('songsUpdated'=>0,'artistsUpdated'=>0,'genresUpdated'=>0,'albumsUpdated'=>0,'albumArtUpdated'=>0);
    if (is_dir($dir)) {
	if ($dh = opendir($dir)) {
	    $albumartFile="";
	    $numAlbums=0;
		$songs=array();
		$md5s=array();
### Alain
		$tableAlbum=array();
	    $album="";
		$lastAlbum="";
	    $oneTagInfo=array();
	    while (($file = readdir($dh)) !== false) {
		if(stripos($file,".")!==0){//all files that don't start with a . (includes dir files . & .. and hidden files .asdf) if ($file != "." && $file != "..") {
		    $fullPath=$dir."/".$file;
		    if (is_file($fullPath)) {
				//See if this file is an album art file.. otherwise process it.
				if(strcasecmp($file,_conf("ablumArtFileName"))==0){//default album art to the conf set default name (like folder.jpg).
					$albumartFile=$fullPath;                            
				}elseif(strcasecmp($albumartFile,_conf("ablumArtFileName"))!=0 && strcasecmp($file,"front.jpg")==0){//if we haven't found the conf defined file, then front.jpg is default (won't work for all libraries but should be harmless otherwise)
					$albumartFile=$fullPath;
				}elseif($albumartFile=="" && stripos($file, ".jpg")>0){//if other 2 don't exist, we'll take any jpg slop (no hidden files though).
					$albumartFile=$fullPath;
				}else{//process all other files.
					log_message("Attempting to process file $fullPath.  ",2);
					$tagInfo=cat_getTagInfoFromFile($fullPath);//this only returns true if its a file we can handle.
					if($tagInfo){
						//log_message("got tag info for $fullPath",2);
						$stat=cat_syncSongToDB($tagInfo);//Insert/Update song.
					
						$songs[]=$stat['songID'];//save off for later album processing
						$md5s[]=$tagInfo['md5'];
						$album=$tagInfo['album'];
						$oneTagInfo=$tagInfo;//save off 1 for ablum processing..doesn't matter which.
						if($stat['songUpdated']||$stat['songInserted']){//Update the artist/genre relations
							$ret['songsUpdated']++;
								if(cat_syncArtist($tagInfo,$stat['songID']))$ret['artistsUpdated']++;
								//genres
							
								if($tagInfo['genre']!="")if(cat_syncGenre($tagInfo,$stat['songID']))$ret['genresUpdated']++;
						}
						//See if the albumID changed from last song (whether we just updated/inserted or no change was done.)
						if($album!=$lastAlbum){
							$lastAlbum=$album;
							$numAlbums++;
						}
					$tableAlbum[$tagInfo['album']][$stat['songID']]=$tagInfo;
					}
				}
		    }
		}

	    }
		//Now update the MD5 on the song so this one doesn't get reprocessed.  If the user cancelled somewhere in the middle of the other updates, this song will get reprocessed next time.
	    //foreach($songs as $i=>$songID){dosql("update songs set tagMD5='".$md5s[$i]."' where songID='$songID'");}
            
	    //Now tool thru the songs and see if they all have the same album.  

### Alain : to process the multi-album/folder case (there is only one albumArt for all or none)
#       if($numAlbums==1 && $album!="" ){//We assume now that we are in a folder/album, so update/insert the album info.  Look for any album art and put it in too.
		foreach($tableAlbum as $album => $v1){
			$songs=array();
			foreach($v1 as $songID => $oneTagInfo){
				$songs[]=$songID;
			}
### Alain fin

			if($oneTagInfo['album']!="Unattached Singles"){//hack for my collection which I for some reason assigned way too many songs to this ablum
				$albUpdateStat=cat_syncAlbum($oneTagInfo,$songs,$albumartFile,$dir);
				$ret['albumsUpdated']=$albUpdateStat[0];
				$ret["albumArtUpdated"]=$albUpdateStat[1];
			} 
		}

		closedir($dh);
	}
    }else log_message("Error, $dir not a directory (in cat_processDir)");
    	    
    return $ret;
}
function cat_setResetAllFromTagsTrue(){//The next time cat_processDir is called after calling this all data will be reimported from
    //the files tags like new...  If the programmer is any good this will never get called :)
    dosql("update songs set tagMD5=null");
}
function cat_cleanUpOrphans($songsToo=false,$dirsToDo=array()){/*Go thru the DB and remove any artists/albums/genres that have no songs attached to them.
    This will actually be a common case because updates are handled by migrating a song to a new instance and removing
    it from the old instance.  See below for details.
    If songsToo passed true then we'll spend a while looping thru all the songs in the db and delete any that don't have a matching file.
    */
/*The param names for this are really confusing because we changed the logic and who calls us around..  this needs to be cleaned up and clarified...*/
log_message($songsToo);
    $deleted=0;
    if($songsToo){
        $count=dosql("select count(*) from songs",0);
        if($count>0){
		prog_initProgressbar(_conf("lang_cleaningOrphans"),"",$count);
		prog_updateProgress(1,_conf("lang_cleaningOrphans"));
            $chunkSize=1000;
            $lastID=0;
            $x=0;
            
            do{
                $a=dosql("select songName,file,songID from songs where songID>$lastID order by songID limit $chunkSize");
                if($a){
                    extract($a);
					$y1=min(150,$count);
                    foreach($songNames as $i=>$song){
                        if(!is_readable(strrev($files[$i]))){//File no longer exists, delete it.
                            log_message("Deleting orphaned song $song, file ".$files[$i]." no longer exists.",2);
                            dosql("delete from songs where songID=".$songIDs[$i]);
                            $deleted++;     
                        }
                        if($y >= $y1) {prog_updateProgress($y,$song); $y=0;}
                        $x++; $y++;
                        if(prog_checkAbort())break 2;//break foreach and while looop
                    }
                    $lastID=array_pop($songIDs);
                }else $x=$count+1;
            }while($x<=$count);
        }
    }
### select songs through selected folders            
	else{
		$a=dosql("select folderPath from albums");
		if($a){
			extract($a);
			foreach($folderPaths as $dir){
				if(!is_readable($dir)) {$dirsToDo[]=$dir;}
			}
		$dirsToDo=array_unique($dirsToDo);
		}
		$count=count($dirsToDo);
		if($count>0){
            prog_initProgressbar("lang_cleaningOrphans","",$count);
			prog_updateProgress(1,_conf("lang_cleaningOrphans"));
			$x=0;
			foreach($dirsToDo as $dir){
				$a=dosql("SELECT songName,file,songs.songID as testsongID FROM albums left join albums_songs on albums.albumID=albums_songs.albumID left join songs on songs.songID=albums_songs.songID WHERE folderPath=\"$dir\"");# order by songs.songID
                if($a){
                    extract($a);
                    foreach($songNames as $i=>$song){
                        if(!is_readable(strrev($files[$i]))){//File no longer exists, delete it.
                            log_message("Deleting orphaned song $song, file ".$files[$i]." no longer exists.",2);
                            dosql("delete from songs where songID=".$testsongIDs[$i]);
                            $deleted++;     
                        }
					if(prog_checkAbort())break 2;//break both foreach looop
                    }
				$x++;
				$a=explode("/",$dir);//pop off the name of the directory for status message.
				prog_updateProgress(1,array_pop($a)."<br><span class='smalItal'>($x of ".count($dirsToDo).")</span>");
                }				
			}
		}
	}
###
    if (!prog_checkAbort()){
	prog_updateProgress(0,_conf("lang_cleaningLinkedOrphans"));
	
    /*Do the cascading deletes, basically like all the triggers wrapped into one.*/
    dosql("delete albums_songs from albums_songs left join songs on albums_songs.songID=songs.songID where songs.songID is null");
    dosql("delete artists_songs from artists_songs left join songs on artists_songs.songID=songs.songID where songs.songID is null");
    dosql("delete genres_songs from genres_songs left join songs on genres_songs.songID=songs.songID where songs.songID is null");
    
    dosql("delete artists from artists left join artists_songs on artists.artistID=artists_songs.artistID where artists_songs.artistID is null");
    dosql("delete genres from genres left join genres_songs on genres.genreID=genres_songs.genreID where genres_songs.genreID is null");
    dosql("delete albums from albums left join albums_songs on albums.albumID=albums_songs.albumID where albums_songs.albumID is null");
    
    dosql("delete playlistItems from playlistItems left join songs on playlistItems.itemID=songs.songID where songs.songID is null and playlistItems.itemType='song'");
    dosql("delete playlistItems from playlistItems left join artists on playlistItems.itemID=artists.artistID where artists.artistID is null and playlistItems.itemType='artist'");
    dosql("delete playlistItems from playlistItems left join albums on playlistItems.itemID=albums.albumID where albums.albumID is null and playlistItems.itemType='album'");
    
    dosql("delete statistics from statistics left join songs on statistics.itemID=songs.songID where songs.songID is null and (statistics.type='reqSong' or statistics.type='playedSong') ");
    dosql("delete statistics from statistics left join artists on statistics.itemID=artists.artistID where artists.artistID is null and (statistics.type='reqArtist' or statistics.type='playedArtist')");
    dosql("delete statistics from statistics left join albums on statistics.itemID=albums.albumID where albums.albumID is null and (statistics.type='reqAlbum' or statistics.type='playedAlbum')");
    }
### to make it callable independantly from cat_doSync
	
	$html="<br>"._conf("lang_songs")." "._conf("lang_deleted").": $deleted";
	prog_stopProgressBar($html);
	log_message($html);
	if(prog_checkAbort()){log_message(_conf("lang_Process_CancelledByUser"));}
	clearstatcache();
	
#    return $deleted;    
}
function cat_syncAlbum($tagInfo,$songIDs,$albumArtFile,$folderPath){/*Assumes atleast one song in songIDs*/	
	//find out how many of these songs have a link to an album and how many albums are linked (should only be 1)
        //and then sync the info.
        //Returns an array (album updated/inserted, ablumart inserted/updated) like array(1,1)
        
	$return=array(0,0);
	$albumID=false;
	$sIDs=""; ### Alain
	foreach($songIDs as $songID){$sIDs=j_appendToList($sIDs,$songID,",");}
	
	$existingAlbumIDs=array();
	$c=0;
	$a=dosql("select albumID as existingAlbumID from albums_songs where songID in ($sIDs)");
	if($a){
		extract($a);
		$c=count($existingAlbumIDs);
		$existingAlbumIDs=array_unique($existingAlbumIDs);//uniqueify
	}

	if(count($existingAlbumIDs)>1){//some sort of problem.. shouldn't happen, but clean it up if it did.
		dosql("delete from albums_songs where songID in ($sIDs)");
		$existingAlbumsIDs=array();//punt on how to migrate these albums, but if these were the only attached songs, it'll get deleted later.
	}
### Alain check if album-folder does exist
	$albumExistID=array();
	$a=dosql("select albumID as albumExistID from albums where name=trim('".$tagInfo['album']."') and folderPath='".scrubTextForDB($folderPath)."' ");

	if($a){//album exists allready
		extract($a);
		$albumID=$albumExistIDs[0];
	}
	else{
### end Alain
	if(count($existingAlbumIDs)==0){//Insert new album
		$ret=dosql("insert albums set name=trim('".$tagInfo['album']."'), year=trim('".$tagInfo['year']."')");
		$albumID=db_getLastInsertID();
                if($albumID){
                    $return[0]=1;//1 album updated/inserted
                    log_message("Album $albumID inserted",3);
                }else log_message("Error inserting album ".$tagInfo['album'],1);
                
	}else{//Only 1 in the array, update if needed
		$albumID=$existingAlbumIDs[0];
		$return[0]=dosql("update albums set name=trim('".$tagInfo['album']."'), year=trim('".$tagInfo['year']."') where albumID=$albumID ");//rely on update only returning number of rows changed.
                if($return[0]==1)log_message("Album $albumID (".$tagInfo['album'].") updated (name or year).",3);
	}
	} ### Alain
	if($albumID){
	    if($albumArtFile){
                $return[1]=dosql("update albums set albumArtFile='".scrubTextForDB($albumArtFile)."',albumArtFileLastMod='".filemtime($albumArtFile)."',jpgThmImgData=null,jpgLgThmImgData=null where albumID=$albumID ");
                /*This was causing problems on atleast one server (a nas box) due to mem problems.  I decided to split the logic so he could atleast update his collection and then optioanlly deal this cached thumbs.
                 require_once("image_funcs.php");
                createThumsForAlbumID($albumID);//redo the caches anytime the album is updated and an album art file exists..
                */
            }else dosql("update albums set albumArtFile=null,albumArtFileLastMod=null,jpgThmImgData=null,jpgLgThmImgData=null where albumID=$albumID");
                        
		if($folderPath){
				dosql("update albums set folderPath='".scrubTextForDB($folderPath)."' where albumID=$albumID");
		}
            
		if($c!=count($songIDs)){//either new album or not all songs were linked yet or the multiple albums error condition above
				foreach($songIDs as $songID){dosql("replace albums_songs set albumID=$albumID, songID=$songID");}
		}
	    
	    if(!($albumArtFile)){//attempt to fetch art from the albums files
		require_once("lib/image_funcs.php");
		$return[1]=getAlbumArtFromTags($albumID);	
	    }
	}
	return $return;
}

function cat_syncGenre($tagInfo,$songID){
    /*Genre sync is similar the artist update below.. see comments there.*/
    $genreID=dosql("select genreID from genres_songs where songID=$songID",0);
    if($genreID){//update mode
        $updateNeeded=dosql("select count(*) from genres where genreID=$genreID and lower(description)!=trim(lower('".$tagInfo['genre']."'))",0);
        if($updateNeeded){
            return cat_migrateSongToNewGenre($genreID,$tagInfo,$songID);
### Alain        }else return false;
	}else{//no genre with this name
		dosql("delete from genres_songs where songID=$songID and genreID=$genreID");
		return cat_migrateSongToNewGenre("",$tagInfo,$songID);//insert mode
	}
### Alain end
    }else{//insert mode
        return cat_migrateSongToNewGenre("",$tagInfo,$songID);
    }
}
function cat_migrateSongToNewGenre($oldGenreID,$tagInfo,$songID){
    /*either migrate song to an existing genre or create a new one and migrate it to it.  Anything that relies on the genre id, like
     smartlists or something need to have a hook in here to gracefully migrate too.
     returns true if genre was added (either it was totaly new, or an update del/insert thing)
     Note $oldGenreID can come in blank.
     */
    $return=false;
    $newGenreID=false;
    $newGenreID=dosql("select genreID from genres where lower(description)=trim(lower('".$tagInfo['genre']."'))",0);//see if new one already exists
    if(!($newGenreID)){
        dosql("insert genres set description=trim('".$tagInfo['genre']."')");
        $newGenreID=db_getLastInsertID();
        if($newGenreID)$return=true;
    }
    if($newGenreID){//no error
        if(dosql("delete from genres_songs where songID='$songID' and genreID='$oldGenreID'")!==false)
            if(dosql("replace genres_songs set songID='$songID', genreID='$newGenreID'")){ ### replace in lieu of insert (same as cat_migrateSongToNewArtist)
                
                /*ANYTHING that relies on genreID should do something here to migrate to new genreID*/
                /*Since this is totally imperfect due to denormalized nature of where the file's data is stored, our logic
                 is optimized for the case where items are merged together rather than split apart,
                 ie dave mathews and dave mathews band are merged together rather than split apart.
                We compensate slightly here by only migrating links to this id when a majority of the songs attached have also
                moved.  Note that since this runs  on every song update, this same query could be run multiple times, which is unfortunate
                but not expected to happen all that often.
                
                */
                $oldCount=($oldGenreID)?dosql("select count(*) from genres_songs where genreID=$oldGenreID",0):0;
                $newCount=dosql("select count(*) from genres_songs where genreID=$newGenreID",0);
                if($newCount>=$oldCount){//do the migrate
                    /*we should probably do something to merge items together when both old and new exist, ie if a playlist has both old genre and new genre, then the result should just be the new one not 2 new ones.  We'll leave that for a future enhancement.
                    */
		    if($oldGenreID){
			dosql("update playlistItems set itemID=$newGenreID where itemType='genre' and itemID=$oldGenreID");
		        //See note below for artist merge.
                        //dosql("update statistics set itemID=$newGenreID where (type='reqGenre' or type='playedGenre') and itemID=$oldGenreID");
		    }
                }
            
            }
    }
    return $return;
}
function cat_syncArtist($tagInfo,$songID){/*Our strategy will be to create a new artist (or find a match), remove our existing
    link and link to the new one.  This way we don't get into a change match with some other non-updated file.  
    Returns true if artist inserted/updated false if no change needed or error.
    */
	log_message("Syncing Artist: ".$tagInfo['artist'],2);
    $artistID=dosql("select artistID from artists_songs where songID=$songID",0);//Fetch any associated artist
    if($artistID){//update mode
        $updateNeeded=dosql("select count(*) from artists where artistID=$artistID and lower(name)!=trim(lower('".$tagInfo['artist']."'))",0);
        if($updateNeeded){
            return cat_migrateSongToNewArtist($artistID,$tagInfo,$songID);
### Alain        }else return false;
			}else{//no artist with this name
			dosql("delete from artists_songs where songID='$songID' and artistID='$artistID'");
			return cat_migrateSongToNewArtist("",$tagInfo,$songID);//insert mode
			}
### Alain end
    }else{//insert mode
        return cat_migrateSongToNewArtist("",$tagInfo,$songID);
    }
    
}

function cat_migrateSongToNewArtist($oldArtistID,$tagInfo,$songID){
    /*either migrate song to an existing artist or create a new one and migrate it to it.  Anything that relies on the artist id, like
     smartlists or something need to have a hook in here to gracefully migrate too.
     returns true if a new artist was added (either it was totaly new, or an update del/insert thing)
     Note $oldArtistID can come in blank.
     */
    $return=false;
    $newArtistID=false;
    $newArtistID=dosql("select artistID from artists where lower(name)=trim(lower('".$tagInfo['artist']."'))",0);//see if new one already exists
    if(!($newArtistID)){
        dosql("insert artists set name=trim('".$tagInfo['artist']."')");
        $newArtistID=db_getLastInsertID();
        if($newArtistID)$return=true;
    }
    if($newArtistID){//no error
        if(dosql("delete from artists_songs where songID='$songID' and artistID='$oldArtistID'")!==false)
### replace au lieu de insert (Pb in one case)
#            if(dosql("insert artists_songs set songID='$songID', artistID='$newArtistID'")){
            if(dosql("replace artists_songs set songID='$songID', artistID='$newArtistID'")){
                
                /*ANYTHING that relies on artistID should do something here to migrate to new artistID*/
                /*Since this is totally imperfect due to denormalized nature of where the file's data is stored, our logic
                 is optimized for the case where items are merged together rather than split apart,
                 ie dave mathews and dave mathews band are merged together rather than split apart.
                We compensate slightly here by only migrating links to this id when a majority of the songs attached have also
                moved.  Note that since this runs  on every song update, this same query could be run multiple times, which is unfortunate
                but not expected to happen all that often.
                */
                $oldCount=($oldArtistID)?dosql("select count(*) from artists_songs where artistID=$oldArtistID",0):0;
                $newCount=dosql("select count(*) from artists_songs where artistID=$newArtistID",0);
                if($newCount>=$oldCount){//do the migrate
                    /*we should probably do something to merge items together when both old and new exist, ie if a playlist has both old genre and new genre, then the result should just be the new one not 2 new ones.  We'll leave that for a future enhancement.
                    */
					if($oldArtistID){
						dosql("update playlistItems set itemID=$newArtistID where itemType='artist' and itemID=$oldArtistID");
								//This next one is causing problems.  The logic isn't correct.  It should be merging the 2 together, not changing id.
								//Kind of complicated though, so I'm blowing off for now.  Should add if both exists or migrate if not exists.  May
								//be able to do this with the replace syntax.
						//dosql("update statistics set itemID=$newArtistID where (type='reqArtist' or type='playedArtist') and itemID=$oldArtistID");
								
					}
                }
                
            }
    }
    return $return;
}
function cat_getTagInfoFromFile($file){//string full path of file to process.  We'll update/add as needed.
    //We'll take a md5  of the file we pull from the tags so we can easily check to see if any data (we're interested in) has changed. (actually we don't do this anymore, we use the mod date.  I left it in though because I think it may be useful in the future).
    //Returns the fields extracted from the file in an array
    log_message("Attempting to read tag info from file:$file",2);
    log_message("Current Memory allocated is:".memory_get_usage()/1000,3);
### Hardcoded list
#    if(stripos($file, ".mp3")>0 || stripos($file, ".ogg")>0 || stripos($file,".flac")>0){//hard code for now.. this should be in a list or lookup though.
###	$listMusic = ".MP3, .OGG, .FLAC, .WMA, .WAV, .M4A";
###	if ( !( stripos( $listMusic, pathinfo($file,PATHINFO_EXTENSION) ) === false )) { # "===" strict booleen
	if ( !( stripos( _conf('allowedMusicExtensions'), pathinfo($file,PATHINFO_EXTENSION) ) === false )) { # "===" strict booleen
	
        $getID3 = new getID3;
        $getID3->option_tag_lyrics3=false;
        $getID3->option_tag_apetag=false;
        $getID3->option_md5_data=false;
        $getID3->option_md5_data_source=false;
        $getID3->option_sha1_data=false;
        $getID3->option_max_2gb_check=false;
        $ThisFileInfo = $getID3->analyze($file);
    
        getid3_lib::CopyTagsToComments($ThisFileInfo);//Merge various tags formats into the comment area.
        //if($ThisFileInfo['fileformat']=='mp3'){//hard code for now.. this should be a list or lookup though.
    
        //Now pull out the zero element of each array(all tag items are arrays)... we don't care what the other elements are for most of these.
        $tagInfo['songName']=scrubTextForDB($ThisFileInfo ['comments_html']['title'][0]);
    
### use filename
###       if($tagInfo['songName']=="")return false;//bail if the tag didn't even have the songName.. this could be improved by inspecting the file name perhaps.
        if($tagInfo['songName']=="") {$tagInfo['songName'] = scrubTextForDB(pathinfo($file,PATHINFO_FILENAME));}//bail if the tag didn't even have the songName.. this could be improved by inspecting the file name perhaps.

	/*
	    We should in theory be able to access multiple artists in the tag array if there are more than 1,
	    but I haven't found a tag yet that has them so couldn't test easily.
	    If it's possible (and in tags) we could then relate the song to both artists
	    which would be much cooler than having the hybrid (joe bloe & fred freak) artists.
	    This would be hard to handle updates for anyway, so for now we'll just leave combined artists as they are and consider them unique.
        	if(count($ThisFileInfo ['comments_html']['artist'])>1)$tagInfo['artists']=$ThisFileInfo ['comments_html']['artist'];
                else $tagInfo['artists']=explode("&",$ThisFileInfo ['comments_html']['artist'][0]);
        */
        $tagInfo['artist']=scrubTextForDB($ThisFileInfo ['comments_html']['artist'][0]);
        
        $tagInfo['album']=scrubTextForDB($ThisFileInfo ['comments_html']['album'][0]);
### use dirname
		if($tagInfo['album']=="") {$tagInfo['album']=scrubTextForDB(basename(dirname($file)));}
        $tagInfo['year']=scrubTextForDB($ThisFileInfo ['comments_html']['year'][0]);
        $tagInfo['genre']=scrubTextForDB($ThisFileInfo ['comments_html']['genre'][0]);
        $tagInfo['trackNo']=scrubTextForDB($ThisFileInfo ['comments_html']['track_number'][0]);
        $tagInfo['file']=scrubTextForDB(strrev($file));//We reverse the file name/path to make the index more selective.
        $tagInfo['fileFormat']=scrubTextForDB($ThisFileInfo ['fileformat']);
        $tagInfo['filesize']=scrubTextForDB($ThisFileInfo ['filesize']);
        $tagInfo['bitRate']=scrubTextForDB($ThisFileInfo ['bitrate']);
        $tagInfo['songLength']=scrubTextForDB($ThisFileInfo ['playtime_seconds']);
    
        //Fetch out all the values we pulled and append them into a string, then we'll md5 that.  This way the code will somewhat self adjust as we add new keys, but only if the song's tag actually has that new key defined.
        //Note this assumes we don't arbitrarily move the order of the above array items (which would cause mass db updates!)
        
        foreach($tagInfo as $t){$string.=$t;}
        $tagInfo['md5']=md5($string);
        log_message(strrev($tagInfo['file']).": $string: ".md5($string),3);
        unset($getID3);//release memory.  
        unset($ThisFileInfo);
    
        return $tagInfo;
    }else{ 
	log_message("$file is an unsupported file type",2);
    }
    return false;
}


function cat_syncSongToDB($tagInfo){//array of tag info we want to send into db
    //First we attempt to find the song in the DB.  If there we'll check to see if any of the data
    //we are interested in has changed using the md5 check.  If it's not there
    //then we'll insert it.
    log_message("processing ".$tagInfo['file'],3);
    /*We return the following array.*/
    $ret=array('songID'=>"",'songUpdated'=>false,'songInserted'=>false);
    
    //build the col sql that might be used in both insert and update of the songs table
    $col="songName='".$tagInfo['songName']."',";
    $col.="file='".$tagInfo['file']."',";
    $col.="trackNo='".$tagInfo['trackNo']."',";
    $col.="fileFormat='".$tagInfo['fileFormat']."',";
    $col.="filesize=".sqlExpr($tagInfo['filesize'],'N').",";
    $col.="tagMD5=".sqlExpr($tagInfo['md5'],'S').",";
    $col.="bitRate=".sqlExpr($tagInfo['bitRate'],'N').",";
    $col.="songLength=".sqlExpr($tagInfo['songLength'],'N').",";
    $col.="albumNameFromTag='".$tagInfo['album']."' ";//used if song not part of a full album
    //Insert/Update the song
    $sql="select songID,tagMD5 from songs where file='".$tagInfo['file']."'";
    $a=dosql($sql,1);
    if($a){//found a match for this file.. update if needed.
	extract($a);//$songID & md5
        log_message("attempt to update ".$tagInfo['file']." id: ".$songID." with md5s: ".$tagMD5.",".$tagInfo['md5'],2);
	$ret['songID']=$songID;
	//$ret['albumID']=dosql("select albumID from albums_songs where songID='$songID'",0);//Fetch this if exists.  Note if not this sets albumID=false instead of blank.
	//log_message("from db:$tagMD5, from tag:".$tagInfo['md5']);
        //Check the MD5 and if different just update everything, otherwise we're done.
	if($tagMD5!=$tagInfo['md5']){
	    $sql="update songs set ";
	    $sql.=$col." ";
	    $sql.="where songID=$songID";
	    if(dosql($sql)!==false){
		$ret['songUpdated']=true;
	    }
	    
	}
    }else{//new file, do insert
	$sql="insert songs set ";
	$sql.=$col;
	if(dosql($sql)){
	    $songID=db_getLastInsertID();
	    $ret['songInserted']=true;
	    $ret['songID']=$songID;
	}
    }
    //var_export($ret);
    return $ret;
}

?>
