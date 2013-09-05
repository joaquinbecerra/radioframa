<?php

/* all functions that return content */
require_once("album_funcs.php");

function getDefaultContent() {//Main page stuff.
    if (BROWSER_TYPE == "normal") {
        /* Do a special check to see if we were instructed to reload the prefs tab (like if user
          just changed skin or lang).  Ideally we should just handle any of the tabs genericlly but
          that got complicated in that they all have different params and there isn't much need for
          the others.. when one comes up the below can easily be reconfigured to handle them too. */
        $loadPrefs = false;
        session_start();
        if (isset($_SESSION['loadTab'])) {
            $loadPrefs = ($_SESSION['loadTab'] == 'menu_preferences');
            unset($_SESSION['loadTab']);
        }

        session_write_close();

        if ($loadPrefs)
            $html = "<script language='JavaScript'>menu_ItemSelected('menu_preferences','editPreferences','',0); </script>";
        else
            $html = " <script language='JavaScript'>
		    loadDivArea('randomBrowseListDiv','getRandomBrowsingTabObj','');
		    setDivHTML('randomBrowseTitleDiv','" . _conf('lang_randomAlbumsHeader') . "');
		    setDivHTML('randomRefreshInstructDiv','" . _conf("lang_refreshTab") . "');
		    startNowPlaying();
		</script>";
        return $html;
    }
    else
        return "small screen stuff here.";
}

function getSearchHTML() {
    $html = "<form id='searchBox' name='searchBox' style='display:inline;' action='javascript:doSearch();'>
	    <table>
		<tr>
		    <td><font class='textboxPrompt'>" . _conf("lang_search") . "</td>
		    <td><input type='text' id='search_val' name='search_val' class='textbox' ></td>
		    <td class='textboxPrompt'><input type='submit' default value='" . _conf("lang_go") . "'></td>
		</tr>
		<tr>
		    <td colspan='3' class='textboxPrompt'>
			<input type='radio' name='search_type' value='artist' checked >" . _conf("lang_artists") . "<input type='radio' name='search_type' value='album'>" . _conf("lang_albums") . "<input type='radio' name='search_type' value='song' >" . _conf("lang_songs") . "
		    </td>
		</tr>
	    </table>
            </font></form><script language='JavaScript'>setFocus('search_val');</script>
            ";
    return $html;
}

function doSearch($searchVal, $searchType) {
    include("search_funcs.php");
    if (!string_search($searchType, $searchVal))
        exit;

    $title = _conf("lang_searchResults") . " '$searchVal' <span class='tiny'>(" . _conf("lang_" . $searchType . "s") . ")</span>";
    $title = "<h3>$title</h3>";

    if ($searchType == "song") {
        bldsql_init();
        bldsql_from("songs s left join albums_songs albs on s.songID=albs.songID left join artists_songs arts on s.songID=arts.songID");

        bldsql_from("haystack h");
        bldsql_where("h.id=s.songID");
        bldsql_orderby("h.precedence desc");

        //bldsql_where("lower(s.songName) like lower('%".$searchVal."%')");

        bldsql_col("s.songID as id");
        bldsql_col("s.songName");
        bldsql_col("s.songLength as length");
        bldsql_col("s.bitRate");
        bldsql_orderby("s.songName");

        bldsql_from("artists art");
        bldsql_where("art.artistID=arts.artistID");
        bldsql_col("art.name as artistName");
        bldsql_col("art.artistID");

        bldsql_from("albums alb");
        bldsql_where("alb.albumID=albs.albumID");
        bldsql_col("alb.name as albumName");
        bldsql_col("alb.albumID");

        $a = dosql(bldsql_cmd());
        if ($a) {
            extract($a);
            foreach ($ids as $i => $id) {
                $names[$i] = "<b>" . $songNames[$i] . "</b>";
                //if($artistNames[$i]!="")$names[$i].=" - ".getBrowseItemLink('artist',$artistIDs[$i],$artistNames[$i]);
                //if($albumNames[$i]!="")$names[$i].=" - ".getBrowseItemLink('album',$albumIDs[$i],$albumNames[$i]);
                if ($lengths[$i])
                    $names[$i].=" (" . util_formatSongLength($lengths[$i]) . ") ";
                if ($bitRates[$i])
                    $names[$i].=" - " . util_formatBitRate($bitRates[$i]) . " ";
            }
            $html = "<div id='searchSongList' class='searchSongList'>";
            $html.=sendItemList($ids, $names, "searchSongList", "loadSongList", $artistIDs, $artistNames, $albumIDs, $albumNames);
            $html.="</div>";
        }
        else
            $html = _conf("lang_noneFound");
        return $title . $html;
    }else {
        return $title . getBrowsePage($searchType, $searchVal, "", 25, false, true);
    }
}

function rf_doSearch($searchVal) {

    bldsql_init();
    $searchVal = strtoupper(mysql_real_escape_string($searchVal));
    bldsql_col("s.songID as id");
    bldsql_col("s.songName");
    bldsql_col("s.songLength as length");
    bldsql_col("s.bitRate");
    bldsql_col("a.name as albumName");
    bldsql_col("c.name as artistName");
    bldsql_from("songs s left join albums_songs albs on s.songID=albs.songID left join albums a on a.albumID=albs.albumID left join artists_songs as art on s.songID=art.songID left join artists c on c.artistID=art.artistID");
    bldsql_where("UPPER(s.songName) like '%$searchVal%' or  UPPER(a.name) like '%$searchVal%' or UPPER(c.name) like '%$searchVal%'");
    bldsql_orderby("s.songName");

    $a = dosql(bldsql_cmd());
    if ($a) {
        //$html="<div id='searchSongList' class='searchSongList'>";
        // var_dump($a);
        // return;
        $res = array();
        extract($a);
        foreach ($ids as $i => $id) {

            $res[] = Array('songId' => $id,
                'songName' => $songNames[$i],
                'artistName' => $artistNames[$i],
                'albumName' => $albumNames[$i]);

            //$html.="<b>".$songNames[$i]."</b>";
            //$html.=" ( {$artistNames[$i]} - {$albumNames[$i]} )<br>";
        }


        //$html.="</div>";
    }
    else
        $res = Array(); //$html=_conf("lang_noneFound");
        
//return $html;
    return json_encode($res);
}

function getRandomBrowsingTabObj() {
    $tabNames = array(_conf("lang_randomAlbums"), _conf("lang_randomArtists"), _conf("lang_randomRecentlyAdded"), _conf("lang_randomRecent"), _conf("lang_randomNotRecent"), _conf("lang_randomNever"), _conf("lang_randomLeast"), _conf("lang_randomMost"), _conf("lang_randomOther"));
    $params = array("doWhat=getRandomList&type=randomAlbums", "doWhat=getRandomList&type=randomArtists", "doWhat=getRandomList&type=randomRecentlyAdded", "doWhat=getRandomList&type=randomRecent", "doWhat=getRandomList&type=randomNotRecent", "doWhat=getRandomList&type=randomNever", "doWhat=getRandomList&type=randomLeast", "doWhat=getRandomList&type=randomMost", "doWhat=getRandomList&type=randomOther");
    $html.=getSideTabsArea("randomBrowseTable", "randomBrowseListDiv", $tabNames, "url", $params, _conf("defaultRandomTab") - 1, 6, 25);
    return $html;
}

