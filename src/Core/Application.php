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
    
    /**
     * Create a new Application instance
     */
    public function __construct()
    {
        // Initialize Config Manager
        $this->configManager = ConfigManager::getInstance();
        
        // Get config for this class
        $config = $this->configManager->getConfigFor($this);
        
        // Initialize the Router
        $this->router = new Router();
        
        // Initialize the Database connection
        $this->database = new Database();
        
        // Initialize services
        // Get JWT configuration from environment
        $jwtConfig = $this->getJwtConfig();
        $tokenService = new TokenService($jwtConfig);
        $cacheService = new CacheService();
        
        // Setup authentication middleware
        $authMiddleware = new AuthMiddleware($tokenService, $cacheService);
        
        // Try to authenticate the user
        $this->request = $authMiddleware->handle() ?? new RequestObject();
        
        // Setup language middleware
        $languageMiddleware = new LanguageMiddleware($this->request);
        $this->request = $languageMiddleware->handle();
        
        // Register routes
        $this->registerRoutes();
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
        
        // Admin routes (protected)
        $this->router->get('/admin', 'AdminController@dashboard');
        $this->router->get('/admin/projects', 'AdminController@projects');
        $this->router->get('/admin/project/new', 'AdminController@newProject');
        $this->router->post('/admin/project/create', 'AdminController@createProject');
        $this->router->get('/admin/project/edit/{id}', 'AdminController@editProject');
        $this->router->post('/admin/project/update/{id}', 'AdminController@updateProject');
        $this->router->post('/admin/project/delete/{id}', 'AdminController@deleteProject');
        
        // Error routes
        $this->router->get('/404', 'ErrorController@notFound');
        $this->router->get('/500', 'ErrorController@serverError');
    }
    
    /**
     * Run the application
     */
    public function run(): void
    {
        // Get the current request method and URI
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        try {
            // Dispatch the request to the appropriate controller/action
            $this->router->dispatch($method, $uri, $this->request);
        } catch (\Exception $e) {
            // Handle exceptions
            $this->handleException($e);
        }
    }
    
    /**
     * Handle uncaught exceptions
     * 
     * @param \Exception $exception The exception to handle
     */
    private function handleException(\Exception $exception): void
    {
        // Log the exception
        error_log($exception->getMessage() . "\n" . $exception->getTraceAsString());
        
        // Create error controller
        $errorController = new ErrorController();
        
        // Determine the appropriate error method based on exception
        if ($exception instanceof \App\Exceptions\NotFoundException) {
            // 404 Not Found
            $errorController->notFound($this->request);
        } else {
            // 500 Server Error
            $errorController->serverError($this->request, $exception);
        }
    }
}