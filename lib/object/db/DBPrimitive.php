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

include_once get_lib("object.ObjType");
include_once get_lib("object.exception.ObjTypeException");

class DBPrimitive extends ObjType {
	private $type;
	public $is_primitive = true;
	
	public function __construct($type, $data = false) {
		$this->setType($type);
		$this->field = false;
		
		if($data !== false)
			$this->setData($data);
	}

	public static function getTypes() {
		return array( //MYSQL SERVER TYPES
			'bit' => 'Bit',
			'serial' => 'Serial', 
			'tinyint' => 'Tiny Int',
			'smallint' => 'Small Int',
			'int' => 'Int',
			'bigint' => 'Big Int',
			'mediumint' => 'Medium Int',
			'decimal' => 'Decimal',
			'double' => 'Double',
			'float' => 'Float',
			'real' => 'Real',
			'numeric' => 'Numeric',
			'integer' => 'Integer',
			
			'date' => 'Date',
			'datetime' => 'Date Time',
			'time' => 'Time',
			'timestamp' => 'Timestamp',
			'year' => 'Year',
			
			'char' => 'Char',
			'varchar' => 'Varchar',
			'text' => 'Text',
			'tinytext' => 'Tiny Text',
			'mediumtext' => 'Medium Text',
			'longtext' => 'Long Text',
			'blob' => 'Blob',
			'tinyblob' => 'Tiny Blob',
			'mediumblob' => 'Medium Blob',
			'longblob' => 'Long Blob',
			'string' => 'String',
			
			//other types
			'enum' => 'Enum',
		);
	}
	public static function getNumericTypes() {
		return array('bit', 'serial', 'tinyint', 'smallint', 'int', 'bigint', 'mediumint', 'decimal', 'double', 'float', 'real', 'numeric', 'integer');
	}
	public static function getDateTypes() {
		return array('date', 'datetime', 'time', 'timestamp', 'year');
	}
	public static function getTextTypes() {
		return array('char', 'varchar', 'tinytext', 'mediumtext', 'text', 'longtext', 'string', 'enum');
	}
	public static function getBlobTypes() {
		return array('tinyblob', 'blob', 'mediumblob', 'longblob', 'binary', 'varbinary', 'geometry', 'point', 'linestring', 'polygon', 'multipoint', 'multilinestring', 'multipolygon', 'geometrycollection');
	}
	public static function getBooleanTypeAvailableValues() {
		return array("boolean");
	}
	public static function getCurrentTimestampAvailableValues() {
		return array("CURRENT_TIMESTAMP", "CURRENT_TIMESTAMP()", "NOW()");
	}
	
	public function getType() {return $this->type;}
	public function setType($type) {$this->type = strtolower($type);}
	
	/**
	 * Same types than self:getTypes();
	 * TODO: Optimize the validation for each type
	 */
	public function setData($data) {
		$ok = false;
		
		switch ($this->type) {
			case "smallserial":
			case "serial":
			case "bigserial":
				$ok = is_numeric($data) && $data > 0; //must be unsigned
				break;
			case "bit":
			case "tinyint":
			case "smallint":
			case "int":
			case "bigint":
			case "decimal":
			case "double":
			case "float":
			case "money":
			case "coordinate":
			case "time":
				$ok = is_numeric($data);
				break;
			case "boolean":
				$ok = $data === TRUE || $data === false || $data === 0 || $data === 1 || $data === "0" || $data === "1" || in_array($data, self::getBooleanTypeAvailableValues());
				
				if (!$ok) {
					$v = strtolower($data);
					$ok = $v === "true" || $v === "false";
				}
				break;
			case "char":
			case "varchar":
			case "mediumtext":
			case "text":
			case "longtext":
			case "blob":
			case "longblob":
				$ok = !is_object($data) && !is_array($data);
				break;
			case "date":
			case "datetime":
			case "timestamp":
				$ok = !is_object($data) && !is_array($data);
				break;
			case "varchar(36)"://UUID
			case "varchar(44)"://CIDR
			case "varchar(43)"://INET
			case "varchar(17)"://MAC Addr
				$ok = !is_object($data) && !is_array($data);
				break;
			
			default: $ok = true;
		}
		
		if($ok) {
			$this->data = $data;
			return true;
		}
		
		launch_exception(new ObjTypeException($this->type, $data));
		return false;
	}
}
?>
