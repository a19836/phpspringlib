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
include_once get_lib("db.DB");
include_once get_lib("sqlmap.IRDBBroker");
include_once get_lib("util.text.TextSanitizer");

class MySQLDBBroker extends DB implements IRDBBroker {
    private mysqli $db;
	
	 //http://mx1.php.net/manual/en/mysqli-result.fetch-field.php
	//http://php.net/manual/en/mysqli.constants.php
	private static $mysqli_data_types = array(
		6 => array("null"), //MYSQLI_TYPE_NULL
		
		//numeric
		0 => array("decimal"), //MYSQLI_TYPE_DECIMAL
		1 => array("tinyint", "bool"), //MYSQLI_TYPE_TINY or MYSQLI_TYPE_CHAR
		2 => array("smallint"), //MYSQLI_TYPE_SHORT
		3 => array("int", "integer"), //MYSQLI_TYPE_LONG
		4 => array("float"), //MYSQLI_TYPE_FLOAT
		5 => array("double"), //MYSQLI_TYPE_DOUBLE
		8 => array("bigint", "serial"), //MYSQLI_TYPE_LONGLONG
		9 => array("mediumint"), //MYSQLI_TYPE_INT24
		16 => array("bit"), //MYSQLI_TYPE_BIT
		246 => array("decimal", "numeric"), //MYSQLI_TYPE_NEWDECIMAL
		
		//dates
		7 => array("timestamp"), //MYSQLI_TYPE_TIMESTAMP
		10 => array("date"), //MYSQLI_TYPE_DATE
		11 => array("time"), //MYSQLI_TYPE_TIME
		12 => array("datetime"), //MYSQLI_TYPE_DATETIME
		13 => array("year"), //MYSQLI_TYPE_YEAR
		14 => array("date"), //MYSQLI_TYPE_NEWDATE
		
		//strings & binary
		245 => array("json"), //MYSQLI_TYPE_JSON
		247 => array("enum", "interval"), //MYSQLI_TYPE_ENUM or MYSQLI_TYPE_INTERVAL
		248 => array("set"), //MYSQLI_TYPE_SET
		249 => array("tinyblob", "tinytext"), //MYSQLI_TYPE_TINY_BLOB
		250 => array("mediumblob", "mediumtext"), //MYSQLI_TYPE_MEDIUM_BLOB
		251 => array("longblob", "longtext"), //MYSQLI_TYPE_LONG_BLOB
		252 => array("blob", "text"), //MYSQLI_TYPE_BLOB
		253 => array("varchar"), //MYSQLI_TYPE_VAR_STRING
		254 => array("char"), //MYSQLI_TYPE_STRING
		255 => array("geometry"), //MYSQLI_TYPE_GEOMETRY
	);
	
	 //http://mx1.php.net/manual/en/mysqli-result.fetch-field.php
	//http://php.net/manual/en/mysqli.constants.php
	/*
	 * According to dev.mysql.com/sources/doxygen/mysql-5.1/mysql__com_8h-source.html the flag bits are:
		NOT_NULL_FLAG          1         // Field can't be NULL 
		PRI_KEY_FLAG           2         // Field is part of a primary key 
		UNIQUE_KEY_FLAG        4         // Field is part of a unique key 
		MULTIPLE_KEY_FLAG      8         // Field is part of a key 
		BLOB_FLAG             16         // Field is a blob 
		UNSIGNED_FLAG         32         // Field is unsigned 
		ZEROFILL_FLAG         64         // Field is zerofill 
		BINARY_FLAG          128         // Field is binary   
		ENUM_FLAG            256         // field is an enum 
		AUTO_INCREMENT_FLAG  512         // field is a autoincrement field 
		TIMESTAMP_FLAG      1024         // Field is a timestamp 
	*/
	private static $mysqli_flags = array(
		1 => "not_null", //MYSQLI_NOT_NULL_FLAG
		2 => "primary_key", //MYSQLI_PRI_KEY_FLAG
		4 => "unique_key", //MYSQLI_UNIQUE_KEY_FLAG
		8 => "multiple_key", //MYSQLI_MULTIPLE_KEY_FLAG
		16 => "blob", //MYSQLI_BLOB_FLAG
		32 => "unsigned", //MYSQLI_UNSIGNED_FLAG
		64 => "zerofill", //MYSQLI_ZEROFILL_FLAG
		512 => "auto_increment", //MYSQLI_AUTO_INCREMENT_FLAG
		1024 => "timestamp", //MYSQLI_TIMESTAMP_FLAG
		2048 => "set", //MYSQLI_SET_FLAG
		32768 => "numeric", //MYSQLI_NUM_FLAG
		16384 => "multi_index", //MYSQLI_PART_KEY_FLAG
		256 => "enum", //MYSQLI_ENUM_FLAG
		128 => "binary", //MYSQLI_BINARY_FLAG
		4096 => "no_default_value", //
	);
	
    public function __construct($host, $user, $pass, $db_name) {
        $this->db = new mysqli($host, $user, $pass, $db_name);

        if ($this->db->connect_error) {
            throw new Exception("MySQL connection failed: " . $this->db->connect_error);
        }

        $this->db->set_charset("utf8mb4");
    }

    /* -------------------------------------------
       BASIC RAW SQL FUNCTIONS
    ------------------------------------------- */

    public function getFunction($function_name, $parameters = false, $options = false) {
      $exists = method_exists($this, $function_name);
		
		if (!$exists) {
			$fn = strtolower($function_name);
			$class_methods = get_class_methods($this);
			
			if ($class_methods)
				foreach ($class_methods as $method_name)
					if (strtolower($method_name) == $fn) {
						$function_name = $method_name;
						$exists = true;
						break;
					}
		}
		
		if ($exists) {
			$func_args = is_array($parameters) ? $parameters : ($parameters ? array($parameters) : array()); //$parameters could be an array with arguments or a simple attribute (string or numeric) which means it should be converted to first argument of the $function_name method.
			$func_args = array_values($func_args);
			//echo "<pre>";print_r($func_args);echo "</pre>";
			
			//echo "<pre>".$this->getType()."::getFunction: $function_name";print_r($func_args);die();
			$result = @call_user_func_array(array($this, $function_name), $func_args); //Note that the @ is very important here bc in PHP 8 this gives an warning, this is: 'Warning: Array to string conversion in...'
			
			return $result;
		}
		
		return null;
    }

