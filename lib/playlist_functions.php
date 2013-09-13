<?php
/*all playlist related functions*/
function loadPlaylist($playlistID,$type,$id,$forceReload=false){
    /*This loads the passed playlist, can be 'temp' for a templist.  If no ID passed, then this starts a new 'temp' list.  If type and id are passed, then this adds them to the playlist.*/
    	if($forceReload)removePlaylistFromSession();//wipe out anything in the session.
        loadPlaylistDef($playlistID);//Load the def
        
        if($type && $id)addItemToPlaylist($type,$id);
	
	$html=getPlaylistHTML();
	return $html;
}
function removePlaylistFromSession(){
    session_start();
    unset($_SESSION['pl']);
    session_write_close();
}
function updatePlaylist($pl_name,$pl_public){//Update the playlist data (not items).
    $pl=false;
    session_start();
    //log_message(var_dump($_SESSON));
    if(isset($_SESSION['pl'])){//load from session if it has already been opened.
        $pl=$_SESSION['pl'];
    }
    $id=false;
    if($pl){
        if($pl['playlistID']=='temp'){//update the session vars and save off everything and reload the now  'perm' list.
            $pl['name']=$pl_name;
	    if(_conf("isDemoSystem")){//don't let those pain in the ass demo users actually write the name.
		$pl['name']="Demo Playlist";
	    }
            $pl['public']=$pl_public;
            $id=saveTempList($pl);
        }else{//just update the passed data and reload.
            if($pl['playlistID']){
		if(_conf("isDemoSystem")){//don't let those pain in the ass demo users actually write the name
        	        $pl_name="Demo Playlist";
	        }

                if(dosql("update playlists set name='".scrubTextForDB($pl_name)."', public=$pl_public where playlistID=".$pl['playlistID'])!==false)$id=$pl['playlistID'];
            }
        }
    }
    session_write_close();
    if($id){
        $html="<script language='JavaScript'>reloadPlaylists($id);</script>";//Send the call back that will reload everything.
    }
    return $html;
}
function saveTempList($pl){
    
    if($pl){
        if($pl['userID'] && $pl['name'] && isset($pl['public']) && $pl['plItems']){
            //log_message("insert playlists (userID,name,public) select ".$pl['userID'].",'".$pl['name']."',".$pl['public']);
            dosql("insert playlists (userID,name,public) select ".$pl['userID'].",'".$pl['name']."',".$pl['public']);
            $playlistID=db_getLastInsertID();
            if($playlistID){
                foreach($pl['plItems'] as $i=>$item){
                    dosql("insert playlistItems (playlistID,itemType,itemID,seq) select $playlistID,'".$item['itemType']."',".$item['itemID'].",$i");
                }
                session_start();
                unset($_SESSION['pl']);//remove from the session var and reload from db.
                session_write_close();
                return $playlistID;
            }
        }
    }
    return false;
}
//Playlist delete isnt working right.  Something where deletes aren't always deleted from db list.  Needs more testing.
//function deletePlaylistItem($seqNum){
function deletePlaylistItem($id){
    //delete item from current playlist.
    $html=sendStatusMssgHTML("There was an error removing this item.");
    session_start();
    if(isset($_SESSION['pl'])){
        $pl=$_SESSION['pl'];
        $items=$pl['plItems'];
        //$id=$items[$seqNum]["playlistItemID"];
        //return sendStatusMssgHTML($id);
        $ok=true;
        if($id){//real playlist, do delete.
            $ok=dosql("delete from playlistItems where seq=".$id);
            $ok=dosql("update playlistItems set seq=seq-1  where seq > ".$id);
        }
        if($ok){//either a templist or delete worked.
            unset($items[$seqNum]);
            $pl['plItems']=$items;
            $_SESSION['pl']=$pl;
            $html="<script language='JavaScript'>removePlaylistItem($seqNum);</script>";//Send the call back that will remove item from display.
        }
    }
    session_write_close();
    return $html;
}

function rf_loadPlaylistDef(){
    $res=dosql("select playlistID from playlists where name='radioframa'",1);
    $id=$res['playlistID'];
    if ($id)
        loadPlaylistDef($id);
}

function rf_getPlaylistAdmin(){
    $playlist=Array();
//    session_start();
//    if(isset($_SESSION['pl'])){
    $sql="select
    s.songID as songId,
    p.playlistitemID as itemId,
    p.seq as seq,
    s.songName as songName,
    s.file as file,
    s.albumNameFromTag as albumName
from
    playlistItems p,
    playlists pl,
    songs s
where
    pl.name = 'radioframa' and 
    p.itemType = 'song'
    and p.itemID = s.songID
    and pl.playlistID=p.playlistID
order by seq";
     $a=dosql($sql);
            if($a){
                extract($a);
                $res=Array();
                foreach($songIds as $i=> $val ){
                    
                    $res[]=Array( 'title' => $songNames[$i],
                        'album'=>$albumNames[$i],
                         'filename'=> str_replace(realpath(__DIR__.'/..').'/','',strrev(stripcslashes($files[$i]))),
                        'songId'=>$songIds[$i],
                        'itemId'=>$itemIds[$i]
                        );
                    
                }
                
            }
     return json_encode($res);
}

