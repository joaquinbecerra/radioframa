<?php
function getAlbumListHTML($a,$destDiv,$includeArtist=true,$includeGenre=true,$class='albumList_div',$noneFoundMessg="",$minToShow=1){
    //Pass the result of the query.  Query must contain albumID albumName.
    //Pass artist,genre true if you want those included in the output.
    //Note that the album art link is handle on the client side.
    //This returns a self loading div (with passed id) containing all the albums in the query.  Any header and content below (like unattached singles) are the caller's responsibility.
   
    if($noneFoundMessg==="")$noneFoundMessg=_conf("lang_noneFound");
        
    if($a){
        extract($a);
        
        if(count($albumIDs)>=$minToShow){
            $artistNames=false;
            $artistIDs=false;
            $genreNames=false;
            $genreIDs=false;
            if($includeArtist || $includeGenre){
                //Fetch out requested info in separate querys.  This turns out to be far easier than trying to make the caller include them in their query due to the 'various' artists problem.
                if($includeArtist || $includeGenre){
                    foreach($albumIDs as $i=>$albumID){
                        if($includeArtist){
                            $a=dosql("select distinct art.name as artN, art.artistID as artID from artists art, artists_songs arts, albums_songs albs where albs.albumID=$albumID and albs.songID=arts.songID and arts.artistID=art.artistID");
                            if($a){
                                extract($a);
                                $artistNames[$i]=(count($artIDs)>1)?_conf("lang_variousArtists"):$artNs[0];
                                $artistIDs[$i]=(count($artIDs)>1)?"-1":$artIDs[0];
                            }
                        }
                        if($includeGenre){
                            $a=dosql("select distinct gen.description as genN, gen.genreID as genID from genres gen, genres_songs gens, albums_songs albs where albs.albumID=$albumID and albs.songID=gens.songID and gen.genreID=gens.genreID");
                            
                            if($a){
                                extract($a);
                                $genreNames[$i]=(count($genIDs)>1)?_conf("lang_variousGenres"):$genNs[0];
                                $genreIDs[$i]=(count($genIDs)>1)?"-1":$genIDs[0];
                            }
                        }
                    }
                }
            }
            
            $html="<div id='$destDiv' align='left' width='100%'>".sendItemList($albumIDs,$albumNames,$destDiv,"loadAlbumList",$artistIDs,$artistNames,$genreIDs,$genreNames)."</div>";
        }else $html=$noneFoundMessg;
    }else $html=$noneFoundMessg;
    return $html;
}
function getAlbumArtImportHTML($albumID){
    /*Returns the html for input form and search links to fetch and import album art from a url.*/
    $html="";
    $a=dosql("select distinct art.name as artistName, alb.name as albumName from artists art, albums alb, artists_songs arts, albums_songs albs where albs.songID=arts.songID and albs.albumID=alb.albumID and arts.artistID=art.artistID and alb.albumID=$albumID");
    if($a){
        extract($a);
        $albumName=$albumNames[0];//Just take the first one..
        $artistName=(count($artistNames)>1)?"":$artistNames[0];//If more than one then leave off.
        
        $wikipediaSearch=getSearchLink(2,$artistName,$albumName);
        $googleSearch=getSearchLink(3,$artistName,$albumName);
        $amazonSearch=getSearchLink(4,$artistName,$albumName);
        $yahooSearch=getSearchLink(5,$artistName,$albumName);
        $bingSearch=getSearchLink(6,$artistName,$albumName);
        
        //$googleSearch="<a href='http://images.google.com/images?hl=en&q=Rush+A+Farewell+To+Kings&um=1&ie=UTF-8&sa=N&tab=wi'>google</a>";
        $html="<form id='albumArtImportForm' name='albumArtImportForm' style='display:inline;' action='javascript:importAlbumArt();'>
                <input type='hidden' id='id' name='id' value='$albumID'>
                <table border='1'>
                    <tr><td align='left'><div class='textboxPrompt'>"._conf("lang_conf_import_art_prompt")."</div>
                        <div class='medItal'>"._conf("lang_conf_import_art_detail")."</div>
                        </td>
                        <td><input type='text' id='albumArtURL' name='albumArtURL' class='textbox' > <input type='submit' default value='"._conf("lang_go")."'></td>
                    </tr>
                    <tr>
                        <td colspan='2' valign='top'>"._conf("lang_config_find_art_on")." $wikipediaSearch $googleSearch $amazonSearch $yahooSearch $bingSearch</td>
                    </tr>";                    
                    /*Turns out this isn't as cool as I thought for a few reasons. Legal wouldn't let me auto-import and to actually get to the good download file you have to nav thru a bunch of marketing bs and clicks.  For now I like wikipedia best :)
                     <tr>
                        <td colspan='2'>".getSelfLoadingDivHTML('AllCDCovers','getAlbumArtSearchHTML',"albArtSearch_mode=1&albArtSearch_artName=$artistName&albArtSearch_albName=$albumName")."</td>
                    </tr>*/
        $html.="
                </table>
            ";
    
    }
    return $html;
    
}
function submitAlbumArtURL($albumID,$url){
    $x=0;
    require_once("lib/image_funcs.php");
    if($albumID){
        if($url!=""){
            if(stripos($url, "http://")===0){
                list($width_orig, $height_orig, $image_type) = getimagesize($url);
                if($image_type){
                    $im=false;   
                    switch ($image_type){
                        case 1: $im = imagecreatefromgif($url); break;
                        case 2: $im = imagecreatefromjpeg($url);  break;
                        case 3: $im = imagecreatefrompng($url); break;
                    }
                    if($im){
                        $tempFile=tempnam("/tmp", 'alb');
                        if($tempFile){
                            imagejpeg($im,$tempFile);
                            $x=storeAlbumArtImage($tempFile,$albumID,"jpgImgData");
                            imagedestroy($im);
                            unlink($tempFile);
			    if($x===0)$html=sendAlertMssgHTML("Image unchanged");
			    elseif($x==1){
				createThumsForAlbumID($albumID);//redo/do the thumbs
			    	$html="<script language='JavaScript'>setDivHTML('albumArtLG_div','".addslashes("<img class='albumArtBorder' src='images.php?doWhat=getImage&type=albumArtLG&id=$albumID&rand=".rand()."'>")."');setDivHTML('albumArtImportDiv','');</script>";
                            	
                                //$html.=sendAlertMssgHTML("Image stored.");
			    }else $html=sendAlertMssgHTML("Error saving the image.$x");
                        }
                    }else $html=sendAlertMssgHTML("There was an unspecified error creating image from url.");
                    
                }else $html=sendAlertMssgHTML("Error reading image.. check url and try again.");
            }else $html=sendAlertMssgHTML("The url must start with http://");
        }else $html=sendAlertMssgHTML("Please enter a valid url to an image");
    }else $html=sendAlertMssgHTML("No albumID !?!?");
    return $html;
}
function getAlbumListForArtist($artistID,$maxDivHeight){
    bldsql_init();
    bldsql_distinct();
    bldsql_col("alb.albumID");
    bldsql_col("case when alb.year is null or alb.year='' then alb.name else concat(alb.name,\" - <span class='alb_year'>\",alb.year,\"</span>\") end as albumName");
    bldsql_from("albums alb");
    bldsql_from("albums_songs albs");
    bldsql_from("artists_songs arts");
    bldsql_where("arts.artistID='$artistID'");
    bldsql_where("arts.songID=albs.songID");
    bldsql_where("alb.albumID=albs.albumID");
### add order by 
	bldsql_orderby("alb.name");
    $albumList=getAlbumListHTML(dosql(bldsql_cmd()),$artistID."_albumListDiv",false,false,"albumList_div",false);

    $artistName=dosql("select name from artists where artistID=$artistID",0);
    $wikipediaSearch="";
    if(UL_ISADMIN){$wikipediaSearch=getSearchLink(1,$artistName,"");}
    $html.="<div align='center'><span class='browse_title'>$artistName</span> &nbsp;&nbsp;&nbsp;".getPlayNowLink("artist",$artistID,_conf("lang_playAll"))." &nbsp;&nbsp;".getAddToPlaylistLink("artist",$artistID)." &nbsp $wikipediaSearch</div>";
    $html.="<div align='left' width='100%' style='height:$maxDivHeight px;max-height:$maxDivHeight px;'>";
    $html.="<div id='".$artistID."_albumListDiv' align='left' width='100%' >";
    $html.=$albumList;
    $html.="</div>";

    //Append on any unattached songs
    $us=getUnattachedSongList($artistID,$artistID."_unattachedSongs");
    if($us){
        if($albumList)$html.="<h4>Unattached Songs for $artistName</h4>";
        else $html.="<br><br>";
        $html.="<div id='".$artistID."_unattachedSongs'></div>$us";
    }    
    $html.="</div>";
    
    //Set into the db session that this was the last one selected
    setLastSelectedBrowseItem("artist",$artistID);

    return $html;
}
function getAlbumListForGenre($genreID,$maxDivHeight){
    bldsql_init();
    bldsql_distinct();
    bldsql_col("alb.albumID");
    bldsql_col("case when alb.year is null or alb.year='' then alb.name else concat(alb.name,\" - <span class='alb_year'>\",alb.year,\"</span>\") end as albumName");
    bldsql_from("albums alb");
    bldsql_where("(select count(*) from albums_songs albs,genres_songs gens where albs.albumID=alb.albumID and gens.genreID=$genreID and gens.songID=albs.songID)>="._conf("minGenreSongsInAlbumThreshold"));
    bldsql_orderby("alb.name");
    $albumList=getAlbumListHTML(dosql(bldsql_cmd()),$genreID."_albumListDiv",true,false);

    $genreName=dosql("select description from genres where genreID=$genreID",0);
    $html.="<div align='center'><span class='browse_title'>$genreName</span> &nbsp;&nbsp;&nbsp;".getPlayNowLink("genre",$genreID,_conf("lang_playAll"))." &nbsp;&nbsp;".getAddToPlaylistLink("genre",$genreID)."</div>";
    $html.="<div id='".$genreID."_albumListDiv' align='left' width='100%' style='overflow:auto;height:$maxDivHeight px;max-height:$maxDivHeight px;'>";
    $html.=$albumList;    
    $html.="</div>";

    //Set into the db session that this was the last one selected
    setLastSelectedBrowseItem("genre",$genreID);

    return $html;
}

