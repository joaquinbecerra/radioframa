 /*JS functions for ajax stuff.
    The Goal was to simplify using the ajax business so you only need one method and
    don't need to sweat the details of how the shit works...

    The model I use is slightly different from standard xml type ajax model. xml may be better,
    but seemed like too much overhead for me.  Instead, all calls should return fully formed
    html; usually a table, form or the like.  This html just gets inserted into the target div.
    The url should be a big switch that gets passed some kind of action parameter (doWhat) and does whatever it needs
    to do for that action and returns the html.  The html can have embedded links to reload, call other
    js functions, whatever.  This is similar to the old iframe model, but without having a separate page
    in each frame.. ie- you can use this to just get a select obj to put into the middle of a form.

    You can call the getUrl function simultaneously from different objects as it doesn't use
    global request objects.

    To use, include this file in the main ajax wanting html file:
    <script src="js/j_ajax.js" type="text/javascript"></script>
    and create some divs then load em up ajax_getUrl below...


    A simplistic history (back/forward browser buttons) functionality is handled natively if you
    include the dhtmlHistory.js script in your main ajax html page:
        <script src="js/dhtmlHistory.js" type="text/javascript"></script>

    (Note; dhtmlHistory.js requires a dummy blank html page for f'ing ie.  You need to set the path
    to this page in dhtmlHistory.js (and put the file there :)  See that file for details... )

    Basically, by default, each div that gets loaded gets it's own 'save point'.
    Each time a back (or forward) occurs the appropriate div is reloaded by requerying the server if its
    a get and by redisplaying the output from a post.
    This means that it's safe to 'back over' a data changing post because all you'll get
    is the result of the submission (ie- 'subitted successfully!').  It works on firefox and ie (6+),
    not at all on safari (sorry) and sporadically on konquoer.

    The defaults will do a reasonable thing for most uses, but if you have a bunch of things getting
    loaded at once, like if a button/row click causes several divs to get populated, then you'll want
    to do a custom save point.  Basically, just call ajax_getNextSavePointID() and then pass that
    save point id in as the 'historyIndex' to each call to ajax_getUrl that you want grouped together.
    You can optionally tell it whether it's ok to use the last cached results for that save point or to
    always requery.  When users back onto this save point all grouped divs will get reloaded at once.
    See comments below for details.
*/
var ajax_progressImgLocation="js/ajax-loader2.gif";//global.. set


