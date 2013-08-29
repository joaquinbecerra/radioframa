/*JS to handle click/navigation events.  These methods assume that url has been set prior to being called.*/

function loadDivArea(divName,doWhat,params){//name of divID,action, any optional params in name=value[&name2=value2[&...]]
    ajax_getUrl(url,"doWhat="+doWhat+"&"+params,divName);
}

function openFlashPlayer(doWhatNext,id){
    loadDivArea("flashPlayerDiv","openFlashPlayer","doWhatNext="+doWhatNext+"&id="+id);
}
var menu_selectedItemID="menu_home";//defaults to the first loaded page.
function menu_ItemSelected(selectedID,doWhat,params,includeLetterList){
    
	//Set the selectedness of the menu picks.
        if(menu_selectedItemID!="")setDivIDClass(menu_selectedItemID,"menu_unselected");
        setDivIDClass(selectedID,"menu_selected");
        menu_selectedItemID=selectedID;

	//Clear out all display areas and then refill with proper content
	clearNowPlaying();
	clearRandomBrowseingListArea();
	setDivHTML("mainContentAreaDiv","");
	setDivHTML("letterBrowseSelects","");
	setDivHTML("tabbedBrowseList","");

        if(doWhat=="browse"){//We'll actually split this into 2 concurrent calls
	    //Clear out the home screen areas if filled.
	    if(includeLetterList==1){
		    ajax_getUrl(url,"doWhat=getLetterSelects&"+params,"letterBrowseSelects",'get',0);
	    }
	    
	    ajax_getUrl(url,"doWhat=getBrowsePage&"+params,"jsDiv",'get',0);
	    
	}else if(doWhat=="home"){
	    loadRandomBrowseingListArea();
	    startNowPlaying();
        }else{
                ajax_getUrl(url,"doWhat="+doWhat+"&"+params,"mainContentAreaDiv",'get',0);
        }
}
function loadNoArtAlbums(){//Load the list of albums with no art... hope there's not so many that it blows up the js tab browse obj :)
    setDivHTML("letterBrowseSelects","");
    setDivHTML("mainContentAreaDiv","");
    ajax_getUrl(url,"doWhat=getBrowsePage&type=noArtAlbums","jsDiv",'get',0);
}
function doSearch(){
    form=document.getElementById("searchBox");
    if(form){
        if(form.search_val.value!=""){
            params=ajax_getFormElementParams(form);
            menu_ItemSelected("","doSearch",params,false);
        }
    }
}
function importAlbumArt(){
    form=document.getElementById("albumArtImportForm");
    if(form){
        if(form.albumArtURL.value!=""){
            params=ajax_getFormElementParams(form);
            ajax_getUrl(url,"doWhat=submitAlbumArtURL&"+params,"jsDiv","post",-1);
        }else alert("No url?!");
    }
}
var filter_selectedLetterDiv="";
function browseFilterClicked(type,filter,letterDiv){

//### Alain &filter seems a reserved word in Ajax
//###    ajax_getUrl(url,"doWhat=getBrowsePage&type="+type+"&filter="+filter,'jsDiv','get',0);
    ajax_getUrl(url,"doWhat=getBrowsePage&type="+type+"&filtering="+filter,'jsDiv','get',0);
//### Alain end

    //setBrowseFilterSelectedStyle(letterDiv);
}
function browseItem(type,id){//Pretend this was a menu pick, then just add in the selected id.
    menuItem="menu_"+type+"s";//Note this works because the menu items are named to support this...
    menu_ItemSelected(menuItem,"browse","type="+type+"&id="+id,1);
}
function setBrowseFilterSelectedStyle(letterDiv){
    div=document.getElementById(letterDiv);
    if(div){
        if(filter_selectedLetterDiv!=""){
            oldDiv=document.getElementById(filter_selectedLetterDiv);
            if(oldDiv){
                oldDiv.className="alphabet";
            }
        }
        div.className='alphabet_selected';
        
        filter_selectedLetterDiv=letterDiv;
    }
}
function updateConfigs(configID,displayType,passedUID){
 	inp=document.getElementById(configID+"_config");
	if(inp){
                if(displayType=="text"){
                    var currVal=inp.value;
                }else{//select
                    var currVal=inp.options[inp.selectedIndex].value;
                }
		setDivHTML(configID+"_cofig_status",_conf("lang_saving"));
		loadDivArea("jsDiv","updateConfig","id="+configID+"&value="+currVal+"&uid="+passedUID);
	}
}
function showHideAlbumSongList(albumID,showText,hideText,divID){//Toggles the song list for an album when albums are in list display
    div=document.getElementById(albumID+"_showAblumDetailDiv");
    if(div){
        wrapperDiv=document.getElementById(albumID+"_songListWrapper");
        if(wrapperDiv){
            if(wrapperDiv.innerHTML==""){//link should be to show details
                loadDivArea(albumID+"_songListWrapper","showAlbumSongs","id="+albumID);
                div.innerHTML=hideText;//"<a href=\"JavaScript:showHideAlbumSongList("+albumID+",'"+showText+"','"+hideText+"','"+albumID+"_songListWrapper"+"');\">"+hideText+"</a>";
            }else{//hide 
                wrapperDiv.innerHTML="";
                div.innerHTML=showText;//"<a href=\"JavaScript:showHideAlbumSongList("+albumID+",'"+showText+"','"+hideText+"','"+albumID+"_songListWrapper"+"');\">"+showText+"</a>";
            }
        }
    }
}

