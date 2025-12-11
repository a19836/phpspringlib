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

class MyArray {
	
	public static function removeRepeatedValues(&$arr) {
		if ($arr) {
			$count = count($arr);
			for($i = 0; $i < $count; $i++)
				if(array_search($arr[$i], $arr) != $i) {
					if($i < $count - 1) {
						$aux = $arr[$count - 1];
						$arr[$count - 1] = $arr[$i];
						$arr[$i] = $aux;
					}
					
					array_pop($arr);
					--$i;
					--$count;
				}
		}
	}
	
	public static function arrKeysToLowerCase(&$arr, $recursely = false) {
		self::arrKeysToCase($arr, CASE_LOWER, $recursely);
	}
	
	public static function arrKeysToUpperCase(&$arr, $recursely = false) {
		self::arrKeysToCase($arr, CASE_UPPER, $recursely);
	}
	
	private static function arrKeysToCase(&$arr, $case = CASE_LOWER, $recursely = false) {
		if(is_array($arr)) {
			$arr = array_change_key_case($arr, $case);
			
			if ($recursely) {
				foreach ($arr as $key => $value) {
					if (is_array($value)) {
						self::arrKeysToCase($arr[$key], $case, true);
					}
				}
			}
		}
	}
	
	public static function sort($arr, $flag = false) {
		sort($arr, $flag);
		return $arr;
	}
	
	/** Takes:
	*        $data,  multidim array
	*        $keys,  array(array(key=>col1, sort=>desc, case_sensitive=>1), array(key=>col2, type=>numeric))
	*
		###############

		// Example Data

		$_DATA['table1'][] = array("name" => "Sebastian", "age" => 18, "male" => true);
		$_DATA['table1'][] = array("name" => "Lawrence",  "age" => 16, "male" => true);
		$_DATA['table1'][] = array("name" => "Olivia",    "age" => 10, "male" => false);
		$_DATA['table1'][] = array("name" => "Dad",       "age" => 50, "male" => true);
		$_DATA['table1'][] = array("name" => "Mum",       "age" => 40, "male" => false);
		$_DATA['table1'][] = array("name" => "Sebastian", "age" => 56, "male" => true);
		$_DATA['table1'][] = array("name" => "Lawrence",  "age" => 19, "male" => true);
		$_DATA['table1'][] = array("name" => "Olivia",    "age" => 24, "male" => false);
		$_DATA['table1'][] = array("name" => "Dad",       "age" => 10, "male" => true);
		$_DATA['table1'][] = array("name" => "Mum",       "age" => 70, "male" => false);

		###############

		$res=MyArray::multisort($_DATA['table1'], array(array('key'=>'name', case_sensitive=>1),array('key'=>'age','sort'=>'desc')))
		var_dump($res); 

		array(10) {
		  [8]=>
		  array(3) {
		    ["name"]=>
		    string(3) "Dad"
		    ["age"]=>
		    int(10)
		    ["male"]=>
		    bool(true)
		  }
		  [3]=>
		  array(3) {
		    ["name"]=>
		    string(3) "Dad"
		    ["age"]=>
		    int(50)
		    ["male"]=>
		    bool(true)
		  }
		  [1]=>
		  array(3) {
		    ["name"]=>
		    string(8) "Lawrence"
		    ["age"]=>
		    int(16)
		    ["male"]=>
		    bool(true)
		  }
		  [6]=>
		  array(3) {
		    ["name"]=>
		    string(8... 	
	*/
	public static function multisort($data, $keys) {
		if ($data && $keys) {
			// Obtain a list of columns
			$cols = array();
			foreach ($data as $key => $row)
			    	foreach ($keys as $k) {
			    		$k_key = isset($k['key']) ? $k['key'] : null;
			    		$col = isset($row[$k_key]) ? $row[$k_key] : null;
					$cols[$k_key][$key] = !empty($k['case_sensitive']) ? strtolower($col) : $col;
				}
				
			$multi_sort_str = "";
			foreach ($keys as $k) {
				$sort = !empty($k['sort']) ? "SORT_" . strtoupper($k['sort']) : "SORT_ASC";
				$type = !empty($k['type']) ? "SORT_" . strtoupper($k['type']) : "SORT_REGULAR";
				$k_key = isset($k['key']) ? $k['key'] : null;
				
				$multi_sort_str .= ($multi_sort_str ? ", " : "") . "\$cols['" . $k_key . "'], $sort, $type";
			}
		
			// Sort the data with volume descending, edition ascending
			// Add $idkeys as the last parameter, to sort by the common key
			if ($multi_sort_str) {
				// List original keys
				$idkeys = array_keys($data);

				$multi_sort_str = "array_multisort($multi_sort_str, \$idkeys);";
				//echo "!$multi_sort_str!";
				eval($multi_sort_str);
				
				// Rebuild Full Array
				$result = array();
				foreach($idkeys as $idkey){
					$result[$idkey] = $data[$idkey];
				}
				
				return $result;
			}
		}
		
		return $data;
	}
	/*public static function multisort($data, $keys) {
		// List As Columns
		$cols = array();
		foreach ($data as $key => $row) {
			foreach ($keys as $k) {
				$cols[ $k['key'] ][$key] = $k['case_sensitive'] ? strtolower($row[ $k['key'] ]) : $row[ $k['key'] ];
			}
		}

		// List original keys
		$idkeys = array_keys($data);

		// Sort Expression
		$i = 0;
		$sort = '';
		foreach ($keys as $k){
			if(is_array($cols[$k['key']])) {
				if($sort) {
					$sort .= ',';
				}
				
				$sort .= is_numeric($k['key']) ? '$cols['.$k['key'].']' : '$cols["'.$k['key'].'"]';
				if(isset($k['sort']) && $k['sort']) {
					$sort.=',SORT_'.strtoupper($k['sort']);
				}
				if(isset($k['type']) && $k['type']) {
					$sort.=',SORT_'.strtoupper($k['type']);
				}
				$i++;
			}
		}
		
		if($sort) {
			$sort .= ',$idkeys';

			// Sort Funct
			$sort = 'array_multisort('.$sort.');';
			//echo "!$sort!";
			eval($sort);
		}
		
		// Rebuild Full Array
		$result = array();
		foreach($idkeys as $idkey){
			$result[$idkey] = $data[$idkey];
		}
		return $result; 
	}*/
	
	public static function arrayToString($arr) {
		if(is_array($arr)) {
			$str = "array(";
		
			$keys = array_keys($arr);
			$t = count($keys);
			for($i = 0; $i < $t; $i++) {
				$key = $keys[$i];
				$value = $arr[$key];
			
				$str .= ($i > 0 ? ", " : "") . self::getVariableValueCode($key) . " => ";
			
				if(is_array($value)) {
					$str .= self::arrayToString($value);
				}
				else {
					$str .= self::getVariableValueCode($value);
				}
			}
			$str .= ")";
		}
		else {
			$str = "''";
		}
		return $str;
	}
	
	private static function getVariableValueCode($variable) {
		if (!isset($variable))
			return "null";
		
		$var = strtolower(trim($variable));
		
		if (substr($var, 0, 1) == '$' || substr($var, 0, 2) == '@$') 
			return $variable;
		else if (is_numeric($var))
			return $variable;
		else if ($var == "true" || $var == "false")
			return $variable;
		else if (substr($var, 0, 2) == "<?")
			return str_replace(array("<?php", "<?=", "<?", "?>"), "", $variable);
		else 
			return "\"" . addcslashes($variable, '\\"') . "\"";
	}
}
?>
