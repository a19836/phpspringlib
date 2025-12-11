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
//$BeanFactory->setCacheRootPath( sys_get_temp_dir() . "/cache/spring/odi/" ); //optional: set cache to be faster

$external_vars = array(
	"my_name" => $my_name,
	"app_path" => $app_path,
	//"GLOBALS" => $GLOBALS,
	//other vars... You can also add here $_GET, $_POST, $GLOBALS, etc...
);
$BeanFactory->init(array(
	"file" => __DIR__ . "/assets/beans.xml", 
	"external_vars" => $external_vars,
	"settings" => array(
		array( //Bean Person
			"bean" => array(
				"name" => "PersonX",
				"path" => "Person",
				"path_prefix" => __DIR__ . "/assets/",
				"constructor_args" => array(
					array("value" => "Female"),
					//other arguments
				),
				"properties" => array(
					array("name" => "age", "value" => "31"),
					//other properties
				),
				"functions" => array(
					array("name" => "eat", "parameters" => array(
						array("value" => "orange")
					)),
					//other functions
				)
			)
		)
		//other beans...
	)
));

//append new objects to existing factory
$BeanFactory->addObjects(array(
	"Product" => (object) array('name' => 'Coca Cola', 'type' => 'Light', 'flavour' => 'Lemon'),
	//other objects...
));

echo '<h1>PHP Spring Lib - ODI</h1>
<p>PHP ODI - Object Dependency Injection.</p>
<div class="note">
		<span>
		Learn how to use ODI - Object Dependency Injection - in PHP, through this library, by creating xml beans that point to PHP classes.<br/>
		For more details please check the files in \'examples/odi\' folder.
		</span>
</div>';

echo '
<h3>Some Available BeanFactory Methods:</h3>
<div class="code input">
	<textarea readonly>
$BeanFactory = new BeanFactory();
$BeanFactory->setCacheRootPath( sys_get_temp_dir() . "/cache/spring/" ); //optional: set cache to be faster

$data = array(
	"external_vars" => null, //optional associative array with key-value items
	"file" => "bean.xml", //file path with xml beans
	"settings" => null, //optional array with associative arrays inside, with the following keys: "import", "bean", "var" and "function".
);
$BeanFactory->init($data); //init factory based in $data
$BeanFactory->add($data); //add to factory based in $data
$BeanFactory->reset(); //clean saved data

$BeanFactory->getSettingsFromFile($file_path); //return settings after parsing xml file
$BeanFactory->getBeansFromSettings($settings, &$sort_elements = false); //return beans after parsing xml file

$BeanFactory->initObjects(); //init all parsed objects
$BeanFactory->initObject($bean_name, $launch_exception = true); //init a specific object
$BeanFactory->initFunction($function, $launch_exception = true); //init a specific function

$BeanFactory->addBeans($beans); //add new beans
$BeanFactory->getBeans(); //get parsed beans
$BeanFactory->getBean($bean_name); //get a specific beans

$BeanFactory->addObjects($objs); //add new objects
$BeanFactory->getObjects(); //get initialized objects
$BeanFactory->getObject($obj_name); //get a specific initialized object

$BeanFactory->setCacheRootPath($dir_path); //set cache folder path, so next time it does not need to parse the xml file
$BeanFactory->setCacheHandler(XmlSettingsCacheHandler $XmlSettingsCacheHandler); //set a different cache engine
	</textarea>
</div>

<h3>Examples:</h3>';

//print initial code
$code = '$BeanFactory = new BeanFactory();
$BeanFactory->setCacheRootPath( sys_get_temp_dir() . "/cache/spring/" ); //optional: set cache to be faster

$BeanFactory->init(array(
	"file" => __DIR__ . "/assets/beans.xml", 
	"external_vars" => $external_vars,
	"settings" => array(
		array( //Bean Person
			"bean" => array(
				"name" => "PersonX",
				"path" => "Person",
				"path_prefix" => __DIR__ . "/assets/",
				"constructor_args" => array(
					array("value" => "Female"),
					//other arguments
				),
				"properties" => array(
					array("name" => "age", "value" => "31"),
					//other properties
				),
				"functions" => array(
					array("name" => "eat", "parameters" => array(
						array("value" => "orange")
					)),
					//other functions
				)
			)
		)
		//other beans...
	)
));

