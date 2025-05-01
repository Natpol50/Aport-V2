<?php

namespace App\Core;

use App\Config\ConfigManager;
use App\Config\ConfigObject;
use App\Controllers\ErrorController;
use App\Middleware\AuthMiddleware;
use App\Middleware\LanguageMiddleware;
use App\Services\Database;
use App\Services\TokenService;
use App\Services\CacheService;
use App\Services\LogService;

/**
 * Application - Main application class
 * 
 * This class is responsible for setting up the environment,
 * registering routes, initializing middleware, and handling 
 * the request/response cycle.
 */
class Application
{
    private Router $router;
    private ConfigManager $configManager;
    private Database $database;
    private RequestObject $request;
    private ErrorHandler $errorHandler;
    private LogService $logger;
    private bool $debugMode = false;
    
    /**
     * Create a new Application instance
     */
    public function __construct()
    {
        try {
            // Initialize Config Manager
            $this->configManager = ConfigManager::getInstance();
            
            // Get config for this class
            $config = $this->configManager->getConfigFor($this);
            
            // Set debug mode from configuration
            $this->debugMode = $config->getBool('APP_DEBUG', false);
            
            // Initialize the error handler with debug mode
            $this->errorHandler = new ErrorHandler($this->debugMode);
            
            // Register error handling
            $this->errorHandler->register();
            
            // Initialize logger
            $this->logger = new LogService($config->get('LOG_DIR', dirname(__DIR__, 2) . '/var/logs'));
            
            // Set logger for error handler
            $this->errorHandler->setLogger($this->logger);
            
            // Set up ConfigManager logger
            $this->configManager->setLogger($this->logger);
            
            // Initialize the Router
            $this->router = new Router();
            
            // Initialize the Database connection with retry mechanism
            $this->initializeDatabase();
            
            // Initialize cache service
            $cacheService = new CacheService();
            
            // Initialize services
            $tokenConfig = $this->getJwtConfig();
            $tokenService = new TokenService($tokenConfig, $cacheService);
            
            // Setup authentication middleware
            $authMiddleware = new AuthMiddleware($tokenService, $cacheService);
            $authMiddleware->setLogger($this->logger);
            
            // Try to authenticate the user
            $this->request = $authMiddleware->handle() ?? new RequestObject();
            
            // Setup language middleware
            $languageMiddleware = new LanguageMiddleware($this->request, $cacheService);
            $this->request = $languageMiddleware->handle();
            
            // Register routes
            $this->registerRoutes();
            
        } catch (\Throwable $e) {
            // If an error occurs during initialization, let the error handler deal with it
            if (isset($this->errorHandler)) {
                $this->errorHandler->handleException($e);
            } else {
                // Fallback if error handler isn't initialized yet
                http_response_code(500);
                echo 'Fatal error during application initialization: ' . $e->getMessage();
                exit(1);
            }
        }
    }
    
    /**
     * Initialize the database connection with retry mechanism
     * 
     * @param int $maxRetries Maximum number of connection retry attempts
     * @return void
     */
    private function initializeDatabase(int $maxRetries = 3): void
    {
        $attempt = 0;
        $lastException = null;
        
        while ($attempt < $maxRetries) {
            try {
                $this->database = new Database();
                
                // Test connection
                $this->database->getConnection();
                
                // Connection successful
                if ($attempt > 0) {
                    $this->logger->info("Database connection established after {$attempt} retries");
                }
                
                return;
            } catch (\Throwable $e) {
                $lastException = $e;
                $attempt++;
                
                if ($attempt < $maxRetries) {
                    // Log retry attempt
                    $this->logger->warning("Database connection failed (attempt {$attempt}): {$e->getMessage()}, retrying...");
                    
                    // Wait before retrying (exponential backoff)
                    $waitTime = pow(2, $attempt - 1) * 100000; // microseconds (0.1s, 0.2s, 0.4s, ...)
                    usleep($waitTime);
                }
            }
        }
        
        // All retries failed
        $this->logger->critical("Database connection failed after {$maxRetries} attempts");
        throw $lastException;
    }
    
    /**
     * Create JWT configuration from environment variables
     *
     * @return ConfigObject
     */
    private function getJwtConfig(): ConfigObject
    {
        // Create a configuration array with JWT settings
        $jwtConfigArray = [
            'JWT_SECRET' => $_ENV['JWT_SECRET'] ?? 'default_secret_key',
            'JWT_NAME' => $_ENV['JWT_NAME'] ?? 'portfolio_token',
            'JWT_EXPIRY' => $_ENV['JWT_EXPIRY'] ?? 1800,
            'JWT_REFRESH_EXPIRY' => $_ENV['JWT_REFRESH_EXPIRY'] ?? 604800
        ];
        
        // Return a new ConfigObject with JWT settings
        return new ConfigObject($jwtConfigArray);
    }
    
