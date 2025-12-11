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

echo '<h1>PHP Spring Lib - Hibernate</h1>
<p>Spring Hibernate - create ORM and DAO objects through XML</p>
<div class="note">
		<span>
		Learn how to use Spring/Hibernate-style concepts in PHP by creating ORM (Object-Relational-Mapping) and DAO (Data-Access-Objects) objects through XML configuration files.<br/>
		This library is inspired by the Hibernate ORM and provides a unified, streamlined way to manage database operations using dependency injection, transaction management, and object–relational mapping.<br/>
		Through XML nodes, you can map a PHP object to a database table and automatically inherit several methods for interacting with that table.<br/>
		You can also extend these methods with custom SQL queries and additional features.
		</span>
</div>';

if (!$password) {
	echo '<div class="error">Please edit config.php file and define your DB credentials first!</div>';
	die();
}

echo '
<h3>Some Available HibernateClient Methods:</h3>
<div class="code input">
	<textarea readonly>
$SQLClient = new HibernateClient();
$SQLClient->setRDBBroker($DBBroker);
$SQLClient->setCacheLayer($MyHibernateCache); //set cache to be faster

$SQLClient->loadXML($obj_path, $external_vars = false);
$obj = $SQLClient->getHbnObj($obj_name, $module_id, $service_id, $options = false);

//OBJET METHODS
$obj->insert($data, &$ids = false, $options = false);
$obj->insertAll($data, &$statuses = false, &$ids = false, $options = false);
$obj->update($data, $options = false);
$obj->updateAll($data, &$statuses = false, $options = false);
$obj->insertOrUpdate($data, &$ids = false, $options = false);
$obj->insertOrUpdateAll($data, &$statuses = false, &$ids = false, $options = false);
$obj->updateByConditions($data, $options = false);
$obj->updatePrimaryKeys($data, $options = false);
$obj->delete($data, $options = false);
$obj->deleteAll($data, &$statuses = false, $options = false);
$obj->deleteByConditions($data, $options = false);
$obj->findById($ids, $data = array(), $options = false);
$obj->find($data = array(), $options = false);
$obj->count($data = array(), $options = false);
$obj->findRelationships($parent_ids, $options = false);
$obj->findRelationship($rel_name, $parent_ids, $options = false);
$obj->countRelationships($parent_ids, $options = false);
$obj->countRelationship($rel_name, $parent_ids, $options = false); 

//SAME METHODS THAN ON IBATIS
$obj->callQuerySQL($query_type, $query_id, $parameters = false);
$obj->callQuery($query_type, $query_id, $parameters = false, $options = false);

$obj->callInsertSQL($query_id, $parameters = false);
$obj->callInsert($query_id, $parameters = false, $options = false);

$obj->callUpdateSQL($query_id, $parameters = false);
$obj->callUpdate($query_id, $parameters = false, $options = false);

$obj->callDeleteSQL($query_id, $parameters = false);
$obj->callDelete($query_id, $parameters = false, $options = false);

$obj->callSelectSQL($query_id, $parameters = false);
$obj->callSelect($query_id, $parameters = false, $options = false);

$obj->callProcedureSQL($query_id, $parameters = false);
$obj->callProcedure($query_id, $parameters = false, $options = false);

//SAME METHODS THAN ON DB BROKER
$obj->getData($sql, $options = false);
$obj->setData($sql, $options = false);
$obj->getSQL($sql, $options = false);
$obj->setSQL($sql, $options = false);
$obj->getInsertedId($options = false);
$obj->getFunction($function_name, $parameters = false, $options = false);

//SOME GETTERS METHODS
$obj->getCacheLayer();
$obj->getObjName();
$obj->getTableName();
$obj->getExtendClassName();
$obj->getExtendClassPath();
$obj->getIds();
$obj->getParameterClass();
$obj->getParameterMap();
$obj->getResultClass();
$obj->getResultMap();
$obj->getTableAttributes();
$obj->getManyToOne();
$obj->getManyToMany();
$obj->getOneToMany();
$obj->getOneToOne();
$obj->getQueries();
$obj->getPropertiesToAttributes();
$obj->getModuleId();
$obj->getServiceId();
	</textarea>
