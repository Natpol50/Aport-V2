<?php

namespace App\Middleware;

use App\Core\RequestObject;
use App\Models\LanguageModel;
use App\Services\CacheService;
use App\Services\Database;

/**
 * LanguageMiddleware - Language detection and switching middleware
 * 
 * This middleware handles language detection and switching based on
 * URL path, query parameters, session state, and browser preferences.
 * 
 * The language detection follows a priority order:
 * 1. Explicit language query parameter (?lang=xx)
 * 2. Language stored in session
 * 3. Language code in URL path (/en/, /fr/)
 * 4. Accept-Language HTTP header
 * 5. Default language from configuration
 */
class LanguageMiddleware
{
    private RequestObject $request;
    private array $availableLanguages;
    private string $defaultLanguage;
    private CacheService $cacheService;
    
    /**
     * Create a new LanguageMiddleware instance
     * 
     * @param RequestObject $request Current request object
     * @param CacheService|null $cacheService Optional cache service
     */
    public function __construct(RequestObject $request, ?CacheService $cacheService = null)
    {
        $this->request = $request;
        $this->defaultLanguage = $_ENV['DEFAULT_LANGUAGE'] ?? 'en';
        $this->cacheService = $cacheService ?? new CacheService();
        
        // Load available languages (with caching)
        $this->loadAvailableLanguages();
    }
    
    /**
     * Load available languages from database with caching
     * 
     * @return void
     */
    private function loadAvailableLanguages(): void
    {
        // Try to get languages from cache
        $cacheKey = 'available_languages';
        $languages = $this->cacheService->get($cacheKey);
        
        if ($languages === null) {
            // Cache miss, load from database
            $database = new Database();
            $languageModel = new LanguageModel($database);
            $languageObjects = $languageModel->getActiveLanguages();
            
            // Format into a simpler array
            $languages = array_map(function($lang) {
                return $lang->code;
            }, $languageObjects);
            
            // Cache for 1 hour
            $this->cacheService->set($cacheKey, $languages, 3600);
        }
        
        $this->availableLanguages = $languages;
        
        // Ensure default language is in available languages
        if (!in_array($this->defaultLanguage, $this->availableLanguages)) {
            // If default language is not available, use the first available language
            $this->defaultLanguage = $this->availableLanguages[0] ?? 'en';
        }
    }
    
    /**
     * Handle language detection and selection
     * 
     * @return RequestObject Updated request object with language
     */
    public function handle(): RequestObject
    {
        // Priority 1: Check for language query parameter
        $langParam = $_GET['lang'] ?? null;
        if ($langParam && $this->isValidLanguage($langParam)) {
            // Set the language in session
            $_SESSION['language'] = $langParam;
            $this->request->setLanguageCode($langParam);
            return $this->request;
        }
        
        // Priority 2: Check language in session
        if (isset($_SESSION['language']) && $this->isValidLanguage($_SESSION['language'])) {
            $this->request->setLanguageCode($_SESSION['language']);
            return $this->request;
        }
        
        // Priority 3: Check language in URL path
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $langFromPath = $this->extractLanguageFromPath($path);
        
        if ($langFromPath) {
            $_SESSION['language'] = $langFromPath;
            $this->request->setLanguageCode($langFromPath);
            return $this->request;
        }
        
        // Priority 4: Check Accept-Language header
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $preferredLanguages = $this->parseAcceptLanguage($acceptLanguage);
        
        foreach ($preferredLanguages as $lang => $quality) {
            $langShort = substr($lang, 0, 2); // Extract first two characters (e.g., "en" from "en-US")
            if ($this->isValidLanguage($langShort)) {
                $_SESSION['language'] = $langShort;
                $this->request->setLanguageCode($langShort);
                return $this->request;
            }
        }
        
        // Priority 5: Use default language
        $_SESSION['language'] = $this->defaultLanguage;
        $this->request->setLanguageCode($this->defaultLanguage);
        return $this->request;
    }
    
    /**
     * Extract language code from URL path
     * 
     * @param string $path URL path
     * @return string|null Language code or null if not found
     */
    private function extractLanguageFromPath(string $path): ?string
    {
        // Check for exact language code match at the start of the path
        if (preg_match('#^/([a-z]{2})(?:/|$)#', $path, $matches)) {
            $langCode = $matches[1];
            if ($this->isValidLanguage($langCode)) {
                return $langCode;
            }
        }
        
        // Check for English-specific paths
        if (preg_match('#(en|english)#i', $path)) {
            return 'en';
        }
        
        // Check for French-specific paths
        if (preg_match('#(fr|french|francais|français)#i', $path)) {
            return 'fr';
        }
        
        return null;
    }
    
    /**
     * Parse the Accept-Language header value
     * 
     * @param string $header Accept-Language header value
     * @return array Associative array of language => quality
     */
    private function parseAcceptLanguage(string $header): array
    {
        $result = [];
        
        // Split the header by comma
        $parts = explode(',', $header);
        
        foreach ($parts as $part) {
            // Split by semicolon to separate language and quality
            $subParts = explode(';', trim($part));
            
            $lang = $subParts[0];
            
            // Default quality is 1.0
            $quality = 1.0;
            
            // Parse quality if provided
            if (isset($subParts[1])) {
                $qValue = str_replace('q=', '', $subParts[1]);
                $quality = (float) $qValue;
            }
            
            $result[$lang] = $quality;
        }
        
        // Sort by quality (highest first)
        arsort($result);
        
        return $result;
    }
    
    /**
     * Check if a language code is valid and available
     * 
     * @param string $langCode Language code to check
     * @return bool True if valid and available
     */
    private function isValidLanguage(string $langCode): bool
    {
        return in_array($langCode, $this->availableLanguages);
    }
    
    /**
     * Get URL for a different language
     * 
     * @param string $path Current URL path
     * @param string $targetLang Target language code
     * @return string URL with target language
     */
    public function getLanguageUrl(string $path, string $targetLang): string
    {
        // Extract current language from path
        $currentLang = $this->extractLanguageFromPath($path);
        
        if ($currentLang) {
            // Replace current language with target language
            return preg_replace('#^/' . $currentLang . '(/?|/.*)$#', '/' . $targetLang . '$1', $path);
        }
        
        // If no language in path, add target language at the beginning
        return '/' . $targetLang . $path;
    }
    
    /**
     * Get all available languages
     * 
     * @return array Array of available language codes
     */
    public function getAvailableLanguages(): array
    {
        return $this->availableLanguages;
    }
    
    /**
     * Get the default language
     * 
     * @return string Default language code
     */
    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
    }
}