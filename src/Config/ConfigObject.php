<?php

namespace App\Config;

/**
 * ConfigObject - Implementation of ConfigInterface
 * 
 * This class provides access to configuration variables
 * It should only be instantiated by the ConfigManager class
 * 
 * The ConfigObject is a immutable value object that contains
 * a subset of configuration values based on the caller's permissions.
 */
class ConfigObject implements ConfigInterface
{
    private array $variables;
    
    /**
     * Constructor
     * 
     * @param array $variables Variables this instance can access
     */
    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Configuration value or default
     */
    public function get(string $key, $default = null)
    {
        return $this->variables[$key] ?? $default;
    }
    
    /**
     * Get an integer configuration value
     * 
     * @param string $key Configuration key
     * @param int $default Default value if key doesn't exist or isn't an integer
     * @return int Configuration value or default
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key);
        return is_numeric($value) ? (int)$value : $default;
    }
    
    /**
     * Get a boolean configuration value
     * 
     * Treats 'true', '1', 'yes', 'on' as true (case-insensitive)
     * Treats 'false', '0', 'no', 'off' as false (case-insensitive)
     * 
     * @param string $key Configuration key
     * @param bool $default Default value if key doesn't exist or isn't a boolean
     * @return bool Configuration value or default
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $value = strtolower($value);
            if (in_array($value, ['true', '1', 'yes', 'on'])) {
                return true;
            }
            if (in_array($value, ['false', '0', 'no', 'off'])) {
                return false;
            }
        }
        
        return $default;
    }
    
    /**
     * Check if a configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool True if the key exists
     */
    public function has(string $key): bool
    {
        return isset($this->variables[$key]);
    }
    
    /**
     * Get all accessible configuration as an array
     * 
     * @return array All accessible configuration
     */
    public function all(): array
    {
        return $this->variables;
    }
    
    /**
     * Get all configuration keys
     * 
     * @return array All accessible configuration keys
     */
    public function keys(): array
    {
        return array_keys($this->variables);
    }
}