function rf_upPlaylistItem($seq){
    
   session_start();
   $playlistID=$_SESSION['pl']['playlistID'];
//    if(isset($_SESSION['pl'])){
    dosql("update playlistItems set seq=-10 where seq=$seq and playlistid=$playlistID");
    dosql("update playlistItems set seq=$seq where playlistid=$playlistID and seq=".($seq-1));
    dosql("update playlistItems set seq=".($seq-1)." where playlistid=$playlistID and seq=-10");
     
    return true;
}

function rf_downPlaylistItem($seq){
    session_start();
   $playlistID=$_SESSION['pl']['playlistID'];
    dosql("update playlistItems set seq=-10 where playlistid=$playlistID and seq=".$seq);
    dosql("update playlistItems set seq=$seq where playlistid=$playlistID and seq=".($seq+1));
    dosql("update playlistItems set seq=".($seq+1)." where playlistid=$playlistID and seq=-10");
     
    return true;
}

function loadPlaylistDef($playlistID){//loads the current list into a local array.  If none exists, creates one and stores it in the session.
    $pl=false;
    session_start();
    if(isset($_SESSION['pl'])){//load from session if it has already been opened.
        if($_SESSION['pl']['playlistID']==$playlistID){
            $pl=$_SESSION['pl'];
        }else{
            unset($_SESSION['pl']);    
        }
    }
    if(!$pl){
        if($playlistID!=""){//load from db and save into session too.
            $a=dosql("select * from playlists where playlistID=$playlistID",1);
            if($a){
                extract($a);
                $pl=array();
                $pl['playlistID']=$playlistID;
                $pl['name']=$name;
                $pl['userID']=$userID;
                $pl['public']=$public;
                $items=array();
                
                //Add in each of the four types of playlist objects using a monster union select
                $sql="(select p.playlistItemID as playlistItemID,p.itemType as itemType,p.itemID as itemID,p.seq as seq,s.songName as itemName from playlistItems p,songs s where p.playlistID=$playlistID and p.itemType='song' and p.itemID=s.songID)
                        union
                    (select p.playlistItemID,p.itemType,p.itemID,p.seq,alb.name from playlistItems p,albums alb where p.playlistID=$playlistID and p.itemType='album' and p.itemID=alb.albumID)
                        union
                    (select p.playlistItemID,p.itemType,p.itemID,p.seq,art.name from playlistItems p,artists art where p.playlistID=$playlistID and p.itemType='artist' and p.itemID=art.artistID)
                        union
                    (select p.playlistItemID,p.itemType,p.itemID,p.seq,g.description from playlistItems p,genres g where p.playlistID=$playlistID and p.itemType='genre' and p.itemID=g.genreID)
                    order by seq";
                    
                $a=dosql($sql);
                if($a){
                    extract($a);
                    foreach($playlistItemIDs as $i=>$id){//Note that $seqs may not be contiguous
                        $items[$seqs[$i]]=array('playlistItemID'=>$id,'itemType'=>$itemTypes[$i],'itemID'=>$itemIDs[$i],'itemName'=>$itemNames[$i]);
                    }
                }
                
                $pl['plItems']=$items;
                $_SESSION['pl']=$pl;//Save off a copy so we don't have to redo this work.
            }
            
        }
        if(!$pl){//create a new temp list if none created yet or some error above retrieving passed id..
            $pl['playlistID']="temp";
            $pl['name']="";
            $pl['userID']=UL_UID;
            $pl['public']=1;
            $pl['plItems']=array();
            $_SESSION['pl']=$pl;
        }
    }
    session_write_close();
    return $pl;
}
function getPlaylistHTML(){//create the html display for passed playlist obj
    $html="";
    session_start();
    if(isset($_SESSION['pl'])){
        $pl=$_SESSION['pl'];
    
    	$html="<table width='100' border='0' class='playlist_table'>";
        //title
        if($pl['playlistID']!="temp")$html.="<tr><td align='left' class='playlist_title'>"._conf("lang_savedPlaylist").": <span class='playlist_name'>".$pl['name']."</span></td><td align='right' class='playlist_close'>".getSetDivHTMLLink("playlistEditDiv","",_conf("lang_playlistClose"))."</td></tr>";
        else $html.="<tr><td class='playlist_title'>"._conf("lang_tempPlaylist")."</td><td align='right' class='playlist_close'>".getSetDivHTMLLink("playlistEditDiv","",_conf("lang_playlistClose"))."</td></tr>";
        //Play
        if($pl['playlistID']!="temp")$html.="<tr><td align='left'>".getPlayNowLink("playlist",$pl["playlistID"])."</td><td align='right'  class='playlist_form'><a href='javascript:deletePlaylist(".$pl['playlistID'].");'>"._conf("lang_delete")."</a></td></tr>";
        else $html.="<tr><td align='left' colspan='2'>".getPlayNowLink("playlist",$pl["playlistID"])."</td></tr>";
	
	//download
        if(_conf("allowDownloads")){
            $html.="<tr><td colspan='2' align='left'>".getDownloadLink($pl["playlistID"])."</td></tr>";
        }
        
	
        //Edit form
        $checked=($pl['public'])?"checked":"";
        $html.="<tr><td colspan='2'><form id='playListEditForm' name='playListEditForm'><table class='playlist_editForm'>
                <tr><td class='playlist_formPrompt'>Name</td><td class='playlist_form'><input id='pl_name' name='pl_name' type='text' value='".$pl['name']."'></td></tr>
        
                <tr><td colspan='2' class='playlist_form'>Public list? <input type='checkbox' id='pl_public' name='pl_public' value='1' $checked> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='javascript:savePlaylist(document.playListEditForm);'>"._conf("lang_save")."</a></td></tr>
                </table></form></td></tr>";
        
        //$html.="<tr><td colspan='2' align='right'  class='playlist_form'><a href='javascript:deletePlaylist(".$pl['playlistID'].");'>"._conf("lang_delete")."</a>";
        $height=($maxDivHeight && $maxDivHeight>400)?$maxDivHeight:400;
        
    	$html.="<tr><td colspan='2'><div style='height:".$height."px;overflow:auto;'><table id='playlistItems' class='playlist_itemsTable'>";
        $items=$pl['plItems'];
        $ids=array();
	foreach($items as $i=>$item){
		$names[]=getFormattedName($item);
		$ids[]=$i;
	}
	$html.="</table></div></td></tr>";
    	$html.="</table>";
	$html.=sendItemList($ids,$names,"playlistItems","appendItemToPlaylist");
    }
    session_write_close();
    return $html;
}
function getFormattedName($item){
	return $item['itemName']."<font class='tiny'>(".$item['itemType'].")</font>";
}
function addItemToPlaylist($type,$id,$returnJS=false){
    /*This adds passed item (song, album, artist, genre) to the currently loaded playlist.*/
    $ret=false;
    session_start();
    if(isset($_SESSION['pl'])){
        $pl=$_SESSION['pl'];
        $items=$pl['plItems'];
        switch($type){
            case "song":
                $name=dosql("select songName as name from songs where songID=$id",0);
                break;
            case "album":
                $name=dosql("select name from albums where albumID=$id",0);
                break;
            case "artist":
                $name=dosql("select name from artists where artistID=$id",0);
                break;
            case "genre":
                $name=dosql("select description as name from genres where genreID=$id",0);
                break;
        }
       
        if($type && $id && $name){
           
            $item=array('playlistItemID'=>'','itemType'=>$type,'itemID'=>$id,'itemName'=>$name);
	    $items[]=$item;
            $pl['plItems']=$items;
            if($pl['playlistID']!="temp" && $pl['playlistID']!=""){//insert into db
                $ret=dosql("insert into playlistItems (playlistID,itemType,itemID,seq) select ".$pl['playlistID'].",'$type',$id,ifnull((select max(seq)+1 from playlistItems where playlistID=".$pl['playlistID']."),0)");
            }else $ret=true;//temp list, no db insert needed yet.
        }
        $_SESSION['pl']=$pl;
    }
    
    session_write_close();
    if($returnJS && $ret){
	$html=sendItemList(array(count($items)-1),array(getFormattedName($item)),"playlistItems","appendItemToPlaylist");
        return $html;
    }else return $ret;
}

