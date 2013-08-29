<?php
session_start();
	$playerType=$_SESSION['player'];
	$jwData=@$_SESSION['jwFlashData'];
	$title=$_SESSION['siteTitle'];
	$xspfPlayerURL=$_SESSION['xspf_swfurl'];
	
	//This is loaded in the variables.php file instead of here due to problems embedding another dyn url in the url call to the flash player...
	$playListURL=$_SESSION['xspf_playlisturl'];
	
	$skinURL=$_SESSION['xspf_skinurl'];
	$varURL=$_SESSION['xspf_varurl'];
	$width=$_SESSION['xspf_width'];
	$height=$_SESSION['xspf_height'];
session_write_close();

//alt swfobject.. <script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/swfobject/2.1/swfobject.js?ver=2.1'></script>
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html lang="en">
<head>
<?php
if($playerType=="jwPlayer"){
	echo $jwData;	
}else{//xspf_jukebox ?>
	<!-- not quite right.. not sure if even needed.<script type="text/javascript" src="lib/xspf_jukebox/swfobject.js"></script>-->
<?php }?>

    <title><?php echo $title;?></title>

</head>
<body style="margin: 0 0;" onload='fitWindow();window.focus();'>
<div id='outerDiv'><?php

if($playerType=="xspf_jukebox"){
    //http://j.tincanjukebox.com/index.php?doWhat=playAlbum&id=10668&playListType=XSPF&PHPSESSID=8lgn7qvavd8au8gcm3rrhp8st0
	//$params=$xspfPlayerURL."?skin_url=".$skinURL."&playlist_url=".$playListURL;
	//$params=$xspfPlayerURL."?autoload=true&autoplay=true&skin_url=".$skinURL."&loadurl=".$varURL."&playlist_url=$playListURL";
        $params=$xspfPlayerURL."?autoload=true&autoplay=true&skin_url=".$skinURL."&loadurl=".$varURL."&playlist_url=$playListURL";
        
        //$params=$xspfPlayerURL."?autoload=true&autoplay=true&skin_url=".$skinURL."&playlist_url=http://j.tincanjukebox.com/index.php?doWhat=xspf_jukebox_playlist";
        //$params=$xspfPlayerURL."?autoload=true&autoplay=true&skin_url=".$skinURL."&loadurl=".$varURL
	//$params=$xspfPlayerURL."?skin_url=".$skinURL."&playlist_url=http://j.tincanjukebox.com/pl.xspf";
?>
	<div id="flashcontent" name='j'>
        <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="<?php echo $width;?>" height="<?php echo $height;?>" id="flashObject" align="middle">
            <param name="movie" value="<?php echo $params;?>" />
            <param name="wmode" value="transparent" />
            <embed src="<?php echo $params;?>" wmode="transparent" width="<?php echo $width;?>" height="<?php echo $height;?>" name="flashObject" align="middle" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />

	</object>
	</div>
    
<?php }
/*This was in right below div above, but couldn't link the swfobj for some reason.. don't actually even know what it's for, I'll leave for now incase some browsers need it and I have to figure out how to make work ;)

	<script type="text/javascript"><!--
            var so = new SWFObject("<?php echo $params;?>", "flashObject", "<?php echo $width;?>", "<?php echo $height;?>", "7", "#ffffff", true);
            so.addParam("wmode", "transparent");
            so.write("flashcontent");
            window.document.flashObject.focus();
      	-->
	</script>
*/
?></div>
</body>
</html>
<script type="text/javascript">

function fitWindow(){
     var viewportwidth;
 var viewportheight;
 // the more standards compliant browsers (mozilla/netscape/opera/IE7) use window.innerWidth and window.innerHeight
    if (typeof window.innerWidth != 'undefined'){
      viewportwidth = window.innerWidth,
      viewportheight = window.innerHeight
    }
 
// IE6 in standards compliant mode (i.e. with a valid doctype as the first line in the document)
    else if (typeof document.documentElement != 'undefined' && typeof document.documentElement.clientWidth != 'undefined' && document.documentElement.clientWidth != 0){
       viewportwidth = document.documentElement.clientWidth,
       viewportheight = document.documentElement.clientHeight
    }
 
    // older versions of IE
 
    else{
       viewportwidth = document.getElementsByTagName('body')[0].clientWidth,
       viewportheight = document.getElementsByTagName('body')[0].clientHeight
    }
    var div=document.getElementById("flashObject");
    if(div){
        if(viewportheight!=div.height ||viewportwidth!=div.width){//resize to fit.. some browsers will have different inner viewable windows.
            window.resizeBy(div.height-viewportheight,div.width-viewportwidth);            
        }
    }
    
}
</script>