         /**
     * Register application routes
     */
    private function registerRoutes(): void
    {
        // Home routes
        $this->router->get('/', 'HomeController@index');
        $this->router->get('/en', 'HomeController@indexEn');
        
        // Contact routes
        $this->router->get('/contact', 'HomeController@contact');
        $this->router->get('/contact-en', 'HomeController@contactEn');
        $this->router->post('/contact/submit', 'HomeController@submitContact');
        
        // Auth routes
        $this->router->get('/login', 'AuthController@loginForm');
        $this->router->post('/login', 'AuthController@login');
        $this->router->get('/logout', 'AuthController@logout');
        $this->router->get('/register', 'AuthController@registerForm');
        $this->router->post('/register', 'AuthController@register');
        $this->router->get('/forgot-password', 'AuthController@forgotPasswordForm');
        $this->router->post('/forgot-password', 'AuthController@forgotPassword');
        $this->router->post('/reset-code', 'AuthController@verifyResetCode');
        $this->router->get('/reset-password', 'AuthController@resetPasswordForm');
        $this->router->post('/reset-password', 'AuthController@resetPassword');
        
        // Project routes
        $this->router->get('/projects', 'ProjectController@index');
        $this->router->get('/projects/type/{type}', 'ProjectController@byType');
        $this->router->get('/projects/status/{status}', 'ProjectController@byStatus');
        $this->router->get('/project/{id}', 'ProjectController@show');
        
        // Admin routes (protected)
        $this->router->get('/admin', 'AdminController@dashboard');
        $this->router->get('/admin/profile', 'AdminController@profile');
        $this->router->post('/admin/profile/update', 'AdminController@updateProfile');
        $this->router->get('/admin/projects', 'AdminController@projects');
        $this->router->get('/admin/project/new', 'AdminController@newProject');
        $this->router->post('/admin/project/create', 'AdminController@createProject');
        $this->router->get('/admin/project/edit/{id}', 'AdminController@editProject');
        $this->router->post('/admin/project/update/{id}', 'AdminController@updateProject');
        $this->router->post('/admin/project/delete/{id}', 'AdminController@deleteProject');
        $this->router->get('/admin/personal-info', 'AdminController@personalInfo');
        $this->router->post('/admin/personal-info/update', 'AdminController@updatePersonalInfo');
        
        // Error routes
        $this->router->get('/404', 'ErrorController@notFound');
        $this->router->get('/403', 'ErrorController@forbidden');
        $this->router->get('/500', 'ErrorController@serverError');
        $this->router->get('/maintenance', 'ErrorController@maintenance');
    }
    
    /**
     * Run the application
     */
    public function run(): void
    {
        try {
            // Get the current request method and URI
            $method = $_SERVER['REQUEST_METHOD'];
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            
            // Check for maintenance mode
            if ($this->isMaintenanceMode() && !$this->isMaintenanceExempt($uri)) {
                // Display maintenance page
                $errorController = new ErrorController();
                $errorController->maintenance($this->request);
                return;
            }
            
            // Dispatch the request to the appropriate controller/action
            $this->router->dispatch($method, $uri, $this->request);
            
        } catch (\Throwable $e) {
            // Let the error handler deal with any exceptions
            $this->errorHandler->handleException($e);
        }
    }
    
    /**
     * Check if the application is in maintenance mode
     * 
     * @return bool True if maintenance mode is enabled
     */
    private function isMaintenanceMode(): bool
    {
        return file_exists(dirname(__DIR__, 2) . '/storage/framework/maintenance.php');
    }
    
    /**
     * Check if the current route is exempt from maintenance mode
     * 
     * @param string $uri Current URI
     * @return bool True if the route is exempt
     */
    private function isMaintenanceExempt(string $uri): bool
    {
        // Always allow these routes during maintenance
        $exemptRoutes = [
            '/maintenance',  // Maintenance page itself
            '/login/admin',  // Admin login (to disable maintenance)
            '/admin/toggle-maintenance', // Toggle maintenance mode
        ];
        
        return in_array($uri, $exemptRoutes);
    }
    
    /**
     * Get the application's debug mode setting
     * 
     * @return bool True if debug mode is enabled
     */
    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }
    
    /**
     * Get the application's current request object
     * 
     * @return RequestObject Current request object
     */
    public function getRequest(): RequestObject
    {
        return $this->request;
    }
    
    /**
     * Get the application's router
     * 
     * @return Router Router instance
     */
    public function getRouter(): Router
    {
        return $this->router;
    }
    
    /**
     * Get the application's database connection
     * 
     * @return Database Database connection
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }
    
    /**
     * Get the application's logger
     * 
     * @return LogService Logger instance
     */
    public function getLogger(): LogService
    {
        return $this->logger;
    }
}