function deletePlaylist($playlistID){
    $ok=dosql("select count(*) from playlists where playlistID=$playlistID and userID=".UL_UID."",0);
    $html=_conf("lang_deleteError");
    if($ok){
        dosql("delete from playlistItems where playlistID=$playlistID");
        dosql("delete from playlists where playlistID=$playlistID");
        $html="<script language='JavaScript'>clearDeletedPlaylist();</script>";
    }
    return $html;
}

function sendPlaylist_getAlbum($albumID){//helper method to fetch album song info.
    $ret=false;
    if($albumID){
        sendPlaylist_commonSQL(false);//default stuff..
        bldsql_from("albums_songs albs");
        bldsql_where("albs.albumID=$albumID");
        bldsql_where("albs.songID=s.songID");
	bldsql_col("s.trackNo");
        $a=dosql(bldsql_cmd());
        if($a){
            extract($a);
//var_dump($trackNos);exit;
            natcasesort($trackNos);//do a natural sort on track no then use this to order all other data.
            foreach($trackNos as $i=>$trackNo){
                $sIDs[]=$songIDs[$i];
                $aKeys[]=$authKeys[$i];
                $ns[]=$names[$i];
                $ls[]=$lengths[$i];
            }
            $ret['songIDs']=$sIDs;
            $ret['authKeys']=$aKeys;
            $ret['names']=$ns;
            $ret['lengths']=$ls;
        }
	
	//Also insert into the stats table as being requested.
        sendPlaylist_updateStats('reqAlbum',$albumID);
    }
    return $ret;
}
function sendPlaylist_getArtist($artistID){//helper method to fetch artist song info.
    $ret=false;
    if($artistID){
        sendPlaylist_commonSQL();//default stuff..
        bldsql_where("arts.artistID=$artistID");
        $a=dosql(bldsql_cmd());
        if($a){
            extract($a);
            $ret['songIDs']=$songIDs;
            $ret['authKeys']=$authKeys;
            $ret['names']=$names;
            $ret['lengths']=$lengths;
        }
	
	//Also insert into the stats table as being requested.
        sendPlaylist_updateStats('reqArtist',$artistID);

    }
    return $ret;
}
function sendPlaylist_getGenre($genreID){//helper method to fetch genre song info.
    $ret=false;
    if($genreID){
        sendPlaylist_commonSQL();//default stuff..
        bldsql_from("genres_songs gens");
        bldsql_where("gens.genreID=$genreID");
        bldsql_where("gens.songID=s.songID");
        $a=dosql(bldsql_cmd());
        if($a){
            extract($a);
            $ret['songIDs']=$songIDs;
            $ret['authKeys']=$authKeys;
            $ret['names']=$names;
            $ret['lengths']=$lengths;
        }
        
	//Also insert into the stats table as being requested.
        sendPlaylist_updateStats('reqGenre',$genreID);
    }
    return $ret;
}
function sendPlaylist_getSong($songID){//helper method to fetch song info.
    $ret=false;
    if($songID){
        sendPlaylist_commonSQL();//default stuff..
        bldsql_where("s.songID=$songID");
        $a=dosql(bldsql_cmd());
        if($a){
            extract($a);
            $ret['songIDs']=$songIDs;
            $ret['authKeys']=$authKeys;
            $ret['names']=$names;
            $ret['lengths']=$lengths;
        }
        
	//Also insert into the stats table as being requested.
	sendPlaylist_updateStats('reqSong',$songID);
    }
    return $ret;
}
function sendPlaylist_updateStats($type,$id){
	//Mark this item as being 'requested' to be played.
	//$type should be 'reqAlbum',reqArtist,reqPlaylist,reqSong...
        dosql("insert statistics (type,itemID,userID,count,lastPlayed) select '$type',$id,".UL_UID.",1,now() on duplicate key update count=count+1,lastPlayed=now()");
}
function sendPlaylist_commonSQL($randomize=true){//add the sql parts that are common to the various get[types]
    bldsql_init();
    bldsql_col("s.songID as songID");
    bldsql_col("s.songName as name");
    bldsql_col("s.songLength as length");
    bldsql_col("md5(concat('".UL_UID."',s.file,'"._conf("masterAuthKey")."')) as authKey");
    bldsql_from("songs s");
    bldsql_from("artists art");
    bldsql_from("artists_songs arts");
    bldsql_where("s.songID=arts.songID");
    bldsql_where("arts.artistID=art.artistID");
    if($randomize)bldsql_orderby("rand()");
}
function sendPlaylist($songs=array(),$albumID="",$playlistID="",$artistID="",$genreID="",$fileType="M3U",$random=true){//Packages passed songs into a playlist (m3u) and sends out  to client.
    //All params are optional, but atleast one must be passed :)
    //If songs are passed, this is an array of songIDs
    //if $fileType is passed as tar.gz we'll actually stream out a tarball of the whole playlist.
    //$random is only supported on some playlists (saved) for now).
    
    $songIDs=array();$authKeys=array();$names=array();$lengths=array();
    $playListName="playlist";
    //Add each passed song.    
    foreach($songs as $songID){
        $a=sendPlaylist_getSong($songID);
        if($a){
            $songIDs=array_merge($songIDs,$a['songIDs']);
            $authKeys=array_merge($authKeys,$a['authKeys']);
            $names=array_merge($names,$a['names']);
            $lengths=array_merge($lengths,$a['lengths']);
        }
    }
     
    //And all songs from a passed album. Do a little post processing step to sort by the track # which is stored as a string in the db...
    $a=sendPlaylist_getAlbum($albumID);
    if($a){
        $songIDs=array_merge($songIDs,$a['songIDs']);
        $authKeys=array_merge($authKeys,$a['authKeys']);
        $names=array_merge($names,$a['names']);
        $lengths=array_merge($lengths,$a['lengths']);
    }
    
    //And all songs from a passed artist
    $a=sendPlaylist_getArtist($artistID);
    if($a){
        $songIDs=array_merge($songIDs,$a['songIDs']);
        $authKeys=array_merge($authKeys,$a['authKeys']);
        $names=array_merge($names,$a['names']);
        $lengths=array_merge($lengths,$a['lengths']);
    }
   
   
    //And all songs from a passed genre
    $a=sendPlaylist_getGenre($genreID);
    if($a){
        $songIDs=array_merge($songIDs,$a['songIDs']);
        $authKeys=array_merge($authKeys,$a['authKeys']);
        $names=array_merge($names,$a['names']);
        $lengths=array_merge($lengths,$a['lengths']);
    }
    
    //And any passed playlist
    if($playlistID){
        if($playlistID=="temp" ){
            //Build it up from the session info.
            session_start();
            $pl=false;
            if(isset($_SESSION['pl'])){
                $pl=$_SESSION['pl'];
            }
            session_write_close();
            if($pl){
                //array('playlistItemID'=>$id,'itemType'=>$itemTypes[$i],'itemID'=>$itemIDs[$i],'itemName'=>$itemNames[$i]);
                $items=$pl['plItems'];
                foreach($items as $i=>$item){
                    $a=false;
                    if($item['itemType']=='song'){
                        $a=sendPlaylist_getSong($item['itemID']);
                    }elseif($item['itemType']=='artist'){
                        $a=sendPlaylist_getArtist($item['itemID']);
                    }elseif($item['itemType']=='album'){
                        $a=sendPlaylist_getAlbum($item['itemID']);
                    }elseif($item['itemType']=='genre'){
                        $a=sendPlaylist_getGenre($item['itemID']);
                    }
                    if($a){
                        $songIDs=array_merge($songIDs,$a['songIDs']);
                        $authKeys=array_merge($authKeys,$a['authKeys']);
                        $names=array_merge($names,$a['names']);
                        $lengths=array_merge($lengths,$a['lengths']); 
                    }
                }
            }
        }else{ /*Fetch all the playlist items in a monster union */
            sendPlaylist_commonSQL($random);
            //Songs
            bldsql_from("playlistItems pi");
            bldsql_where("pi.playlistID=$playlistID");
            bldsql_where("pi.itemType='song'");
            bldsql_where("pi.itemID=s.songID");
	    if(!($random))bldsql_orderby("pi.seq");
            $sql="(".bldsql_cmd().")";
            //artists
            sendPlaylist_commonSQL($random);
            bldsql_from("playlistItems pi");
            bldsql_where("pi.playlistID=$playlistID");
            bldsql_where("pi.itemType='artist'");
            bldsql_where("pi.itemID=art.artistID");
            $sql.=" union (".bldsql_cmd().")";
            //albums
            sendPlaylist_commonSQL($random);
            bldsql_from("playlistItems pi");
            bldsql_where("pi.playlistID=$playlistID");
            bldsql_where("pi.itemType='album'");
            bldsql_from("albums_songs albs");
            bldsql_where("albs.songID=s.songID");
            bldsql_where("pi.itemID=albs.albumID");
            $sql.=" union (".bldsql_cmd().")";
            //genres
            sendPlaylist_commonSQL($random);
            bldsql_from("playlistItems pi");
            bldsql_where("pi.playlistID=$playlistID");
            bldsql_where("pi.itemType='genre'");
            bldsql_from("genres_songs gens");
            bldsql_where("gens.songID=s.songID");
            bldsql_where("pi.itemID=gens.genreID");
            $sql.=" union (".bldsql_cmd().")";
            
            if($random)$sql.=" order by rand()";
            $a=dosql($sql);
            if($a){
                extract($a);
                //arrays should be filled and properly named already..

		//Also insert into the stats table as being requested.
	        sendPlaylist_updateStats('reqPlaylist',$playlistID);
                $a=dosql("select itemID from playlistItems where playlistID=$playlistID and itemType='album'");
                if($a){
                    extract($a);
                    foreach($itemIDs as $itemID){sendPlaylist_updateStats('reqAlbum',$itemID);}
                }
                $a=dosql("select itemID from playlistItems where playlistID=$playlistID and itemType='artist'");
                if($a){
                    extract($a);
                    foreach($itemIDs as $itemID){sendPlaylist_updateStats('reqArtist',$itemID);}
                }
                $a=dosql("select itemID from playlistItems where playlistID=$playlistID and itemType='genre'");
                if($a){
                    extract($a);
                    foreach($itemIDs as $itemID){sendPlaylist_updateStats('reqGenre',$itemID);}
                }
                $a=dosql("select itemID from playlistItems where playlistID=$playlistID and itemType='song'");
                if($a){
                    extract($a);
                    foreach($itemIDs as $itemID){sendPlaylist_updateStats('reqSong',$itemID);}
                }
                
                //Fetch the name of the list so we can name the sent file.
                $playListName=dosql("select name from playlists where playlistID=$playlistID",0);
            }
            //$pl=loadPlaylistDef($playlistID);
        }
        
    }

    if(count($names)>0){
	switch ($fileType){
	    case "M3U":
		//tar -cz -f - 
		header("Cache-control: s-maxage=0");
		header("Content-Disposition: filename=".$playListName.".m3u");
		header("Content-Type: audio/x-mpegurl;");
	
		$playList=genM3U($songIDs,$names,$lengths,$authKeys);
		if(_conf("isDemoSystem")){//Should have gotten here if in demo mode, but just in case.
			echo "Demo";
		}else echo $playList;
		break;
	    case "PLS":
                header("Cache-control: s-maxage=0");
                header("Content-Disposition: filename=".$playListName.".pls");
                header("Content-Type: audio/x-scpls;");

                $playList=genPLS($songIDs,$names,$lengths,$authKeys);
                if(_conf("isDemoSystem")){//Should have gotten here if in demo mode, but just in case.
                        echo "Demo";
                }else echo $playList;
                break;

            case "XSPF":
		header("Cache-control: s-maxage=0");
		header("Content-Disposition: filename=".$playListName.".xspf");
		header("Content-Type: application/xspf+xml;");
	
		$playList=genXSPF($songIDs,$names,$lengths,$authKeys);
		if(_conf("isDemoSystem")){//Should have gotten here if in demo mode, but just in case.
			echo "Demo";
		}else echo $playList;
		break;
	    case "piped":
		header("Cache-control: s-maxage=0");
                header("Content-Disposition: filename=".$playListName.".txt");
                header("Content-Type: application/xspf+xml;");

		$playList=genPipedList($songIDs,$names,$lengths,$authKeys);
		 if(_conf("isDemoSystem")){//Should have gotten here if in demo mode, but just in case.
                        echo "Demo";
                }else echo $playList;
                break;

	    case "tar.gz":
                if(_conf("allowDownloads")){
                    set_time_limit(0);//Don't let this time out.
                    header("Cache-control: s-maxage=0");
                    $playListName=str_replace(" ","_",$playListName);
                    header("Content-Disposition: filename=".$playListName.".tar.gz");
                    header("Content-Type: application/x-gzip");
                                    //header("Content-encoding: gzip");
                    $cmd=_conf("downloadCmd");
                    if($cmd){
                        $size=0;
                        $tmpfname = tempnam("/tmp","tcj_");//This uses sys temp dir if /tmp doesn't exist.
                        $handle = fopen($tmpfname, "w");
                        $cmd=str_replace("[file]",$tmpfname,$cmd);
                        // do here something
                        
                        
                        foreach($songIDs as $songID){
                            //Total kluge.  Should be selecting filenames above all the way thru the stack.  May still program it that way, but for now, just iterate thru and grab the filenames
                            $a=dosql("select file,filesize from songs where songID=$songID",1);
                            if($a){
                                extract($a);
                                $fileName="".strrev(stripcslashes($file))."";
                                fwrite($handle, "$fileName\n");
                                //$files=j_appendToList($files,$fileName," ");
                                $size+=$filesize;
                            }		 
                        }
                        fclose($handle);
                        
                        
                        header('Content-Length: ' . $size);
        
                        if($size>0){
                            $fp=popen($cmd,'rb');
                            
                            $sent=0;
                            do{
                                $chunk=fread($fp,min(2048,$size-$sent));
                            
                                echo $chunk;
                                $sent+=strlen($chunk);
                                
                            }while(!feof($fp) && $sent<$size && (connection_status()== 0) );
                            pclose($fp);
                            if($sent==0)echo "Unspecified error streaming files.  Good luck with that.";
                            
                        }else echo "No files!?!";
                        
                        unlink($tmpfname);//del the list of files.
                    }
                }else echo "Sorry, you don't have permission for this.";
                break;
	}
    }
}