function getAlbumSongList($albumID,$destDivID,$includeArtists){//returns a self-loading list of an album's songs.
    $artistIDs=array();$artistNames=array();

    bldsql_init();
    bldsql_col("s.songID as id");
    bldsql_col("s.songName");
    bldsql_col("s.songLength as length");
    bldsql_col("s.bitRate");
    bldsql_col("s.trackNo");
    bldsql_from("songs s");
    bldsql_from("albums_songs albs");
    bldsql_where("albs.albumID=$albumID");
    bldsql_where("s.songID=albs.songID");
    bldsql_orderby("convert(s.trackNo,unsigned)");
    if($includeArtists){//add in the join to the artists table
	bldsql_col("art.name as artistName");
	bldsql_col("art.artistID");
	bldsql_from("artists_songs arts");
	bldsql_from("artists art");
	bldsql_where("art.artistID=arts.artistID");
	bldsql_where("arts.songID=s.songID");
    }
    $a=dosql(bldsql_cmd());
    if($a){
        extract($a);
        foreach($ids as $i=>$id){
            $name="";
            $name=$trackNos[$i];
            $name=j_appendToList($name,"<b>".$songNames[$i]."</b>"," - ");
            if($lengths[$i])$name.=" (".util_formatSongLength($lengths[$i]).") ";
            if($bitRates[$i])$name.=" - ".util_formatBitRate($bitRates[$i])." ";
            $names[]=$name;
        }
        
        $html=sendItemList($ids,$names,$destDivID,"loadSongList",$artistIDs,$artistNames);
    }else $html=_conf("lang_noSongsFound");
    return $html;
}
function showAlbumSongs($albumID){//wrapper for getAlbumSongList that's called from the 'show songs' link on sm album display
    //figure out if this album has 1 artist or many and include as appropriate
    $includeArtists=false;
    $count=dosql("select count(art.name) from artists art, artists_songs arts, albums_songs albs where albs.albumID=$albumID and albs.songID=arts.songID and arts.artistID=art.artistID",0);
    $html="<div id='".$albumID."_songsDiv'>".getAlbumSongList($albumID,$albumID."_songsDiv",($count>1))."</div>";
    return $html;
}

