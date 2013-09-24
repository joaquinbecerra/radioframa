<?php
session_start();

require_once("lib/dbLogin.php");

if (!isset($_SESSION['pl'])) {
    require_once("lib/playlist_functions.php");
    rf_loadPlaylistDef();
}


require_once("lib/UL_userLogin.php");
UL_checkAuth(_conf("defaultDB"));
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Radio Frama</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link href="player/skin/pink.flag/jplayer.pink.flag.css" rel="stylesheet" type="text/css" />
        
        <link type="text/css" rel="stylesheet" href="css/estilos.css" media="all" />



        <script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>
        <script type="text/javascript" src="js/jquery.jplayer.min.js"></script>
        <script type="text/javascript">

            var chattime = 0;
            var updateplay = 0;
            var songId = 0;

            function loadPlaylist() {

                $.getJSON('index.php?doWhat=rf_getPlaylistDetail', function(data) {
                    var html = '';

                    for (var i = 0; i < data.length; i++) {
                        html += "<div>";
                        html += "<b>" + data[i].songName + "</b>";
                        html += " ( de " + data[i].artistName + " ";
                        if (data[i].albumName)
                            html += " / " + data[i].albumName + " ) ";
                        if (data[i].user)
                            html += " Sugerido por: " + data[i].user + "</div> ";

                    }

                    $('#playlist').html(html);

                })

            }

            function loadSong() {

                $.getJSON('index.php?doWhat=rf_nowPlayingF', function(data) {
                    //console.log(data);
                    if (!data)
                        return;
                    if (data.songId == songId) {
                        // $("#jquery_jplayer_1").jPlayer("pause", parseInt(data.time,10));
                        // console.log(typeof parseInt(data.time,10));
                        return;
                    }

                    $("#jquery_jplayer_1").jPlayer("setMedia", {
                        mp3: data.filename
                    });
                    console.log(data.time);

                    $("#jquery_jplayer_1").jPlayer("play", parseInt(data.time, 10) + 3);

                    songId = data.songId;
                })
            }

            function loadEscuchando() {

                $.getJSON('index.php?doWhat=rf_nowPlaying', function(data) {
                    //console.log(data);
                    if (!data) {

                        $('#escuchando').html(' Todavia no comenzo la transmisión, hacé tus sugerencias!');

                    }
                    else {

                        var html = ' ';
                        if (data.albumID) {
//                            html += "<img src='images.php?doWhat=getImage&type=albumArt&id=" + data.albumID + "' style='float:left;'>";
                        }
                        html += '<b>' + data.songName + ' - </b>';
                        html += ' Artista: ' + data.artistName + '';
                        html += ' Album: ' + data.albumName + '';
                        html += ' Sugerido por: ' + data.userName + "<div style='clear:both;'></div>";
                        $('#escuchando').html(html).attr('data-songid', data.songID);
                    }

                })

            }

            function updateChat() {

                $.getJSON('index.php?doWhat=rf_getMessages&id=' + chattime, function(data) {


                    var html = '';

                    for (var i = 0; i < data.length; i++) {

                        html += '<div><b>' + data[i].time;
                        html += ' ' + data[i].userName + '</b>';
                        html += ': ' + data[i].msg;
                        html += '</div>';
                        chattime = data[i].id;
                    }

                    //console.log(html);
                    //chattime=data[i].id;
                    $('#chat').append(html).scrollTop($('#chat')[0].scrollHeight);
                });
            }

            function Chat() {

                var msg = $('#chattext').val();
                $('#chattext').val('');
                $.getJSON('index.php?doWhat=rf_sendMessages&msg=' + msg, function(data) {
                    chattime = data.id;


                });
            }

            function search() {

                $.getJSON('index.php?doWhat=rf_doSearch&search_val=' + $('#searchtext').val(), function(data) {

                    var html = '';
                    if (!data.length)
                        html = ' No se encontraron resultados ';
                    else
                    {

                        for (var i = 0; i < data.length; i++) {
                            var link = "<a class='addToPlaylist' href='#' data-songId='" + data[i].songId + "' title='Agregar a la Playlist' onclick='addToPlaylist(" + data[i].songId + ");return false;'> + </a>";
                            // dar formato a esto                                 
                            html += link + " <b>" + data[i].songName + "</b> (" + data[i].artistName + " / " + data[i].albumName + ") <br>";
                        }

                    }
                    $('#results').html(html);

                })

            }

            function addToPlaylist(id) {

                //id=$link.data('songId');

                $.get("index.php?doWhat=rf_addToPlaylist&id=" + id, function(data) {
                    loadPlaylist();
                })
            }

            function logOut() {
                $.get('index.php?UL_logoff=1', function() {

                    window.location.assign('oyentes.php');

                });
            }

            $(document).ready(function() {

                loadPlaylist();
                loadEscuchando();
                var escuchando = setInterval(loadEscuchando, 5000);
                //loadEscuchando();

                $('#search').submit(function() {
                    search();

                    return false;
                })

//                $('#playlist').hide();
//                $('#playlistshow').click(function() {
//                    $('#playlist').toggle(300);
//                    return false;
//
//                });

                $('#logOut').click(function() {
                    alert('chaucito');
                    logOut();
                    return false;
                })



                $('#chatinput').submit(function() {

                    Chat();
                    updateChat();
                    return false;

                });

                updateChat();
                var chatup = setInterval(updateChat, 5000);

                /*$('.addToPlaylist').on('click',function(){
                 alert('ya');
                 addToPlaylist(this);
                 return false;
                 });*/

                $('#musicOn').click(function() {
                    $('.musicOn').show();
                    loadSong();
                    updateplay = setInterval(loadSong, 2000);
                    $('.musicOff').hide()
                })

                $('#musicOff').click(function() {
                    $("#jquery_jplayer_1").jPlayer("pause");
                    songId = 0;
                    clearInterval(updateplay);
                    $('.musicOff').show();
                    $('.musicOn').hide();
                })

                $("#jquery_jplayer_1").jPlayer({
                    swfPath: "js/",
                    supplied: "mp3",
                    smoothPlayBar: true,
                    keyEnabled: true,
                    audioFullScreen: true,
                });

            })

        </script>
    </head>
    <body>
        <div class="container">
            <div style="width:90%;padding: 10px; text-align: right; font-size: 10px;"><a id="logOut" href="#"> Cerrar Sesión</a></div>

            <div class="buscador">
                <form id="search" >
                    <input type="text" id="searchtext" class="texto_busca"/>
                    <input type="submit" value="buscar" class="button"/>
                </form>
                <div class="resultados" >
                    <div id="results" class="contenedor_resultados">

                    </div>
                </div>


            </div>
            <div class="play" style='position:relative;'>
                <div class='musicOff' style='position:absolute;width: 100% ;height: 100% ; background: black;z-index: 2'>
                    <a href='#' id='musicOn'>Encender</a>
                </div>
                <div class='musicOn'>
                    <a href='#' id='musicOff'>Apagar</a>

                    <div id="jquery_jplayer_1" class="jp-jplayer_"></div>

                    <div id="jp_container_1" class="jp-audio">
                        <div class="jp-type-single">
                            <div class="jp-gui_ jp-interface " style='height: 0px;'>
                                <ul class="jp-controls"  style='height: 0px;padding:0'>
                                    <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
                                    <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
                                    <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
                                </ul>
                                <div class="jp-volume-bar">
                                    <div class="jp-volume-bar-value"></div>
                                </div>                    
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <div class="nowplaying" id="escuchando"></div>

            <div class="playlist">
                <div id="playlist" class="contenedor_playlist">

                </div>
            </div>



            <div class="chat_mjes" >
                <div  id="chat" class="contenedor_chat" >

                </div>
            </div>
            <form id="chatinput" >
                <input type="text" id="chattext" class="texto_chat"/>
            </form>
        </div>
    </body>


</html>