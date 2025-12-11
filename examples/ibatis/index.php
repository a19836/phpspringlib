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

echo '<h1>PHP Spring Lib - iBatis</h1>
<p>Spring iBatis - create SQL statements in xml</p>
<div class="note">
		<span>
		Learn how to use Spring iBatis in PHP, with SQL statements defined in XML files.<br/>
		With this library, you can map SQL queries to PHP objects using XML-based configurations, where all your queries can be organized across multiple XML files.<br/>
		The SQL statements can receive inputs that are automatically replaced in the query.<br/>
		These inputs can be filtered and validated through a parameter map or class.<br/>
		After the query is executed, the result can also be filtered using a result map or class.
		</span>
</div>';

if (!$password) {
	echo '<div class="error">Please edit config.php file and define your DB credentials first!</div>';
	die();
}

echo '
<h3>Some Available IBatisClient Methods:</h3>
<div class="code input">
	<textarea readonly>
$SQLClient = new IBatisClient();
$SQLClient->setRDBBroker($DBBroker);
$SQLClient->setCacheRootPath( sys_get_temp_dir() . "/cache/spring/ibatis/" ); //set cache to be faster

$SQLClient->loadXML($obj_path, $external_vars = false);
$query = $SQLClient->getQuery($query_type, $query_id);
$result = $SQLClient->execQuery($query, $parameters, $options = null);
$sql = $SQLClient->getQuerySQL($query, $parameters, $options = null);
$result = $SQLClient->getFunction($function_name, $parameters, $options = null);
$result = $SQLClient->getData($sql, $options = null);
$status = $SQLClient->setData($sql, $options = null);
$result = $SQLClient->getSQL($sql, $options = null);
$status = $SQLClient->setSQL($sql, $options = null);
$id = $SQLClient->getInsertedId($options = null);
$status = $SQLClient->insertObject($table_name, $attributes, $options = null);
$status = $SQLClient->updateObject($table_name, $attributes, $conditions, $options = null);
$status = $SQLClient->deleteObject($table_name, $conditions, $options = null);
$result = $SQLClient->findObjects($table_name, $attributes, $conditions, $options = null);
$total = $SQLClient->countObjects($table_name, $conditions, $options = null);
$result = $SQLClient->findRelationshipObjects($table_name, $rel_elm, $parent_conditions, $options = null);
$total = $SQLClient->countRelationshipObjects($table_name, $rel_elm, $parent_conditions, $options = null);
$max = $SQLClient->findObjectsColumnMax($table_name, $attribute_name, $options = null);
	</textarea>
</div>

<h3>Examples:</h3>';

//Init IBatisClient
$SQLClient = new IBatisClient();
//$SQLClient->setCacheRootPath( sys_get_temp_dir() . "/cache/spring/ibatis/" ); //set cache to be faster

$DBBroker = new MySQLDBBroker($host, $username, $password, $db_name);
$SQLClient->setRDBBroker($DBBroker);

//Prepare table if not exists
createTable($DBBroker);

//Load iBatis queries
$SQLClient->loadXML( __DIR__ . "/assets/item.xml" );

$code = '$DBBroker = new MySQLDBBroker($host, $username, $password, $db_name);

$SQLClient = new IBatisClient();
$SQLClient->setRDBBroker($DBBroker);
$SQLClient->loadXML( __DIR__ . "/assets/item.xml", $external_vars = false);';
echo '<div>
	<h4>Start iBatis Client and load xml file:</h4>
	<div class="code short input">
		<textarea readonly>' . $code . '</textarea>
	</div>
	<div class="code xml">
		<textarea readonly>' . file_get_contents(__DIR__ . "/assets/item.xml") . '</textarea>
	</div>
</div>

<h4>Play with query \'select_item\':</h4>
<div style="margin-left:20px;">';

//Call and execute iBatis queries
$query = $SQLClient->getQuery("select", "select_item");
$code = '$query = $SQLClient->getQuery("select", "select_item");';
printTest("Get query", $code, print_r($query, true), "one-line");

if ($query) {
	$parameters = array("item_id" => 1);
	$options = null;
	
	//includes are optional - Includes are only needed for REST or SOAP request where we can pass the object class paths and then include them, before convert the data into class objects.
	//$includes = $SQLClient->getLibsOfResultClassAndMap($query);
	//SQLMapIncludesHandler::includeLibsOfResultClassAndMap($includes);
	//echo "Includes: <pre>";print_r($includes);echo "</pre><br/>";
	
	$result = $SQLClient->execQuery($query, $parameters, $options);
	$ItemTest = @$result[0];
	$code = '$parameters = array("item_id" => 1);
$result = $SQLClient->execQuery($query, $parameters);

$ItemTest = @$result[0];
$id = $ItemTest->getId();
$title = $ItemTest->getTitle();
$status = $ItemTest->getStatus();';
	$output = "Result:\n" . print_r($result, true) . "\nItemTest:\n- id: " . $ItemTest->getId() . "\n- Title: " . $ItemTest->getTitle() . "\n- Status: " . $ItemTest->getStatus();
	printTest("Execute query", $code, $output, "short", "");
	
	$sql = $SQLClient->getQuerySQL($query, $parameters, $options);
	$code = '$sql = $SQLClient->getQuerySQL($query, array("item_id" => 1));';
	printTest("Get sql from query", $code, $sql, "one-line", "one-line");
	
	$result = $SQLClient->getSQL($sql);
	$code = '$result = $SQLClient->getSQL($sql);';
	printTest("Execute sql", $code, "Result:\n" . print_r($result, true), "one-line");
}