function getAlbumDetail($albumID){/*The full album page.. large art, song list...*/
	$a=dosql("select alb.name as albumName, alb.year from albums alb where alb.albumID=$albumID",1);
	if($a){
		extract($a);

		//figure out if this album has 1 artist or many and include as appropriate
		$includeArtists=false;
		$artistName="";
                $artistID="";
                $wikipediaSearch=$albumName;
                
                //Album art import/update link.
                $importLink="";
                if(UL_ISADMIN){
                    $text=(dosql("select count(*) from albums where albumID=$albumID and (albumArtFile is not null or jpgImgData is not null)",0)>0)?_conf("lang_config_updateAlbumArt"):_conf("lang_config_importAlbumArt");
                    $importLink=getHREFlink("getAlbumArtImportHTML","id=".$albumID,$text,"albumArtImportDiv","importAlbumArtLink");
                }
                
		$a=dosql("select distinct art.artistID,art.name as artistName from artists art, artists_songs arts, albums_songs albs where albs.albumID=$albumID and albs.songID=arts.songID and arts.artistID=art.artistID");
		if($a){
			extract($a);
			if(count($artistNames)==1){
                            $artistName=$artistNames[0];
                            $artistID=$artistIDs[0];
                            $wikipediaSearch=$artistName." ".$albumName;
                        }
			else $includeArtists=true;
		}
                $wikipediaSearch=getSearchLink(1,$artistName,$albumName);
                if($artistName=="")$artistName=_conf("lang_variousArtists");
                
                $artistName=($artistID)?getBrowseItemLink('artist',$artistID,$artistName):$artistName;//make browseable if it's just 1 artist.
                $img="<td>&nbsp;</td>";
                
                if(_conf("showAlbumArt"))$img="<td class='lg_albumartTD'><div id='albumArtLG_div'><img class='albumArtBorder' src='images.php?doWhat=getImage&type=albumArtLG&id=$albumID'></div></td>";
		$html="<table width='80%'><tr>$img<td class='text' align='left'><h2>$albumName</h2><h4>$artistName</h4>$year<br><div class='text'>".getPlayNowLink("album",$albumID,_conf("lang_playAlbum"))." &nbsp;&nbsp;".getAddToPlaylistLink("album",$albumID)."</div>".$wikipediaSearch."&nbsp;&nbsp;$importLink</td></tr>";
    		$html.="<tr><td colspan='2'><div id='albumArtImportDiv'></div></td></tr>";
                $html.="<tr><td colspan='2'><div id='".$albumID."_songsDiv' style='overflow: auto;height:300px;'>".getAlbumSongList($albumID,$albumID."_songsDiv",$includeArtists)."</div></td></tr>";//_NEED SCREEN HEIGHT
		$html.="</table>";
	
		//Set into the db session that this was the last one selected
    		setLastSelectedBrowseItem("album",$albumID);

	}
	

    return $html;
}
function getCoverArtSearchLinks($type,$artistName="",$albumName=""){
    /*Do a search on passed site for album art and put up links to hits
    1 is allCDCovers.com
    */
    //This turned out to kind of suck. It was hard to link directly to good art and the guy kept hassling me about more prominent links and stuff.  He was way to businessy...
    return "";
    $html="";
    switch ($type){
        case 1://allCDCovers.com
            $searchTitle="Search Results from <a href='http://www.allcdcovers.com'>AllCDCovers.com</a><br>";                
            $user="tincanjukebox";
            $key="jU4eZenaD6G5";
            $searchPhrase=$artistName." ".$albumName.'/music';
            $hash=md5($key.$searchPhrase);

            $xml_request_url = 'http://www.allcdcovers.com/api/search/'.$user.'/'.$hash.'/'.urlencode($searchPhrase);
            libxml_use_internal_errors(true);
            $err=error_reporting(0);//Couldn't figure out how else to suppress errors when the site is offline, which seems to happen fairly often.
            $xml = @new SimpleXMLElement($xml_request_url, null, true);
            error_reporting($err);//reset to whatever it was.
            if (!$xml) {
              //$html.=$xml->err['msg'];
              $html=_conf("langErrRetrievingSearch")."AllCDCovers.com";
            } else {
                foreach ($xml as $title) {
                    if($title->image!="")$html.="<a href='".$title->image."' target='_new'><img src='".$title->image."' height='80' width='80'></a>";
                }
            }
            if($html){
                $html=$searchTitle."<div style='overflow:auto;'>$html</div>";            
            }else{$html=$searchTitle."None found";}
            break;
    }
    return $html;
    
    
}
?>