//Playlist Functions
function reloadPlaylists(playlistID){
    loadPlaylist(playlistID);
    menu_ItemSelected("menu_playlists","browse","type=playlist&id="+playlistID,0);//reload select list.
}
function loadPlaylist(playlistID){
    loadDivArea("playlistEditDiv","loadPlaylist","playlistID="+playlistID);
}
function deletePlaylistItem(seqNum){//send call to server to remove item.
    loadDivArea("jsDiv","deletePlaylistItem","id="+seqNum);
}
function removePlaylistItem(itemSeq){//Actually remove item from playlist display... this is called by server scripts.  This is slightly complicated because we need to figure out which row contains this itemSeq
    tbl=document.getElementById("playlistItems");
    if(tbl){
        var index=-1;
        for(var i=0;i<tbl.rows.length;i++){
            if(tbl.rows[i].id==itemSeq){
                index=i;
            }
        }
        if(index>=0){
            tbl.deleteRow(index);
        }else{//just incase we couldn't find it and there was somesort of display sync error.
            alert(_conf("lang_playlistDeleteError"));
        }
    }
}
function addToPlaylist(type,id){
    /*adds passed item to the current playlist.  PlaylistID can be blank which means it's a 'temp' playlist.*/
    div=document.getElementById("playlistEditDiv");
    if(div){
        if(div.innerHTML==""){//Send output directly to the dest div so it can load the basic table and functions and stuff...
            loadDivArea("playlistEditDiv","loadPlaylist","playlistID=&type="+type+"&id="+id);//pass type and Id if passed to add at the same time.
        }else{//div has already been set up, so send to jsDiv so it can update this item dynamically.
            loadDivArea("jsDiv","addToPlaylist","type="+type+"&id="+id);
        }
        
    }
}
function deletePlaylist(playlistID){
    if(confirm(_conf("lang_reallyDelete"))){
        loadDivArea("playlistEditDiv","deletePlaylist","id="+playlistID);
    }
}
function clearDeletedPlaylist(){
    //reset the playlist areas after deletion
    setDivHTML("playlistEditDiv","");
    menu_ItemSelected("menu_playlists","browse","type=playlist",0);//reload select list.
}
function savePlaylist(formObj){
    name=document.getElementById("pl_name");
    if(name){
        if(name.value==""){
            noNameEntered=_conf("lang_playlistNoNameError");
            alert(noNameEntered);
        }else{
            params=ajax_getFormElementParams(formObj);
            loadDivArea("playlistEditDiv","savePlaylist",params);
        }
    }
    
}
function devAdmin_submitSQLGenForm(formObj){
    params=ajax_getFormElementParams(formObj);
    loadDivArea("devAdmin_sqlOutput","devAdmin_genConfigInsert",params);
}
function devAdmin_insertConfig(formObj){
    params=ajax_getFormElementParams(formObj);
    loadDivArea("devAdmin_sqlOutput","devAdmin_insertConfig",params);
}