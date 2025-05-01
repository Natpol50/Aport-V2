<?php

namespace App\Core;

use App\Controllers\ErrorController;
use App\Exceptions\AuthenticationException;
use App\Exceptions\ConfigException;
use App\Exceptions\ControllerException;
use App\Exceptions\DatabaseException;
use App\Exceptions\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * ErrorHandler - Centralized error and exception handling
 * 
 * This class provides consistent error and exception handling across the application.
 * It logs errors appropriately and renders user-friendly error pages.
 */
class ErrorHandler
{
    private ?LoggerInterface $logger = null;
    private bool $debugMode;
    
    /**
     * Create a new ErrorHandler instance
     * 
     * @param bool $debugMode Whether debug mode is enabled
     */
    public function __construct(bool $debugMode = false)
    {
        $this->debugMode = $debugMode;
    }
    
    /**
     * Set a logger for the error handler
     * 
     * @param LoggerInterface $logger PSR-3 compatible logger
     * @return self
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }
    
    /**
     * Register error and exception handlers
     * 
     * @return void
     */
    public function register(): void
    {
        // Set custom exception handler
        set_exception_handler([$this, 'handleException']);
        
        // Set custom error handler
        set_error_handler([$this, 'handleError']);
        
        // Register shutdown function to catch fatal errors
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Handle uncaught exceptions
     * 
     * @param \Throwable $exception The exception to handle
     * @return void
     */
    public function handleException(\Throwable $exception): void
    {
        // Log the exception
        $this->logException($exception);
        
        // Create request object for error controller
        $request = new RequestObject();
        
        // Create error controller
        $errorController = new ErrorController();
        
        // Determine the appropriate error method based on exception type
        if ($exception instanceof NotFoundException) {
            http_response_code(404);
            $errorController->notFound($request);
        } elseif ($exception instanceof AuthenticationException) {
            http_response_code(401);
            // Store current URL for redirection after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            $errorController->unauthenticated($request, $exception);
        } elseif ($exception instanceof \App\Exceptions\ForbiddenException) {
            http_response_code(403);
            $errorController->forbidden($request);
        } else {
            http_response_code(500);
            $errorController->serverError($request, $exception);
        }
        
        exit(1);
    }
    
    /**
     * Handle PHP errors
     * 
     * @param int $level Error level
     * @param string $message Error message
     * @param string $file File where the error occurred
     * @param int $line Line number where the error occurred
     * @return bool Whether the error has been handled
     */
    public function handleError(int $level, string $message, string $file, int $line): bool
    {
        // Convert errors to ErrorException if they match error_reporting level
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        
        // Otherwise, let PHP handle the error
        return false;
    }
    
    /**
     * Handle script shutdown and catch fatal errors
     * 
     * @return void
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Clean any output buffers
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Log the fatal error
            $this->logError($error['type'], $error['message'], $error['file'], $error['line']);
            
            // Create request object for error controller
            $request = new RequestObject();
            
            // Create error controller
            $errorController = new ErrorController();
            
            // Display server error page
            http_response_code(500);
            $errorController->fatalError($request, $error);
            
            exit(1);
        }
    }
    
    /**
     * Log an exception
     * 
     * @param \Throwable $exception The exception to log
     * @return void
     */
    private function logException(\Throwable $exception): void
    {
        $severity = $this->getSeverityLevel($exception);
        
        $message = $exception->getMessage();
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'previous' => $exception->getPrevious() ? [
                'message' => $exception->getPrevious()->getMessage(),
                'file' => $exception->getPrevious()->getFile(),
                'line' => $exception->getPrevious()->getLine(),
            ] : null,
        ];
        
        if ($this->logger) {
            // Log with appropriate level using PSR-3 logger
            $this->logger->log($severity, $message, $context);
        } else {
            // Fall back to error_log
            error_log(sprintf(
                "%s: %s in %s on line %d\nStack trace:\n%s", 
                get_class($exception),
                $message,
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            ));
        }
    }
    
    /**
     * Log a PHP error
     * 
     * @param int $level Error level
     * @param string $message Error message
     * @param string $file File where the error occurred
     * @param int $line Line number where the error occurred
     * @return void
     */
    private function logError(int $level, string $message, string $file, int $line): void
    {
        $severity = $this->getErrorSeverity($level);
        
        $context = [
            'file' => $file,
            'line' => $line,
        ];
        
        if ($this->logger) {
            // Log with appropriate level using PSR-3 logger
            $this->logger->log($severity, $message, $context);
        } else {
            // Fall back to error_log
            error_log(sprintf(
                "PHP %s: %s in %s on line %d", 
                $this->getErrorName($level),
                $message,
                $file,
                $line
            ));
        }
    }
    
    /**
     * Get severity level for an exception
     * 
     * @param \Throwable $exception The exception
     * @return string PSR-3 log level
     */
    private function getSeverityLevel(\Throwable $exception): string
    {
        // Map exception types to PSR-3 log levels
        if ($exception instanceof NotFoundException) {
            return 'notice';
        } elseif ($exception instanceof AuthenticationException) {
            return 'warning';
        } elseif ($exception instanceof ConfigException) {
            return 'critical';
        } elseif ($exception instanceof DatabaseException) {
            return 'error';
        } elseif ($exception instanceof ControllerException) {
            return 'error';
        } elseif ($exception instanceof \ErrorException) {
            return $this->getErrorSeverity($exception->getSeverity());
        }
        
        // Default to error level
        return 'error';
    }
    
    /**
     * Get PSR-3 severity level for a PHP error
     * 
     * @param int $level PHP error level
     * @return string PSR-3 log level
     */
    private function getErrorSeverity(int $level): string
    {
        // Map PHP error levels to PSR-3 log levels
        switch ($level) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                return 'critical';
                
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return 'warning';
                
            case E_PARSE:
                return 'alert';
                
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'notice';
                
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return 'info';
                
            default:
                return 'warning';
        }
    }
    
    /**
     * Get human-readable name for PHP error level
     * 
     * @param int $level PHP error level
     * @return string Error name
     */
    private function getErrorName(int $level): string
    {
        // Map PHP error levels to names
        $levels = [
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Standards',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];
        
        return $levels[$level] ?? 'Unknown Error';
    }
}