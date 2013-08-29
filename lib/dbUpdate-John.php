<?php
$updateNeeded=false;
$updateMssg="";
$ok=true;

$updateToVer=20090117;
if($currDbVersion<$updateToVer){
    $updateNeeded=true;
    
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Creating and populating system tables");
    
    $ok=dbUpdate("DROP TABLE IF EXISTS albums");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `albums` (
        `albumID` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `year` char(4) DEFAULT NULL,
        `md5` varchar(255) DEFAULT NULL,
        `folderPath` text,
        `albumArtFile` text,
        `albumArtFileLastMod` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`albumID`),
        KEY `name` (`name`)
        ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1");
    if($ok!==false)$ok=dbUpdate("DROP TABLE IF EXISTS albums_songs");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `albums_songs` (
          `albumID` int(10) unsigned NOT NULL,
          `songID` int(10) unsigned NOT NULL,
          PRIMARY KEY (`albumID`,`songID`),
          KEY `songID` (`songID`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1");
    if($ok!==false)$ok=dbUpdate("DROP TABLE IF EXISTS artists");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `artists` (
        `artistID` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'SEE cat_migrateSongToNewArtist if you link to artistID',
        `name` varchar(255) NOT NULL,
        PRIMARY KEY (`artistID`),
        KEY `name` (`name`(50))
        ) ENGINE=MyISAM AUTO_INCREMENT=11197 DEFAULT CHARSET=latin1 COMMENT='SEE cat_migrateSongToNewArtist if you link to artistID'");
    if($ok!==false)$ok=dbUpdate("DROP TABLE IF EXISTS artists_songs");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `artists_songs` (
        `artistID` int(10) unsigned NOT NULL,
        `songID` int(10) unsigned NOT NULL,
        PRIMARY KEY (`artistID`,`songID`),
        KEY `songID` (`songID`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1");
    if($ok!==false)$ok=dbUpdate("DROP TABLE IF EXISTS configs");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `configs` (
          `configID` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL COMMENT 'This is the name of this config used internally (in php code)\n',
          `value` varchar(255) NOT NULL,
          `type` tinyint(3) unsigned NOT NULL DEFAULT '2' COMMENT 'valid values: 0=system,1=privilege,2=preference',
          `displayType` varchar(20) NOT NULL COMMENT 'valid values: select, text,number,dynamicFileSelect\nIf select, then values list must be entered and displaytext lang entry must exist in the lang conf file\nIf dynamincFileSelect then valuesList must contain ''directory,file-ext''\n',
          `valuesList` varchar(255) DEFAULT NULL COMMENT 'for select list, these are the choices (comma seperated).  Displayed text is in lang conf files',
          `sortOrder` int(11) NOT NULL DEFAULT '1',
          `editable` tinyint(4) NOT NULL DEFAULT '1',
          PRIMARY KEY (`configID`),
          UNIQUE KEY `name` (`name`)
        ) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=latin1 COMMENT='This is the dictionary table of all configs with default val'");
    if($ok!==false)$ok=dbUpdate("DROP TABLE IF EXISTS genres");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `genres` (
        `genreID` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `description` varchar(255) NOT NULL,
        PRIMARY KEY (`genreID`)
        ) ENGINE=MyISAM AUTO_INCREMENT=904 DEFAULT CHARSET=latin1");
    if($ok!==false)$ok=dbUpdate("DROP TABLE IF EXISTS genres_songs");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `genres_songs` (
        `genreID` int(10) unsigned NOT NULL,
        `songID` int(10) unsigned NOT NULL,
        PRIMARY KEY (`genreID`,`songID`),
        KEY `songID` (`songID`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1");
    if($ok!==false)$ok=dbUpdate("DROP TABLE IF EXISTS nowPlaying");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `nowPlaying` (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `startTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `completed` tinyint(4) NOT NULL DEFAULT '0',
        `userID` int(10) unsigned NOT NULL,
        `songLength` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Slightly denormalized to let the pruning functions run without table joins.',
        `songID` int(10) unsigned NOT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=MyISAM AUTO_INCREMENT=205222 DEFAULT CHARSET=latin1 COMMENT='Current and recently played songs'");
    if($ok!==false)$ok=dbUpdate("DROP TABLE IF EXISTS playlistItems");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `playlistItems` (
        `playlistItemID` int(11) NOT NULL AUTO_INCREMENT,
        `playlistID` int(11) NOT NULL,
        `itemType` varchar(45) NOT NULL,
        `itemID` int(11) NOT NULL,
        `seq` int(11) NOT NULL,
        PRIMARY KEY (`playlistItemID`) ,
        UNIQUE KEY `playlistID_seq` (`playlistID`,`seq`) 
        ) ENGINE=MyISAM AUTO_INCREMENT=145 DEFAULT CHARSET=latin1");
    if($ok!==false)$ok=dbUpdate("DROP TABLE IF EXISTS playlists");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `playlists` (
        `playlistID` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `userID` int(10) unsigned NOT NULL,
        `name` varchar(255) NOT NULL,
        `public` tinyint(1) NOT NULL,
        PRIMARY KEY (`playlistID`)
        ) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=latin1");
    if($ok!==false)$ok=dbUpdate("DROP TABLE IF EXISTS preferences");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `preferences` (
        `configID` int(10) unsigned NOT NULL,
        `userID` int(11) NOT NULL COMMENT 'negative 1 is ''system'' user',
        `value` varchar(255) NOT NULL,
        PRIMARY KEY (`configID`,`userID`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='overrides to any system config'");
    if($ok!==false)$ok=dbUpdate("DROP TABLE IF EXISTS songs");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `songs` (
        `songID` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `songName` varchar(255) NOT NULL,
        `tagMD5` varchar(32) DEFAULT NULL,
        `filesize` int(10) DEFAULT NULL,
        `file` text COMMENT 'Note this is the reverse (strrev()) of the file to make the index more selective',
        `songLength` float unsigned DEFAULT NULL COMMENT 'seconds',
        `bitRate` float DEFAULT NULL,
        `trackNo` varchar(7) DEFAULT NULL,
        `track_volume` float DEFAULT NULL,
        `fileFormat` varchar(255) DEFAULT NULL,
        `albumNameFromTag` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`songID`),
        UNIQUE KEY `file` (`file`(255)) ,
        KEY `songName` (`songName`)
        ) ENGINE=MyISAM AUTO_INCREMENT=219028 DEFAULT CHARSET=latin1");
    if($ok!==false)$ok=dbUpdate("DROP TABLE IF EXISTS statistics");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `statistics` (
        `type` varchar(20) NOT NULL,
        `itemID` int(10) unsigned NOT NULL,
        `userID` int(11) NOT NULL,
        `count` int(10) unsigned NOT NULL,
        `lastPlayed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`type`,`itemID`,`userID`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1");
    if($ok!==false)$ok=dbUpdate("DROP TABLE IF EXISTS users");
    if($ok!==false)$ok=dbUpdate("CREATE TABLE `users` (
        `userID` int(11) NOT NULL AUTO_INCREMENT,
        `userName` varchar(40) NOT NULL,
        `password` varchar(40) NOT NULL,
        `admin` tinyint(4) NOT NULL DEFAULT '0',
        `enabled` tinyint(4) NOT NULL DEFAULT '1',
        `lastLogin` date DEFAULT NULL,
        `fname` varchar(100) DEFAULT NULL,
        `lname` varchar(100) DEFAULT NULL,
        `email` varchar(255) DEFAULT NULL,
        `storedSessionData_hide` text NOT NULL,
        PRIMARY KEY (`userID`),
        UNIQUE KEY `userName` (`userName`)
        ) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1");
    if($ok!==false)$ok=dbUpdate("REPLACE INTO configs VALUES  (1,'minGenreSongsInAlbumThreshold','2',2,'number',NULL,1,1),
         (2,'limitToArtistsWithFullAlbums','0',2,'select','1,0',1,1),
         (3,'siteTitle','TinCanJukebox',0,'text','',1,1),
         (4,'artistSongThreshold','1',2,'number',NULL,1,1),
         (5,'showWhatForGenre','album',2,'select','album',1,1),
         (6,'ablumArtFileName','folder.jpg',0,'text',NULL,1,1),
         (7,'error_log','/tmp/error_log.txt',0,'text',NULL,1,1),
         (8,'defaultAlbumArt','skins/Default/images/defaultAlbumArt.jpg',0,'text',NULL,1,1),
         (9,'normalLayoutTemplate','norm_template.php',0,'dynamicFileSelect','layout,_template.php',1,1),
         (10,'cssFile','styles.css',0,'dynamicFileSelect','layout,.css',1,1),
         (11,'langFile','lang_english.php',2,'dynamicFileSelect','lang,.php',1,1),
         (12,'nowPlayingRecentness','60',2,'number',NULL,1,1),
         (13,'randomBrowse','0',2,'select','1,0',1,1),
         (14,'numNowPlayingItems','10',2,'number',NULL,1,1),
         (15,'nowPlayingRefreshInterval','d1',0,'select','d1,d2,2,3,4,5,10,20,30,45,60,90,120,180,240',1,1),
         (16,'maxDownSampleRate','0',0,'number',NULL,1,1)");
    if($ok!==false)$ok=dbUpdate("REPLACE INTO configs VALUES  (17,'downSampleCmd','/usr/local/bin/lame --mp3input -q 3 -b %RATE% -S %FILE% -',0,'text',NULL,1,1),
         (18,'masterAuthKey','secret auth key',0,'text',NULL,1,1),
         (19,'mainCatalogFilePath','',0,'text',NULL,0,1),
         (20,'catalogLastUpdatedDate','0',0,'number',NULL,1,0),
         (21,'dbVerNum','20090117',0,'number',NULL,1,0)");
    if($ok!==false && dbUpdate_setDBVer($updateToVer)){
            $currDbVersion=$updateToVer;
    }
    
    
    //On this first one, drop out to let user create the admin user login.  We'll pickup again after he logs in.
    $content.=$updateMssg;
    if($ok!==false)$content.="<br><br><br>System table creation was <b>successfull</b>.<br> You now need to create an admin user before continuing.  After logging in, the db update utility will complete.";
    else $content.="There was a problem doing some of the updates.  Check error logs for details.  Current DB Version is <b>$currDbVersion</b>.  You can also post this to the forums at tincanjukebox.com<br>";
    $content.="<br><br>".j_hrefBtn($_SERVER['PHP_SELF'],"Continue");
    dbUpdate_outputHTMLpage($content);
}

$updateToVer=20090120;
if($ok!==false && $currDbVersion<$updateToVer){   
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Adding versioning info to the configs table.");
    
    $ok=dbUpdate("ALTER TABLE configs ADD COLUMN `dbVersionAdded` varchar(50)  DEFAULT NULL COMMENT 'reference for the devs to know which configs are new' AFTER `editable`;");
    if($ok!==false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
    
}

$updateToVer=20090121;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Changing some config defaults.");
        
    $ok=dbUpdate("update configs set value=8 where configID=14");
    if($ok!==false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
    
}

$updateToVer=20090122;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Enabling custom css conf option.");
    
    $ok=dbUpdate("update configs set valuesList='layout,styles.css' where configID=10");
    if($ok!==false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
    
}

$updateToVer=20090123;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Adding ability to store album art in database if needed (for demo db and for future amazon art fetch ability).");

    $ok=dbUpdate("ALTER TABLE `albums` ADD COLUMN `jpgImgData` MEDIUMBLOB  DEFAULT NULL COMMENT 'Default location for albumart is on disk.. this is a temp location for the demo db and for loading from the web.' AFTER `albumArtFileLastMod`;");
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}

$updateToVer=20090129;
if($ok!==false && $currDbVersion<$updateToVer){
    //get confirmation for anon statistics optin
    /*WTF?  There's some kind of wackiness with the creating a new admin user (above, right after dropping out of the first update.  This page gets run with no output (or maybe gets a location header change after starting up) and then comes back a 2nd time.. The effect is that you don't see the out put from 2nd update to here in the conf messg?! */

    $updateMssg.=dbUpdate_getMssg($updateToVer,"");
    $doAnon=dbUpdate_getConfirmAnswer($updateToVer);
    if($doAnon===false){
        dbUpdate_confirm("This version ($updateToVer) update allows the occasional sending of completely anonymous usage statistics.  Do you want this feature enabled?",$updateToVer,$updateMssg);
    }else{
        
        $updateNeeded=true;
        $updateMssg.=dbUpdate_getMssg("","Adding admin statistics tab (Admin->statistics) to show various system information.");
        $updateMssg.=dbUpdate_getMssg("","Adding ablity to display update notifications (only when admin logs in) <b>NOTE</b>; This is <b>ENABLED</b> by default.  If you do not want to be notified of updates you will need to change the default settings in the admin->system configs.</b>");
        $opt=($doAnon==1)?"ENABLE":"DISABLE";
        $updateMssg.=dbUpdate_getMssg("","Adding ability to send anonymous usage statistics.  You have chosen to <b> $opt </b> this option.");
        
        $ok=dbUpdate("replace configs set configID=24, name='showUpdateNotify',value='1',type=0,displayType='select',valuesList='1,0',sortOrder=1,editable=1,dbVersionAdded=''");
        if($ok)$ok=dbUpdate("replace configs set configID=23, name='sendAnonStats',value='1',type=0,displayType='select',valuesList='1,0',sortOrder=1,editable=1,dbVersionAdded=''");
        updateConfig(23,$doAnon,-1);//This does nothing if user enabled, sets a pref override if user disabled.
    
        if($ok!=false && dbUpdate_setDBVer($updateToVer)){
            $currDbVersion=$updateToVer;
        }
    }
}


$updateToVer=20090201;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Updating default location of the error log to /tmp/tcj_error_log.txt");
        
    $ok=dbUpdate("update configs set value='/tmp/tcj_error_log.txt' where configID=7");
    
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}

$updateToVer=20090206;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Creating user privledge to download playlists (default false).");
    $updateMssg.=dbUpdate_getMssg("","Creating a system config to specify download cmd to use for streaming files to user.");
    $updateMssg.=dbUpdate_getMssg("","Creating a system config to specify how many albums is 'recent'.");
    
    $ok=dbUpdate("replace configs set configID=25,name='allowDownloads',value='0',type=1,displayType='select',valuesList='1,0'");
    if($ok)$ok=dbUpdate("replace configs set configID=26,name='downloadCmd',value='tar -cz -f - -T [file]',type=0,displayType='text',valuesList=null");
    if($ok)$ok=dbUpdate("Replace configs set configID=27, name='numRandRecentAlbums', value='35', type=0, displayType='number', valuesList='', editable=1 ");
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}

$updateToVer=20090219;
if($ok!==false && $currDbVersion<$updateToVer){//Dummy update to get us to today's release
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Updating default site title to TinCanJukebox");
    
    $ok=dbUpdate("update configs set value='TinCanJukebox' where configID=3");
    
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}

$updateToVer=20090304;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;

    $updateMssg.=dbUpdate_getMssg($updateToVer,"Creating a user preference to control the method of playback (open playlist on client or stream through embedded flash player).  Note you can set the 'default' for all users for this preference in Admin->Preference Defaults->Play Method.");
    $updateMssg.=dbUpdate_getMssg("","Creating a user preference to control which type of playlist to output (currently just M3U and XSPF).");
    $updateMssg.=dbUpdate_getMssg("","Creating a user preference to control which tab on the random items object on the home page is the default tab.");

    if($ok)$ok=dbUpdate("Replace configs set configID=28, name='playMethod', value='0', type=2, displayType='select', valuesList='0,1', editable=1 ");
    if($ok)$ok=dbUpdate("Replace configs set configID=29, name='playListType', value='M3U', type=2, displayType='select', valuesList='M3U,XSPF', editable=1 ");
    if($ok)$ok=dbUpdate("Replace configs set configID=30, name='defaultRandomTab', value='1', type=2, displayType='number', valuesList='', editable=1 ");
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}


$updateToVer=20090315;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;

    $updateMssg.=dbUpdate_getMssg($updateToVer,"Adding a user preference to turn off the display of Album Art in the Now Playing list and while browsing your music collection.");
    $updateMssg.=dbUpdate_getMssg("","Adding initial cacheing support to allow browsers to cache dynamcially created images (album art).  Further cacheing enhancements to come after more useage testing.");
    
    if($ok)$ok=dbUpdate("Replace configs set configID=31, name='showAlbumArt', value='1', type=2, displayType='select', valuesList='0,1', editable=1 ");
    if($ok)$ok=dbUpdate("ALTER TABLE albums ADD COLUMN `imgDataLastMod` VARCHAR(255)  DEFAULT NULL AFTER `jpgImgData`");
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}


$updateToVer=20090330;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;

    $updateMssg.=dbUpdate_getMssg($updateToVer,"Adding a system preference to control the level of debug logging.");
    //$updateMssg.=dbUpdate_getMssg("","Adding a system preference to allow auto update of albumart caches.");
    $updateMssg.=dbUpdate_getMssg("","Adding a system preference to allow auto update of the catalog (when admin logs in).");
    $updateMssg.=dbUpdate_getMssg("","Adding image cache columns to the albums table.");
    $updateMssg.=dbUpdate_getMssg("","Adding system preference to allow periodic auto optimize of db tables (when admin logs in).");
    
    if($ok)$ok=dbUpdate("Replace configs set configID=32, name='log_level', value='1', type=0, displayType='select', valuesList='1,2,3', editable=1 ");

    $doCache=(dosql("select count(*) from albums",0)>0)?1:0;//only set the flag to do the auto-cacheing if this is an update and not a first time install.
    if($ok)$ok=dbUpdate("Replace configs set configID=33, name='autoUpdateImgCaches', value='$doCache', type=0, displayType='number', valuesList='', editable=0 ");
    if($ok)$ok=dbUpdate("Replace configs set configID=34, name='autoUpdateCatalog', value='1', type=0, displayType='select', valuesList='0,1', editable=1 ");
    if($ok)$ok=dbUpdate("Replace configs set configID=35, name='autoOptimizeTables', value='1', type=0, displayType='select', valuesList='0,1', editable=1 ");
    if($ok)$ok=dbUpdate("Replace configs set configID=36, name='lastOptimizeDate', value='0', type=0, displayType='text', valuesList='', editable=0 ");
    if($ok)$ok=dbUpdate("ALTER TABLE `albums` ADD COLUMN `jpgThmImgData` mediumblob  COMMENT 'precached resized thm img' AFTER `imgDataLastMod`,
 ADD COLUMN `jpgLgThmImgData` mediumblob  COMMENT 'The larger size albumart thm' AFTER `jpgThmImgData`;");
    
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}

$updateToVer=20090401;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Minor version update for bug fix.");

    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}


$updateToVer=20090430;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Adding new flash player types to the config options.");
    
    if($ok)$ok=dbUpdate("update configs set valuesList='0,1,2,3,4,5,6,7' where configID=28");
    if($ok)$ok=dbUpdate("Replace configs set configID=37, name='uniqID', value='tcj_".md5(uniqid(rand(), true))."', type=0, displayType='text', valuesList='', editable=0 ");
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}


$updateToVer=20090817;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Adding a user preference to set a max bit rate for streamed music (for the bandwidth impaired).");

    if($ok)$ok=dbUpdate("Replace configs set configID=38, name='maxBitRate', value='0', type=2, displayType='number', valuesList='', editable=1 ");
    if($ok)$ok=dbUpdate("ALTER TABLE `songs` ADD COLUMN `imgDataLastMod` VARCHAR(255) AFTER `albumNameFromTag`, ADD COLUMN `jpgThmImgData` MEDIUMBLOB AFTER `imgDataLastMod`");
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}

$updateToVer=20090923;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}

$updateToVer=20091008;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Adding a system config to limit the number of thumbnails to pre-cache when updating the library.");

    if($ok)$ok=dbUpdate("Replace configs set configID=39, name='numThumbsToPrecache', value='-1', type=0, displayType='number', valuesList='', editable=1 ");
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}