</div>

<h3>Examples:</h3>';

//Init HibernateClient
$SQLClient = new HibernateClient();

//$MyHibernateCache = new MyHibernateCache( sys_get_temp_dir() . "/cache/spring/hibernate/" ); //set cache to be faster
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
$SQLClient->loadXML( __DIR__ . "/assets/item_subitem.xml", $external_vars = false);';
echo '<div>
	<h4>Start Hibernate Client and load xml file:</h4>
	<div class="code short input">
		<textarea readonly>' . $code . '</textarea>
	</div>
	<div class="code xml">
		<textarea readonly>' . file_get_contents(__DIR__ . "/assets/item_subitem.xml") . '</textarea>
	</div>
</div>

<h4>Play with obj \'ItemObj\':</h4>
<div style="margin-left:20px;">
';

//Call object
$ItemObj = $SQLClient->getHbnObj("ItemObj");

if ($ItemObj) {
	//No need for cast, unless you use REST or SOAP request.
	//$ItemObj = is_a($ItemObj, "MyItemHbnModel") ? $ItemObj : ObjectHandler::objectToObject($ItemObj, "MyItemHbnModel"); //if broker is REST, we must convert the 
	//echo "<pre>";print_r($ItemObj);die();
	$code = '$ItemObj = $SQLClient->getHbnObj("ItemObj");
$class = get_class($ItemObj);';
	$output = "ItemObj class: " . get_class($ItemObj) . "\n\n" . print_r($ItemObj, true);
	printTest("ItemObj", $code, $output, "one-line", "");

	$code = '$ids = $ItemObj->getIds();
	$many_to_one = $ItemObj->getManyToOne();
	$many_to_many = $ItemObj->getManyToMany();
	$one_to_many = $ItemObj->getOneToMany();
	$one_to_one = $ItemObj->getOneToOne();
	$queries = $ItemObj->getQueries();
	$table_attributes = = $ItemObj->getTableAttributes();
	$properties_to_attributes = $ItemObj->getPropertiesToAttributes();';
	$output = "ITEM OBJ SETTINGS:\n" 
		. "- getIds: " . print_r($ItemObj->getIds(), true) . "\n"
		. "- getManyToOne: " . print_r($ItemObj->getManyToOne(), true) . "\n"
		. "- getManyToMany: " . print_r($ItemObj->getManyToMany(), true) . "\n"
		. "- getOneToMany: " . print_r($ItemObj->getOneToMany(), true) . "\n"
		. "- getOneToOne: " . print_r($ItemObj->getOneToOne(), true) . "\n"
		. "- getQueries: " . print_r($ItemObj->getQueries(), true) . "\n"
		. "- getTableAttributes: " . print_r($ItemObj->getTableAttributes(), true) . "\n"
		. "- getPropertiesToAttributes: " . print_r($ItemObj->getPropertiesToAttributes(), true) . "\n";
	printTest("ItemObj settings", $code, $output, "short", "");

	$attributes = array("name" => "hibernate item test X", "status" => 0);
	$status = $ItemObj->insert($attributes, $ids);
	$new_id = $ItemObj->getInsertedId();
	$code = '$attributes = array("name" => "hibernate item test X", "status" => 0);
$status = $ItemObj->insert($attributes, $ids);
$new_id = $ItemObj->getInsertedId();';
	$output = "status: $status\nnew_id: $new_id\n\$ids: " . print_r($ids, true);
	printTest("Insert new item", $code, $output);

	$result = $ItemObj->findById($new_id, array(
			"attributes" => array("pk_id", "name", "status"),
			"relationships" => true
		),
		array(
			"separated_by_objects" => true
		)
	);
	$code = '$result = $ItemObj->findById($new_id, array(
		"attributes" => array("pk_id", "name", "status"),
		"relationships" => true
	),
	array(
		"separated_by_objects" => true
	)
);';
	$output = "result: \n" . print_r($result, true);
	printTest("Select item '{$new_id}' with separated objects:", $code, $output);

	$item_id = $new_id;
	$status = $ItemObj->insertOrUpdateAll(
		array(
			array("pk_id" => $item_id, "name" => "hibernate item test W", "status" => 1),
			array("name" => "hibernate item test T", "status" => 1)
		),
		$statuses,
		$ids
	);
	$new_id = isset($ids[1]["pk_id"]) ? $ids[1]["pk_id"] : null;
	$code = '$item_id = $new_id;
