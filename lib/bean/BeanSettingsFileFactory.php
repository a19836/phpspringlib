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

include get_lib("bean.BeanFactoryCache");
include_once get_lib("bean.BeanXMLParser");
include get_lib("bean.exception.BeanSettingsFileFactoryException");
include_once get_lib("cache.xmlsettings.XmlSettingsCacheHandler");

class BeanSettingsFileFactory {
	private $BeanFactoryCache;
	
	public function __construct() {
		$this->BeanFactoryCache = new BeanFactoryCache();
	}
	
	public function getSettingsFromFile($file_path, $external_vars) {
		$settings = array();
		$settings_to_execute_php_code = array();
		
		$file_path_to_execute_php_code = $file_path . "_to_execute_php_code";
		
		$is_cache_active = $this->BeanFactoryCache->isActive();
		
		if($is_cache_active && $this->BeanFactoryCache->cachedFileExists($file_path)) {
			$settings = $this->BeanFactoryCache->getCachedFile($file_path);
			
			$settings_to_execute_php_code = $this->BeanFactoryCache->getCachedFile($file_path_to_execute_php_code);
		}
		else {
			if(!empty($file_path) && file_exists($file_path) && !is_dir($file_path)) {
				$content = file_get_contents($file_path);
				$settings = BeanXMLParser::parseXML($content, $external_vars, $file_path);
				
				self::prepareExtendedNodes($settings);
				
				$settings_to_execute_php_code = self::getSettingsKeysWithPHPCodeToExecute($settings);
				
				if(!is_array($settings)) $settings = array();
				if(!is_array($settings_to_execute_php_code)) $settings_to_execute_php_code = array();
				
				if ($is_cache_active) {
					//Last attr of setCachedFile must be true, otherwise the $settings/$file_path_to_execute_php_code will be merged with the old contents of the $file_path/$file_path_to_execute_php_code, and we don't want this. We only want the last values!!!
					//If the last attr is false, the $file_path_to_execute_php_code will be inacurated and this logic code won't work correctly because when we execute executeSettingsWithPHPCode, the $file_path_to_execute_php_code will only have the keys of the new $settings and not the old $settings. So please leave this as TRUE!!!
					$this->BeanFactoryCache->setCachedFile($file_path, $settings, true);
					$this->BeanFactoryCache->setCachedFile($file_path_to_execute_php_code, $settings_to_execute_php_code, true);
				}
			}
			else {
				launch_exception(new BeanSettingsFileFactoryException(1, $file_path));
			}
		}
		
		self::executeSettingsWithPHPCode($settings, $settings_to_execute_php_code, $external_vars);
				
		return $settings;
	}
	
	public static function getBeanSettingsByName($settings, $name) {
		if ($settings && !empty($name)) {
			$total = count($settings);
			for($i = 0; $i < $total; $i++) 
				if (!empty($settings[$i]["bean"])) {
					$bean_name = isset($settings[$i]["bean"]["name"]) ? $settings[$i]["bean"]["name"] : null;
					
					if ($bean_name == $name)
						return $settings[$i]["bean"];
				}
		}
		return false;
	}
	
	private static function prepareExtendedNodes(&$settings) {
		$total = $settings ? count($settings) : 0;
		for($i = 0; $i < $total; $i++) {
			$setting = $settings[$i];
			
			if(!empty($setting["bean"]["extend"])) {
				$sub_total = count($setting["bean"]["extend"]);
				for ($j = 0; $j < $sub_total; $j++) {
					$extended_class_name = $setting["bean"]["extend"][$j];
					$settings[$i]["bean"]["bean_to_extend"][$extended_class_name] = self::getBeanSettingsByName($settings, $extended_class_name);
				}
			}
		}
	}
	
	private static function getSettingsKeysWithPHPCodeToExecute($settings, $prefix = "") {
		$keys = array();
		
		$total = $settings ? count($settings) : 0;
		foreach ($settings as $key => $value) {
			$key_aux = is_numeric($key) ? $key : "'" . addcslashes($key, "\\'") . "'";
			
			if (is_array($value)) {
				$sub_keys = self::getSettingsKeysWithPHPCodeToExecute($value, $prefix . "[" . $key_aux . "]");
				$keys = array_merge($keys, $sub_keys);
			}
			else if (strpos($value, "&lt;?") !== false || strpos($value, "<?") !== false) {
				$keys[] = $prefix . "[" . $key_aux . "]";
			}
		}
		
		return $keys;
	}
	
	private static function executeSettingsWithPHPCode(&$settings, $settings_to_execute_php_code, $external_vars) {
		$total = $settings_to_execute_php_code ? count($settings_to_execute_php_code) : 0;
		
		for ($i = 0; $i < $total; $i++) {
			$key = $settings_to_execute_php_code[$i];
			eval("\$value = \$settings" . $key . ";");
			
			if ($value) {
				$value = str_replace("&lt;?", "<?", $value);
				$value = str_replace("?&gt;", "?>", $value);
				
				//error_log("\n$key:$value", 3, "/tmp/error.log");
				$value = PHPScriptHandler::parseContent($value, $external_vars);
				
				eval("\$settings" . $key . " = \$value;");
			}
		}
	}
	
	public function setCacheRootPath($dir_path) {
		$this->BeanFactoryCache->initCacheDirPath($dir_path);
	}
	
	public function setCacheHandler(XmlSettingsCacheHandler $XmlSettingsCacheHandler) {
		$this->BeanFactoryCache->setCacheHandler($XmlSettingsCacheHandler);
	}
}
?>
