<?php
//some date stuff.  These all take and return a date array (from getdate()).
function j_getDateArray($date){//get a date array from string date.
	if($date=="now") return getdate();
	return getdate(strtotime($date));
}
function j_getDatePartsArray(){//returns the list of acceptable dateparts in an assoc array
    $dateparts=array('minutes'=>'minute(s)','hours'=>'hour(s)','day'=>'day(s)','week'=>'week(s)','mon'=>'month(s)','year'=>'year(s)');
    return $dateparts;
}
function j_firstOfMonth($aDate){//returns the date array of the first day of the month.
    $start=mktime(0,0,0,$aDate['mon'],1,$aDate['year']);
    return getdate($start);
}
function j_lastOfMonth($aDate){//returns the date array of the last day of the month.
    $end=mktime(0,0,0,$aDate['mon']+1,0,$aDate['year']);//the zeroth day of next month is the last day of this month.
    return getdate($end);
}
function j_startOfDay($aDate){
    $date=mktime(0,0,0,$aDate['mon'],$aDate['mday'],$aDate['year']);
    return getdate($date);
}
function j_endOfDay($aDate){
    $date=mktime(23,59,59,$aDate['mon'],$aDate['mday'],$aDate['year']);
    return getdate($date);
}
function j_dateAdd($aDate,$datePart,$num){/*datePart (string) can be:
    hours,minutes,seconds,mon,day,year,week. 
    $num can be +/- integer
    
    NOTE; if adding months, you should always use first of month due to
    variable size of months.. this won't crash, but adding 1 month to 1/31 will
    return something like 3/3 (depending on leap year).  Same applies to years if
    day is 2/29.  All others should work fine though.
    */
    if($datePart=="day")$datePart="mday";
    if($datePart=="week"){
        $datePart="mday";
        $num=$num*7;
    }
    $aDate[$datePart]=$aDate[$datePart]+$num;
    $date=j_getTimeStamp($aDate);
    return getdate($date);
}
function j_getTimeStamp($aDate){//returns unix time stamp for passed date array.
    return mktime($aDate['hours'],$aDate['minutes'],$aDate['seconds'],$aDate['mon'],$aDate['mday'],$aDate['year']);
}
function j_getNiceDate($aDate,$showTime=true){//returns a date suitable for display (5/12/2005 2:15 pm)
	//$aDate is a date array like what is returned by getdate();  This assumes the timestamp in datearray is accurate and this uses time info.
	$format="n/j/Y";
        if($showTime &&($aDate['seconds']!=0 || $aDate['minutes']!=0 || $aDate['hours']!=0)){
            $format.=" g:i a";
        }
        $dateStr=date($format,$aDate[0]);
	return $dateStr;	 
}
function j_get_YYYYMMDD_date($aDate){
        $format="Y/m/d";//Note; this format is relied on elsewhere.. if need to change, leave this as default.
        $dateStr=date($format,$aDate[0]);
        return $dateStr;
}
function j_dateDiff($aDate1,$aDate2,$datePart){//NO WORKIE YET (not handling 32 days...)
    /*returns number of $dateParts difference ($aDate2-$aDate1) between passed dates..
    negative means that $aDate1 is after $aDate2.
    dateparts can be hours,minutes,seconds,mon,day,year,week. */
    $part=$datePart;
    if(($datePart=="day")||($datePart=="week"))$part="mday";
    $num=$aDate2[$part]-$aDate1[$part];
    if($datePart=="week")$num=$num/7;
    return $num;
}
?>
