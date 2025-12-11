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

include_once get_lib("util.text.TextSanitizer");

class PHPScriptHandler {
	
	public static function parseContent($content, &$external_vars = array(), &$return_values = array(), $ignore_undefined_vars_error = false) {
		//echo "content:!$content!<br>\n";
		//echo "<pre>PHPScriptHandler external_vars: ".print_r($external_vars, 1)."</pre>\n";
		
		$set_error_handler = empty($GLOBALS["ignore_undefined_vars_errors"]) && $ignore_undefined_vars_error;
		
		if ($set_error_handler)
			set_error_handler("ignore_undefined_var_error_handler", E_WARNING);
		
		//create vars based in the $external_vars
		$external_vars_backup_var_with_unique_name_that_does_not_enter_in_conflict_with_content = $external_vars;
		$global_keys_inside_of_globals = array("EVC", "_GET", "_POST", "_REQUEST", "_FILES", "_COOKIE", "_ENV", "_SERVER", "_SESSION");
		
		if (is_array($external_vars)) {
			//first globals then local vars
			if (isset($external_vars["GLOBALS"]) && is_array($external_vars["GLOBALS"])) //GLOBALS var cannot be replaced as a whole var. We can only change the inner vars.
				foreach ($external_vars["GLOBALS"] as $k => $v)
					if (!in_array($k, $global_keys_inside_of_globals))
						$GLOBALS[$k] = $v;
			
			foreach ($external_vars as $var_name => $var_value) 
				if ($var_name && $var_name != "GLOBALS") {
					//echo '$' . $var_name . ' = $var_value;'."\n<br>";
					eval('$' . $var_name . ' = $var_value;');
				}
		}
		
		//execute php code
		$start_delimiters = array("<?", "<?php", "<?=");
		$end_delimiters = array("?>");
		
		$new_content = $content;
		$offset = 0;
		$eval_runned = false;
		
		do {
			$exists = false;
	
			$start_delimiter_index = false;
			$end_delimiter_index = false;
			$start_delimiter_pos = false;
			$end_delimiter_pos = false;

			$t = count($start_delimiters);
			for ($i = 0; $i < $t; $i++) {
				$delimiter = $start_delimiters[$i];
				$pos = strpos($content, $delimiter, $offset);
			
				if($pos !== false && ($pos <= $start_delimiter_pos || $start_delimiter_pos === false)) {
					$next_char = substr($content, $pos + strlen($delimiter), 1);
					if($next_char == " " || $next_char == "(" || $next_char == "\$" || $next_char == "\n" || preg_match("/\s/", $next_char)) {
						$start_delimiter_index = $i;
						$start_delimiter_pos = $pos;
						$exists = true;
					}
				}
			}
		
			$t = count($end_delimiters);
			for ($i = 0; $i < $t; $i++) {
				$delimiter = $end_delimiters[$i];
				$pos = strpos($content, $delimiter, $start_delimiter_pos);
				
				if($pos !== false && ($pos <= $end_delimiter_pos || $end_delimiter_pos === false)) {
					$open_double_quotes = false;
					$open_single_quotes = false;
					for($j = $start_delimiter_pos + 1; $j < $pos; $j++) {
						if($content[$j] == '"' && !TextSanitizer::isCharEscaped($content, $j))
							$open_double_quotes = !$open_double_quotes;
						elseif($content[$j] == "'" && !TextSanitizer::isCharEscaped($content, $j))
							$open_single_quotes = !$open_single_quotes;
					}
					
					if(!$open_double_quotes && !$open_single_quotes) {
						$end_delimiter_index = $i;
						$end_delimiter_pos = $pos;
					}
				}
			}
	
			if (!is_numeric($end_delimiter_index)) {
				$end_delimiter_index = 0;
				$end_delimiter_pos = strlen($content);
				$end_delimiter = $end_delimiters[0];
				
				$offset = strlen($content);
			}
			else {
				$end_delimiter = $end_delimiters[$end_delimiter_index];
				
				$offset = $end_delimiter_pos + strlen($end_delimiter);
			}
			
			if ($exists && is_numeric($start_delimiter_index)) {
				$start_delimiter = $start_delimiters[$start_delimiter_index];
		
				$end = $end_delimiter_pos + strlen($end_delimiter);
				$code_to_search = substr($content, $start_delimiter_pos, $end - $start_delimiter_pos);
				
				$start = $start_delimiter_pos + strlen($start_delimiter);
				$code_to_replace = substr($content, $start, $end_delimiter_pos - $start);
				
				if($start_delimiter == "<?=") {
					$code_to_replace = "echo " . $code_to_replace;
					
					if(substr(trim($code_to_replace), strlen(trim($code_to_replace)) - 1) != ";")
						$code_to_replace .= ";";
				}
				
				//echo "code_to_replace:!$code_to_replace!<br>\n";
				//error_log($code_to_replace . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
				
				ob_start(null, 0);
				
				try {
					//error_log("$start_delimiter_pos:$end_delimiter_pos => ".substr($code_to_search, 0, 100)."\n", 3, TMP_PATH . "/error_log_index");
					$eval_runned = true;
					
					$return_values[] = eval($code_to_replace);
				}
			   catch (Error $e) {
			   	self::debugError($e, $code_to_replace, "parseContent", "PHP error");
			   }
				catch(ParseError $e) {
			   	self::debugError($e, $code_to_replace, "parseContent", "Parse error");
				}
				catch(ErrorException $e) {
			   	self::debugError($e, $code_to_replace, "parseContent", "Error exception");
				}
				catch(Exception $e) {
			   	self::debugError($e, $code_to_replace, "parseContent", "Exception");
				}
				
				$code_to_replace = ob_get_contents();
				ob_end_clean();
				
				$new_content = str_replace($code_to_search, $code_to_replace, $new_content);
				
				//$code_to_search != $code_to_replace && error_log($code_to_replace."\n\n\n", 3, TMP_PATH . "/error_log_code");
			}
		}
		while($exists && $offset < strlen($content));
		
		//update external vars with the potential changed values
		$external_vars = $external_vars_backup_var_with_unique_name_that_does_not_enter_in_conflict_with_content;
		
		if ($eval_runned && is_array($external_vars)) {
			//in case the code_to_replace runned in the eval has some references
			unset($var_name);
			unset($var_value);
			unset($k);
			unset($v);
			
			//update external vars - first globals then local vars
			if (isset($external_vars["GLOBALS"]) && is_array($external_vars["GLOBALS"]))
				foreach ($external_vars["GLOBALS"] as $k => $v)
					if (!in_array($k, $global_keys_inside_of_globals))
						$external_vars["GLOBALS"][$k] = $GLOBALS[$k];
			
			foreach($external_vars as $var_name => $var_value)
				if ($var_name && $var_name != "GLOBALS") {
					eval('$external_vars["'. $var_name . '"] = $' . $var_name . ';'); //DO NOT DO THIS: $external_vars[$var_name] = ${$var_name};', OTHERWISE IF THE VAR_NAME == _POST OR _GET, IT WON'T WORK!!!
					
					//update globals too, otherwise the next time we call this function in the some logic flow, the Globals will have the original values.
					if (in_array($var_name, $global_keys_inside_of_globals) && isset($external_vars["GLOBALS"][$var_name]))
						$external_vars["GLOBALS"][$var_name] = $external_vars[$var_name];
				}
			
			//error_log("code_to_search:$code_to_search\nexternal_vars POST:".print_r($external_vars["_POST"], 1).print_r($_POST, 1)."\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
		}
		
		if ($set_error_handler)
			restore_error_handler();
		
		return $new_content;
	}
	
