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

class TextSanitizer {
	
	/**
	* isCharEscaped: checks if a char is escaped given its position 
	*/
	public static function isCharEscaped($str, $index) {
		$escaped = false;
		
		if (is_numeric($str))
			$str = (string)$str; //bc of php > 7.4 if we use $sql[$i] gives an warning
		
		for ($i = $index - 1; $i >= 0; $i--) {
			if ($str[$i] == "\\")
				$escaped = !$escaped;
			else
				break;
		}
		
		return $escaped;
	}
}	

?>