function getRandomList($type) {
    /* Returns a list of random items of specified type */
    bldsql_init();
    bldsql_distinct();
    bldsql_orderby("rand()");
    $limit = "5";
    $namesLinkType = 'album'; //default for most of them.
    $destDiv = "randomListDiv";
    $artists = array();
    $noneFoundMssg = _conf("lang_notEnoughData");
    switch ($type) {
        case "randomAlbums"://rand from all albums
            bldsql_col("alb.albumID");
            bldsql_col("alb.name as albumName");
            bldsql_from("albums alb");
            $html = getAlbumListHTML(dosql(bldsql_cmd() . " limit $limit"), $destDiv, true, true, "albumList_div", $noneFoundMssg, $limit);

            break;
        case "randomArtists":
            bldsql_from("artists art");
            bldsql_col("art.artistID");
            bldsql_col("art.name as artistName");
            bldsql_col("concat((select count(*) from artists_songs where artistID=art.artistID),' songs') as artistInfo");
            $sql = bldsql_cmd() . " limit $limit";
            $a = dosql($sql);
            $html = getArtistListHTML($a, $destDiv);
            //var_dump($sql);

            break;
        case "randomRecent"://Random from recently Played
            //Couldn't get the syntax right to do this all in 1 query on my version of mysql.. so just split parts.
            $c = dosql("select count(*) from statistics where  type='playedAlbum' and userID=" . UL_UID, 0);
            $c = floor($c / 2);
            $d = dosql("select lastPlayed from statistics where type='playedAlbum' and userID=" . UL_UID . " order by lastPlayed limit $c,1", 0);
            bldsql_col("alb.albumID");
            bldsql_col("alb.name as albumName");
            bldsql_from("albums alb");
            bldsql_from("statistics stat");
            bldsql_where("alb.albumID=stat.itemID");
            bldsql_where("stat.type='playedAlbum'");
            bldsql_where("stat.userID=" . UL_UID);
            bldsql_where("stat.lastPlayed>='$d'");
            $html = getAlbumListHTML(dosql(bldsql_cmd() . " limit $limit"), $destDiv, true, true, "albumList_div", $noneFoundMssg, $limit);
            break;
        case "randomNever"://random from never played
            bldsql_col("alb.albumID");
            bldsql_col("alb.name as albumName");
            bldsql_from("albums alb left join statistics stat on alb.albumID=stat.itemID and stat.type='playedAlbum' and stat.userID=" . UL_UID);
            bldsql_where("stat.itemID is null");

            $html = getAlbumListHTML(dosql(bldsql_cmd() . " limit $limit"), $destDiv, true, true, "albumList_div", $noneFoundMssg, $limit);
            break;
        case "randomNotRecent": //random from played, but not recently
            $c = dosql("select count(*) from statistics where  type='playedAlbum' and userID=" . UL_UID, 0);
            $c = floor($c / 2);
            $d = dosql("select lastPlayed from statistics where type='playedAlbum' and userID=" . UL_UID . " order by lastPlayed limit $c,1", 0);
            bldsql_col("alb.albumID");
            bldsql_col("alb.name as albumName");
            bldsql_from("albums alb");
            bldsql_from("statistics stat");
            bldsql_where("alb.albumID=stat.itemID");
            bldsql_where("stat.type='playedAlbum'");
            bldsql_where("stat.userID=" . UL_UID);
            bldsql_where("stat.lastPlayed<='$d'");
            $html = getAlbumListHTML(dosql(bldsql_cmd() . " limit $limit"), $destDiv, true, true, "albumList_div", $noneFoundMssg, $limit);
            break;
        case "randomOther"://random from other users played
            bldsql_col("alb.albumID");
            bldsql_col("alb.name as albumName");
            bldsql_from("albums alb");
            bldsql_from("statistics stat");
            bldsql_where("alb.albumID=stat.itemID");
            bldsql_where("stat.type='playedAlbum'");
            bldsql_where("stat.userID!=" . UL_UID);
            $html = getAlbumListHTML(dosql(bldsql_cmd() . " limit $limit"), $destDiv, true, true, "albumList_div", $noneFoundMssg, $limit);
            break;
        case "randomLeast"://random from least number of times played
            $c = dosql("select count(*) from statistics where  type='playedAlbum' and userID=" . UL_UID, 0);
            $c = floor($c / 2);
            $d = dosql("select count from statistics where type='playedAlbum' and userID=" . UL_UID . " order by lastPlayed limit $c,1", 0);
            bldsql_col("alb.albumID");
            bldsql_col("alb.name as albumName");
            bldsql_from("albums alb");
            bldsql_from("statistics stat");
            bldsql_where("alb.albumID=stat.itemID");
            bldsql_where("stat.type='playedAlbum'");
            bldsql_where("stat.userID=" . UL_UID);
            bldsql_where("stat.count<='$d'");
            $html = getAlbumListHTML(dosql(bldsql_cmd() . " limit $limit"), $destDiv, true, true, "albumList_div", $noneFoundMssg, $limit);
            break;
        case "randomMost"://random from most number of times played
            $c = dosql("select count(*) from statistics where  type='playedAlbum' and userID=" . UL_UID, 0);
            $c = floor($c / 2);
            $d = dosql("select count from statistics where type='playedAlbum' and userID=" . UL_UID . " order by lastPlayed limit $c,1", 0);
            bldsql_col("alb.albumID");
            bldsql_col("alb.name as albumName");
            bldsql_from("albums alb");
            bldsql_from("statistics stat");
            bldsql_where("alb.albumID=stat.itemID");
            bldsql_where("stat.type='playedAlbum'");
            bldsql_where("stat.userID=" . UL_UID);
            bldsql_where("stat.count>='$d'");
            $html = getAlbumListHTML(dosql(bldsql_cmd() . " limit $limit"), $destDiv, true, true, "albumList_div", $noneFoundMssg, $limit);
            break;
        case "randomRecentlyAdded"://random from recently added.
            //As we don't store the date something was added (maybe we should), we just use the albumID (which is always increasing) as a proxy.
            bldsql_col("alb.albumID");
            bldsql_col("alb.name as albumName");
            bldsql_from("albums alb");

            //$numAlbums=dosql("select count(*) from albums",0);
            $maxID = dosql("select max(albumID) from albums", 0); //There can be gaps (and are on my server because of how many times I wiped and reloaded the db).

            bldsql_where("alb.albumID>" . floor(($maxID - _conf("numRandRecentAlbums")))); //arbitrary hist
            $html = getAlbumListHTML(dosql(bldsql_cmd() . " limit $limit"), $destDiv, true, true, "albumList_div", $noneFoundMssg, $limit);
            break;
    }

    return $html;
}

