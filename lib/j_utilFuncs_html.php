<?php
function j_prettySubmitBtn($text,$formName){//returns a submit button that looks pretty. (white, moves...)
	$html.="<table><tr><td class=\"j_prettyBtn_raised\" onClick=\"document.$formName.submit();\" onMouseDown=\"this.className='j_prettyBtn_pressed'\" onMouseUp=\"this.className='j_prettyBtn_hover'\" onMouseOver=\"this.className='j_prettyBtn_hover'\" onMouseOut=\"this.className='j_prettyBtn_raised'\">
				<font class='j_prettyBtn_text'>$text</font>
			</td></tr></table>";
	return $html;
}
function j_prettyHrefBtn($uniqueButtonName,$text,$url,$param1Name="",$param1Value="",$param2Name="",$param2Value=""){
	$html.="<table><form action=\"$url\" method='get' name='$uniqueButtonName'>";
	if($param1Name!="")$html.="<input type='hidden' name='$param1Name' value='$param1Value'>";
	if($param2Name!="")$html.="<input type='hidden' name='$param2Name' value='$param2Value'>";
	$html.="<tr><td class=\"j_prettyBtn_raised\" onClick=\"document.$uniqueButtonName.submit();\" onMouseDown=\"this.className='j_prettyBtn_pressed'\" onMouseUp=\"this.className='j_prettyBtn_hover'\" onMouseOver=\"this.className='j_prettyBtn_hover'\" onMouseOut=\"this.className='j_prettyBtn_raised'\">
				<font class='j_prettyBtn_text'>$text</font>
			</td></tr></form></table>";
	return $html;
}
function j_prettyHrefBtn2($jsFunc,$text){
    $html="<table><tr><td class=\"j_prettyBtn_raised\" onClick=\"$jsFunc;\" onMouseDown=\"this.className='j_prettyBtn_pressed'\" onMouseUp=\"this.className='j_prettyBtn_hover'\" onMouseOver=\"this.className='j_prettyBtn_hover'\" onMouseOut=\"this.className='j_prettyBtn_raised'\"><div class='j_prettyBtn_text'>$text</div></td></tr></table>";
    return $html;
}
function j_hrefBtn($url,$text,$showInactiveBorder=true,$target=""){
    if($showInactiveBorder)$class="j_href_btn";
    else $class="j_href_btn2";
    return "<a href=\"$url\" class=\"$class\" $target>$text</a>";
}
function j_select($array,$name,$selected="",$size="1",$addBlank=true,$onClick="",$allowMultiple=false){//returns html for a select
    /*array is an associative array with index=select option values and the value is displayed as text
	Note; you can pass  array_combine($keys_array,$values_array) as the $array to combine 2 together (like from db query)
	*/
	if($onClick)$onClick="onChange='$onClick'";
	if($allowMultiple)$multiple="multiple";
	else $multiple="";
    $html.="<select id='$name' name='$name' size='$size' $onClick $multiple>";
    if($addBlank)$html.="<option value=''></option>";
    foreach($array as $value=>$text){
        if($value==$selected)$sel="selected";
        else $sel="";
        $html.="<option value='$value' $sel>$text</option>";
    }
    $html.="</select>";
    return $html;
}

