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

include_once dirname(__DIR__) . "/lib/app.php";
include get_lib("bean.BeanFactory");

//SET SOME STYLING
$style = '<style>
h1 {margin-bottom:0; text-align:center;}
h3 {font-size:1.4em; margin:40px 0 0; font-weight:bold;}
h4 {font-size:1.2em; margin:40px 0 0; font-weight:bold;}
h5 {font-size:1em; margin:40px 0 0; font-weight:bold;}
p {margin:0 0 20px; text-align:center;}

.note {text-align:center;}
.note span {text-align:center; margin:0 20px 20px; padding:10px; color:#aaa; border:1px solid #ccc; background:#eee; display:inline-block; border-radius:3px;}

.code {display:block; margin:10px 0; padding:0; background:#eee; border:1px solid #ccc; border-radius:3px; position:relative;}
.code:before {content:"php"; position:absolute; top:5px; left:5px; display:block; font-size:80%; opacity:.5;}
.code.output:before {content:"output";}
.code.xml:before {content:"xml";}
.code textarea {width:100%; height:300px; padding:30px 10px 10px; display:inline-block; background:transparent; border:0; resize:vertical; font-family:monospace;}
.code.short textarea {height:80px;}
.code.output.short textarea {height:120px;}
</style>';

function printTest($title, $code, $result, $input_class = "short", $output_class = "short") {
	echo '
<div class="test">
	<h5>' . $title . ':</h5>
	<div class="code ' . $input_class . ' input">
		<textarea readonly>' . $code . '</textarea>
	</div>
	<div class="code ' . $output_class . ' output">
		<textarea readonly>' . $result . '</textarea>
	</div>
</div>';
}

function printCode($title, $code, $class = "short") {
	echo '
<div class="test">
	<h5>' . $title . ':</h5>
	<div class="code ' . $class . ' input">
		<textarea readonly>' . $code . '</textarea>
	</div>
</div>';
}
?>
