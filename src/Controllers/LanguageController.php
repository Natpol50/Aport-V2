<?php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Middleware\LanguageMiddleware;
use App\Services\CacheService;

/**
 * LanguageController - Handles language switching
 * 
 * This controller is responsible for switching between languages
 * and redirecting back to the previous page.
 */
class LanguageController extends BaseController
{
    private array $supportedLanguages = ['en', 'fr'];
    private LanguageMiddleware $languageMiddleware;
    
    /**
     * Create a new LanguageController instance
     */
    public function __construct()
    {
        $cacheService = new CacheService();
        $this->languageMiddleware = new LanguageMiddleware(new RequestObject(), $cacheService);
    }
    
    /**
     * Switch the current language
     * 
     * @param RequestObject $request Current request information
     * @param string $lang Language code to switch to
     * @return void
     */
    public function switchLanguage(RequestObject $request, string $lang): void
    {
        // Validate language
        if (!in_array($lang, $this->supportedLanguages)) {
            $lang = $this->languageMiddleware->getDefaultLanguage();
        }
        
        // Store language preference in session
        $_SESSION['language'] = $lang;
        
        // Get referer (previous page)
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        
        // Extract the URL path
        $parsedUrl = parse_url($referer);
        $path = $parsedUrl['path'] ?? '/';
        
        // Get the corresponding URL for the selected language
        $redirectUrl = $this->languageMiddleware->getLanguageUrl($path, $lang);
        
        // Add query parameters if they exist
        if (isset($parsedUrl['query'])) {
            $redirectUrl .= '?' . $parsedUrl['query'];
        }
        
        // Redirect to the same page in the new language
        header("Location: $redirectUrl");
        exit;
    }
    
    /**
     * Get all available languages
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function getLanguages(RequestObject $request): void
    {
        $langs = $this->languageMiddleware->getAvailableLanguages();
        
        // Return as JSON
        header('Content-Type: application/json');
        echo json_encode([
            'languages' => $langs,
            'current' => $request->getLanguageCode(),
            'default' => $this->languageMiddleware->getDefaultLanguage()
        ]);
        exit;
    }
}