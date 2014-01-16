<?php
session_start();

function getLocalIp(){
    $output = shell_exec('/sbin/ifconfig');        
    preg_match("/inet?[[:space:]]?addr:([0-9.]+)/", $output, $matches);
    if(isset($matches[1]))
        $ip = $matches[1];
    else
        $ip = 0;
    return $ip;
}

require_once("lib/dbLogin.php");
require_once("lib/UL_userLogin.php");
require_once("lib/Browser.php");
require_once("lib/j_utilFuncs_conf.php");

UL_checkAuth(_conf("defaultDB"));

if (!UL_ISADMIN) {
    die('Fuera de aqui raton!');
}

$a=dosql("select group_concat(email) as mails from users");
$recipients = $a["mailss"][0];
// echo '<p>'.$_SERVER['REMOTE_ADDR'].'</p>';


// $addrs = getLocalIp();
// echo $addrs;

?>
<?php
   $ip = shell_exec("/sbin/ifconfig");
   $ip = preg_match("/(192.168.1.[0-9]+)/", $ip, $array);
   $ip_local = $array[0];
?>

<?php
	$mensaje="<h1>RADIO FRAMA</h1>
	<p>Estimado oyente de RadioFrama by Joaco:</p>
	<p>La radio está online y lista para que sugieras canciones<br>
	Podés hacerlo ingresando en: <a href='http://$ip_local/radioframa/oyentes.php'>http://$ip_local/radioframa/oyentes.php</a></p>
	<p>Gracias.</p>"
?>

<?php
// $to      = $recipients;
$to      = $recipients;
$subject = 'RADIOFRAMA - ONLINE';
$message = $mensaje;
// To send HTML mail, the Content-type header must be set
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$headers .= 'From: mframarini@lyracons.com' . "\r\n" .
    'Reply-To: mframarini@lyracons.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

mail($to, $subject, $message, $headers);
?>