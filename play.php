<?php

set_time_limit(0);//Don't let this time out.
require_once("lib/dbLogin.php");
require_once("lib/Browser.php");
require_once("lib/j_utilFuncs_conf.php");

/*note we do not do a security check here, just check to see if the user is still enabled.*/

//Pull out and clean/escape any request vars we're interested in...
$doWhat=scrubTextIn($_REQUEST['doWhat'],1);//Strict 'word' only filter
$id=scrubTextIn($_REQUEST['id'],1);

$passedUID=scrubTextIn($_REQUEST['uid'],1);
define("PASSED_UID",$passedUID);//save this off for use by the configs

$authKey=scrubTextIn($_REQUEST['authKey'],1);

define("CONF_LOAD_SHORT",true);//set flag to skip loading anything we won't need here..
require_once("lib/loadConfigs.php");

if(_conf("isDemoSystem")){//shouldn't have gotten here, but if did then exit now.
	exit;
}

if(_conf("php-locale")!=""){//set the charset locale if needed.
	log_message("Using alternate php-locale of '"._conf("php-locale")."'",2);
	setlocale(LC_CTYPE, _conf("php-locale"));	
}

if($doWhat=="playSong"){
    playSong($id,$passedUID,$authKey);
}else var_dump($doWhat);exit;

