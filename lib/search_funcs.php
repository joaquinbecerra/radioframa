<?php
function string_search($type,$needle,$includeSoundex=false){
//$type can be artist,album,song.  $needle can be 1 or more words.  Note soundex can give some pretty wacky answers. 

/* This function will use various methods to find rows in #haystack with string that matches $needle.  
It will attempt to order them by assigning a precedence value to each resulting match, the higher the better (no fixed scale).

Caller can then select/join to the haystack table and order by precedence desc to get ordered results.

Returns true if no errors occured, although there may not be any rows/matches in the table.
*/

	if(!$needle)return false;
	$needles=explode(" ",$needle);
	$needle=scrubTextForDB($needle,true);//escape any msyql special chars

	//build the temp table to hold our results and 
	dosql("create temporary table if not exists haystack (id int not null,precedence int null,PRIMARY KEY (id))");
	dosql("truncate table haystack");

	$col="name";
	$table=$type."s";
	$idCol=$type."ID";
	if($type=="song")$col="songName";

	$ins="insert into haystack (id,precedence) select $idCol,1 from $table where ";
	$onDup="on duplicate key update precedence=precedence+1 ";

	//Now do a series of selects to find best matches.  These are separated (instead of a giant or) to give more weight to those with multiple hits.
	dosql($ins."upper($col)=upper('$needle') ".$onDup);//direct case-insensitive match
	dosql($ins."upper($col) like upper('%".$needle."%') ".$onDup);//contains
	dosql($ins."upper($col) like upper('".$needle."%') ".$onDup);//starts with
	dosql($ins."upper($col) like upper('%".$needle."') ".$onDup);//ends with

	//Break the needle up into words and match on any of those.  We'll do it so all passed words have to match in some way.
	foreach($needles as $n){
		$needle=scrubTextForDB($n);
		if($needle){
			$whr=j_appendToList($whr,"(upper($col) like upper('% $needle %') or upper($col) like upper('$needle %') or upper($col) like upper('% $needle'))"," and ");//word match
			$whr2=j_appendToList($whr2,"(upper($col) like upper('%".$needle."%'))"," and ");//contains
		}
	}
	dosql($ins." ".$whr." ".$onDup);
	dosql($ins." ".$whr2." ".$onDup);

	//dosql($ins."upper($col) like upper('% $needle %') or upper($col) like upper('$needle %') or upper($col) like upper('% $needle') ".$onDup);//word match
        //dosql($ins."upper($col) like upper('%".$needle."%') ".$onDup);//contains
	return true;
}
?>
