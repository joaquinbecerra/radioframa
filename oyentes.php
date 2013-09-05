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
                
                $('#search').submit(function(){                   
                   search();
                   
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
        <div>Radio Frama</div>
    
    
        <div id="playlist"></div>
    
        <form id="search" >
            Buscar: <input type="text" id="searchtext"/>
        </form>
        <div id="results" style="border:1px solid black;width:80%; max-height: 200px; overflow: auto;"></div>
    </body>
    
    
</html>