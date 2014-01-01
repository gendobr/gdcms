<?php
// ---------------------- email broadcast -- begin -----------------------------
// THIS IS NEW FUNCTION MY_MAIL (). REPLACE THE OLD FUNCTION TO NEW IN functions.php
//function my_mail($mail_to,$mail_subject,$mail_body,$IsHTML=IsHTML,$reply_to=false)
function my_mail($mail_to,$mail_subject,$mail_body,$options=Array()) {
  // global $prefix;

  $debug=false;

  $mail = new phpmailer();

  if(!isset($options['IsHTML'])){
      $options['IsHTML']=IsHTML;
  }

  //$mail->SMTPDebug=10;
  $mail->Timeout=120;

  $mail->PluginDir=script_root."/lib/";

  if (mail_IsSMTP)  {
    $mail->IsSMTP();
    $mail->Host = mail_SMTPhost;
  }elseif(mail_IsSendMail) {
    $mail->IsSendmail();
  }elseif(defined('mail_IsMail') && mail_IsMail){
      $mail->IsMail();
  }

  $mail->CharSet  = site_charset;
  $mail->SMTPAuth = mail_SMTPAuth;
  $mail->Username = mail_SMTPAuth_Username;
  $mail->Password = mail_SMTPAuth_Password;

  // $mail->SMTPDebug = true;

  $mail->From = mail_FromAddress;

  if(isset($options['FromName'])){
      $mail->FromName = $options['FromName'];
  }else{
      $mail->FromName = mail_FromName;
  }

  if(is_array($mail_to)) $recipients=$mail_to; else $recipients=Array(0=>$mail_to);
  //prn($recipients);

  if($debug)
  {
     //prn('debug');
     $mail->AddCC(mail_FromAddress);
  }
  else
  {
    $rcnt=Count($recipients);
    $valid_mails=0;
    for ($j=0;$j<$rcnt;$j++)
    {
       //prn($recipients[$j]);
       if (is_valid_email($recipients[$j]))
       {
           if($valid_mails==0) $mail->AddAddress($recipients[$j]);
           else  $mail->AddCC($recipients[$j]);
           //$mail->AddBCC($recipients[$j]);
           $valid_mails++;
       }
    }
    if ($valid_mails==0) return false;
  }

  if(isset($options['ReplyTo'])){
      $mail->AddReplyTo($options['ReplyTo']);
  }else{
      $mail->AddReplyTo(mail_FromAddress);
  }
  $mail->WordWrap = word_wrap; // set word wrap to 50 characters
  $mail->IsHTML($options['IsHTML']);
  $mail->Subject = $mail_subject;
  //$mail->Body    = nl2br ($mail_body);
  //$mail->AltBody = $mail_body;
  $mail->Body    = $mail_body;
  //prn('sending email',$mail);

  $success=$mail->Send();
  if(!$success)
  {
     echo "Message could not be sent. <p>";
     echo "Mailer Error: " . $mail->ErrorInfo;
     //exit;
  }

  return $success;
}
// ---------------------- email broadcast -- end -------------------------------

?>