//append new objects to existing factory
$BeanFactory->addObjects(array(
	"Product" => (object) array("name" => "Coca Cola", "type" => "Light", "flavour" => "Lemon"),
	//other objects...
));';
echo '<div>
	<h4>Start Bean Factory:</h4>
	<div class="code input">
		<textarea readonly>' . $code . '</textarea>
	</div>
	<div class="code xml">
		<textarea readonly>' . file_get_contents(__DIR__ . "/assets/beans.xml") . '</textarea>
	</div>
</div>

<h4>Get Beans Objects:</h4>';

//init individual objects and print them
$BeanFactory->initObject("PersonX", $launch_exception = true);
$Person = $BeanFactory->getObject("PersonX");
$code = '$BeanFactory->initObject("PersonX", $launch_exception = true);
$Person = $BeanFactory->getObject("PersonX");
echo $Person->toString();';
printTest("PersonX", $code, $Person->toString());

$BeanFactory->initObject("Person1");
$Person = $BeanFactory->getObject("Person1");
$code = '$BeanFactory->initObject("Person1");
$Person = $BeanFactory->getObject("Person1");
echo $Person->toString();';
printTest("Person1", $code, $Person->toString());

$BeanFactory->initObject("Person2");
$Person = $BeanFactory->getObject("Person2");
$code = '$BeanFactory->initObject("Person2");
$Person = $BeanFactory->getObject("Person2");
echo $Person->toString();';
printTest("Person2", $code, $Person->toString());

//init all objects
$BeanFactory->initObjects();
printCode('Init all objects', '$BeanFactory->initObjects();
//we do NOT need to call anymore the \'$BeanFactory->initObject("XXX")\'');

//print other objects
$Person = $BeanFactory->getObject("Person3");
$code = '$Person = $BeanFactory->getObject("Person3");
echo $Person->toString();';
printTest("Person3", $code, $Person->toString(), "short", "");

$Person = $BeanFactory->getObject("Person4");
$code = '$Person = $BeanFactory->getObject("Person4");
echo $Person->toString();';
printTest("Person4", $code, $Person->toString(), "short", "");

$Person = $BeanFactory->getObject("Person5");
$code = '$Person = $BeanFactory->getObject("Person5");
echo $Person->toString();';
printTest("Person5", $code, $Person->toString());

$Child = $BeanFactory->getObject("Child1");
$code = '$Child = $BeanFactory->getObject("Child1");
echo $Child->toString();';
printTest("Child1", $code, $Child->toString());

$Child = $BeanFactory->getObject("Child2");
$code = '$Child = $BeanFactory->getObject("Child2");
echo $Child->toString();';
printTest("Child2", $code, $Child->toString(), "short", "");

$Child = $BeanFactory->getObject("Child3");
$code = '$Child = $BeanFactory->getObject("Child3");
echo $Child->toString();';
printTest("Child3", $code, $Child->toString(), "short", "");

echo '<hr style="margin-top:40px;"/>
<h4>Parsed Vars/Objects/Beans:</h4>';

//print vars
$code = 'echo $BeanFactory->getObject("female_gender");
echo $BeanFactory->getObject("male_gender");
//etc...';
$output =  "- female_gender: " . $BeanFactory->getObject("female_gender") . "\n"
		 . "- male_gender: " . $BeanFactory->getObject("male_gender") . "\n"
		 . "- food: " . print_r($BeanFactory->getObject("food"), true) . "\n"
		 . "- props: " . print_r($BeanFactory->getObject("props"), true);
printTest('Loaded Vars from BeanFactory', $code, $output, "short", "");

//print all objects and vars
$objects = $BeanFactory->getObjects();
printTest('Loaded Objects from BeanFactory', '$objects = $BeanFactory->getObjects();', print_r($objects, true), "short", "");

//print all beans
$beans = $BeanFactory->getBeans();
printTest('Loaded Beans from BeanFactory', '$beans = $BeanFactory->getBeans();', print_r($beans, true), "short", "");
?>
