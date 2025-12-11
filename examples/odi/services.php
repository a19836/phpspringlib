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

echo $style;

$BeanFactory = new BeanFactory();
//$BeanFactory->setCacheRootPath( sys_get_temp_dir() . "/cache/spring/" );

$external_vars = array(
	"app_path" => $app_path
);
$BeanFactory->init(array(
	"file" => __DIR__ . "/assets/beans.xml", 
	"external_vars" => $external_vars
));

//extend beans factory
$services = parseServicesFile(__DIR__ . "/assets/sub_folder/services.xml", $external_vars, $BeanFactory);


echo '<h1>PHP Spring Lib - ODI with services</h1>
<p>Extend PHP ODI to allow services.</p>
<div class="note">
		<span>
		Learn how to extend this ODI library, by creating your own xml nodes.<br/>
		In this example we create the \'service\' nodes that point to functions or methods from bean objects.
		</span>
</div>';

echo '
<h3>Some Extended Methods:</h3>
<div class="code short input">
	<textarea readonly>
$services = parseServicesFile(__DIR__ . "/assets/sub_folder/services.xml", $external_vars, $BeanFactory);
$obj = getServiceConstructorObject($services["init_default_person"], $BeanFactory);
callService($services["init_default_person"], $parameters, $obj);
	</textarea>
</div>

<h3>Examples:</h3>';

$code = '$BeanFactory = new BeanFactory();

$external_vars = array(
	"app_path" => $app_path
);
$BeanFactory->init(array(
	"file" => __DIR__ . "/assets/beans.xml", 
	"external_vars" => $external_vars
));

//extend beans factory
$services = parseServicesFile(__DIR__ . "/assets/sub_folder/services.xml", $external_vars, $BeanFactory);';
echo '<div>
	<h4>Start Bean Factory and extend it with some services:</h4>
	<div class="code input">
		<textarea readonly>' . $code . '</textarea>
	</div>
	<div class="code xml">
		<textarea readonly>' . file_get_contents(__DIR__ . "/assets/sub_folder/services.xml") . '</textarea>
	</div>
</div>';

$bean = $BeanFactory->getBean("PersonService");
echo '<div>
	<h4>Get PersonService Bean:</h4>
	<div class="code short input">
		<textarea readonly>$bean = $BeanFactory->getBean("PersonService");
print_r($bean);</textarea>
	</div>
	<div class="code output input">
		<textarea readonly>' . print_r($bean, true) . '</textarea>
	</div>
</div>';

echo '<h4>Get Services:</h4>';

printCode("Loaded services", print_r($services, true), "");

//call services and print them
$obj = getServiceConstructorObject($services["init_default_person"], $BeanFactory);
callService($services["init_default_person"], null, $obj);
$code = '$obj = getServiceConstructorObject($services["init_default_person"], $BeanFactory);
callService($services["init_default_person"], null, $obj);
echo $obj->toString();';
printTest("Call service 'init_default_person'", $code, $obj->toString());

callService($services["define_food"], $BeanFactory->getObject("food"), $obj);
$code = 'callService($services["define_food"], $BeanFactory->getObject("food"), $obj);
echo $obj->toString();';
printTest("Call service 'define_food' using the same Person object of the service 'init_default_person'", $code, $obj->toString());


$obj = getServiceConstructorObject($services["define_age"], $BeanFactory);
callService($services["define_age"], 56, $obj);
$code = '$obj = getServiceConstructorObject($services["define_age"], $BeanFactory);
callService($services["define_age"], 56, $obj);
echo $obj->toString();';
printTest("Call service 'define_age'", $code, $obj->toString());

$result = callService($services["test"], "test", "JP");
$code = '$result = callService($services["test"], "test", "JP");';
printTest("Call service 'test'", $code, "result: $result");

/* FUNCTIONS used in services.xml */

function foo($a) {
	return "return '$a' successfully";
}

/* EXTENDING SERVICES FUNCTIONS */

function callService($service, $arguments = null, $obj = null) {
	$result = null;
	
	if ($service) {
		$constructor = $service["constructor"];
		$function = $service["function"];
		
		if ($function) {
			if ($arguments === null)
				$arguments = array();
			else if (!is_array($arguments))
				$arguments = array($arguments);
			
			if ($constructor) {
				if (!$obj)
					launch_exception( new Exception("No obj passed as argument for the constructor '$constructor'!") );
				else if (!method_exists($obj, $function))
					launch_exception( new Exception("No method '$function' for class '" . get_class($obj) . "'!") );
				else
					$result = call_user_func_array(array($obj, $function), $arguments);
			}
			else if (!function_exists($function))
				launch_exception( new Exception("No function '$function' defined!") );
			else
				$result = call_user_func_array($function, $arguments);
		}	
	}
	
	return $result;
}

function getServiceConstructorObject($service, $BeanFactory) {
	if ($service) {
		$constructor = $service["constructor"];
		
		if ($constructor) {
			if ($BeanFactory->getBean($constructor)) {
				$obj = $BeanFactory->getObject($constructor);
				
				if(!$obj) {
					$BeanFactory->initObject($constructor);
					$obj = $BeanFactory->getObject($constructor);
				}
					
				if ($obj)
					return $obj;
				else
					launch_exception( new Exception("No object for constructor '$constructor'!") );
			}
			else
				launch_exception( new Exception("No constructor defined in beans!") );
		}
		else
			launch_exception( new Exception("service does NOT have any constructor defined!") );
	}
	else
		launch_exception( new Exception("No service defined!") );
	
	return null;
}

function parseServicesFile($services_file_path, $external_vars, &$BeanFactory) {
	//get beans
	$BeanSettingsFileFactory = new BeanSettingsFileFactory();
	$beans = $BeanSettingsFileFactory->getSettingsFromFile($services_file_path, $external_vars);
	//echo "asd<pre>";print_r($beans);
	
	//get services
	$content = file_get_contents($services_file_path);
	$xml_schema_file_path = get_lib("xmlfile.schema.beans", "xsd");
	$nodes = XMLFileParser::parseXMLContentToArray($content, $external_vars, $services_file_path, $xml_schema_file_path);
	
	$first_node_name = is_array($nodes) ? array_keys($nodes) : array();
	$first_node_name = isset($first_node_name[0]) ? $first_node_name[0] : null;
	
	$services_node = isset($nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"]) && is_array($nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"]) ? $nodes[$first_node_name][0]["childs"]["services"][0]["childs"]["service"] : array();
	$services = array();
	$t = $services_node ? count($services_node) : 0;
	
	for($i = 0; $i < $t; $i++) {
		$service_node = $services_node[$i];
		
		$id = XMLFileParser::getAttribute($service_node, "id");
		$constructor = XMLFileParser::getAttribute($service_node, "constructor");
		$function = XMLFileParser::getAttribute($service_node, "function");
		
		$services[$id] = array(
			"constructor" => $constructor,
			"function" => $function
		);
	}
	
	//add beans to the existing BeanFactory
	if ($beans)
		$BeanFactory->add(array(
			"settings" => $beans
		));
	
	//return services
	return $services;
}
?>
