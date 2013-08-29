<?php
exit;
-need to fix ajax history
-see if can have backgrounded processes (progressBar) store state and pick back up.
-need to find the on-client disconnect event and hook progressBar in so it cancells gracefully.
-bug in playlist/header content that get's sent to some clients.  Winamp seems to change to file/song name once played instead of whatever is in the playlist
x-fix playlists up
x-update playlists and stats in catalog maint hooks.  make sure this scheme actually makes sense. DONE.  Needs more testing though.
x-do song search output.
x-do catalog interface and finish up admin page
x-figure out upgrade scheme
-clean up styles and all output code that uses them
-copy utils and js back to themunds.com
x-improve search (add multiword search, maybe top 5 from each domain option)
x-Make reload playlist js func reload passed id (private list doesn't seem to work.)
-add config to limit to single login for a user.
x-admin stats
-user stats
-send email to getid3 guys and sign up for notifications for new versions (read readme.txt)
x-remove "using btree" from dbUpdate ?  didn't work on whatever sourceforge is using.
x-add optimize/repair functions to admin
x-add way to force catalog update (rm configID 20 from preferences)
-remove the demo.tincanjukebox.com dns entry from godaddy if gonna stick with sourceforge for demo
-delete the local demo db
x-figure out a better way to xfr everything over. 
x-change styles.css config syntax to limit to *styles.css files
x-check router logs for error.
x-admin site preference defaults tab?
x-need to test prefrences and overrides thouroughly. 
-ozomatli, street signs,(can't stop) won't play, goes crazy.

-amazon art lookup
-refresh random on timer
-incubus album + songs too long for window and strechy guys it out.  Needs container div to scroll in.
-play all on random lists
-fix stats collection so played albums,artists and genres get updated for each song played?
x-delete songs with no file 
x-add user priviledges page
x-add ability to download.
x-need to make js sideTab_selectedTab into an array so you can have multple tab windows on same page (user privs).
x-random from recently added.
x-add automated way to add configs
x-browse by recently added
-add gpl text to all pages...
-change scroll logic so it's always the last item on the list that's the top on the next page.
-some sort of bug; browse by ablums->new then click albums and the last selected (from new) is shown but with no name listed.  It's because craig meyerhoffers's album is 49 floors and the filter is set to # when passed with that album ID.  Needs to be more smarter.
-add soundex to search

x-delete songs with no file
-scroll window on playlist
-on mouse over on playlist items should show artist/album info
x-display playlists when they aren't yours
-change font on menu (and darken)
-add ability to play all search results.
-browsing new artists (probably all) isn't quite right.  If artist already exists, it doesn't show on the new list.  Probably should join with songs and use songid to determine newness.  Maybe not actually.. i think it's right for now.
x-add flash player.
x-when changing play type (to/from flash) send conf update to js so play link changes.
-set order bys in the configs/prefs
x-hide 'unattached songs' when no albums presesnt (artist browse)
x-add more flash players
x-disable album art
x-cache album art.
x-add auto catalog update
-when clicking on 3 doors down artist from album detail page or search for 3 doors down and then come back to artist (so last selected is shown) it doesn't show the artist or the other # artists in the tab browser
x-make test to see if gd is a bottle neck and come up with system to cache images (maybe in source folder).

x-add in debug levels, particularly on the cat maint

x-add in a checker to see if have R access to files and log if don't
x-add in a checker to see if have GD installed.
-when changing play method, it should wipe out the flash div
x-have an automated way to clear out the error log.
x--change folder image logic to find any jpg, but give precedence to one named the default name.
--When looking at playlists, if the same song has been added twice (or more times), it only shows in the "Playlist Contents" once, even though it is counted in the number of songs.  If I edit the playlist, it shows twice.  This could cause confusion, so it may be best to have the song show in "Playlist Contents" as many times as it has been entered.
--normalize errors.

--update user admin form to work with ie (add button doesn't work)
--fix css on user admin.
--remove charset options on create table commands
--fix all <? to be <?php
                         











//For developers...    
 to release:
-add a dummy function if needed in the dbUpdate script to bring version up to today's date.
-make sure lib/releaseNotes.txt is up to date.
-log into calvin and do an updateDemundTar.  Test upgrade from /tincanjukeboxtest/tincanjukebox/
    wipe all tables and check full upgrade too.
-under /tincanjukeboxtest cp tinCanJukebox.tar.gz tinCanJukebox-[version].tar.gz
-login to sourceforge project page

//old
//-admin->file release->add new-> release number from above (new layout, goto downloads, manage releases).
//new
-admin->file manager, open tcj folder, create new folder (same name as release), click the config thingy, upload here.. then click upload and find file... new html5 interface, uploaded files, set as 'default' for the tarball.

-upload release notes txt file (lib/releasenotes.txt) and mark as 'release notes' (click file and fill in details).
-click release file file and fill in details. (set as default for all platform)


-announce:
    -develop->news->submit
    Should probably include 'TinCanJukebox' in the subject so it looks right on slashdot.
    copy and announce on freshmeat.net too.
-update /updatetincanjukebox/update.php with new version.

?>