function ajax_getUrl(url,parameters,divID,sendMethod,historyIndex,historyReQuery,callBackMethod){
//alert(parameters);
historyIndex=-1;
/*
Load results from url and params into target divID.  Optionally specifiy get/post and a callback method
-url is the url without '?' appended to the end
-parameters are key value pairs deliminated with '&' ... name=value&name2=value2
-divID is the destination divID to fill with the results.  This will wipe anything in there currently.
    Can be blank if you are using a custom call back method.
-sendMethod(optional) is either 'get' or 'post'.  Post 'should' be used when submitting data.
    Defaults to 'get'.
-historyIndex is the index number of the history 'save point'.  Default is 0.
    If passed 0, then a new id/save point is created automatically for this call.
    If passed -1, no save point is created.
    Any other number, then this query is added to that index number.  This should happen in
    2 cases.
        1) by the methods below when requerying on a historyEvent
        2) When you want to group a bunch of calls into one save point,
        ie- if one button click causes 3 different ajax calls that should all go together,
        you would first call ajax_getNextSavePointID() to get the next index, then pass
        that value in with all three calls to this method.  That way when user 'backs' into
        this save point (back button), all three get updated at once.
-historyReQuery is passed true to requery the server with the original parameters (from above) when
    a user clicks the back button and gets to this 'save point'.  False to just load up the
    results from our local cache of what was returned.  This should probably not be passed true if
    you are 'post' ing a change to the server (sending a db update or something) because it would
    send the same thing in again.  On the other hand, for gets, passing true can speed up considerably
    if that is ok from the app stand point.  Browsers in general (non ajax land) use a cached version.
    The default for gets is true; The default for post is false.

-callBackMethod(optional) is the method to call with the results.
    If specified, method must handle 3 params: http_request, divID,historyIndex.
    It can do whatever it pleases with them.
    
    Defaults to "ajax_genericHandler" which should suffice for most uses unless
     you need to do something fancy after getting the results (like check for success).

All params are strings (pass in quotes)
If you use the generic call back method then it will display a cute little 'working' gif
in a div called 'progress', if present, while any connections are open.
*/
    var http_request = false;
    if(sendMethod==null)sendMethod='get';//default to gets
    if(historyIndex==null)historyIndex='0';
    if(historyReQuery==null){//set default to requery if this is a normal get, from cache if a post.
        if(sendMethod=='get')historyReQuery=true;
        else historyReQuery=false;
    }
    if(callBackMethod==null)callBackMethod="ajax_genericHandler";//default to builtin handler.

    //Add this content to the history (back/forward) cache (if being used)
    if((window.dhtmlHistory!=null)&&(historyIndex>=0)){
        if(historyIndex==0)historyIndex=ajax_getNextSavePointID();//if not specified, just get the next index #
        //Prune off any dead branches if we can (if user backed up then started a new save point,
        //none of the old 'forwards' can be reached, so we'll just remove them)
        //Note, we are just deleting the storage, not the 'savepoints' because we don't
        //have an easy means of doing that.
        var currLoc=dhtmlHistory.getCurrentLocation();
        currLoc++;//need to increment like this to force as a number..
        for(i=currLoc;i<historyIndex;i++){
            if(historyStorage.hasKey(i+"_data"))historyStorage.remove(i+"_data");
        }

        //Save off all the parameter info about this call
        var thisCall=new Object();
        thisCall.url=url;
        thisCall.parameters=parameters;
        thisCall.divID=divID;
        thisCall.sendMethod=sendMethod;
        thisCall.historyIndex=historyIndex;//This may now be different than passed (if it was zero).
        thisCall.historyReQuery=historyReQuery;
        thisCall.callBackMethod=callBackMethod;
        thisCall.content="";//to be filled later if we're in cache mode...

        //See if we've already got this index in the array, and if so, append thisCall to it
        if(historyStorage.hasKey(historyIndex+"_data")){
            histCacheArray=historyStorage.get(historyIndex+"_data");
            //we assume that save point has already been added to dhtmlHistory, so we'll skip that part here.
        }else{
            var histCacheArray=new Array();
            //add this save point
            dhtmlHistory.add(historyIndex,"");
        }
        histCacheArray.push(thisCall);
        historyStorage.put(historyIndex+"_data",histCacheArray);//save the newly appeneded cache array
//        setDivHTML('contentArea3',historyStorage.toSource());

    }

    var d=new Date();//we'll include a changing param to avoid caches... not sure that this needed.
    parameters=parameters+"&epochcount="+d.getTime();//seconds since unix epoch.
    setDivHTML(divID,"");//clear anything in the destination currently
    if (window.XMLHttpRequest) { // Mozilla, Safari,...
        try{
            http_request = new XMLHttpRequest();
            if (http_request.overrideMimeType) {
                    //http_request.overrideMimeType('text/xml');
            }
        }catch(e){http_request=false;}

        } else if (window.ActiveXObject) { // IE
            try {
                http_request = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_request = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {http_request=false;}
            }
        }
        if (!http_request) {
            alert('Giving up :( Cannot create an XMLHTTP instance');
            return false;
        }
         //set the callback using dynamic function voodoo
        http_request.onreadystatechange = function() { eval(callBackMethod)(http_request,divID,historyIndex); };
        if((sendMethod=="post")||(sendMethod=="POST")){
            http_request.open('POST', url, true);
            http_request.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=UTF-8");
            http_request.send(parameters);
        }else if((sendMethod=="get")||(sendMethod=="GET")){
            http_request.open('GET', url+"?"+parameters, true);
        http_request.send("");
        }
    if(callBackMethod=="ajax_genericHandler"){//using the generic handler.. do progress bar stuff
        ajax_setProgress("add");
    }
    return true;
}