function playSong($songID,$passedUID,$authKey){//Credit to Karl Vollmer (ampache.org) for some of the concepts for this part.  
    //Stream passed song.

    //First make sure user is still enabled.  Note we don't check their password here.  This allows you to play a saved playlist without logging in.
    if(!(dosql("select enabled from users where userID=$passedUID",0))){exit();}
    
    
    $browser=new Browser();
    $a=dosql("select bitRate,songName,file as fileName,fileFormat,songLength from songs where songID=$songID and md5(concat('".$passedUID."',file,'"._conf("masterAuthKey")."'))='$authKey'",1);
    if($a){ 
        extract($a);
        $fend=0;$fstart=0;$usePipe=false;$pipe="";$fp=false;$usePclose=false;$dsRatio=1;$usePclose=false;$canUseTail=false;$seekInCmd=false;$useLameRawInput=false;$useMp3Split=false;$useMMSSSSFormat=false;
	
        $fileName=strrev(stripcslashes($fileName));//The file is stored in reverse order to make the index more efficient, so we need to flip back.
	$fileSize=filesize($fileName);
	if($bitRate!=0)$bitRate=floor($bitRate/1000);
        if($bitRate<=0) $bitRate=320;//make reasonable incase the mp3 tag was screwy.  A high val will force it to get downsampled if that's enabled.  note we used to enforce a max of 320, but dropped that now that we support flac.
        	
	//downsampling stuff...
	$maxBandwidth=_conf("maxDownSampleRate");
	$maxBitRate=_conf("maxBitRate");
        $dsCmd=_conf("downSampleCmd");
	
	//transmorgify commands.
        $flacCmd=_conf("flacCommand");
	$oggCmd=_conf("oggCommand");
	$faadCmd=_conf("faadCommand");
	$neverConvert=_conf("neverConvert");
        
	//See if the client passed a byte range that we need to seek to.
	if(isset($_SERVER['HTTP_RANGE'])){
		header("HTTP/1.1 206 Partial Content");
		$range = sscanf($_SERVER['HTTP_RANGE'], "bytes=%d-%d");
		if(count($range)==2){
			$fend=array_pop($range);
			$fstart=array_pop($range);
		}
	}
	//make sure start/end points are valid.
	$fend=($fend)?$fend:$fileSize-1;
	$fend=($fend>$fileSize-1 || $fend<=0)?$fileSize-1:$fend;
	$fstart=($fstart)?$fstart:0;
	$fstart=($fstart>=$fileSize || $fstart<0 || $fstart>=$fend)?0:$fstart;
	$contentLength=$fend-$fstart+1;//Note that this and $fend may be overriden in ds logic below..
	
	log_message("Stream request for $fileName, user: $passedUID, fstart:$fstart fend:$fend",2);
        
        //Figure out if we need to transmogrify to mp3 or just stream the file.
	if($neverConvert){
		$usePipe=false;
	}elseif($dsCmd!="" && $flacCmd!="" && strcasecmp($fileFormat,"flac")==0){//use the flac command
		$usePipe=($flacCmd!="");
		$pipe=$flacCmd;
                $useLameRawInput=true;
                $useMMSSSSFormat=true;
	}elseif($dsCmd!="" && $oggCmd!="" && strcasecmp($fileFormat,"ogg")==0){//use the Ogg Vorbis convert cmd
		$usePipe=true;
		$pipe=$oggCmd;
                $useLameRawInput=true;
	}elseif($dsCmd!="" && $faadCmd!="" && (strcasecmp($fileFormat,"aac")==0 || strcasecmp($fileFormat,"mp4")==0 || strcasecmp($fileFormat,"m4a")==0)){//Use the faad convert cmd (not sure all these will actually work)
		$usePipe=true;
		$pipe=$faadCmd;
		$useLameRawInput=true;
        }elseif($dsCmd!="" && ($maxBandwidth>0 || $maxBitRate>0) && strcasecmp($fileFormat,"mp3")==0){//Standard mp3 downsampling.. Note that we may downsample above too.
		$usePipe=true;
                $canUseTail=true;
        }
	
	
	
	if($usePipe){//We need to convert &/or downsample mp3 out stream.
		$newRate=getDownSampleRate($maxBandwidth,$bitRate,$passedUID,$maxBitRate,($pipe!=""));
		if($newRate && $bitRate>0){//Note we need the bitRate to figure stuff out below.
			
			$dsRatio=($newRate/$bitRate);//This ratio tells us how big the resulting file will end up being.
			$fileSize=floor($dsRatio*$fileSize);//adjust the filesize for the new downsampled size.  Note this is approx.
			$fend=$fileSize-1;//override anything they may have passed, and run to the end.
			$contentLength=$fend-$fstart+1;
			
			if($fstart>0){
                                if(strpos($pipe,"%SEEK%")!==false){//This pipe command can do the seek for us...
                                        $s=floor($songLength*($fstart/$fileSize));//Convert to seconds...
                                        if($useMMSSSSFormat){//convert seconds to mm:ss.ss format
                                                $s=(floor($s/60)).":".($s%60).".00";
                                        }
                                        
                                        $pipe=str_ireplace("%SEEK%",$s,$pipe);
                                //}elseif($useMp3Split){
                                        
                                }elseif($canUseTail){/*Need to seek to some mid point.  We'll assume the system has the tail command (if it doens't, this fails and we try again below).
                                        that we can use to trim the file before piping.  If we do it afterwards then we'll have to wait for the
                                        pipe to process all the way to $fstart, which gets longer the further the seek request.
                                        This is complicated though because the client is sending byte ranges for the file AFTER it's been
                                        converted to mp3 (and downsampled).  To get the equiv point in the file prior to conversion we'll
                                        use the dsRatio to predict where it is.  This likely isn't exactly right because we use floor everywhere and
                                        the source file may have been dynamicly sampled (not constant ratio), but hopefully it's close enough.
                                        Regardless, we'll cheat and send the rest of the file even if the client only asked for a portion (not likely).
                                        That way we limit the fudge.
                                        Note this only works on file types that can be fed in starting mid stream (no headers needed).*/
                                        $s=($fstart/$dsRatio);
                                        //log_message("s:$s fstart:$fstart dsRatio:$dsRatio fileSize:$fileSize realFS:".filesize($fileName),2);
                                        $s=($s>0 && $s<filesize($fileName)-1)?floor($s):0;//make sure the start is sane.
                                        $clip="tail -c+".$s." ".escapeshellarg($fileName);
                                        if($pipe)$pipe=$clip."|".str_ireplace("%FILE%"," - ",$pipe);//set to read file from std in.
                                        else $pipe=$clip;
                                }//else we'll do the slow method below.
			}
			
			if($pipe){//Set the file in the pipe command and lame to use std in.
				$pipe=str_ireplace("%FILE%",escapeshellarg($fileName),$pipe);
				$pipe=str_ireplace("%SEEK%",0,$pipe);//If not set above, set to 0
                                $dsCmd=$pipe."|".str_ireplace("%FILE%"," - ",$dsCmd);//set to read file from std in.
                                if($useLameRawInput)$dsCmd=str_ireplace("--mp3input","-r",$dsCmd);//kind of lame.. assumes lame and -r.        
                                
			}else $dsCmd=str_ireplace("%FILE%",escapeshellarg($fileName),$dsCmd);//as we are passing as an arg, use the escape function to make it shell kosher (embedded 's)
		
					
			$dsCmd=str_ireplace("%RATE%",$newRate,$dsCmd);
                        $dsCmd=str_ireplace("%TITLE%",escapeshellarg($songName),$dsCmd);
			log_message("Attempting to stream file using command '$dsCmd' If this fails, copy the command and try running from the command line to find error.  fstart:$fstart fend:$fend contentLength:$contentLength filesize:$fileSize actualBitrate:$bitRate newBitRate:$newRate",2);

                        
		
			$fp=popen($dsCmd,'rb');//Note that if this fails because the command doesn't exist, it still returns a valid fp resource!  
			if($fp!==false)$usePclose=true;
                        else log_message("pipe command failed.  Try copying and running from command line to find error.  '$dsCmd'.",1);
                        //var_dump($fp);exit;
		}
	}
	
	if($fp===false)$fp=fopen($fileName,'rb');//if we bypassed the pipe stuff or it failed, then try a normal pipe.
	
	
        if($fp){
		
		
		header('Cache-Control: no-cache');
		header("Cache-Control: no-store, must-revalidate");
		header('Pragma: no-cache');
		header('Accept-Ranges: bytes');
		
		header("Content-Length:$contentLength");
		header('Content-Range: bytes '.$fstart.'-'.$fend.'/'.$fileSize);
		$browser->downloadHeaders($songName,$fileFormat,false,$fileSize);//Note pass songName instead of file name here because we don't want the full path to be included in the headers
		$sent=0;
		$npID=startSongTasks($songID,$passedUID);
		if($npID){//only send file if we successfully logged it
			$sent=0;
			if($fstart>0){//seek ahead if requested/needed (may have already been done above).
				if(!($usePclose)){//jump to the start point if we're just using a normal file read.
					fseek($fp,$fstart);
				}elseif(!$canUseTail && !$seekInCmd && !$useMp3Split){
					//We have to spin thru the first bit of the file going thru the pipe (downsampling & transmorgifying).  This isn't a great solution
					//because it takes as long as the pipe (like lame) takes to process the file to this point.  This only happens for file
					//types that couldn't use the tail method above.  We do this (even though it's lame) so it doesn't error out, but depending on where they seek'd to, they might prefer to error out :)
					do{	$chunk=fread($fp,min(8192,$fstart-1-$sent));
						$sent+=strlen($chunk);
					}while(!feof($fp) && $sent<$fileSize && $sent<($fstart-1) && (connection_status()== 0) );
				}
			}
			
			$sent=0;		
			$sequence=0;

			if($usePclose){//streaming/transmorgifying in some way.. read and send out in chunks
				do{// log_message("Sent $sent bytes.  Content length:$contentLength FileSize:$fileSize fstart:$fstart fend:$fend file:$fileName",2);
				    $chunk=fread($fp,min(8192,$contentLength-$sent));//was 2048..trying larger chunk size to speed up and have larger buffer on client
				    if($chunk!='') print $chunk;//make sure there is content to actually send..
				    flush();//attempt to flush this chunk to the client.
				    $sent+=strlen($chunk);
				    $sequence++;
				}while(!feof($fp) && $sent<$contentLength && strlen($chunk)>=1 && (connection_status()== 0) );
	                        if($contentLength>$sent){//if we muffed our calculation a bit (in pipe) send filler out for clients that care
        	                        while($contentLength>$sent){echo " ";$sent++;}
					log_message("Sending filler.  contentlen:$contentLength sent:$sent",2);
                        	}
			}else{//we just opened a file pointer, so send the whole sucker out.
				log_message("Sending file $fileName using fpassthru.",2);
				ob_clean();
    				flush();
				$sent=fpassthru($fp);
				if($sent==false)$sent=0;
			}
		}
		if($usePclose)pclose($fp);
		else fclose($fp);
                log_message("Sent $sent bytes.  Content length:$contentLength FileSize:$fileSize fstart:$fstart fend:$fend file:$fileName",2);
		//Mark this song as completed
		if($sent>0)endSongTasks($songID,$npID,$passedUID);

        }else log_message("Couldn't open file $fileName");
    
    
    }else exit;
}

