<?php

define("albumArt_thmSize",60);
define("albumArt_lgSize",200);
set_time_limit(0);//Don't let this time out.

function doConditionalGet($etag, $lastModified){//subset of Smart Image Resizer by Joe Lencioni @ shiftingpixel.com
    //This either exits the script and returns a not modified status to browser or returns to caller to proceed with output.
    //Note I'm not convinced this actually helps much (except for maybe within a session).  Maybe I don't have the headers set right.
        //set the cacheing headers to allow img to be stored, but must revalidate each time it's loaded.
	header("Last-Modified: $lastModified");
	header("ETag: \"{$etag}\"");
	header('Cache-Control: public, must-revalidate');
        header('Expires: Thu, 12 Mar 2009 08:52:00 GMT');
        header('Pragma:');//blank out anything set by php for this header
	
	$if_none_match=null;
	$if_modified_since=null;

	if(isset($_SERVER['HTTP_IF_NONE_MATCH'])){
		$if_none_match=(get_magic_quotes_gpc())?stripslashes($_SERVER['HTTP_IF_NONE_MATCH']):$_SERVER['HTTP_IF_NONE_MATCH'];
		$if_none_match=(($if_none_match==$etag) || ($if_none_match=='"' . $etag . '"'));
	}
	if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
		$if_modified_since=($_SERVER['HTTP_IF_MODIFIED_SINCE']);
		if (($x = strrpos($if_modified_since, ';'))!==false){//strip out any extra semi-colons that may be added by various browsers
        	    $if_modified_since = substr($if_modified_since,0,$x);
	        }
		$if_modified_since=($lastModified==$if_modified_since);
	}
	if(($if_none_match || $if_modified_since) && $if_modified_since!==false && $if_none_match!==false){
		header('HTTP/1.1 304 Not Modified');
        	exit();
	}
	return true;
/*	$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ?
		stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : 
		false;
	
	$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?
		stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) :
		false;
	
	if (!$if_modified_since && !$if_none_match)
		return;
	
	if ($if_none_match && $if_none_match != $etag && $if_none_match != '"' . $etag . '"')
		return; // etag is there but doesn't match
	
	if ($if_modified_since && $if_modified_since != $lastModified)
		return; // if-modified-since is there but doesn't match

	// Nothing has changed since their last request - serve a 304 and exit
	header('HTTP/1.1 304 Not Modified');
	exit();
*/

} 

