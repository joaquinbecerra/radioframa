<?php
require_once("lib/dbLogin.php");
require_once("lib/UL_userLogin.php");UL_checkAuth(_conf("defaultDB"));
if(UL_ISADMIN){
    //Really should be super-dev-admin, not just admin.. but this is a harmless out put function to help developers do updates....
    //assumes that any updates will set the dbVersionAdded col to null and inserts will default to null.
    $a=dosql("select * from configs where (dbVersionAdded is null or dbVersionAdded='')");
    if($a){
        extract($a);
        foreach($configIDs as $i=>$configID){
            $sql.="replace configs set configID=$configID, name='".$names[$i]."',value='".$values[$i]."',type=".$types[$i].",displayType='".$displayTypes[$i]."',valuesList='".$valuesLists[$i]."',sortOrder=".$sortOrders[$i].",editable=".$editables[$i].",dbVersionAdded='".$dbVersionAddeds[$i]."'<br>";
        }
        
        echo $sql;
    }else echo "None found to update.";
    
}
?>