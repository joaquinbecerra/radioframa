
/*javascript functions to handle tabs, linked to the ajax stuff. 
*/
//var sideTab_selectedTab = "";//globals
var tabBrowseData = new Object();
var scrollHelpText = "Use arrows to scroll ";
function sideTab_selectTab(tableID,tabID,params){/*
	Handle the click event. 
        */
        //stab=tabBrowseData['']
        
        if(window.tabBrowseData[tableID+"_selectedTab"]===undefined){//See if the selectedTab var for this table exists yet and if not, define it.
                tabBrowseData[tableID+"_selectedTab"]="";
        }
        
        if(tabBrowseData[tableID+"_selectedTab"]!=""){
                setDivIDClass(tabBrowseData[tableID+"_selectedTab"],"sideTab_backgroundedTab");
        }
        setDivIDClass(document.getElementById(tabID).parentNode.id,"sideTab_foregroundedTab");
        tabBrowseData[tableID+"_selectedTab"]=document.getElementById(tabID).parentNode.id;
        
	maxHeight=sideTab_getMaxHeight(tableID);
        params=params+"&maxDivHeight="+maxHeight;//pass into server in case it's interested in the div height to use..
        ajax_getUrl(tabBrowseData[tableID+"_targetURL"],params,tabBrowseData[tableID+"_contentDivID"],'get',-1);//NOTE: overridding whatever passed in and setting for no history on tab changes.  If re-enabling you need to fix the tab select part which no workie when going backwards :(
}
function sideTab_getMaxHeight(tableID){/*returns the max table height for this table.  We may want this to be dynamic in the future.. which is why it's wrapped.*/
        //kluge.  This should really be reading dynamically depending on content and size of browser window.
	var h= 15*tabBrowseData[tableID+"_maxRows"]+32+tabBrowseData[tableID+"_maxRows"];
        if (h< 300){
                h=300;
        }
	wHeight=sideTab_getWindowHeight();
	if(h<wHeight-200)h=wHeight-200;//arbitrary.. this would be the amount of header above the table.  should probably pass this in.
	return h;
}
function sideTab_getWindowHeight(){
	var myWidth = 0, myHeight = 0;
	if( typeof( window.innerWidth ) == 'number' ) {
	  //Non-IE
	  myWidth = window.innerWidth;
	  myHeight = window.innerHeight;
	} else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
	  //IE 6+ in 'standards compliant mode'
	  myWidth = document.documentElement.clientWidth;
	  myHeight = document.documentElement.clientHeight;
	} else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
	  //IE 4 compatible
	  myWidth = document.body.clientWidth;
	  myHeight = document.body.clientHeight;
	}
	return myHeight;
}
function sideTab_scroll(direction,tableID,distance){
    /*scroll the tabs. distance (optional) is number of tabs to scroll.  Defaults to displayRows (# showing).*/
    if(distance==null)distance=0;
    scrollHelpText="";//stop showing the help text once they've clicked an arrow once...
    tbl=document.getElementById(tableID);
    if(tbl){
	var namesArrayName=tableID+"_names";
        var paramsArrayName=tableID+"_params";
        var mapsArrayName=tableID+"_mappings";
	var displayedRows=tabBrowseData[tableID+"_maxRows"];

        var datalen=tabBrowseData[namesArrayName].length;
        var currStart=tabBrowseData[mapsArrayName][0];
        //var currEnd=currStart+displayedRows-1;//0 offest.
        var currEnd=currStart+(tbl.rows.length-3);
       	//alert(currStart+" "+currEnd+" "+currEnd);

	if((direction=="up" && currStart>0) || (direction=="down" && currEnd<datalen-1)){//anywhere to browse to?  shouldn't be called if not, but doesn't hurt to check.

	        if(direction=="down"){
			var newEnd=currEnd+distance;
			if(newEnd==currEnd){//distance wasn't passed, add default.
				newEnd=currEnd+displayedRows;//+1 let the lastrow start out the next scroll set so it has a little continuity.
			}
           		if(newEnd>datalen)newEnd=datalen;
            		var newStart=newEnd-displayedRows;
                        if(newStart<0)newStart=0;
			if((newStart<currEnd)&&(distance==0))newStart=currEnd;//If less then the max # of displayed rows, start and the last end for continuity.  only do this if moving a page (displayedRows) at a time.
        	}else{//going up...
			var newStart=currStart-distance;
			if(newStart==currStart){//distance wasn't passed, add default.
				newStart=currStart-displayedRows+1;
			}
            		if(newStart<0)newStart=0;
            		var newEnd=newStart+displayedRows;
                        if(newEnd>datalen)newEnd=datalen;
			
        	}
		//alert(newEnd+" "+newStart+" "+currEnd+" "+currStart+" "+direction+" "+displayedRows+" "+datalen);
		//Remove all existing tabs, then add new ones back in.  
		for(var i=tbl.rows.length-2;i>0;i--){//leave the first and last rows.
	                tbl.deleteRow(i);
        	}
		sideTab_addTabs(tableID,newStart,newEnd);

		sideTab_setScrollRows(tableID,datalen,newStart,newEnd);
       
		//Select the first tab..
        	tabID="sideTab_"+tableID+"_"+newStart;//The addTabs function sets the id of 1st data row to be the start index.
		sideTab_selectTab(tableID,tabID,tabBrowseData[paramsArrayName][tabBrowseData[mapsArrayName][0]]);
	
	}
    }
}