function getArtistListHTML($a) {//Pass the result of the query.  Query must contain artistID,artistName and optionally artistInfo.
    //returns js to load the passed div with formatted results
    $noneFoundMessg = _conf("lang_noneFound");
    if ($a) {
        extract($a);
        $includeInfo = isset($artistInfos);
        $html = "<table class='artistList_table' width='60%'>";
        foreach ($artistIDs as $i => $artistID) {
            $info = ($includeInfo) ? "<td class='artistList_info'>$artistInfos[$i]</td>" : "<td></td>";
            $html.="<tr><td class='artistList_name'>" . getBrowseItemLink("artist", $artistID, $artistNames[$i], "artistList_name") . "</td>$info</tr>";
            $html.="<tr><td class='artistList_playRow' colspan='2'>" . getPlayNowLink("artist", $artistID) . " " . getAddToPlaylistLink("artist", $artistID) . "</td></tr>";
            $html.="<tr><td colspan='2'><hr width='60%'></td></tr>";
        }
        $html.="</table>";
    }
    return $html;
}

function getNowPlaying($lastID) {
    //First do a little cleanup on the table if needed.
    $recentness = _conf("nowPlayingRecentness");
    $recentness = ($recentness > 0) ? $recentness : 60; //Preferences should be programmed to prohibit neg numbers, but hasn't been yet...
###    dosql("delete from nowPlaying where timestampdiff(MINUTE,startTime,CURRENT_TIMESTAMP)>$recentness");//Prune out old entries.
    dosql("delete from nowPlaying where date_add(startTime, interval $recentness minute)<now()"); //Prune out old entries.
    //Now fetch out the remaining.
    bldsql_init();
    bldsql_from("users u");
    bldsql_from("songs s");
    bldsql_from("nowPlaying np");
    bldsql_where("u.userID=np.userID");
    bldsql_where("s.songID=np.songID");
    bldsql_orderby("np.id desc");
    bldsql_col("u.userName");
    bldsql_col("s.songName");
    bldsql_col("(select alb.name from albums alb, albums_songs albs where alb.albumID=albs.albumID and albs.songID=s.songID) as albumName");
    bldsql_col("(select art.name from artists art, artists_songs arts where art.artistID=arts.artistID and arts.songID=s.songID) as artistName");
### timestampdiff unknown in sql prior v5
    /*    $timeCol="case  when timestampdiff(SECOND,np.startTime,now())=1 then concat(timestampdiff(SECOND,np.startTime,now()),' second ago')
      when timestampdiff(SECOND,np.startTime,now())<60 then concat(timestampdiff(SECOND,np.startTime,now()),' seconds ago')
      when timestampdiff(MINUTE,np.startTime,now())=1 then concat(timestampdiff(MINUTE,np.startTime,now()),' minute ago')
      when timestampdiff(MINUTE,np.startTime,now())<60 then concat(timestampdiff(MINUTE,np.startTime,now()),' minutes ago')
      when timestampdiff(HOUR,np.startTime,now())=1 then concat(timestampdiff(HOUR,np.startTime,now()),' hour ago')
      else concat(timestampdiff(HOUR,np.startTime,now()),' hours ago') end";
     */
    $timeCol = "case  when floor(time_to_sec(timediff((now()),np.startTime)))=1 then concat(floor(time_to_sec(timediff((now()),np.startTime))),' second ago')
                    when floor(time_to_sec(timediff((now()),np.startTime)))<60 then concat(floor(time_to_sec(timediff((now()),np.startTime))),' seconds ago')
                    when floor((time_to_sec(timediff((now()),np.startTime)))/60)=1 then concat(floor((time_to_sec(timediff((now()),np.startTime)))/60),' minute ago')
                    when floor((time_to_sec(timediff((now()),np.startTime)))/60)<60 then concat(floor((time_to_sec(timediff((now()),np.startTime)))/60),' minutes ago')
                    when floor((time_to_sec(timediff((now()),np.startTime)))/3600)=1 then concat(floor((time_to_sec(timediff((now()),np.startTime)))/3600),' hour ago')
                    else concat(floor((time_to_sec(timediff((now()),np.startTime)))/3600),' hours ago') end";
    bldsql_col($timeCol . " as time");
    bldsql_col("(select alb.albumID from albums alb, albums_songs albs where alb.albumID=albs.albumID and albs.songID=s.songID) as albumID");
    bldsql_col("(select art.artistID from artists art, artists_songs arts where art.artistID=arts.artistID and arts.songID=s.songID) as artistID");
    bldsql_col("s.songID");
    bldsql_col("np.id as npID");

    $limit = (_conf("numNowPlayingItems") > 0) ? _conf("numNowPlayingItems") : 10; //conf doesn't support number limits yet, so make reasonable

    $a = dosql(bldsql_cmd() . " limit " . $limit);
    if ($a) {
        extract($a);
        //array_reverse($npIDs,true);
        foreach ($npIDs as $i => $npID) {
            $jsNPIDs = j_appendToList($jsNPIDs, "'" . $npIDs[$i] . "'", ",");
            $jsTimes = j_appendToList($jsTimes, "'" . $times[$i] . "'", ",");
            if ($npID > $lastID) {//get the full text to insert for a row if the browser hasn't gotten it yet.  We could just send the ids and build it up there, but it's easier to do it here
                if (_conf("showAlbumArt")) {
                    if ($albumIDs[$i] != "") {//Use the song's album's art if this song is attached to an album.
                        $jsTDs.="td1Txts['" . $npID . "']=\"" . addslashes(getBrowseItemLink("album", $albumIDs[$i], "<img class='albumArtBorder' src='images.php?doWhat=getImage&type=albumArt&id=" . $albumIDs[$i] . "'>")) . "\";\n";
                    } else {//else use the song's  songArt (from tags) if there.
                        $jsTDs.="td1Txts['" . $npID . "']=\"" . addslashes(getBrowseItemLink("album", $albumIDs[$i], "<img class='albumArtBorder' src='images.php?doWhat=getImage&type=songArt&id=" . $songIDs[$i] . "'>")) . "\";\n";
                    }
                } else {
                    $jsTDs.="td1Txts['" . $npID . "']=\"\";\n";
                }
                $jsTDs.="td2Txts['" . $npID . "']=\"" . addslashes("<div class='np_song'>" . getAddToPlaylistLink("song", $songIDs[$i], false) . getPlayNowLink("song", $songIDs[$i], $songNames[$i]) . "</div><div class='np_album'>" . getBrowseItemLink("album", $albumIDs[$i], $albumNames[$i]) . "</div><div class='np_artist'>" . getBrowseItemLink("artist", $artistIDs[$i], $artistNames[$i]) . "</div><div class='np_user'>" . $userNames[$i] . " - <div class='np_time' id='np_" . $npIDs[$i] . "_time'>" . $times[$i] . "</div></div>") . "\";\n";
            }
        }
        $html = "<script language='JavaScript'>\n
                var IDs=new Array(" . $jsNPIDs . ");\n
                var times= new Array(" . $jsTimes . ");\n
                var td1Txts= new Object();\n
                var td2Txts= new Object();\n
                $jsTDs
                npData['IDs']=IDs;\n
                npData['times']=times;\n
                npData['td1Txts']=td1Txts;\n
                npData['td2Txts']=td2Txts;\n
                updateNowPlaying($limit);
            </script>";
    }
    return $html;
}

/* browsing stuff */