var ajax_openConnectionsCounter=0;//global
function ajax_setProgress(action){//adds a nice little gif in a progress <div> area while any conn is open. assumes white background
    if(action=="add"){
        ajax_openConnectionsCounter=ajax_openConnectionsCounter+1;
    }else{
        ajax_openConnectionsCounter=ajax_openConnectionsCounter-1;
    }
    if(ajax_openConnectionsCounter>0){
        html="<img src='"+ajax_progressImgLocation+"' width='16' height='16' alt=''>";
    }else{html="&nbsp;";}
    //alert(html);
    setDivHTML("progress",html);
}
function ajax_silentHandler(http_request,divID){//similar to the generic handler, but runs in silent mode.. no progress notification.
    if (http_request.readyState == 4) {
        if (http_request.status == 200) {
            setDivHTML(divID,http_request.responseText);
        }
    }
}
function ajax_genericHandler(http_request,divID,historyIndex){
    if (http_request.readyState == 4) {
            if (http_request.status == 200) {
        setDivHTML(divID,http_request.responseText);
        ajax_setProgress("remove");

        //Add this content to the history (back/forward) cache (if being used)
        if((window.dhtmlHistory!=null)&&(historyIndex>=0)){
            if(historyStorage.hasKey(historyIndex+"_data")){
                newCacheArray=new Array();
                histCacheArray=historyStorage.get(historyIndex+"_data");
                for(i=0;i<histCacheArray.length;i++){
			if((histCacheArray[i].historyReQuery==false)&&(histCacheArray[i].divID==divID)){//cache the results of this query
                        histCacheArray[i].content=http_request.responseText;
                    }
                }
                historyStorage.put(historyIndex+"_data",histCacheArray);

            }
        }
    } else {
                //Actually just silently error out..
		//alert('There was a problem with the request. Status code:'+http_request.status);
            }
        }
}
function ajax_historyChange(historyIndex,historyData){
    //alert("(histChang["+historyIndex+"])hasKey for : "+historyStorage.hasKey(historyIndex+"_data"));
    if(historyStorage.hasKey(historyIndex+"_data")){
        histCacheArray=historyStorage.get(historyIndex+"_data");
        for(i=0;i<histCacheArray.length;i++){
            var callObj=histCacheArray[i];
            if(callObj.historyReQuery==false){//just reset results from cache
                setDivHTML(callObj.divID,callObj.content);
            }else{//requery but tell getUrl method to ignore this as a savepoint
                ajax_getUrl(callObj.url,callObj.parameters,callObj.divID,callObj.sendMethod,-1,callObj.historyReQuery,callObj.callBackMethod);
            }
        }

    }
}
function ajax_getNextSavePointID(){//returns the next save point index to use.  Persists over a refresh.
        $index=-1;
        if(window.dhtmlHistory!=null){
                if(historyStorage.hasKey("storePointIndex")){
                        $index=historyStorage.get("storePointIndex");
                        $index++;
                }else{
                        $index=1;
                }
                historyStorage.put("storePointIndex",$index);
        }
        return $index;
}
function ajax_history_initialize() {
    if(window.dhtmlHistory!=null){//only add if there...
        dhtmlHistory.initialize();
        dhtmlHistory.addListener(ajax_historyChange);
    }
}
//add the initialize stuff to the onload handler without wiping whatever may be there...
if(window.addEventListener)window.addEventListener("load",ajax_history_initialize,false);
else if(window.attachEvent)window.attachEvent("onload",ajax_history_initialize);

function setDivHTML(divID,html){
    /*Note that before pasting the html in, we first check to see if this has any js script blocks
     and if so strip them out eval them after pasting in the html.  We leave the js in the original html
     .. still a little experimental :)
    */
    var htmlStr,l_html,start,end,tagStart,js="";
    htmlStr=new String(html);
    l_html=htmlStr.toLowerCase();//just to make searches easier.
    tagStart=l_html.indexOf("<script");
    if(tagStart>=0){
        while(tagStart>=0){
            start=l_html.indexOf(">",tagStart)+1;//find very next closing tag delim
            end=l_html.indexOf("</script>",start);
            js=js+htmlStr.substring(start,end);//Note using full cased html
            tagStart=l_html.indexOf("<script",end);
            //alert("js block: "+js);
        }
    }
    var div = document.getElementById(divID);
    if(div){
        div.innerHTML="";
        div.innerHTML=html;
    }
    if(js!="")window.eval(js);//Note we use window.eval instead of just eval because that seems more reliable cross browser for global var scope.
    //alert(divID+" "+html);
}

