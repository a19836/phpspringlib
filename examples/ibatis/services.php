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
include get_lib("sqlmap.ibatis.IBatisClient");
include dirname(__DIR__) . "/MySQLDBBroker.php";

echo $style;

echo '<h1>PHP Spring Lib - iBatis with services</h1>
<p>Extend iBatis to allow services.</p>
<div class="note">
		<span>
		Learn how to use extend the Spring iBatis library, by creating your own xml nodes.<br/>
		In this example we create the \'service\' nodes to work as alias to existing queries.
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
$query_id = $services[$service_alias];
$query = getQuery($SQLClient, $query_id);
	</textarea>
</div>

<h3>Examples:</h3>';

//Init IBatisClient
$SQLClient = new IBatisClient();
//$SQLClient->setCacheRootPath( sys_get_temp_dir() . "/cache/spring/ibatis/" );

$DBBroker = new MySQLDBBroker($host, $username, $password, $db_name);
$SQLClient->setRDBBroker($DBBroker);

//Prepare table if not exists
createTable($DBBroker);

//Load iBatis queries
$SQLClient->loadXML( __DIR__ . "/assets/item.xml" );

$code = '$DBBroker = new MySQLDBBroker($host, $username, $password, $db_name);

$SQLClient = new IBatisClient();
$SQLClient->setRDBBroker($DBBroker);
$SQLClient->loadXML( __DIR__ . "/assets/item.xml" );';
echo '<div>
	<h4>Start iBatis Client and load xml file:</h4>
	<div class="code short input">
		<textarea readonly>' . $code . '</textarea>
	</div>
	<div class="code xml">
		<textarea readonly>' . file_get_contents(__DIR__ . "/assets/item.xml") . '</textarea>
	</div>
</div>';

//Extend beans factory
$services = parseServicesFile(__DIR__ . "/assets/services.xml");

$code = '$services = parseServicesFile(__DIR__ . "/assets/services.xml");';
echo '<div>
	<h4>Extend iBatis with services:</h4>
	<div class="code one-line input">
		<textarea readonly>' . $code . '</textarea>
	</div>
	<div class="code short xml">
		<textarea readonly>' . file_get_contents(__DIR__ . "/assets/services.xml") . '</textarea>
	</div>
</div>

<h4>Play with query \'get_my_item\':</h4>
<div style="margin-left:20px;">';

//Call and execute iBatis queries
$query = getQuery($SQLClient, $services["get_my_item"]);

if ($query) {
	$code = '$query = getQuery($SQLClient, $services["get_my_item"]);';
	printTest("Get query", $code, print_r($query, true), "one-line");
	
	$result = $SQLClient->execQuery($query, array("item_id" => 1));
	$ItemTest = @$result[0];
	$code = '$result = $SQLClient->execQuery($query, array("item_id" => 1));

$ItemTest = @$result[0];
$id = $ItemTest->getId();
$title = $ItemTest->getTitle();
$status = $ItemTest->getStatus();';
	$output = "Result:\n" . print_r($result, true) . "\nItemTest:\n- id: " . $ItemTest->getId() . "\n- Title: " . $ItemTest->getTitle() . "\n- Status: " . $ItemTest->getStatus();
	printTest("Execute query", $code, $output, "short", "");
}

echo '</div>
<h4>Play with query \'get_my_item_simple\':</h4>
<div style="margin-left:20px;">';

$query = getQuery($SQLClient, $services["get_my_item_simple"]);

if ($query) {
	$code = '$query = getQuery($SQLClient, $services["get_my_item_simple"]);';
	printTest("Get query", $code, print_r($query, true), "one-line");
	
	$sql = $SQLClient->getQuerySQL($query, array("item_id" => 1));
	$code = '$sql = $SQLClient->getQuerySQL($query, array("item_id" => 1));';
	printTest("Get sql from query", $code, $sql, "one-line", "one-line");
	
	$result = $SQLClient->getSQL($sql);
	$code = '$result = $SQLClient->getSQL($sql);';
	printTest("Execute sql", $code, "Result:\n" . print_r($result, true), "one-line");
}
echo '</div>';

/* EXTENDING SERVICES FUNCTIONS */

function getQuery($SQLClient, $query_id) {
	$nodes = $SQLClient->getNodesData();
	
	if ($nodes)
		foreach ($nodes as $query_type => $queries)
			if (isset($queries[$query_id]))
				return $queries[$query_id];
	
	return null;
}

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
		$query = XMLFileParser::getAttribute($service_node, "query");
		
		$services[$alias] = $query;
	}
	
	//return services
	return $services;
}
?>
