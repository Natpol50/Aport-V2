<?php

namespace App\Config;

use Psr\Log\LoggerInterface;
use App\Exceptions\ConfigException;

/**
 * ConfigManager - Secure configuration management using Reflection API
 * 
 * This class provides controlled access to environment variables from .env files
 * using PHP's Reflection API to verify the requesting class.
 * 
 * The access control mechanism ensures that sensitive configuration values
 * (like database credentials or API secrets) are only accessible to authorized classes.
 */
class ConfigManager
{
    private static ?ConfigManager $instance = null;
    private array $variables = [];
    private array $accessMap = [];
    private ?LoggerInterface $logger = null;
    
    /**
     * Private constructor to prevent direct instantiation
     * 
     * Loads environment variables and initializes the access map.
     */
    private function __construct()
    {
        try {
            // Load environment variables using PHP dotenv
            $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
            $dotenv->load();
            
            // Store all variables in our private array
            foreach ($_ENV as $key => $value) {
                $this->variables[$key] = $value;
            }
            
            $this->initializeAccessMap();
        } catch (\Throwable $e) {
            // If we can't load the environment variables, we should fail early
            throw new ConfigException(
                "Failed to initialize configuration: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Define which classes can access which variables
     * 
     * This method defines the security perimeter for configuration access.
     * Each class is only allowed to access specific environment variables.
     */
    private function initializeAccessMap(): void
    {
        // Define access permissions - which classes can access which env variables
        $this->accessMap = [
            // Database service needs database credentials
            'App\\Services\\Database' => [
                'DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME', 'DB_PORT'
            ],
            // Token service needs JWT configuration
            'App\\Services\\TokenService' => [
                'JWT_SECRET', 'JWT_NAME', 'JWT_EXPIRY', 'JWT_REFRESH_EXPIRY'
            ],
            // Core application needs general app settings
            'App\\Core\\Application' => [
                'APP_ENV', 'APP_DEBUG', 'APP_URL', 'STATIC_URL', 'DEFAULT_LANGUAGE'
            ],
            // Cache service needs cache configuration
            'App\\Services\\CacheService' => [
                'CACHE_DIR', 'CACHE_TTL'
            ],
            // Translation service needs language settings
            'App\\Services\\TranslationService' => [
                'DEFAULT_LANGUAGE', 'AVAILABLE_LANGUAGES'
            ],
            // Email service needs SMTP configuration
            'App\\Services\\EmailService' => [
                'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 
                'MAIL_ENCRYPTION', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME'
            ],
            // Request object needs language settings
            'App\\Core\\RequestObject' => [
                'DEFAULT_LANGUAGE'
            ],
            // Base controller and all controllers that extend it
            'App\\Controllers\\BaseController' => [
                'APP_ENV', 'APP_DEBUG', 'APP_URL', 'STATIC_URL', 'DEFAULT_LANGUAGE'
            ],
            // Auth controller needs additional access
            'App\\Controllers\\AuthController' => [
                'APP_ENV', 'APP_DEBUG', 'APP_URL', 'STATIC_URL', 'DEFAULT_LANGUAGE', 
                'JWT_SECRET', 'JWT_NAME', 'JWT_EXPIRY', 'JWT_REFRESH_EXPIRY'
            ]
        ];
    }
    
    /**
     * Prevent cloning of the singleton instance
     */
    private function __clone(): void {}
    
    /**
     * Prevent unserializing of the singleton instance
     * 
     * @throws ConfigException If an attempt is made to unserialize the instance
     */
    public function __wakeup(): void
    {
        throw new ConfigException("Cannot unserialize singleton");
    }
    
    /**
     * Get the singleton instance
     * 
     * @return ConfigManager The singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Set a logger for the ConfigManager
     * 
     * @param LoggerInterface $logger PSR-3 compatible logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
    
    /**
     * Get configuration access for a specific object using Reflection
     * 
     * @param object $service The object requesting configuration
     * @return ConfigInterface A restricted configuration access object
     * @throws ConfigException If the calling class is not authorized
     */
    public function getConfigFor(object $service): ConfigInterface
    {
        try {
            // Use Reflection to get the actual class of the calling object
            $reflection = new \ReflectionObject($service);
            $className = $reflection->getName();
            
            // Get parent class if exists - this helps with inheritance
            if (!isset($this->accessMap[$className])) {
                $parentClass = $reflection->getParentClass();
                if ($parentClass) {
                    $className = $parentClass->getName();
                }
            }
            
            // Check if this class is allowed in the access map
            if (!isset($this->accessMap[$className])) {
                // Log the unauthorized access attempt if a logger is available
                if ($this->logger) {
                    $this->logger->warning("Unauthorized config access attempt", [
                        'class' => $className,
                        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
                    ]);
                }
                
                throw new ConfigException("Class '$className' is not authorized to access configuration");
            }
            
            // Get the allowed keys for this class
            $allowedKeys = $this->accessMap[$className];
            $accessibleVariables = [];
            
            // Filter the variables based on allowed keys
            foreach ($allowedKeys as $key) {
                if (isset($this->variables[$key])) {
                    $accessibleVariables[$key] = $this->variables[$key];
                } else if ($this->logger) {
                    // Log missing but required configuration values
                    $this->logger->notice("Missing configuration value", [
                        'key' => $key,
                        'class' => $className
                    ]);
                }
            }
            
            // Return a ConfigObject with only the allowed variables
            return new ConfigObject($accessibleVariables);
        } catch (ConfigException $e) {
            // Rethrow ConfigExceptions as they're already properly formatted
            throw $e;
        } catch (\Throwable $e) {
            // Convert any other exceptions to ConfigExceptions
            throw new ConfigException(
                "Error accessing configuration: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Check if a specific environment variable exists
     * 
     * This method is used primarily for testing and validation purposes.
     * It doesn't expose the actual value, just whether it exists.
     * 
     * @param string $key The environment variable key
     * @return bool True if the variable exists
     */
    public function hasVariable(string $key): bool
    {
        return isset($this->variables[$key]);
    }
}