function getSideTabsArea($tableID,$destDivID,$tabNames,$url,$params,$selectedTab,$historyIndex,$maxRows,$textCutOffLen=25,$tabWidth=150){/*Returns a html side tab selector.
        Assumes j_tabs.css, j_tabs.js & j_ajax.js are included.
        $tableID should have no spaces.
        $destDivID is where to stick the table
        $tabNames is array of names.  If more than $maxRows, then they will scroll up and down.
        $url is target url for ajax calls
            Note $url isn't quoted when it's inserted below.. That's because I ususally set a js var to hold the url and pass this var (unquoted) around to the ajax stuff as needed.
            If you pass an actual url it will need to be single quoted.
	
        $params must be same size as tabNames, can be all blank, but usually will contain params (like doWhat, id...) to pass with the ajax call when a tab is clicked to get the content for that tab.
            Params should be in the form of param=value[&param2=value2[...]] and correspond to the tabNames array.
        $selectedTab is the one to pre-select.  0 is the first.
        if historyIndex=0 then a default history save point is created, -1 nope.  For other values see j_ajax.js
        $maxRows is the max # of rows to display at once.  Any more will be accessable via scroll arrows.
        $textCutOffLen is how much text of the name to show before changing to a mynam...
        $tabWidth is the width of the tabs (I think this may actually be fixed at 150 now due to css issues).
        $browseTitle, if passed, displays at the top of the browse obj.
        Assumes there's atleast 1 tab.
	
	*/
//$selectedTab=0;//The JS functions don't seem to support this yet.. need a bit of  work and I've had a few too many runs to the keg o rater...

        //Sanitize all the tabNames for htmltransport.  We assume params are all safe (should just be ids and simple text).
        foreach($tabNames as $i=>$n){$tabNames[$i]=addslashes($n);}        
        
        //Set up the js arrays to hold tab info
        $jsNamesName=$tableID."_names";//these are just to make the below quoting easier.
        $jsParamsName=$tableID."_params";
        $jsMapsName=$tableID."_mappings";
        $jsMaxRows=$tableID."_maxRows";
        $jsURL=$tableID."_targetURL";
        $jsDivID=$tableID."_destDivID";
        $jsHistoryIndex=$tableID."_historyIndex";
        $jsTextCutOff=$tableID."_textCutOffLen";
        $jsTabWidth=$tableID."_tabWidth";
        $jsContentDivID=$tableID."_contentDivID";
        foreach($tabNames as $i=>$tabName){
                $jsNames=j_appendToList($jsNames,"'".$tabName."'",",");
                $jsParams=j_appendToList($jsParams,"'".addslashes($params[$i])."'",",");
        }
        $jsNames="var $jsNamesName=new Array(".$jsNames.");";
        $jsParams="var $jsParamsName=new Array(".$jsParams.");";
        $jsMaps="var $jsMapsName=new Array();";//let the JS fill this one.
        $html="\n<script language='JavaScript'>
                        \n $jsNames \n $jsParams \n $jsMaps \n
                        tabBrowseData['$jsNamesName']=$jsNamesName;
                        tabBrowseData['$jsParamsName']=$jsParamsName;
                        tabBrowseData['$jsMapsName']=$jsMapsName;
                        tabBrowseData['$jsMaxRows']=$maxRows;
                        tabBrowseData['$jsURL']=$url;
                        tabBrowseData['$jsDivID']='$destDivID';
                        tabBrowseData['$jsHistoryIndex']=$historyIndex;
                        tabBrowseData['$jsTextCutOff']=$textCutOffLen;
                        tabBrowseData['$jsTabWidth']=$tabWidth;
                        tabBrowseData['$jsContentDivID']='$jsContentDivID';
                        sideTab_loadArrays('$tableID',$selectedTab);
                </script>";//add in the tabinfo arrays and send the call to load them up.
        
        
        return $html;
}