$updateToVer=20091108;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Adding a cool new flash player type (neoMp3Player) and setting as the default player.");
    $updateMssg.=dbUpdate_getMssg("","Adding a config to hold the flac decoder command.");
    $updateMssg.=dbUpdate_getMssg("","Adding a config to hold the ogg decoder command.");
    $updateMssg.=dbUpdate_getMssg("","Adding a user priviledge to turn off downsampling");
    
    if($ok)$ok=dbUpdate("update configs set valuesList='0,1,2,3,4,5,6,7,8',value=8 where configID=28"); 
    if($ok)$ok=dbUpdate("Replace configs set configID=40, name='flacCommand', value='/usr/local/bin/flac -dcs --skip=%SEEK% %FILE%', type=0, displayType='text', valuesList='', editable=1 ");
    if($ok)$ok=dbUpdate("Replace configs set configID=41, name='oggCommand', value='/usr/local/bin/oggdec -QR -o - %FILE%', type=0, displayType='text', valuesList='', editable=1 ");
    if($ok)$ok=dbUpdate("Replace configs set configID=42, name='neverConvert', value='0', type=1, displayType='select', valuesList='0,1', editable=1 ");
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}

$updateToVer=20100224;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Adding the most excellent JW Player as an output option (a popup-with visuals).");
    
    //$updateMssg.=dbUpdate_getMssg("","Adding a config to put the import process into 'conservative' mode which avoids any file that may have previously caused a problem.");
    $updateMssg.=dbUpdate_getMssg("","Adding db confings support to play PLS formatted playlists.");
    $updateMssg.=dbUpdate_getMssg("","Adding db configs support for skinning.");
    $updateMssg.=dbUpdate_getMssg("","Adding db configs support to set a locale (lang set) for different charsets.");
 
    if($ok)$ok=dbUpdate("update configs set valuesList='0,1,2,3,4,5,6,7,8,9',value=8 where configID=28"); 
    if($ok)$ok=dbUpdate("update configs set valuesList='M3U,XSPF,PLS',value='M3U' where configID=29"); 
    if($ok)$ok=dbUpdate("update configs set name='skinDir',type=2,valuesList=null,value='skins/Default',displayType='skinSelector' where configID=10");
    if($ok)$ok=dbUpdate("Replace configs set configID=44, name='php-locale', value='', type=0, displayType='text', valuesList='', editable=1 ");
    dosql("delete from preferences where configID=10"); //remove anything that may have been added previously... sorry.
    
    
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}