	private static function debugError($e, $code, $func, $type) {
		$traces = self::getTracesAsString();
		$code_traces = $e->getTraceAsString();
		$code = self::getCodeConfiguredForLogging($code);
		
		$traces = str_replace("\n", "\n\t", $traces);
		$code_traces = str_replace("\n", "\n\t", $code_traces);
		$code = str_replace("\n", "\n\t", $code);
		
		debug_log("[PHPScriptHandler::$func] $type: " . $e->getMessage() . "\n   In file " . $e->getFile() . ":" . $e->getLine() . "\n   With code traces:\n\t" . $code_traces . "\n\n   With code:\n\t" . $code . "\n   With PHPScriptHandler traces:\n\t" . $traces, "error"); //should be error and not exception, bc if exception it will add an extra level of back trace which is not user friendly.
	}
	
	private static function getCodeConfiguredForLogging(&$code) {
		$new_code = "";
		$lines = explode("\n", $code);
		foreach ($lines as $i => $line) 
			$new_code .= "line" . ($i + 1) . ": " . $line . "\n"; 
		
		return $new_code;
	}
	
	private static function getTracesAsString($ignore_traces = 1, $max_arg_len = null) {
		$str = "";
		
		//Note that the args will contain the same information then the $code, so we must omit this so the log doesn't get too confused.
		$traces = debug_backtrace();
		//$traces = self::getTracesAsString(debug_backtrace(3)); //DEPRECATED bc we filter the $code in the code below. debug_backtrace(3) or debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS): Populate index "object" and omit index "args". 
		
		$ignore_traces = $ignore_traces > 0 ? $ignore_traces : 0;
				
		if ($traces)
			foreach($traces as $i => $trace) {
				if ($i < $ignore_traces)
					continue;
				
				/**
				* THIS IS NEEDED! If all your objects have a __toString function it's not needed!
				* 
				* Catchable fatal error: Object of class B could not be converted to string
				* Catchable fatal error: Object of class A could not be converted to string
				* Catchable fatal error: Object of class B could not be converted to string
				*/
				if (isset($trace["object"]) && is_object($trace["object"]))
					$trace["object"] = /*"CONVERTED OBJECT OF CLASS " . */get_class($trace["object"]);
				
				if (isset($trace["args"]) && is_array($trace["args"]))
					foreach ($trace["args"] as &$arg)
						if (is_object($arg)) 
							$arg = /*"CONVERTED OBJECT OF CLASS " . */get_class($arg);
				
				$type = isset($trace["type"]) ? $trace["type"] : "";
				$object = isset($trace["object"]) ? $trace["object"] : "";
				$function = isset($trace["function"]) ? $trace["function"] : "";
				$args = isset($trace["args"]) ? $trace["args"] : "";
				
				if (is_array($args)) {
					foreach($args as $j => $v) {
						if (is_null($v))
							$v = 'null';
						else if (is_array($v))
							$v = 'Array['.sizeof($v).']';
						else if (is_object($v)) 
							$v = 'Object:'.get_class($v);
						else if (is_bool($v)) 
							$v = $v ? 'true' : 'false';
						else if ($j == 0 && is_string($v) && !$object && $function == "parseContent")
							$v = '$executed_php_code';
						else if ($j == 1 && is_string($v) && !$object && $function == "debugError")
							$v = '$executed_php_code';
						else { 
							$v = (string) @$v;
							
							if ($max_arg_len) {
								$aux = htmlspecialchars(substr($v, 0, $max_arg_len));
								
								if (strlen($v) > $max_arg_len) 
									$aux .= '...';
								
								$v = $aux;
							}
						}
						
						$args[$j] = $v;
					}
					
					$args = implode(', ', $args);
				}
				
				$str .= "#" . ($i - $ignore_traces) . " " . $trace["file"] . "(" . $trace["line"] . ") ";
				$str .= $object ? $object . $type : "";
				$str .= $function . "(" . $args . ")\n";
			}
		
		return $str;
	}
}
?>
