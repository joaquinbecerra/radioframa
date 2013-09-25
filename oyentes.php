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
        <link type="text/css" rel="stylesheet" href="css/estilos.css" media="all" />
        <link type="text/css" rel="stylesheet" href="css/jquery.scrollpane.css" media="all" />
        <script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>
        <script type="text/javascript" src="js/jquery.mousewheel.js"></script> 
        <script type="text/javascript" src="js/jquery.jscrollpane.js"></script> 
        <script type="text/javascript">

            var chattime=0;
            function loadPlaylist() {

                $.getJSON('index.php?doWhat=rf_getPlaylistDetail', function(data) {
                    var html = '';
                    /*RESET SCROLLPANE*/
                        var container = $('.contenedor_playlist').jScrollPane();
                        var api = container.data('jsp');
                        api.destroy();
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
                    $('.contenedor_playlist').jScrollPane();
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
            
            function updateChat(){
            
                $.getJSON('index.php?doWhat=rf_getMessages&id='+chattime, function(data) {
                    
                    
                    var html='';
                   
                    for (var i=0;i<data.length;i++){
                        
                        html+='<div><b>'+data[i].time;
                        html+=' '+data[i].userName+'</b>';
                        html+=': '+data[i].msg;
                        html+='</div>';
                        chattime=data[i].id;
                    }
                    
                    //console.log(html);
                    //chattime=data[i].id;
                    $('#chat').append(html).scrollTop($('#chat')[0].scrollHeight);
                });
            }
            
            function Chat(){
                
                var msg=$('#chattext').val();
                $('#chattext').val('');
                $.getJSON('index.php?doWhat=rf_sendMessages&msg='+msg, function(data) {                    
                    chattime=data.id;
                    
                   
                });
            }

            function search() {

                $.getJSON('index.php?doWhat=rf_doSearch&search_val=' + $('#searchtext').val(), function(data) {

                    var html = '';
                    if (!data.length)
                        html = ' No se encontraron resultados ';
                    else
                    {
                        /*RESET SCROLLPANE*/
                        var container = $('.contenedor_resultados').jScrollPane();
                        var api = container.data('jsp');
                        api.destroy();
                        
                        for (var i = 0; i < data.length; i++) {
                            html +='<div class="item-search">';
                            var link = "<a class='addToPlaylist' href='#' data-songId='" + data[i].songId + "' title='Agregar a la Playlist' onclick='addToPlaylist(" + data[i].songId + ");return false;'><img src=\"images\/add.png\" align=\"absmiddle\"/></a>";
                            // dar formato a esto                                 
                            html += link + " <b>" + data[i].songName + "</b> (" + data[i].artistName + " / " + data[i].albumName + ")";
                            html +='</div>';
                        }

                    }
                    $('#results').html(html);
                    $(".item-search:odd").addClass("odd");

                    $('.contenedor_resultados').jScrollPane();
                    
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

                

                $('#chatinput').submit( function(){
                    
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
                 /*SCROLL*/
                 $('.contenedor_resultados').jScrollPane();
                 $('.contenedor_playlist').jScrollPane();
            })

        </script>
    </head>
    <body>
        <div class="container">
        <div class="logout">
            <a id="logOut" href="#"> Cerrar Sesión</a>
            <h1 class="tit_logout"><img src="images/tit_logout.png" alt="Logout"/></h1>        
        </div>

        <div class="buscador">
            <h1 class="tit_buscador"><img src="images/tit_buscar.png" alt="Buscador"/></h1>
            <form id="search" >
                <input type="text" id="searchtext" class="texto_busca"/>
                <input type="submit" value="buscar" class="button"/>
            </form>
            <div class="resultados" >
                <div id="results" class="contenedor_resultados">
                    
                </div>
            </div>
        </div>
        
        <div class="nowplaying" id="escuchando"></div>
        
        <h1 class="tit_playlist"><img src="images/tit_playlist.png" alt="Playlist"/></h1>
        <div class="playlist">
            <div id="playlist" class="contenedor_playlist">
                
            </div>
        </div>


        <h1 class="tit_mensajes"><img src="images/tit_mensajes.png" alt="Mensajes"/></h1>
            <div class="chat_mjes" >
                <div  id="chat" class="contenedor_chat" >
                    
                </div>
            <form id="chatinput" >
                <input type="text" id="chattext" class="texto_chat"/>
            </form>
            </div>

        <EMBED allowScriptAccess="always" allowNetworking="all" src="http://seven.flash-gear.com/lts/lts.php?c=f&o=1&id=3949813&k=6105848" quality=high wmode=transparent scale=noscale salign=LT bgcolor="FFFFFF" WIDTH="450" HEIGHT="400" NAME="lts124393" ALIGN="" TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer" />
        
       </div>
    </body>


</html>