function getBrowsePage($type, $filter, $selectedID = "", $maxRows = 25, $letterFilter = true, $useHaystack = false) {/* Returns html for a nicely formatted alphabetic browse list.  
  This prints a title with any sub-ranges needed to fit the # rows
  into $maxRows and the tab browse object.
  It's assumed that the passed filters/ranges will limit the # rows sufficiently, ie that there won't be hundreds of
  items per filter.
  -If $selectedID is passed, then we ignore filter and set the filter to be appropriate for the passed id for the type.
  for example if type is artist and an id is passed for dave mathews, then filter will be set to D and dave will be selected.

  if $letterFilter is false then we do a matches (%$filter%). If true (default) the we do a starts with ($filter%).
  if $useHaystack=true, then we ignore any filter and join to the haystack table that caller is expected to have created and filled with matches

 */
    bldsql_init(); //For the content query

    $selectedTab = 0;
    $union = "";

    //build up a filter clause that below switches can use if they need to.  
    $nameCol = ($type == "genre") ? "description" : "name";
    //Now build up the content select query depending on the type.
    $selID = "";
    $selname = "";
    switch ($type) {
        case "artist":
            if ($selectedID) {//NEED to exclude from filters from below ! :(
                $filter = dosql("select " . getFilterLetterSelect($nameCol) . " from artists where artistID=$selectedID", 0);
                $selName = dosql("select name as selName from artists where artistID=$selectedID", 0);
            }
            bldsql_from("artists a");
            if ($useHaystack) {
                bldsql_from("haystack h");
                bldsql_where("h.id=a.artistID");
                bldsql_orderby("h.precedence desc");
            } elseif ($letterFilter) {
                switch ($filter) {
                    case "num":
                        bldsql_where("substring(lower(a." . $nameCol . "),1,1)<'a'");
                        break;
                    case "recent":
                        $maxID = dosql("select max(artistID) from artists", 0); //There can be gaps (and are on my server because of how many times I wiped and reloaded the db).
                        bldsql_where("a.artistID>" . floor(($maxID - _conf("numRandRecentAlbums"))));
                        bldsql_orderby("a.artistID desc");
                        break;
### Alain add select "all" case..break : not needed but...
                    case "all":
                        break;
                    default://contains
                        bldsql_where("lower(a." . $nameCol . ") like lower('" . $filter . "%')");
                        break;
                }
                //$whereFilter=($filter=="num")?"substring(lower(a.".$nameCol."),1,1)<'a'":"lower(a.".$nameCol.") like lower('".$filter."%')";
            }
            else
                bldsql_where("lower(a." . $nameCol . ") like lower('%" . $filter . "%')");
            bldsql_col("a.artistID as id");
            bldsql_col("a.name as name");
            bldsql_orderby("a.name");

            if (_conf("limitToArtistsWithFullAlbums")) {
                bldsql_from("albums_songs albs");
                bldsql_from("artists_songs arts");
                bldsql_where("arts.artistID=a.artistID");
                bldsql_where("arts.songID=albs.songID");
                bldsql_distinct();
            }
            if (_conf("artistSongThreshold")) {
                bldsql_where("(select count(*) from artists_songs where artistID=a.artistID)>" . _conf("artistSongThreshold"));
            }
            $doWhat = "getAlbumListForArtist"; //action for tab click
            $title = "Artists";
            //If we're doing a letter search, then attempt to highlight it.
            if ($letterFilter && !$useHaystack)
                $html.="<script language='JavaScript'>setBrowseFilterSelectedStyle('filter_" . $filter . "')</script>"; //note that the 3rd param follows syntax rules used by the getLetterSelects function below.

            break;

        case "album":
            if ($selectedID) {
                $filter = dosql("select " . getFilterLetterSelect($nameCol) . " from albums where albumID=$selectedID", 0);
                $selName = dosql("select name as selName from albums where albumID=$selectedID", 0);
                //var_dump($filter);
            }
            bldsql_from("albums a");

            if ($useHaystack) {
                bldsql_from("haystack h");
                bldsql_where("h.id=a.albumID");
                bldsql_orderby("h.precedence desc");
            } elseif ($letterFilter) {
                switch ($filter) {
                    case "num":
                        bldsql_where("substring(lower(a." . $nameCol . "),1,1)<'a'");
                        break;
                    case "recent":
                        $maxID = dosql("select max(albumID) from albums", 0); //There can be gaps (and are on my server because of how many times I wiped and reloaded the db).
                        bldsql_where("a.albumID>" . floor(($maxID - _conf("numRandRecentAlbums"))));
                        bldsql_orderby("a.albumID desc");
                        break;
### Alain add select "all" case..break : not needed but...
                    case "all":
                        break;
                    default:
                        bldsql_where("lower(a." . $nameCol . ") like lower('" . $filter . "%')");
                        break;
                }
            }
            else
                bldsql_where("lower(a." . $nameCol . ") like lower('%" . $filter . "%')");
            bldsql_col("a.albumID as id");
            bldsql_col("a.name as name");
            bldsql_orderby("a.name");
            $doWhat = "getAlbumDetail";
            $title = "Albums";
            //If we're doing a letter search, then attempt to highlight it.
            if ($letterFilter && !$useHaystack)
                $html.="<script language='JavaScript'>setBrowseFilterSelectedStyle('filter_" . $filter . "')</script>"; //note that the 3rd param follows syntax rules used by the getLetterSelects function below.
            break;
        case "noArtAlbums":
            bldsql_from("albums a");

            /* if($letterFilter){
              switch($filter){
              case "num":
              bldsql_where("substring(lower(a.".$nameCol."),1,1)<'a'");
              break;
              case "recent":
              $maxID=dosql("select max(albumID) from albums",0);//There can be gaps (and are on my server because of how many times I wiped and reloaded the db).
              bldsql_where("a.albumID>".floor(($maxID-_conf("numRandRecentAlbums"))));
              break;
              default:
              bldsql_where("lower(a.".$nameCol.") like lower('".$filter."%')");
              break;
              }
              }else bldsql_where("lower(a.".$nameCol.") like lower('%".$filter."%')");
             */
            bldsql_col("a.albumID as id");
            bldsql_col("a.name as name");
            bldsql_where("a.albumArtFile is null");
            bldsql_where("a.jpgImgData is null");
            bldsql_orderby("a.name");
            $doWhat = "getAlbumDetail";
            $title = "Albums";
            //If we're doing a letter search, then attempt to highlight it.
            if ($letterFilter && !$useHaystack)
                $html.="<script language='JavaScript'>setBrowseFilterSelectedStyle('filter_" . $filter . "')</script>"; //note that the 3rd param follows syntax rules used by the getLetterSelects function below.
            break;
        case "genre":
            if ($selectedID) {
                $selName = dosql("select description as selName from genres where genreID=$selectedID", 0);
            }
            bldsql_distinct();
            bldsql_from("genres g");
            //bldsql_where("lower(a.description) like lower('".$filter."%')");
            bldsql_col("g.genreID as id");
            bldsql_col("g.description as name");
            bldsql_orderby("g.description");
            switch (_conf("showWhatForGenre")) {
                case "artist"://Not programmed yet.
                    break;
                default: //album or garbage
                    $doWhat = "getAlbumsForGenre";
###                                bldsql_where("(select count(*) from albums_songs albs,genres_songs gens where gens.genreID=g.genreID and gens.songID=albs.songID group by albs.albumID order by count(*) desc limit 1)>="._conf("minGenreSongsInAlbumThreshold"));
                    bldsql_where("(select count(*) from albums_songs albs,genres_songs gens where gens.genreID=g.genreID and gens.songID=albs.songID group by albs.albumID  order by 'count(*)' desc limit 1)>=" . _conf("minGenreSongsInAlbumThreshold"));
            }
            $title = "Genres";
            break;
        case "playlist":
            if ($selectedID) {
                $selName = dosql("select name as selName from playlists where playlistID=$selectedID", 0);
            }
            bldsql_from("playlists p");
            bldsql_col("p.playlistID as id");
            bldsql_col("concat(p.name,case when p.public=1 then ' (public)' else '' end) as name");
            bldsql_orderby("case when p.userID=" . UL_UID . " then 0 else 1 end");
            bldsql_orderby("p.public");
            bldsql_orderby("p.name");
            bldsql_where("(p.userID=" . UL_UID . " or p.public=1)");
            $doWhat = "getPlaylistDetail";
            $title = "Playlists";
            break;
    }
    //var_dump(bldsql_cmd());
    $a = dosql(bldsql_cmd());

    if ($a) {
        extract($a);

        foreach ($ids as $i => $id) {
            $params[$i] = "doWhat=$doWhat&id=$id";
        }
        if ($selectedID) {
            $tab = array_search($selectedID, $ids);
            if ($tab !== false)
                $selectedTab = $tab;
            else {//add in because the selected got filtered out....
                if ($selname && $selectedID) {
                    $params[] = "doWhat=$doWhat&id=$selectedID";
                    $names[] = $selname;
                    $selectedTab = count($ids) - 1;
                }
            }
        }
        //var_dump($selectedTab);exit;
        $html.=getSideTabsArea("tabbedBrowseTable", "tabbedBrowseList", $names, "url", $params, $selectedTab, 0, 20);
    } elseif ($selectedID && $selName) {//No items found from the filters, but an id was passed.. make it a selection of 1
        $params[] = "doWhat=$doWhat&id=$selectedID";
        $names[] = $selname;
        $selectedTab = 0;
        $html.=getSideTabsArea("tabbedBrowseTable", "tabbedBrowseList", $names, "url", $params, $selectedTab, 0, 20);
    } else {//none found.
        $html = "<script language='JavaScript'>setDivHTML('tabbedBrowseList','" . _conf("lang_noneFound") . "');</script>";
    }
//getSideTabsArea($tableID,$divID,$tabNames,$url,$params,$selectedTab,$historyIndex,$maxRows=0,$textCutOffLen=25,$tabWidth=150){/*Returns a html side tab selector.
    return $html;
}

