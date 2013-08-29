<?php
/*admin stuff*/
function getAdminPage(){
    //Return the list of admin type functions.
    $tabNames=array(_conf("lang_config"),_conf("lang_editPrefDefaults"),_conf("lang_users"),_conf("lang_userPriv"),_conf("lang_catalog"),_conf("lang_stats"),_conf("lang_log"));            
    $params=array("doWhat=editSystemConfigs","doWhat=editPreferenceDefaults","doWhat=admin_users","doWhat=admin_privs","doWhat=admin_catalog","doWhat=getAdminStats","doWhat=admin_showLogs");
    
    if(_conf("showDevAdmin")==1){//add in the dev admin page
        $tabNames[]="Dev Admin";
        $params[]="doWhat=devAdmin";
    }
    $html.=getSideTabsArea("tabbedBrowseTable","tabbedBrowseList",$tabNames,"url",$params,0,0,25);
    return $html;
}
function admin_showLogs(){
    $html="No log file configured.";
    if(_conf("error_log")){
	$html="";
	$a=file(_conf("error_log"));
	if($a){
	    $a=array_reverse($a);
	    foreach($a as $line){$html.=$line."<BR>";}
	}

	
    }
    return $html;
}
function getCatalogPage(){
    //Return the catalog functions
    $html="<table border='0' width='100%'><tr><th>"._conf("lang_description")." <div class='smalItal'>"._conf("lang_backgrounded")."</div></th><th></th></tr>";
    $html.="<tr><td class='configDesc'><b>"._conf("lang_updateCatalog")."</b><br>"._conf("lang_updateCatalogExplain")."</td>
                <td>".j_prettyHrefBtn2("prog_startActionWithProgressBar('doWhat=updateCatalog','');",_conf("lang_DoIt"))."</td>
                
            </tr>
	    <tr><td class='configDesc'><b>"._conf("lang_deleteOrphans")."</b><br>"._conf("lang_deleteOrphansExplain")."</td>
                <td>".j_prettyHrefBtn2("prog_startActionWithProgressBar('doWhat=deleteOrphans','');",_conf("lang_DoIt"))."</td>

            </tr>
	    <tr><td class='configDesc'><b>"._conf("lang_updateCatalogAll")."</b><br>"._conf("lang_updateCatalogAllExplain")."</td>
                <td>".j_prettyHrefBtn2("prog_startActionWithProgressBar('doWhat=updateCatalogAll','');",_conf("lang_DoIt"))."</td>

            </tr>
            <tr><td class='configDesc'><b>"._conf("lang_showNoArtAlbums")."</b><br>"._conf("lang_showNoArtAlbumsExplain")."</td>
                <td>".j_prettyHrefBtn2("loadNoArtAlbums();",_conf("lang_DoIt"))."</td>

            </tr>
            <tr><td class='configDesc'><b>"._conf("lang_cacheAlbumArt")."</b><br>"._conf("lang_cacheAlbumArtExplain")."</td>
                <td>".j_prettyHrefBtn2("prog_startActionWithProgressBar('doWhat=cacheAlbumArt','');",_conf("lang_DoIt"))."</td>

            </tr>
            <tr><td class='configDesc'><b>"._conf("lang_optimizeTables")."</b><br>"._conf("lang_optimizeTablesExplain")."</td>
                <td>".j_prettyHrefBtn2("prog_startActionWithProgressBar('doWhat=optimizeTables','');",_conf("lang_DoIt"))."</td>

            </tr>


            <!--
	    <tr><td class='configDesc'><b>"._conf("lang_listCatalogs")."</b><br><div id='listCatalogsDiv'></div></td>
		<td>".j_prettyHrefBtn2("loadDivArea('listCatalogsDiv','listCatalogs','');",_conf("lang_DoIt"))."</td>
	    </tr>
		<tr>
			<td>delete all from db<br>say bye bye</td><td>".j_prettyHrefBtn2("loadDivArea('mainContentArea','clearDB','');",_conf("lang_DoIt"))."</td>
		</tr>-->
		";
    
    $html.="</table>";
    
    return $html;
}
function optimizeTables(){
    prog_initProgressbar(_conf("lang_optimizingTables"),"",100);
    $a=dosql("show tables");
    if($a){
        foreach($a as $tables){//not sure what this will be called so it's easier to use this syntax than extract.. there should only ever be 1 array in $a though
            prog_initProgressbar(_conf("lang_optimizingTables"),"Processing ".count($tables)." tables",count($tables));   
            foreach($tables as $table){
                prog_updateProgress(1,"$table");
                dosql("optimize table $table");
            }
            prog_stopProgressBar("Optimize complete.");
            log_message("Optimized tables");
            updateConfig("36",time());//log this as last time tables were optimized for the auto updater thingy.
        }
    }else prog_stopProgressBar(_conf("lang_noWorkToDo"));
    return true;
}
function admin_getDevAdminPage(){
        //bunch of helpers for the developers
        $html="<h3>Add config</h3>
                <form name='devAdmin_addConfig' id='devAdmin_addConfig'>
                <table>
                        <tr><td class='formPrompt'>ConfigID</td><td class='textbox'><input type='text' name='devAdmin_configID' id='devAdmin_configID' value='".dosql("select max(configID)+1 from configs",0)."'></td></tr>
                        <tr><td class='formPrompt'>Config name</td><td class='textbox'><input type='text' name='devAdmin_configName' id='devAdmin_configName'></td></tr>
                        <tr><td class='formPrompt'>Value</td><td class='textbox'><input type='text' name='devAdmin_configValue' id='devAdmin_configValue'></td></tr>
                        <tr><td class='formPrompt'>Type<br><span class='tiny'>0=system,1=priv,2=pref</span></td><td class='textbox'>".j_select(array(0=>"System",1=>"Privilege",2=>"Preference"),"devAdmin_configType",0,1,false)."</td></tr>
                        <tr><td class='formPrompt'>Display Type<br><span class='tiny'>If select,then values list must be entered and displaytext lang entry<br>
                                                                                must exist in the lang conf file.  If dynamincFileSelect then valuesList <br>
                                                                                must contain 'directory,file-ext'</span>
                                                                        </td><td class='textbox'>".j_select(array('text'=>"Text","number"=>"Number","select"=>"Select List","dynamicFileSelect"=>"Dynamic file selector"),"devAdmin_configDisplayType",1,1,false)."</td></tr>
                        <tr><td class='formPrompt'>ValuesList<br><span class='tiny'>List of value keys for select like '0,1' <br>or if dyn file select 'directory,file-ext'</span> </td><td class='textbox'><input type='text' name='devAdmin_configValuesList' id='devAdmin_configValuesList'></td></tr>
                        <tr><td class='formPrompt'>Editable<br><span class='tiny'></span></td><td class='textbox'>".j_select(array(1=>"True",0=>"False"),"devAdmin_configEditable",1,1,false)."</td></tr>
                        <tr><td colspan='2'><input type='button' onClick='devAdmin_submitSQLGenForm(this.form);' value='Go'></td></tr>
                        <tr><td colspan='2'><div id='devAdmin_sqlOutput'></div></td></tr>
                        
                </table>
                </form>
                ";

        return $html;        
}
function devAdmin_genConfigInsert(){
        $html="Copy this statement into dbUpdate.php page section for this release:<br><br>";
        $html.='<span class="sql">if($ok)$ok=dbUpdate("'.devAdmin_genConfigInsertSQL().'");</span>';
        $html.="<br><br><input type='button' onClick='devAdmin_insertConfig(this.form);' value='Submit SQL'>";
        return $html;
}
function devAdmin_genConfigInsertSQL(){
        $sql="Replace configs set
                configID=".$_REQUEST['devAdmin_configID'].",
                name='".$_REQUEST['devAdmin_configName']."',
                value='".$_REQUEST['devAdmin_configValue']."',
                type=".$_REQUEST['devAdmin_configType'].",
                displayType='".$_REQUEST['devAdmin_configDisplayType']."',
                valuesList='".$_REQUEST['devAdmin_configValuesList']."',
                editable=".$_REQUEST['devAdmin_configEditable']."        
        ";
        return $sql;
}
function devAdmin_insertConfig(){
        $sql=devAdmin_genConfigInsertSQL();
        if(UL_ISADMIN && _conf("showDevAdmin")){//should already be checked, but doesn't hurt...
                $html=(dosql($sql)==1)?"1 row inserted/updated":"crap.. some kind of error.  Hope there was a message :).";
        }
        return $html;
}
function getAdminUsersPage(){
	/*For now, just shows a link to the user admin page.. would be nice in the future to have this integrated better....*/
	$html.="<br><br><br><br><div align='center'><a href='index.php?UL_showAdmin=1'>"._conf("lang_userAdmin")."</a></div>";
	
	return $html;
}