$status = $ItemObj->insertOrUpdateAll(
	array(
		array("pk_id" => $item_id, "name" => "hibernate item test W", "status" => 1),
		array("name" => "hibernate item test T", "status" => 1)
	),
	$statuses,
	$ids
);
$new_id = isset($ids[1]["pk_id"]) ? $ids[1]["pk_id"] : null;';
	$output = "- updated_id: $item_id\n- new_id: $new_id\n- news_ids: " . print_r($ids, true) . "\n- statuses: " . print_r($statuses, true);
	printTest("Update and insert items:", $code, $output, "", "");

	$result = $ItemObj->getSQL("select * from item where id in ($item_id, $new_id)");
	//$result = $ItemObj->getSQL("select * from item where id in ($item_id, $new_id)", array("limit" => 2, "start" => 0));
	$code = '$result = $ItemObj->getSQL("select * from item where id in ($item_id, $new_id)");';
	$output = "result: \n" . print_r($result, true);
	printTest("Select new and updated item ($new_id, $item_id):", $code, $output, "one-line", "");

	$result = $ItemObj->find(array(
			"conditions" => array("pk_id" => array($item_id, $new_id)),
			"conditions_join" => "or"
		)
	);
	$code = '$result = $ItemObj->find(array(
		"conditions" => array("pk_id" => array($item_id, $new_id)),
		"conditions_join" => "or"
	)
);';
	$output = "result: \n" . print_r($result, true);
	printTest("Select new and updated item through 'find' method:", $code, $output, "short", "");
	
	include_once get_lib("root.examples.ibatis.assets.dao.ItemTest");
	$ItemTest = new ItemTest();
	$ItemTest->setTitle("Foo");
	$sql = $ItemObj->callProcedureSQL("procedure_items", $ItemTest);
	$result = $ItemObj->callProcedure("procedure_items", $ItemTest);
	$code = 'include_once get_lib("root.examples.ibatis.assets.dao.ItemTest");
$ItemTest = new ItemTest();
$ItemTest->setTitle("Foo");

$sql = $ItemObj->callProcedureSQL("procedure_items", $ItemTest);
$result = $ItemObj->callProcedure("procedure_items", $ItemTest);';
	$output = "sql: \n$sql\n\nresult: \n" . print_r($result, true);
	printTest("ItemObj Procedure:", $code, $output, "short", "");
}

echo '</div>

<h4>Play with obj \'SubItemObj\':</h4>
<div style="margin-left:20px;">';

$SubItemObj = $SQLClient->getHbnObj("SubItemObj");

