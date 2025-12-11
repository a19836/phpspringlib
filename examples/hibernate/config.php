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

$host = "localhost";
$username = "root";
$password = "portugal";
$db_name = "test";

$app_path = __DIR__ . "/";

$style .= '<style>
.code.short textarea {height:120px;}
.code.output.short textarea {height:180px;}
.code.one-line textarea {height:60px;}

.error {margin:20px 0; text-align:center; color:red;}
</style>';

function createTable($DBBroker) {
	$sql = file_get_contents(__DIR__ . "/assets/init_db.sql");
	return $DBBroker->setSQL($sql);
}
?>