function admin_getUserPrivTab(){
	$a=dosql("select userID, userName from users where enabled=1 order by userName");
	if($a){
		extract($a);
		foreach ($userIDs as $i=>$userID){
			$tabNames[]=$userNames[$i];
			$params[]="doWhat=admin_a_user_privs&id=$userID";
		}
		$html.=getSideTabsArea("userPrivTab","tabbedBrowseTable_contentDivID",$tabNames,"url",$params,1,-1,20);
	}
	
	return $html;
}
function getAdminStats(){
	bldsql_init();
	bldsql_from("users u");
	bldsql_from("songs s");
	bldsql_from("statistics st");
	bldsql_where("u.userID=st.userID");
	bldsql_where("st.type='playedSong'");
	bldsql_where("s.songID=st.itemID");
	bldsql_col("u.userName");
	bldsql_col("round(sum((s.filesize*st.count)/1024/1024/1024),2) as 'GB Streamed'");
	bldsql_col("sum(st.count) as '# songs played'");
	bldsql_col("(select max(lastPlayed) from statistics where userID=u.userID) as 'lastPlayed' ");
	
	bldsql_groupby("u.userName");
	//var_dump(bldsql_cmd());
	$html.="<div align='center'><h4>"._conf("lang_userStats")."</h4>";
	$html.=j_printTable(dosql(bldsql_cmd()));
	
	bldsql_init();
	bldsql_from("songs s");
	bldsql_col("count(*) as '# Songs'");
	bldsql_col("(select count(*) from albums) as '# Albums'");
	bldsql_col("(select count(*) from artists) as '# Artists'");
	bldsql_col("round(sum(s.filesize/1024/1024/1024),2) as 'Total GBs'");
	bldsql_col("round(sum(s.songLength/60/60/24),2) as 'Days of music'");
		   
	$html.="<h4>"._conf("lang_dbStats")."</h4>";
	$html.=j_printTable(dosql(bldsql_cmd()));
	
	bldsql_init();
	bldsql_from("statistics st");
	bldsql_from("songs s left join artists_songs arts on arts.songID=s.songID left join albums_songs albs on albs.songID=s.songID");
	bldsql_from ("artists art");
	bldsql_from("albums alb");
	bldsql_where("alb.albumID=albs.albumID");
	bldsql_where("art.artistID=arts.artistID");
	bldsql_where("st.type='playedSong'");
	bldsql_where("s.songID=st.itemID");
	bldsql_col("s.songName  as 'Song name'");
	bldsql_col("art.name as 'Artist'");
	bldsql_col("alb.name as 'Album'"); ### 'Album' in lieu of 'Alubm'
	bldsql_col("sum(st.count) as '# times played'");
	//bldsql_col("(select art.name from artists art, artists_songs arts where arts.artistID=art.artistID and arts.songID=s.songID) as 'Artist'");
	//bldsql_col("(select alb.name from albums alb, albums_songs albs where albs.albumID=alb.albumID and albs.songID=s.songID) as 'Album'");
	bldsql_groupby("s.songName");
	bldsql_groupby("art.name");
	bldsql_groupby("alb.name");
	bldsql_orderby("'sum(st.count)' desc"); ### in lieu of sum(st.count)
	
	$html.="<h4>"._conf("lang_mostPopularSongs")."</h4>";
	$html.=j_printTable(dosql(bldsql_cmd()." limit 10"));
	
	bldsql_init();
	bldsql_from("statistics st");
	bldsql_from("artists a");
	bldsql_where("st.type='playedArtist'");
	bldsql_where("a.artistID=st.itemID");
	bldsql_col("a.name as 'Artist'");
	bldsql_col("sum(st.count) as '# times played'");
	bldsql_groupby("a.name");
	bldsql_orderby("'sum(st.count)' desc"); ### in lieu of sum(st.count)

	$html.="<h4>"._conf("lang_mostPopularArtists")."</h4>";
	$html.=j_printTable(dosql(bldsql_cmd()." limit 10"));
	
	bldsql_init();
	bldsql_from("statistics st");
	bldsql_from("albums a");
	bldsql_where("st.type='playedAlbum'");
	bldsql_where("a.albumID=st.itemID");
	bldsql_col("a.name as 'Album'");
	bldsql_col("sum(st.count) as '# times played'");
	bldsql_groupby("a.name");
	bldsql_orderby("'sum(st.count)' desc"); ### in lieu of sum(st.count)

	
	$html.="<h4>"._conf("lang_mostPopularAlbums")."</h4>";
	$html.=j_printTable(dosql(bldsql_cmd()." limit 10"));
	
	
	$html.="</div><br><br><br><br>";
	return $html;
}
function editConfigs($configType,$userID=UL_UID){/*returns html for editing the config overrides
$configType:0=system,1=privilege,2=preference
Pass userID -1 to set system default overrides (ie type 2, user -1)
 
The basic concept with the configs is that the configs table hold the master list of config variables along with the defaults provided by us.
There are 3 types; system, user priviledges and user preferences.
The system and priviledge configs are only editable by the admin.
in the case of system configs, any changes the site admin makes will create a row in the preferences table with a userID of -1;
The admin can also set the user preference defaults (override the ones we provide) which also go into the preferences with a user of -1;
If a user changes a pref, then a row is added with the new value just for that user ID
 */

	$html=_conf("lang_noAccess");
	if(($configType=="2" && $userID>0) || UL_ISADMIN){//This is either a user pref edit or the admin doing anything else.
	    if($configType==0)$userID=-1;//system settings don't belong to any user.
	    bldsql_init();
	    bldsql_from("configs c left join preferences p on c.configID=p.configID and p.userID=$userID");//join into preferences to get any overrides (either system or user level)
	    bldsql_col("c.configID");
	    if($userID==-1)bldsql_col("c.value as 'default'");//show sys defaults when in pref default override mode (when admin is setting user preference defaults) or when editing a system config
	    else bldsql_col("ifnull((select value from preferences  where configID=c.configID and userID=-1),c.value) as 'default'");//This could probably be built into left join syntax, but that got me confused
	    bldsql_col("c.name");
            
	    //bldsql_col("ifnull(p.value,c.value) as override");//This is the overrided value (either system or user) currently set for this config.
	    bldsql_col("ifnull(p.value,ifnull((select value from preferences where configID=c.configID and userID=-1),c.value)) as override");
	    bldsql_col("c.displayType");
	    bldsql_col("c.valuesList");
	    bldsql_where("c.type=$configType");
	    bldsql_where("c.editable=1");
	    bldsql_orderby("c.sortOrder");
            bldsql_orderby("c.configID");
		//var_dump(bldsql_cmd());exit;
	    $a=dosql(bldsql_cmd());
	    if($a){
	        extract($a);
	        $html="<table border='0'><tr><th>"._conf("lang_description")." <span class='smalItal'>("._conf("lang_refreshNotice").")</span>";
		if($configType=="2" && $userID==-1){//editing preference default overrids
			$html.="<div class='text'>"._conf("lang_prefDefaultOverridesExp")."</div>";	
		}
		$html.="</th><th>"._conf("lang_currentValue")."</th></tr>";
	        foreach($configIDs as $i=>$configID){
			/*Build the html input for this item.  Note that each item must have a configs table entry.
			 All items must have a 'lang_config_[itemname]' entry in the lang conf files
			 They can also have a 'lang_config_[itemname]_desc' entry for a text description
			 If item is a select,
			then the item must have a 'lang_config_[itemname]_selectList' entry
			*/
			$name=_conf("lang_config_".$names[$i]);
			$desc=_conf("lang_config_".$names[$i]."_desc");
			$default=$defaults[$i];
			switch($displayTypes[$i]){
				case "text":
					$inp="<input type='text' id='".$configIDs[$i]."_config' value='".$overrides[$i]."' maxLength='255'  onchange=\"updateConfigs(".$configIDs[$i].",'text',$userID);\">";
					break;
				case "select":
					$selectDisplayList=explode(",",_conf("lang_config_".$names[$i]."_selectList"));
                                        //var_dump($names[$i]);
					$valList=explode(",",$valuesLists[$i]);
					$key=array_search($defaults[$i],$valList);//find the default value.. should always find it if everything set up ok
					
                                        if(sizeof($selectDisplayList)!=sizeof($valList)){
						log_message("Sync error with config settings and lang_file settings for config:$name (different # of descriptors and values).  Your lang_[] config file may be out of date.");
						
						/*We used to use the lang override list and just mark new entries as unnamed, but now we'll load from the master list (and assume it's always correct).
						$diff=sizeof($selectDisplayList)-sizeof($valList);
						if($diff<0){//fewer displays.  This could be common if a lang file wasn't updated.
							for($i=1;$i<=abs($diff);$i++){$selectDisplayList[]="[unnamed config option]";}							
						}else for($i=1;$i<=$diff;$i++){$valList[]=$default;}//punting on this one.. not exptected to happen.
						*/
						$selectDisplayList=explode(",",_conf("lang_config_".$names[$i]."_selectList",true));//load list from the master.
					}
					
					$default=$selectDisplayList[$key];
					$inp=j_select(array_combine($valList,$selectDisplayList),$configIDs[$i]."_config",$overrides[$i],1,false,"updateConfigs(".$configIDs[$i].",\"select\",$userID)");
					break;
				case "dynamicFileSelect"://load the list of values(files) from a directory
					$p=explode(",",$valuesLists[$i]);//expected format is dir,ext
					$dir=$p[0];
					$ext=$p[1];
					$valList=array();
					$valList[]=$default;//atleast this one.
					if(is_dir($dir)){
						if($dh=opendir($dir)){
							while(($file = readdir($dh))!== false){
								if(strstr($file,$ext)==$ext && !in_array($file,$valList)){//file ends in this extension
									$valList[]=$file;
								}
							}
						}
					}
					$selectDisplayList=$valList;
					$inp=j_select(array_combine($valList,$selectDisplayList),$configIDs[$i]."_config",$overrides[$i],1,false,"updateConfigs(".$configIDs[$i].",\"select\",$userID)");
					break;
				case "skinSelector"://see if any skins are loaded and use these to create the list.
				    //dynamicFileSelect layout,styles.css
				    $valList=array();
				    $selectDisplayList=array();
				    $skinsDir="skins";
				    if(is_dir($skinsDir)){
					$a=scandir($skinsDir);
					foreach($a as $dir){
					    if(is_dir($skinsDir."/".$dir) & ($dir!=".") & ($dir!="..") & is_file($skinsDir."/".$dir."/styles.css")){
						$valList[]=$skinsDir."/".$dir;
						$selectDisplayList[]=$dir;
					    }
					}
				    }
				    if(count($valList)==0)echo "Error loading skins directory.";
				    			
				    $key=array_search($overrides[$i],$valList);//See if their choices still exists (wasn't deleted) and reset if not
				    if($key===false && $overrides[$i]!=""){//reset to default
					dosql("delete from preferences where configID=10 and value='".scrubTextForDB($overrides[$i])."' and userID=$userID");
					$overrides[$i]=$default;//no need to actually save the default, just make it the selected.
				    }
			
			     	    //$key=array_search($default,$valList);//This one better be there..
                                    //if($key!==false)$default=$selectDisplayList[$key];

				    $inp=j_select(array_combine($valList,$selectDisplayList),$configIDs[$i]."_config",$overrides[$i],1,false,"updateConfigs(".$configIDs[$i].",\"select\",$userID)");
				    break;
				case "number":
					$inp="<input type='text' id='".$configIDs[$i]."_config' value='".$overrides[$i]."' maxLength='5' size='3' onchange=\"updateConfigs(".$configIDs[$i].",'text',$userID);\">";
					break;
			}
			$desc.="<br><div align='right' class='medItal'>"._conf("lang_default").": $default</div>";
	            $html.="<tr><td class='configDesc'><b>$name</b><br>$desc</td><td>$inp<div style='display:inline;' id='".$configIDs[$i]."_config_status'></div></td></tr>";
	        }
	        $html.="</table>";
	    }
	}
	return $html;
}
function updateConfig($configID,$newValue,$userID=UL_UID){

    if(_conf("isDemoSystem"))return;
    $otherJS="";
    
    //Updates the passed configID to value.  If new value is the default, removes the override in preferences table.
    //pass userID =-1 for system default overrides (ie type 2 item overrides.)
    
    $stat=0;

    //fetch the default for this preference
    if($userID>-1)	$a=dosql("select ifnull((select p.value from preferences p where p.configID=c.configID and p.userID=-1),c.value)as value,c.type,c.displayType from configs c where c.configID=$configID",1);
    //use the system default if this is a pref override.
    else $a=dosql("select c.value,c.type,c.displayType from configs c where c.configID=$configID",1);

    if($a){
            extract($a);
            if($type==0)$userID=-1;//system settings don't belong to any user.
            if(($type==2 && $userID>0) || UL_ISADMIN){//anybody can edit a priviledge, only admin can edit other 2
                    
                    if(strtolower(trim($value))==strtolower(trim($newValue))){//delete any overrides that may exist
                            $stat=dosql("delete from preferences where configID=$configID and userID=$userID");
                    }else{//add an override
                            if($displayType=="number" && !is_numeric($newValue)){
                                    return sendStatusMssgHTML(_conf("lang_mustBeNumber"))."<script language='JavaScript'>setDivHTML('".$configID."_config_status','');document.getElementById('".$configID."_config').value=document.getElementById('".$configID."_config').defaultValue</script>";
                            }else{
                                $stat=dosql("replace preferences set configID=$configID, userID=$userID, value='".scrubTextForDB(trim($newValue))."'");
                                
                            }
                    }
            }
    }
    if($stat===false){
            $html=sendStatusMssgHTML(_conf("lang_saveError"))."<script language='JavaScript'>setDivHTML('".$configID."_config_status','');document.getElementById('".$configID."_config').value=document.getElementById('".$configID."_config').defaultValue</script>";
    }else{
        if($stat>0){//Something changed
            $otherJS="";
            //For some of the configs we'll run some other js function after changing a config.  here's where that's set.
            switch($configID){
                case 19:
                    //If this was the catalog path, reset the catalog last updated date too (this is a hidden config) so it will update,
                    //then kick off the update process.                                    
                    dosql("delete from preferences where configID=20 and userID=$userID");
                    $otherJS="prog_startActionWithProgressBar('doWhat=updateCatalog','');";
                    break;
                case 28://playMethod.  Update the js conf vars which only get set when the whole page is loaded.
                    $otherJS="_confSet('playMethod','$newValue');";
                    break;
		case 10://Skin
		   $otherJS='location.reload(true);';
		   session_start();
		   $_SESSION['loadTab']='menu_preferences';//reload this tab after refresh.
		   session_write_close();
		   break;
		case 11://lang file
		   $otherJS='location.reload(true);';
		   session_start();
                   $_SESSION['loadTab']='menu_preferences';//reload this tab after refresh.
                   session_write_close();
		   break;
            }
            $mssg=_conf("lang_saved");
        }else $mssg=_conf("lang_noChange");
    
        $html="<script language='JavaScript'>setDivHTML('".$configID."_config_status','$mssg');setTimeout(\"setDivHTML('".$configID."_config_status','')\",3000);$otherJS</script>";
    }
    
    return $html;
}

