<?php
/*These are the general abstracted db utility functions.
 To use these you need to include a file (like dbLogin.php) that you
 include everywhere that needs db access and that contains something like:
                require_once("/home/www/lib/j_dbHead.php");
                getMysqlConnection("phpuser","php123","jDev");
                //getSybaseConnection("sybuser","syb123","jDev");
Once this is done you can access the dosql() function (see below).

Note the scrubText function needs to be tested on SYBASE.
*/
require_once("j_utilFuncs.php");

$link=false;
$j_dbHead_connType=-1;
define(MYSQL,1);
define(SYBASE,2);
$j_dbHead_debugSQLOutDir="";//globals
$j_dbHead_debugExplainSQL="";

function getMysqlConnection($user,$pass,$db="",$persistent=true,$debugSQLOutDir="",$debugExplainSQL=false,$mysqlhost='localhost'){/*Opens conn to mysql db on passed host
        This can be called repeatedly.  pconnect/connect just returns the current link.
        if $persistent passed true, then this calls pconnect (reuses connection, doesn't close at end of script)
        otherwise opens a normal connect.
        You should use $persistent=false if you are going to use temp tables... (I think:)

        Pass a Dir path (with trailing '/' ie '/tmp/sqlout/') in debugSQLOutDir to have all querys logged to file
        pass $debugExplainSQL true to have an 'explain $sql' done and logged to same file.
        */
        global $link,$j_dbHead_connType,$j_dbHead_debugSQLOutDir,$j_dbHead_debugExplainSQL;
        $j_dbHead_connType=MYSQL;
        if($persistent){
                $link = mysql_pconnect( $mysqlhost, $user, $pass );
        }else{
                $link = mysql_connect( $mysqlhost, $user, $pass );
        }
        if($db)mysql_select_db( $db, $link );
        //dbg("DB Link:$link",4,"getMysqlConnection");
        $j_dbHead_debugSQLOutDir=$debugSQLOutDir;
        $j_dbHead_debugExplainSQL=$debugExplainSQL;
        return $link;
}
function getSybaseConnection($user,$pass,$server,$db="",$persistent=true,$debugSQLOutDir=""){//Opens conn to sybase db on $server
        //can be called repeatedly.  server is the 'server' listed in ini file.
        //Pass a file name in debugSQLOutFile to have all querys logged to file
        require_once("j_sybMssgHandler.php");
        global $link,$j_dbHead_connType,$j_dbHead_debugSQLOutDir;
        $j_dbHead_connType=SYBASE;
        if($persistent)$link = sybase_pconnect( $server,$user,$pass );
        else $link=sybase_connect($server,$user,$pass);
        if($db)sybase_select_db( $db, $link );
        //dbg("DB Link:$link User:$user Pass:$pass Server:$server",4,"getSybaseConnection");
        $j_dbHead_debugSQLOutDir=$debugSQLOutDir;
        $j_dbHead_debugExplainSQL=false;//this could be mapped to set statistics io,time maybe?

        return $link;
}
function dosql($sql,$numRows=-1,$arraySuffix="s"){
        /*
         For Selects:
         Returns an array containing one member for each column (in order) selected
         in the query and 0 if no rows returned. False on error.
          -Each column must have a name, ie- select count(*) as total...(although it doesn't appear to be strictly necessary in mysql)
          -If $numRows is passed as -1 (default) all rows are returned in arrays that are named the same as the column name (+ $arraySuffix)
          -If $numRows is passed as 0 then the value of the first col of the first row is returned, false on error or if no rows returned.
                Intended for when you just want one value (like count(*) or something)and expect 1 row to always be returned. (note this is not tested on sybase)
          -If $numRows is passed as 1 then only the first row is returned and each member of the returned array is just a variable containing the col value(not an array)
          -Any other positive value will return that number of rows.

          -The returned array of arrays (when $numRows is not 1 or 0) will append $arraySuffix to the name of each
                column if requested. (ie- column "id" will become "ids" by defalut).
           if $numRows is passed as 1, then suffix is not appended.
           if $numRows is passed as 0, no variable is returned anyway (just the value), false if no rows returned..

        You can do an "extract($q);" on the returned array to split out all the individual arrays into separate ones for easier processing.
                -be careful not to overwrite any existing variables with extract.. see help for how to do without overwrite or with prefix.

        If you're in a class and want to put the results in to class arrays you can do this:
                if($a){//some rows returned... suck em out into this-> variables(must match the colnames.
                    foreach($a as $colName=>$array){
                        $this->$colName=$array;
                    }
                }

        For Updates, Inserts, Deletes ..
        Returns number of affected rows on success and false on failure.
        check for success by $a !== false
        */

        global $link,$j_dbHead_connType,$j_dbHead_debugSQLOutDir,$j_dbHead_debugExplainSQL;
//var_dump($j_dbHead_debugSQLOutDir);exit;
        //Do the actual query... and time if we're debuggin.
        if($j_dbHead_debugSQLOutDir!="")startTimer("sqlQuery");
                if($j_dbHead_connType==MYSQL)$a=doMySQLsql($sql,$numRows,$arraySuffix);
                elseif($j_dbHead_connType==SYBASE)$a=doSYBASEsql($sql,$numRows,$arraySuffix);
        if($j_dbHead_debugSQLOutDir!="")$time=endTimer("sqlQuery",true,false);

        //If we're to output sql to a file, do so now..
        if($j_dbHead_debugSQLOutDir!=""){
                $filename=$j_dbHead_debugSQLOutDir.mysql_thread_id()."-SQLOut.html";
                $out="<br>".j_getNiceDate(j_getDateArray("now"))."<br>";
                $out.="<b>".$sql."</b><br>Query took $time<br>";
                if(($j_dbHead_debugExplainSQL)&&(strrpos($sql,"insert")===false)&&(strrpos($sql,"truncate")===false)&&(strrpos($sql,"drop")===false)&&(strrpos($sql,"create")===false)&&(strrpos($sql,"update")===false)&&(strrpos($sql,"delete")===false)){//run the query thru with an explain prepended if appropriate (mysql only)
                        if($j_dbHead_connType==MYSQL){
                                $explain=doMySQLsql("explain ".$sql,-1,'s');
                                $out.=j_printTable($explain);
                        }//elseif($j_dbHead_connType==SYBASE)$explain=doSYBASEsql("explain ".$sql,-1,'s');
                }
                //write this out to logs.
                if(!$handle = fopen($filename, 'a')){//attempt to open file
                        echo ("Cannot open writable log file");
                    exit;
                }
                if (fwrite($handle, $out) === FALSE) {//attempt to write to it.
                      echo ("Cannot write to file ($filename)");
                      exit;
                }
                fclose($handle);
        }
        return $a;
}
function doSYBASEsql($sql,$numRows,$arraySuffix){
        //See notes in dosql wrapper, which you should be using anyway:)
        global $sybMssg;
        $a=array();
        global $link;
        $q=sybase_query($sql,$link);
        if($q===false){
                $message.="<h3>Failed SQL</h3><em><code>".htmlspecialchars($sql)."</code></em><br>";
                $message.='Invalid query: ' .$sybMssg. "\n";
                die($message);
                return false;
        }else{
                if($q===true){//This was a successful update,delete,insert type statement so just return
                        return sybase_affected_rows($link);
                }elseif(sybase_num_rows($q)>0 || $numRows==0){//otherwise iterate thru the results and put into appropriate variables.
                        if($numRows==1){//if we are only getting the first row, we use slightly different logic.
                                $row=sybase_fetch_assoc($q);
                                foreach($row as $name=>$value){
                                                $a[$name]=$value;
                                }
                        }elseif($numRows==0){
                                $row=sybase_fetch_row($q);
                                if($row)return $row[0];//just bail out now with whatever is there
                                else return false;//don't get mixed up with an actual zero being returned.
                        }else{//make sure valid input.
                                $count=1;
                                while (($row=sybase_fetch_assoc($q))&&(($numRows<0)||($count<=$numRows))){//loop thru all results or as many as requested.
                                        foreach($row as $name=>$value){
                                                $a[$name.$arraySuffix][]=$value;
                                        }
                                        $count++;
                                }
                        }
                }else return 0;//no rows returned.

        }
        return $a;
}
function doMySQLsql($sql,$numRows,$arraySuffix){
        //See notes in dosql wrapper, which you should be using anyway:)
        $a=array();
        global $link;
        //dbg("Link:$link",3,"doMySQLsql");
        $q=mysql_query($sql,$link);
        if($q===false){
                $message.= "<h3>Failed SQL</h3><b><code>".htmlspecialchars($sql)."</code></b><br>";
                $message.= 'Invalid query: ' . mysql_error() . "\n";
		if(log_message($message));//send to the application error handler if present.
                die($message);//send to browser output if can.
                return false;
        }else{
                if($q===true){//This was a successful update,delete,insert type statement so just return
                        return mysql_affected_rows($link);
                }else if(mysql_num_rows($q)>0 || $numRows==0){//otherwise iterate thru the results and put into appropriate variables. If numRows=0 we'll let below logic handle no rows returned.
                        if($numRows==0){//return first value (0th element) of first row
                                $row=mysql_fetch_row($q);//get a numerical array
                                if($row)return $row[0];//just bail out now with whatever is there
                                else return false;//don't get mixed up with an actual zero being returned.
                        }elseif($numRows==1){//if we are only getting the first row
                                $row=mysql_fetch_assoc($q);
                                foreach($row as $name=>$value){
                                        $a[$name]=$value;
                                }
                        }else{//make sure valid input.
                                $count=1;
                                while (($row=mysql_fetch_assoc($q))&&(($numRows<0)||($count<=$numRows))){//loop thru all results or as many as requested.
                                        foreach($row as $name=>$value){
                                                $a[$name.$arraySuffix][]=$value;
                                        }
                                        $count++;
                                }
                        }
                }else return 0;//no rows returned.

        }
        return $a;

}
function printQueryResult($a,$echo=true){//generic result printer for debug purposes..
        $str="";
        if($a!==false){
                if(!(is_array($a))){
                        $str.=CR.CR."No Results";
                }else{
                        $str.="<table border='1' align='left'>";
                        foreach($a as $col=>$rows){
                                if(is_array($rows)){
                                        foreach ($rows as $row=>$value){
                                            $str.= "<tr><td>".$col."[".$row."]</td><td>$value&nbsp</td></tr>\n";
                                        }
                                }else{//single var result set.  $rows (improperly named) contains the value
                                        $str.="<tr><td>$col</td><td>$rows&nbsp</td></tr>";
                                }
                        }
                        $str.="</table>";
                }
        }else $str.=CR."Result set is false".CR;
        if($echo)echo $str;
        return $str;
}
function db_getLastInsertID(){//wrapper to get the last inserted ID for a auto increment col.. Not working on SYBASE!!
        global $link,$j_dbHead_connType,$j_dbHead_debugSQLOutDir,$j_dbHead_debugExplainSQL;
        if($j_dbHead_connType==MYSQL)return dosql("select LAST_INSERT_ID()",0);
        //elseif($j_dbHead_connType==SYBASE)return dosql("select LAST_INSERT_ID()",0);
        else return false;
}
function dumpSQL($sql,$exit=true){//dump sql without annoying </> tag issues
        var_dump(htmlentities($sql));
        if($exit)exit();
}
function getDBDate($aDate){//returns a date suitable for use in a sql query (sybase & mysql).
        //$aDate is a date array like what is returned by getdate();  This assumes the timestamp in datearray is accurate and this uses time info.
        $dateStr=date("Y/m/d H:i:s",$aDate[0]);
        //dbg($dateStr,3,"getDBDate");
        return $dateStr;
}
function scrubTextForDB($text,$isGPC=false){//clean text for a db insert.  If isGPC (get,post,cookie) is true, we check for magice quotes
//and reverse before scrubbing.  You should pass true if passing request vars.
        global $link,$j_dbHead_connType;
        $ret="";
	if($isGPC && get_magic_quotes_gpc()==1)$text=stripslashes($text);

        if($j_dbHead_connType==MYSQL){
                $ret=mysql_real_escape_string($text,$link);
        }elseif($j_dbHead_connType==SYBASE){
                 $ret=str_replace("'","''",$text);/*We used to also do a strip_tags() on the
                 string, but took it out because we wanted to leave the embedded <>s that may be in there.
                 We hope there's not to much risk in this... (famous last words).
                 */
        }
        return $ret;
}

