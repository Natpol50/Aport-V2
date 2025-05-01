<?php

namespace App\Config;

/**
 * ConfigInterface - Interface for configuration access, making it easier to mock
 * 
 * This interface defines the methods that configuration access objects must implement.
 * It provides a contract for accessing configuration values in a type-safe manner.
 */
interface ConfigInterface
{
    /**
     * Get a configuration value
     * 
     * @param string $key The configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The configuration value or default
     */
    public function get(string $key, $default = null);
    
    /**
     * Get an integer configuration value
     * 
     * @param string $key The configuration key
     * @param int $default Default value if key doesn't exist or isn't an integer
     * @return int The configuration value or default
     */
    public function getInt(string $key, int $default = 0): int;
    
    /**
     * Get a boolean configuration value
     * 
     * @param string $key The configuration key
     * @param bool $default Default value if key doesn't exist or isn't a boolean
     * @return bool The configuration value or default
     */
    public function getBool(string $key, bool $default = false): bool;
    
    /**
     * Check if a configuration key exists
     * 
     * @param string $key The configuration key
     * @return bool True if the key exists
     */
    public function has(string $key): bool;
    
    /**
     * Get all accessible configuration as an array
     * 
     * @return array All accessible configuration
     */
    public function all(): array;
    
    /**
     * Get all configuration keys
     * 
     * @return array All accessible configuration keys
     */
    public function keys(): array;
}