function getImage($type,$id){
/*$type can be:
	'albumArt' - thm sized album art
	'albumArtLG' - large sized album art (for album browse)
	'songArt' - thm sized album art for unattached songs (stored in song table from tags)

*/
    $albumArtFile="";
    $im=false;
    $dbImg=false;
    if($id){    
	if($type=="albumArt" || $type=="albumArtLG")$a=dosql("select albumArtFile,imgDataLastMod from albums where albumID=$id",1);
	else $a=dosql("select imgDataLastMod,null as 'albumArtFile' from songs where songID=$id",1);
	if($a)extract($a);
    }
    
    //See if the requestor has a valid cached img for this id/type and if so use that...
    
    //$find the last mod date of pic in order of db img, file img, default file img
    if($imgDataLastMod)$lastModifiedString=gmdate('D, d M Y H:i:s',j_getTimeStamp(j_getDateArray($imgDataLastMod))).' GMT';
    elseif($albumArtFile)$lastModifiedString=gmdate('D, d M Y H:i:s', filemtime($albumArtFile)) . ' GMT';
    else $lastModifiedString=gmdate('D, d M Y H:i:s', filemtime(_conf("defaultAlbumArt"))) . ' GMT';
    log_message("Last mod string of image is:$lastModifiedString.  db mod:$imgDataLastMod",3);
    $etag=md5($lastModifiedString.$type.$id);
    
    doConditionalGet($etag,$lastModifiedString);//This exits if the browsers cached version was still valid.
    log_message("Conditional image get not used, fetching image",3);

    //Fetch any img from db.  Note, we do the query whether the imgDataLastMod above was found or not because historical images (on demo) didn't enter that...
    if($id){        
        if($type=="albumArt")$dbImg=dosql("select jpgThmImgData from albums where albumID=$id",0);//sm thumb
        elseif($type=="albumArtLG") $dbImg=dosql("select jpgLgThmImgData from albums where albumID=$id",0);//lg image
	elseif($type=="songArt")$dbImg=dosql("select jpgThmImgData from songs where songID=$id",0);

        if($dbImg)log_message("Img from cache used for albumID $id",3);
        
        if(!$dbImg && ($type=="albumArt" || $type=="albumArtLG"))$dbImg=dosql("select jpgImgData from albums where albumID=$id",0);//grab the default image if there and precached aren't.
        
        if($dbImg)$im=imagecreatefromstring($dbImg);	
    }
        
    if($type=="albumArt"||$type=="songArt")$size=albumArt_thmSize;
    else $size=albumArt_lgSize;//$type=="albumArtLG"
    
    if($albumArtFile || $im){
        resizeAndOutput($albumArtFile,$size,$im);           
    }else{
        if(is_readable(_conf("defaultAlbumArt")."_$type")) readfile(_conf("defaultAlbumArt")."_$type");//This may break if browser can't tell it's a jpg.
        else resizeAndOutput(_conf("defaultAlbumArt"),$size,false);
        
    }
    
}
function resizeAndOutput($img, $thumb_width,$im,$outFile=NULL)
{/*credit to ninjabear on php forums and "vandai" on www.akemapa.com.*/
  log_message("Current Memory allocated(resizeAndOutput) is:".(memory_get_usage(true)/1000)." peak usage:".(memory_get_peak_usage(true)/1000),3);
  //if $outFile is set, then this outputs to passed file, otherwise it streams to browser.
  
  $max_width=$thumb_width;

    //Check if GD extension is loaded
    if (!extension_loaded('gd') && !extension_loaded('gd2'))
    {
        log_message("GD doesn't appear to be configured in your php setup.  This is required for albumart display.",2);
        //trigger_error("GD is not loaded", E_USER_WARNING);
        return false;
    }

    //Get Image size info and load up if needed.
    if($im){//get it from passed img resource
        $width_orig=imagesx($im);
        $height_orig=imagesy($im);
        $image_type=IMAGETYPE_JPEG;//assume all stored images coming in on stream are jpeg.
    }else{//get it from file path
	    list($width_orig, $height_orig, $image_type) = getimagesize($img);
	    $im=false;   
	    switch ($image_type)
	    {
        	case 1: $im = imagecreatefromgif($img); break;
        	case 2: $im = imagecreatefromjpeg($img);  break;
        	case 3: $im = imagecreatefrompng($img); break;
        	//default:  trigger_error('Unsupported filetype!', E_USER_WARNING);  break;
    	    }
   }

   if(!$im)return false;
    /*** calculate the aspect ratio ***/
    $aspect_ratio = (float) $height_orig / $width_orig;

    /*** calulate the thumbnail width based on the height ***/
    $thumb_height = round($thumb_width * $aspect_ratio);
   

    while($thumb_height>$max_width)
    {
        $thumb_width-=10;
        $thumb_height = round($thumb_width * $aspect_ratio);
    }
   
    $newImg = imagecreatetruecolor($thumb_width, $thumb_height);
   
    /* Check if this image is PNG or GIF, then set if Transparent*/ 
    if(($image_type == 1) OR ($image_type==3))
    {
        imagealphablending($newImg, false);
        imagesavealpha($newImg,true);
        $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
        imagefilledrectangle($newImg, 0, 0, $thumb_width, $thumb_height, $transparent);
    }
    imagecopyresampled($newImg, $im, 0, 0, 0, 0, $thumb_width, $thumb_height, $width_orig, $height_orig);
   
    //Generate the file, and output
    $ret=imagejpeg($newImg,$outFile);
    imagedestroy($im);
    imagedestroy($newImg);
    
    return $ret;
    
    
    
    /*We used to output in whatever format the original was in, but decided to switch to always jpeg for convience of other callers (like thm store function).
     switch ($image_type)
    {
        case 1: imagegif($newImg); break;
        case 2: imagejpeg($newImg);  break;
        case 3: imagepng($newImg); break;
        default:  trigger_error('Failed resize image!', E_USER_WARNING);  break;
    }
    */
}
function createThmsForAll($forceAll=false){
    /*Create a resized thm and lg img of all albumart using the config limit. If limit>0 then we'll just grab the next $limit that need to be updated.
    0 for none, -1 for all.  If $forceAll is true, then we do all regardless of the config setting.*/
    $limit=_conf("numThumbsToPrecache");
    if($forceAll)$limit=-1;
    if($limit<0 || $limit>0){
        prog_initProgressbar(_conf("lang_updatingAlbumArtCaches"),"",100);
        $numToDo=dosql("select count(*) from albums where (albumArtFile is not null or jpgImgData is not null) and (jpgThmImgData is null or jpgLgThmImgData is null) ",0);
        if($numToDo){
            $numToDo=($limit>0 && $limit<$numToDo)?$limit:$numToDo;
            $limit=($limit>0)?"limit $limit":"";
            prog_initProgressbar(_conf("lang_updatingAlbumArtCaches"),"Processing $numToDo albums",$numToDo);
            
            $a=dosql("select albumID from albums where (albumArtFile is not null or jpgImgData is not null) and (jpgThmImgData is null or jpgLgThmImgData is null) order by rand() $limit");
            $x=0;
            if($a){
                sleep(1);//Silly.. just to kick the therm.  Only needed in some cases.  Too lazy to figure out why:)
                extract($a);
                foreach($albumIDs as $i=>$albumID){
                    prog_updateProgress(1,"$i of $numToDo");
                    if(prog_checkAbort())break;//break foreach loop
                    $x+=createThumsForAlbumID($albumID);                
                }
                prog_stopProgressBar("Updated $x image caches(".($x/2)." albums)");
                log_message("Updated $x image caches(".($x/2)." albums)");
                if(_conf("autoUpdateImgCaches")==1){
                    require_once("admin_functions.php");
                    updateConfig("33",0);//Disable the 1 time auto-update of image caches once we've run successfully.
                }
                
                return true;
            }
            
        }
        prog_stopProgressBar(_conf("lang_noWorkToDo"));
    }
}
function createThumsForAlbumID($albumID){//create the cached & resized images for the passed albumID.  Returns # of images cached. (0,1,2);
    $x=0;
    log_message("Attempting to precache and resize album art images for album $albumID",2);
    $a=dosql("select albumArtFile,jpgImgData from albums where albumID=$albumID",1);
    if($a){
        extract($a);
        $im=false;
        if($jpgImgData){
            //$im=imagecreatefromstring($jpgImgData);
            $albumArtFile=tempnam($tempDir,'alb');
            writeStrToFile($jpgImgData,$albumArtFile);
        }
        
        //First do the small thumb
        $tempDir="/tmp";
        
        $tempFile=tempnam($tempDir, 'alb');
        if($tempFile){
            if(resizeAndOutput($albumArtFile,albumArt_thmSize,$im,$tempFile)){//First do the small thumb
                $x+=storeAlbumArtImage($tempFile,$albumID,"jpgThmImgData");
            }
            unlink($tempFile);
        }
        
        //Now the larger full view.
        $tempFile=tempnam($tempDir, 'alb');
        if($tempFile){
            if(resizeAndOutput($albumArtFile,albumArt_lgSize,$im,$tempFile)){
                $x+=storeAlbumArtImage($tempFile,$albumID,"jpgLgThmImgData");
            }
            unlink($tempFile);
        }
        if($im)imagedestroy($im);
        
        if($jpgImgData)unlink($albumArtFile);
        $jpgImgData="";
    }
    return $x;
}
function writeStrToFile($imgStr,$fileName){//just writes passed string to passed file. Returns true on success.
    $handle = fopen($fileName, "w");
    if($handle){
        fwrite($handle, $imgStr);
        fclose($handle);
        return true;
    }
    return false;
}
function storeAllAlbumArt(){//util function to write all folder.jpg s into the db for demo db
    $a=dosql("select albumID,albumArtFile from albums where albumArtFile is not null");
    $x=0;
    if($a){
	extract($a);
	foreach($albumIDs as $i=>$albumID){//We'll normalize and convert them all into jpgs first then store the jpg image.
            $imgFile=$albumArtFiles[$i];
            $im=false;
            list($w,$h,$image_type) = getimagesize($imgFile);
            switch ($image_type){
                case 1: $im = imagecreatefromgif($imgFile); break;
                case 2: $im = imagecreatefromjpeg($imgFile);  break;
                case 3: $im = imagecreatefrompng($imgFile); break;
                //default:  trigger_error('Unsupported filetype!', E_USER_WARNING);  break;
            }
            if($im){
                $tempFile=tempnam("/tmp", 'alb');
                if($tempFile){
                    imagejpeg($im,$tempFile);
                    $x+=storeAlbumArtImage($tempFile,$albumID,"jpgImgData");
                    imagedestroy($im);
                    unlink($tempFile);
                }
            }
	}
    }
    return "$x images stored.";
}
function storeAlbumArtImage($imgFile,$albumID,$albumsCol){//pass valid img file, albumID and which col in the albums table to store it in.
    //Returns 0 on failure, 1 on success
    $ret=0;
    $str=file_get_contents($imgFile);//read the resulting jpeg into a string
    if($str && $albumID && $albumsCol){
	$ret=dosql("update albums set $albumsCol='".mysql_real_escape_string($str)."' where albumID=$albumID");
	log_message("Sending image update to db:"."update albums set $albumsCol='[img contents]' where albumID=$albumID",3);
        if($albumsCol=='jpgImgData'){//update the last mod date and thumbs for this col too.  We'll let caching logic elsewhere redo the thumbs 
            dosql("update albums set imgDataLastMod=now(),jpgThmImgData=null,jpgLgThmImgData=null where albumID=$albumID");    
        }
    }
    $str="";
    return $ret;
}