function ajax_getFormElementParams(formObj){/*
This is a little helper method to fetch out all the fields from a form.
Returns the params in name=value[&name2=val2[...]] format. You will need to put a '?' between url and these params.
Should work on most except maybe a multi select...
NOTE; you must specify a name in all form objects!
*/
        var str = "";
        for (i=0; i<formObj.elements.length; i++){
            name_val=formObj.elements[i].name + "=" + encodeURIComponent(formObj.elements[i].value);
            if ((formObj.elements[i].tagName == "INPUT")||(formObj.elements[i].tagName=="TEXTAREA")){
                if((formObj.elements[i].type == "text")||(formObj.elements[i].type == "textarea")||(formObj.elements[i].type == "hidden")){
                    str=ajax_appendToList(str,name_val,"&");
                }
                if (formObj.elements[i].type == "checkbox"){
                    if (formObj.elements[i].checked){
                        str=ajax_appendToList(str,name_val,"&");
                    }else{
                        str=ajax_appendToList(str,formObj.elements[i].name + "=","&");//no value on this one.
                    }
                }
                if (formObj.elements[i].type == "radio"){
                    if (formObj.elements[i].checked){
                        str=ajax_appendToList(str,name_val,"&");
                    }
                }
            }
            if(formObj.elements[i].tagName == "SELECT"){
                var sel = formObj.elements[i];
                if(sel.selectedIndex>=0)
                str=ajax_appendToList(str,sel.name+"="+sel.options[sel.selectedIndex].value,"&");
            }
        }

        return str;
}

function ajax_appendToList(str1,str2,delim){//assumes str1 and str2 are either "" or have contents.
    if((str1!="")&&(str2!=""))return str1+delim+str2;
    else return str1+str2;
}




//See j_utilFuncs_html.php for more details on how to use the progress bar
//js func should start long running process using prog_startActinWithProgressBar,
//then call prog_startProgressBar().
//Server must know how to handle a 'doWhat=getProgress&first=true'
//progressBarActionDiv, progressBarDiv & jsDiv must exist on the loaded html page.
var prog_currVal=0;
var prog_pollInterval=1000;
var prog_timeOutID="";
var prog_cancelled=0;
var prog_inUse=0;
var prog_aborted=0;
function prog_startActionWithProgressBar(params){//params are passed to server and should be like doWhat=action[&param2=fee]...
    if(prog_inUse>0){//somebody else is using the therm right now.
        alert("Sorry, only 1 process is allowed to run in the background.  Please wait until the current operation finishes.");//Should be lang independant...
    }else{
	ajax_getUrl(url,params,"progressBarActionDiv","get",-1);//never use history thingy..
	setTimeout("prog_startProgressBar()",1000);//wait a second before fetching to give the backgrounded process a chance to setup.
    }
}
function prog_startProgressBar(){
    prog_currVal=0;
    prog_cancelled=0;
    prog_inUse=1;
    prog_aborted=0;
    setDivHTML('progressBarDiv','');
    setDivHTML('progressBarActionDiv','');

    ajax_getUrl(url,"doWhat=getProgress&first=true","progressBarDiv","get",-1);
    prog_timeOutID=setTimeout("prog_getProgressThermStatus()",prog_pollInterval);
}
function prog_abort(){
    ajax_getUrl(url,"doWhat=abortProgress","jsDiv","get",-1);
    setDivHTML('prog_progressBarStatusMssg','Aborting...');
    prog_aborted=1;
}
function prog_stopProgressBar(){
	window.clearTimeout(prog_timeOutID);
	prog_cancelled=1;
	prog_inUse=0;
	setTimeout("if(prog_inUse==0){setDivHTML('progressBarDiv','');}",4000);//only wipe if still not in use(user didn't restart again)
	setTimeout("if(prog_inUse==0){setDivHTML('progressBarActionDiv','');}",4000);
}
function prog_updateProgressTherm(newValue,newStatusMsg,newTitle,cancel){
    if(prog_cancelled==0){
        prog_currVal=newValue;
	//newStatusMsg+=" "+newValue;
        tbl=document.getElementById('prog_progressBarTable');
        if(tbl){
            tbl.rows[0].cells[0].width=newValue+"px";
	    tbl.rows[0].cells[1].width=(100-newValue)+"px";
        }
        if(prog_aborted==0 || cancel==1){//only change the text if it's a normal update (aborted==0) or the final cancel msg.  Skip if user just aborted and we haven't cancelled just yet.
            setDivHTML('prog_progressBarStatusMssg',newStatusMsg);//
        }
	if(cancel==0){
		setDivHTML('prog_progressBarTitle',newTitle);
	}else{
		setDivHTML('prog_progressBarTitle','');
	}
	
    }
    if(cancel==1)prog_stopProgressBar();
}
function prog_getProgressThermStatus(){
    //alert("running status, val:"+prog_currVal);
    ajax_getUrl(url,"doWhat=getProgress","jsDiv",'get',-1);//send this (it'll just be js code) to the special jsDiv so we don't wipe out the bar
    if(prog_cancelled==0){
        prog_timeOutID=setTimeout("prog_getProgressThermStatus()",prog_pollInterval);
    }
}