function getPlaylistDetail($playlistID) {

    bldsql_init();
    bldsql_from("playlists p");
    bldsql_where("p.playlistID=$playlistID");
    bldsql_where("(p.public=1 or p.userID=" . UL_UID . ")");
    bldsql_col("p.name as name");
    bldsql_col("p.public as public");
    bldsql_col("(select count(*) from playlistItems where playlistID=$playlistID) as objects");

    bldsql_col("p.userID");
    bldsql_col("u.userName");
    bldsql_from("users u");
    bldsql_where("u.userID=p.userID");

    bldsql_col("(select count(*) from playlistItems where playlistID=$playlistID and itemType='album') as numAlbums");
    bldsql_col("(select count(*) from playlistItems where playlistID=$playlistID and itemType='artist') as numArtists");
    bldsql_col("(select count(*) from playlistItems where playlistID=$playlistID and itemType='genre') as numGenres");

    bldsql_col("(select count(*) from playlistItems pi, albums_songs albs where pi.playlistID=$playlistID and pi.itemType='album' and albs.albumID=pi.itemID) as numAlbumSongs");
    bldsql_col("(select count(*) from playlistItems pi, artists_songs arts where pi.playlistID=$playlistID and pi.itemType='artist' and arts.artistID=pi.itemID) as numArtistSongs");
    bldsql_col("(select count(*) from playlistItems pi, genres_songs gens where pi.playlistID=$playlistID and pi.itemType='genre' and gens.genreID=pi.itemID) as numGenreSongs");

    bldsql_col("(select count(*) from playlistItems where playlistID=$playlistID and itemType='song') as numSongs");

    bldsql_col("(select sum(s.songLength) from playlistItems pi, albums_songs albs, songs s where albs.songID=s.songID and pi.playlistID=$playlistID and pi.itemType='album' and albs.albumID=pi.itemID) as timeAlbumSongs");
    bldsql_col("(select sum(s.songLength) from playlistItems pi, artists_songs arts,songs s where arts.songID=s.songID and pi.playlistID=$playlistID and pi.itemType='artist' and arts.artistID=pi.itemID) as timeArtistSongs");
    bldsql_col("(select sum(s.songLength) from playlistItems pi, genres_songs gens,songs s  where gens.songID=s.songID and pi.playlistID=$playlistID and pi.itemType='genre' and gens.genreID=pi.itemID) as timeGenreSongs");

    bldsql_col("(select sum(s.songLength) from playlistItems pi, songs s where pi.playlistID=$playlistID and pi.itemType='song' and pi.itemID=s.songID) as timeSongs");

//songLength
    $a = dosql(bldsql_cmd(), 1);
    if ($a) {
        extract($a);

        $html = "<table border='0' width='100%'><tr><td valign='top'><h3>$name";
        if ($userID != UL_UID)
            $html.="<span class='tiny'> ($userName)&nbsp;&nbsp;</span>";
        $html.="</h3>";
        $html.=getPlayNowLink("playlist", $playlistID) . " ";
        $html.=getPlayNowLink("playlistInOrder", $playlistID) . "<BR>";

        //download
        if (_conf("allowDownloads")) {
            $html.="<br>" . getDownloadLink($playlistID) . "<br><br>";
        }
        if ($public)
            $html.=_conf("lang_publicList");
        else
            $html.=_conf("lang_privateList");

        $html.="<br>$objects " . _conf("lang_itemsInPlaylist") . "<br>";
        $html.="<ul>";
        $html.="<li>$numAlbums " . _conf("lang_albums") . "</li>";
        $html.="<li>$numArtists " . _conf("lang_artists") . "</li>";
        $html.="<li>$numGenres " . _conf("lang_genres") . "</li>";
        $html.="<li>$numSongs " . _conf("lang_songs") . "</li>";
        $html.="</ul>";
        $html.=($numAlbumSongs + $numArtistSongs + $numGenreSongs + $numSongs) . " " . _conf("lang_totalSongs");

        $seconds = ($timeAlbumSongs + $timeArtistSongs + $timeGenreSongs + $timeSongs);
        $hours = floor($seconds / 3600);
        $min = floor(($seconds % 3600) / 60);
        $sec = ($seconds % 60);
        if ($seconds)
            $html.="<br>" . _conf("lang_totalSongTime") . ": $hours " . _conf("lang_hours") . " $min " . _conf("lang_minutes") . " $sec " . _conf("lang_seconds");

        if ($userID == UL_UID)
            $html.="<br><a href=\"javascript:loadPlaylist('$playlistID');\">" . _conf("lang_editPlaylist") . "</a>";
        $html.="</td><td valign='top'><h4>" . _conf("lang_playlistDetail") . "</h4>";
        $html.=sendPlaylistItems($playlistID);
        $html.="</td></tr></table>";
    }
    return $html;
}

