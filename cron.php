<?php 
if(!isset($_REQUEST['crontest'])) exit('disabled. remove this whole line to activate cron.');
/* Tip: You can automatically enable this CRON by running
   # bash -c "sed -i -e '/disabled./s/if(/\/\/if(/' cron.php"
   on your server directory */

$_SERVER['CRONDIRECTORY'] = __DIR__ ."/html/";
$_SERVER['CRONSERVER'] = "http://".($_SERVER['SERVER_NAME'] = "localhost")."/";

$_SERVER['CRONCOMMAND'] = 'bash -c "nohup setsid [WAIT] wget --background --debug --no-cache --timeout=59 --tries=1 --no-check-certificate '.
                          '--server-response --output-document=[LOG] --output-file=[LOG] [PROJECT]" > /dev/null 2>&1 &';


echo date('d-m-Y H:i:s')." Starting cron...\r\n\r\n";

function cronexecute($project,$cronfile,$delay=0,$retries=1) {
  for($i=0;$i<$retries;$i++) {
    $croncmd = ($_SERVER['CRONCOMMAND'] ?? '');

    $croncmd = str_replace('[WAIT] ', 
      (((($wait = ($delay * $i)) > 0) && (strpos($croncmd, '[WAIT]') !== false))
      ? "sleep $wait && " : ""), $croncmd);

    if(strpos($croncmd, '[LOG]') !== false)
      $croncmd = str_replace('[LOG]', $_SERVER['CRONDIRECTORY'].$project.'/'.str_replace('.php', '.log', $cronfile), $croncmd);

    if(strpos($croncmd, '[PROJECT]') !== false)
      $croncmd = str_replace('[PROJECT]', $_SERVER['CRONSERVER'].$project.'/'.$cronfile.
                             '?cron='.strtotime('now').".$i", $croncmd);

    echo "Running cron $project ".(($wait > 0)?'(in about '.$wait.'s)':'').": $cronfile ...\r\n$croncmd\r\n";
    echo shell_exec($croncmd)."\r\n\r\n";
  }
}

function cronexists($project) {
  /* cron types */
  $crontypes = ['quartely','quarterly','halfly','minutly','minutely','fively','hourly','daily','mondly','tuesdly','wednesdly','thursdly','fridly','saturdly','sundly','monthly','yearly'];
  /* cron variables */
  $day = ((int)date('d')); $month = ((int)date('m')); $year = ((int)date('Y')); 
  $hour = ((int)date('H')); $minute = ((int)date('i')); $weekday = ((int)date('w')); /* 0-sun..6-sat */
  /* auto deploy config */
  if(file_exists($_SERVER['CRONDIRECTORY'].'/'.$project.'/on.deploy.php'))
      if(@rename($_SERVER['CRONDIRECTORY'].'/'.$project.'/on.deploy.php', 
                 $_SERVER['CRONDIRECTORY'].'/'.$project.'/on.deploy.run.php')) /* guarantee run once */
        cronexecute($project,'on.deploy.run.php');
  /* search crons */
  foreach($crontypes as $crontime) {
    /* time verification */
    if((($crontime == 'quartely')  || ($crontime == 'quarterly')) || (($crontime == 'halfly')) ||
       (($crontime == 'minutly'))  || (($crontime == 'minutely')) ||
       (($crontime == 'fively')    && (!($minute % 5))) ||
       (($crontime == 'hourly')    && (($minute == 1))) ||
       (($crontime == 'daily')     && (($hour == 6) && ($minute == 2))) ||
       (($crontime == 'mondly')    && (($weekday == 1) && ($hour == 7) && ($minute == 5))) ||
       (($crontime == 'tuesdly')   && (($weekday == 2) && ($hour == 7) && ($minute == 5))) ||
       (($crontime == 'wednesdly') && (($weekday == 3) && ($hour == 7) && ($minute == 5))) ||
       (($crontime == 'thursdly')  && (($weekday == 4) && ($hour == 7) && ($minute == 5))) ||
       (($crontime == 'fridly')    && (($weekday == 5) && ($hour == 7) && ($minute == 5))) ||
       (($crontime == 'saturdly')  && (($weekday == 6) && ($hour == 7) && ($minute == 5))) ||
       (($crontime == 'sundly')    && (($weekday == 0) && ($hour == 7) && ($minute == 5))) ||
       (($crontime == 'monthly')   && (($day == 1) && ($hour == 4) && ($minute == 3))) ||
       (($crontime == 'yearly')    && (($month == 1) && ($day == 2) && ($hour == 3) && ($minute == 4))) )
        /* file read */
        if(is_array($filesdir = scandir($_SERVER['CRONDIRECTORY'].'/'.$project)))
          foreach($filesdir as $file)
            if((strpos($file, '.php') !== false) && (file_exists($_SERVER['CRONDIRECTORY'].'/'.$project.'/'.$file)))
              if((strpos($file, 'cron.'.$crontime) !== false)
              || (strpos($file, 'cron_'.$crontime) !== false))
                if($crontime == 'halfly') 
                  cronexecute($project,$file,27,2);
                else
                  if($crontime == 'quartely' || $crontime == 'quarterly')
                    cronexecute($project,$file,14,4);
                  else
                      cronexecute($project,$file);
  } return true;
}

/* Auto clear logs */
if(($autoclearlogs ?? true) && (intval(date('d')) === 1) && (intval(date('H')) === 23) && (intval(date('i')) === 23))
  @shell_exec('echo " " > /var/www/access.log && echo " " > /var/www/error.log');

/* list 2 deep files */
$folders = scandir($_SERVER['CRONDIRECTORY'], 1);
foreach($folders as $project)
  if(is_dir($_SERVER['CRONDIRECTORY'].'/'.$project))
    if(!(strpos($project,'.old') !== false))
      if(!(strpos($project,'.') !== false))
        if(!(strpos($project,'--') !== false))
          if(is_bool(cronexists($project)))
            if(is_array($subtree = scandir($_SERVER['CRONDIRECTORY'].'/'.$project)))
              foreach($subtree as $sub)
                if(is_dir($_SERVER['CRONDIRECTORY'].'/'.$project.'/'.$sub))
                  if(!(strpos($sub,'.old') !== false))
                      if(!(strpos($sub,'.') !== false))
                        if(!(strpos($sub,'--') !== false))
                          cronexists($project.'/'.$sub);
?>
