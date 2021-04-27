<?php if(!defined('auth_version')) {
/* creator mayrocolnago@gmail.com - authentication script */
/* ##################################### */
define('auth_version','v10.9.2');
/* use if(defined('auth_version')) to check if script was called */
/* ##################################### */
@date_default_timezone_set('America/Sao_Paulo');
@session_start();
/* project name */
$projectname = ((isset($projectname)) ? $projectname : "project");

/* activate firebase push notification mecanism of google cloud messaging */
$activatepushnotifications = ((isset($activatepushnotifications)) ? $activatepushnotifications : false);
$gcmapiauthkey = ((isset($gcmapiauthkey)) ? $gcmapiauthkey : 'FIREBASE-KEY');
$gcmautoexpire = ((isset($gcmautoexpire)) ? $gcmautoexpire : '1 year'); /* erase tokens older than an year */
$retainusergcm = ((isset($retainusergcm)) ? $retainusergcm : false); /* always create user gcm tokens */
$protectgcmkey = ((isset($protectgcmkey)) ? $protectgcmkey : false); /* lock a cookie associated to gcm */

/* activate authentication through username and password */
$activateuserandpassword = ((isset($activateuserandpassword)) ? $activateuserandpassword : false);
$authenticatefieldname = ((isset($authenticatefieldname)) ? $authenticatefieldname : 'username'); /* uid, username ou email */
$authenticateusertable = ((isset($authenticateusertable)) ? $authenticateusertable : 'users');
$startfromautoinc = ((isset($startfromautoinc)) ? $startfromautoinc : 1);
$allowmultilogin = ((isset($allowmultilogin)) ? $allowmultilogin : false);
$mainpageredirect = ((isset($mainpageredirect)) ? $mainpageredirect : "index.php"); /* redirect where to after login/logout */

/* oauth server configuration */
$activateoauthserver = ((isset($activateoauthserver)) ? $activateoauthserver : false);

/* oauth client configuration */
$activateoauthclient = ((isset($activateoauthclient)) ? ((!$activateoauthserver) ? $activateoauthclient : false) : false);
$oauthremoteaddress = ((isset($oauthremoteaddress)) ? $oauthremoteaddress : ((empty($_SERVER['HTTPS']))?'http':'https').'://'.((isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : 'localhost').'/'.((isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : 'index.php'));
$oauthclientauthid = ((isset($oauthclientauthid)) ? $oauthclientauthid : 'CLIENT-ID');
$oauthclientsecret = ((isset($oauthclientsecret)) ? $oauthclientsecret : 'CLIENT-SECRET');

/* expire login monthly */
$expireloginpermonth = ((isset($expireloginpermonth)) ? $expireloginpermonth : false);

/* hold cookies on a specific domain/sub (leave blank for all) */
$cookiepathhold = ((isset($cookiepathhold)) ? $cookiepathhold : "/");  
$cookiedomainhold = ((isset($cookiedomainhold)) ? $cookiedomainhold : "");

/* allow auth to create a local table to store global configurations */
$storelocalconfigs = ((isset($storelocalconfigs)) ? $storelocalconfigs : false);

/* main authentication class (DO NOT CHANGE FOR NOW AHEAD) */
if(class_exists('auth')) exit('Module conflict class auth');
class auth {
	public static $usertable = 'users';
	public static $gcmtable  = 'firebases';
	public static $oauthtable  = 'oauthd';
	public static $configtable  = 'configs';
	public static $oauthdevices = 'odevices';
	public static $securitykey = '';
	public static $dbfields = array();
	public static $userconfig = array();
	public static $oauthudata = array();
	public static $stcfg = array();
	public static $user = array();
}
/* security key to generate hashes */
auth::$securitykey = "huge-key-to-calc-hashes".$projectname;
function rethash($what) {
/* generate security key hash */
return hash('sha512',str_replace(array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z"),"",strtolower(md5($what.auth::$securitykey)))); }

/* anti hacking/injection and script uploader by mayrocolnago@gmail.com */
function antihackingvars() {
  function recursivelycheckvars(&$item, $key) {
    if((!is_array($item)) && ($key != 'editor')) {
    /* $item = preg_replace("/%/","&#37;",$item); */
    /* $item = preg_replace("/\* /","&#42;",$item); */
    $item = preg_replace("/\"/","&#34;",$item); 
    $item = preg_replace("/'/","&#39;",$item); } }
  array_walk_recursive($_POST, 'recursivelycheckvars');
  array_walk_recursive($_GET, 'recursivelycheckvars');
  array_walk_recursive($_COOKIE, 'recursivelycheckvars');
  array_walk_recursive($_REQUEST, 'recursivelycheckvars');
}
  
function checkuploads($seclevel=3) {
  foreach($_FILES as $nome=>$fgnome)
    if(!empty($fgnome["name"])) {
     $keyban = array("echo","php","aspx","shell","sock","open","read","write","base64","eval","dump","mysql","code");
     $filecont = @file_get_contents($fgnome['tmp_name']); $pg=""; $qp = 0;
     foreach($keyban as $kb) if(stripos($filecont,$kb) !== false){ $pg .= $kb.';'; $qp++; }
     if(($qp >= intval($seclevel)) and ($seclevel != '5')) {
      $msggm = '['.@$_SERVER['HTTP_HOST'].'] user'.((isset($_COOKIE['uid']))? ' '.($_COOKIE['uid'] - 999) : '').
               ' ip '.@$_SERVER['REMOTE_ADDR'].' uploaded file '.@$fgnome['name'].' as '.@$fgnome['type'].
               ' containing "'.$pg.'" as key for security breach at '.date('H:i:s d/m/Y')."\n\n"; 
      $nomean = 'analyse'.date("YzGHis").'.txt';
      $fp = @fopen($nomean,"w");
      @fwrite($fp, $msggm.base64_encode($filecont));
      @fclose($fp); unlink($fgnome['tmp_name']); } } }
  
antihackingvars();

/* userconfig recall on authentications and pushnotifications and cookie vars */
auth::$userconfig['oauthdactive'] = $activateoauthserver;
auth::$userconfig['oauthremoteaddress'] = $oauthremoteaddress;
auth::$userconfig['oauthclientauthid'] = $oauthclientauthid;
auth::$userconfig['oauthclientsecret'] = $oauthclientsecret;
auth::$userconfig['cookiepathhold'] = $cookiepathhold;
auth::$userconfig['cookiedomainhold'] = $cookiedomainhold;
auth::$userconfig['pushnotifications'] = $activatepushnotifications;
auth::$userconfig['pushnotretaingcms'] = $retainusergcm;
auth::$userconfig['storelocalconfigs'] = $storelocalconfigs;
auth::$userconfig['authenticatefieldname'] = $authenticatefieldname;
auth::$userconfig['activateuserandpassword'] = $activateuserandpassword;
auth::$userconfig['listallusers'] = [];
auth::$userconfig['usertable'] = $authenticateusertable;

/* PDO database functions */
if(!function_exists('pdo_connect'))
  @include(__DIR__ . '/pdomysql/load.php');

function autocreatedbtables() {
  if(!function_exists('pdo_query')) return;
  
	pdo_query("CREATE TABLE IF NOT EXISTS users (
    uid bigint(20) NOT NULL AUTO_INCREMENT,
    username varchar(100) COLLATE utf8_general_ci DEFAULT NULL,
    email varchar(100) COLLATE utf8_general_ci DEFAULT NULL,
    passw varchar(200) COLLATE utf8_general_ci NOT NULL DEFAULT '0',
    tags longtext COLLATE utf8_general_ci NULL,
    token varchar(200) COLLATE utf8_general_ci DEFAULT NULL,
    registered int(11) NULL,
    PRIMARY KEY (uid))");

  if(!(@pdo_num_rows(pdo_query("select uid from users limit 1")) > 0))
      pdo_query("INSERT INTO users (username,email,passw,tags,registered) VALUES ('admin','admin@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "','" . hash('sha512', 'admin') . "','" . json_encode(array('master' => '1', 'isdeveloper' => '1')) . "','" . strtotime('now') . "') ");
      
  pdo_query("CREATE TABLE IF NOT EXISTS oauthd (
      oid bigint(20) NOT NULL AUTO_INCREMENT,
      secret varchar(225) COLLATE utf8_general_ci NOT NULL,
      project varchar(30) COLLATE utf8_general_ci NOT NULL,
      ctags longtext COLLATE utf8_general_ci,
      owner bigint(20) NOT NULL,
      created bigint(20) DEFAULT NULL,
      PRIMARY KEY (oid))");

  pdo_query("CREATE TABLE IF NOT EXISTS odevices (
    id bigint(20) NOT NULL AUTO_INCREMENT,
      oid bigint(20) NOT NULL,
      uid bigint(20) NOT NULL,
      device longtext COLLATE utf8_general_ci,
      token longtext COLLATE utf8_general_ci,
      auth longtext COLLATE utf8_general_ci,
      code longtext COLLATE utf8_general_ci,
      counter bigint(20) NOT NULL DEFAULT '0',
  lastaccess bigint(20) NOT NULL,
      registered bigint(20) NOT NULL,
  PRIMARY KEY (id))");

  pdo_query("CREATE TABLE IF NOT EXISTS firebases (
      gid bigint(20) NOT NULL AUTO_INCREMENT,
      uid bigint(20) NULL,
      gcm varchar(225) COLLATE utf8_general_ci NOT NULL,
      ltags longtext COLLATE utf8_general_ci NULL,
      platform varchar(225) COLLATE utf8_general_ci NOT NULL,
      lastview bigint(20) NULL,
      lastlogin bigint(20) NULL,
  PRIMARY KEY (gid))");

  pdo_query("CREATE TABLE IF NOT EXISTS configs (
      cid bigint(20) NOT NULL AUTO_INCREMENT,
      ckey varchar(100) NOT NULL,
      cvalue longtext NULL,
      ctag varchar(100) NULL,
      PRIMARY KEY (cid))");
}
  
/* create tables if them fail */
auth::$dbfields['users'] = array("uid","username","email","passw","tags","token","registered");
auth::$dbfields['gcm'] = array("gid","gcm","platform","useragent","lastlogin");
auth::$dbfields['startid'] = $startfromautoinc;


/* return user-agent info or his ip address */
function useragent($piece=false) { return ((strlen(@$_SERVER['HTTP_USER_AGENT']) > 1) ? $_SERVER['HTTP_USER_AGENT'] : @$_SERVER['REMOTE_ADDR'].' '.@$_SERVER['GATEWAY_INTERFACE'].' '.@$_SERVER['HTTP_ACCEPT_CHARSET'].' '.@$_SERVER['SERVER_SIGNATURE']).(($piece) ? ((isset($_REQUEST['gcm']))?' ('.substr($_REQUEST['gcm'].')',-7,7):'') : ''); }

/* detect device s platform */
auth::$user['deviceinfo'] = useragent();

$platf = "d"; $isDesktop = true; /* takes desktops and laptops as default platform */
/* Android */
$isAndroid = (strpos(strtolower(useragent()),'android') !== false) ? true : false; if($isAndroid) $platf = "a";
/* iPhone */
$isiPhone = (strpos(strtolower(useragent()),'iphone') !== false) ? true : false; if($isiPhone) $platf = "i";
/* iPad */
$isiPad = (strpos(strtolower(useragent()),'ipad') !== false) ? true : false; if($isiPad) $platf = "p";
/* Xbox */
$isxbox = (strpos(strtolower(useragent()),'xbox') !== false) ? true : false; if($isxbox) $platf = "x";
/* Playstation */
$isplaystation = (strpos(strtolower(useragent()),'playstation') !== false) ? true : false; if($isplaystation) $platf = "y";
/* Nintendo */
$isnintendo = (strpos(strtolower(useragent()),'nintendo') !== false) ? true : false; if($isnintendo) $platf = "n";
/* Desktop */
if($platf != "d") $isDesktop = false;
/* stores device s platform on user s configuration properly and sets a function to retreive it */
auth::$user['platform'] = $platf;
function isplatform($w=null) { return ($w == null) ? (auth::$user['platform'] ?? 'd') : (($w == (auth::$user['platform'] ?? 'd')) ? true : false); }
  
/* Get a filtered version of the user-agent */ 
function filtereduseragent($str='',$level=3,$match='lcnm') {
  if($str == '') $str = useragent(false); $str = str_replace(' ','_',trim($str));
  $str = str_replace(array('<','>','Mozilla/5.0_','(Linux;_','(iPhone;_','CPU_','AppleWebKit','Intel_Mac','Version/','KHTML',',','like_Gecko','address','(',')','__'),'_',$str);
  if(!((bool) strpos(strtolower($match),'p'))) $str = str_replace(';','_',$str);
  if(!((bool) strpos(strtolower($match),'b'))) $str = str_replace('/','_',$str);
  if(!((bool) strpos(strtolower($match),'n'))) $str = preg_replace('/[0-9]/','',$str);
  if(!(((bool) strpos(strtolower($match),'l')) || ((bool) strpos(strtolower($match),'c')))) $str = preg_replace('/[a-zA-Z]/','',$str); else
  if(!((bool) strpos(strtolower($match),'m'))) if((bool) strpos(strtolower($str),'mobile')) $str = str_replace(array('Chrome','Firefox','Safari','Opera','like_Mac_'),'_',$str);
  $str = str_replace(' ','_',trim(str_replace('_',' ',$str)));
  while ((bool) strpos($str,'__')) $str = str_replace('__','_',$str);
  if(($level > 0) && ($level < count(explode('_',$str)))) {
    $nstr = explode('_',$str); $str = '';
    for($i=0;$i<$level;$i++) $str .= $nstr[$i].'_'; }
  return trim(str_replace('_',' ',$str));
}

/* install the global configs part */
if(!$storelocalconfigs) {
  function setconfig($key='',$value='') { return $value; }
  function getconfig($key='',$def='',$save=false) { return $def; }
} else {
  function setconfig($key='',$value='') {
    $key = preg_replace('/[^0-9a-zA-Z]/','',$key);
    if(@pdo_query("UPDATE ".auth::$configtable." SET cvalue='".urlencode($value)."' WHERE ckey='".$key."' ") < 1)
      if(@pdo_num_rows(pdo_query("SELECT * FROM ".auth::$configtable." WHERE ckey='".$key."' ")) < 1)
        if(pdo_query("INSERT INTO ".auth::$configtable." (ckey,cvalue) VALUES ('".$key."','".urlencode($value)."') ") < 1)
          autocreatedbtables();
    auth::$stcfg[$key] = $value;
    return $value;
  }
  function getconfig($key='',$def='',$save=false) {
    $key = preg_replace('/[^0-9a-zA-Z]/','',$key);
    if(empty(auth::$stcfg)) { 
      $load = pdo_query("SELECT * FROM ".auth::$configtable." ");
      foreach(@pdo_fetch_array($load) as $l) auth::$stcfg[$l['ckey']] = urldecode($l['cvalue']); }
    if(isset(auth::$stcfg[$key])) $def = auth::$stcfg[$key]; 
    else if($save) setconfig($key,$def);
    return $def; 
  }
} 

/* detect whether the oauth stuff is activated */
if(!$activateoauthclient) {

	/* user get info function */
	function getuser($key='',$second='aut@') {
		if(($key == '') || (is_bool($key))) { /* clear users class variable indexes and return an object */
		  if($second == 'aut@') {
			$newuserarrayfilter = array();
			foreach ((array)auth::$user as $u => $v)
			  if(!is_numeric($u)) $newuserarrayfilter[$u] = $v;
			if(is_array($tags = @json_decode((auth::$user['tags'] ?? ''),true)))
			  foreach($tags as $t => $v) 
			    if(!isset($newuserarrayfilter[$t]))
			  	  if(!(strpos($t,'_') !== false)) $newuserarrayfilter[$t] = $v;
				  else {
					$e = explode('_',$t.'_');
					if(!empty($e[1] ?? '')) $newuserarrayfilter[$e[1]] = $v;
				  } 
			return ($key === true) ? ((array) $newuserarrayfilter) : ((object) $newuserarrayfilter);
		  } else {
			$newuserarrayfilter = array();
			$allusers = listusers();
			foreach ($allusers as $userr)
			  if(($userr['uid'] ?? '') == $second)
			    foreach ($userr as $u => $v)
				  if(!is_numeric($u)) $newuserarrayfilter[$u] = $v;
			return ($key === true) ? ((array) $newuserarrayfilter) : ((object) $newuserarrayfilter);
		  }
		} else {
		if(is_numeric($second)) { /* obtaining another user */
			$pu=@pdo_fetch_item(pdo_query("SELECT * FROM ".auth::$usertable." WHERE uid='".$second."' "));
			$atags = @json_decode(($pu['tags'] ?? '[]'),true);
			return ((isset($pu[$key])) ? $pu[$key] : ((isset($atags[$key])) ? urldecode($atags[$key]) : ''));
		} else { /* threathing default value matter */
			if(isauthed()) {
				if(!isset(auth::$user['tags'])) auth::$user['tags'] = array();
				if(!is_array(auth::$user['tags'])) if(auth::$user['tags'] != "")
						auth::$user['tags'] = json_decode(auth::$user['tags'],true);
					else auth::$user['tags'] = array();
				return ((isset(auth::$user[$key])) ? auth::$user[$key] : ((isset(auth::$user['tags'][$key])) ? urldecode(auth::$user['tags'][$key]) : (($second != 'aut@') ? str_replace('==','',$second) : '')));
			} else
			return '';
		} }
	}
	
	/* user set info function */
	function setuser($key,$valor,$theid='auto') {
    $key = str_replace('@','',$key); $result = '';
		if(is_numeric($theid)) {
			if(in_array($key,auth::$dbfields['users']))
				$result = pdo_query("UPDATE ".auth::$usertable." SET ".$key."='".$valor."' WHERE uid='".$theid."' ");
			else {
				$nowtags = @json_decode(getuser('tags',$theid),true);
				if($valor == "") unset($nowtags[$key]); else $nowtags[$key] = $valor;
				$result = pdo_query("UPDATE ".auth::$usertable." SET tags='".json_encode($nowtags)."' WHERE uid='".$theid."' ");
			}
		} else
			if(isauthed()) {
				$theid = getuser('uid');
				if((in_array($key,auth::$dbfields['users'])) || (in_array($key,auth::$dbfields['gcm']))) {
					$result = pdo_query("UPDATE ".((in_array($key,auth::$dbfields['users'])) ? auth::$usertable : auth::$gcmtable)." SET ".$key."='".$valor."' WHERE uid='".$theid."' ");
					auth::$user[$key] = $valor; }
				else {
					auth::$user['tags'][$key] = urlencode($valor);
					if($valor == "") unset(auth::$user['tags'][$key]);
					$result = pdo_query("UPDATE ".auth::$usertable." SET tags='".json_encode(auth::$user['tags'])."' WHERE uid='".$theid."' "); }
			}
    return (($result == '1') ? $valor : '');
	}

   /* create a new user */
   function newuser($mixedarray=['username'=>'admin', 'passw'=>'admin']) {
	 $data = $extra = [];
	 $convert = [
		'credential' => auth::$userconfig['authenticatefieldname'],
		'credencial' => auth::$userconfig['authenticatefieldname'],
		'usuario' => auth::$userconfig['authenticatefieldname'],
		'acesso' => auth::$userconfig['authenticatefieldname'],
		'login' => auth::$userconfig['authenticatefieldname'],
		'auth' => auth::$userconfig['authenticatefieldname'],
		'mail' => 'email',
		'senha' => 'passw',
		'keypass' => 'passw',
		'password' => 'passw',
		'keyphrase' => 'passw',
		'created' => 'registered',
	 ];
	 /* split fixed columns from extras */
	 foreach($mixedarray as $mx => $v)
	   if((!empty($v = (is_array($v) ? json_encode($v) : $v))) && (!empty($v = (is_object($v) ? json_encode($v) : $v))))
	     if(in_array(($mx = preg_replace('/[^a-z0-9]/','',strtolower($convert[$mx] ?? $mx))), auth::$dbfields['users']))
	       $data[$mx] = $v; else $extra[$mx] = $v;
	 /* parse name entry */
	 if(!empty($name = ($extra[$nk = 'name'] ?? ($extra[$nk = 'nome'] ?? ''))))
	   $name = rmA(urldecode(($extra[$nk] = urlencode(ucstrname(strtolower(urldecode($name)))))));
     /* create an username if not set */
	 if((!isset($data['username'])) && (!empty($name ?? ''))) {
		$auser = explode(" ",strtolower($name)); $i = 1;
		if(count($auser) < 2) return 0;
		$data['username'] = $auser[0].$auser[count($auser)-1];
		if((alreadyindb('username',$data['username'])) && (count($auser) >= 3)) $data['username'] = $auser[0].$auser[count($auser)-2];
		if(alreadyindb('username',$data['username'])) while (alreadyindb('username',$data['username'])) $data['username'] = $auser[0].$auser[count($auser)-1].$i++;
	 } else
	   if(!isset($data['username'])) $data['username'] = 'admin';
	 if(!isset($data['passw'])) $data['passw'] = 'admin';
	 if(!isset($data['email'])) $data['email'] = $data['username'].'@'.($_SERVER['SERVER_NAME'] ?? 'localhost');
	 if(!isset($data['registered'])) $data['registered'] = strtotime('now');
	 if(!isset($data['token']))	$data['token'] = strtotime('now');
	 /* validate fields */
	 if(strlen($data['passw']) < 128) $data['passw'] = hash('sha512',$data['passw']);
	 if(!is_numeric($data['token'])) $data['token'] = substr(preg_replace('/[^0-9]/','',$data['token']),0,11);
	 if(!is_numeric($data['registered'])) $data['registered'] = substr(preg_replace('/[^0-9]/','',$data['registered']),0,11);
	 $data['username'] = substr(preg_replace('/[^a-z0-9]/','',@strtolower($data['username'])),0,100);
	 $data['email'] = substr(preg_replace('/[^a-z0-9\@\.\_]/','',@strtolower($data['email'])),0,100);
	 $data['passw'] = substr(preg_replace('/[^a-z0-9]/','',@strtolower($data['passw'])),0,200);
	 /* add user */
	 return (alreadyindb('username',$data['username'])) ? 0 :
		     ((pdo_query("insert into users (username,passw,email,tags,token,registered) values ".
	 	               "('".$data['username']."', '".$data['passw']."', '".$data['email']."', '".json_encode($extra)."', '".$data['token']."', '".$data['registered']."')") > 0)
		    ? pdo_insert_id() : 0);
   }


  /* get all users (same from oauth) */
  function listusers($query='') {
	if(empty(auth::$userconfig['listallusers'][$qhash = md5($query.'q')] ?? [])) {
	  auth::$userconfig['listallusers'][$qhash] = @pdo_fetch_array(pdo_query("SELECT * FROM ".auth::$usertable." ".((!empty($query)) ? "WHERE ".str_replace(':','=',$query) : "")));
	  foreach(auth::$userconfig['listallusers'][$qhash] as &$user) {
	    $tags = @json_decode($user['tags'], true);
		foreach($tags as $t => $v)
		  if(!in_array($t,auth::$dbfields['users'])) 
		    if(!(strpos($t,'_') !== false)) $user[$t] = $v;
			else {
				$e = explode('_',$t.'_');
				if(!empty($e[1] ?? '')) $user[$e[1]] = $v;
			}
		for($i=0;$i<=10;$i++)
		  if(isset($user[strval($i)]))
		     unset($user[strval($i)]);
		unset($user['tags']);
	} }
	return auth::$userconfig['listallusers'][$qhash];
  }
  
  /* get user uid functions */
  function getuseruidbyusername($username) {
    $uid = @pdo_fetch_item(pdo_query("SELECT uid,username FROM ".auth::$usertable." WHERE username='".str_replace(array('"',"'"),'',$username)."'"));
    return ((!isset($uid['uid'])) || (@$uid['uid'] == '')) ? -1 : $uid['uid'];
  }
  
  function getuseruidbyemail($email) {
    $uid = @pdo_fetch_item(pdo_query("SELECT uid,email FROM ".auth::$usertable." WHERE email='".preg_replace('/[^0-9a-z\.\@\_\-]/','',$email)."'"));
    return ((!isset($uid['uid'])) || (@$uid['uid'] == '')) ? -1 : $uid['uid'];
  }

	/* user is logged in info function */
	function isauthed() {
		return (intval(auth::$user['uid'] ?? 0) > 0);
	}

}


/* user get info function */
function getlocal($key,$second='aut@') {
	if((auth::$userconfig['pushnotifications']) && (auth::$userconfig['pushnotretaingcms'])) {
		if(!isset(auth::$user['ltags'])) {
			$pq = pdo_query("SELECT ltags,gcm from ".auth::$gcmtable." WHERE gcm='".@$_COOKIE['gcm']."' ");
			if(pdo_num_rows($pq) > 0) {
				$pq = @pdo_fetch_item($pq);
				auth::$user['ltags'] = (($pq['ltags'] != "") ? $pq['ltags'] : ''); }
			else auth::$user['ltags'] = '';
		}
		if((!isset(auth::$userconfig['localtags'])) || (!is_array(auth::$userconfig['localtags'])))
			auth::$userconfig['localtags'] = json_decode(auth::$user['ltags'],true);
	} else {
		if(!isset(auth::$userconfig['localtags'])) auth::$userconfig['localtags'] = ((isset($_COOKIE['ltags'])) ? urldecode($_COOKIE['ltags']) : '');
	}
	if(!is_array(auth::$userconfig['localtags'])) if(auth::$userconfig['localtags'] != "")
		auth::$userconfig['localtags'] = json_decode(auth::$userconfig['localtags'],true);
	else auth::$userconfig['localtags'] = array();

	return (($key != "") ? ((isset(auth::$userconfig['localtags'][$key])) ? urldecode(auth::$userconfig['localtags'][$key]) : (($second != 'aut@') ? $second : '')) : '');
}

/* local set info function */
function setlocal($key,$valor) {
	$constructltags = getlocal('');
	auth::$userconfig['localtags'][$key] = urlencode($valor);
	if($valor == "") unset(auth::$userconfig['localtags'][$key]);

	if((auth::$userconfig['pushnotifications']) && (auth::$userconfig['pushnotretaingcms'])) {
		if(isset($_COOKIE['gcm'])) pdo_query("UPDATE ".auth::$gcmtable." SET ltags='".json_encode(auth::$userconfig['localtags'])."' WHERE gcm='".$_COOKIE['gcm']."' ");
		if(isset($_COOKIE['ltags'])) delcookie('ltags');
	} else
		savecookie('ltags',urlencode(json_encode(auth::$userconfig['localtags'])));
  return $valor;
}

/* cookie functions */
function delcookie($key) {
	@setcookie($key,'',(strtotime('now')-1),'/', auth::$userconfig['cookiedomainhold'], false, false);
	unset($_COOKIE[$key]);
  return '';
}

function savecookie($key,$valor,$tempo='auto') {
	if($tempo == 'auto') { $tempo = strtotime("+1 year"); }
	if(isset($_COOKIE[$key])) { delcookie($key); }
	$_COOKIE[$key]=@$valor;
	@setcookie($key,$valor,$tempo, auth::$userconfig['cookiepathhold'], auth::$userconfig['cookiedomainhold'], false, false);
  return $valor;
}


auth::$userconfig['allowmultilogin'] = $allowmultilogin;

/* set debug cookie identification */
if(isset($_GET['debug'])) savecookie('debug',$_GET['debug']);

/* detect whatever something is on database */
function alreadyindb($campo,$valor,$wheres='',$tabl='auto') {
	if($tabl == 'auto') { $tabl = auth::$usertable; }
	return ((@pdo_num_rows(pdo_query("SELECT ".$campo." FROM ".$tabl." WHERE ".$campo." like '".$valor."' ".$wheres)) > 0) ? true : false);
}

/* get the reverse color of a background */
function getcontrastcolor($hexcolor) {
  $hexcolor = str_replace('#','',$hexcolor);
  $r = @hexdec(substr($hexcolor, 0, 2));
  $g = @hexdec(substr($hexcolor, 2, 2));
  $b = @hexdec(substr($hexcolor, 4, 2));
  $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
	return ($yiq >= 128) ? '#333333' : '#f6f6f6'; 
}

/* merge two arrays into one */
function mergearrays($a1,$a2,$priorfirst=false,&$instant=null) {
  $a1 = @json_decode(json_encode($a1),true);
  $a2 = @json_decode(json_encode($a2),true);
  if(!is_array($a1)) $a1 = array();
  if(!is_array($a2)) $a2 = array();
  foreach ((array)$a2 as $a => $v) 
    if(((in_array($a,$a1)) && (!$priorfirst)) || (!in_array($a,$a1)))
      $a1[$a]=$v;
  if($instant != null) $instant = $a1;
	return $a1;
}
  
/* exit script with a code in json format */
function json_exitcode($returnstr,$defkey="result",$printhead=false,$printpolicy=false,$extra=null) {
  $return = array($defkey => $returnstr);
  if($printpolicy) header('Access-Control-Allow-Origin: *');
	if($printhead) header('Content-Type: application/json');
  if($extra != null) if(is_array($extra)) mergearrays($return,$extra,true,$return);
  echo json_encode($return); exit;
}
  
/* calculate the remaining time between two timestamp */
function remainingstr($value1,$value2) {
  if($value1 > $value2) $sl = $value1 - $value2;
  else $sl = $value2 - $value1; $msgst = '';
  $days = ((int) ($sl / 86400)); $sl = $sl % 86400;
  $hours = ((int) ($sl / 3600)); $sl = $sl % 3600;
  $minutes = ((int) ($sl / 60));
  $seconds = ((int) ($sl % 60));
  if($days > 0) $msgst .= $days.' day'.(($days == '1')?'':'s').' ';
  if($hours > 0) $msgst .= $hours.' hour'.(($hours == '1')?'':'s').' ';
  if($minutes > 0) $msgst .= $minutes.' minute'.(($minutes == '1')?'':'s').' ';
  if($seconds > 0) $msgst .= $seconds.' second'.(($seconds == '1')?'':'s');
  return $msgst;
}
  
/* names in portuguese */
function ucstrname($string, $delimiters = array(" ", "-", ".", "'", "O'", "Mc"), $exceptions = array("de", "da", "dos", "das", "do", "I", "II", "III", "IV", "V", "VI")) {
    $string = mb_convert_case($string, MB_CASE_TITLE, "UTF-8");
    foreach ($delimiters as $dlnr => $delimiter) {
        $words = explode($delimiter, $string);
        $newwords = array();
        foreach ($words as $wordnr => $word) {
          if(in_array(mb_strtoupper($word, "UTF-8"), $exceptions)) $word = mb_strtoupper($word, "UTF-8");
          else if(in_array(mb_strtolower($word, "UTF-8"), $exceptions)) $word = mb_strtolower($word, "UTF-8");
               else if(!in_array($word, $exceptions)) $word = ucfirst($word);
          array_push($newwords, $word); }
        $string = join($delimiter, $newwords); }
   return $string;
} 

function rmA($string) {
	return strtr(utf8_decode($string), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
}
  
/* language functions */
function isbrazil() {
  $pref_br = array('66', '138', '154', '177', '179', '187', '189', '192', '200', '201');
  $pref_ip = substr(($_SERVER['REMOTE_ADDR'] ?? '...'), 0, 3);
  return in_array($pref_ip, $pref_br);
}
  
class lang {
  public static $its = 'en_US';
  public static function is() { return lang::$its; }
  public static function tr($en='',$br=null) { if((strpos(strtolower(lang::$its),'br') !== false) && ($br != null)) return $br; return $en; }
  public static function configure(){
    if((!isset($_GET['lg'])) && (!isset($_GET['locale'])) && (!isset($_GET['l'])) && (!isset($_GET['lang'])) && (!isset($_GET['language'])))
      if(!isset($_COOKIE['language']))
        if(strpos(strtolower(@$_SERVER['HTTP_ACCEPT_LANGUAGE']),'br') !== false)
          lang::$its = (isbrazil()) ? 'pt_BR' : 'en_US';
        else
          lang::$its = 'en_US';
      else
        lang::$its = $_COOKIE['language'];
    else
      if(isset($_GET['l']))
        if(($_GET['l'] == 'en_US') || ($_GET['l'] == 'pt_BR') || ($_GET['l'] == 'en') || ($_GET['l'] == 'br'))
          lang::$its = savecookie('language', ((strpos(strtolower($_GET['l']),'br') !== false) ? 'pt_BR' : 'en_US') );
        else
          lang::$its = (strpos(strtolower(lang::$its),'br') !== false) ? 'pt_BR' : 'en_US';
      else
        lang::$its = savecookie('language',(strpos(strtolower( 
                      ((isset($_GET['language']))?$_GET['language'] : 
                      ((isset($_GET['locale']))?$_GET['locale'] :
                      ((isset($_GET['lang']))?$_GET['lang'] :
                      ((isset($_GET['lg']))?$_GET['lg'] : lang::$its )))) 
                    ),'br') !== false) ? 'pt_BR' : 'en_US');
  }
}
lang::configure();
  

/* simpler version of curl function */
function curlsend($addressi,$pdata=null,$timeout=0) {
	$data = (!is_array($pdata)) ? $pdata : http_build_query($pdata);
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $addressi);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($ch, CURLOPT_POST, (($pdata != null) ? true : false) );
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);
 	curl_setopt ($ch, CURLOPT_USERAGENT,useragent());
    if(is_numeric($timeout)) if($timeout > 0) curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    if(is_numeric($timeout)) if($timeout > 0) curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	if($pdata != null) curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
	$returne = curl_exec ($ch);
	curl_close( $ch );
	return $returne;
}

/* mastered curl function that retains cookies */
function fetchsend($url,$pdata=null,$persistent=false,$timeout=0) {
	$data = (!is_array($pdata)) ? $pdata : http_build_query($pdata);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	if($pdata != null) curl_setopt ($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_USERAGENT,useragent());
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
  if(is_numeric($timeout)) if($timeout > 0) curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  if(is_numeric($timeout)) if($timeout > 0) curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	if(isset($_COOKIE['cookiefetch'])) curl_setopt($ch, CURLOPT_COOKIE, urldecode($_COOKIE['cookiefetch']));
	curl_setopt($ch, CURLOPT_HEADER, 1);
	if($pdata != null) curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
	$result = curl_exec($ch);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$header = substr($result, 0, $header_size);
	$body = substr($result, $header_size);
	$start = strpos($header, 'Set-Cookie');
	$end = strpos($header, 'Content-Length');
	$parts = explode('Set-Cookie:', substr($header, $start, $end - $start));
	$cookies = array();
	foreach ((array)$parts as $co) {
			$cd = explode(';', $co);
			if (!empty($cd[0])) $cookies[] = $cd[0]; }
	curl_close($ch);
	$cookiesgr = array(); 
	foreach((array)$cookies as $item)
		if(!(strpos($item,'cookiefetch') !== false))
			if(!(strpos($item,'PHPSESSID') !== false))
				$cookiesgr[] = $item;
	if(!empty($cookiesgr)) {
		$grco = urlencode(implode(';',$cookiesgr));
		@setcookie('cookiefetch',$grco,strtotime('+1 '.(($persistent)?'year':'second')),'/','',false,false);
		$_COOKIE['cookiefetch'] = $grco; }
	return $body;
}

/* logout attempt */
function clearlogin() {
  delcookie('gcm');
  delcookie('uid');
  delcookie('auth');
  delcookie('token');
  delcookie('oauthtoken_'.(auth::$userconfig['oauthclientauthid'] ?? ''));
}

if(isset($_REQUEST['logout'])) {
  header('Access-Control-Allow-Origin: *');
  
  pdo_query("delete from ".auth::$oauthdevices." where uid='".((isset($_COOKIE['uid']))?($_COOKIE['uid'] - 999):'')."' and device='".filtereduseragent()."'");
  pdo_query("delete from ".auth::$gcmtable." where uid=".(($retainusergcm) ? "null" : "'".((isset($_COOKIE['uid']))?($_COOKIE['uid'] - 999):'')."'")." and platform='".filtereduseragent()."'");
  
  clearlogin();
  auth::$user = array();
  
  if(isset($_REQUEST['json'])) {
    header('Content-Type: application/json');
  	echo json_encode(array("isauthed" => 0));
    exit;
  }
  if(isset($_REQUEST['redirect_uri'])) { 
    echo '<meta http-equiv="refresh" content="0; url='.$_REQUEST['redirect_uri'].'">'; 
    exit; 
  }
	if(strpos(($_SERVER['SCRIPT_FILENAME'] ?? ''),'auth.php') !== false) {
		echo '<meta http-equiv="refresh" content="0; url='.$mainpageredirect.'">';
		exit;
	}
}


/* auto fill access token cookies */
if(isset($_REQUEST['access_token'])) savecookie('oauthtoken_'.$oauthclientauthid, $_REQUEST['access_token']);
if(isset($_REQUEST['actk'])) savecookie('oauthtoken_'.$oauthclientauthid, $_REQUEST['actk']);
$_SERVER['access_token'] = ($_COOKIE['oauthtoken_'.$oauthclientauthid] ?? '');


/* pushnotifications get an automatic identification */
if(($activatepushnotifications) && (!$activateoauthclient)) {

	if(($protectgcmkey) && (isset($_COOKIE['gcm']))) {
		$protectgcmtoken = preg_replace('/[a-z]/','',strtolower(md5($_COOKIE['gcm'].'510CKZ'.((!isset($_COOKIE['debug'])) ? filtereduseragent() : '') )));
		if(!((isset($_COOKIE['gcmauth'])) && ($_COOKIE['gcmauth'] == $protectgcmtoken))) {
			$_REQUEST['gcm'] = 'null'; delcookie('gcm'); delcookie('gcmauth'); }
	}
	if((isset($_GET['gcm'])) || ( ($retainusergcm) && (!isset($_COOKIE['gcm'])) )) {
		if((strlen(@$_GET['gcm']) > 10) || (!isset($_COOKIE['gcm']))) {
			if(strlen(@$_GET['gcm']) < 50) { /*temp*/
				$_GET['gcm'] = str_replace(".","a",($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')).'b'.md5(filtereduseragent().($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
				savecookie('gcm',@$_GET['gcm']);
				if($protectgcmkey) savecookie('gcmauth',preg_replace('/[a-z]/','',strtolower(md5($_COOKIE['gcm'].'510CKZ'.((!isset($_COOKIE['debug'])) ? filtereduseragent() : '') ))));
			} else {
				if($_COOKIE['gcm'] != @$_GET['gcm']) {
					if(isset($_COOKIE['gcm'])) auth::$userconfig['previousgcm'] = @$_COOKIE['gcm']; /*merge*/
					pdo_query("DELETE FROM ".auth::$gcmtable." WHERE gcm='".@$_COOKIE['gcm']."' or gcm='".@$_GET['gcm']."' "); }
				savecookie('gcm',@$_GET['gcm']);
				if($protectgcmkey) savecookie('gcmauth',preg_replace('/[a-z]/','',strtolower(md5($_COOKIE['gcm'].'510CKZ'.((!isset($_COOKIE['debug'])) ? filtereduseragent() : '') ))));
			}
		}
	}
	/* deleting expired tokens */
	if(strlen($gcmautoexpire) > 2)
		pdo_query("DELETE FROM ".auth::$gcmtable." WHERE lastlogin < ".strtotime('-'.str_replace(array('+','-'),'',$gcmautoexpire)));

	/* information gattering through gcm */
	if(isset($_COOKIE['gcm'])) {
		$guser = pdo_query("SELECT * FROM ".auth::$gcmtable." WHERE gcm='".str_replace(array('"',"'",'%'),'',$_COOKIE['gcm'])."' limit 1");
		if(pdo_num_rows($guser) > 0) {
			$guser = @pdo_fetch_item($guser);
            auth::$user = mergearrays($guser,auth::$user);
		} else
			if((isauthed()) || ($retainusergcm)) {
				pdo_query("INSERT INTO ".auth::$gcmtable." (".((getuser('uid')!="")?'uid,':'')."gcm,".((isset($_COOKIE['ltags']))?'ltags,':'')."platform,lastlogin) VALUES (".((getuser('uid')!="")?"'".getuser('uid')."',":"")."'".$_COOKIE['gcm']."',".((isset($_COOKIE['ltags'])) ? "'".$_COOKIE['ltags']."',":"")."'".filtereduseragent()."','".strtotime("now")."') ");
				auth::$user['gid'] = pdo_insert_id(); auth::$user['lastlogin'] = strtotime('now'); }
		
		auth::$user['gcm'] = @$_COOKIE['gcm'];
	}

	/* cleaning cached out tokens from database */
	if((!isauthed()) && (isset(auth::$user['gid']))) {
		if(!$retainusergcm) {
			pdo_query("DELETE FROM ".auth::$gcmtable." WHERE gcm='".str_replace(array('"',"'",'%'),'',$_COOKIE['gcm'])."' ");
			if(isset(auth::$user['gid'])) unset(auth::$user['gid']);
			if(isset(auth::$user['lastlogin'])) unset(auth::$user['lastlogin']); }
		if(isset(auth::$user['uid'])) unset(auth::$user['uid']); }

	/* push server send command */
	 auth::$userconfig['servergcm'] = $gcmapiauthkey;

	 function sendGCM($uid,$titulo,$preview="",$mensagem="",$postdata="") {
     $rback = true;
		 if(is_array($uid)) {
       foreach($uid as $ru)
         $rback = sendGCM($ru,$titulo,$preview,$mensagem,$postdata);
     return $rback; }
     $quemvai = $uid;
      if(strlen($uid) > 50) $quemvai = $uid; else $quemvai = '/topics/'.$uid;
      if(is_numeric($uid)) {
        $quemvai = array();
        $todos = pdo_query("select uid,gcm from ".auth::$gcmtable." where uid='".$uid."' ");
        foreach ((array)pdo_fetch_array($todos) as $tq)
          array_push($quemvai,$tq['gcm']);
      } else
        $quemvai = array($quemvai);
			foreach ((array)$quemvai as $quem) {
				$url = 'https://fcm.googleapis.com/fcm/send';
				if($postdata == "") {
					if($mensagem == "") {
						$mensagem = $preview;
						if($preview == "") $mensagem = $titulo; }
					if(strlen($titulo) > 40) $titulo = substr($titulo,0,40).'...';
					if(strlen($preview) > 90) $preview = substr($preview,0,90).'...';
					$datan = array("title" => $titulo, "body" => $preview, "sound" => "default"); /* , "click_action" => "FCM_PLUGIN_ACTIVITY" */
					$post = array("to" => $quem, "priority" => "high", "notification" => $datan, "data" => array("msg" => $mensagem) );
				} else
					$post = $postdata;
				$headers = array('Authorization: key=' . auth::$userconfig['servergcm'], 'Content-Type: application/json');
		  		$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		  		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $post ) );
      			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 15);
        		curl_setopt( $ch, CURLOPT_TIMEOUT, 15);
		  		$result = curl_exec( $ch );
				if (curl_errno( $ch ) ) { $rback = false; $result .= '<!-- GCM error: ' . curl_error( $ch ). ' -->'; }
			  if(isset($_GET['debug'])) echo $result;
			  curl_close( $ch );
		 }
    return $rback;
	}

	/* Update lastseen/lastview status */
	if(!empty($_COOKIE['gcm'] ?? '')) pdo_query("UPDATE ".auth::$gcmtable." SET lastview='".strtotime('now')."' WHERE gcm='".str_replace(array('"',"'",'%'),'',$_COOKIE['gcm'])."' limit 1 ");
}

/* detect whether the oauth stuff is activated again */
if(!$activateoauthclient) {

/* authenticate a user id */
function authenticateuserid($uid,$timer='@auto') {
  if($timer != '@auto') $logintime = $timer;
  else $logintime = strtotime('now');
	setuser('token',$logintime,$uid);
	savecookie('uid',($uid + 999));
	savecookie('auth',rethash(((auth::$userconfig['allowmultilogin']) ? getuser('registered',$uid) : $logintime)));
	savecookie('token',rethash($uid.((!isset($_COOKIE['debug'])) ? filtereduseragent() : '')));
}

/* login attempts */
if($activateuserandpassword) {
	if(/* (!isset($_COOKIE['auth'])) && */ (isset($_POST['login'])) && (isset($_POST['password']))) {
		$auser = pdo_query("SELECT * from ".auth::$usertable." WHERE (".$authenticatefieldname."='".str_replace(' ','',$_POST['login'])."')");
		if(@pdo_num_rows($auser) > 0) {
			$auser = pdo_fetch_item($auser);
			if($auser['passw'] == hash('sha512',$_POST['password'])) {
				$auser['lastlogin'] = strtotime('now');
				authenticateuserid($auser['uid']);
			}
	  }
	}
}

/* login identification */
if((isset($_COOKIE['uid'])) && (isset($_COOKIE['auth'])) && (isset($_COOKIE['token']))) {
	
	if(is_numeric($_COOKIE['uid'])) {
		$uid = $_COOKIE['uid'] - 999;
 	 	$iuser = pdo_query("SELECT * FROM ".auth::$usertable." WHERE (uid='".$uid."')");

		if(@pdo_num_rows($iuser) > 0) {
			$iuser = @pdo_fetch_item($iuser);

			if(@$_COOKIE['auth'] == rethash($iuser[((@auth::$userconfig['allowmultilogin'])?'registered':'token')])) {
			 	if(@$_COOKIE['token'] == rethash($iuser['uid'].((!isset($_COOKIE['debug'])) ? filtereduseragent() : '') )) {
					auth::$user = mergearrays(auth::$user,$iuser);

					if(($activatepushnotifications) && (isset($_COOKIE['gcm'])))
						if(alreadyindb('gcm',$_COOKIE['gcm'],'',auth::$gcmtable))
							pdo_query("UPDATE ".auth::$gcmtable." SET uid='".$iuser['uid']."' WHERE gcm='".$_COOKIE['gcm']."'");
						else
							pdo_query("INSERT INTO ".auth::$gcmtable." (uid,gcm,platform,lastlogin) VALUES ('".$iuser['uid']."','".$_COOKIE['gcm']."','".filtereduseragent()."','".strtotime("now")."') ");
					  
				} else clearlogin();
			} else clearlogin();
		} else clearlogin();
	} else clearlogin();
}


if(isset($_COOKIE['oauthtoken_'.$oauthclientauthid])) {

	$otuid = ($otcookie = str_replace(array('"',"'"),'',$_COOKIE['oauthtoken_'.$oauthclientauthid]));
    if(!(strpos($otuid,'_') !== false)) clearlogin();
	else {
		$otuid = explode('_',$otuid);
		if((!isset($otuid[3])) || (!is_numeric($otuid[3])) || ($otuid[3] != $oauthclientauthid)) clearlogin();
		else
			if((!is_numeric($otuid = str_replace('Awk','',$otuid[0]))) || ($otuid < 1)) clearlogin();
			else 
				if((($otuid[1] ?? '') != preg_replace('/[^0-9]/','',md5(filtereduseragent().'slockz'))) ||
            	   (pdo_query("update ".auth::$oauthdevices." set lastaccess='".strtotime('now')."', counter=counter + 1 where token='".$otcookie."' ") < 1))
						
				  	  auth::$user = @pdo_fetch_item(pdo_query("SELECT * FROM ".auth::$usertable." WHERE uid='".$otuid."' "));
	}
}


/* Return whether the user is logged in or not */
if(isset($_REQUEST['auth_isauthed'])) 
  json_exitcode(((isauthed()) ? 1 : 0),"isauthed",true,true);

}

  
/* forgot the password functions */
function forgotpassword($successmsg='1',$errormsg='0',$waitingmsg='2') {
  if(!isset($_REQUEST['forgot'])) $_REQUEST['forgot'] = getuser('uid');
	if(is_numeric($_REQUEST['forgot'])) $us = array('uid' => $_REQUEST['forgot']);
	  else $us = @pdo_fetch_item(pdo_query("select uid".((auth::$userconfig['authenticatefieldname'] != 'uid')?','.auth::$userconfig['authenticatefieldname']:'')." from ".auth::$usertable." where ".((is_numeric($_REQUEST['forgot'])) ? "uid='".$_REQUEST['forgot']."'" : auth::$userconfig['authenticatefieldname']."='".str_replace(array('"',"'",'%','&'),'',$_REQUEST['forgot'])."'") ));
	if((isset($_REQUEST['forgot'])) && (isset($_REQUEST['ftoken'])) && (is_numeric(@$_REQUEST['forgot'])))
		if($_REQUEST['ftoken'] == rethash(getuser('passw',$us['uid'])))
			if((isset($_POST['newpassw'])) && (isset($_POST['confirmpassw'])) && ((@$_POST['newpassw'] == @$_POST['confirmpassw']) && (trim(@$_POST['newpassw']) != '') && (strlen(trim($_POST['newpassw'])) > 2)))
				if(pdo_query("UPDATE ".auth::$usertable." SET passw='".hash('sha512',$_POST['newpassw'])."' WHERE uid='".$us['uid']."' ") > 0)
					return $successmsg;
				else return $errormsg;
			else return $waitingmsg;
		else return $errormsg;
	else
		return ((isset(auth::$userconfig['oauthremoteaddress'])) ? auth::$userconfig['oauthremoteaddress'].((strpos(auth::$userconfig['oauthremoteaddress'],'?') !== false) ? '':'?oauth&json=1') :
           ((empty($_SERVER['HTTPS']))?'http':'https').'://'.str_replace('//','/',($_SERVER['HTTP_HOST'] ?? 'localhost').'/'.($_SERVER['PHP_SELF'] ?? '')).'?').
           '&forgot='.$us['uid'].'&ftoken='.rethash(getuser('passw',$us['uid']));
} 
}
  

/* oauth stuff */
if($activateoauthserver)
  if(isset($_REQUEST['oauth'])) {
		if(!isset($_REQUEST['client_id']))
			$cli = array();
		else 
			if(is_numeric($_REQUEST['client_id']))
				$cli = @pdo_fetch_item(pdo_query("select * from ".auth::$oauthtable." where oid='".$_REQUEST['client_id']."' "));
    
    /* push notifications through oauthserver */
    if(isset($_REQUEST['pushnotification']))
      if($activatepushnotifications) {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        if(!isset($_REQUEST['client_secret'])) json_exitcode(-1);
        if(!isset($_REQUEST['client_id'])) json_exitcode(-2);
        if(!isset($_REQUEST['uid'])) json_exitcode(-3);
        if(!isset($_REQUEST['title'])) json_exitcode(-4);
        if(!is_numeric($_REQUEST['client_id'])) json_exitcode(-5);
        if($cli['secret'] != $_REQUEST['client_secret']) json_exitcode(-6);
        if(!isset($_REQUEST['postdata'])) $postdata = ''; else parse_str($_REQUEST['postdata'],$postdata);
        $uid = $_REQUEST['uid']; if(strpos($uid,',') !== false) $uid = explode(',',$uid);
        $mensagem = @$_REQUEST['message']; $preview = @$_REQUEST['preview']; $titulo = $_REQUEST['title'];
        if($mensagem == "") { $mensagem = $preview; if($preview == "") $mensagem = $titulo; }
        json_exitcode( ((sendGCM($uid,$titulo,$preview,$mensagem,$postdata)) ? 1 : 0) );
      } else json_exitcode(-7);

    /* rouge project functions */
    if((isset($_REQUEST['client_secret'])) && (isset($_REQUEST['client_id'])) && (!empty($cli))) {
      if(isset($_REQUEST['json'])) header('Access-Control-Allow-Origin: *');
      if($cli['secret'] == $_REQUEST['client_secret']) {

        if(isset($_REQUEST['newuser'])) {
    			if(isset($_REQUEST['json'])) header('Access-Control-Allow-Origin: *');
    			header('Content-Type: application/json');
			$squery = "INSERT INTO ".auth::$usertable." ("; $squery_size = strlen($squery); $extags = array();
			if(isset($_POST['login'])) { $_POST[$authenticatefieldname] = $_POST['login']; unset($_POST['login']); }
			if(isset($_POST['password'])) { $_POST['passw'] = $_POST['password']; unset($_POST['password']); }
			if(isset($_POST['passw'])) hash('sha512',$_POST['passw']); unset($_POST['uid']);
			if(isset($_POST[$authenticatefieldname]))
				if(@pdo_num_rows(pdo_query("SELECT ".$authenticatefieldname." FROM ".auth::$usertable." WHERE ".$authenticatefieldname."='".$_POST[$authenticatefieldname]."' ")) > 0) {
				echo json_encode(array('uid'=>'0')); exit; }
			foreach((array)$_POST as $fd => $v)
				if(in_array($fd,auth::$dbfields['users']))
				$squery .= (($squery_size < strlen($squery))?',':'').str_replace(' ','',strtolower($fd));
				else array_push($extags,$fd);
			$squery .= ",tags,token,registered) VALUES ("; $tagger = array();
			foreach((array)$extags as $k) $tagger[$k] = urlencode($_POST[$k]);
			$squery_size = strlen($squery);
			foreach((array)$_POST as $fd => $v)
				if(in_array($fd,auth::$dbfields['users']))
				$squery .= (($squery_size < strlen($squery))?',':'')."'".str_replace(' ','',strtolower($v))."'";
			if(pdo_query($squery.",'".json_encode($tagger)."','','".strtotime('now')."')") > 0)
				echo json_encode(array('uid'=>pdo_insert_id()));
			else echo json_encode(array('uid'=>'0'));
			exit; 
		}
        if(isset($_REQUEST['listusers'])) { 
		  $retlist = array();
		  if(isset($_REQUEST['json'])) header('Access-Control-Allow-Origin: *');
		  header('Content-Type: application/json');
          $vfields = (isset($_REQUEST['fields'])) ? @explode(',',$_REQUEST['fields']) : array();
          $lus = pdo_query("SELECT uid,username,email,tags,registered from ".auth::$usertable." ORDER BY username");
          if(@pdo_num_rows($lus) > 0)
            foreach(@pdo_fetch_array($lus) as $lu) {
              $tbtbase = (strlen($lu['tags']) > 1) ? @json_decode($lu['tags'],true) : array(); $tbt = array();
              foreach((array)$tbtbase as $tb => $tv) if(!(strpos($tb,'_') !== false)) if((empty($vfields)) || (in_array($tb,$vfields))) $tbt[$tb] = urldecode($tv);
              foreach((array)$tbtbase as $tb => $tv) if(strpos($tb,$cli['oid'].'_') !== false) if((empty($vfields)) || (in_array(str_replace($cli['oid'].'_','',$tb),$vfields))) $tbt[str_replace($cli['oid'].'_','',$tb)] = urldecode($tv);
              $lu = mergearrays(array('uid'=>$lu['uid'], 'username'=>$lu['username'], 'email'=>$lu['email'], 'registered'=>$lu['registered']),$tbt);
              if(isset($_REQUEST['search'])) {
                $arraydados = str_replace(array('"',"'",' ',':','=',"\\",'{','}','[',']'),'',strtolower(urldecode(json_encode($lu))));
                $arraysearch = str_replace(array('tags',' ',':','=','"','%',"\\"),'',strtolower(urldecode(@$_REQUEST['search'])));
                if(strpos($arraysearch,'registered') !== false) $arraysearch .= ',';
                if(strpos($arraysearch,'username') !== false) $arraysearch .= ',';
                if(strpos($arraysearch,'uid') !== false) $arraysearch .= ',';
              } else { $arraydados = array(); $arraysearch = array(); }
              if(!isset($_REQUEST['search'])) array_push($retlist,$lu); 
              else if(strpos($arraydados,$arraysearch) !== false) array_push($retlist,$lu);
            } echo json_encode($retlist);
          exit; 
		}
        if((isset($_POST['uid'])) && (is_numeric($_POST['uid']))) {
    	  if(isset($_REQUEST['json'])) header('Access-Control-Allow-Origin: *');
    	  header('Content-Type: application/json');
          $bora = @pdo_fetch_item(pdo_query("select * from ".auth::$usertable." where uid='".$_POST['uid']."' ")); 
          if(((int) $bora['uid']) < 1) json_exitcode(0); else { $qvai = array(); $bora['tags'] = @json_decode($bora['tags'],true);
          foreach((array)$bora as $ui => $v) if((!is_numeric($ui)) && ($ui != 'tags')) $qvai[$ui] = $v;
          foreach((array)$bora['tags'] as $ui => $v) if(!(strpos($ui,'_') !== false))  $qvai[$ui] = $v;
          foreach((array)$bora['tags'] as $ui => $v) if((strpos($ui,$cli['oid'].'_') !== false)) $qvai[str_replace($cli['oid'].'_','',$ui)] = $v;
          if(isset($_POST['setuser'])) {
            parse_str($_POST['setuser'],$sudata);
            if(!empty($sudata)) foreach((array)$sudata as $sd => $v) {
              $bora[$sd] = $v; $qvai[$sd] = $v;
              setuser($cli['oid'].'_'.$sd,$bora[$sd], $_POST['uid']); } } 
          echo json_encode($qvai); }
          exit; 
	    }
    } }
    
		if(isset($_REQUEST['access_token'])) {
			if(isset($_REQUEST['json'])) header('Access-Control-Allow-Origin: *');
			header('Content-Type: application/json');
			$k = explode("_",$_REQUEST['access_token']);
			$cod = str_ireplace('Awk','',$k[0]);
			if(isset($k[1])) $key = $k[1]; else $key = '';
			if(isset($k[2])) $tim = $k[2]; else $tim = '';
			if(isset($k[3])) $clit = $k[3]; else $clit = '';
			if((is_numeric($cod)) && (is_numeric($key)) && (is_numeric($clit))) {
        /* delete within an year */ pdo_query("delete from ".auth::$oauthdevices." where registered < ".strtotime('-1 year'));
				$bora = @pdo_fetch_item(pdo_query("select * from ".auth::$usertable." where uid='".$cod."' "));
        if(isset($bora['tags'])) if(strlen($bora['tags']) > 1) $bora['tags'] = @json_decode($bora['tags'],true);
          if($key == preg_replace('/[^0-9]/','',md5(filtereduseragent().'slockz')))
            if(pdo_query("update ".auth::$oauthdevices." set lastaccess='".strtotime('now')."',counter=counter + 1 where oid='".$clit."' and uid='".$cod."' and token='".$_REQUEST['access_token']."' ") > 0) {
							/* regular user get&post */ $qvai = array();
							  foreach((array)$bora as $ui => $v) if((!is_numeric($ui)) && ($ui != 'passw') && ($ui != 'tags')) $qvai[$ui] = $v;
								foreach((array)$bora['tags'] as $ui => $v) if(!(strpos($ui,'_') !== false)) if(($ui != 'accode')) $qvai[$ui] = $v;
								foreach((array)$bora['tags'] as $ui => $v) if((strpos($ui,$clit.'_') !== false)) $qvai[str_replace($clit.'_','',$ui)] = $v;
								if(isset($_POST['setuser'])) 
								 if((isset($_POST['uid'])) && (isset($_REQUEST['client_secret']))) {
									/* another user set value */
								  parse_str($_POST['setuser'],$sudata);
									if(!empty($sudata)) foreach((array)$sudata as $sd => $v) {
                    $chavesd = str_replace('@','',$sd);
                    $chavesdpre = ((strpos($sd,'@') !== false)? $chavesd : $clit.'_'.$chavesd);
                    $bora[$chavesd] = $v;
										setuser($chavesdpre,$v, ((is_numeric($_POST['uid']))?$_POST['uid'] : $cod) ); }
								 } else {
									/* logged user set */
									parse_str($_POST['setuser'],$sudata);
									if(!empty($sudata)) foreach((array)$sudata as $sd => $v) { 
                    $chavesd = str_replace('@','',$sd);
                    $chavesdpre = ((strpos($sd,'@') !== false)? $chavesd : $clit.'_'.$chavesd);
                    $bora[$chavesd] = $v; 
                    $qvai[$chavesd] = $v; 
                    if($v == '') unset($qvai[$chavesd]);
										setuser($chavesdpre,$v,$cod); } }
								echo json_encode($qvai);
							} else echo json_encode(array('uid'=>'0'));
            else echo json_encode(array('uid'=>'0'));
		    } else echo json_encode(array('uid'=>'0'));
			exit;
	  } else
		
		 if(@$_REQUEST['grant_type'] == 'access_token') {
			 if(isset($_REQUEST['json'])) header('Access-Control-Allow-Origin: *');
			 header('Content-Type: application/json');
       if(!isset($_REQUEST['code'])) json_exitcode('-1');
			 $cod = explode('_',$_REQUEST['code']);
			 if(is_numeric($cod[1]))
			   if(isset($_REQUEST['client_id'])) if(is_numeric($_REQUEST['client_id']))
					 if($cli['secret'] == @$_REQUEST['client_secret']) {
             $otoken = 'Awk'.getuser('uid',$cod[1]).'_'.preg_replace('/[^0-9]/','',md5(filtereduseragent().'slockz')).'_'.hash('sha512',uniqid()).'_'.$_REQUEST['client_id'];
             if(pdo_query("update ".auth::$oauthdevices." set token='".$otoken."', code='', lastaccess='".strtotime('now')."' where oid='".$_REQUEST['client_id']."' and uid='".$cod[1]."' and code='".$_REQUEST['code']."' ") > 0)
               echo json_encode(array('access_token'=>$otoken));
						 else echo json_encode(array('access_token'=>'0')); }
					 else echo json_encode(array('access_token'=>'0'));
				 else echo json_encode(array('access_token'=>'0'));
       else echo json_encode(array('access_token'=>'0'));
		 } else {

			$redirp = (isset($_REQUEST['redirect_uri'])) ? $_REQUEST['redirect_uri'] : $mainpageredirect;
			if(!(strpos(str_replace('//','',$redirp),'/') !== false)) $redirp .= '/';
			if(!(strpos($redirp,'?') !== false)) $redirp .= '?'; $segundaparte = explode('?',$redirp);
			if(!(strpos($segundaparte[1],'oauth') !== false)) $redirp = str_replace('?','?oauth&',$redirp);
			if(substr($redirp,-1,1) != "&") $redirp .= '&';

			if(@$_REQUEST['grant_type'] == 'authorization_code') {
        if(!function_exists('saveaccodeandprint')) { function saveaccodeandprint($wi) {
          $clientid = (isset($_REQUEST['client_id'])) ? $_REQUEST['client_id'] : ''; if($clientid == '') $clientid = '0';
          $oauthref = preg_replace('/[^0-9]/','',md5('slockz'.uniqid())); savecookie('oauthref',$oauthref);
          pdo_query("insert into ".auth::$oauthdevices." (oid,uid,device,code,auth,lastaccess,registered) ".
                    "values ('".$clientid."','".getuser('uid')."','".filtereduseragent()."','".$wi."','".$oauthref."','".strtotime('now')."','".strtotime('now')."')");
          return $wi; } }
				if(isset($_REQUEST['json'])) 
          json_exitcode(((isauthed())?saveaccodeandprint(hash('sha512',uniqid()).'_'.getuser('uid')):0),'code',true,true);
				else
          if((isauthed()) && (isset($_REQUEST['redirect_uri']))) {
            header('Location: '.$redirp.'code='.saveaccodeandprint(hash('sha512',uniqid()).'_'.getuser('uid'))); exit; }
          else
            if(isset($_POST['login']))
              if((!isauthed()) && (isset($_REQUEST['redirect_uri'])) && (!isset($_POST['local']))) {
                header('Location: '.$redirp.'error_code=1'); exit; }
              else
                if((isauthed()) && (!isset($_REQUEST['redirect_uri'])) && (isset($_POST['local']))) {
                  header('Location: ?oauth'); exit; }
                else
                  if((isauthed()) && (!isset($_REQUEST['redirect_uri'])) && (!isset($_POST['local']))) {
                    header('Location: '.$redirp.'code='.saveaccodeandprint(hash('sha512',uniqid()).'_'.getuser('uid'))); exit; }
                  else
                    if((!isauthed()) && (!isset($_REQUEST['redirect_uri'])) && (!isset($_POST['local']))) {
                      header('Location: '.$redirp.'error_code=1'); exit; }
			}

			if(isset($_POST['projname']))
				if(@$_REQUEST['application'] == 'editproject')
					if(pdo_query("UPDATE ".auth::$oauthtable." SET project='".preg_replace('/[^0-9a-zA-Z ]/','',$_POST['projname'])."' where oid='".$cli['oid']."' and owner='".getuser('uid')."' ") > 0) {
						echo '<script>alert("Project name changing was successfully"); window.location.href="?oauth&application=editproject&client_id='.$cli['oid'].'";</script>'; exit;
					} else
						echo '<script>alert("There was no change on project\'s name");</script>';
									
			if(isset($_REQUEST['flushkey']))
				if(@$_REQUEST['application'] == 'editproject')
					if(pdo_query("UPDATE ".auth::$oauthtable." SET secret='".preg_replace('/[^0-9a-zA-Z]/','',str_replace(array('-','=','+','3'),'A',base64_encode(md5(uniqid()))))."' where oid='".$cli['oid']."' and owner='".getuser('uid')."' ") > 0) {
						echo '<script>alert("Your secret key has been flushed. You might need to update your project\'s old key for the new one."); window.location.href="?oauth&application=editproject&client_id='.$cli['oid'].'";</script>'; exit;
					} else
						echo '<script>alert("No secret key was changed");</script>';

			if(isset($_REQUEST['delproject']))
					pdo_query("DELETE FROM ".auth::$oauthtable." WHERE oid='".$_REQUEST['delproject']."' and owner='".getuser('uid')."' ");

			$locale = str_replace('_','-',strtolower(((isset($_REQUEST['locale']))?$_REQUEST['locale']:'')));
			$locale = ($locale == 'pt-br') ? 'pt_BR' : 'en_US';
			$lg = array('formtitle' => 'Enter your login and password',
									'formlogin' => 'Login',
									'formlogid' => 'ID number',
									'formusern' => 'Username',
									'formemail' => 'Email address',
									'formpassw' => 'Password',
									'formsubmt' => 'Sign in',
									'formredir' => 'Redirecting...',
									'formaquit' => 'Logout',
									'formerror' => 'Invalid user login or password.');
			
			if($locale == 'pt_BR') {
							$lg['formtitle'] = 'Autentica&ccedil;&atilde;o';
							$lg['formlogid'] = 'N&uacute;mero de identifica&ccedil;&atilde;o';
							$lg['formusern'] = 'Nome de usu&aacute;rio';
							$lg['formemail'] = 'E-mail de acesso';
							$lg['formpassw'] = 'Senha';
							$lg['formsubmt'] = 'Entrar';
							$lg['formredir'] = 'Redirecionando...';
							$lg['formerror'] = (($authenticatefieldname == 'email')?$lg['formemail']:(($authenticatefieldname == 'username')?$lg['formusern']:(($authenticatefieldname == 'uid')?$lg['formlogid']:$lg['formlogin']))).' ou senha inv&aacute;lidos.'; }

			$colorprint = (isset($_REQUEST['color'])) ? ((is_numeric(str_replace(array('#','a','b','c','d','e','f',urlencode('#')),'',strtolower($_REQUEST['color'])))) ? strtolower($_REQUEST['color']) : 'e6e6e6') : 'e6e6e6;';
			if(!((strlen($colorprint) >= 3) && (strlen($colorprint) <= 6))) $colorprint = '#e6e6e6';
			else if(trim(str_replace(array('#','a','b','c','d','e','f','0','1','2','3','4','5','6','7','8','9',urlencode('#')),'',$colorprint)) == '')
				$colorprint = '#'.$colorprint; else $colorprint = '#e6e6e6';

			if(isset($_REQUEST['application']))
			echo str_replace(array("  ","\n",chr(13),chr(9)),' ','<!DOCTYPE html>
			<html lang="br"><head><meta charset="utf-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title>OAuth</title>
			<link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet">
			<style> body { font-family: Lato; } a { color:#333; } h1,h2,h3,h4 { margin-top:0px; }
					.btn { background-color:'.$colorprint.'; color:'.getcontrastcolor($colorprint).';
							display: inline-block; font-weight: 400; white-space: nowrap;
							width:100%; margin:auto; text-align: center; vertical-align: middle;
							-webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none;
							border: 1px solid silver; padding: .375rem .75rem; font-size: 1rem; line-height: 1.5; border-radius: .25rem;
							transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out; }
					.form { display: inline-block; padding: .375rem 0px; font-size: 1rem; line-height: 1.5;
							width:100%; color: #495057; background-color: #fff; text-align: center; margin:auto;
							background-clip: padding-box; border: 1px solid #ced4da; border-radius: .25rem;
							transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out; }
					.alert { left:0px; right:0px; padding: .75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: .25rem; }
					.alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
					.alert-warning { color: #856404; background-color: #fff3cd; border-color: #ffeeba; }
					@media only screen and (min-width: 769px) { .container { width:60%; } .espacamento { width:500px; margin:auto; margin-top:16px; } .centralizy { text-align:left; } }
					@media only screen and (max-width: 769px) { .container { width:90%; } .espacamento { left:0px; right:0px; margin:16px 0px 0px 0px; } .centralizy { text-align:center; padding-top:20px; } .d-ng { display:none; } } </style></head>
			<body style="background-color:#f0f0f0;color:#333;text-align:center;">
			<div class="espacamento" style="background-color:#fff;padding:16px 16px 16px 16px;min-height:50px;border:1px solid silver;text-align:left;"> ');

			if((isset($_POST['oldpass'])) && (isset($_POST['newpass'])) && (isset($_POST['confirmpass'])))
				if((trim($_POST['oldpass']) != "") && (trim($_POST['newpass']) != "") && (trim($_POST['confirmpass']) != ""))
					if($_POST['newpass'] == $_POST['confirmpass'])
						if(hash('sha512',$_POST['oldpass']) == getuser('passw'))
							if(pdo_query("UPDATE ".auth::$usertable." SET passw='".hash('sha512',$_POST['newpass'])."' where uid='".getuser('uid')."' ") > 0)
								echo '<div class="alert alert-success">Password changed successfully.</div>';
							else echo '<div class="alert alert-warning">The password was not changed.</div>';
						else echo '<div class="alert alert-warning">Current password is invalid.</div>';
					else echo '<div class="alert alert-warning">Password does not match.</div>';
				else echo '<div class="alert alert-warning">Fill all fields correctly.</div>';

			if(@$_REQUEST['grant_type'] == 'authorization_code') 
				echo '<center><div style="'.(($colorprint == '#e6e6e6') ? 'border:1px solid #aaa' : 'border:2px solid '.$colorprint ).';padding:10px 12px;background-color:#f0f0f0;display:inline-block;-webkit-border-radius: 50%;-moz-border-radius: 50%;border-radius: 50%;">'.
						 '<svg style="color:#777;width:40px;height:40px;" aria-hidden="true" data-prefix="fas" data-icon="user-lock" class="svg-inline--fa fa-user-lock fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M320 320c0-11.1 3.1-21.4 8.1-30.5-4.8-.5-9.5-1.5-14.5-1.5h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h280.9c-5.5-9.5-8.9-20.3-8.9-32V320zm-96-64c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm384 32h-32v-48c0-44.2-35.8-80-80-80s-80 35.8-80 80v48h-32c-17.7 0-32 14.3-32 32v160c0 17.7 14.3 32 32 32h224c17.7 0 32-14.3 32-32V320c0-17.7-14.3-32-32-32zm-80 0h-64v-48c0-17.6 14.4-32 32-32s32 14.4 32 32v48z"></path></svg>'.
						 '</div><br style="clear:both;"><br><div class="container">'.
						 ((!isauthed()) ? '<div style="overflow:visible;border-bottom:2px solid silver;height:10px;">'.
						 '<div style="padding:1px 10px;background-color:#fff;font-weight:bold;display:inline-block;">'.$lg['formtitle'].'</div></div>'.
						 '<br style="clear:both;"><br><form method="post">'.
						 '<input type="text" name="login" placeholder="'.(($authenticatefieldname == 'email')?$lg['formemail']:(($authenticatefieldname == 'username')?$lg['formusern']:(($authenticatefieldname == 'uid')?$lg['formlogid']:$lg['formlogin']))).'" class="form" value="'.@$_POST['login'].'" style="display:block;margin-bottom:24px;">'.
						 '<input type="password" name="password" placeholder="'.$lg['formpassw'].'" class="form" style="display:block;margin-bottom:24px;">'.
						 ((isset($_POST['login'])) ? '<font style="color:red;display:block;text-align:center;margin-bottom:24px;">'.$lg['formerror'].'</font>' : '').
						 '<input type="submit" name="local" value="'.$lg['formsubmt'].'" class="btn" style="margin-top:8px;display:block;margin-bottom:24px;">'.
						 '</form>' : '<br style="clear:both;"><a href="?'.($_SERVER['QUERY_STRING'] ?? '').'&logout=1">'.
						 '<button class="btn" style="width:50%;">'.$lg['formaquit'].'</button></a>').'</div>';
			else
			 if(@$_REQUEST['grant_type'] == 'developer_access')
				 if(!isset($_POST['logindev']))
						 echo '<h2>Sign up developer program</h2><br><form method="post">'.
						      '<center><div class="container">Enter your login and password to request developer access:<br><br>'.
									'<input type="text" placeholder="Login" name="login" class="form"><br><br>'.
									'<input type="password" placeholder="Password" name="password" class="form"><br><br>'.
									'<input type="submit" value="Sign up" name="logindev" class="btn">'.
									'</div><br><br><a href="?oauth">Go back</a></center></form><br>';
					else
						if(isauthed())
							if(getuser('isdeveloper') == '1')
								echo '<div class="alert alert-warning">This account is already on the developers program</div><br><a href="?oauth">Go back</a>';
							else
								echo '<!-- '.setuser('isdeveloper','2').' --><div class="alert alert-success">Your request has been sent. You are not going be notificated unless the adminsitrator decides to. So keep up coming back to see if your request has been approved, and good luck.</div>';
						else
							echo '<div class="alert alert-warning">'.$lg['formerror'].'</div><br><a href="?oauth">Go back</a>';
			 else
				if(getuser('isdeveloper') != '1') /* not a developer */
					echo '<div class="alert alert-warning">Sign in with a developer account in order to continue.</div>'.
							 '<br style="clear:both;"><br><center><div class="container">'.
							 '<a href="?oauth&grant_type=developer_access" style="text-decoration:none;"><button class="btn" style="background-color:#d6d6d6;color:#333;">Sign up</button></a><br><br>'.
							 '<a href="?oauth&grant_type=authorization_code" style="text-decoration:none;"><button class="btn">'.$lg['formsubmt'].'</button></a>'.
							 '<br style="clear:both;"><br></div></center>';
				else
				 if(@$_REQUEST['application'] == 'createproject') /* create projects */
						echo '<h2>Create new project</h2><br style="clear:both;"><form method="post" action="?oauth">Project name:<br><br>'.
							'<input type="text" name="newprojname" class="form" style="width:200px;" placeholder="New project"><br><br><br>'.
							'<input type="submit" value="Create" class="btn" style="width:100px;">'.
							'&nbsp;&nbsp;&nbsp;<a href="?oauth">Go back</a></form>';
				 else
					if(@$_REQUEST['application'] == 'editproject') /* edit projects */
					  echo '<div style="float:right;margin-right:20px;"><a onclick="if(!confirm(\'Are you sure you want to delete this project?\')) return false;" href="?oauth&delproject='.$cli['oid'].'" class="btn" style="text-decoration:none;">Delete</a></div>'.
								 '<h2>Project data</h2><form method="post" style="margin-right:-10px;"><table border="0" style="width:100%;">'.
								 '<tr><td style="width:200px;white-space:nowrap;">Project name:&nbsp;&nbsp;</td><td style="width:100%;"><input type="text" name="projname" value="'.$cli['project'].'" class="form" style="font-size:14px;"></td><td style="text-align:right;"><input type="submit" value="Change" class="btn" style="margin-left:5px;width:100px;"></td></tr>'.
								 '<tr><td>Client ID:&nbsp;&nbsp;</td><td><input type="text" class="form" value="'.$cli['oid'].'" style="font-size:14px;color:gray;" readonly></td><td></td></tr>'.
								 '<tr><td>Client Secret:&nbsp;&nbsp;</td><td><input type="text" value="'.$cli['secret'].'" class="form" style="font-size:14px;color:gray;" readonly></td><td style="text-align:right;"><a href="?oauth&application=editproject&client_id='.$cli['oid'].'&flushkey=1"><input type="button" class="btn" value="Flush" style="margin-left:5px;width:100px;"></a></td></tr>'.
								 '</table></form><br style="clear:both;"><br><a href="?oauth">Go back</a>';
					else
					 if(@$_REQUEST['application'] == 'newuser')
						 if(isset($_POST['username']))
							 if((isset($_POST['newpass'])) && (isset($_POST['confirmpass'])))
								 if($_POST['newpass'] == $_POST['confirmpass'])
									if(!((empty($_POST['username'])) && (empty($_POST['email'])) && (empty($_POST['newpass'])) && (empty($_POST['confirmpass']))))
										if(!(@pdo_num_rows(pdo_query("SELECT ".$authenticatefieldname." FROM ".auth::$usertable." WHERE ".$authenticatefieldname."='".$_POST[$authenticatefieldname]."' ")) > 0))
											if(pdo_query("INSERT INTO ".auth::$usertable." (username,email,passw,tags,token,registered) VALUES ('".preg_replace('/[^0-9a-z]/','',strtolower(@$_POST['username']))."','".str_replace(' ','',strtolower(@$_POST['email']))."','".hash('sha512',@$_POST['newpass'])."','".json_encode(array('createdby'=>getuser('uid')))."','','".strtotime('now')."') ") > 0)
											 echo '<div class="alert alert-success">New user has been created.</div>'.
														'<font style="font-size:10px;"><b>result: </b>curl <b>-></b> <i>'.((empty($_SERVER['HTTPS'] ?? null))?'http':'https').'://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].
														'?oauth&accesstoken=...&setuser</i> <b>POST</b> '.
														'<i>username='.urlencode($_POST['username']).'&email='.urlencode($_POST['email']).'&passw='.urlencode($_POST['newpass']).'&token='.strtotime('now').'& registered='.strtotime('now').'</i>'.
														'</font><br style="clear:both;"><br><a href="?oauth&application=newuser">Go back</a>';
											else echo '<div class="alert alert-warning">Error creating a new user.</div><br style="clear:both;"><a href="?oauth&application=newuser">Go back</a>';
										else echo '<div class="alert alert-warning">User already exists.</div><br style="clear:both;"><a href="?oauth&application=newuser">Go back</a>';	
									else echo '<div class="alert alert-warning">Missing fields.</div><br style="clear:both;"><a href="?oauth&application=newuser">Go back</a>';
								 else echo '<div class="alert alert-warning">Passwords do not match.</div><br style="clear:both;"><a href="?oauth&application=newuser">Go back</a>';
							 else echo '<div class="alert alert-warning">Missing fields.</div><br style="clear:both;"><a href="?oauth&application=newuser">Go back</a>';
						 else
							echo '<h2>Create new user</h2><form method="post"><table border="0">'.
									 '<tr><td>Username:&nbsp;</td><td><input type="text" name="username" class="form"></td></tr>'.
							     '<tr><td>Email address:&nbsp;</td><td><input type="text" name="email" class="form"></td></tr>'.
									 '<tr><td><br>Password:&nbsp;</td><td><br><input type="password" name="newpass" class="form"></td></tr>'.
							     '<tr><td>Confirm password:&nbsp;</td><td><input type="password" name="confirmpass" class="form"></td></tr>'.
							     '<tr><td></td><td><br><input type="submit" value="Submit" class="btn"></td></tr>'.
							     '</table></form><br style="clear:both;"><a href="?oauth">Go back</a>';
					 else
						 if(@$_REQUEST['application'] == 'profile')
					 		 echo '<div style="float:right;margin-right:20px;"><a onclick="if(!confirm(\'Are you sure you want to delete this user?\')) return false;" href="?oauth&deluser='.getuser('uid').'" class="btn" style="text-decoration:none;">Delete</a></div>'.
											'<h2>User information</h2><br>ID: '.getuser('uid').'<br><br>'.
											'Username: '.getuser('username').'<br>'.
											'Email: '.getuser('email').'<br><br>'.
											'Last login: '.date('d/m/Y H:i:s',getuser('token')).'<br>'.
											'Created on: '.date('d/m/Y H:i:s',getuser('registered')).'<br>'.
											'<br style="clear:both;"><br><a href="?oauth">Go back</a>';
						 else
						 if(@$_REQUEST['application'] == 'changepass')
					 		 echo '<h2>Change password</h2><form method="post"><table border="0">'.
										'<tr><td>Old password:&nbsp;</td><td><input type="password" name="oldpass" class="form"></td></tr>'.
										'<tr><td>New password:&nbsp;</td><td><input type="password" name="newpass" class="form"></td></tr>'.
										'<tr><td>Confirm password:&nbsp;</td><td><input type="password" name="confirmpass" class="form"></td></tr>'.
										'<tr><td></td><td><br><input type="submit" value="Change password" class="btn"></td></tr>'.
										'</table></form><br style="clear:both;"><a href="?oauth">Go back</a>';
						 else	
						 if(@$_REQUEST['application'] == 'projects') { /* list projects */
								if(isset($_POST['newprojname']))
									pdo_query("INSERT INTO ".auth::$oauthtable." (project,secret,owner,created) VALUES ('".$_POST['newprojname']."','".preg_replace('/[^0-9a-zA-Z]/','',str_replace(array('-','=','+','3'),'A',base64_encode(md5(uniqid()))))."','".getuser('uid')."','".strtotime('now')."') ");
								
								echo '<div style="float:right;margin-right:20px;text-align:right;">'.
								'<a href="?oauth&logout=1" class="btn" style="text-decoration:none;width:auto;">Logout</a></div>'.
								'<h2>Projects</h2><div class="centralizy">'.
								'<style>.projectcard { display:inline-block; vertical-align:top; width:150px; height:120px; margin:20px; padding:10px 16px 0px 16px; border:1px solid silver; text-align:left; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; font-size:12px; background-color:#fefefe; } '.
								'.projectcard h4 { font-size:16px; } </style>';
								$projs = pdo_query("select * from ".auth::$oauthtable." "); /* where owner='".getuser('uid')."' "); */
								foreach((array)pdo_fetch_array($projs) as $item) echo '<a href="?oauth&application=editproject&client_id='.$item['oid'].'">'.
									'<div class="projectcard"><h4>'.$item['project'].'</h4>Client_id: '.$item['oid'].'<br>'.
									'<span style="font-size:12px;color:#a6a6a6;">'.date('d/m/Y',$item['created']).'<br>at '.date('H:i',$item['created']).'</span></div></a>';
								echo '<a href="?oauth&application=createproject"><div class="projectcard">'.
										 '<h4 style="font-size:15px;">Create a new project</h4>OAuth login<br><div style="float:right;margin-top:35px;">'.
									   '<span style="border:1px solid #999;padding:1px 5px;font-size:14px;font-weight:bold;-webkit-border-radius: 50%; -moz-border-radius: 50%; border-radius: 50%;color:#fff;background-color:#a6a6a6">+</span>'.
									   '</div></div></a>';
								echo '<div class="projectcard"><h4 style="margin-bottom:15px;">Configuration</h4>'.
								     '<a href="?oauth&application=profile" style="color:blue;text-decoration:underline;font-size:12px;">Profile</a><div style="margin-top:5px;"></div>'.
										 '<a href="?oauth&application=changepass" style="color:blue;text-decoration:underline;font-size:12px;">Change password</a><div style="margin-top:5px;"></div>'.
										 '<a href="?oauth&application=newuser" style="color:blue;text-decoration:underline;font-size:12px;">Create new user</a>'.
										 '<br><div style="float:right;margin-top:2px;">'.
								     '<a href="?oauth&application=profile" style="text-decoration:none;"><span style="border:1px solid #999;padding:1px 5px;font-size:14px;font-weight:bold;-webkit-border-radius: 50%; -moz-border-radius: 50%; border-radius: 50%;color:#fff;background-color:#a6a6a6">=</span></a>'.
								     '</div></div></div>';

								if(isset($_REQUEST['grantdev']))
									if(is_numeric($_REQUEST['grantdev']))
										pdo_query("UPDATE ".auth::$usertable." SET tags='".str_replace('"isdeveloper":"'.getuser('isdeveloper',$_REQUEST['grantdev']).'"','"isdeveloper":"1"',getuser('tags',$_REQUEST['grantdev']))."' where uid='".$_REQUEST['grantdev']."' ");

								if(isset($_REQUEST['revokedev']))
									if(is_numeric($_REQUEST['revokedev']))
										pdo_query("UPDATE ".auth::$usertable." SET tags='".str_replace('"isdeveloper":"'.getuser('isdeveloper',$_REQUEST['revokedev']).'"','"isdeveloper":""',getuser('tags',$_REQUEST['revokedev']))."' where uid='".$_REQUEST['revokedev']."' ");
										
								$devs = pdo_query("select * from ".auth::$usertable." where tags like '%\"isdeveloper\"%' and uid not like '".getuser('uid')."' ");
								if(pdo_num_rows($devs) > 0) {
									echo '<br><h2>More developers</h2><br><table border="0" style="width:100%;">';
									foreach((array)pdo_fetch_array($devs) as $dev)
										echo '<tr><td>'.$dev['username'].'</td><td class="d-ng" style="text-align:center;">'.$dev['email'].'</td><td style="text-align:right;"><a class="btn" style="text-decoration:none;width:50px;" '.((strpos($dev['tags'],'"isdeveloper":"1"') !== false) ? 'href="?oauth&revokedev='.$dev['uid'].'">Revoke' : 'href="?oauth&grantdev='.$dev['uid'].'">Grant').'</a></td>';
									echo '</table>';
								}
								echo '</div><br style="clear:both;"><br>';
							}
			echo '</div></body></html>';
		}
	}

if($activateoauthclient) {

  function oauthapi($query,$default='',$returntype=false,$accesstoken='') {
    $method = 'GET'; 
    if(strpos($query,'ACT/') !== false) $method = 'ACT';
    if(strpos($query,'POST/') !== false) $method = 'POST';
    $query = str_replace(array('GET/','POST/','ACT/'),'',$query);

    $serverurl = auth::$userconfig['oauthremoteaddress'];
    if(!(strpos($serverurl,'?') !== false)) $serverurl .= '?&'; 
    else $serverurl = str_replace('?','?&',$serverurl); $segparte = explode('?',$serverurl);                                                                     
    if(!(strpos($segparte[1],'oauth') !== false)) $serverurl = str_replace('?','?oauth',$serverurl);
    if(substr($serverurl,-1,1) != "&") $serverurl .= '&';

    $clientid = auth::$userconfig['oauthclientauthid'];
    $clientsk = auth::$userconfig['oauthclientsecret'];
    
    if(strpos($query,"newuser/") !== false) return @json_decode(curlsend($serverurl.'client_id='.$clientid.'&client_secret='.$clientsk.'&newuser=1', str_replace('newuser/','',$query) ),$returntype); 

    if(strpos($query,"listusers/") !== false) { $query = str_replace('listusers/','',$query); $pei = explode('/',$query); $ppostdata = array();
      if(trim($pei[0]) != '') $ppostdata['search'] = $pei[0];
      if(isset($pei[1])) if(trim($pei[1]) != '') $ppostdata['fields'] = $pei[1];
      return @json_decode(curlsend($serverurl.'client_id='.$clientid.'&client_secret='.$clientsk.'&listusers=1', $ppostdata),$returntype); }

    if(strpos($query,"sendgcm/") !== false) { $query = str_replace('sendgcm/','',$query); $pei = explode('/',$query); $ppostdata = array();
      if(trim($pei[0]) != '') $ppostdata['uid'] = str_replace('@@@','/',$pei[0]); else return false;
      if(isset($pei[1])) if(trim($pei[1]) != '') $ppostdata['title'] = str_replace('@@@','/',$pei[1]);
      if(isset($pei[2])) if(trim($pei[2]) != '') $ppostdata['preview'] = str_replace('@@@','/',$pei[2]);
      if(isset($pei[3])) if(trim($pei[3]) != '') $ppostdata['message'] = str_replace('@@@','/',$pei[3]);
      $result = @json_decode(curlsend($serverurl.'client_id='.$clientid.'&client_secret='.$clientsk.'&pushnotification=1', $ppostdata),true);
      if(!isset($result['result'])) return false;
      else return ($result['result'] > 0) ? true : false; }
    
    if($query == 'loginurl') { $redirect_uri = ((!empty($_SERVER['HTTPS'] ?? null))?'https':'http').'://'.($_SERVER['SERVER_NAME'] ?? 'localhost').($_SERVER['PHP_SELF'] ?? '').'?'.($_SERVER['QUERY_STRING'] ?? '');
      if($default != '') if(strpos($default,'http') !== false) $redirect_uri = $default; else
       $redirect_uri = ((!empty($_SERVER['HTTPS'] ?? null))?'https':'http').'://'.($_SERVER['SERVER_NAME'] ?? 'localhost').'/'.$default;
      return $serverurl.'client_id='.$clientid.'&grant_type=authorization_code&redirect_uri='.urlencode($redirect_uri); }

    if($query == 'logouturl') { $redirect_uri = ((!empty($_SERVER['HTTPS'] ?? null))?'https':'http').'://'.($_SERVER['SERVER_NAME'] ?? 'localhost').($_SERVER['PHP_SELF'] ?? '').'?'.($_SERVER['QUERY_STRING'] ?? '');
      if($default != '') if(strpos($default,'http') !== false) $redirect_uri = $default; else
       $redirect_uri = ((!empty($_SERVER['HTTPS'] ?? null))?'https':'http').'://'.($_SERVER['SERVER_NAME'] ?? 'localhost').'/'.$default;
       if(!(strpos($redirect_uri,'?') !== false)) $redirect_uri .= '?&'; else $redirect_uri = str_replace('?','?&',$redirect_uri);
       if(substr($redirect_uri,-1,1) != "&") $redirect_uri .= '&';
       if(!(strpos($redirect_uri,'&logout') !== false)) $redirect_uri .= 'logout=1';
      return $serverurl.'logout=1&redirect_uri='.urlencode($redirect_uri); }
    
    if($query == 'cleardata') { delcookie('oauthtoken_'.auth::$userconfig['oauthclientauthid']); return ''; }
    
    if(isset($_COOKIE['oauthtoken_'.auth::$userconfig['oauthclientauthid']]))
      if($accesstoken == '') $accesstoken = $_COOKIE['oauthtoken_'.auth::$userconfig['oauthclientauthid']];

    if($query == 'accesstoken') return $accesstoken;
    
    if($query == 'sync') if($accesstoken != "") return @json_decode(curlsend($serverurl.'access_token='.$accesstoken ),true); else return array('uid' => '0');
    
    if($method == 'ACT') return @json_decode(curlsend($serverurl.'grant_type=access_token&client_id='.$clientid.'&client_secret='.$clientsk.'&code='.$query),true);
                
    if($accesstoken == '')
      if((isset($_REQUEST['oauth'])) && (isset($_REQUEST['code']))) {
        $atv = oauthapi('ACT/'.$_REQUEST['code']);
        if((isset($atv['access_token'])) && ($atv['access_token'] != "0"))
          savecookie('oauthtoken_'.auth::$userconfig['oauthclientauthid'], $atv['access_token']); else oauthapi('cleardata');
        $pars = array(); foreach((array)$_GET as $gt => $v)
          if(($gt != 'oauth') && ($gt != 'code') && ($gt != 'error_code') && ($gt != 'access_token') && ($gt != 'logout'))
            $pars[$gt] = $v; if(!headers_sent()) { header('Location: ?'.http_build_query($pars)); exit; } }
    
    if($query == 'isauthed') if($accesstoken == '') return false; else return (((int) oauthapi('GET/uid','',true)) < 1) ? false : true;

    if($method == "POST") {
      if(strpos($query,'/') !== false) { $query = explode('/',$query); $uid = $query[1]; $query = $query[0]; } else $uid = '';
      $postdata = array('setuser'=> $query.'='.urlencode($default));
      if($uid != '') $postdata['uid'] = $uid;
      $dopost = @json_decode(curlsend($serverurl.'access_token='.$accesstoken.(($uid != '')?'&client_secret='.$clientsk:''),$postdata ),true);
      if(!isset($dopost['uid'])) return oauthapi('cleardata'); else if($dopost['uid'] < 1) return oauthapi('cleardata');
      else { if($default == '') unset($dopost[$query]); else $dopost[$query] = $default; auth::$oauthudata = $dopost; return $default; }
    } else
        if($accesstoken == '') return '';
        else {
          if(empty(auth::$oauthudata)) auth::$oauthudata = oauthapi('sync'); $resp = auth::$oauthudata;
          if(!isset(auth::$oauthudata['uid'])) $resp = array('uid'=>((int) oauthapi('cleardata')));
          if(((int) auth::$oauthudata['uid']) < 1) $resp = array('uid'=>((int) oauthapi('cleardata')));
          if($query == "")
            return ($returntype) ? $resp : ((object) $resp);
          else
            return (!isset($resp[$query])) ? '' : $resp[$query];
        }
	}

	function getuser($key='',$default='') { return oauthapi('GET/'.$key,$default,true); }

	function setuser($key,$value='',$for='') { return oauthapi('POST/'.str_replace('/','',$key).'/'.str_replace('/','',$for),$value,true); }
  
	function sendgcm($uid,$title,$prev='',$msg='') { return oauthapi('sendgcm/'.str_replace('/','@@@',$uid).'/'.str_replace('/','@@@',$title).'/'.str_replace('/','@@@',$prev).'/'.str_replace('/','@@@',$msg),'',true); }
  
	function listusers($filter='',$fields='') { return oauthapi('listusers/'.str_replace('/','',$filter).'/'.str_replace('/','',$fields),array(),true); }
  
	function newuser($mixedarray) { return oauthapi('newuser/'.http_build_query($mixedarray),'',true); }

	function isauthed() { return oauthapi('isauthed'); }
}

?>