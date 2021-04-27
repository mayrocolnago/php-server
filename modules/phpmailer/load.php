<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load autoloader
require __DIR__ .'/PHPMailer.php';
require __DIR__ .'/SMTP.php';
//require __DIR__ .'/POP3.php';
require __DIR__ .'/Exception.php';

function sendmail($to,$subject,$msg,$extra = array()) {

$mail = new PHPMailer(((isset($_REQUEST['debug'])) ? true : false)); // Passing `true` enables exceptions
try {
    //Server settings
    $mail->SMTPDebug = ((isset($_REQUEST['debug'])) ? ((is_numeric($_REQUEST['debug'])) ? $_REQUEST['debug'] : '2') : '0'); // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = ($extra['host'] ?? ($_SERVER['SMTP_HOST'] ?? '')); // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = $extra['user'] ?? ((isset($extra['from'])) ? $extra['from'] : ($_SERVER['SMTP_MAIL'] ?? '')); // SMTP username
    $mail->Password = $extra['pass'] ?? ($_SERVER['SMTP_PASS'] ?? '');  // SMTP password
    $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = ($extra['port'] ?? ($_SERVER['SMTP_PORT'] ?? 587));  // TCP port to connect to

    //Recipients
    $mail->setFrom( ((isset($extra['from'])) ? $extra['from'] : ((isset($extra['user'])) ? $extra['user'] : ($_SERVER['SMTP_MAIL'] ?? ''))) , ((isset($extra['name'])) ? $extra['name'] : ((isset($extra['from'])) ? $extra['from'] : ($_SERVER['SMTP_NAME'] ?? '')) ) );

    if(!is_array($to))
      $mail->addAddress($to);
    else
      foreach($to as $item)
        $mail->addAddress($item);

    //$mail->addReplyTo('MAIL1', 'MAIL2');

    if(isset($extra['cc']))
      if(!is_array($extra['cc']))
        $mail->addCC($extra['cc']);
      else
        foreach($extra['cc'] as $item)
          $mail->addCC($item);

    if(isset($extra['bcc']))
      if(!is_array($extra['bcc']))
        $mail->addBCC($extra['bcc']);
      else
        foreach($extra['bcc'] as $item)
          $mail->addBCC($item);

    //Attachments
    if(isset($extra['attach']))
      if(!is_array($extra['attach']))
        $mail->addAttachment($extra['attach']);
      else
        foreach($extra['attach'] as $item)
          $mail->addAttachment($item);

    //Content
    $mail->isHTML(true); // Set email format to HTML
    $mail->Subject = utf8_decode($subject);
    $mail->Body    = utf8_decode($msg);
    $mail->AltBody = strip_tags(str_ireplace('<br>',chr(13),utf8_decode($msg)));

    $mail->send();

    if(isset($_REQUEST['debug']))
      return json_encode(array('status' => '1', 'msg' => 'Message has been sent'));
    else return true;
  } catch (Exception $e) {
      if(isset($_REQUEST['debug']))
        return json_encode(array('status' => '0', 'msg' => 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo));
      else return false;
  }
}
?>