//Progress Bar stuff...
$prog_abort=false;// store abort info in global var as well as session becasue we need to know in this script if abort is true and can't always rely on state of session vars
function prog_initProgressbar($title,$statusMssg,$maxVal){//Called by backgrounded process to start the progress..
    //assumes the following divs exist in the page: jsDiv, progressBarDiv & progressBarActionDiv and that j_ajax.js is linked in.  Also assumes that a JS var url is set with the url of the main switcher php page.
    //Puts a title, progress bar and then status message.  Updates thru ajax every few seconds.  Call prog_updateProgress to update it.

	//The 'backgrounded' script should call this method as soon as possible so the js poller has a title to display.
	//It can be recalled to reset the maxVal. It should then call  prog_updateProgress to update the current prog value and 
	//prog_checkAbort() at at the same time to see if it should quit.  Call prog_stopProgressBar to finish.  

    //Note all scripts running should close the session as soon as possible as session single threads everything.
    
    //main routing script needs to handle
        //doWhat=getProgress&first=true and call prog_getProgress() to handle the js calls.
	//doWhat=abortProgress and call prog_abort() 
//JS caller should start the backgrounded process by calling the js func prog_startActionWithProgressBar()

        
    
    ignore_user_abort(TRUE);//we'll handle aborts internally for this backgrounded process    
	//I'm not sure this is working as well as it should.. we don't seem to get the aborted notification (thru connection_aborted() below) reliably and this process keeps running :(

    session_start();
    $_SESSION['prog_title']=$title;
    $_SESSION['prog_statusMssg']=$statusMssg;
    $_SESSION['prog_maxVal']=$maxVal;
    $_SESSION['prog_curVal']=0;
    $_SESSION['prog_abort']=false;
    $_SESSION['prog_complete']=false;    
    session_write_close();
}
function prog_checkAbort(){//Backgrounded process should call this frequently to see if it should quit.
    global $prog_abort;

    $abort=false;
    //flush();//clear the buffer so php will check the client connection status, although I don't think this works if there's nothing in the buffer, but I don't want to just send out garbage :(
ob_end_flush();
    session_start();
    $abort=($_SESSION['prog_abort'] || connection_aborted() || $prog_abort);
    session_write_close();
    $prog_abort=$abort;//save off so backgrounded process will catch the abort even after the session vars are wiped...

    if(connection_aborted()){//this means the client is no longer there.. go ahead and clean up
      	$a=prog_stopProgressBar();
    }

    return $abort;
}
function prog_abort(){//called by js ajax to abort the backgrounded process
    session_start();
    $_SESSION['prog_abort']=true;
    session_write_close();
}
function prog_stopProgressBar($finalMssg=""){//called by backgrounded process to signal it's done.
	session_start();
    	$_SESSION['prog_statusMssg']=$finalMssg;
	$_SESSION['prog_complete']=true;  
  	session_write_close();
//This should get caught on next js update...
}
function prog_updateProgress($newVal,$statusMssg=false,$relative=true){//Called by backgrounded process to update progress
	//If statusMssg !==false then it's updated otherwise it's left alone.
    	//If $relative==true the current value is incremented by newVal otherwise it's replaced by newVal

    session_start();
    //log_message("updateProg: newVal=$newVal, curVal=".$_SESSION['prog_curVal'].", maxVal=".$_SESSION['prog_maxVal']);
    if($statusMssg!==false)$_SESSION['prog_statusMssg']=$statusMssg;
    if($relative)$_SESSION['prog_curVal']+=$newVal;
    else $_SESSION['prog_curVal']=$newVal;
    if($_SESSION['prog_curVal']>$_SESSION['prog_maxVal'])$_SESSION['prog_curVal']=$_SESSION['prog_maxVal'];
    session_write_close();
}
function prog_getProgress($first=false){//Called by JS script to get current progress
    //returns html for update progress...
    session_start();
    $numDone=$_SESSION["prog_curVal"];
    $numDone=($numDone==0)?".00001":$numDone;
    $numToDo=$_SESSION["prog_maxVal"];
    $title=addslashes($_SESSION['prog_title']);
    $msg=addslashes($_SESSION['prog_statusMssg']);
    $cancel=($_SESSION['prog_complete'])?1:0;
    session_write_close();
    if($numToDo==0)$numToDo=1;
        $width=floor((100*$numDone/$numToDo));
        if($first){//The widths below are somewhat arbitrary, but: the table must stay at 100 so the % works and the 2 divs should be as wide as the widest text you expect so it doesn't reflow constantly.
            $html="<div id='prog_progressBarTitle' align='right' style='width:300px;' class='textboxPrompt'>$title</div>
		    <div align='right'>
                    <table width='100px' id='prog_progressBarTable' cellpadding='0' class='prog_progressBarTable'>
                        <tr>
				<td class='prog_progressBar' style='width:$width px;'></td>
				<td style='width:".(100-$width)." px;'></td>
			</tr>
                    </table>
		    </div>
                    <div id='prog_progressBarStatusMssg' style='width:300px;' align='right' class='smalItal'>$msg</div>
		    <div align='right'><a href='javascript:prog_abort();'><span class='prog_CancelText'>Cancel</span></a></div>";
        }else{
                $html="<script language='JavaScript'>prog_updateProgressTherm($width,'$msg','$title',$cancel);</script>";
        }

    return $html;
}
?>