function getPlayListFilePath($songID,$chkSum){//Returns the full path to be used in a playlist (url)
    $url=_conf("fullSiteAddress")."/play.php?doWhat=playSong&id=$songID&uid=".UL_UID."&authKey=$chkSum";
    return $url;
}
function genPLS($songIDs,$songNames,$lengths,$chkSums){
    /*Generate a PLS format playlist*/
    $txt="[playlist]\n";
    $c=0;
    foreach($songIDs as $i=>$songID){
	$c++;
	$txt.="File".$c."=".getPlayListFilePath($songID,$chkSums{$i})."\n";
	$txt.="Title".$c."=".$songNames{$i}."\n";
	$txt.="Length".$c."=".floor($lengths{$i})."\n";
    }
    $txt.="NumberOfEntries=$c \n";
    $txt.="Version=2 \n";   
    return $txt;
}

function genM3U($songIDs,$songNames,$lengths,$chkSums){
    /*Generate a M3U format playlist*/
    $txt="#EXTM3U\n";
    foreach($songIDs as $i=>$songID){
        $txt.="#EXTINF:".floor($lengths{$i}).",".$songNames{$i}."\n";
        $txt.=getPlayListFilePath($songID,$chkSums{$i})."\n";
    }
    return $txt;
}
function genPipedList($songIDs,$songNames,$lengths,$chkSums){
	/*Generate a list of songs in Piped format (for neoMp3player)*/
	foreach($songIDs as $i=>$songID){
		$txt.=getPlayListFilePath($songID,$chkSums{$i})."|".$songNames{$i}."\n";
	}
	return $txt;
}
function genXSPF($songIDs,$songNames,$lengths,$chkSums){
    /*generatge a xspf format playlist*/
    $txt="<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<playlist version='1' xmlns='http://xspf.org/ns/0/'>
    <trackList>\n";
    foreach($songIDs as $i=>$songID){
        $albumID="";$albumName="";$artistName="";$songName="";
        bldsql_init();
        bldsql_from("songs s");
        bldsql_where("s.songID=$songID");
        bldsql_col("(select albs.albumID from albums_songs albs where albs.songID=s.songID) as albumID");
        bldsql_col("(select alb.name from albums_songs albs, albums alb where alb.albumID=albs.albumID and albs.songID=s.songID) as albumName ");
        bldsql_col("(select art.name from artists_songs arts, artists art where art.artistID=arts.artistID and arts.songID=s.songID)as artistName");
        //$a=dosql("select distinct alb.albumID,alb.name as albumName from albums_songs albs, albums alb where albs.songID=$songID and albs.albumID=alb.albumID",1);//may not need the distinct here.
        $a=dosql(bldsql_cmd(),1);
        if($a)extract($a);
        $txt.="<track>
            <location>".getPlayListFilePath($songID,$chkSums{$i})."</location>
            <title>".$songNames{$i}."</title>
            <duration>".($lengths{$i})."</duration>
            <meta rel='type'>mp3</meta>
            <creator>$artistName</creator>
            <album>$albumName</album>
            <image>"._conf("fullSiteAddress")."/images.php?doWhat=getImage&type=albumArtLG&id=$albumID</image>
        </track>\n";
        //log_message($txt);
    }//
    
    $txt.="</trackList>\n</playlist>";
    return $txt;
}
function openJWPlayer($doWhatNext,$id,$external){//doWhat next is like playSong, playAlbum... and is set in the play link 
	$external=true;//This doesn't work embeded for now because the js code src's the swfobject.js in the script tag an our current dynamic js handler (in j_ajax.js) doesn't handle that so no workie :(  Possible solution is to rewrite this so the the swf thingie is sourced in the js code itself, but I don't know how to do that just now...
    	$playListURL=urlencode(_conf("fullSiteAddress").'/index.php?doWhat='.$doWhatNext.'&id='.$id.'&playListType=XSPF');
	$JWDir=_conf("fullSiteAddress").'/lib/JWPlayer';
	$width="400";$height="300";
	if($external){//For the external player (popup window), we'll write the js that will create the player into a session var that gets opened by the popup window.  We just return the js to open the popup.
	    $js="	
		<script type='text/javascript' src='$JWDir/swfobject.js'></script>
 		<div id='mediaspace'>This text will be replaced</div>
 		<script type='text/javascript'>
  			var so = new SWFObject('$JWDir/player.swf','mpl','$width','$height','9');
  			so.addParam('allowfullscreen','true');
  			so.addParam('allowscriptaccess','always');
  			so.addParam('wmode','opaque');
			so.addVariable('autostart','true');
			so.addVariable('file','$playListURL');
 			so.addVariable('plugins','revolt');
			so.addVariable('stretching','uniform');
			so.addVariable('repeat','list');
			
			so.write('mediaspace');
			
		</script>";
	    session_start();
	    $_SESSION['jwp_FlashData']=$js;
	    $_SESSION['jwp_siteTitle']=_conf('siteTitle');
	    session_write_close();
	    $winParams="'width=$width,height=$height,resizable=no,scrollbars=no,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no'";
	    $html="<script language='JavaScript' type='text/javascript'>
		var w=window.open('jwPlayer.php','extPlayerWin',$winParams);
		w.focus();
	    </script>";
	}else{//Embedded.
	    $width="200";$height="24";
	    $html="	
		<script type='text/javascript' src='$JWDir/swfobject.js'></script>
 		<div id='mediaspace'>This text will be replaced</div>
 		<script type='text/javascript'>
  			var so = new SWFObject('$JWDir/player.swf','mpl','$width','$height','9');
  			so.addParam('allowfullscreen','true');
  			so.addParam('allowscriptaccess','always');
  			so.addParam('wmode','opaque');
			so.addVariable('autostart','true');
			so.addVariable('file','$playListURL');
 			so.addVariable('stretching','uniform');
			so.addVariable('repeat','list');
			
			so.write('mediaspace');
			
		</script>";
	}
	
	


    return $html;
        
}
function openFlashPlayer($doWhatNext,$id){//doWhat next is like playSong, playAlbum... and is set in the play link 
    //Return html to open the embedded flash player.
    $player=_conf("playMethod");//will come from conf...
    switch($player){
        case 1:
            $width=230;
            $height=100;
            $skin="MiniTunesReduced";
            $external=false;
            break;
        case 2:   
            $width=278;
            $height=505;//562;
            $skin="WinampSmall";
            $external=true;
            break;
        case 3:
            $width=700;
            $height=400;//460
            $skin="mp3music";
            $external=true;
            break;
        case 4:
            $width=400;
            $height=170;//230
            $skin="Original";
            $external=true;
            break;
        case 5:
            $width=80;
            $height=80;
            $skin="SquareOne";
            $external=false;
            break;
        case 6:
            $width=180;
            $height=370;//430
            $skin="VerticalMiniTunes";
            $external=true;
            break;
        case 7:
            $width=400;
            $height=15;
            $skin="Slim";
            $external=false;
            break;
	case 8://'neoflash'
	    $width=200;
	    $height=70;
	    $external=false;
	    break;
	case 9://jw player external
	    return openJWPlayer($doWhatNext,$id,true);
	
        default:
            $width=230;
            $height=114;
            $skin="MiniTunesReduced";
            $external=false;
            break;
        
    }
    
    $playListURL=urlencode(_conf("fullSiteAddress").'/index.php?doWhat='.$doWhatNext.'&id='.$id.'&playListType=XSPF&'.SID);//."&autoplay=true";
    $skinURL=_conf("fullSiteAddress").'/lib/xspf_jukebox/skins/'.$skin;
    $xspfPlayerURL=_conf("fullSiteAddress")."/lib/xspf_jukebox/xspf_jukebox.swf";//$xspfPlayerURL;
    $varURL=_conf("fullSiteAddress").'/lib/xspf_jukebox/variables.php';
    if($external){
	
	session_start();
        
	$_SESSION['siteTitle']=_conf('siteTitle');
	$_SESSION['player']="xspf_jukebox";
        $_SESSION['xspf_swfurl']=$xspfPlayerURL;
        $_SESSION['xspf_playlisturl']=$playListURL;
        $_SESSION['xspf_skinurl']=$skinURL;
	$_SESSION['xspf_width']=$width;
	$_SESSION['xspf_height']=$height;
        $_SESSION['xspf_doWhat']=$doWhatNext;
        $_SESSION['xspf_id']=$id;
        $_SESSION['xspf_varurl']=$varURL;
        session_write_close();
        $winParams="'width=$width,height=$height'";
        $html="<script language='JavaScript' type='text/javascript'>
            var newWindow=window.open('extPlayer.php','extPlayerWin',$winParams);
	    newWindow.focus();
        </script>";
    }elseif($player==8){//"Neo's MP3 Player"
	$mp3PlayerURL=_conf("fullSiteAddress")."/lib/neoMp3Player/player_mp3_multi.swf";
	$playListURL=urlencode(_conf("fullSiteAddress").'/index.php?doWhat='.$doWhatNext.'&id='.$id.'&playListType=piped&'.SID);
	$params='playlist='.$playListURL.'&amp;autoplay=1&amp;height='.$height.'&amp;width='.$width.'&amp;bgcolor1=cad6d4&amp;bgcolor2=95a0a0&amp;sliderovercolor=007a00&amp;buttoncolor=0d004d&amp;textcolor=000000&amp;playlistcolor=ffffe0&amp;currentmp3color=0027b3&amp;scrollbarcolor=12006b';
		$html='<div id="flashcontent" style="padding-top:3px;">
		<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0" type="application/x-shockwave-flash" data="'.$mp3PlayerURL.'" width="'.$width.'" height="'.$height.'">
    		<param name="movie" value="'.$mp3PlayerURL.'" />
    		<param name="bgcolor" value="#ffffff" />
    		<param name="FlashVars" value="'.$params.'" />
		<embed src="'.$mp3PlayerURL.'?'.$params.'" wmode="transparent" width="'.$width.'" height="'.$height.'" name="flashObject" align="middle" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
		</object></div>';	
    }else{
	$params=$xspfPlayerURL."?autoload=true&autoplay=true&skin_url=".$skinURL."&loadurl=".$varURL."&playlist_url=$playListURL";
        $html='<div id="flashcontent">
            <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0" width="'.$width.'" height="'.$height.'" id="flashObject" align="middle">
                <param name="movie" value="'.$params.'" />
                <param name="wmode" value="transparent" />
                <param name="quality" value="high">
                <embed src="'.$params.'" wmode="transparent" width="'.$width.'" height="'.$height.'" name="flashObject" align="middle" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
    
            </object>
            </div>
    
        ';
        
    }
    
    

return $html;
}
?>
