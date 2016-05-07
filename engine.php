<?php
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 1);

$engine = json_decode(file_get_contents('sys/engine.json'));

if (!isset($_GET['web'])) {
	$outputs = array('mp' => true, 'tm' => false, 'web' => false);
	define('INPUT', 'tm');
	define('OUTPUT', 'mp');
} else {
	$outputs = array('mp' => false, 'tm' => false, 'web' => true);
	define('INPUT', 'tm');
	define('OUTPUT', 'web');
}
define('MLLINK', 'maniaplanet:///:');

$webOnly = array('s0', 's1', 'p0', 'p1', 'p2', 'name');
$timeStart = microtime(true);

$javascript = '';
function addJs($js)
{
	global $javascript;
	$javascript .= $js;
}

require_once('sys/phphooks.php');

require_once('sys/tmfcolorparser.php');
$colorPraser = new TMFColorParser();

$hook = new phphooks();
function add_hook($tag, $function, $priority = 10)
{
	global $hook;
	$hook->add_hook($tag,$function,$priority);
}
$hook->load_all_plugins('sys/');
$xml_hooks = $hook->hooks;

//$get_xml = file_get_contents('ml.xml');
$get_xml = file_get_contents('http://rstyle.paragon-esports.com/2010_HD/index.php?'.$_SERVER['QUERY_STRING']);
$manialink_xml_file = simplexml_load_string($get_xml);

$attr = array('scale' => 1, 'p0' => '0', 'p1' => '0', 'p2' => '0', 'posn' => '0 0 0');
function pml($xml)
{
	global $xml_hooks, $hook, $attr;
	foreach ($xml_hooks as $xml_hook => $xml_hook2) {
		if (strpos($xml_hook,'xml_') !== false) {
			$name = str_replace('xml_','',$xml_hook);
			foreach ($xml->$name as $obj) {
				
				$obj->addAttribute('xmlName', $name);
				if (!isset($obj->attributes()->halign))
					$obj->addAttribute('halign','left');
				if (!isset($obj->attributes()->valign))
					$obj->addAttribute('valign','top');
				
				//xml_position($obj);
				
				$hook->hook('xml_all',$obj);
				$hook->hook('xml_'.$name,$obj);
				$hook->hook('xml_all_end',$obj);
			}
		}
	}
}

if (OUTPUT == 'web') {
	echo '<!DOCTYPE html>
	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="author" content="RStyle">
	<title>Title of the document</title>
	<style type="text/css">
	<!--
	img{
		border:none;
		position:absolute;
	}
	body{
		overflow:hidden;
		margin:0;
		padding:0;
		font-size:16px;
		background-color:#999;
		font-family:Verdana;
		color:#FFF;
	}
	-->
	</style>
	</head>
	<body>
	';
} else {
	echo '<?xml version="1.0" encoding="utf-8" ?>
	<manialink version="1">';
}

pml($manialink_xml_file);

if (OUTPUT == 'web') {
	echo '<script type="text/javascript" src="'.$engine->jQuery.'"></script>
	HookCalls:'.$hook->hookCalls.' - PluginCalls:'.$hook->pluginCalls.'-
	'.(microtime(true) - $timeStart).'
	<script type="text/javascript">
	/* <![CDATA[ */
	'.$javascript.'
	/* ]]> */
	</script>
	</body>
	</html>';
} else {
	echo '</manialink>';
}