	 public function getData($sql, $options = false) {
		 $data = [
		     "fields" => [],
		     "result" => []
		 ];
		 
		 $queries = explode(";", $sql);
		 
		 foreach ($queries as $query) 
		 	if (trim($query)) {
		 		if (is_array($options) && stripos($query, "select ") === 0) { //$query can be a procedure call
					if (substr($query, -1) == ";") 
						$query = substr($query, 0, -1);
					
					if (!empty($options["sort"])) {
						$sort = self::addSortOptionsToSQL($options["sort"]);
						if ($sort) {
							if (stripos($query, " limit ") !== false)
								$query = "SELECT * FROM (" . $query . ") AS QUERY_WITH_SORTING ORDER BY " . $sort;
							else 
								$query .= " ORDER BY " . $sort;
						}
					}
					
					if(isset($options["limit"]) && is_numeric($options["limit"])) {
						if (stripos($query, " order by ") !== false)
							$query = "SELECT * FROM (" . $query . ") AS QUERY_WITH_PAGINATION LIMIT " . (!empty($options["start"]) ? $options["start"] : 0) . ", " . $options["limit"];
						else
							$query .= " LIMIT " . (!empty($options["start"]) ? $options["start"] : 0) . ", " . $options["limit"];
					}
				}
				
				 $result = $this->db->query($query);

				 if (!$result)
					  throw new Exception("Query failed: " . $this->db->error);
				 else if ($result === true) //if query is insert/update/delete query returns true (if is true)
						$data["result"] = $result;
				 else if (is_a($result, "mysqli_result")) {
					 // --- Fetch fields metadata ---
					 $field_count = $result->field_count;

					 for ($i = 0; $i < $field_count; $i++) {
						  $field = mysqli_fetch_field_direct($result, $i);
						  $this->prepareMysqliField($field);
						  
						  $data["fields"][] = $field;
					 }
					
					 // --- Fetch rows ---
					 while ($row = $result->fetch_assoc()) {
					 	if (!is_array($data["result"]))
					 		$data["result"] = array();	
					 		
						  $data["result"][] = $row;
					 }

					 $result->free();
				}
			}
		
		 //return data
		 if (!empty($options["return_type"]))
			switch (strtolower($options["return_type"])) {
				case "fields": return $data["fields"];
				case "result": return $data["result"];
			}
			
		 return $data;
	}


    public function setData($sql, $options = false) {
			/* execute multi query */
			$status = $this->db->multi_query($sql);
			
			do {
				 /* store the result set in PHP */
				 if ($result = $this->db->store_result()) {
					  if ($result === false) //$result could be an int(0)
							$status = $result;
					  else if ($result && is_a($result, "mysqli_result")) //free result just in case
							$this->db->free_result($result);
				 }
				 
				$this->db->more_results();
			} while ($this->db->next_result());
        
        	if (!$status)
				throw new Exception("Query failed: " . $this->db->error);
			
			return $status;
    }

    public function getSQL($sql, $options = false) {
        return $this->getData($sql, $options);
    }

    public function setSQL($sql, $options = false) {
        return $this->setData($sql, $options);
    }

    public function getInsertedId($options = false) {
        return $this->db->insert_id;
    }
    
    private function prepareMysqliField(&$field) {
		//echo "<pre>";print_r($field);
		if (is_array($field))
			$field = (object) $field; //cast to object
		
		$field_types = self::$mysqli_data_types[$field->type];
		
		if ($field->flags) {
			foreach (self::$mysqli_flags as $n => $t) 
			    	if ($field->flags & $n)
			    		$field->$t = true;
			
			$field_type_0 = isset($field_types[0]) ? $field_types[0] : null;
			$field_type_1 = isset($field_types[1]) ? $field_types[1] : null;
			
			switch($field->type) {
				//246 => array("decimal", "numeric")
				case MYSQLI_TYPE_NEWDECIMAL: 
					$field->type = empty($field->decimal) && !empty($field->numeric) ? $field_type_1 : $field_type_0;
					break;
			
				//249 => array("tinyblob", "tinytext"),
				//250 => array("mediumblob", "mediumtext"),
				//251 => array("longblob", "longtext"),
				//252 => array("blob", "text"),
				case MYSQLI_TYPE_TINY_BLOB: 
				case MYSQLI_TYPE_MEDIUM_BLOB: 
				case MYSQLI_TYPE_LONG_BLOB: 
				case MYSQLI_TYPE_BLOB: 
					$field->type = empty($field->blob) ? $field_type_1 : $field_type_0;
					break;
				
				default:
					$field->type = $field_type_0;
			}
		}
		else 
			$field->type = isset($field_types[0]) ? $field_types[0] : null;
		
		//echo "<pre>";print_r($field);die();
	}

    /* -------------------------------------------
       OBJECT OPERATIONS
    ------------------------------------------- */

    public function insertObject($table_name, $attributes, $options = false) {
        list($cols, $vals) = $this->_buildAttributes($attributes);

        $sql = "INSERT INTO `$table_name` ($cols) VALUES ($vals)";
        return $this->setData($sql);
    }

    public function updateObject($table_name, $attributes, $conditions = false, $options = false) {
        $set = $this->_buildSet($attributes);
        $where = $this->_buildConditions($conditions);

        $sql = "UPDATE `$table_name` SET $set $where";
        return $this->setData($sql);
    }

    public function deleteObject($table_name, $conditions = false, $options = false) {
        $where = $this->_buildConditions($conditions);
        $sql = "DELETE FROM `$table_name` $where";
        return $this->setData($sql);
    }

