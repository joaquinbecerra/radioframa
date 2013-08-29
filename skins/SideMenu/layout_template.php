<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<head>
<?php include("lib/head_include.php");//REQUIRED. ?>
</head><?php
/*This is the default header/layout.  This can be customized for any skin.
 Any important area is wrapped in a div and will have a comment like <!--DIV for ...-->
 If you are customizing this for a new skin, you should include all of these special divs.  The rest
 (of the tables and layout) is totally arbitrary though.  Note that this divs are populated asyncronously through
 ajax click handling and so they may or may not have content at any given time and so could be of various sizes.
*/
?>
<body onResize="windowResizeEventFired();">
    <table width='100%' cellpadding='0' border='0' cellspacing='0' >
	<tr>
	    <td valign='top'>
		<div style='width:120px;vertical-align: top;'></div>
		<table width='100px' cellpadding='0' border='0' cellspacing='0'>
		    <tr>
			<td valign='top'>
			    <div style='float:left;'>
					
				<!--DIV for logo-->
				<div title='<?php echo _conf("lang_config_siteTitle");?>'>
				    <a href='http://www.tincanjukebox.com'><img class='logo' src='<?php echo _conf("skinDir");?>/images/logo2.gif' alt='' width='120px' height='90' border='0'></a>
				</div>
			    
			    </div>  
			</td>
		    </tr>
		    <tr>
			<td align='left'>
			    
			    <!--DIV and JS to display update status-->
			    <div id='versionUpdateDiv' class='tiny'>		
				<script language='JavaScript'>ajax_getUrl(url,"doWhat=getVersionUpdateStatus","versionUpdateDiv","get",-1,true,"ajax_silentHandler");</script>
			    </div>
			    
			</td>
		    </tr>
		    <tr>
			<td>
			    
			    <!--DIV for the little gif that shows when network activity is going on (ajax async gets)-->
			    <div class='connectionStatus' id='progress'>&nbsp</div>
			    
			</td>
		    </tr>
		    
		    <tr>
			<td align='left'>
			
			    <!--DIV for menu bar.  Note, that the menu bar is actually a table (probably could be all css...)  So if you modify this you just
			    need to ensure that the basic td id/naming/class/onclick actions are maintained.  You can certainly change the orientation and layout though.
			    -->
			    <div style='border:thin outset silver;width:120px;'>
			    <table class='menu_bar' width='100%'>
				<tr><td width="100px" id='menu_home' class='menu_selected' onclick='menu_ItemSelected(this.id,"home","",0);'><?php echo _conf("lang_home")?></td></tr>
				<tr><td width="100px" id='menu_artists' class='menu_unselected' onclick='menu_ItemSelected(this.id,"browse","type=artist",1);'><?php echo _conf("lang_artists")?></td></tr>
				<tr><td width="100px" id='menu_albums' class='menu_unselected' onclick='menu_ItemSelected(this.id,"browse","type=album",1);'><?php echo _conf("lang_albums")?></td></tr>
				<tr><td width="100px" id='menu_genres' class='menu_unselected' onclick='menu_ItemSelected(this.id,"browse","type=genre",0);'><?php echo _conf("lang_genres")?></td></tr>
				<tr><td width="100px" id='menu_playlists' class='menu_unselected' onclick='menu_ItemSelected(this.id,"browse","type=playlist",0);'><?php echo _conf("lang_playlists")?></td></tr>
				    <?php if(defined("UL_HIDE_PREFS") && UL_HIDE_PREFS==1){echo "";}else{?>
				<tr><td width="100px" id='menu_preferences' class='menu_unselected' onclick='menu_ItemSelected(this.id,"editPreferences","",0);'><?php echo _conf("lang_preferences")?></td></tr>
				    <?php }?>
				    <?php if(UL_ISADMIN){?>
				<tr><td width="100px" id='menu_admin' class='menu_unselected' onclick='menu_ItemSelected(this.id,"admin","",0);'><?php echo _conf("lang_admin")?></td></tr>
				    <?php }?>
				
			    </table>
			    </div>
			</td>
		    </tr>
		    <tr>
			<td>
			    <!--DIV for the user admin link (both for users to change their password and for admin to admin users) and the logoff link.-->
			    <div>
				<a href="index.php?UL_showAdmin=1"><font class='tiny'><?php if(UL_ISADMIN==1)echo _conf("lang_userAdmin");else echo _conf("lang_changePassword"); ?></font></a><br>
				<a href="index.php?UL_logoff=1"><font class='tiny'><?php echo _conf("lang_logout");?></font></a>
			    </div>
			</td>
		    </tr>
		    
	   
		</table>
	    </td>
	    <td valign='top' width='100%' align='left'>
		<table width='100%'><tr><td valign='top'>
			
		<table width='100%' border='0'>
		    <tr>
			<td align='left'>
			    <table border='0'>
				<tr>
				    <td align='left'>
					
					<!--DIV for embedded flash player-->
					<div align='left' valign='top' id='flashPlayerDiv'></div>
					
				    </td>
				    <td>
					    
					    <!--DIV for search box-->
					    <div><?php echo getSearchHTML();?></div>
					     
				    </td>
				    <td>
					
					<!--DIV for progress bar text (updates to db catalog and similar)-->
					<div id='progressBarActionDiv' class='textboxPrompt'></div>
					
					<!--DIV for progress bar graphic (updates to db catalog and similar)-->
					<div id='progressBarDiv'></div>
					
				    </td>
				   
				</tr>
			    </table>
			</td>
		    </tr>
		    <tr>
			<td >
			    <!--<hr width='60%'></hr>-->
			</td>
		    </tr>
		    <tr>
			<td valign='top'>
			    
			    <!--DIV for the alphabet browsing letter selections (when browesing by album/artist/genre...)-->
			    <div id='letterBrowseSelects' class='letterBrowseSelects'>&nbsp;</div>
			    
			    <!--DIV for the randome browse title-->
			    <div id='randomBrowseTitleDiv' class='randomBrowseTitleDiv'></div>
			    <!--DIV for the randome browse refresh instructions-->
			    <div id='randomRefreshInstructDiv' class='smalItal'></div>			    
			    <!--DIV for the random browse list (only on the home screen)-->
			    <div id='randomBrowseListDiv'></div>
			    
			    
			    <!--DIV for main data area-->
			    <div class='mainContentArea' id='mainContentAreaDiv'></div>
			
			    <!--DIV for any of the tabbed browseing lists (like when browseing by artist/album...)-->
			    <div id='tabbedBrowseList' style='display:inline;'></div>
			    
			</td>
		    </tr>
		</table>
		</td>
		<td align='right' valign='top'>
		    <!--DIV for the playlist editor-->
		    <div id='playlistEditDiv'></div>

		   
		    <!--DIV for the now playing title-->
		    <div id='nowPlayingTitleDiv' class='nowPlayingTitleDiv'></div>
		    <!--DIV for the now playing area (only on the home screen)-->
		    <div id='nowPlayingDiv' ></div>
		</td></tr></table>		
	    </td>

	</tr>
    </table>
    
    
    
   
<!--DIV for status messages after updates occur-->
<div id='statusDiv'>
    <?php
	$mssg=_conf("updateMessage");
	if($mssg!=""){echo "$mssg <script language='JavaScript'>setTimeout('setDivHTML(\"statusDiv\",\"\")',5000)</script>";}
    ?>
</div>

<!--DIV for dynamic javascript to be loaded into from the server-->
<div id='jsDiv'><?php echo $html;//default content ?></div>

<br><br>

<!--DIV for version info and link to tcj site... This isn't required at all though.-->
<div align='left'><a href='http://www.tincanjukebox.com'><font class='tiny'><?php echo "Version: "._conf("dbVerNum");?></font></a></div>

</body>
</html>
