<?php 
if(!defined('ENVIRONMENTSTARTED')) {
  
  /* Default timezone configuration */
  @date_default_timezone_set('America/Sao_Paulo');

  /* Environment variables */
  if(!defined('HOSTPX')) define("HOSTPX",($_SERVER['HOSTPX'] = ($_SERVER['SERVER_NAME'] = ($_SERVER['SERVER_NAME'] ?? 'localhost'))));

  if(!defined('DEVELOPING')) define("DEVELOPING",($_SERVER['DEVELOPING'] = $developing = (strpos(str_replace('.local.','.dev.',HOSTPX),'.dev.') !== false)));
  if(!defined('PRODUCTION')) define("PRODUCTION",($_SERVER['PRODUCTION'] = $production = (!($developing ?? true))));
  if(!defined('ENVIRONMENTSTARTED')) define("ENVIRONMENTSTARTED",true);

  if(!defined('PATH')) define("PATH",($_SERVER['PATH'] = (__DIR__ .'/data/')),true);
  if(!defined('TEMP_DIR')) define("TEMP_DIR",($_SERVER['TEMP_DIR'] = $_SERVER['PATH']),true);
  if(!defined('DOCUMENT_PATH')) define("DOCUMENT_PATH",($_SERVER['DOCUMENT_PATH'] = $_SERVER['PATH']),true);
  if(!defined('MODULES_PATH')) define("MODULES_PATH",($_SERVER['MODULES_PATH'] = (__DIR__ .'/modules')),true);

  if(!defined('AUTH_MODULE')) define("AUTH_MODULE",($_SERVER['AUTH_MODULE'] = $_SERVER['MODULES_PATH'] . "/auth.php"),true);
  if(!defined('PHPMAIL_MODULE')) define("PHPMAIL_MODULE",($_SERVER['PHPMAIL_MODULE'] = $_SERVER['MODULES_PATH'] . "/phpmailer/load.php"),true);
  if(!defined('DOMPDF_MODULE')) define("DOMPDF_MODULE",($_SERVER['DOMPDF_MODULE'] = $_SERVER['MODULES_PATH'] . "/pdfdom/autoload.inc.php"),true);
  if(!defined('PDOMYSQL_MODULE')) define("PDOMYSQL_MODULE",($_SERVER['PDOMYSQL_MODULE'] = $_SERVER['MODULES_PATH'] . "/pdomysql/load.php"),true);
  if(!defined('PHPSECLIB_MODULE')) define("PHPSECLIB_MODULE",($_SERVER['PHPSECLIB_MODULE'] = $_SERVER['MODULES_PATH'] . "/phpseclib"),true);
  if(!defined('SCPSECLIB_MODULE')) define("SCPSECLIB_MODULE",($_SERVER['SCPSECLIB_MODULE'] = $_SERVER['MODULES_PATH'] . "/phpseclib/Net/SCP.php"),true);
  if(!defined('SSH2SECLIB_MODULE')) define("SSH2SECLIB_MODULE",($_SERVER['SSH2SECLIB_MODULE'] = $_SERVER['MODULES_PATH'] . "/phpseclib/Net/SSH2.php"),true);
  if(!defined('SFTPSECLIB_MODULE')) define("SFTPSECLIB_MODULE",($_SERVER['SFTPSECLIB_MODULE'] = $_SERVER['MODULES_PATH'] . "/phpseclib/Net/SFTP.php"),true);

  $dbhost = 'mysql'; // default from container
  $dbuser = 'root'; // default from container
  $dbpass = 'root'; // default from container

  $_SERVER['SMTP_HOST'] = ''; // default smtp server
  $_SERVER['SMTP_PORT'] = '587'; // default smtp port 
  $_SERVER['SMTP_MAIL'] = 'noreply@localhost'; // default sender email
  $_SERVER['SMTP_PASS'] = ''; // defaul email password auth
  $_SERVER['SMTP_NAME'] = ''; // default sender name on email

  /* Basic configurations */
  $_SERVER['HTTPPX'] = 'http'.((!empty(@$_SERVER['HTTPS'])) ? 's':'').'://';
  
  /* Debuggin tools */
  if($_SERVER['DEVELOPING']) @ini_set('display_errors', '1'); @error_reporting(($_SERVER['DEVELOPING'])?1:0);

  /* Custom global environment script */
  if(file_exists($ce = __DIR__.'/custom.php')) @include($ce);
}
?>