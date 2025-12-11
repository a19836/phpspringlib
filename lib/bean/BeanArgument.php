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

include get_lib("bean.exception.BeanArgumentException");

class BeanArgument {
	public $index;
	public $value = false;
	public $reference = false;
	
	public function __construct($index, $value = false, $reference = false) {
		$this->index = $index;
		$this->value = $value;
		$this->reference = $reference;
		
		$this->isValid();
	}
	
	private function isValid() {
		if(!is_numeric($this->index)) {
			launch_exception(new BeanArgumentException(1, $this->index));
			return false;
		}
		elseif($this->index <= 0) {
			launch_exception(new BeanArgumentException(2, $this->index));
			return false;
		}
		elseif($this->value && $this->reference) {
			launch_exception(new BeanArgumentException(3, array($this->value, $this->reference)));
			return false;
		}
		return true;
	}
}
?>
