<?php
/* database configuration variables (dont change it here) */
if(!defined('PDC_DBHOST')) define('PDC_DBHOST', ($dbhost = ((isset($dbhost)) ? $dbhost : "localhost")));
if(!defined('PDC_DBPORT')) define('PDC_DBPORT', ($dbport = ((isset($dbport)) ? $dbport : "3306")));
if(!defined('PDC_DBUSER')) define('PDC_DBUSER', ($dbuser = ((isset($dbuser)) ? $dbuser : "")));
if(!defined('PDC_DBPASS')) define('PDC_DBPASS', ($dbpass = ((isset($dbpass)) ? $dbpass : "")));
if(!defined('PDC_DBBASE')) define('PDC_DBBASE', ($dbbase = ((isset($dbbase)) ? $dbbase : $projectname))); /* name of the database (required) */
if(!defined('PDC_DBDIER')) define('PDC_DBDIER', ($dbdier = ((isset($dbdier)) ? $dbdier : false))); /* script dies in case of db returns error */

/* Basic usage */
/*
    $var = pdo_query("select from ...");
    $var = pdo_fetch_array($var);
    foreach($var as &$item)
      $value = $item['key'];

    pdo_query("insert into ...");

	// You can also connect to a different database by invoking the class
	$db = new pdoclass("database"[,"$dbhost:$dbport",$dbuser,$dbpass]);
	$db::pdo_query("insert into ...");

*/

/* PDO database functions */
if(!class_exists('pdoclass')) {
	
	class pdoclass { 
		public static $dbstring = 'new';
		public static $db = null; 
		public static $dbconn = array(); 
		public static $con = false;
		
		public function __construct($dbbase='', $conectionstr=null, $dbuser=null, $dbpass=null) {
			if($conectionstr === null) $conectionstr = (PDC_DBHOST ?? 'mysql').':'.(PDC_DBPORT ?? '3306');
			if($dbuser === null) $dbuser = (PDC_DBUSER ?? 'root');
			if($dbpass === null) $dbpass = (PDC_DBPASS ?? '');
			self::$dbstring = $dbstring = preg_replace('/[^0-9a-z]/','',"pdo".microtime().uniqid());
			return pdo_connect($conectionstr,$dbuser,$dbpass,$dbbase,$dbstring);
		} 

		public function __callStatic($name, $arguments) {
			if(!function_exists($name)) return [];
			$arguments[] = self::$dbstring;
			return call_user_func_array($name, $arguments);
		}

		public function database() {
			pdo_query("CREATE TABLE IF NOT EXISTS log_query (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				query longtext NULL DEFAULT NULL,
				parameters longtext NULL DEFAULT NULL,
				response longtext NULL DEFAULT NULL,
				runat int NULL,
				PRIMARY KEY (id))");
		}		
	}
}

