<?php
/**
 * Simple caching system to reduce database queries
 */
class CacheManager {
    private $cache_dir;
    private $cache_ttl = 3600; // 1 hour default
    
    public function __construct($cache_dir = null) {
        if ($cache_dir === null) {
            $cache_dir = __DIR__ . '/../storage/cache/';
        }
        
        $this->cache_dir = $cache_dir;
        
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
    }
    
    /**
     * Get cached data
     */
    public function get($key, $default = null) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        // Check if cache has expired
        if ($data['expires'] < time()) {
            unlink($file);
            return $default;
        }
        
        return $data['value'];
    }
    
    /**
     * Store data in cache
     */
    public function set($key, $value, $ttl = null) {
        if ($ttl === null) {
            $ttl = $this->cache_ttl;
        }
        
        $file = $this->getCacheFile($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created_at' => time()
        ];
        
        file_put_contents($file, json_encode($data), LOCK_EX);
        return true;
    }
    
    /**
     * Delete cache entry
     */
    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
            return true;
        }
        return false;
    }
    
    /**
     * Clear all cache
     */
    public function flush() {
        $files = glob($this->cache_dir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFile($key) {
        $key = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->cache_dir . $key . '.cache';
    }
}

// Initialize cache manager
$cache = new CacheManager();

/**
 * Cache wrapper functions
 */
function cache_get($key, $default = null) {
    global $cache;
    return $cache->get($key, $default);
}

function cache_set($key, $value, $ttl = 3600) {
    global $cache;
    return $cache->set($key, $value, $ttl);
}

function cache_delete($key) {
    global $cache;
    return $cache->delete($key);
}

function cache_flush() {
    global $cache;
    return $cache->flush();
}

?>