function rf_getPlaylistDetail() {

    bldsql_init();
    bldsql_from("playlists p");
    bldsql_where("p.name='radioframa'");
    bldsql_where("(p.public=1 or p.userID=" . UL_UID . ")");
    bldsql_col("p.name as name");
    bldsql_col("p.playlistID");
    bldsql_col("p.public as public");
    bldsql_col("(select count(*) from playlistItems where playlistID=p.playlistID) as objects");

    bldsql_col("p.userID");
    bldsql_col("u.userName");
    bldsql_from("users u");
    bldsql_where("u.userID=p.userID");

    bldsql_col("(select count(*) from playlistItems where playlistID=p.playlistID and itemType='album') as numAlbums");
    bldsql_col("(select count(*) from playlistItems where playlistID=p.playlistID and itemType='artist') as numArtists");
    bldsql_col("(select count(*) from playlistItems where playlistID=p.playlistID and itemType='genre') as numGenres");

    bldsql_col("(select count(*) from playlistItems pi, albums_songs albs where pi.playlistID=p.playlistID and pi.itemType='album' and albs.albumID=pi.itemID) as numAlbumSongs");
    bldsql_col("(select count(*) from playlistItems pi, artists_songs arts where pi.playlistID=p.playlistID and pi.itemType='artist' and arts.artistID=pi.itemID) as numArtistSongs");
    bldsql_col("(select count(*) from playlistItems pi, genres_songs gens where pi.playlistID=p.playlistID and pi.itemType='genre' and gens.genreID=pi.itemID) as numGenreSongs");

    bldsql_col("(select count(*) from playlistItems where playlistID=p.playlistID and itemType='song') as numSongs");

    bldsql_col("(select sum(s.songLength) from playlistItems pi, albums_songs albs, songs s where albs.songID=s.songID and pi.playlistID=p.playlistID and pi.itemType='album' and albs.albumID=pi.itemID) as timeAlbumSongs");
    bldsql_col("(select sum(s.songLength) from playlistItems pi, artists_songs arts,songs s where arts.songID=s.songID and pi.playlistID=p.playlistID and pi.itemType='artist' and arts.artistID=pi.itemID) as timeArtistSongs");
    bldsql_col("(select sum(s.songLength) from playlistItems pi, genres_songs gens,songs s  where gens.songID=s.songID and pi.playlistID=p.playlistID and pi.itemType='genre' and gens.genreID=pi.itemID) as timeGenreSongs");

    bldsql_col("(select sum(s.songLength) from playlistItems pi, songs s where pi.playlistID=p.playlistID and pi.itemType='song' and pi.itemID=s.songID) as timeSongs");

//songLength
    $a = dosql(bldsql_cmd(), 1);
    if ($a) {
        extract($a);

        $html = "<table border='0' width='100%'><tr><td valign='top'><h3>Playlist";
        if ($userID != UL_UID)
            $html.="<span class='tiny'> ($userName)&nbsp;&nbsp;</span>";
        $html.="</h3>";
        // $html.=getPlayNowLink("playlist",$playlistID)." ";
        //$html.=getPlayNowLink("playlistInOrder",$playlistID)."<BR>";
        //download
        /* if(_conf("allowDownloads")){
          $html.="<br>".getDownloadLink($playlistID)."<br><br>";
          }
          if($public)$html.=_conf("lang_publicList");
          else $html.=_conf("lang_privateList");

          $html.="<br>$objects "._conf("lang_itemsInPlaylist")."<br>";
          $html.="<ul>";
          $html.="<li>$numAlbums "._conf("lang_albums")."</li>";
          $html.="<li>$numArtists "._conf("lang_artists")."</li>";
          $html.="<li>$numGenres "._conf("lang_genres")."</li>";
          $html.="<li>$numSongs "._conf("lang_songs")."</li>";
          $html.="</ul>";
          $html.=($numAlbumSongs+$numArtistSongs+$numGenreSongs+$numSongs)." "._conf("lang_totalSongs");

          $seconds=($timeAlbumSongs+$timeArtistSongs+$timeGenreSongs+$timeSongs);
          $hours=floor($seconds/3600);
          $min=floor(($seconds%3600)/60);
          $sec=($seconds%60);
          if($seconds)$html.="<br>"._conf("lang_totalSongTime").": $hours "._conf("lang_hours")." $min "._conf("lang_minutes")." $sec "._conf("lang_seconds");
         */
        //if($userID==UL_UID)$html.="<br><a href=\"javascript:loadPlaylist('$playlistID');\">"._conf("lang_editPlaylist")."</a>";
        //$html.="</td><td valign='top'><h4>"._conf("lang_playlistDetail")."</h4>";
        $html.=sendPlaylistItems($playlistID);
        $html.="</td></tr></table>";
    }
    return $html;
}

function sendItemList($ids, $names, $destDivID, $jsMethod, $ids2 = false, $names2 = false, $ids3 = false, $names3 = false) {//helper to do the js voodoo to send out a list of items (songs, albums...)
    /* Sets up the js to create the list of songs or albums..
      Requires the js object to exist already on the page (var xxxListData = new Object();)
      and jsFuncs.js to be linked in.
      $jsMethod is method to use to load the arrays into html, like loadAlbumList()...
      Each pair if id/names needs to be same size (ids=names).  2 and 3 are optional
     */
    $jsNames2 = "";
    $jsIDs2 = "";
    $jsNames3 = "";
    $jsIDs3 = "";
    foreach ($ids as $i => $id) {
        $jsNames = j_appendToList($jsNames, "'" . addslashes($names[$i]) . "'", ",");
        $jsIDs = j_appendToList($jsIDs, "'" . $id . "'", ",");
        if ($ids2) {
            $jsNames2 = j_appendToList($jsNames2, "'" . addslashes($names2[$i]) . "'", ",");
            $jsIDs2 = j_appendToList($jsIDs2, "'" . $ids2[$i] . "'", ",");
        }
        if ($ids3) {
            $jsNames3 = j_appendToList($jsNames3, "'" . addslashes($names3[$i]) . "'", ",");
            $jsIDs3 = j_appendToList($jsIDs3, "'" . $ids3[$i] . "'", ",");
        }
    }
    $jsIDs = "var IDs=new Array(" . $jsIDs . ");";
    $jsNames = "var names=new Array(" . $jsNames . ");";
    $jsIDs2 = "var IDs2=new Array(" . $jsIDs2 . ");";
    $jsNames2 = "var names2=new Array(" . $jsNames2 . ");";
    $jsIDs3 = "var IDs3=new Array(" . $jsIDs3 . ");";
    $jsNames3 = "var names3=new Array(" . $jsNames3 . ");";

    $html = "\n<script language='JavaScript'>\n $jsIDs \n $jsNames2 \n $jsIDs2 \n $jsNames \n $jsIDs3 \n $jsNames3 \n 
            listData['" . $destDivID . "_IDs']=IDs;
            listData['" . $destDivID . "_names']=names;
            listData['" . $destDivID . "_IDs2']=IDs2;
            listData['" . $destDivID . "_names2']=names2;
            listData['" . $destDivID . "_IDs3']=IDs3;
            listData['" . $destDivID . "_names3']=names3;
            $jsMethod('$destDivID');
            </script>";
    //log_message($html);exit;
    return $html;
}

