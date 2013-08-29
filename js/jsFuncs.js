

var listData = new Object();//Used to display lists of songs below an album.
function setFocus(input){
	inp=document.getElementById(input);
	if(inp){
		inp.focus();
	}
}
function loadRandomBrowseingListArea(){
	loadDivArea('randomBrowseListDiv','getRandomBrowsingTabObj','');
	setDivHTML("randomBrowseTitleDiv",_conf('lang_randomAlbumsHeader'));
	setDivHTML("randomRefreshInstructDiv",_conf("lang_refreshTab"));
}
function clearRandomBrowseingListArea(){
	setDivHTML("randomBrowseTitleDiv","");
	setDivHTML("randomRefreshInstructDiv","");
	setDivHTML("randomBrowseListDiv","");
}
function loadSongList(destDivID){
    div=document.getElementById(destDivID);
    if(div){
        //fetch the data arrays from the obj holder
        songIDs=listData[destDivID+"_IDs"];
        songNames=listData[destDivID+"_names"];
        //Fetch the artist and genre info, if passed;
        artistIDs=listData[destDivID+"_IDs2"];
        artistNames=listData[destDivID+"_names2"];
        albumIDs=listData[destDivID+"_IDs3"];
        albumNames=listData[destDivID+"_names3"];
        includeArtists=(artistIDs.length>0);
        includeAlbums=(albumIDs.length>0);
  
        //Wipe out all from the obj holder so it doesn't get huge.
        listData[destDivID+"_IDs"]="";
        listData[destDivID+"_names"]="";
        listData[destDivID+"_IDs2"]="";
        listData[destDivID+"_names2"]="";
        listData[destDivID+"_IDs3"]="";
        listData[destDivID+"_names3"]="";
        
        setDivHTML(destDivID,"");//clear out whatever may be there now.
        
        var tbl=document.createElement("table");
        tbl.className="songList_table";
        tbl.cellSpacing="0";

        for(var i=songIDs.length-1;i>=0;i--){
            tbl.insertRow(0);
            tbl.rows[0].insertCell(0);
            
            html=getPlayNowLink("song",songIDs[i])+" "+songNames[i];
            if(includeArtists)html=html+" "+getBrowseItemLink('artist',artistIDs[i],artistNames[i]);
            if(includeAlbums)html=html+" "+getBrowseItemLink('album',albumIDs[i],albumNames[i]);
            html=html+" "+getAddToPlayListLink("song",songIDs[i]);
            tbl.rows[0].cells[0].innerHTML=html;
        }
        div.appendChild(tbl);
    }    
}
var np_dynamicIntervalCounter=0;