$updateToVer=20101214;
if($ok!==false && $currDbVersion<$updateToVer){
    $updateNeeded=true;
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Change default values in preferences.");
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Adding db support for allowed music extension.");
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Adding 'All' option in albums and genre selection.");
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Adding album creation in multi-album per folder.");
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Adding compatibility with SQL4.1 upward.");
    $updateMssg.=dbUpdate_getMssg($updateToVer,"Correcting a few bugs, typing errors.");
    
    if($ok)$ok=dbUpdate("update configs set value='1' where configID=1");
    if($ok)$ok=dbUpdate("update configs set value='0' where configID=4");
    if($ok)$ok=dbUpdate("Replace configs set configID=45, name='allowedMusicExtensions', value='.mp3, .ogg, .flac, .wma, .wav, .m4a', type=0, displayType='text', valuesList='', editable=1 ");
    if($ok!=false && dbUpdate_setDBVer($updateToVer)){
        $currDbVersion=$updateToVer;
    }
}




if($updateNeeded){//We had to update something.  Display what was changed and any error condition.
    $content.=$updateMssg;
    if($ok!==false)$content.="<br><br><br>All database updates were <b>successfull</b> (Software updates have already been applied).  Current DB Version is <b>$currDbVersion</b>.<br><br><a href='"._conf("fullSiteAddress")."/lib/releaseNotes.txt' target='_new'>View Release Notes</a><br>";
    else $content.="There was a problem doing some of the updates.  Check error logs for details.  Current DB Version is <b>$currDbVersion</b>.  You can also post this to the forums at tincanjukebox.com<br>";

    $content.="<br><br><a href='".$_SERVER['PHP_SELF']."'>Click Here</a> to continue.";
    dbUpdate_outputHTMLpage($content);
}
function dbUpdate_outputHTMLpage($content){//wrapper to send output from dbupdate..
    echo "<html><head><title>Tin Can Jukebox - DB Update Utility</title><link rel='stylesheet' href='layout/styles.css' type='text/css'>
</head><body><div style='font-size: 14px;color: #666666; background-color: #FFFFFF;'><div align='center'><h3>Database update utility</h3></div>";
    echo $content;
    echo "</div></body></html>";
    exit;
}
function dbUpdate_confirm($confirmTxt,$version,$updateMssgSoFar){    
    //Displays a confirm dialog with a yes/no answer
    $html=$updateMssgSoFar."<br><br>".$confirmTxt;
    $html.="<br><br><table><tr><td>".j_hrefBtn($_SERVER['PHP_SELF']."?dbUpdate_confirm_version=$version&dbUpdate_cofirm_yes=1","Yes")."</td>";
    $html.="<td>".j_hrefBtn($_SERVER['PHP_SELF']."?dbUpdate_confirm_version=$version&dbUpdate_cofirm_yes=0","No")."</td></tr></table>";
    //$html.="<br><a href='".$_SERVER['PHP_SELF']."?dbUpdate_confirm_version=$version&dbUpdate_cofirm_yes=1'>Yes</a>";
    //$html.=" <a href='".$_SERVER['PHP_SELF']."?dbUpdate_confirm_version=$version&dbUpdate_cofirm_yes=0'>No</a>";
    dbUpdate_outputHTMLpage($html);
}
/*//not yet implemented, this will allow the music folder location to be entered automatically.
function dbUpdate_prompt($promptTxt,$version,$updateMssgSoFar){
    //Displays a confirm dialog with a yes/no answer
    $html=$updateMssgSoFar."<br><br>".$confirmTxt;
    
    $html.="<br><table><tr><td><a href='".$_SERVER['PHP_SELF']."?dbUpdate_confirm_version=$version&dbUpdate_cofirm_yes=1'>Yes</a></td>";
    $html.="<td><a href='".$_SERVER['PHP_SELF']."?dbUpdate_confirm_version=$version&dbUpdate_cofirm_yes=0'>No</a></td></tr></table>";
    dbUpdate_outputHTMLpage($html);
}*/
function dbUpdate_getConfirmAnswer($version){
    //returns false if not confirmed (yes or no) yet... so caller should put up the confirmation mssg.  If it has already be displayed, this
    //returns 1 for 'yes' and 0 for 'no'
    if($_REQUEST['dbUpdate_confirm_version']==$version){
        require_once("lib/admin_functions.php");//link in the admin funcs (to set config overrides) here so caller's above won't have to.
        if(isset($_REQUEST['dbUpdate_cofirm_yes'])){
            $ans=$_REQUEST['dbUpdate_cofirm_yes'];
            if($ans==1 || $ans==0) return $ans;
        }        
    }
    return false;
}
function dbUpdate_getMssg($updateToVer,$description){
    if($updateToVer)$mssg="<br>Updating database to version <b>$updateToVer</b> (see release notes for full change details)<br>";
    if($description)$mssg.="&nbsp;&nbsp;&nbsp; -$description<br>";
    return $mssg;
}
function dbUpdate($sql){//wrapper to run sql for db updates
    $ret=true;
    if(_conf("isDevGM")!='1'){//bypass on the develpment server
        $ret=(dosql($sql)!==false);
    }
    return $ret;
}
function dbUpdate_setDBVer($ver){
    $ret=false;
    if(dosql("update configs set value='$ver' where name='dbVerNum'")){
        dosql("update configs set dbVersionAdded='$ver' where (dbVersionAdded is null or dbVersionAdded='')");
        j_setConfValue("updateMessage","Database has been updated to version: $ver");
        j_setConfValue("dbVerNum",$ver);
        $ret=true;        
    }
    return $ret;
}
?>