    public function findObjects($table_name, $attributes = false, $conditions = false, $options = false) {
        $cols = $attributes ? implode(",", $attributes) : "*";
        $where = $this->_buildConditions($conditions);

        $sql = "SELECT $cols FROM `$table_name` $where";
        return $this->getData($sql, array("return_type" => "result"));
    }

    public function countObjects($table_name, $conditions = false, $options = false) {
        $where = $this->_buildConditions($conditions);

        $sql = "SELECT COUNT(*) AS total FROM `$table_name` $where";
        $data = $this->getData($sql, array("return_type" => "result"));

        return intval($data[0]["total"] ?? 0);
    }

    public function findRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
        $where = $this->_buildConditions($parent_conditions);
        $sql = "SELECT * FROM `$table_name` WHERE `$rel_elm` IN (SELECT `$rel_elm` FROM `$table_name` $where)";

        return $this->getData($sql, array("return_type" => "result"));
    }

    public function countRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
        $where = $this->_buildConditions($parent_conditions);
        $sql = "SELECT COUNT(*) AS total FROM `$table_name` WHERE `$rel_elm` IN (SELECT `$rel_elm` FROM `$table_name` $where)";

        $data = $this->getData($sql, array("return_type" => "result"));
        return intval($data[0]["total"] ?? 0);
    }

    public function findObjectsColumnMax($table_name, $attribute_name, $options = false) {
        $sql = "SELECT MAX(`$attribute_name`) AS max_value FROM `$table_name`";
        $data = $this->getData($sql, array("return_type" => "result"));
        return $data[0]["max_value"] ?? null;
    }
    
    /* -------------------------------------------
       SQL OPERATIONS
    ------------------------------------------- */
    
    public function listTableFields($table, $options = false) {
		$fields = array();
		
		$sql = "SELECT 
				COLUMN_NAME AS 'column_name', 
				DATA_TYPE AS 'data_type', 
				COLUMN_TYPE AS 'column_type', 
				COLUMN_DEFAULT AS 'column_default', 
				IS_NULLABLE AS 'is_nullable', 
				CHARACTER_MAXIMUM_LENGTH AS 'character_maximum_length', 
				NUMERIC_PRECISION AS 'numeric_precision', 
				CHARACTER_SET_NAME AS 'character_set_name', 
				COLLATION_NAME AS 'collation_name', 
				COLUMN_KEY AS 'column_key', 
				EXTRA AS 'extra', 
				COLUMN_COMMENT AS 'column_comment', 
				IF(LOWER(COLUMN_KEY) = 'pri', 1, 0) AS is_primary,
				IF(LOWER(COLUMN_KEY) = 'uni', 1, 0) AS is_unique
			FROM information_schema.COLUMNS 
			WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table'
			ORDER BY ORDINAL_POSITION ASC";
		
		$options = $options ? $options : array();
		$options["return_type"] = "result";
		$result = $this->getData($sql, $options);
		
		if($result)
			foreach ($result as $field) 
				if (isset($field["column_name"])) {
				 	$ck = isset($field["column_key"]) ? $field["column_key"] : null;
					$ct = isset($field["column_type"]) ? $field["column_type"] : null;
					$dt = isset($field["data_type"]) ? $field["data_type"] : null;
					$cd = isset($field["column_default"]) ? $field["column_default"] : null;
					
					$lck = strtolower($ck);
					$lct = strtolower($ct);
					$is_unsigned = strpos($lct, "unsigned") > 0;
					
					$length = !empty($field["character_maximum_length"]) ? $field["character_maximum_length"] : (isset($field["numeric_precision"]) ? $field["numeric_precision"] : null);
					preg_match("/" . $dt . "\(([0-9]+)\)/", $ct, $matches);
					$l = !empty($matches[0]) ? $matches[1] : null;
					//$length = !is_numeric($length) || ($l > 0 && $l < $length) ? $l : $length; //JP 2021-01-25: this is not correct bc the character_maximum_length and numeric_precision don't always have the correct length. The length should be taken from the column_type, if possible.
					
					if (is_numeric($l))
						$length = $l;
					else if (is_numeric($length) && preg_match("/" . $dt . "\(([0-9]+),([0-9]+)\)/", $ct, $matches) && !empty($matches[0]))
						$length = $matches[1] . "," . $matches[2];
					
					//bc of mariaDB
					$cd = $cd == "''" ? "" : $cd;
					
					$props = array(
						"name" => $field["column_name"],
						"type" => $dt,
						"length" => $length,
						"null" => isset($field["is_nullable"]) && strtolower($field["is_nullable"]) == "no" ? false : true,
						"primary_key" => !empty($field["is_primary"]) || $lck == "pri" ? true : false,
						"unique" => !empty($field["is_primary"]) || !empty($field["is_unique"]) || $lck == "pri" || $lck == "uni" ? true : false,
						"unsigned" => $is_unsigned,
						"default" => $cd,
						"charset" => isset($field["character_set_name"]) ? $field["character_set_name"] : null,
						"collation" => isset($field["collation_name"]) ? $field["collation_name"] : null,
						"extra" => isset($field["extra"]) ? $field["extra"] : null,
						"comment" => isset($field["column_comment"]) ? $field["column_comment"] : null,
					);
					
					//set auto_increment and flags
					$auto_increment = in_array($dt, array("serial", "smallserial", "bigserial"));
					
					if ($auto_increment && stripos($props["extra"], "auto_increment") === false)
						$props["extra"] .= ($props["extra"] ? " " : "") . "auto_increment";
					
					$props["auto_increment"] = $auto_increment || stripos($props["extra"], "auto_increment") !== false;
					
					$fields[ $field["column_name"] ] = $props;
				}
		
		return $fields;
	}
	
	public static function buildTableFindSQL($table_name, $attributes = false, $conditions = false, $options = false) {
		$sql = null;
		
		if ($table_name) {
			$options = is_array($options) ? $options : array();
			$conditions_join = isset($options["conditions_join"]) ? $options["conditions_join"] : null;
			$sorts = isset($options["sorts"]) ? $options["sorts"] : null;
			$extra_sql_conditions = isset($options["sql_conditions"]) ? $options["sql_conditions"] : null;
			
			$sql_conditions = self::getSQLConditions($conditions, $conditions_join, "", $options);
			$sql_conditions .= $extra_sql_conditions ? ($sql_conditions ? " AND " : "") . $extra_sql_conditions : "";
			$sql_sort = self::getSQLSort($sorts);
			
			//version 2
			$sql_attrs = self::getSQLAttributes($attributes);
			
			$sql = "SELECT {$sql_attrs} FROM " . self::getParsedSqlTableName($table_name);
			$sql .= $sql_conditions ? " WHERE {$sql_conditions}" : "";
			$sql .= $sql_sort ? " ORDER BY {$sql_sort}" : "";
		}
		//error_log("buildDefaultTableFindSQL:$sql\n\n", 3,  "/var/www/html/livingroop/default/tmp/test.log");
		
		return $sql;
	}
	
	public static function buildTableCountSQL($table_name, $conditions = false, $options = false) {
		$sql = null;
		
		if ($table_name) {
			$options = is_array($options) ? $options : array();
			$conditions_join = isset($options["conditions_join"]) ? $options["conditions_join"] : null;
			$extra_sql_conditions = isset($options["sql_conditions"]) ? $options["sql_conditions"] : null;
			
			$sql_conditions = self::getSQLConditions($conditions, $conditions_join, "", $options);
			$sql_conditions .= $extra_sql_conditions ? ($sql_conditions ? " AND " : "") . $extra_sql_conditions : "";
			
			$sql = "SELECT count(*) AS total FROM " . self::getParsedSqlTableName($table_name);
			$sql .= $sql_conditions ? " WHERE {$sql_conditions}" : "";
		
		}
		//error_log("buildDefaultTableCountSQL:$sql\n\n", 3,  "/var/www/html/livingroop/default/tmp/test.log");
		
		return $sql;
	}
	
	public static function buildTableFindRelationshipSQL($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		$sql = null;
		
		if ($table_name && $rel_elm) {
			$options = is_array($options) ? $options : array();
			$keys = isset($rel_elm["keys"]) ? $rel_elm["keys"] : null;
			$attributes = isset($rel_elm["attributes"]) ? $rel_elm["attributes"] : null;
			$conditions = isset($rel_elm["conditions"]) ? $rel_elm["conditions"] : null;
			$groups_by = isset($rel_elm["groups_by"]) ? $rel_elm["groups_by"] : null;
			$sorts = !empty($options["sorts"]) && empty($rel_elm["sorts"]) ? $options["sorts"] : (isset($rel_elm["sorts"]) ? $rel_elm["sorts"] : null);
			$extra_sql_conditions = isset($options["sql_conditions"]) ? $options["sql_conditions"] : null;
			
			$sql_conditions = self::getSQLRelationshipConditions($conditions, $table_name, $parent_conditions, $options);
			$sql_conditions .= $extra_sql_conditions ? ($sql_conditions ? " AND " : "") . $extra_sql_conditions : "";
			$sql_groups_by = self::getSQLRelationshipGroupBy($groups_by, $table_name);
			$sql_sort = self::getSQLRelationshipSort($sorts, $table_name, ($sql_groups_by ? true : false));
			
			$sql = "SELECT ";
			$sql .= self::getSQLRelationshipAttributes($attributes, $table_name, $keys);
			$sql .= " FROM " . self::getParsedSqlTableName($table_name) . " " . self::getSQLRelationshipJoins($keys, $table_name, $options);
			$sql .= $sql_conditions || $extra_sql_conditions ? " WHERE $sql_conditions" : "";
			$sql .= $sql_groups_by ? " " . $sql_groups_by : "";
			
			if($sql_groups_by && $sql_sort) 
				$sql = "SELECT * FROM ({$sql}) Z ORDER BY {$sql_sort}";
			elseif($sql_sort) 
				$sql .= " ORDER BY {$sql_sort}";
		}
		//error_log("buildDefaultTableFindRelationshipSQL:$sql\n\n", 3,  "/var/www/html/livingroop/default/tmp/test.log");
		
		return $sql;
	}
	
	public static function buildTableCountRelationshipSQL($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		$sql = null;
		
		if ($table_name && $rel_elm) {
			$keys = isset($rel_elm["keys"]) ? $rel_elm["keys"] : null;
			$attributes = isset($rel_elm["attributes"]) ? $rel_elm["attributes"] : null; //is only used if groups_by exists
			$conditions = isset($rel_elm["conditions"]) ? $rel_elm["conditions"] : null;
			$groups_by = isset($rel_elm["groups_by"]) ? $rel_elm["groups_by"] : null;
			$extra_sql_conditions = isset($options["sql_conditions"]) ? $options["sql_conditions"] : null;
			
			$sql_conditions = self::getSQLRelationshipConditions($conditions, $table_name, $parent_conditions, $options);
			$sql_conditions .= $extra_sql_conditions ? ($sql_conditions ? " AND " : "") . $extra_sql_conditions : "";
			$sql_group_by = self::getSQLRelationshipGroupBy($groups_by, $table_name);
			
			$sql = " FROM " . self::getParsedSqlTableName($table_name) . " " . self::getSQLRelationshipJoins($keys, $table_name, $options);
			$sql .= $sql_conditions ? " WHERE {$sql_conditions}" : "";
			$sql .= $sql_group_by ? " " . $sql_group_by : "";
			
			if($sql_group_by)
				$sql = "SELECT count(*) AS total FROM (
					SELECT " . self::getSQLRelationshipAttributes($attributes, $table_name, $keys) . "
					$sql
				) Z";
			else
				$sql = "SELECT count(*) AS total " . $sql;
		}
		//error_log("buildDefaultTableCountRelationshipSQL:$sql\n\n", 3,  "/var/www/html/livingroop/default/tmp/test.log");
		
		return $sql;
	}
	
    /* -------------------------------------------
       INTERNAL SQL FUNCTIONS
    ------------------------------------------- */
	
	protected static function getSQLRelationshipConditions($conditions, $table_name = false, $parent_conditions = false, $options = false) {
		$sql = "";
		
		if(is_array($parent_conditions)) 
			$sql .= ($sql ? " AND " : "") . self::getSQLConditions($parent_conditions, null, $table_name, $options);
			/*foreach($parent_conditions as $key => $value) 
				$sql .= ($sql ? " AND " : "") . self::prepareTableAttributeWithFunction($key, $table_name) . "=" . self::createBaseExprValue($value, $options);*/
		
		$t = $conditions ? count($conditions) : 0;
		$is_numeric_array = $t == 0 || ( array_keys($conditions) === range(0, $t - 1) );
		
		if (!$is_numeric_array) //if associative array
			$sql .= ($sql ? " AND " : "") . self::getSQLConditions($conditions, null, $table_name, $options);
		else
			for ($i = 0; $i < $t; $i++) {
				$condition = $conditions[$i];
				
				if (is_array($condition)) {
					$column = isset($condition["column"]) ? $condition["column"] : null;
					$table = !empty($condition["table"]) ? $condition["table"] : null;
					$operator = !empty($condition["operator"]) ? $condition["operator"] : "=";
					$value = isset($condition["value"]) ? $condition["value"] : null;
					$ref_column = isset($condition["refcolumn"]) ? $condition["refcolumn"] : null;
					$ref_table = isset($condition["reftable"]) ? $condition["reftable"] : null;
				
					if ($column) {
						//get table from column
						if (!$table)
							$table = self::getTableFromColumn($column);
						
						if (!$table)
							$table = $table_name;
						
						if ($ref_column) {
							$ref_table = $ref_table ? $ref_table : $table_name;
						
							$sql .= ($sql ? " AND " : "") . self::prepareTableAttributeWithFunction($column, $table) . " {$operator} " . self::prepareTableAttributeWithFunction($ref_column, $ref_table);
						}
					
						if (isset($condition["value"])) {
							$lo = strtolower($operator);
							$value_options = self::prepareValueOptions($options, $condition);
							
							if ($lo == "in" || $lo == "not in")
								$value = self::createBaseExprValueForOperatorIn($value, $value_options);
							else if ($lo == "is" || $lo == "is not")
								$value = self::createBaseExprValueForOperatorIs($value, $value_options);
							else
								$value = self::createBaseExprValue($value, $value_options);
							
							$cond = array(
								$column => array(
									"operator" => $operator,
									"value" => $value
								)
							);
							$sql .= ($sql ? " AND " : "") . self::getSQLConditions($cond, null, $table, $options);
							//$sql .= ($sql ? " AND " : "") . self::prepareTableAttributeWithFunction($column, $table) . " {$operator} " . self::createBaseExprValue($value, $value_options);
						}
					}
				}
				else
					$sql .= ($sql ? " AND " : "") . $condition;
			}
		
		return $sql;
	}
	
	protected static function getSQLRelationshipSort($sorts, $table_name, $group_by = false) {
		$sql = "";
		
		if ($sorts)
			foreach ($sorts as $idx => $sort) {
				if (is_array($sort)) {
					$column = isset($sort["column"]) ? $sort["column"] : null;
					$order = isset($sort["order"]) ? $sort["order"] : null;
					$table = !empty($sort["table"]) ? $sort["table"] : null;
					
					//get table from column
					if ($column && !$table)
						$table = self::getTableFromColumn($column);
				}
				else {
					$column = is_numeric($idx) ? $sort : $idx; //where $sorts is an associative array($column => $order);
					$order = is_numeric($idx) ? "" : $sort;
					$table = null;
					
					//get table from column
					if ($column)
						$table = self::getTableFromColumn($column);
				}
				
				if($column) {
					if (!$table)
						$table = $table_name;
					
					$field = $group_by ? $column : self::prepareTableAttributeWithFunction($column, $table);
					$sql .= ($sql ? ", " : "") . "{$field} {$order}";
				}
			}
		
		return $sql;
	}
	
	protected static function getSQLRelationshipAttributes($attrs, $table_name, $keys) {
		$sql = "";
		
		if ($attrs)
			foreach ($attrs as $idx => $attr) {
				if (is_array($attr)) {
					$name = !empty($attr["name"]) ? $attr["name"] : (isset($attr["column"]) ? $attr["column"] : null);
					$column = isset($attr["column"]) ? $attr["column"] : null;
					$table = !empty($attr["table"]) ? $attr["table"] : null;
					
					//get table from column
					if ($column && !$table)
						$table = self::getTableFromColumn($column);
				}
				else {
					$name = $attr;
					$column = is_numeric($idx) ? $attr : $idx; //where $attrs is an associative array($column => $name)
					$table = null;
					
					//get table from column
					if ($column)
						$table = self::getTableFromColumn($column);
				}
				
				if($column) {
					if (!$table)
						$table = $table_name;
					
					$sql .= (strlen($sql) ? ", " : "") . self::prepareTableAttributeWithFunction($column, $table) . ($column != "*" && $column != $name && $name ? " AS \"{$name}\"" : "");
				}
			}
		
		if (!$sql && $keys) {
			$t = count($keys);
			for($i = 0; $i < $t; $i++) {
				$key = $keys[$i];
				
				if (!empty($key["ftable"])) {
					$f_table_alias = self::getAlias($key["ftable"]);
					
					$sql .= ($sql ? ", " : "") . self::prepareTableAttributeWithFunction("*", $f_table_alias ? $f_table_alias : $key["ftable"]);
				}
			}
			//print_r($attrs);echo"table:$table_name";print_r($keys);die($sql);
		}
		
		return $sql ? $sql : "*";
	}
	
	protected static function getSQLRelationshipJoins($keys, $table_name, $options = false) {
		$joins = array();
		
		$t = $keys ? count($keys) : 0;
		for($i = 0; $i < $t; $i++) {
			$key = $keys[$i];
			
			$p_table = !empty($key["ptable"]) ? $key["ptable"] : $table_name;
			$p_column = isset($key["pcolumn"]) ? $key["pcolumn"] : null;
			$f_table = isset($key["ftable"]) ? $key["ftable"] : null;
			$f_column = isset($key["fcolumn"]) ? $key["fcolumn"] : null;
			$join = !empty($key["join"]) ? strtoupper($key["join"]) : "inner";
			$operator = isset($key["operator"]) ? $key["operator"] : null;
			$value = isset($key["value"]) ? $key["value"] : null;
			
			$value_exists = isset($key["value"]) && strlen($key["value"]);
			$value_options = self::prepareValueOptions($options, $key);
			
			$operator = $operator ? $operator : "=";
			$lo = strtolower($operator);
			
			//get p_table from p_column
			if ($p_column && !$p_table)
				$p_table = self::getTableFromColumn($p_column);
			
			//get f_table from f_column
			if ($f_column && !$f_table)
				$f_table = self::getTableFromColumn($f_column);
			
			if ($value_exists) {
				if ($lo == "in" || $lo == "not in")
					$value = self::createBaseExprValueForOperatorIn($value, $value_options);
				else if ($lo == "is" || $lo == "is not")
					$value = self::createBaseExprValueForOperatorIs($value, $value_options);
				else
					$value = self::createBaseExprValue($value, $value_options);
			}
			
			$join_keys = array();
			$join_key_index = null;
			
			$p_table_only = self::getTableName($p_table);
			$f_table_only = self::getTableName($f_table);
			
			if ($f_column && $f_table) {
				$f_table_alias = $f_table == $table_name ? $f_table . "_aux" : self::getAlias($f_table);
				$join_key_index = " {$join} JOIN " . self::getParsedSqlTableName($f_table_only) . ($f_table_alias != $f_table ? " {$f_table_alias}" : "") . " ON ";
				
				//Add new inner join table in case of multiple tables in joins
				$p_table_alias = $p_table == $table_name ? $p_table . "_aux" : self::getAlias($p_table);
				$join_key_index_aux = " {$join} JOIN " . self::getParsedSqlTableName($p_table_only) . " ON ";
				
				if (!empty($joins[ $join_key_index ]) && $p_table && $p_table != $table_name && empty($joins[ $join_key_index_aux ])) {
					$join_key_index = $join_key_index_aux;
				}
				
				$join_keys = isset($joins[ $join_key_index ]) && is_array($joins[ $join_key_index ]) ? $joins[ $join_key_index ] : array();
				
				if($p_column) {
					$join_sql = " " . self::prepareTableAttributeWithFunction($f_column, $f_table_alias) . " $operator " . self::prepareTableAttributeWithFunction($p_column, $p_table);
					if(!in_array($join_sql, $join_keys)) {
						$join_keys[] = $join_sql;
					}
				}
				
				if ($value_exists) {
					$join_sql = " " . self::prepareTableAttributeWithFunction($f_column, $f_table_alias) . " $operator {$value}";
					if (!in_array($join_sql, $join_keys)) {
						$join_keys[] = $join_sql;
					}
					
					if ($p_column) {
						$join_sql = " " . self::prepareTableAttributeWithFunction($p_column, $p_table) . " $operator {$value}";
						if(!in_array($join_sql, $join_keys)) {
							$join_keys[] = $join_sql;
						}
					}
				}
			}
			else if ($p_column && $value_exists) {
				$p_table_alias = $p_table == $table_name ? $p_table . "_aux" : self::getAlias($p_table);
				$join_key_index = " {$join} JOIN " . self::getParsedSqlTableName($p_table_only) . ($p_table_alias != $p_table ? " {$p_table_alias}" : "") . " ON ";
				
				$join_keys = isset($joins[ $join_key_index ]) && is_array($joins[ $join_key_index ]) ? $joins[ $join_key_index ] : array();
				
				$join_sql = " " . self::prepareTableAttributeWithFunction($p_column, $table_alias) . " $operator {$value}";
				if(!in_array($join_sql, $join_keys)) {
					$join_keys[] = $join_sql;
				}
			}
			else if ($f_column && $value_exists) {
				$f_table_alias = $f_table == $table_name ? $f_table . "_aux" : self::getAlias($f_table);
				$join_key_index = " {$join} JOIN " . self::getParsedSqlTableName($f_table_only) . ($f_table_alias != $f_table ? " {$f_table_alias}" : "") . " ON ";
				
				$join_keys = isset($joins[ $join_key_index ]) && is_array($joins[ $join_key_index ]) ? $joins[ $join_key_index ] : array();
				
				$join_sql = " " . self::prepareTableAttributeWithFunction($f_column, $f_table_alias) . " $operator {$value}";
				if(!in_array($join_sql, $join_keys)) {
					$join_keys[] = $join_sql;
				}
			}
			
			if(count($join_keys))
				$joins[ $join_key_index ] = $join_keys;
		}
		
		$sql = "";
		foreach($joins as $join_table => $join_keys) 
			$sql .= $join_table . implode(" AND ", $join_keys);
		
		return $sql;
	}
	
	protected static function getSQLRelationshipGroupBy($group_by, $table_name) {
		$sql_group_by = "";
		$sql_having = "";
		
		$repeated_group_by_fields = array();
		
		if ($group_by)
			foreach ($group_by as $group_by_item) {
				if (is_array($group_by_item)) {
					$column = isset($group_by_item["column"]) ? $group_by_item["column"] : null;
					$having = isset($group_by_item["having"]) ? $group_by_item["having"] : null;
					$table = !empty($group_by_item["table"]) ? $group_by_item["table"] : null;
					
					//get table from column
					if ($column && !$table)
						$table = self::getTableFromColumn($column);
				}
				else {
					$column = $group_by_item;
					$having = "";
					$table = null;
					
					//get table from column
					if ($column)
						$table = self::getTableFromColumn($column);
				}
				
				if($column) {
					if (!$table)
						$table = $table_name;
					
					$group_by_field = self::prepareTableAttributeWithFunction($column, $table);
					
					if(!in_array($group_by_field, $repeated_group_by_fields)) {
						$repeated_group_by_fields[] = $group_by_field;
						$sql_group_by .= ($sql_group_by ? ", " : "") . $group_by_field;
					}
					$sql_having .= ($sql_having ? " AND " : "") . $having;
				}
			}
		
		$sql = "";
		if($sql_group_by) {
			$sql .= " GROUP BY " . $sql_group_by;
			
			if($sql_having) {
				$sql .= " HAVING " . $sql_having;
			}
		}
		return $sql;
	}
	
	protected static function getSQLAttributes($attributes) {
		$sql = "";
		
		if (is_array($attributes) && count($attributes)) {
			$is_numeric_array = array_keys($attributes) === range(0, count($attributes) - 1);
			
			foreach($attributes as $attr_name => $attr_alias) {
				if ($is_numeric_array) //if $attributes is a numeric array, $attr_name is a numeric key and $attr_alias is the real attribute name.
					$attr_name = $attr_alias;
				
				if($attr_name) {
					$attr = self::prepareTableAttributeName($attr_name);
					
					$sql .= (strlen($sql) ? ", " : "") . $attr . ($attr_alias && $attr_alias != $attr_name ? " AS \"" . $attr_alias . "\"" : "");
				}
			}
		}
		else
			$sql = "*";
		
		return $sql;
	}
	
	protected static function getSQLConditions($conditions, $join = false, $key_table_name = "", $options = false) {
		$sql = "";
		
		if (is_array($conditions)) {
			$join = $join ? strtoupper($join) : null;
			$join = $join == "AND" || $join == "OR" ? $join : "AND";
			
			foreach ($conditions as $key => $value) {
				$ukey = strtoupper($key);
				
				if ($ukey == "AND" || $ukey == "OR" || (is_numeric($key) && is_array($value))) {
					$sub_sql = is_array($value) ? self::getSQLConditions($value, $ukey, $key_table_name, $options) : (is_string($value) && $value ? $value : "");
					
					$sql .= $sub_sql ? ($sql ? " $join " : "") . "(" . $sub_sql . ")" : "";
				}
				else {
					$sql .= $sql ? " $join " : "";
					$key_str = self::prepareTableAttributeWithFunction($key, $key_table_name);
					
					if (is_array($value)) {
						$is_assoc = array_keys($value) !== range(0, count($value) - 1);
						
						if ($is_assoc)
							$value = array($value);
						
						$c = '';
						foreach ($value as $v) {
							$c .= ($c ? " $join " : "");
						
							if (is_array($v)) {
								$operator = "=";
								$val = "";
								$value_options = self::prepareValueOptions($options, $v);
								
								foreach ($v as $k => $a) {
									$k = strtolower($k);
									
									if ($k == "operator")
										$operator = strtolower($a);
									else if ($k == "value")
										$val = $a;
								}
								
								if ($operator == "in" || $operator == "not in")
									$c .= "$key_str $operator " . self::createBaseExprValueForOperatorIn($val, $value_options);
								else if ($operator == "is" || $operator == "is not")
									$c .= "$key_str $operator " . self::createBaseExprValueForOperatorIs($val, $value_options);
								else
									$c .= "$key_str $operator " . self::createBaseExprValue($val, $value_options);
							}
							else
								$c .= "$key_str = " . self::createBaseExprValue($v, $options);
						}
						$sql .= $c;
					}
					else
						$sql .= "$key_str = " . self::createBaseExprValue($value, $options);
				}
			}
		}
		
		return $sql;
	}
	
	protected static function getSQLSort($sort) {
		$sql = "";
		
		if(is_array($sort) && count($sort)) {
			foreach($sort as $sort_item) {
				if(is_array($sort_item)) {
					$sort_column = "";
					$sort_order = "";
					foreach($sort_item as $key => $value) {
						switch(strtolower($key)) {
							case "column": $sort_column = $value; break;
							case "order": $sort_order = $value; break;
						}
					}
					
					if($sort_column)
						$sql .= ($sql ? ", " : "") . self::getParsedSqlColumnName($sort_column) . " {$sort_order}";
				}
			}
		}
		return $sql;
	}
	
	protected static function prepareTableAttributeWithFunction($attr_name, $table_name = false) {
		if (strpos($attr_name, "(") !== false) {
			$start_pos = strrpos($attr_name, "(") + 1;
			$end_pos = strpos($attr_name, ")", $start_pos);
			$end_pos = $end_pos >= $start_pos ? $end_pos : strlen($attr_name);
			
			$prev = substr($attr_name, 0, $start_pos);
			$real_attr_name = substr($attr_name, $start_pos, $end_pos - $start_pos);
			$next = substr($attr_name, $end_pos);
			
			$real_attr_name = self::prepareTableAttributeName($real_attr_name, $table_name);
			
			return $prev . $real_attr_name . $next;
		}
		
		return self::prepareTableAttributeName($attr_name, $table_name);
	}
	
	protected static function prepareTableAttributeName($attr_name, $table_name = false) {
		$attr_name = trim($attr_name);
		$pos = strrpos($attr_name, ".");
		
		if ($pos !== false) {
			$tn = self::removeInvalidCharsFromName(substr($attr_name, 0, $pos));
			$table_name = $tn ? $tn : $table_name;
			
			$attr_name = self::removeInvalidCharsFromName(substr($attr_name, $pos + 1));
		}
		
		$tn = $table_name ? self::getParsedSqlTableName($table_name) . "." : "";
		$attr_name = $attr_name == "*" ? "*" : self::getParsedSqlColumnName($attr_name);
		
		return $tn . $attr_name;
	}
	
	protected static function prepareValueOptions($options, $value_options) {
		$options = is_array($options) ? $options : array();
		
		if (is_array($value_options) && isset($value_options["skip_reserved_words"]))
			$options["skip_reserved_words"] = $value_options["skip_reserved_words"];
		
		return $options;
	}
	
	//it's used in the DB too.
	protected static function createBaseExprValueForOperatorIn($value) {
		$value = is_array($value) ? implode(", ", $value) : $value;
		
		$values = array();
		$v = "";
		$open_single_quotes = false;
		$open_double_quotes = false;
		
		if (is_numeric($value))
			$value = (string)$value; //bc of php > 7.4 if we use $var[$i] gives an warning
		
		$t = strlen($value);
		for ($i = 0; $i < $t; $i++) {
			$c = $value[$i];
			
			if ($c == "," && !$open_single_quotes && !$open_double_quotes) {
				//$values[] = is_numeric($v) ? $v : "'" . addcslashes($v, "\\'") . "'";
				$values[] = self::createBaseExprValue($v);
				$v = "";
			}
			else if ($c == "'" && !$open_double_quotes && !TextSanitizer::isCharEscaped($value, $i)) 
				$open_single_quotes = !$open_single_quotes;
			else if ($c == '"' && !$open_single_quotes && !TextSanitizer::isCharEscaped($value, $i))
				$open_double_quotes = !$open_double_quotes;
			else if ($open_single_quotes || $open_double_quotes) 
				$v .= $c;
			else if ($v !== "" || $c != " ")
				$v .= $c;
		}
		
		if (strlen($v) || !count($values))
			//$values[] = is_numeric($v) ? $v : "'" . addcslashes($v, "\\'") . "'";
			$values[] = self::createBaseExprValue($v);
		
		return "(" . implode(", ", $values) . ")";
	}
	
	//it's used in the DB too.
	protected static function createBaseExprValueForOperatorIs($value) {
		$lv = strtolower($value);
		
		if ($lv == "null" || $lv == "true" || $lv == "false" || $lv == "unknown")
			return $value;
		
		return self::createBaseExprValue($value); //if $value is not allowed, return the value with "". This should give an sql error when executed to the DB, but at least the DB will not be hacked and we can see then that the sql query is wrong.
	}
	
	protected static function getParsedSqlTableName($table) {
		$sql = "";
		$parts = self::parseTableName($table);
		
		foreach ($parts as $part)
			if ($part)
				$sql .= ($sql ? "." : "") . "`" . $part . "`";
		
		return $sql;
	}
	
	protected static function parseTableName($table, $start_delimiter = "`", $end_delimiter = false) {
		if ($table) {
			if ($start_delimiter) {
				if (!$end_delimiter)
					$end_delimiter = $start_delimiter;
				
				$parts = array();
				$len = strlen($table);
				$start = 0;
				
				do {
					$pos = strpos($table, ".", $start); //split based in "."
					$pos_delimiter = strpos($table, $start_delimiter, $start); //split based in delimiters
					$delimiter_active = false;
					
					if ($pos_delimiter !== false && ($pos === false || $pos > $pos_delimiter)) {
						$pos = $pos_delimiter; //set pos to start delimiter
						$delimiter_active = true;
					}
					
					if ($pos === false) 
						$pos = $len; //set delimiter to length
					
					$str = trim( substr($table, $start, $pos - $start) );
					$start = $pos + 1;
					
					if ($str)
						$parts[] = $str;
					
					if ($delimiter_active && $start < $len) {
						$pos = strpos($table, $end_delimiter, $start);
						$pos = $pos !== false ? $pos : $len;
						
						$str = substr($table, $start, $pos - $start); //Don't trim $str bc is inside of enclosing, which means the user really wanted to leave a space here.
						$start = $pos + 1;
						
						if ($str)
							$parts[] = $str;
					}
				}
				while ($start < $len);
			}
			else
				$parts = explode(".", $table);
			
			return $parts;
		}
		
		return array($table); //this should be an empty value.
	}
	
	protected static function getParsedSqlColumnName($column) {
		return $column == "*" ? "*" : "`" . self::removeInvalidCharsFromName($column) . "`";
	}
	
	protected static function getTableFromColumn($column) {
		$table = null;
		
		if ($column && strpos($column, ".") !== false) {
			$c = explode(" ", $column);
			$parts = self::parseTableName($c[0]);
			array_pop($parts);
			$table = implode(".", $parts);
		}
		
		return $table;
	}
	
	protected static function getAlias($table) {
		$parts = explode(" ", trim($table));
		$alias = $parts[0];
		
		if (count($parts) > 1) {
			if (strtolower($parts[1]) != "as")
				$alias = $parts[1];
			else
				$alias = $parts[2];
		}
		
		return $alias;
	}
	
	protected static function getTableName($table) {
		$parts = explode(" ", $table);
		return $parts[0];
	}
	
	protected static function removeInvalidCharsFromName($name) {
		return trim(str_replace(array("'", '"', "`"), "", $name));
	}

    /* -------------------------------------------
       INTERNAL UTIL FUNCTIONS
    ------------------------------------------- */
	
	private function _buildAttributes($arr) {
        $cols = [];
        $vals = [];

        foreach ($arr as $key => $val) {
            $cols[] = "`$key`";
            $vals[] = $this->escape($val);
        }

        return [implode(",", $cols), implode(",", $vals)];
    }

    private function _buildSet($arr) {
        $parts = [];
        foreach ($arr as $key => $val) {
            $parts[] = "`$key` = " . $this->escape($val);
        }
        return implode(", ", $parts);
    }

    private function _buildConditions($conditions) {
        if (!$conditions || !is_array($conditions)) {
            return "";
        }

        $parts = [];
        foreach ($conditions as $key => $val) {
            $parts[] = "`$key` = " . $this->escape($val);
        }

        return "WHERE " . implode(" AND ", $parts);
    }

    private function escape($value) {
        if ($value === null) 
        		return "NULL";
        else if (is_numeric($value)) 
        		return $value;
       
        return "'" . $this->db->real_escape_string($value) . "'";
    }
}
?>
