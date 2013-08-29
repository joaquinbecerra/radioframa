<?php
/*This is an 'unauthenticated' login to fetch images.  I decided to move it here to trim amount of work being done just to load images.*/
//require_once("lib/j_timer.php");
//startTimer("pageLoad");

require_once("lib/dbLogin.php");
require_once("lib/loadConfigs.php");//Need this for the default albumart location !?!

$id=scrubTextIn($_REQUEST['id'],1);//Strict 'word' only filter
$type=scrubTextIn($_REQUEST['type'],1);//Strict 'word' only filter

if($type){//Id is allowed to come in blank.
    require_once("lib/image_funcs.php");
    getImage($type,$id);
}
//endTimer("pageLoad");
?>