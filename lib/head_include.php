<title><?php echo _conf('siteTitle')?></title>
<script language="JavaScript">
    url="<?php echo _conf("fullSiteAddress");?>/index.php";
    imageUrl="<?php echo _conf("fullSiteAddress");?>/images.php";
</script>
<?php  /*Force a reload of all js and css files each time there's an update by appending the current version to the url.
        This way any changes will get re-cached into the browser...*/
    $ver="?ver="._conf("dbVerNum");

?>
<script src="js/j_ajax.js<?php echo $ver ?>" type="text/javascript"></script>
<!--<script src="js/dhtmlHistory.js<?php echo $ver ?>" type="text/javascript"></script>-->
<script src="js/j_sortable.js<?php echo $ver ?>" type="text/javascript"></script>
<script src="js/jsFuncs.js<?php echo $ver ?>" type="text/javascript"></script>
<script src="js/j_tabs.js<?php echo $ver ?>" type="text/javascript"></script>
<script src="js/clickHandlers.js<?php echo $ver ?>" type="text/javascript"></script>

<!--<link rel="stylesheet" href="layout/styles.css<?php echo $ver ?>" type="text/css">-->
<link rel="stylesheet" href="<?php echo _conf("skinDir")?>/styles.css<?php echo $ver ?>" type="text/css">

<!--<script language="JavaScript" src="js/j_dateFuncs.js"></script>-->
<script language='JavaScript'>
    var _confDataObj=new Object();
    function _conf(name){
        return _confDataObj[name];
    }
    function _confSet(name,value){
        _confDataObj[name]=value;
    }
    <?php
        //Create a data structure to store language translations of anything the js might need to print.
        echo "_confSet('lang_showSongs','"._conf("lang_showSongs")."');\n";
        echo "_confSet('lang_hideSongs','"._conf("lang_hideSongs")."');\n";
        echo "_confSet('lang_play','"._conf("lang_play")."');\n";
        echo "_confSet('lang_addToPlaylist','"._conf("lang_addToPlaylist")."');\n";
        echo "_confSet('lang_saving','"._conf("lang_saving")."');\n";
        echo "_confSet('lang_playlistDeleteError','"._conf("lang_playlistDeleteError")."');\n";
        echo "_confSet('lang_playlistNoNameError','"._conf("lang_playlistNoNameError")."');\n";
        echo "_confSet('lang_showSongs','"._conf("lang_showSongs")."');\n";
        echo "_confSet('lang_showSongs','"._conf("lang_showSongs")."');\n";
        echo "_confSet('lang_showSongs','"._conf("lang_showSongs")."');\n";
        echo "_confSet('lang_showSongs','"._conf("lang_showSongs")."');\n";
        echo "_confSet('nowPlayingRefreshInterval','"._conf("nowPlayingRefreshInterval")."');\n";
        echo "_confSet('lang_reallyDelete','"._conf("lang_reallyDelete")."');\n";
        echo "_confSet('isDemoSystem','"._conf("isDemoSystem")."');\n";
        echo "_confSet('playMethod','"._conf("playMethod")."');\n";
        echo "_confSet('showAlbumArt','"._conf("showAlbumArt")."');\n";
        echo "_confSet('skinDir','"._conf("skinDir")."');\n";
        echo "_confSet('lang_nowPlaying','"._conf("lang_nowPlaying")."');\n";
        echo "_confSet('lang_randomAlbumsHeader','"._conf("lang_randomAlbumsHeader")."');\n";
        echo "_confSet('lang_refreshTab','"._conf("lang_refreshTab")."');\n";        
        
                //set up a timer to start up any queued jobs to do from the updater.
        if($dbUpdate_queuedJob){
            echo "setTimeout('$dbUpdate_queuedJob',1200);";
        }
    ?>
</script>
<?php
if(_conf("playMethod")==2)echo "<script type=\"text/javascript\" src=\"lib/JWPlayer/swfobject.js\"></script>";
?>
