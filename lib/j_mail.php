<?php
function j_sendMail($to,$from,$subject,$mssg,$to_name="",$from_name="",$cc=""){
	if($to_name)$to_address="$to_name <$to>";
	else $to_address="$to";
	if($from_name)$from_address="$from_name <$from>\n";
	else $from_address="$from\n";	
	
	$headers .= "MIME-Version: 1.0\n";
	$headers .= "Content-type: text/html; charset=iso-8859-1\n";
	$headers .= "From: $from_address";
	$headers .= "Reply-To: $from_address";
	$headers .= "Reply-Path: $from_address";
	$headers .= "Return-Path: $from_address";
	//the following Xcrap is to make this play nice with ms outlook and hotmail...  not exactly sure what it does though.
	$headers .= "X-Priority: 3\n";
	$headers .= "X-MSMail-Priority: 3\n";
	$headers .= "X-Mailer: MIQS.com\n";
	if($cc) $headers .= "Cc: ".$cc."\n";
//var_dump($to,stripslashes($subject),$mssg,$headers);exit;
	if(mail($to, stripslashes($subject), $mssg, $headers)){//
	 	return true;
	}else{
		var_dump("Could not send email");
		return false;
	}
}
function send_mail_attachments($emailaddress, $emailsubject, $body, $attachments=false)
{
	/*$attachments = Array(
  Array("file"=>"../../test.doc", "content_type"=>"application/msword"),
  Array("file"=>"../../123.pdf", "content_type"=>"application/pdf")
);*/
  $eol="\n";
  $mime_boundary=md5(time());
 
  # Common Headers
  $headers .= 'From: TheMunds.com<jumperooter@yahoo.com>'.$eol;
  $headers .= 'Reply-To: TheMunds.com<jumperooter@yahoo.com>'.$eol;
  $headers .= 'Reply-Path: TheMunds.com<jumperooter@yahoo.com>'.$eol;
  $headers .= 'Return-Path: TheMunds.com<jumperooter@yahoo.com>'.$eol;    // these two to set reply address
  $headers .= "Message-ID: <".$now." TheSystem@".$_SERVER['SERVER_NAME'].">".$eol;
  $headers .= "X-Mailer: MIQS.com".$eol;          // These two to help avoid spam-filters
	$headers .= "X-Priority: 3\n";
	$headers .= "X-MSMail-Priority: 3\n";
	
	
  # Boundry for marking the split & Multitype Headers
  $headers .= 'MIME-Version: 1.0'.$eol;
  $headers .= "Content-Type: multipart/related; boundary=\"".$mime_boundary."\"".$eol;

  $msg = "";     
 

 
  # Setup for text OR html
  $msg .= "Content-Type: multipart/alternative".$eol;
	
  # HTML Version
  $msg .= "--".$mime_boundary.$eol;
  $msg .= "Content-Type: text/html; charset=iso-8859-1".$eol;
  $msg .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
  $msg .= $body.$eol.$eol;
  
   if ($attachments !== false)
  {

   for($i=0; $i < count($attachments); $i++)
   {
     if (is_file($attachments[$i]["file"]))
     { 
       # File for Attachment
       $file_name = substr($attachments[$i]["file"], (strrpos($attachments[$i]["file"], "/")+1));
      
       $handle=fopen($attachments[$i]["file"], 'rb');
       $f_contents=fread($handle, filesize($attachments[$i]["file"]));
       $f_contents=chunk_split(base64_encode($f_contents));    //Encode The Data For Transition using base64_encode();
       fclose($handle);
      
       # Attachment
       $msg .= "--".$mime_boundary.$eol;
       $msg .= "Content-Type: ".$attachments[$i]["content_type"]."; name=\"".$file_name."\"".$eol;
       $msg .= "Content-Transfer-Encoding: base64".$eol;
       $msg .= "Content-Disposition: attachment; filename=\"".$file_name."\"".$eol.$eol; // !! This line needs TWO end of lines !! IMPORTANT !!
       $msg .= $f_contents.$eol.$eol;
      
     }
   }
  }
  # Finished
  $msg .= "--".$mime_boundary."--".$eol.$eol;  // finish with two eol's for better security. see Injection.
  
  # SEND THE EMAIL
  ini_set(sendmail_from,$fromaddress);  // the INI lines are to force the From Address to be used !
  $ok=mail($emailaddress, $emailsubject, $msg, $headers);
  ini_restore(sendmail_from);
  //echo "mail sent";
  return $ok;
}
?>