function scrubTextIn($txt,$level=1){
	/*Clean up user input/passed data.
	 $level 1 (more strict)leaves only "word" chars (letters and numbers no white space anywhere).  Strips any possible tags first
         $level 2 strips trailing white space, non printable ascii (converts tab to ' ' and leaves CRs) and any html/php tags.
	*/
	if($level==2){
		$txt=rtrim(strip_tags($txt));//strip any tags (php/html)... not this may strip legit text too! oh well.
		$txt=str_replace(chr(9)," ",$txt);//replace tabs with a space
		$txt=str_replace(chr(127),"",$txt);//replace delete key
		for($i=0;$i<32;$i++){//strip out any remaining non-printable ascii chars except for \r
		    if($i!=13)$txt=str_replace(chr($i),"",$txt);
		}
	}else $txt=preg_replace("/\W/","",strip_tags($txt));
        return $txt;
}

function merge2ColsIntoArray($a){//$a is a result set from dosql comprised of only 2 columns.. this returns an array with col 1 in index and col2 in value
        $aRet=array();
        if($a){
                $cols=array_keys($a);
                foreach($a[$cols[0]] as $row=>$index){
                        $aRet[$index]=$a[$cols[1]][$row];
                }
        }
        return $aRet;
}
function sqlExpr($value,$dataType){//dataType can be S string, N number, or D date.  Returns a string suitable to include in a sql statement (quoted if needed).
        if($dataType=="S" || $dataType=="s")$value="'".addslashes($value)."'";
        else if($dataType=="D"|$dataType=="d")$value="'".getDBDate(j_getDateArray($value))."'";
        if($value=="")$value="NULL";
        return $value;
}
$bldsql_froms=array();
$bldsql_wheres=array();
$bldsql_cols=array();
$bldsql_distinct="";
$bldsql_groupbys=array();
$bldsql_orderbys=array();
$bldsql_unions=array();
function bldsql_init(){//Reset the query builder
        global $bldsql_froms,$bldsql_wheres,$bldsql_cols,$bldsql_distinct,$bldsql_groupbys,$bldsql_orderbys;
        $bldsql_froms=array();
        $bldsql_wheres=array();
        $bldsql_cols=array();
        $bldsql_distinct="";
        $bldsql_groupbys=array();
        $bldsql_orderbys=array();
        $bldsql_unions=array();
}
function bldsql_col($col){//can be just 'col' or 'col as "alias"'.  Dups ignored
    global $bldsql_cols;
    if(in_array($col,$bldsql_cols)==false)$bldsql_cols[]=$col;
}
function bldsql_from($table){//Dups ignored
    global $bldsql_froms;
    if(in_array($table,$bldsql_froms)==false)$bldsql_froms[]=$table;
}
function bldsql_where($where){//Dups ignored
    global $bldsql_wheres;
    if(in_array($where,$bldsql_wheres)==false)$bldsql_wheres[]=$where;
}
function bldsql_orderby($col){//Dups ignored
    global $bldsql_orderbys;
    if(in_array($col,$bldsql_orderbys)==false)$bldsql_orderbys[]=$col;
}
function bldsql_groupby($col){//Dups ignored
    global $bldsql_groupbys;
    if(in_array($col,$bldsql_groupbys)==false)$bldsql_groupbys[]=$col;
}
function bldsql_distinct(){
        global $bldsql_distinct;
        $bldsql_distinct="distinct";
}
function bldsql_merge($a,$b,$delim=","){//util to append comma when needed.
        if($a!="" && $b!="")return $a.$delim.$b;
        return $a.$b;
}
function bldsql_cmd(){//Get the final sql.
        global $bldsql_froms,$bldsql_wheres,$bldsql_cols,$bldsql_distinct,$bldsql_groupbys,$bldsql_orderbys;//,$bldsql_unions;
        
        foreach ($bldsql_cols as $col){$cols=bldsql_merge($cols,$col);}
        foreach ($bldsql_froms as $from){$froms=bldsql_merge($froms,$from);}
        foreach ($bldsql_wheres as $where){$wheres=bldsql_merge($wheres,$where," and ");}
        foreach ($bldsql_groupbys as $col){$groupbys=bldsql_merge($groupbys,$col);}
        foreach ($bldsql_orderbys as $col){$orderbys=bldsql_merge($orderbys,$col);}
        $wheres=($wheres!="")?"where $wheres":"";
        $groupbys=($groupbys!="")?"group by $groupbys":"";
        $orderbys=($orderbys!="")?"order by $orderbys":"";
        
        $sql="select $bldsql_distinct $cols from $froms $wheres $groupbys $orderbys";
        return $sql;
}
function j_printTable($mArray,$tableClass="j_printTable",$strip_s=true,$rowClickecAction="",$keyArrayName="",$makeSortable=true,$makeFullSize=false,$showSortMssg=false){/*print out the results of a dosql query...
                                mArray is a 2d array, each sub array is a row of data.  each col/member of this array
                                has the col head assoc name.
                                -If you pass a class, you must have linked, the class for the table,
                                        a '$tableClass td' for tds and one for '$tableClass th' and optionally (if rowClickedAction passed)
                                        '$tableClass_onMouseOver' and '$tableClass_onMouseOut'
                                                like    .myTableClass{...
                                                        .myTableClass td{...
                                                        .myTableClass th{...
                                                        .myTableClass_onMouseOut{...
                                                        .myTableClass_onMouseOver{...

                                                        see pub/j_styles.css for ex.
                                        Note for convienence, you can pass this param '' and j_printTable will get subbed in.
                                        
                                -You can also include some formatting arrays for per cell formatting by class.
                                        Todo this just select a col with the same name as target plus "_tdclass" -- myCol_tdclass.
                                        each cell should contain either blank or name of a defined and linked class which will
                                        get included in the td like <td class='classname'>...</td>
                                        These obviously won't get printed with the rest of the data.

                                -If strip_s passed true then this will strip off the trailing s (if exists) for header names that gets tacked on from dosql...
                                -If $rowClickecAction is passed then whatever passed will be called on click.. keyword "key" (lowercase)will
                                        be substituted with passed $keyArrayName (remember to include 's' on end if appropriate).
                                        rowClickecAction should be like
                                                javascript:rowClicked(key);
                                        or      javascript:rowClicked('key');
                                        You pass the above in quotes because it's a string, but don't have to worry about the tag quotes...
                                -if $makeSortable passed true (default) this will put js links in the col headers to sort the table.
                                        You MUST link the sortable.js script in the html header.
                                -If $makeFullSize=true then the tablewidth will be set to 100%
                                -This will skip printing any array that contains in '_sortCol' or '_hide' as well as 'rowid'
                                -If array ends in '_emptyHeader' then the header text will be blank, but col will print.
                                -If the array end in '_title' the contents of the array will be used for the row title (mouseover)
                                */
        $onclick="";
        $rowStyle="";
        $titleArrayName="";
        $tableFontClass="";
        if($tableClass==""){
                $tableClass="j_printTable";//default in for blank.
        }
        /*if($tableClass=="j_printTable"){//do the font size thing for default table class.. 
                $tableFontClass=getPref("pref_printTableFontSize");//special font size handling.. not document very well :)
        }*/
        if($mArray){
                //Set some vars for the sorting mojo...
                $sortTableClass=($makeSortable)?"sortable":"";
                $sortCounter=0;
                $tableWidth=($makeFullSize)?"width='100%'":"";
                //Header
                $html="<table class='$tableClass $sortTableClass $tableFontClass' id='".uniqid("j_printTable")."' $tableWidth><tr>";//Note we add a unique ID to this table.. it's totally arbitrary but needed to make some js stuff (sorttable)work.  Hopefully does no harm.  Need to pass prefix for php4
                foreach($mArray as $arrayName=>$array){//print the header row.
                        if((strrpos($arrayName,"rowid")===false)&&(strrpos($arrayName,"_tdclass")===false)&&(strrpos($arrayName,"_sortCol")===false)&&(strrpos($arrayName,"_hide")===false)&&(strrpos($arrayName,"_title")===false)){//skip any special name_class format or sorting arrays
                                if($strip_s){
                                        if(strrpos($arrayName,"s")==strlen($arrayName)-1){//ends with a 's'?
                                                $arrayName=substr($arrayName,0,strlen($arrayName)-1);
                                        }
                                }
                                if(strrpos($arrayName,"_emptyHeader")!==false)$arrayName="&nbsp;";
                                if($makeSortable){//include a js link to sort the table
                                        $arrayName="<a href='#' onclick=\"ts_resortTable(this,$sortCounter);return false;\">$arrayName<span class='sortarrow'>&nbsp;</span></a>";
                                        $sortCounter++;
                                }
                                $html.="<th>$arrayName</th>";
                        }
                        $arraySize=sizeof($array);//remember the size of one of them.. do it here becuase we don't know the names of the arrays outside of this loop..
                        //also remember the array name of the title if exists
                        if(strrpos($arrayName,"_title")!==false)$titleArrayName=$arrayName;
                }
                $html.="</tr>";
                //Data
                for($x=0;$x<$arraySize;$x++){
                        if($rowClickecAction!="")$onclick="onClick=\"".str_replace("key",$mArray[$keyArrayName][$x],$rowClickecAction)."\"";
                        if($onclick){
                                $rowStyle=" style='cursor: pointer;' ";
                                $mouseOver=" onMouseOver=\"this.className='".$tableClass."_onMouseOver'\" onMouseOut=\"this.className='".$tableClass."_onMouseOut'\" ";
                        }
                        $title=($titleArrayName!="")?"title='".$mArray[$titleArrayName][$x]."'":"";//Set the title, if any.
                        $html.="<tr $onclick $rowStyle $mouseOver $title>";
                        foreach($mArray as $arrayName=>$col){
                                if((strrpos($arrayName,"rowid")===false)&&(strrpos($arrayName,"_tdclass")===false)&&(strrpos($arrayName,"_sortCol")===false)&&(strrpos($arrayName,"_hide")===false)&&(strrpos($arrayName,"_title")===false)){//skip any special name_class format or sorting arrays
                                        //see if there's a matching format col for this piece of data.  This is unfortunately complicated by the dosql optionally appending 's' on the arrays
                                        if(array_key_exists(substr($arrayName,0,strlen($arrayName)-1)."_tdclasss",$mArray))$class=$mArray[substr($arrayName,0,strlen($arrayName)-1)."_tdclasss"][$x];
                                        elseif(array_key_exists($arrayName."_tdclass",$mArray))$class=$mArray[$arrayName."_tdclass"][$x];
                                        else $class="";
                                        if($class)$class="class=\"".$class."\"";
                                        $val=$col[$x];
                                        if($val==""||$val==' ')$val="&nbsp";//set to a space so cell isn't weird.
                                        $html.="<td $class>".$val."</td>";
                                }
                        }
                        $html.="</tr>";
                }
                $html.="</table>";
                if($makeSortable && $showSortMssg)$html="<table><tr><td>$html<div align='right' class='tiny'><i>Click a column heading to sort</i></div></td></tr></table>";
        }else $html="<div class='small_ital'>None found</div>";
        return $html;
        //echo $html;
}

?>