function sendPlaylistItems($playlistID) {

    $sql = "  select concat(a.name,' <span class=\"tiny\">(" . _conf("lang_albumby") . ": ',
            case when (select count(distinct(arts.artistID)) from albums_songs albs, artists_songs arts where albs.albumID=a.albumID and albs.songID=arts.songID)>1 then '" . _conf("lang_variousArtists") . "' else
            (select max(art.name) from artists art, albums_songs albs, artists_songs arts where albs.albumID=a.albumID and albs.songID=arts.songID and arts.artistID=art.artistID) end,')</span>') as name
            from albums a,playlistItems pi where pi.itemType='album' and pi.itemID=a.albumID and pi.playlistId=$playlistID ";


    $sql.="union select concat(a.name,' <span class=\"tiny\">(" . _conf("lang_artist") . ")</span>') as name from artists a,playlistItems pi where pi.itemType='artist' and pi.itemID=a.artistID and pi.playlistId=$playlistID ";
    $sql.="union select concat(a.description,' <span class=\"tiny\">(" . _conf("lang_genre") . ")</span>') as name from genres a,playlistItems pi where pi.itemType='genre' and pi.itemID=a.genreID and pi.playlistId=$playlistID ";

    $sql.="union select concat(a.songName,' <span class=\"tiny\">(" . _conf("lang_songby") . ": ',
                case when (select count(distinct(arts.artistID)) from artists_songs arts where arts.songID=a.songID)=0 then '" . _conf("lang_unknownArtist") . "' else 
                (select art.name from artists art, artists_songs arts where arts.songID=a.songID and arts.artistID=art.artistID) end, ')</span>') as name
            from songs a,playlistItems pi where pi.itemType='song' and pi.itemID=a.songID and pi.playlistId=$playlistID ";


    $a = dosql($sql);

    if ($a) {
        extract($a);
        $html.="<div class='playlistDetailDiv' style='width:100%'><ul>";
        foreach ($names as $name) {
            $html.="<li>$name";
        }
        $html.="</ul></div>";
    }
    return $html;
}

function getUnattachedSongList($artistID, $destDivID) {//returns a self-loading div of an artist's 'unattached' songs.
    bldsql_init();
    bldsql_col("s.songID as id");
    bldsql_col("s.songName");
    bldsql_col("s.songLength as length");
    bldsql_col("s.bitRate");
    bldsql_from("songs s left join albums_songs albs on s.songID=albs.songID ");
    bldsql_where("albs.songID is null");

    bldsql_from("artists_songs arts");
    bldsql_where("arts.songID=s.songID");
    bldsql_where("arts.artistID=$artistID");

    bldsql_orderby("s.songName");
    $html = "";
    $a = dosql(bldsql_cmd());
    if ($a) {
        extract($a);
        foreach ($ids as $i => $id) {
            $name = "<b>" . $songNames[$i] . "</b>";
            if ($lengths[$i])
                $name.=" (" . util_formatSongLength($lengths[$i]) . ") ";
            if ($bitRates[$i])
                $name.=" - " . util_formatBitRate($bitRates[$i]) . " ";
            $names[] = $name;
        }

        $html = sendItemList($ids, $names, $destDivID, "loadSongList");
    }
    return $html;
}

/* Util */

function util_formatBitRate($rate) {
    $rate = ($rate / 1000) . "kbps";
    return $rate;
}

function util_formatSongLength($length) {
    $min = floor($length / 60);
    $sec = floor($length - ($min * 60));
    if ($sec < 10)
        $sec = "0" . $sec;
    return $min . ":" . $sec;
}

function getBrowseItemLink($type, $selectedID, $itemName, $className = "browseItem") {
    $html = "<a href=\"javascript:browseItem('$type','$selectedID');\"><div class='$className'>$itemName</div></a>";
    return $html;
}

function getDownloadLink($playListID) {
    $html = "<a href='" . $_SERVER['PHP_SELF'] . "?doWhat=downloadPlaylist&id=$playListID'><img src='" . _conf("skinDir") . "/images/icons/download-arrow-grey.jpg' width='15' height='15' border='0' alt='' class='icon'><div class='playNow'>" . _conf("lang_download") . "</div></a>";
    return $html;
}

function getPlayNowLink($type, $id, $altText = "") {//Returns the link to play object now.. type can be song, album, artist, playlist or genre
    $play = ($altText) ? $altText : _conf("lang_play");

    switch ($type) {
        case "song":
            $doWhat = "playSong";
            break;
        case "album":
            $doWhat = "playAlbum";
            break;
        case "artist":
            $doWhat = "playArtist";
            break;
        case "playlist":
            $doWhat = "playPlaylist";
            break;
        case "playlistInOrder":
            $doWhat = "playPlaylistInOrder";
            $play = ($altText) ? $altText : _conf("lang_playInOrder");
            break;
        case "genre":
            $doWhat = "playGenre";
            break;
    }


    $play = "<div class='playNow'><img src='" . _conf("skinDir") . "/images/icons/play_sm.jpg' width='15' height='15' border='0' alt='' class='icon'> $play</div>";

    if (_conf("playMethod") >= 1) {//need to set up the embedded flash player
        $html = "<a href='javascript:openFlashPlayer(\"" . $doWhat . "\",\"" . $id . "\");'>$play</a>";
    }
    else
        $html = "<a href='" . $_SERVER['PHP_SELF'] . "?doWhat=" . $doWhat . "&id=$id'>$play</a>";
    if (_conf("isDemoSystem"))
        $html = $play;
    return $html;
}

function getAddToPlaylistLink($type, $id, $showText = true) {
    $t = ($showText) ? _conf("lang_addToPlaylist") : "";
    $t = "<div class='addToPlayList'><img src='" . _conf("skinDir") . "/images/icons/add.gif' width='15' height='15' border='0' alt='' class='icon'> $t</div>";
    $html = "<a href=\"javascript:addToPlaylist('" . $type . "','" . $id . "');\">$t</a>";
    if (_conf("isDemoSystem"))
        $html = $t;
    return $html;
}

function getLetterSelects($type, $selected) {
    /* Returns a list of letters that open a browse page for the passed type for the clicked letter
      Types allowed are 'artist', 'album' & 'genre'
     */
    bldsql_init();
    $name = "a.name";
    switch ($type) {
        case "artist":
            bldsql_from("artists a");
            break;
        case "album":
            bldsql_from("albums a");
            break;
        case "genre":
            bldsql_from("genres a");
            $name = "a.description";
            break;
    }
    bldsql_distinct();
    bldsql_col(getFilterLetterSelect($name));
    bldsql_orderby($name);
    $a = dosql(bldsql_cmd());
    $html = "<table class='alphabet'><tr>";
    if ($a) {
        extract($a);
### Alain ajout All
        $html.="<td onClick=\"browseFilterClicked('$type','all','filter_all')\" class='divAsAHref' onMouseOut=\"this.className='divAsAHref'\" onMouseOver=\"this.className='divAsAHrefHover'\"><div id='filter_all' class='alphabet'>&nbsp;" . _conf("lang_all") . "</div></td>";
        foreach ($letters as $letter) {
            $filter = $letter;
            if ($letter == '#')
                $filter = 'num';
            $html.="<td onClick=\"browseFilterClicked('$type','$filter','filter_" . $filter . "')\" class='divAsAHref' onMouseOut=\"this.className='divAsAHref'\" onMouseOver=\"this.className='divAsAHrefHover'\"><div id='filter_" . $filter . "' class='alphabet'>$letter</div></td>";
        }
        $html.="<td onClick=\"browseFilterClicked('$type','recent','filter_recent')\" class='divAsAHref' onMouseOut=\"this.className='divAsAHref'\" onMouseOver=\"this.className='divAsAHrefHover'\"><div id='filter_recent' class='alphabet'>&nbsp;" . _conf("lang_new") . "</div></td>";
    }else {//nothing found!?
        $html.="<td>" . _conf("noSongs") . "</td>";
    }
    $html.="</tr></table>";
    return $html;
}

