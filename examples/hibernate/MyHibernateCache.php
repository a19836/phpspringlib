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

include_once get_lib("sqlmap.hibernate.IHibernateClientCacheLayer");

class MyHibernateCache implements IHibernateClientCacheLayer {
    private $root_folder;
    private $ttl;

    public function __construct($root_folder, $ttl = 86400) {
        $this->root_folder = rtrim($root_folder, "/");
        $this->ttl = $ttl;
    }

    // Check if file exists and if TTL is still valid
    public function isValid($module_id, $service_id, $parameters = false, $options = false) {
        $file = $this->getCacheFilePath($module_id, $service_id, $parameters, $options);

        if (!file_exists($file))
            return false;

        // Check TTL expiration
        $file_mtime = filemtime($file);
        
        return time() - $file_mtime <= $this->ttl;
    }

    // Get cached result
    public function get($module_id, $service_id, $parameters = false, $options = false) {
        $file = $this->getCacheFilePath($module_id, $service_id, $parameters, $options);

        if (!file_exists($file))
            return null;

        $content = file_get_contents($file);
        
        return unserialize($content);
    }

    // Save result in cache
    public function check($module_id, $service_id, $parameters = false, $result = false, $options = false) {
        $file = $this->getCacheFilePath($module_id, $service_id, $parameters, $options);

        $dir = dirname($file);
        
        if (!is_dir($dir))
            mkdir($dir, 0777, true);

        return file_put_contents($file, serialize($result)) !== false;
    }

    // Generate file path based on: module_id, service_id, parameters, options
    private function getCacheFilePath($module_id, $service_id, $parameters = false, $options = false) {
        // Normalize input to avoid collisions
        $key_data = array(
            "module_id"  => $module_id,
            "service_id"  => $service_id,
            "parameters" => $parameters,
            "options" => $options
        );

        // Unique deterministic ID
        $cache_id = hash("crc32b", serialize($key_data));
			
        // You can also shard the cache for performance:
        $subfolder = substr($cache_id, 0, 2);

        return $this->root_folder . "/" . $subfolder . "/" . $cache_id . ".cache";
    }
}
?>
