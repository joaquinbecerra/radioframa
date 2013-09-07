<?php

session_start();

require_once("lib/dbLogin.php");

if (!isset($_SESSION['pl'])){
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
        <script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>
        <script type="text/javascript">
            
            function loadPlaylist(){
                
                $.get('index.php?doWhat=rf_getPlaylistDetail&id=31&maxDivHeight=352',function (data){
                    
                    $('#playlist').html(data);
                    
                })
                
            }
            
             function loadEscuchando(){
                
                $.getJSON('index.php?doWhat=rf_nowPlaying',function (data){
                    //console.log(data);
                    if(!data){
                        
                        $('#escuchando').html(' Todavia no comenzo la transmisión, hacé tus sugerencias!');
                        
                    }
                    else{
                        
                        var html=' Estas escuchando: <br>';
                        if (data.albumID){
                          html+="<img src='images.php?doWhat=getImage&type=albumArt&id=" + data.albumID + "' style='float:left;'>";
                        }
                        html+= '<b>'+ data.songName +'</b><br>';
                        html+= ' Artista: '+ data.artistName +'';
                        html+= ' Album: '+ data.albumName +'';
                        html+= ' Sugerido por: '+ data.userName +"<div style='clear:both;'></div>";
                        $('#escuchando').html(html).attr('data-songid',data.songID);
                    }
                    
                })
                
            }
            
            function search(){
                
                $.getJSON('index.php?doWhat=rf_doSearch&search_val='+$('#searchtext').val(),function (data){
                    
                    var html='';
                    if (!data.length)
                        html=' No se encontraron resultados ';
                    else
                        {
                            
                            for(var i=0;i<data.length;i++){
                                var link= "<a class='addToPlaylist' href='#' data-songId='"+data[i].songId+"' title='Agregar a la Playlist' onclick='addToPlaylist("+data[i].songId+");return false;'> + </a>"; 
                            // dar formato a esto                                 
                                html+=link+" <b>"+data[i].songName+"</b> ("+data[i].artistName+" / "+data[i].albumName+") <br>";
                            }
                            
                        }
                    $('#results').html(html);
                    
                })
                
            }
            
            function addToPlaylist(id){
                
                //id=$link.data('songId');
               
                $.get("index.php?doWhat=rf_addToPlaylist&id="+id,function(data){
                    loadPlaylist();
                })
            }
            
            
            
            $(document).ready(function(){
                
                loadPlaylist();
                loadEscuchando();
                var escuchando=setInterval(loadEscuchando,5000);
                //loadEscuchando();
                
                $('#search').submit(function(){                   
                   search();
                   
                   return false;
                })
                
                $('#playlist').hide();
                $('#playlistshow').click(function(){
                   $('#playlist').toggle(300); 
                   return false;
                    
                })
                /*$('.addToPlaylist').on('click',function(){
                    alert('ya');
                    addToPlaylist(this);
                    return false;
                });*/
                
            })
        
        </script>
    </head>
    <body>
        <h1>Radio Frama</h1>
    
        <div id="escuchando" style="border: 1px solid blueviolet; margin-bottom: 20px; padding: 5px;border-radius: 15px;"></div>
        <div  style="border: 1px solid blueviolet; margin-bottom: 20px; padding: 5px;border-radius: 15px; "><a href="#" id="playlistshow">Playlist</a>
        <div id="playlist"></div>
        </div>
        
        <div  style="border: 1px solid blueviolet; margin-bottom: 20px; padding: 5px;border-radius: 15px;">
        <form id="search" >
            Buscar: <input type="text" id="searchtext"/>
        </form><br>
            <div id="results" style="border:1px solid blueviolet;width:80%; max-height: 200px; overflow: auto; margin: auto auto;border-radius: 15px;padding:5px;"></div>
        </div>
        
    </body>
    
    
</html>