<?php
session_start();

require_once("lib/dbLogin.php");



require_once("lib/UL_userLogin.php");
UL_checkAuth(_conf("defaultDB"));

//if (!UL_ISADMIN) {
//    die('Fuera de aqui raton!');
//}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset=utf-8 />
        <title>Radio Player</title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <link href="css/jPlayer.css" rel="stylesheet" type="text/css" />
        <link href="player/skin/pink.flag/jplayer.pink.flag.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>
        <script type="text/javascript" src="js/jquery.jplayer.min.js"></script>
        <script type="text/javascript">
            //<![CDATA[

            var myPlayer;
            var songId=0;

            function load() {

                $.getJSON('index.php?doWhat=rf_nowPlayingF', function(data) {
                    //console.log(data);
                    if (!data)
                        return;
                    if (data.songId==songId){
                       // $("#jquery_jplayer_1").jPlayer("pause", parseInt(data.time,10));
                       // console.log(typeof parseInt(data.time,10));
                        return;
                    }
                        
                    $("#jquery_jplayer_1").jPlayer("setMedia", {
                        mp3: data.filename
                    });

                    $("#jquery_jplayer_1").jPlayer("play", parseInt(data.time,10)+3);
                    
                    songId=data.songId;
                })

            }

            $(document).ready(function() {

                $("#jquery_jplayer_1").jPlayer({
                    swfPath: "js/",
                    supplied: "mp3",
                    smoothPlayBar: true,
                    keyEnabled: true,
                    audioFullScreen: true,
                    ready: function(){
                        load();
                      
                    }
                });
                //load();
                var updateplay = setInterval(load, 3000);
                
                

            });
            //]]>
        </script>
        <style>
            .jp-controls{
                height: 0;
                width: 0;
            }
        </style>

    </head>
    <body >

        <div id="jquery_jplayer_1" class="jp-jplayer_">?</div>

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
    </body>


</html>