function getDownSampleRate($maxBandwidth,$songBitRate,$passedUID,$maxBitRate,$needRate){//Returns false if no downsampling needed, the new mp3 bitrate if it is needed.  This returns a max of 320.
    //how many users currently playing or have played in last 10 minutes
    //Note this should exclude users who are on a local lan as downsampling wouldn't be needed. That will have to be a future enhancement.  Maybe by tracking ip address (although subnettng and local nat will make that hard) or by setting a flag for 'lan user' in the users table.
    //$needRate is true to always return a bitrate (like if transmorgifying from flac), false if the logic should determine whether to downsample, ie mp3 to mp3.
    
###    $n=dosql("select count(distinct(userID)) from nowPlaying where userID!=$passedUID and (timestampdiff(MINUTE,startTime,CURRENT_TIMESTAMP)<10 or completed=0)",0);
    $n=dosql("select count(distinct(userID)) from nowPlaying where userID!=$passedUID and (date_add(startTime, interval 10 minute)>now() or completed=0)",0);

    $r=320;//default to max (mp3) value.

    if($maxBandwidth>0) $r=($maxBandwidth/($n+1));//add one for this user
    
    if($maxBitRate>0) $r=($r>$maxBitRate)?$maxBitRate:$r;//Don't let this be higher than the user's max defined bitrate preference.	
    
    $r=getValidBitRate($r);//make it a valid mp3 stream rate
    
    if($r>=$songBitRate && (!($needRate)))return false;//No downsampling needed.
    else return min($r,$songBitRate);
}
function getValidBitRate($r){//returns one of the 'valid' mp3 bitrates for passed bitrate
    if($r>=320)$r=320;
    elseif($r>=256)$r=256;
    elseif($r>=224)$r=224;
    elseif($r>=192)$r=192;
    elseif($r>=160)$r=160;
    elseif($r>=128)$r=128;
    elseif($r>=112)$r=112;
    elseif($r>=96)$r=96;
    elseif($r>=80)$r=80;
    elseif($r>=64)$r=64;
    elseif($r>=56)$r=56;
    elseif($r>=48)$r=48;
    elseif($r>=40)$r=40;
    else $r=32;
    return $r;
}
function startSongTasks($songID,$uid){
    $id=false;
    if(dosql("insert nowPlaying (songID,songLength,userID) select songID,ifnull(floor(songLength),0),$uid from songs where songID=$songID")){
        $id=db_getLastInsertID();
        //Prune out previous song if user didn't listen to very much of it
###        dosql("delete from nowPlaying where userID=$uid and completed=0 and timestampdiff(SECOND,startTime,CURRENT_TIMESTAMP)<30 and id!=$id");
        dosql("delete from nowPlaying where userID=$uid and completed=0 and date_add(startTime, interval 30 second)>now() and id!=$id");
    }
    return $id;
}
function endSongTasks($songID,$npID,$passedUID){
	//Update the nowplaying table to set this song as completed.
	if($npID && passedUID && $songID){
		dosql("update nowPlaying set completed=1 where id=$npID");
	
	
		//Insert into stats
		//Mark this song as being played.

		dosql("insert statistics (type,itemID,userID,count,lastPlayed) values ('playedSong',$songID,$passedUID,1,now()) on duplicate key update count=count+1,lastPlayed=now()");
		dosql("insert statistics (type,itemID,userID,count,lastPlayed) select 'playedAlbum',albs.albumID,$passedUID,1,now() from albums_songs albs where songID=$songID on duplicate key update count=count+1,lastPlayed=now()");
		dosql("insert statistics (type,itemID,userID,count,lastPlayed) select 'playedArtist',arts.artistID,$passedUID,1,now() from artists_songs arts where songID=$songID on duplicate key update count=count+1,lastPlayed=now()");
	}
}
?>
