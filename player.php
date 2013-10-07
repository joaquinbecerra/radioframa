<?php
session_start();

require_once("lib/dbLogin.php");



require_once("lib/UL_userLogin.php");
UL_checkAuth(_conf("defaultDB"));

if (!UL_ISADMIN) {
    die('Fuera de aqui raton!');
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset=utf-8 />

        <!-- Website Design By: www.happyworm.com -->
        <title>Radio Player</title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <link href="css/jPlayer.css" rel="stylesheet" type="text/css" />

        <link href="js/prettify/prettify-jPlayer.css" rel="stylesheet" type="text/css" />
        <link href="player/skin/pink.flag/jplayer.pink.flag.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>
        <script type="text/javascript" src="js/jquery.jplayer.min.js"></script>
        <script type="text/javascript" src="js/jplayer.playlist.js"></script>
        <script type="text/javascript" src="js/jquery-ui-1.10.3.custom.min.js"></script>
        <!--<script type="text/javascript" src="js/jquery.jplayer.inspector.js"></script>
        <script type="text/javascript" src="js/themeswitcher.js"></script>-->
        <style>


            div.jp-type-playlist div.jp-playlist a.jp-playlist-item-up,
            div.jp-type-playlist div.jp-playlist a.jp-playlist-item-down
            {
                color: #8C7A99;
                display: inline;
                float: right;
                font-weight: bold;
                margin-left: 10px;
                text-align: right;
            }
            div.jp-type-playlist div.jp-playlist a.jp-playlist-item-up:hover,
            div.jp-type-playlist div.jp-playlist a.jp-playlist-item-down:hover{
                color: #E892E9;
            }

        </style>
        <script type="text/javascript">
            //<![CDATA[

            var myPlaylist;
            var espera = false;

            function PlayNowChange(tipo) {

                if (espera)
                    return;
                espera = true;

                var index = myPlaylist.current;
                var id = myPlaylist.playlist[index].songId;

                $.get('index.php?doWhat=rf_updatenowPlaying&id=' + id, function() {
                    espera = false;
                });


                //si cambio porque termino el tema anterior
                if (tipo == 'ended') {
                    if (index - 1 < 0)
                        index = 1;
                    myPlaylist.remove(index - 1);
                    RemoveItem(index - 1);
                }

            }

            function RemoveItem(index) {

                // console.log(index);
                if (espera)
                    return;
                espera = true;
                var id = myPlaylist.playlist[index].itemId;
                $.get('index.php?doWhat=rf_deletePlaylistItem&id=' + id, function() {
                    espera = false
                });


            }

            function Clear() {

                if (espera)
                    return;
                espera = true;
                if (confirm('Estas seguro??')) {


                    $.get('index.php?doWhat=rf_clearPlaylist', function() {
                        espera = false
                        myPlaylist.setPlaylist([]);

                    });

                }





            }

//            function upItem(index) {
//
//                //  console.log(index);
//                if (espera)
//                    return;
//                espera = true;
//                $.get('index.php?doWhat=rf_upPlaylistItem&id=' + index, function() {
//                    espera = false
//                });
//
//
//            }
//
//            function downItem(index) {
//
//                // console.log(index);
//                if (espera)
//                    return;
//                espera = true;
//                $.get('index.php?doWhat=rf_downPlaylistItem&id=' + index, function() {
//                    espera = false
//                });
//
//
//            }

            function logOut() {

                $.get('index.php?UL_logoff=1', function() {

                    window.location.assign('player.php');

                });
            }

            function updatePlaylist() {
                if (espera)
                    return;
                espera = true;
                $.getJSON('index.php?doWhat=rf_getPlaylistAdmin', function(data) {
                    //var pl = myPlaylist.playlist;

                    espera = false;
                    var pl = [];
                    for (var i = 0; i < data.length; i++) {
                        //console.log(data['filename']);
                        pl.push({title: data[i].title,
                            artist: data[i].album,
                            mp3: data[i].filename,
                            songId: data[i].songId,
                            itemId: data[i].itemId,
                            userName:data[i].userName
                        });




                    }
                    //console.log(pl);
                    myPlaylist.playlist = pl;
                    myPlaylist._refresh(true);
                    myPlaylist._highlight(myPlaylist.current);

                    /*for (var i = 0; i < data.length; i++) {
                     var esta = false;
                     for (var j = 0; j < pl.length; j++) {
                     
                     if (pl[j].songId == data[i].songId) {
                     esta = true;
                     }
                     
                     }
                     if (!esta) {
                     
                     
                     //console.log(data['filename']);
                     myPlaylist.add({title: data[i].title,
                     artist: data[i].album,
                     mp3: data[i].filename,
                     songId: data[i].songId,
                     itemId: data[i].itemId,
                     });
                     }
                     
                     
                     }
                     */

                })

            }

            var PlaylistResortUI = function(event, ui) {
                //console.log(ui);
                espera = true;
                var pl = [];
                var s = [];
                $(".jp-playlist ul li").each(function(index, elem) {

                    var itemid = $(elem).data('itemid');
                    for (var i = 0; i < myPlaylist.playlist.length; i++) {

                        if (myPlaylist.playlist[i].itemId == itemid) {
                            pl[index] = myPlaylist.playlist[i];
                            break;
                        }

                    }
                    s.push(itemid);
                })

                $.get('index.php?doWhat=rf_setPlOrder&pl=' + s.join('_'), function() {


                    espera = false;

                });
                myPlaylist.playlist = pl;
                myPlaylist._refresh(true);
                myPlaylist._highlight(myPlaylist.current);


            }

            function Shuffle(on) {
                console.log(on);
                espera = true;
                var s = [];
                
                $(".jp-playlist ul li").each(function(index, elem) {

                    var itemid = $(elem).data('itemid');
                   
                    s.push(itemid);
                })

                $.get('index.php?doWhat=rf_setPlOrder&pl=' + s.join('_'), function() {


                    espera = false;

                });

            }

            $(document).ready(function() {
                               
                myPlaylist = new jPlayerPlaylist({
                    jPlayer: "#jquery_jplayer_N",
                    cssSelectorAncestor: "#jp_container_N"
                }, [
                ], {
                    playlistOptions: {
                        enableRemoveControls: true,
                        onPlayNowChange: PlayNowChange,
                        // onUp: upItem,
                        // onDown: downItem,
                        onShuffle: Shuffle,
                        onRemove: RemoveItem
                    },
                    swfPath: "js/",
                    supplied: "mp3",
                    smoothPlayBar: true,
                    keyEnabled: true,
                    audioFullScreen: true,
                });




                $.getJSON('index.php?doWhat=rf_getPlaylistAdmin', function(data) {

                    var pl = [];
                    for (var i = 0; i < data.length; i++) {
                        //console.log(data['filename']);
                        pl.push({title: data[i].title,
                            artist: data[i].album,
                            mp3: data[i].filename,
                            songId: data[i].songId,
                            itemId: data[i].itemId,
                            userName: data[i].userName,
                        });
                    }
                    //console.log(pl);
                    myPlaylist.setPlaylist(pl);


                });
                $('#logOut').click(function() {
                    alert('chaucito');
                    logOut();
                    return false;
                });

                $('#clear').click(function() {

                    Clear();
                    return false;
                })

                var updateplay = setInterval(updatePlaylist, 5000);

                //sortable:
                $(".jp-playlist ul").sortable({
                    stop: PlaylistResortUI,
                    start: function() {
                        espera = true;
                    }
                }
                );
                $(".jp-playlist ul").disableSelection();

                /** DRAG AND DROP**/
                
//                $("#test-list").sortable({ 
//                    handle : 'div', 
//                    update : function () { 
//                        var order = $('#test-list').sortable('serialize'); 
//                        $("#info").load("update_list.php?"+order);
//                        console.log(order);
//                    } 
//                }); 

            });
            //]]>
        </script>

    </head>
    <body  onload="">
        <div style="font-size:10px; width:480px;text-align: center;" >
            <a id="logOut" href="#" > Cerrar Sesión</a>

        </div>


        <div id="jp_container_N" class="jp-video jp-video-270p">
            <div class="jp-type-playlist">
                <div id="jquery_jplayer_N" class="jp-jplayer"></div>
                <div class="jp-gui">
                    <div class="jp-video-play">
                        <a href="javascript:;" class="jp-video-play-icon" tabindex="1">play</a>
                    </div>
                    <div class="jp-interface">
                        <div class="jp-progress">
                            <div class="jp-seek-bar">
                                <div class="jp-play-bar"></div>
                            </div>
                        </div>
                        <div class="jp-current-time"></div>
                        <div class="jp-duration"></div>
                        <div class="jp-title">
                            <ul>
                                <li></li>
                            </ul>
                        </div>
                        <div class="jp-controls-holder">
                            <ul class="jp-controls">
                                <li><a href="javascript:;" class="jp-previous" tabindex="1">previous</a></li>
                                <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
                                <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
                                <li><a href="javascript:;" class="jp-next" tabindex="1">next</a></li>
                                <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
                                <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
                                <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
                                <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
                            </ul>
                            <div class="jp-volume-bar">
                                <div class="jp-volume-bar-value"></div>
                            </div>
                            <ul class="jp-toggles">
                                <li><a href="javascript:;" class="jp-full-screen" tabindex="1" title="full screen">full screen</a></li>
                                <li><a href="javascript:;" class="jp-restore-screen" tabindex="1" title="restore screen">restore screen</a></li>
                                <li><a href="javascript:;" class="jp-shuffle" tabindex="1" title="shuffle">shuffle</a></li>
                                <li><a href="javascript:;" class="jp-shuffle-off" tabindex="1" title="shuffle off">shuffle off</a></li>
                                <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
                                <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="jp-playlist">
                    <ul id="test-list">
                        <!-- The method Playlist.displayPlaylist() uses this unordered list -->
                        <li></li>
                    </ul>
                </div>
                <div class="jp-no-solution">
                    <span>Update Required</span>
                    To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
                </div>
            </div>
        </div></div>

    <div style="font-size:10px; width:480px;text-align: center;" >

        <a id="clear" href="#" > Borrar Todos</a> &nbsp;&nbsp;&nbsp;

    </div>

        <div id="info"></div>
    </body>


</html>