if ($SubItemObj) {
	$code = '$SubItemObj = $SQLClient->getHbnObj("SubItemObj");
$class = get_class($SubItemObj);';
	$output = "SubItemObj class: ".get_class($SubItemObj) . "\n\n" . print_r($SubItemObj, true);
	printTest("ItemObj", $code, $output, "one-line", "");
	
	$attributes = array("item_id" => $item_id, "title" => "hibernate subitem of item $item_id");
	$status = $SubItemObj->insert($attributes);
	$new_id = $SubItemObj->getInsertedId();
	$code = '$attributes = array("item_id" => $item_id, "title" => "hibernate subitem of item $item_id");
$status = $SubItemObj->insert($attributes);
$new_id = $SubItemObj->getInsertedId();';
	$output = "status: $status\nnew_id: $new_id";
	printTest("Insert new sub-item in item $item_id", $code, $output, "short", "one-line");
	
	$result = $ItemObj->findRelationships(array("pk_id" => $item_id));
	$code = '$result = $ItemObj->findRelationships(array("pk_id" => $item_id));';
	$output = "findRelationships result: \n" . print_r($result, true);
	printTest("Get relationships for item $item_id (from cache)", $code, $output, "one-line", "");
	
	$result = $ItemObj->findRelationships(array("pk_id" => $item_id), array("no_cache" => true));
	$code = '$result = $ItemObj->findRelationships(array("pk_id" => $item_id), array("no_cache" => true));';
	$output = "findRelationships result: \n" . print_r($result, true);
	printTest("Get relationships for item $item_id (without cache)", $code, $output, "one-line", "");
	
	$sql = $SubItemObj->callSelectSQL("select_all_by_item", array("item_id" => $item_id));
	$result = $SubItemObj->callSelect("select_all_by_item", array("item_id" => $item_id));
	$code = '$sql = $SubItemObj->callSelectSQL("select_all_by_item", array("item_id" => $item_id));
$result = $SubItemObj->callSelect("select_all_by_item", array("item_id" => $item_id));';
	$output = "result: \n" . print_r($result, true);
	printTest("Select sub-items from item $item_id by calling a query from the SubItemObj object", $code, $output, "one-line");
	
	$result = $ItemObj->findRelationship("sub_items", array("pk_id" => $item_id));
	$code = '$result = $ItemObj->findRelationship("sub_items", array("pk_id" => $item_id));';
	$output = "findRelationship 'sub_items' result: \n" . print_r($result, true);
	printTest("Select relationship 'sub-items' from item $item_id", $code, $output, "one-line", "");
	
	$result = $ItemObj->find(array(
		"CONDITIONS" =>  array("status" => 1),
		"sort" =>  array(array("COLUMN" => "pk_id", "ORDER" => "DESC"), array("column" => "status", "order" => "asc")),
		"start" =>  0,
		"LIMIT" =>  5,
		"attributes" => array("name", "pk_id", "status"),
		"RELATIONSHIPS" => true
		 )
	);
	//$result = $ItemObj->find();
	//$result = $ItemObj->find(array("LIMIT" =>  5, "start" =>  4));
	$code = '$result = $ItemObj->find(array(
	"CONDITIONS" =>  array("status" => 1),
	"sort" =>  array(array("COLUMN" => "pk_id", "ORDER" => "DESC"), array("column" => "status", "order" => "asc")),
	"start" =>  0,
	"LIMIT" =>  5,
	"attributes" => array("name", "pk_id", "status"),
	"RELATIONSHIPS" => true
	 )
);';
	$output = "find result: \n" . print_r($result, true);
	printTest("Select first 5 items with 'status' = 1", $code, $output, "short", "");
	
	$result = $SubItemObj->find(array("CONDITIONS" =>  array("id" => $new_id) ) );
	$code = '$result = $SubItemObj->find(array("CONDITIONS" =>  array("id" => $new_id) ) );';
	$output = "find result: \n" . print_r($result, true);
	printTest("Select sub-item '$new_id' - before deletion", $code, $output, "one-line", "");
	
	$status= $SubItemObj->delete($new_id);
	$code = '$status= $SubItemObj->delete($new_id);';
	$output = "status: $status";
	printTest("Delete sub-item '$new_id'", $code, $output, "one-line", "one-line");
	
	$result = $SubItemObj->find(array("CONDitions" =>  array("id" => $new_id) ) );
	$code = '$result = $SubItemObj->find(array("CONDitions" =>  array("id" => $new_id) ) );';
	$output = "find result: \n" . print_r($result, true);
	printTest("Select cached sub-item '$new_id' - after deletion", $code, $output, "one-line");
	
	$result = $SubItemObj->find(array("conDITIons" =>  array("id" => $new_id) ), array("no_cache" => true) );
	$code = '$result = $SubItemObj->find(array("conDITIons" =>  array("id" => $new_id) ), array("no_cache" => true) );';
	$output = "find result: \n" . print_r($result, true);
	printTest("Select DB sub-item '$new_id' - after deletion", $code, $output, "one-line");
}

echo '</div>';
?>
