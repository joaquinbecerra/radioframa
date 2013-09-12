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
<script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>-
<script type="text/javascript" src="js/jquery.jplayer.min.js"></script>
<script type="text/javascript" src="js/jplayer.playlist.js"></script>
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
    var pl=[];
    var myPlaylist;
$(document).ready(function(){

	myPlaylist = new jPlayerPlaylist({
		jPlayer: "#jquery_jplayer_N",
		cssSelectorAncestor: "#jp_container_N"
	}, [
		
	], {
		playlistOptions: {
			enableRemoveControls: true,
                        onPlayNowChange: function(){ console.log('cambio de tema')},
                        onUp: function (index){ console.log('up ' + index)},
                        onDown: function (index){ console.log('down ' + index)},
                        onRemove: function (index){ console.log('remove ' + index)}
		},
		swfPath: "js/",
		supplied: "mp3",
		smoothPlayBar: true,
		keyEnabled: true,
		audioFullScreen: true,
                
	});
        
        

        
        $.getJSON('index.php?doWhat=rf_getPlaylistAdmin',function(data){
            

            console.log(data);
            for(var i=0;i< data.length;i++){
                //console.log(data['filename']);
                pl.push({title:data[i].title,
                artist:data[i].album,
                mp3:data[i].filename});
                
                
            }
            console.log(pl);
            myPlaylist.setPlaylist(pl);
            
           
        })
        

});
//]]>
</script>

</head>
<body class="demo" onload="">
<div id="container">


	
	<div id="content_main">


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
					<ul>
						<!-- The method Playlist.displayPlaylist() uses this unordered list -->
						<li></li>
					</ul>
				</div>
				<div class="jp-no-solution">
					<span>Update Required</span>
					To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
				</div>
			</div>
		</div>
			

	
</body>


</html>