echo '</div>
<h4>Play with query \'select_item_simple\':</h4>
<div style="margin-left:20px;">';

$query = $SQLClient->getQuery("select", "select_item_simple");

if ($query) {
	$code = '$query = $SQLClient->getQuery("select", "select_item_simple");';
	printTest("Get query", $code, print_r($query, true), "one-line");
	
	$parameters = array("item_id" => 1);
	$result = $SQLClient->execQuery($query, $parameters, $options);
	$MyItem = @$result[0];
	$code = '$result = $SQLClient->execQuery($query, array("item_id" => 1));

$MyItem = @$result[0];
$id = $MyItem->getId();
$name = $MyItem->getName()->getData();
$status = $MyItem->getStatus()->getData();';
	$output = "Result:\n" . print_r($result, true) . "\nMyItem\n- id: " . $MyItem->getId() . "\n- Name: " . $MyItem->getName()->getData() . "\n- Status: " . $MyItem->getStatus()->getData();
	printTest("Execute query", $code, $output, "short", "");
}

echo '</div>
<h4>Play with query \'select_item_by_pk\':</h4>
<div style="margin-left:20px;">';

$query = $SQLClient->getQuery("select", "select_item_by_pk");

if ($query) {
	$code = '$query = $SQLClient->getQuery("select", "select_item_by_pk");';
	printTest("Get query", $code, print_r($query, true), "one-line");
	
	$parameters = array("pk" => 1);
	$result = $SQLClient->execQuery($query, $parameters, $options);
	$item = @$result[0];
	$code = '$parameters = array("pk" => 1);
$result = $SQLClient->execQuery($query, $parameters);

$item = @$result[0];
$pk = $item["pk"];
$name = $item["name"]->getData();
$active = $item["active"]->getData();';
	$output = "Result:\n" . print_r($result, true) . "\nitem:\n- pk: " . $item["pk"] . "\n- Name: " . $item["name"]->getData() . "\n- Active: " . $item["active"]->getData();
	printTest("Execute query", $code, $output, "short", "");
}

echo '</div>
<h4>Play with query \'select_sub_item\':</h4>
<div style="margin-left:20px;">';

$query = $SQLClient->getQuery("select", "select_sub_item");

if ($query) {
	$code = '$query = $SQLClient->getQuery("select", "select_sub_item");';
	printTest("Get query", $code, print_r($query, true), "one-line");
	
	$parameters = array("item_id" => 1);
	$result = $SQLClient->execQuery($query, $parameters, $options);
	$MySubItem = @$result[0];
	$code = '$parameters = array("item_id" => 1);
$result = $SQLClient->execQuery($query, $parameters);

$MySubItem = @$result[0];
$data = $MySubItem->getData();
';
	$output = "Result:\n" . print_r($result, true) . "\nMySubItem data:\n" . print_r($MySubItem->getData(), true);
	printTest("Execute query", $code, $output, "short", "");
	
	$sql = $SQLClient->getQuerySQL($query, $parameters, $options);
	$code = '$sql = $SQLClient->getQuerySQL($query, $parameters);';
	printTest("Get sql from query", $code, $sql, "one-line", "one-line");
	
	$result = $SQLClient->getSQL($sql);
	$code = '$result = $SQLClient->getSQL($sql);';
	printTest("Execute sql", $code, "Result:\n" . print_r($result, true), "one-line", "");
}

echo '</div>
<h4>Play with query \'procedure_items\':</h4>
<p style="text-align:left;">This query is a procedure.</p>
<div style="margin-left:20px;">';

$query = $SQLClient->getQuery("procedure", "procedure_items");

if ($query) {
	$code = '$query = $SQLClient->getQuery("procedure", "procedure_items");';
	printTest("Get procedure", $code, print_r($query, true), "one-line");
	
	$ItemTest = new $ItemTest();
	$ItemTest->setTitle("Some title");
	$sql = $SQLClient->getQuerySQL($query, $ItemTest, $options);
	$code = '$ItemTest = new $ItemTest();
$ItemTest->setTitle("Some title");
$sql = $SQLClient->getQuerySQL($query, $ItemTest);';
	printTest("Get procedure sql", $code, str_replace("\t", "", $sql), "short", "one-line");
	
	$result = $SQLClient->getData($sql);
	$code = '$result = $SQLClient->getData($sql);';
	printTest("Execute procedure", $code, "Result:\n" . print_r($result, true), "one-line", "");
}

echo '</div>';
?>
