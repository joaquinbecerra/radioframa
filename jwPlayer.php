<?php
session_start();
//$obj=$_SESSION['flashPlayerObj'];
$s=$_SESSION['jwp_FlashData'];
$title=$_SESSION['jwp_siteTitle'];
session_write_close();
?><html>
<head><title><?php echo $title?></title>
 
<!--<script type="text/javascript" src="lib/JWPlayer/jquery.js"></script>
<script type="text/javascript" src="lib/JWPlayer/jquery.playlist.js"></script>
<link rel="stylesheet" type="text/css" href="lib/JWPlayer/playlist.css" />
-->

 <?php echo $s;?>  
    </head>
<body style="margin: 0 0;" onload='fitWindow();window.focus();'>
  
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