/*Not used (i think) and now obsoleted.
function sideTab_getSelectedTab(){//returns the currently selected tab
	return sideTab_selectedTab;
}
*/

/*util*/
function setDivIDClass(divID,newClass){
    div=document.getElementById(divID);
    if(div){
            div.className=newClass;
    }
}

function sideTab_loadArrays(tableID,selectedTab){
    /*build a tab browse thingy, fill with array data from the tabBrowseObj (must exist already and is set from php)
        
	selectedTab is the index into the data arrays (ie- the first row is 0)

	see the php func for details and usage
    */
    destDivID=tabBrowseData[tableID+"_destDivID"];
    var dest=document.getElementById(destDivID);
    var tbl=document.getElementById(tableID);
    var contentDiv=tabBrowseData[tableID+"_contentDivID"];
    if(dest){
        if(tbl){//We can skip creating the table and 1st/last rows and just use what ever is here.  Remove all data rows first though
            for(var i=tbl.rows.length-2;i>0;i--){//leave the first and last rows.
                tbl.deleteRow(i);
            }
        }else{//create a new one
            //clear out anything that may be in the dest div
            dest.innerHTML="";
            var tbl=document.createElement("table");
            tbl.className="sideTab_table";
            tbl.cellSpacing="0";
	    tbl.id=tableID;
                                    
            tbl.insertRow(0);//This will either be a scroll row or a filler row along with the big multi row content div.
            //Insert the cells and define as appropriate.
            tbl.rows[0].insertCell(0);
            tbl.rows[0].insertCell(1);
            
            //set up the content area.
            tbl.rows[0].cells[1].className="sideTab_contentTD";
	    var maxDivHeight=sideTab_getMaxHeight(tableID);
            tbl.rows[0].cells[1].innerHTML="<div name='sideTabContentDivName' id='"+contentDiv+"' class='sideTab_contentArea' style='height:"+maxDivHeight+"px;'>&nbsp;</div>";
            
            //Add the last row which will also either be a scroll row or filler as needed.
            tbl.insertRow(1);
            tbl.rows[1].insertCell(0);
            tbl.rows[1].insertCell(1);
            
            tbl.rows[0].cells[0].align='right';
            tbl.rows[1].cells[0].align='right';
            dest.appendChild(tbl);//Set the table into the Dest Div
        }
	
	tbl.rows[0].cells[1].rowSpan=2;//This is temporary until we add the rows below..
        
        //Get the data from the tabBrowseObj.. assume success.
        var names=tabBrowseData[tableID+"_names"];
        var params=tabBrowseData[tableID+"_params"];
        
        var datalen=names.length;
        
        if(selectedTab>=datalen || selectedTab<0)selectedTab=0;
        
        var start=selectedTab-1;//If a selected Tab was passed, make it the 2nd down.  Purely so it looks better.  No other reason.
        if(start<0)start=0;
	
        
	//var end=tabBrowseData[tableID+"_maxRows"];
        var end=start+tabBrowseData[tableID+"_maxRows"];
        
        if(end>datalen)end=datalen;
        
        sideTab_setScrollRows(tableID,datalen,start,end);

	//Now add in all the content rows.
	sideTab_addTabs(tableID,start,end);

	//Select a tab
	tabID="sideTab_"+tableID+"_"+(selectedTab);
	sideTab_selectTab(tableID,tabID,params[selectedTab]);
    }//..dest
}
function sideTab_addTabs(tableID,start,end){
	var tbl=document.getElementById(tableID);
	if(tbl){
		j=0;
		for(i=start;i<end;i++){
			row=tbl.insertRow(tbl.rows.length-1);
			index=row.rowIndex;
			row.insertCell(0);
			row.cells[0].className="sideTab_tableTD";
			title=tabBrowseData[tableID+"_names"][i];
			DisplayTabName=title;
                        
                        row.cells[0].id=tableID+"_td_"+index;//Note,the td needs an id so the select tab function can set it's class
			addScrollHandler(tableID+"_td_"+index,tableID);//add scrolling
			if(DisplayTabName.length>tabBrowseData[tableID+"_textCutOffLen"]){
			    DisplayTabName=DisplayTabName.substring(0,tabBrowseData[tableID+"_textCutOffLen"]-3)+"...";
			}
			row.cells[0].className='sideTab_backgroundedTab';
                        //row.style.height='15px';
			//This used to use index insead of i to build the id, this should be correct now... need i so that the passed 'selectedTab' can reference the right row
                        row.cells[0].innerHTML="<div title='"+title+"' id='sideTab_"+tableID+"_"+i+"' onClick=\"sideTab_selectTab('"+tableID+"',this.id,'"+tabBrowseData[tableID+"_params"][i]+"');\">"+DisplayTabName+"</div>";
                
			tabBrowseData[tableID+"_mappings"][j]=i;//save off where in the data array we are...
			j++;
		}
		tbl.rows[0].cells[1].rowSpan=tbl.rows.length;
	}
}
function sideTab_setScrollRows(tableID,datalen,currStart,currEnd){
    /*this sets up the scrollRows appropriately for the passed tab table.  Assumes they already exists.*/

    var tbl=document.getElementById(tableID);
    tabWidth=tabBrowseData[tableID+"_tabWidth"];
    if(tbl){
        if(currStart>0 || currEnd<datalen){//need add scrolling stuff...
		
            if(currStart>0){//we're scrolled down a little to start out with..
                tbl.rows[0].cells[0].className="sideTab_scrollEnabled";
                tbl.rows[0].cells[0].innerHTML="<div class='smalItal' align='right' style='display:inline;vertical-align:bottom;'>"+scrollHelpText+"</div><div style='display:inline;' onClick=\"sideTab_scroll('up','"+tableID+"',1);\"><img src='"+_conf("skinDir")+"/images/NavArrowUp.gif' width='16' height='16' alt='/\' class='icon'></div><div style='display:inline;' onClick=\"sideTab_scroll('up','"+tableID+"');\"><img src='"+_conf("skinDir")+"/images/NavArrowAllUp.gif' width='16' height='16' alt='/\' class='icon'></div>";
            }else{
                tbl.rows[0].cells[0].className="sideTab_scrollDisabled";
                tbl.rows[0].cells[0].innerHTML="<div>&nbsp;</div>";
            }

            if(currEnd<datalen){//we're scrolled up a little to start out with..
                tbl.rows[tbl.rows.length-1].cells[0].className="sideTab_scrollEnabled";
                tbl.rows[tbl.rows.length-1].cells[0].innerHTML="<div class='smalItal' align='right' style='display:inline;vertical-align:top;'>"+scrollHelpText+"</div><div style='display:inline;' onClick=\"sideTab_scroll('down','"+tableID+"',2);\" style='width:"+tabWidth+";'><img src='"+_conf("skinDir")+"/images/NavArrowDown.gif' width='16' height='16' alt='\/' class='icon'></div><div style='display:inline;' onClick=\"sideTab_scroll('down','"+tableID+"');\" style='width:"+tabWidth+";'><img src='"+_conf("skinDir")+"/images/NavArrowAllDown.gif' width='16' height='16' alt='\/' class='icon'></div>";
            }else{
                tbl.rows[tbl.rows.length-1].cells[0].className="sideTab_fillerCell";
                tbl.rows[tbl.rows.length-1].cells[0].innerHTML="<div style='width:"+tabWidth+";height:100%;'>&nbsp;</div>";
            }
        }else{//just set the tabs for filler/pad
                tbl.rows[tbl.rows.length-1].cells[0].className="sideTab_fillerCell";
                tbl.rows[tbl.rows.length-1].cells[0].innerHTML="<div style='width:"+tabWidth+";height:100%;'>&nbsp;</div>";
		tbl.rows[0].cells[0].className="sideTab_fillerCellTop";
                tbl.rows[0].cells[0].innerHTML="<div style='width:"+tabWidth+";'>&nbsp;</div>";

        }
    }
}
function getInnerScreenHeight(){
     var winH = 0;
    if (document.body && document.body.offsetWidth) {
    winH = document.body.offsetHeight;
    }
    if (document.compatMode=='CSS1Compat' &&
	document.documentElement &&
	document.documentElement.offsetWidth ) {
     winH = document.documentElement.offsetHeight;
    }
    if (window.innerWidth && window.innerHeight) {
     winH = window.innerHeight;
    }
    return winH;
}

