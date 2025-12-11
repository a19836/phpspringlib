<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original PHP Spring Lib Repo: https://github.com/a19836/phpspringlib/
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once __DIR__ . "/../config.php";
include_once __DIR__ . "/config.php";
include get_lib("sqlmap.hibernate.HibernateClient");
include __DIR__ . "/MyHibernateCache.php";
include dirname(__DIR__) . "/MySQLDBBroker.php";

echo $style;

echo '<h1>PHP Spring Lib - Hibernate with services</h1>
<p>Extend Hibernate to allow services.</p>
<div class="note">
		<span>
		Learn how to use extend the Spring Hibernate library, by creating your own xml nodes.<br/>
		In this example we create the \'service\' nodes to work as alias to existing hibernate objects.
		</span>
</div>';

if (!$password) {
	echo '<div class="error">Please edit config.php file and define your DB credentials first!</div>';
	die();
}

echo '
<h3>Some Extended Methods:</h3>
<div class="code short input">
	<textarea readonly>
$services = parseServicesFile(__DIR__ . "/assets/services.xml");
$obj_name = $services["Item"];
$obj = $SQLClient->getHbnObj($obj_name);
	</textarea>
</div>

<h3>Examples:</h3>';

//Init HibernateClient
$SQLClient = new HibernateClient();

//$MyHibernateCache = new MyHibernateCache( sys_get_temp_dir() . "/cache/spring/hibernate/" );
//$SQLClient->setCacheLayer($MyHibernateCache);

$DBBroker = new MySQLDBBroker($host, $username, $password, $db_name);
$SQLClient->setRDBBroker($DBBroker);

//Prepare table if not exists
createTable($DBBroker);

//Load iBatis queries
$external_vars = array(
	"app_path" => $app_path
);
$SQLClient->loadXML( __DIR__ . "/assets/item_subitem.xml", $external_vars);

$code = '$DBBroker = new MySQLDBBroker($host, $username, $password, $db_name);

$SQLClient = new HibernateClient();
$SQLClient->setRDBBroker($DBBroker);
$SQLClient->loadXML( __DIR__ . "/assets/item_subitem.xml");';
echo '<div>
	<h4>Start Hibernate Client and load xml file:</h4>
	<div class="code short input">
		<textarea readonly>' . $code . '</textarea>
	</div>
	<div class="code xml">
		<textarea readonly>' . file_get_contents(__DIR__ . "/assets/item_subitem.xml") . '</textarea>
	</div>
</div>';

//Extend beans factory
$services = parseServicesFile(__DIR__ . "/assets/services.xml");

$code = '$services = parseServicesFile(__DIR__ . "/assets/services.xml");';
echo '<div>
	<h4>Extend Hibernate with services:</h4>
	<div class="code one-line input">
		<textarea readonly>' . $code . '</textarea>
	</div>
	<div class="code short xml">
		<textarea readonly>' . file_get_contents(__DIR__ . "/assets/services.xml") . '</textarea>
	</div>
</div>

<h4>Get hibernate object \'Item\':</h4>
<div style="margin-left:20px;">';

//Call Hibernate objs
$obj = $SQLClient->getHbnObj($services["Item"]);

if ($obj) {
	$code = '$obj = $SQLClient->getHbnObj($services["Item"]);
$class = get_class($obj);';
	$output = "class: " . get_class($obj) . "\n\n" . print_r($obj, true);
	printTest("Item obj", $code, $output, "one-line", "");
	
	$props_to_attributes = $obj->getPropertiesToAttributes();
	$code = '//Then call some hibernate methods

$props_to_attributes = $obj->getPropertiesToAttributes();
//$obj->insert(...);
//$obj->callQuery(...);
//$obj->getData(...);
//$obj->getSQL(...);
//etc...';
	$output = print_r($props_to_attributes, true);
	printTest("SubItem obj", $code, $output, "short");
}

echo '</div>
<h4>Get hibernate object \'SubItem\':</h4>
<div style="margin-left:20px;">';

$obj = $SQLClient->getHbnObj($services["SubItem"]);

if ($obj) {
	$code = '$obj = $SQLClient->getHbnObj($services["SubItem"]);
$class = get_class($obj);';
	$output = "class: " . get_class($obj) . "\n\n" . print_r($obj, true);
	printTest("SubItem obj", $code, $output, "one-line", "");
}

echo '</div>';

/* EXTENDING SERVICES FUNCTIONS */

function parseServicesFile($services_file_path) {
	//get services
	$content = file_get_contents($services_file_path);
	$nodes = XMLFileParser::parseXMLContentToArray($content);
	
	$first_node_name = is_array($nodes) ? array_keys($nodes) : array();
	$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
	
	$services_node = isset($nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"]) && is_array($nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"]) ? $nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"] : array();
	$services = array();
	$t = $services_node ? count($services_node) : 0;
	
	for($i = 0; $i < $t; $i++) {
		$service_node = $services_node[$i];
		
		$alias = XMLFileParser::getAttribute($service_node, "alias");
		$obj = XMLFileParser::getAttribute($service_node, "obj");
		
		$services[$alias] = $obj;
	}
	
	//return services
	return $services;
}
?>