function getAlbumArtFromTags($albumID){
    /*passed an album, we iterate thru each song's id3 tag, taking the first art image and inserting for the whole album.
    Returns 1 if an image was found.*/
    $ret=0;
    log_message("Attempting art lookup from tag for album $albumID",2);
    $a=dosql("select s.file from albums_songs albs,songs s where albs.albumID=$albumID and albs.songID=s.songID");
    if($a){
	extract($a);
	foreach($files as $i=>$f){
	    $file=strrev($f);//file names are stored in reverse for better index performance.
	    //Thanks http://labs.spaceshipnofuture.org/icky/GetID3/ for following.. (slightly modified)
	    log_message("Checking tag for art on file:$file",3);
	    $getID3 = new getID3;
	    $cover=false;
	    
	    $getID3->analyze($file);
	    if (isset($getID3->info['id3v2']['APIC'][0]['data'])) {
		$cover = $getID3->info['id3v2']['APIC'][0]['data'];
	    } elseif (isset($getID3->info['id3v2']['PIC'][0]['data'])) {
		$cover = $getID3->info['id3v2']['PIC'][0]['data'];
	    } 
	    
	    //var_dump($getID3->info);exit;
	    if($cover){//convert to jpeg and then store in album record.
		log_message("Found canidate art in tag for $file, attempting import.",3);
		log_message($cover,3);
		$im=imagecreatefromstring($cover);
		if($im){
		    log_message("image successfully created from tag info",3);
		    $tempFile=tempnam("/tmp", 'alb');
		    if($tempFile){
			imagejpeg($im,$tempFile);
			$ret+=storeAlbumArtImage($tempFile,$albumID,"jpgImgData");
			imagedestroy($im);
			unlink($tempFile);
		    }
		}else log_message("Failed to create image from tag info",3);
	    }
	    if($ret)break;//skip out after first success.
	}
    }
    if($ret)log_message("import sucessful.",3);
    else log_message("import failed",3);
    return $ret;
}


?>