function getInnerScreenWidth(){
     var winW = 0;
    if (document.body && document.body.offsetWidth) {
     winW = document.body.offsetWidth;
    }
    if (document.compatMode=='CSS1Compat' &&
	document.documentElement &&
	document.documentElement.offsetWidth ) {
     winW = document.documentElement.offsetWidth;
    }
    if (window.innerWidth && window.innerHeight) {
     winW = window.innerWidth;
   }
    return winW;
}
function windowResizeEventFired(){
  //General place to hook in to the resize event.
  //This should be safe to call from where ever (like by adding this at the end of your
  // div html: <script language='Javascript'>windowResizeEventFired();</script>).
  //Note the div should have a class='resizeableDiv' 
  var bottomOfTab=200; //approx.  Use this below so it's easier to change the header stuff on top.
  //We wanted to be able to tell dynamically where the div is, but that was hard.
  
  //setDivHeight('reportScrollDiv',bottomOfTab+100);//Report window.
    var div=document.getElementsByName('sideTabContentDivName')[0];
    if(div){
    	var id=div.id;
    	setDivHeight(id,250);
    }

}
function setDivHeight(elementID,margin){
  /*set the height of the div to the inner browser window height minus margin (in pixels),
  ie if you pass 200 for the header and the browser height is 1000, it will get set to 800;*/
    var h=0;
    h=getInnerScreenHeight();
    if(h>500){
        div=document.getElementById(elementID);
        if(div){
            div.style.height=(h-margin)+'px';//arbitrary offset based on the header /menu heights.  We should find the abs height, but that was hard.            
        }
    }
}
//Scrolling 
function scrollTabsByWheel(tableID,e){
    var evt=window.event || e; //equalize event object
    var delta=evt.detail? evt.detail*(-120) : evt.wheelDelta; //check for detail first so Opera uses that instead of wheelDelta
    
    var direction=(delta<0)? "down":"up";
    //delta=Math.abs(delta/120);
    delta=1;//Hard code to one because we had trouble with touch pads.  This seems to work on all browsers/devices so far.    
    if(direction=='down')delta++;

    sideTab_scroll(direction,tableID,delta);
    if(evt.preventDefault) evt.preventDefault();//disable page scrolling.
    else return false;    
}
function addScrollHandler(objID,tableID){
/*objID is the name of the tab obj id to attach scroll handler to, tableID is the data to scroll (see above)*/
    var id=document.getElementById(objID);
    if(id){ 
        var mousewheelevt=(/Firefox/i.test(navigator.userAgent))? "DOMMouseScroll" : "mousewheel"; //FF doesn't recognize mousewheel as of FF3.x
 
        if (id.attachEvent) //if IE (and Opera depending on user setting)
            id.attachEvent("on"+mousewheelevt, function() {return scrollTabsByWheel(tableID);});
        else if (id.addEventListener) //WC3 browsers
            id.addEventListener(mousewheelevt,  function(e) {return scrollTabsByWheel(tableID,e);}, false);
    }
}
