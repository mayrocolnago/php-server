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
*/

/* PDO database functions */
if(!class_exists('pdoclass')) {
	class pdoclass { public static $db = null; public static $dbconn = array(); public static $con = false; }
}

if(!function_exists('pdo_connect'))	{
	function pdo_connect($dbhost,$dbport,$dbuser,$dbpass,$dbbase,$cname="default") {
	if($cname == "default") { try {
		pdoclass::$db = new PDO('mysql:host='.$dbhost.';port='.$dbport.';dbname='.$dbbase.'', $dbuser, $dbpass);
			pdoclass::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
		return (pdoclass::$con = true);
		} catch (PDOException $e) {
		if(@$_GET['debug'] == "2") echo "<!-- Error: " . $e->getMessage() . " -->";
		return false; } 
	} else { try {
		if((isset(pdoclass::$dbconn[$cname])) && (@pdoclass::$dbconn[$cname] != null)) return false; pdoclass::$dbconn[$cname] = null;
		pdoclass::$dbconn[$cname] = new PDO('mysql:host='.$dbhost.';port='.$dbport.';dbname='.$dbbase.'', $dbuser, $dbpass);
			pdoclass::$dbconn[$cname]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); return true;
		} catch (PDOException $e) { return false; } } }
}

if(!function_exists('pdo_query')) {
	function pdo_query($select,$cname="default") {
	if(($cname == "default") && (!pdoclass::$con)) return null;
	if(($cname != "default") && (!(isset(pdoclass::$dbconn[$cname])) && (@pdoclass::$dbconn[$cname] != null))) return null;
	$pdodb = ($cname == "default") ? pdoclass::$db : pdoclass::$dbconn[$cname];
	try { $statm = $pdodb->prepare($select); $par = explode(' ',$select);
	if(strtolower($par[0]) != '') if(!(strpos(strtolower($par[0]),'select') !== false)) $statm = pdo_num_rows($statm);
	} catch (PDOException $e) { if(@$_GET['debug'] == "2") echo "<!-- Error: " . $e->getMessage() . " -->";
		if(($cname == "default") && (function_exists('autocreatedbtables'))) autocreatedbtables();
	$statm = $pdodb->prepare("select 0 as void"); }
		return $statm; }
}

if(!function_exists('pdo_prepare'))	{	
	function pdo_prepare($select,$cname="default") {
	if(($cname == "default") && (!pdoclass::$con)) return null;
	if(($cname != "default") && (!(isset(pdoclass::$dbconn[$cname])) && (@pdoclass::$dbconn[$cname] != null))) return null;
	$pdodb = ($cname == "default") ? pdoclass::$db : pdoclass::$dbconn[$cname];
		try { $statm = $pdodb->prepare($select);
		} catch (PDOException $e) { if(@$_GET['debug'] == "2") echo "<!-- Error: " . $e->getMessage() . " -->"; }
		return $statm; }
}

if(!function_exists('pdo_insert_id')) {	
	function pdo_insert_id($cname="default") { 
	if(($cname == "default") && (!pdoclass::$con)) return null;
	if(($cname != "default") && (!(isset(pdoclass::$dbconn[$cname])) && (@pdoclass::$dbconn[$cname] != null))) return null;
	$pdodb = ($cname == "default") ? pdoclass::$db : pdoclass::$dbconn[$cname];
	return $pdodb->lastInsertId(); }
}

if(!function_exists('pdo_num_rows')) {	
	function pdo_num_rows($statm) {
	if($statm == null) return 0;
		try { @$statm->execute(); return @$statm->rowCount();
		} catch (PDOException $e) { return 0; } }
}

if(!function_exists('pdo_fetch_array')) {
	function pdo_fetch_array($statm) {
	if($statm == null) return array();
	try { @$statm->execute();
	} catch (PDOException $e) { return array(); }
	$retor = $statm->fetchAll(); return $retor; }
}

if(!function_exists('pdo_fetch_object')) {	
	function pdo_fetch_object($statm) { if($statm == null) return array(); @$statm->execute(); return $statm->fetchAll(PDO::FETCH_OBJ); }
}

if(!function_exists('pdo_fetch_item')) {	
	function pdo_fetch_item($statm) { if($statm == null) return array(); $retor = pdo_fetch_array($statm); return @$retor[0]; }
}

if(!function_exists('pdo_fetch_row')) {
	function pdo_fetch_row($statm) { if($statm == null) return array(); return @pdo_fetch_item($statm); }
}

if(!function_exists('pdo_execute')) {	
	function pdo_execute($statm, $array) { if($statm == null) return array(); return $statm->execute($array); }
}

if(!function_exists('pdo_start_transaction')) {	
	function pdo_start_transaction($cname="default") { 
        $pdodb = ($cname == "default") ? pdoclass::$db : ((isset(pdoclass::$dbconn[$cname])) ? pdoclass::$dbconn[$cname] : null); 
        return ($pdodb == null) ? null : $pdodb->beginTransaction(); }
}

if(!function_exists('pdo_commit')) {	
	function pdo_commit($cname="default") { 
        $pdodb = ($cname == "default") ? pdoclass::$db : ((isset(pdoclass::$dbconn[$cname])) ? pdoclass::$dbconn[$cname] : null); 
        return ($pdodb == null) ? null : $pdodb->commit(); }
}

if(!function_exists('pdo_rollback')) {	
	function pdo_rollback($cname="default") { 
        $pdodb = ($cname == "default") ? pdoclass::$db : ((isset(pdoclass::$dbconn[$cname])) ? pdoclass::$dbconn[$cname] : null); 
        return ($pdodb == null) ? null : $pdodb->rollBack(); }
}

if(!function_exists('pdo_close')) {	
	function pdo_close($cname="default") { 
	if($cname != "default") pdoclass::$dbconn[$cname] = null;
	else { pdoclass::$db = null; pdoclass::$con = false; }
	return true; }
}

/* Auto PDO connect */
if(($dbuser != '') && ($dbpass != '') && ($dbbase != ''))
  if(!pdo_connect($dbhost,$dbport,$dbuser,$dbpass,$dbbase))
    if(isset($dbdier)) if($dbdier) die();

?>