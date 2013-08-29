<?php
//load the db functions, start conn and do Security.
//... load up some utilities too.


//Set the app wide error level.  This is mostly because I'm not as strict about uninited vars and other people get notice warnings
error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once("j_dbHead.php");
include_once("j_mail.php");
include_once("j_utilFuncs.php");
j_loadConfFile("lib/conf.php");//This has to be specified by path from root.
$host=(_conf("mysqlHost"))?_conf("mysqlHost"):"localhost";
if(getMysqlConnection(_conf("username"),_conf("password"),_conf("defaultDB"),false,"",false,$host)===false){echo "<html><body><br><br><br><br><br><h3>Unable to connect to DB.  Make sure database catalog and user have been created and that lib/conf.php is correct.</h3></body></html>";exit;}

?>