if(!function_exists('pdo_connect')) {

	function pdo_autoconfig($dir = null, $file = 'database.php') {
		if($dir === null) $dir = __DIR__;
		if(($_SERVER[$on = 'autocreatedbtables_'.md5("$dir/$file")] ?? '') == '1') return; else $_SERVER[$on] = '1';
		if(function_exists('autocreatedbtables')) return autocreatedbtables();
		for($i=0;$i<5;$i++)
			if(file_exists("$dir/$file")) return @include_once("$dir/$file");
			else $dir = realpath("$dir/../"); 
	}

	function pdo_connect($conectionstr,$dbuser,$dbpass,$dbbase='',$cname="default",$defresult=false) {
		$conectionstr = 'mysql:host='.$conectionstr.((!empty($dbbase)) ? ';dbname='.$dbbase : '');
		if($cname == "default") { 
			try {
				pdoclass::$db = new PDO($conectionstr , $dbuser, $dbpass);
				pdoclass::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
				$defresult = (pdoclass::$con = true);
			} catch (PDOException $e) {
				if(@$_GET['debug'] == "2") echo "<!-- Error: " . $e->getMessage() . " -->";
				$defresult = (pdoclass::$con = false); } 
		} else { 
			try {
				if((isset(pdoclass::$dbconn[$cname])) && (@pdoclass::$dbconn[$cname] != null)) return false;
				pdoclass::$dbconn[$cname] = null;
				pdoclass::$dbconn[$cname] = new PDO($conectionstr, $dbuser, $dbpass);
				pdoclass::$dbconn[$cname]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
				$defresult = true;
			} catch (PDOException $e) { 
				$defresult = false; 
		} } 
		if(!$defresult) pdo_autoconfig();
		return $defresult; }


	function pdo_log($statm, $eo = null, $save = false) { 
	  if(!((isset(pdoclass::$db)) && (pdoclass::$con) && (@pdoclass::$db != null))) return false;
	  if(!(($save ?? false) || ($statm['l'] ?? false))) return $eo;
	  try { $e = $eo; if(is_array($e) || is_object($e)) $e = json_encode($e);
		if(($clear = @pdoclass::$db->prepare("select count(*) qtd from log_query"))->execute()) {
			$qtd = intval($clear->fetchAll()[0]['qtd'] ?? -1);
			if($qtd < 0) return;
			else if($qtd > 1000)
					@pdoclass::$db->prepare("delete from log_query order by id asc limit 100")->execute(); }
		if((!empty($statm['s'] ?? '')) && (($statm['c'] ?? '') == 'default'))
		  if(!(strpos(preg_replace('/[^a-z]/','',strtolower(explode(' ',trim($statm['s']))[0] ?? '')),'set') !== false))
				@pdoclass::$db->prepare("INSERT INTO log_query (query, parameters, response, runat) VALUES (:q, :p, :r, :t)")
						  	  ->execute(['q'=>($statm['s'] ?? null), 'p'=>json_encode($statm['v'] ?? []), 
									     'r'=>($statm['d'] ?? ($e ?? null)), 't'=>strtotime('now')]);
		} catch (PDOException $e) { } 
		return $eo; }


	function pdo_insert_id($cname="default") { 
	if(($cname == "default") && (!pdoclass::$con)) return null;
	if(($cname != "default") && (!(isset(pdoclass::$dbconn[$cname])) && (@pdoclass::$dbconn[$cname] != null))) return null;
	$pdodb = ($cname == "default") ? pdoclass::$db : pdoclass::$dbconn[$cname];
	return $pdodb->lastInsertId(); }


	function pdo_num_rows($statm) {
		if($statm == null) return 0;
		if(!isset($statm['q'])) return 0;
		try { 
		  @$statm['q']->execute($statm['v'] ?? []);
		  return pdo_log($statm, @$statm['q']->rowCount());
		} catch (PDOException $e) { 
		  pdo_log($statm, $e, true);
		  return 0; } }


	function pdo_fetch_array($statm) {
	  if($statm == null) return array();
	  if(!isset($statm['q'])) return array();
	  try { 
	    @$statm['q']->execute($statm['v'] ?? []);
		return pdo_log($statm, $statm['q']->fetchAll());
	  } catch (PDOException $e) { 
		pdo_log($statm, $e, true);
		return array(); } }


	function pdo_query($select, $vname=[], $cname="default", $log=false) {
		if(($cname == 'default') && (is_string($vname))) { $cname = $vname; $vname = []; }
		if(($cname == "default") && (!pdoclass::$con)) return null;
		if(($cname != "default") && (!(isset(pdoclass::$dbconn[$cname])) && (@pdoclass::$dbconn[$cname] != null))) return null;
		$pdodb = ($cname == "default") ? pdoclass::$db : pdoclass::$dbconn[$cname];
		if($pdodb == null) return;
		try { 
			$statm = ['q'=>$pdodb->prepare($select), 's'=>$select, 'c'=>$cname, 'v'=>$vname, 'l'=>$log];
			return (!(strpos(preg_replace('/[^a-z]/','',strtolower(explode(' ',trim($select))[0] ?? '')),'select') !== false))
			       ? pdo_num_rows($statm)
				   : $statm; 
		} catch (PDOException $e) { pdo_log($statm, $e, true);
			if(@$_GET['debug'] == "2") echo "<!-- Error: " . $e->getMessage() . " -->";
			if($cname == "default") pdo_autoconfig();
			return []; } }


	function pdo_prepare($select,$cname="default") {
	if(($cname == "default") && (!pdoclass::$con)) return null;
	if(($cname != "default") && (!(isset(pdoclass::$dbconn[$cname])) && (@pdoclass::$dbconn[$cname] != null))) return null;
	$pdodb = ($cname == "default") ? pdoclass::$db : pdoclass::$dbconn[$cname];
	try { $statm = $pdodb->prepare($select);
	} catch (PDOException $e) { if(@$_GET['debug'] == "2") echo "<!-- Error: " . $e->getMessage() . " -->"; }
	return $statm; }


	function pdo_execute($statm, $array) { 
		if($statm == null) return array(); 
		return $statm->execute($array); }


	function pdo_fetch_object($statm) { 
		if($statm == null) return array(); 
		if(!isset($statm['q'])) return array();
		@$statm['q']->execute($statm['v'] ?? []);
		return @$statm['q']->fetchAll(PDO::FETCH_OBJ); }


	function pdo_fetch_item($statm) {
		if($statm == null) return array();
		return (pdo_fetch_array($statm)[0] ?? []); }


	function pdo_fetch_row($statm) { if($statm == null) return array(); return @pdo_fetch_item($statm); }


	function pdo_start_transaction($cname="default") { 
        $pdodb = ($cname == "default") ? pdoclass::$db : ((isset(pdoclass::$dbconn[$cname])) ? pdoclass::$dbconn[$cname] : null); 
        return ($pdodb == null) ? null : $pdodb->beginTransaction(); }


	function pdo_commit($cname="default") { 
        $pdodb = ($cname == "default") ? pdoclass::$db : ((isset(pdoclass::$dbconn[$cname])) ? pdoclass::$dbconn[$cname] : null); 
        return ($pdodb == null) ? null : $pdodb->commit(); }


	function pdo_rollback($cname="default") { 
        $pdodb = ($cname == "default") ? pdoclass::$db : ((isset(pdoclass::$dbconn[$cname])) ? pdoclass::$dbconn[$cname] : null); 
        return ($pdodb == null) ? null : $pdodb->rollBack(); }


	function pdo_close($cname="default") { 
	if($cname != "default") pdoclass::$dbconn[$cname] = null;
	else { pdoclass::$db = null; pdoclass::$con = false; }
	return true; }


	/* Auto PDO connect */
	if((PDC_DBHOST != '') && (PDC_DBUSER != '') && (PDC_DBBASE != ''))
	  if(!pdo_connect(PDC_DBHOST.":".(PDC_DBPORT ?? '3306'),PDC_DBUSER,(PDC_DBPASS ?? ''),PDC_DBBASE))
		if(PDC_DBDIER) die();
}
?>
