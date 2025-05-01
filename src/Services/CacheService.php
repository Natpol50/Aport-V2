<?php

namespace App\Services;

use App\Config\ConfigManager;
use App\Config\ConfigInterface;

/**
 * CacheService - File-based caching service
 * 
 * This class provides caching functionality for storing and retrieving
 * frequently accessed data to improve performance.
 * 
 * The cache uses a simple file-based storage mechanism with JSON serialization.
 * Each cache entry has an expiration time and supports automatic cleanup of expired entries.
 */
class CacheService
{
    private string $cacheDir;
    private int $defaultTtl;
    private ConfigInterface $config;
    private bool $enabled;
    
    /**
     * Create a new CacheService instance
     * 
     * @param string|null $cacheDir Cache directory (default: from config or ROOT_DIR/var/cache)
     * @param int|null $defaultTtl Default time-to-live in seconds (default: from config or 3600)
     */
    public function __construct(?string $cacheDir = null, ?int $defaultTtl = null)
    {
        // Try to get config
        try {
            $configManager = ConfigManager::getInstance();
            $this->config = $configManager->getConfigFor($this);
            
            // Check if caching is enabled
            $this->enabled = $this->config->getBool('CACHE_ENABLED', true);
            
            // Get cache directory from config if not provided
            if ($cacheDir === null) {
                $cacheDir = $this->config->get('CACHE_DIR');
            }
            
            // Get default TTL from config if not provided
            if ($defaultTtl === null) {
                $defaultTtl = $this->config->getInt('CACHE_TTL', 3600);
            }
        } catch (\Exception $e) {
            // If config is not available, use defaults
            $this->enabled = true;
            $defaultTtl = $defaultTtl ?? 3600;
        }
        
        // Fall back to default cache directory if not set
        if (empty($cacheDir)) {
            $cacheDir = defined('ROOT_DIR') ? ROOT_DIR . '/var/cache' : dirname(__DIR__, 2) . '/var/cache';
        }
        
        $this->cacheDir = $cacheDir;
        $this->defaultTtl = $defaultTtl ?? 3600;
        
        // Ensure cache directory exists
        if ($this->enabled && !is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached role permissions
     * 
     * @param int $roleId Role ID
     * @return array Role permissions
     */
    public function getRolePermission(int $roleId): array
    {
        $cacheKey = "role_permission_$roleId";
        
        // Try to get from cache first
        $cachedData = $this->get($cacheKey);
        
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // If not in cache, load from database (simplified for this example)
        // In a real application, this would query the database
        
        // For this example, we'll use hardcoded permissions
        $permissions = [
            1 => [
                'id' => 1,
                'name' => 'Admin',
                'view_projects' => 1,
                'edit_projects' => 1,
                'delete_projects' => 1,
                'view_personal_info' => 1,
                'edit_personal_info' => 1,
                'manage_users' => 1
            ],
            2 => [
                'id' => 2,
                'name' => 'Manager',
                'view_projects' => 1,
                'edit_projects' => 1,
                'delete_projects' => 0,
                'view_personal_info' => 1,
                'edit_personal_info' => 0,
                'manage_users' => 0
            ],
            3 => [
                'id' => 3,
                'name' => 'Editor',
                'view_projects' => 1,
                'edit_projects' => 1,
                'delete_projects' => 0,
                'view_personal_info' => 1,
                'edit_personal_info' => 1,
                'manage_users' => 0
            ],
            4 => [
                'id' => 4,
                'name' => 'User',
                'view_projects' => 1,
                'edit_projects' => 0,
                'delete_projects' => 0,
                'view_personal_info' => 1,
                'edit_personal_info' => 0,
                'manage_users' => 0
            ],
            5 => [
                'id' => 5,
                'name' => 'Student',
                'view_projects' => 1,
                'edit_projects' => 0,
                'delete_projects' => 0,
                'view_personal_info' => 1,
                'edit_personal_info' => 0,
                'manage_users' => 0
            ]
        ];
        
        // Default permissions for unknown roles
        $rolePermissions = $permissions[$roleId] ?? [
            'id' => $roleId,
            'name' => 'Unknown',
            'view_projects' => 0,
            'edit_projects' => 0,
            'delete_projects' => 0,
            'view_personal_info' => 0,
            'edit_personal_info' => 0,
            'manage_users' => 0
        ];
        
        // Cache the permissions
        $this->set($cacheKey, $rolePermissions, 86400); // 24 hours
        
        return $rolePermissions;
    }
    
    /**
     * Get a value from cache
     * 
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found/expired
     */
    public function get(string $key)
    {
        if (!$this->enabled) {
            return null;
        }
        
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        try {
            $content = file_get_contents($filename);
            $data = json_decode($content, true);
            
            // Check if data is expired
            if ($data['expires'] < time()) {
                // Remove expired cache file
                @unlink($filename);
                return null;
            }
            
            return $data['value'];
        } catch (\Exception $e) {
            // If there's any error reading or parsing the cache, return null
            error_log("Cache read error for key '$key': " . $e->getMessage());
            return null;
        }
    }
    
         /**
     * Set a value in cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time-to-live in seconds (null for default)
     * @return bool True on success
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $ttl = $ttl ?? $this->defaultTtl;
        
        $data = [
            'expires' => time() + $ttl,
            'value' => $value
        ];
        
        $filename = $this->getCacheFilename($key);
        
        try {
            // Create directory if it doesn't exist
            $dir = dirname($filename);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Write to a temporary file first to avoid race conditions
            $tempFile = $filename . '.tmp';
            $result = file_put_contents($tempFile, json_encode($data), LOCK_EX);
            
            if ($result === false) {
                return false;
            }
            
            // Atomic rename to ensure consistency
            return rename($tempFile, $filename);
        } catch (\Exception $e) {
            error_log("Cache write error for key '$key': " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a value from cache
     * 
     * @param string $key Cache key
     * @return bool True if the cache was deleted or didn't exist
     */
    public function delete(string $key): bool
    {
        if (!$this->enabled) {
            return true;
        }
        
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return @unlink($filename);
        }
        
        return true;
    }
    
    /**
     * Clear all cache files
     * 
     * @param string|null $prefix Optional prefix to limit clearing to specific keys
     * @return bool True on success
     */
    public function clear(?string $prefix = null): bool
    {
        if (!$this->enabled) {
            return true;
        }
        
        try {
            if ($prefix) {
                // Only clear files with a specific prefix
                $prefixPattern = $this->getCacheFilename($prefix . '*');
                $prefixPattern = str_replace('*', '', $prefixPattern);
                $files = glob($prefixPattern . '*');
            } else {
                // Clear all cache files
                $files = glob($this->cacheDir . '/*.cache');
            }
            
            if (empty($files)) {
                return true;
            }
            
            $success = true;
            foreach ($files as $file) {
                if (is_file($file) && !@unlink($file)) {
                    $success = false;
                }
            }
            
            return $success;
        } catch (\Exception $e) {
            error_log("Cache clear error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean expired cache entries
     * 
     * @return int Number of cleared entries
     */
    public function cleanExpired(): int
    {
        if (!$this->enabled) {
            return 0;
        }
        
        $count = 0;
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            try {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                
                if (!$data || !isset($data['expires']) || $data['expires'] < time()) {
                    if (@unlink($file)) {
                        $count++;
                    }
                }
            } catch (\Exception $e) {
                // Skip problematic files
                continue;
            }
        }
        
        return $count;
    }
    
    /**
     * Get the filename for a cache key
     * 
     * @param string $key Cache key
     * @return string Cache filename
     */
    private function getCacheFilename(string $key): string
    {
        // Create a safe filename from the key
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        
        // Use a hash for uniqueness and to handle long keys
        $hash = md5($key);
        
        return $this->cacheDir . '/' . $safeKey . '_' . $hash . '.cache';
    }
}