function refreshNowPlaying(isFirst){//If is first then we reset any dynamic timer settings.
    div=document.getElementById("np_BorderDiv");
    if(div){//is div still displayed on screen and waiting to be filled.?
        var i=_conf('nowPlayingRefreshInterval');
	if(i=="d1" || i=="d2"){//set for dynamic interval
		if(isFirst==1){//reset.  Could be from first load or clicking 'home'
			np_dynamicIntervalCounter=0;
		}
		np_dynamicIntervalCounter++;
		interval=getNPDynamicInterval(np_dynamicIntervalCounter,i);
	}else{interval=i;}
	interval=interval*1000;//convert to seconds
        loadDivArea("jsDiv","nowPlaying","id="+np_lastID);
	if(!(_conf("isDemoSystem"))) setTimeout("refreshNowPlaying(0)",interval);

    }else{np_lastID=0;}//This will reset for next time nowplaying is displayed.
}
function startNowPlaying(){//Start up the now playing widget
	np_lastID=0;
	setDivHTML("nowPlayingTitleDiv",_conf('lang_nowPlaying'));
	setDivHTML("nowPlayingDiv","<div class='np_BorderDiv' id='np_BorderDiv'></div>");
	setTimeout('refreshNowPlaying(1)',1000);	
}
function clearNowPlaying(){//Clear the now playing area and quit the refreshes
	setDivHTML("nowPlayingDiv","");//note this clears out the np_BorderDiv that sits inside it.
	setDivHTML("nowPlayingTitleDiv","");
}
function getNPDynamicInterval(count,dtype){//set a dynamic interval for refreshing the now playing thingy.
	//the basic idea is to get progressively slower the longer the page sits there because it's increasingly
	//likely the user isn't looking anyway.  We'll do the first bunch rapidly so it looks cool.
	//If the user refreshes or goes back to the home page from somewhere else, this gets reset.
	//The algorithm is completely arbitrary.
	var step=0;
	if(count<20){step=1;}
	else if(count<40){step=2;}
	else if(count<60){step=3;}
	else {step=4;}
	switch (step){
		case 1:
			i=5;
			break;
		case 2:
			i=15;
			break;
		case 3:
			i=30;
			break;
		default:
			i=60;
			break;			
	}
	if(dtype=="d2"){//less server load option, adjust up
		i=i*2;
	}
	return i;
}
var npData = new Object();
var np_lastID=0;
function updateNowPlaying(maxRows){//This is called by server after refreshNowPlaying is called to actually update/init the display.
    npDiv=document.getElementById("np_BorderDiv");
    if(npDiv){//make sure still there...
        IDs=npData['IDs'];
        times=npData['times'];
        td1Txts=npData['td1Txts'];
        td2Txts=npData['td2Txts'];
        tbl=document.getElementById("npTable");
        if(!tbl){
            var tbl=document.createElement("table");
            tbl.className="np_table";
            tbl.id="npTable";
            npDiv.appendChild(tbl);
        }
        for(var i=IDs.length-1;i>=0;i--){//iterate thru backwards, inserting new rows into the top in descending order.
            id=IDs[i];
            if(id>np_lastID){//new
                np_lastID=id;//remember the last row added.
                var row=tbl.insertRow(0);
                row.id="np_"+id+"_row";
                row.insertCell(0);
                row.insertCell(1);
                row.cells[0].innerHTML=td1Txts[id];
                row.cells[1].className="np_dataTD";
                row.cells[1].innerHTML=td2Txts[id];
            }
        }
        if(tbl.rows.length==0){//insert a dummy row so the border shows up.
            row=tbl.insertRow(0);
            row.id='npFiller';
            cell=row.insertCell(0);
            cell.colSpan=2;
            cell.innerHTML="&nbsp;";
        }
	        
        //Now go thru the entire table and update the times.
	//While we do that, mark which rowindexes in the table are 'valid'.  Then we can loop thru all the rows and delete any we didn't just update
	var validRows=new Object();
        for(var i=0;i<IDs.length;i++){
            setDivHTML("np_"+IDs[i]+"_time",times[i]);

	    row=document.getElementById("np_"+IDs[i]+"_row")
	    if(row){
		validRows[row.rowIndex]=1;
	    }
        }
        
	//Now delete any extras
	for(var i=tbl.rows.length-1;i>=0;i--){
		var del=1;
		for(index in validRows){
			if(i==index){
				del=0;
			}
		}
		if(del==1)tbl.deleteRow(i);
	}
    }
}
function appendItemToPlaylist(){//add any items in the listData thingy to the current playlist.  Assumes everything has already been set up.
        tbl=document.getElementById("playlistItems");
        if(tbl){
            itemSeqs=listData["playlistItems_IDs"];
            itemNames=listData["playlistItems_names"];
            listData["playlistItems_IDs"]="";//blank out from the obj holder
            listData["playlistItems_names"]=""

            //append each item to the end of the table.  Note; we can assume that the seqs are unique for this playlist (to use to pass back to server to id an i
            //but can not assume that they are sequential without gaps so we do not use the seq to id the table rows.
            for(var i=0;i<itemSeqs.length;i++){
                var row=tbl.insertRow(tbl.rows.length);//insert at the end of table
                row.insertCell(0);
                row.id=itemSeqs[i];//set the row id to the seq so we can find it later (in delete script).
                row.cells[0].innerHTML="<a href='javascript:deletePlaylistItem("+itemSeqs[i]+");'><img src='"+_conf("skinDir")+"/images/icons/delete.gif' border='0' alt='X' width='15' height='15' class='icon'></a> - "+itemNames[i];//seqs will be a link to remove at some point
            }
	}
}
function loadAlbumList(destDivID){//Dynamically creates table to display a list of albums.
    div=document.getElementById(destDivID);
    if(div){
        //fetch the data arrays from the obj holder
        albumIDs=listData[destDivID+"_IDs"];
        albumNames=listData[destDivID+"_names"];
        
        //Fetch the artist and genre info, if passed;
        artistIDs=listData[destDivID+"_IDs2"];
        artistNames=listData[destDivID+"_names2"];
        genreIDs=listData[destDivID+"_IDs3"];
        genreNames=listData[destDivID+"_names3"];
        includeArtists=(artistIDs.length>0);
        includeGenres=(genreIDs.length>0);
 
        labels=listData[destDivID+"_otherData"];
        showListText=_conf("lang_showSongs");
        hideListText=_conf("lang_hideSongs");
  
        //Wipe out all from the obj holder so it doesn't get huge.
        listData[destDivID+"_IDs"]="";
        listData[destDivID+"_names"]="";
        listData[destDivID+"_IDs2"]="";
        listData[destDivID+"_names2"]="";
        listData[destDivID+"_IDs3"]="";
        listData[destDivID+"_names3"]="";
        
        setDivHTML(destDivID,"");//clear out whatever may be there now (including js used to call this).
        
        var tbl=document.createElement("table");
        tbl.className="albumListTable";
        //tbl.cellSpacing="5";
        
        for(var i=albumIDs.length-1;i>=0;i--){
            //Going from bottom up... first the horiz line break;
            tbl.insertRow(0);
            tbl.rows[0].insertCell(0);
            tbl.rows[0].cells[0].align='center';
            tbl.rows[0].cells[0].colSpan='2';
            tbl.rows[0].cells[0].innerHTML="<hr width='60%'>";
            
            //Next the div for expando song list.
            tbl.insertRow(0);
            tbl.rows[0].insertCell(0);
            tbl.rows[0].cells[0].align='left';
            tbl.rows[0].cells[0].colSpan='2';
            tbl.rows[0].cells[0].innerHTML="<div id='"+albumIDs[i]+"_songListWrapper'></div>";
            
            //Now the main content, which will be 3 tds.  The 2nd will contain another table.
            tbl.insertRow(0);
            
            tbl.rows[0].insertCell(0);//art
            tbl.rows[0].cells[0].className="albumArt";
            var img="";
            if(_conf("showAlbumArt")==1){
                //img="<img border='1' src='"+url+"?doWhat=getImage&type=albumArt&id="+albumIDs[i]+"'>";
                img="<img border='1' src='images.php?doWhat=getImage&type=albumArt&id="+albumIDs[i]+"' class='albumArtBorder'>";
            }
            tbl.rows[0].cells[0].innerHTML=getBrowseItemLink('album',albumIDs[i],img);
            
            tbl.rows[0].insertCell(1);//text.. this will be a separate table.
            var tbl2=document.createElement("table");
                tbl2.width='100%';
                
                tbl2.insertRow(0);
                tbl2.rows[0].insertCell(0);//album name
                //tbl2.rows[0].cells[0].colSpan='3';
                    tbl2.rows[0].cells[0].className="listingContent";
                    html="<div class='alb_albumName'>"+getBrowseItemLink('album',albumIDs[i],albumNames[i])+"</div>";
                    if(includeArtists){
                        if(artistIDs[i]!="-1"){html=html+"<div class='alb_artistName'>"+getBrowseItemLink('artist',artistIDs[i],artistNames[i])+"</div>";}
                        else{html=html+"<div class='alb_artistName'>"+artistNames[i]+"</div>";}
                    }
                    if(includeGenres){
                        if(genreIDs[i]!="-1"){html=html+"<div class='alb_genreName'>"+getBrowseItemLink('genre',genreIDs[i],genreNames[i])+"</div>";}
                        else{html=html+"<div class='alb_genreName'>"+genreNames[i]+"</div>";}
                    }
                    tbl2.rows[0].cells[0].innerHTML=html;
                    
                    tbl2.rows[0].cells[0].align="left";
                tbl2.rows[0].insertCell(1);//Play now
                    tbl2.rows[0].cells[1].innerHTML=getPlayNowLink("album",albumIDs[i]);
                    tbl2.rows[0].cells[1].className="listingFunctions";
                    tbl2.rows[0].cells[1].align="right";
                tbl2.rows[0].insertCell(2);//filler
                //tbl2.rows[0].cells[1].colSpan=    
                
                tbl2.insertRow(1);//functions
                tbl2.rows[1].insertCell(0);//Show Details
                    tbl2.rows[1].cells[0].align="left";
                    //html="<a href=\"JavaScript:showHideAlbumSongList("+albumIDs[i]+",'"+showListText+"','"+hideListText+"','"+albumIDs[i]+"_songListWrapper"+"');\">"+showListText+"</a>";
                    html="<div id='"+albumIDs[i]+"_showAblumDetailDiv' class='divAsAHref' onMouseOut=\"this.className='divAsAHref'\" onMouseOver=\"this.className='divAsAHrefHover'\" onClick=\"showHideAlbumSongList("+albumIDs[i]+",'"+showListText+"','"+hideListText+"','"+albumIDs[i]+"_songListWrapper"+"');\">"+showListText+"</div>";
                    tbl2.rows[1].cells[0].innerHTML=html;
                
                tbl2.rows[1].insertCell(1);//Add to playlist
                    tbl2.rows[1].cells[1].className="listingFunctions";
                    tbl2.rows[1].cells[1].innerHTML=getAddToPlayListLink("album",albumIDs[i]);
                    tbl2.rows[1].cells[1].align="right";
                tbl2.rows[1].insertCell(2);//filler    
                /*tbl2.rows[1].insertCell(2);//Play Now
                    tbl2.rows[1].cells[2].className="listingFunctions";
                    tbl2.rows[1].cells[2].align='left';
                    tbl2.rows[1].cells[2].innerHTML=getPlayNowLink("album",albumIDs[i]);//"<a href=\"JavaScript:showHideAlbumSongList("+albumIDs[i]+",'"+showListText+"','"+hideListText+"','"+albumIDs[i]+"_songListWrapper"+"');\">"+showListText+"</a>";
                */
            tbl.rows[0].cells[1].appendChild(tbl2);
            
        }
        div.appendChild(tbl);
    }    
}
function getBrowseItemLink(type,selectedID,itemName){
    html="<a href=\"javascript:browseItem('"+type+"','"+selectedID+"');\"><span class='browseItem'>"+itemName+"</span></a>";
    return html;
}
function getPlayNowLink(type,id){
    var doWhat="";
    if(type=="song") doWhat="playSong";
    if(type=="album") doWhat="playAlbum";
    if(type=="artist") doWhat="playArtist";
    if(type=="playlist") doWhat="playPlaylist";
    if(type=="genre") doWhat="playGenre";
    
    var play="<div class='playNow'><img src='"+_conf("skinDir")+"/images/icons/play_sm.jpg' width='15' height='15' border='0' alt='' class='icon'> "+_conf("lang_play")+"</div>";
        
    if(_conf("playMethod")>=1 ){//play in  flash player.  we need to set it up first
        html="<a href='javascript:openFlashPlayer(\""+doWhat+"\",\""+id+"\");'>"+play+"</a>";      
    }else{
        html="<a href='"+url+"?doWhat="+doWhat+"&id="+id+"'>"+play+"</a>";
    }
    if(_conf("isDemoSystem")==1)html=play;
    return html;
}
function getAddToPlayListLink(type,id){
    var add="<div class='addToPlayList'><img src='"+_conf("skinDir")+"/images/icons/add.gif' width='15' height='15' border='0' alt='' class='icon'> "+_conf("lang_addToPlaylist")+"</div>";
    //html="<a href=\"javascript:addToPlaylist('"+type+"','"+id+"');\"><div class='addToPlayList'>"+_conf("lang_addToPlaylist")+"</div></a>";
    html="<a href=\"javascript:addToPlaylist('"+type+"','"+id+"');\">"+add+"</a>";
    if(_conf("isDemoSystem")==1)html=add;
    return html;
}
function statusMssg(mssg){//Display a status mssg for 5 seconds.
    setDivHTML("statusDiv",mssg);
    setTimeout("setDivHTML('statusDiv','')",5000);
}
function alertMssg(mssg){//Display mssg in an alert box
        alert(mssg);        
}
