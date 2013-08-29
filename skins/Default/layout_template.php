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
<!--Header above the menu bar-->
<table width='100%' cellpadding='0' border='0' cellspacing='0' >
    <tr>
	    
        <td width='360px' align='left' title='<?php echo _conf("lang_config_siteTitle");?>'>
	
	    <!--DIV for logo-->
	    <div title='<?php echo _conf("lang_config_siteTitle");?>'>
		<a href='http://www.tincanjukebox.com'><img class='logo' src='<?php echo _conf("skinDir");?>/images/logo2.gif' alt='' width='360' height='70' border='0'></a>
	    </div>
	    
	</td>
	
	<td valign='top' align='left'>
	
	    <!--DIV for embedded flash player-->
	    <div align='left' valign='top' id='flashPlayerDiv'></div>
	    
	</td>
	    
        <td valign='top' style="height:100%;">
            
            <table cellpadding='0' border='0' cellspacing='0' width='100%' style="height:100%;">
                <tr>
		    <td colspan='2' valign='top'>
			<table border='0' cellpadding='0' cellspacing='0' width='100%'>
			    <tr>
				<td align='right' valign='top'>
	
				    <!--DIV and JS to display update status-->
				    <div id='versionUpdateDiv' class='tiny'>		
					<script language='JavaScript'>ajax_getUrl(url,"doWhat=getVersionUpdateStatus","versionUpdateDiv","get",-1,true,"ajax_silentHandler");</script>
				    </div>
				    
				</td>
			    </tr>
			</table>
		    </td>
		</tr>
                <?php if(_conf("isDemoSystem") ){?>
                    <tr>
                        <td colspan='2' valign='top'><a style='float:right' href="http://sourceforge.net"><img src="http://sflogo.sourceforge.net/sflogo.php?group_id=251128&amp;type=1" width="88" height="31" border="0" alt="SourceForge.net Logo" /></a>
                        <span  class='tiny'>This site is a limited functionality (no logins, no playing anything) demo hosted by the good people at Sourceforge.  The library below is taken from an installation with a little over 100GB of music to give you a feel for how browsing your collection would be.  As this is a massively shared server, your installation will likely be a bit faster...</span></td> 
                    </tr>
                <?php }?>
		<tr>
                    <td align='right' valign='bottom'>
	
			<!--DIV for search box-->
			<div><?php echo getSearchHTML();?></div>
			
		    </td>
                    <td align='right'>
			<div style='float:right;'>
			    
			    <!--DIV for progress bar text (updates to db catalog and similar)-->
			    <div id='progressBarActionDiv' class='textboxPrompt'></div>
			
			</div>
			
			<div style='float:right;'>
			    
			    <!--DIV for progress bar graphic (updates to db catalog and similar)-->
			    <div id='progressBarDiv'></div>
			    
			</div>
			
		    </td>
                </tr>
            </table>
    
        </td>
    </tr>
</table>

<!--DIV for menu bar.  Note, that the menu bar is actually a table (probably could be all css...)  So if you modify this you just
need to ensure that the basic td id/naming/class/onclick actions are maintained.  You can certainly change the orientation and layout though.
-->
<table width='100%' class='menu_bar'>
    <tr><td width="30px" class='menu_filler'>&nbsp</td><!--Note the format (menu_[type]s) of the menu ids is expected elsewhere..-->
        <td width="100px" id='menu_home' class='menu_selected' onclick='menu_ItemSelected(this.id,"home","",0);'><?php echo _conf("lang_home")?></td>
        <td width="100px" id='menu_artists' class='menu_unselected' onclick='menu_ItemSelected(this.id,"browse","type=artist",1);'><?php echo _conf("lang_artists")?></td>
        <td width="100px" id='menu_albums' class='menu_unselected' onclick='menu_ItemSelected(this.id,"browse","type=album",1);'><?php echo _conf("lang_albums")?></td>
        <td width="100px" id='menu_genres' class='menu_unselected' onclick='menu_ItemSelected(this.id,"browse","type=genre",0);'><?php echo _conf("lang_genres")?></td>
        <td width="100px" id='menu_playlists' class='menu_unselected' onclick='menu_ItemSelected(this.id,"browse","type=playlist",0);'><?php echo _conf("lang_playlists")?></td>
        <?php if(defined("UL_HIDE_PREFS") && UL_HIDE_PREFS==1){echo "";}else{?>
        <td width="100px" id='menu_preferences' class='menu_unselected' onclick='menu_ItemSelected(this.id,"editPreferences","",0);'><?php echo _conf("lang_preferences")?></td>
        <?php }?>
        <?php if(UL_ISADMIN){?>
        <td width="100px" id='menu_admin' class='menu_unselected' onclick='menu_ItemSelected(this.id,"admin","",0);'><?php echo _conf("lang_admin")?></td>
        <?php }?>
        <td class='menu_filler'>&nbsp</td>        
    </tr>
