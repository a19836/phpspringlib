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

include_once __DIR__ . "/examples/config.php";

echo $style;
?>
<h1>PHP Spring Lib</h1>
<p>Conversion of Java <b>Spring Architecture</b> to PHP - with <b>iBatis</b> and <b>Hibernate</b> features.</p>
<div class="note">
		<span>
		This library brings core concepts of the Java Spring ecosystem into PHP, using XML-based bean configuration.<br/>
		It includes <b>ODI (Object Dependency Injection)</b>, <b>iBatis-style SQL mapping</b>, and <b>Hibernate-inspired ORM</b>, all configured through XML files.<br/>
		With this XML-style library, you can create your own XML bean definitions that map directly to PHP classes and other things.<br/>
		<br/>
		The goal of this library is to provide a PHP architecture similar to Java Spring, enriched with Beans-style ODI, iBatis-style query mappings and Hibernate-like ORM behavior, enabling PHP developers to create decoupled, modular, and scalable applications.<br/>
		<br/>
		You will learn how to use ODI in PHP, by creating xml beans that point to PHP classes, iBatis-style queries in xml nodes and Hibernate ORM objects.
		</span>
</div>
<div style="text-align:center;">
	<div style="display:inline-block; text-align:left;">
		<div>Some tutorials:</div>
		<ul>
			<li><a href="examples/odi/" target="odi">ODI - Object Dependency Injection</a></li>
			<li><a href="examples/odi/services.php" target="odi_services">Extending Spring ODI</a></li>
			<li><a href="examples/ibatis/" target="ibatis">Spring iBatis</a></li>
			<li><a href="examples/ibatis/services.php" target="ibatis_services">Extending Spring iBatis</a></li>
			<li><a href="examples/hibernate/" target="hibernate">Spring Hibernate</a></li>
			<li><a href="examples/hibernate/services.php" target="hibernate_services">Extending Spring Hibernate</a></li>
		</ul>
	</div>
</div>

<div>
	<h5>ODI usage sample:</h5>
	<div class="code">
		<textarea readonly>
//init bean factory
$BeanFactory = new BeanFactory();
$BeanFactory->init(array(
	"file" => __DIR__ . "/examples/odi/assets/beans.xml"
));
$BeanFactory->initObjects();

//call bean objects
$BeanObj = $BeanFactory->getObject("MyBeanId");

//call methods defined in the correspondent classes of the bean object
$BeanObj->foo();

//more examples at: examples/odi/index.php
		</textarea>
	</div>
</div>

<div>
	<h5>iBatis usage sample:</h5>
	<div class="code">
		<textarea readonly>
//init DB connection
$DBBroker = new MySQLDBBroker($host, $username, $password, $db_name);

//init iBatis engine
$SQLClient = new IBatisClient();
$SQLClient->setRDBBroker($DBBroker);
$SQLClient->loadXML( __DIR__ . "/examples/ibatis/assets/item.xml");

//call an iBatis query and execute it
$query = $SQLClient->getQuery("select", "select_item");
$result = $SQLClient->execQuery($query, array("item_id" => 1));
$ItemTest = $result[0];
$id = $ItemTest->getId();
$title = $ItemTest->getTitle();

//more examples at: examples/ibatis/index.php
		</textarea>
	</div>
</div>

<div>
	<h5>Hibernate usage sample:</h5>
	<div class="code">
		<textarea readonly>
//init DB connection
$DBBroker = new MySQLDBBroker($host, $username, $password, $db_name);

//init Hibernate engine
$SQLClient = new HibernateClient();
$SQLClient->setRDBBroker($DBBroker);
$SQLClient->loadXML( __DIR__ . "/examples/hibernate/assets/item_subitem.xml");

//call an Hibernate object
$ItemObj = $SQLClient->getHbnObj("ItemObj");
$result = $ItemObj->findById(1);
$results = $ItemObj->find();

//more examples at: examples/hibernate/index.php
		</textarea>
	</div>
</div>


