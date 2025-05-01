<?php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Services\TranslationService;

/**
 * ErrorController - Handles error pages
 * 
 * This controller is responsible for rendering error pages
 * such as 404 Not Found, 403 Forbidden, 401 Unauthorized, and 500 Server Error.
 */
class ErrorController extends BaseController
{
    /**
     * Display 404 Not Found page
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function notFound(RequestObject $request): void
    {
        // Set HTTP status code
        http_response_code(404);
        
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Render 404 page
        echo $this->render('errors/404', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode
        ]);
    }
    
    /**
     * Display 401 Unauthorized page
     * 
     * @param RequestObject $request Current request information
     * @param \Throwable|null $exception The exception that caused the error
     * @return void
     */
    public function unauthenticated(RequestObject $request, ?\Throwable $exception = null): void
    {
        // Set HTTP status code
        http_response_code(401);
        
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Log the exception
        if ($exception) {
            error_log($exception->getMessage());
        }
        
        // Set message in session
        $_SESSION['error'] = [
            $translationService->translate('error.login_required')
        ];
        
        // Redirect to login page
        header('Location: /login');
        exit;
    }
    
    /**
     * Display 500 Server Error page
     * 
     * @param RequestObject $request Current request information
     * @param \Throwable|null $exception The exception that caused the error
     * @return void
     */
    public function serverError(RequestObject $request, ?\Throwable $exception = null): void
    {
        // Set HTTP status code
        http_response_code(500);
        
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Log the exception
        if ($exception) {
            error_log($exception->getMessage());
            error_log($exception->getTraceAsString());
        }
        
        // Only show exception details in debug mode
        $exceptionDetails = null;
        if ($_ENV['APP_DEBUG'] === 'true' && $exception) {
            $exceptionDetails = [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'previous' => $exception->getPrevious() ? [
                    'message' => $exception->getPrevious()->getMessage(),
                    'file' => $exception->getPrevious()->getFile(),
                    'line' => $exception->getPrevious()->getLine(),
                ] : null,
            ];
        }
        
        // Render 500 page
        echo $this->render('errors/500', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'exception' => $exceptionDetails
        ]);
    }
    
    /**
     * Display fatal error page
     * 
     * @param RequestObject $request Current request information
     * @param array|null $error PHP error array from error_get_last()
     * @return void
     */
    public function fatalError(RequestObject $request, ?array $error = null): void
    {
        // Set HTTP status code
        http_response_code(500);
        
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Format error details if available
        $errorDetails = null;
        if ($_ENV['APP_DEBUG'] === 'true' && $error) {
            $errorDetails = [
                'message' => $error['message'] ?? 'Unknown error',
                'file' => $error['file'] ?? 'Unknown file',
                'line' => $error['line'] ?? 0,
                'type' => $this->getErrorType($error['type'] ?? 0),
            ];
        }
        
        // Render fatal error page
        echo $this->render('errors/fatal', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'error' => $errorDetails
        ]);
    }
    
    /**
     * Display forbidden page (403)
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function forbidden(RequestObject $request): void
    {
        // Set HTTP status code
        http_response_code(403);
        
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Render 403 page
        echo $this->render('errors/403', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode
        ]);
    }
    
    /**
     * Display maintenance mode page
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function maintenance(RequestObject $request): void
    {
        // Set HTTP status code
        http_response_code(503);
        
        // Set retry-after header (1 hour)
        header('Retry-After: 3600');
        
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Render maintenance page
        echo $this->render('errors/maintenance', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode
        ]);
    }
    
    /**
     * Get human-readable error type
     * 
     * @param int $type PHP error type constant
     * @return string Human-readable error type
     */
    private function getErrorType(int $type): string
    {
        $types = [
            E_ERROR => 'Fatal Error',
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
        
        return $types[$type] ?? 'Unknown Error';
    }
}