<?php
/*Generic timer.
-You can do multiple timers at once.
-Call startTimer("name") to start.
-Call endTimer("name") to end and print time.
*/
$TIMER_timers=array();
function startTimer($name){
        global $TIMER_timers;
        $TIMER_timers[$name]=microtime_float();
}
function endTimer($name,$removeOnEnd=true,$printMsg=true){
/*passing removeOnEnd false, leaves the start time in the timer, allowing you to
 recall it again, updating total time from start.
returns the text of time and prints by default
*/
        global $TIMER_timers;
        $time=0;
        $txt=0;
        if(isset($TIMER_timers[$name])){
            $time=microtime_float()-$TIMER_timers[$name];
            if($removeOnEnd) unset($TIMER_timers[$name]);
            if($time>3600){//more than an hour
                $hr=($time-(fmod($time,3600)))/3600;
                $time=fmod($time,3600);
                $txt.="$hr hour(s) ";
            }
            if($time>60){//more than a minute
                $min=($time-(fmod($time,60)))/60;
                $time=fmod($time,60);
                $txt.="$min min(s) ";
            }
            if($time>0)$txt.="$time second(s)";
        }else echo "<br>Error; no timer started for '$name'<br>";
        if($printMsg && $txt !="") echo "<br><div class='tiny'>'$name' timer took <strong>$txt</strong>.</div><br>";
        return $txt;

}
function microtime_float(){//timer function
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}
?>