function getVersionUpdateInfo(){
    /*does an anonymous connect to remote site to see if any update is available.  If can't connect or no update available, just prints current version
    This should be called in a silent ajax call so it doesn't hang anything if the server is unavailable.
    
    If the send anon stats is enabled, then we'll gather some db metrics to send with the query.
    */
    $html="Version: "._conf("dbVerNum");
    if(_conf("showUpdateNotify")){//Are we allowed to check for updates at all?
        
        $hashID=_conf("uniqID");//Totally random, non-identifiable one way hashed unique(ish) id.  This was generated in lib/dbUpdate.php (4/28/09 update) and is just used to get update status.  If you are paranoid, turn off the update notifier and this will never be used.
        $args="hashID=$hashID&installedVersion="._conf("dbVerNum");
	if(_conf("sendAnonStats")){//Gather anon stats to append on too.  These queries should all be fairly quick.  Regardless they'll all be backgrounded and only run when an admin loads the page for the first time (not while he's navigating the site).
            $numSongs=dosql("select count(*) from songs",0);
            $numArtists=dosql("select count(*) from artists",0);
            $numAlbums=dosql("select count(*) from albums",0);
            $filesSize=dosql("select round(sum(filesize/1024/1024/1024),2) from songs",0);//in GB
            $songsLen=dosql("select round(sum(songlength/60/60/24),2) from songs",0);//in days
            $numUsers=dosql("select count(*) from users where enabled=1",0);
            $numSongsPlayed=dosql("select sum(count) from statistics",0);
            $args.="&numSongs=$numSongs&numArtists=$numArtists&numAlbums=$numAlbums&filesSize=$filesSize&songsLen=$songsLen&numUsers=$numUsers&numSongsPlayed=$numSongsPlayed";
        }
        //The manual said to use this when using fopen with a url, but it didn't seem to get parsed correctly in the php $_REQUEST obj. As I know what all the data is going in, I decided it wasn't necessary.
	//$args=urlencode($args);
        
	$err=error_reporting(0);//Turn off all error reporting.. we want to silently fail if the server isn't available or any other error occurs.
        
	$msg=file_get_contents("http://update.tincanjukebox.com/update.php?$args");
        if(strncasecmp($msg,"New version (",13)==0){//Didn't fail and returned an update link..
            $html=$msg;    
        }     
	error_reporting($err);//reset to whatever it was.
    }
    return $html;
}
?>