</table>


<!--Server connection status (activity graphic) and logout links-->
<table width='100%' cellpadding='0'>
    <tr>
	<td align='left' width='100'>
	
	    <!--DIV for the little gif that shows when network activity is going on (ajax async gets)-->
	    <div class='connectionStatus' id='progress'>&nbsp</div>
	
	</td>
	<td align='center'>
	    
	    <!--DIV for the alphabet browsing letter selections (when browesing by album/artist/genre...)-->
	    <div id='letterBrowseSelects' class='letterBrowseSelects'>&nbsp;</div>
	
	</td>
	<td align='right' style="width:150px;">
	    
	    <!--DIV for the user admin link (both for users to change their password and for admin to admin users) and the logoff link.-->
	    <div><a href="index.php?UL_showAdmin=1"><font class='tiny'><?php if(UL_ISADMIN==1)echo _conf("lang_userAdmin");else echo _conf("lang_changePassword"); ?></font></a> <a href="index.php?UL_logoff=1"><font class='tiny'><?php echo _conf("lang_logout");?></font></a></div>
	
	</td>
    </tr>
</table>

<!--Main data area-->
<table width='100%' cellpadding='0' border='0'>
	<tr>
		<td valign='top'>

		    <table width='100%'>
			<tr>
			    <td>
				<div id='nowPlayingTitleDiv' class='nowPlayingTitleDiv'></div>
			    </td>
			    <td>
				<div id='randomBrowseTitleDiv' class='randomBrowseTitleDiv'></div><div id='randomRefreshInstructDiv' class='smalItal'></div>
			    </td>
			</tr>
			<tr>
			    <td valign='top' align='left'>
				
				<!--DIV for the now playing area (only on the home screen)-->
				<div id='nowPlayingDiv'></div>
			    
			    </td>
			    <td valign='top' align='right'>
				
				<!--DIV for the random browse list (only on the home screen)-->
				<div id='randomBrowseListDiv'></div>
			
			    </td>
			</tr>
		    </table>
		
			<!--DIV for main data area.-->
			<div class='mainContentArea' id='mainContentAreaDiv'></div>
			
			<!--DIV for any of the tabbed browseing lists (like when browseing by artist/album...)-->
			<div id='tabbedBrowseList' style='display:inline;'></div>
			
		</td>
		<td align="left" valign="top">
		    
			<!--DIV for the playlist editor-->
			<div id='playlistEditDiv'></div>
			
		</td>
	<tr>
</table>
    
<!--DIV for status messages after updates occur-->
<div id='statusDiv'>
    <?php
	$mssg=_conf("updateMessage");
	if($mssg!=""){echo "$mssg <script language='JavaScript'>setTimeout('setDivHTML(\"statusDiv\",\"\")',5000)</script>";}
    ?>
</div>

<!--DIV for dynamic javascript to be loaded into from the server-->
<div id='jsDiv'><?php echo $html;//default content loader ?></div>

<br><br>

<!--DIV for version info and link to tcj site... This isn't required at all though.-->
<div align='left'><a href='http://www.tincanjukebox.com'><font class='tiny'><?php echo "Version: "._conf("dbVerNum");?></font></a></div>

</body>
</html>