function getFilterLetterSelect($nameCol) {
    return "case when substring(lower($nameCol),1,1)<'a' then '#' else substring(upper($nameCol),1,1) end as letter";
}

function getHREFlink($doWhat, $params, $text, $destDiv, $class = "") {
    $text = ($class != "") ? "<div class='$class'>$text</div>" : $text;
    return "<a href=\"JavaScript:loadDivArea('$destDiv','$doWhat','$params');\">$text</a>";
}

function getSetDivHTMLLink($destDiv, $html, $linkText) {//Shouldn't be used for complicated or large html, just a quick messeage or a clear.
    return "<a href=\"JavaScript:setDivHTML('$destDiv','" . addslashes($html) . "');\">$linkText</a>";
}

function getSelfLoadingDivHTML($divID, $doWhat, $params) {//returns html for a self-loading div that will use $doWhat and params to fetch content.  Advantage of doing this is that it will load async so you can do several at once.
    $html = "<div id='$divID' name='$divID'><script language='JavaScript'>loadDivArea('$divID','$doWhat','$params');</script></div>";
    return $html;
}

function sendStatusMssgHTML($mssg) {
    return "<script language='JavaScript'>statusMssg(\"" . addslashes($mssg) . "\");</script>";
}

function sendAlertMssgHTML($mssg) {
    return "<script language='JavaScript'>alertMssg(\"" . addslashes($mssg) . "\");</script>";
}

function getStoredSessionData($key) {
    //returns passed key's data stored for this user's db level session
    //Returns false if none found or it's mangled.
    $ret = false;
    $dbSess = dosql("select storedSessionData_hide from users where userID=" . UL_UID, 0);
    if ($dbSess) {
        $dbSess = unserialize($dbSess);
        if (isset($dbSess[$key]))
            $ret = $dbSess[$key];
    }
    return $ret;
}

function setStoredSessionData($key, $value) {/* save some data into the db level session for this user.  Key is a string, value can be any php object (array, string,num...) */
    if (UL_UID) {
        $a = dosql("select storedSessionData_hide from users where userID=" . UL_UID, 0);
        if ($a !== false) {//Only skip if there was an error, but set the value even if there are no stored prefs yet.
            $dbSess = unserialize($a);
            $dbSess[$key] = $value;
            $a = dosql("update users set storedSessionData_hide='" . scrubTextForDB(serialize($dbSess)) . "' where userID=" . UL_UID);
        }
    }
}

function setLastSelectedBrowseItem($type, $id) {
    /* Wrapper to set the last selected browse item.  Kinda like the name says. */
    setStoredSessionData("lastSelected_" . $type, $id);
}

function getLastSelectedBrowseItem($type) {
    return getStoredSessionData("lastSelected_" . $type);
}

function getSearchLink($searchType, $artistName = "", $albumName = "") {
    /* $searchType:1 for google link thru to wikipedia (I'm feelin luck)
      2 for same but with album art search text
      3 google art search
     */
    $url = "";
    $queryStr = rtrim($artistName . " " . $albumName); //quotes caused problems on multi-album searches.
    $queryStr = urlencode($queryStr); //convert embedded ampersands and such...
    $queryStr = str_replace(" ", "+", $queryStr); //all the search engines seem to use this syntax
    //var_dump($queryStr);
    $label = "";
    $image = "";
    //$googleURL="http://"._conf("lang_google_url")."/#hl="._conf("lang_google_lang");
    $googleURL = "http://" . _conf("lang_google_url") . "/search?hl=" . _conf("lang_google_lang") . "&btnI=I'm+Feeling+Lucky"; //This syntax seems a bit more documented.
    switch ($searchType) {
        case 1://google i'm feelin lucky search of wikipedia
            $queryStr.="+site:" . _conf("lang_wikipedia_url");
            $url = $googleURL . "&q=" . $queryStr;
            $label = "Wikipedia";
            $image = "<img class='icon' src='" . _conf("skinDir") . "/images/icons/Wikipedia-globe-icon_sm.jpg' width='15' height='15' border='0' alt=''>";
            break;
        case 2://google i'm feelin lucky search of wikipedia with slightly different text...
            $queryStr.="+site:" . _conf("lang_wikipedia_url");
            $url = $googleURL . "&q=" . $queryStr;
            $label = _conf("lang_config_art_search_wikipedia");
            $image = "<img class='icon' src='" . _conf("skinDir") . "/images/icons/Wikipedia-globe-icon_sm.jpg' width='15' height='15' border='0' alt=''>";
            break;
        case 3://google image search
            $url = "http://" . _conf("lang_google_images_url") . "/images?hl=" . _conf("lang_google_lang") . "&q=" . $queryStr . "&um=1&sa=N&tab=wi";
            $label = _conf("lang_config_art_search_google");
            $image = "<img class='icon' src='http://images.google.com/favicon.ico' width='15' height='15' border='0' alt=''>";
            break;
        case 4://google i'm feelin lucky search of amazon
            $queryStr.="+site:" . _conf("lang_config_amazon_url");
            $url = $googleURL . "&q=" . $queryStr;
            $label = _conf("lang_config_art_search_amazon");
            $image = "<img class='icon' src='http://www.amazon.com/favicon.ico' width='15' height='15' border='0' alt=''>";
            break;
        case 5://yahoo image search
            //http://images.search.yahoo.com/search/images;_ylt=A9G_bHJX_8tKe1oBCyuLuLkF?p=the+doors+an+american+prayer&ei=utf-8&iscqry=&fr=sfp
            $url = "http://images.search.yahoo.com/search/images?p=" . $queryStr;
            $label = _conf("lang_config_art_search_yahoo");
            $image = "<img class='icon' src='http://images.search.yahoo.com/favicon.ico' width='15' height='15' border='0' alt=''>";
            break;
        case 6://bing image search
            //http://www.bing.com/images/search?q=the+doors+an+american+prayer&go=&form=QBIR&qs=n
            $url = "http://www.bing.com/images/search?q=" . $queryStr;
            $label = _conf("lang_config_art_search_bing");
            $image = "<img class='icon' src='http://www.bing.com/favicon.ico' width='15' height='15' border='0' alt=''>";
            break;
    }

    if ($url)
        $url = "<a href=\"" . $url . "\" target='_new'>$image<div class='search'>$label</div></a>";
    return $url;
}

?>
