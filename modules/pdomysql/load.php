<?php
/* database configuration variables (dont change it here) */
$dbhost = ((isset($dbhost)) ? $dbhost : "localhost");
$dbport = ((isset($dbport)) ? $dbport : "3306");
$dbuser = ((isset($dbuser)) ? $dbuser : "");
$dbpass = ((isset($dbpass)) ? $dbpass : "");
$dbbase = ((isset($dbbase)) ? $dbbase : $projectname); /* name of the database (required) */
$dbdier = ((isset($dbdier)) ? $dbdier : false); /* script dies in case of db returns error */


/* Basic usage */
/*
    $var = pdo_query("select from ...");
    $var = pdo_fetch_array($var);
    foreach($var as &$item)
      $value = $item['key'];

    pdo_query("insert into ...");

	// You can also have a database.php file which will be called to create/update
	// your database structure in case the script fails to execute a query
*/

/* PDO database functions */
if(!class_exists('pdoclass')) {
	class pdoclass { public static $db = null; public static $dbconn = array(); public static $con = false; }


	function pdo_autoconfig($dir = null, $file = 'database.php') {
		if($dir === null) $dir = __DIR__;
		if(($_SERVER[$on = 'autocreatedbtables_'.md5("$dir/$file")] ?? '') == '1') return; else $_SERVER[$on] = '1';
		if(function_exists('autocreatedbtables')) return autocreatedbtables();
		for($i=0;$i<5;$i++)
			if(file_exists("$dir/$file")) return @include_once("$dir/$file");
			else $dir = realpath("$dir/../"); }


	function pdo_connect($dbhost,$dbuser,$dbpass,$dbbase='',$dbport='3306',$cname="default",$defresult=false) {
		if(strpos($dbhost,':') !== false)
		  if((is_array($sep = explode(':',$dbhost))) && (count($sep) < 3))
		    $sep = (($dbhost=$sep[0]).':'.($dbport=$sep[1]));
		$conectionstr = 'mysql:host='.$dbhost.((!empty($dbport)) ? ';port='.$dbport : '').((!empty($dbbase)) ? ';dbname='.$dbbase : '');
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
	if(($dbuser != '') && ($dbpass != '') && ($dbbase != ''))
	  if(!pdo_connect($dbhost,$dbuser,$dbpass,$dbbase,$dbport))
		if(isset($dbdier)) if($dbdier) die();
}
?>