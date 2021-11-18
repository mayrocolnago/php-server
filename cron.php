<?php 
if(!isset($_REQUEST['crontest'])) exit('disabled. remove this whole line to activate cron.');


$_SERVER['CRONDIRECTORY'] = $directory = __DIR__ ."/html/";
$_SERVER['CRONSERVER'] = "http://".($_SERVER['SERVER_NAME'] = "localhost")."/";

if(!isset($_REQUEST['debug'])) {
  $_SERVER['CRONCURLPREF'] = 'bash -c "exec nohup setsid curl -d project=[PROJECT] -d cron='.strtotime('now').' --raw --max-time 119 --speed-time 119 --tcp-fastopen --tcp-nodelay --connect-timeout 59'; /* --raw --max-time 59 --speed-time 59 --tcp-fastopen --tcp-nodelay --connect-timeout 59 */
  $_SERVER['CRONCURLSUFX'] = '> /dev/null 2>&1 &"'; /* &>/dev/null & disown */
} else {
  $_SERVER['CRONCURLPREF'] = 'curl -d project=[PROJECT] -d cron='.strtotime('now').' --raw --max-time 119 --speed-time 119 --tcp-fastopen --tcp-nodelay --connect-timeout 59'; /* --raw --max-time 59 --speed-time 59 --tcp-fastopen --tcp-nodelay --connect-timeout 59 */
  $_SERVER['CRONCURLSUFX'] = '';
}

echo date('d-m-Y H:i:s')." Starting cron...\r\n\r\n";

function cronexists($project,$path = "") {
  /* cron types */
  $crontypes = ['minutely','hourly','daily','monthly','yearly'];
  /* cron variables */
  $day = ((int)date('d')); $month = ((int)date('m')); $year = ((int)date('Y')); 
  $hour = ((int)date('H')); $minute = ((int)date('i')); $weekday = ((int)date('w')); /* 0-sun..6-sat */
  /* auto deploy config */
  if(file_exists($project.($path = '/on.deploy.php')))
    if(@rename($project.'/on.deploy.php', $project.($path = '/on.deploy.run.php')))
      return $path;
  /* search crons */
  foreach($crontypes as $crontime) {
    /* time verification */
    if((($crontime == 'minutely')) ||
       (($crontime == 'hourly')   && ($minute == 1)) ||
       (($crontime == 'daily')    && (($hour == 6) && ($minute == 2))) ||
       (($crontime == 'monthly')  && (($day == 1) && ($hour == 4) && ($minute == 3))) ||
       (($crontime == 'yearly')   && (($month == 1) && ($day == 2) && ($hour == 3) && ($minute == 4))) )
        /* file read */
        if(is_array($filesdir = scandir($project)))
          foreach($filesdir as $file)
            if(strpos($file, '.php') !== false)
              if((strpos($file, 'cron.'.$crontime) !== false) && (file_exists($project.'/'.$file))) return $file;
              else if((strpos($file, 'cron_'.$crontime) !== false) && (file_exists($project.'/'.$file))) return $file;
  } return "";
}

function cronexecute($project,$cronfile) {
  if(($cronlog = str_replace('.php','.log',$cronfile)) !== $cronfile)
      $croncurlsufx = str_replace('/dev/null', $_SERVER['CRONDIRECTORY'].$project.'/'.$cronlog, ($_SERVER['CRONCURLSUFX'] ?? ''));

  echo "Running cron ".$project." : ".$cronfile." ...\r\n";
  echo shell_exec($line = str_replace('[PROJECT]', preg_replace('/[^0-9a-zA-Z]/','',$project), 
                  $_SERVER['CRONCURLPREF']).' "'.$_SERVER['CRONSERVER'].$project.'/'.$cronfile.'" '.($croncurlsufx ?? ($_SERVER['CRONCURLSUFX'] ?? ''))).$line."\r\n\r\n";
}

/* Auto clear logs */
if(($autoclearlogs ?? true) && (intval(date('d')) === 1) && (intval(date('H')) === 23) && (intval(date('i')) === 23))
  @shell_exec('echo " " > /var/www/access.log && echo " " > /var/www/error.log');

/* list 2 deep files */
$folders = scandir($directory, 1);
foreach($folders as $project)
  if(!(strpos($project,'.old') !== false))
    if(is_dir($directory.$project))
      if(!(strpos($project,'.') !== false))
        if(!(strpos($project,'--') !== false))
          if(!empty($path = cronexists($directory.$project))) cronexecute($project,$path);
          else 
            if(is_array($subtree = scandir($directory.$project)))
              foreach($subtree as $sub)
              if(!(strpos($sub,'.old') !== false))
                  if(!(strpos($sub,'.') !== false)) 
                    if(!empty($path = cronexists($directory.$project.'/'.$sub))) cronexecute($project.'/'.$sub,$path);
?>