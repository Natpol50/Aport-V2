<?php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Middleware\LanguageMiddleware;
use App\Services\CacheService;
use App\Services\TranslationService;

/**
 * LanguageController - Handles language switching
 * 
 * This controller manages language switching functionality with improved
 * URL handling to prevent internal errors.
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
        // Get language code for translations
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Validate language
        if (!in_array($lang, $this->supportedLanguages)) {
            $lang = $this->languageMiddleware->getDefaultLanguage();
        }
        
        // Store language preference in session
        $_SESSION['language'] = $lang;
        
        // Get referer (previous page)
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        
        // Determine redirect URL based on the target language
        // Special handling for home page and common routes
        if ($lang === 'en') {
            // When switching to English
            $currentPath = parse_url($referer, PHP_URL_PATH);
            
            if ($currentPath === '/' || $currentPath === '/fr') {
                // Home page in French to home page in English
                $redirectUrl = '/en';
            } else if ($currentPath === '/contact') {
                // Contact page in French to contact page in English
                $redirectUrl = '/contact-en';
            } else {
                // Default handling for other pages
                $redirectUrl = '/en';
            }
        } else {
            // When switching to French
            $currentPath = parse_url($referer, PHP_URL_PATH);
            
            if ($currentPath === '/en' || $currentPath === '/') {
                // Home page in English to home page in French
                $redirectUrl = '/';
            } else if ($currentPath === '/contact-en') {
                // Contact page in English to contact page in French
                $redirectUrl = '/contact';
            } else {
                // Default handling for other pages
                $redirectUrl = '/';
            }
        }
        
        // Add query parameters if they exist in the referer
        $parsedUrl = parse_url($referer);
        if (isset($parsedUrl['query'])) {
            $redirectUrl .= '?' . $parsedUrl['query'];
        }
        
        // Redirect to the appropriate page in the new language
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