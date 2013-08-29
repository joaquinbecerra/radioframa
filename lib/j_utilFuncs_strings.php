<?php

//string utils
function j_getStringStart($str,$maxlen=15){
    if(strlen($str)<=$maxlen)return $str;
    else return substr($str,0,$maxlen-3)."...";
}
function j_appendToList($str1,$str2,$delim){
    /*add item to a text variable.  
    Returns 	$str1+$delim+$str2 if 1 and 2 are non null;
                            $1 if $2 is null
                            $2 if $1 is null
                            "" if both are null
    */
    $text="";
    if (($str1!="") & ($str2!="")){
            $text= $str1.$delim.$str2;
    }else{
            $text= $str1.$str2;
    }
    return $text;
}
?>