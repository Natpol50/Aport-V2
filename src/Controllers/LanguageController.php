<?php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Services\TranslationService;

/**
 * LanguageController - Manages language switching functionality
 * 
 * This controller handles language switching between French and English,
 * preserving the user's current location within the site when switching.
 * It uses session storage to maintain language preference across requests.
 */
class LanguageController extends BaseController
{
    /**
     * List of supported language codes
     * @var array
     */
    private array $supportedLanguages = ['en', 'fr'];
    
    /**
     * Language-specific URL patterns for mapping between languages
     * Uses key-value pairs to translate URL patterns between languages
     * @var array
     */
    private array $urlMappings = [
        'en' => [
            '/' => '/en',
            '/contact' => '/contact-en',
            '/projects' => '/projects-en',
            '/project/' => '/project-en/'
        ],
        'fr' => [
            '/en' => '/',
            '/contact-en' => '/contact',
            '/projects-en' => '/projects',
            '/project-en/' => '/project/'
        ]
    ];
    
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
            // Default to French if invalid language
            $lang = 'fr';
        }
        
        // Store language preference in session
        $_SESSION['language'] = $lang;
        
        // Determine redirect URL based on referer
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $redirectUrl = $this->getTranslatedUrl($referer, $lang);
        
        // Redirect to the equivalent page in the new language
        $this->redirect($redirectUrl);
    }
    
    /**
     * Get the translated URL for the target language
     * 
     * @param string $currentUrl Current URL
     * @param string $targetLang Target language code
     * @return string Translated URL in the target language
     */
    private function getTranslatedUrl(string $currentUrl, string $targetLang): string
    {
        // Parse the current URL to get the path
        $parsedUrl = parse_url($currentUrl);
        $currentPath = $parsedUrl['path'] ?? '/';
        
        // Determine source language based on the path (assuming default is French)
        $currentLang = 'fr';
        if (preg_match('/\/en($|\/)/', $currentPath)) {
            $currentLang = 'en';
        }
        
        // If already on target language, return the current path
        if ($currentLang === $targetLang) {
            return $currentPath;
        }
        
        // Translate the URL using the mappings
        $translatedPath = $currentPath;
        
        // Check if we have a direct mapping for this path
        if (isset($this->urlMappings[$targetLang][$currentPath])) {
            $translatedPath = $this->urlMappings[$targetLang][$currentPath];
        } else {
            // Handle more complex URLs with parameters
            foreach ($this->urlMappings[$targetLang] as $sourcePath => $targetPath) {
                // Check if current path starts with the source path
                if (strpos($currentPath, $sourcePath) === 0) {
                    // Replace only the prefix part
                    $translatedPath = $targetPath . substr($currentPath, strlen($sourcePath));
                    break;
                }
            }
        }
        
        // Add query parameters if they exist in the original URL
        if (isset($parsedUrl['query'])) {
            $translatedPath .= '?' . $parsedUrl['query'];
        }
        
        return $translatedPath;
    }
    
    /**
     * Get information about available languages for API/AJAX use
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function getLanguages(RequestObject $request): void
    {
        // Prepare response data
        $response = [
            'languages' => $this->supportedLanguages,
            'current' => $request->getLanguageCode(),
            'default' => 'fr'  // French is the default language
        ];
        
        // Return as JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    /**
     * Set the default language if one is not already set
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function setDefaultLanguage(RequestObject $request): void
    {
        // Check if a language is already set
        if (!isset($_SESSION['language'])) {
            // Check browser language preferences
            $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
            $detectedLang = $this->detectLanguageFromHeader($acceptLang);
            
            // Set the detected language or default to French
            $_SESSION['language'] = $detectedLang ?: 'fr';
        }
        
        // Update the request object with the current language
        $request->setLanguageCode($_SESSION['language']);
        
        // Continue to next middleware or controller
        return;
    }
    
    /**
     * Detect preferred language from Accept-Language header
     * 
     * @param string $acceptHeader Accept-Language header value
     * @return string|null Detected language code or null if not determined
     */
    private function detectLanguageFromHeader(string $acceptHeader): ?string
    {
        // Parse language preferences from header
        $langs = [];
        
        // Extract language codes and quality values
        if (preg_match_all('~([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?~i', $acceptHeader, $matches)) {
            // Create a list like ['en' => 0.8, 'fr' => 1.0]
            for ($i = 0; $i < count($matches[1]); $i++) {
                $lang = strtolower(substr($matches[1][$i], 0, 2));
                $quality = $matches[4][$i] ?? 1.0;
                
                $langs[$lang] = (float) $quality;
            }
            
            // Sort by quality (highest first)
            arsort($langs);
            
            // Find the first supported language
            foreach (array_keys($langs) as $lang) {
                if (in_array($lang, $this->supportedLanguages)) {
                    return $lang;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get a list of URLs for each supported language
     * Used for generating language switcher links
     * 
     * @param string $currentUrl Current URL
     * @return array Associative array of language code => URL
     */
    public function getLanguageUrls(string $currentUrl): array
    {
        $urls = [];
        
        // Parse the current URL
        $parsedUrl = parse_url($currentUrl);
        $currentPath = $parsedUrl['path'] ?? '/';
        
        // Determine current language
        $currentLang = 'fr'; // Default
        if (preg_match('/\/en($|\/)/', $currentPath)) {
            $currentLang = 'en';
        }
        
        // Generate URLs for each supported language
        foreach ($this->supportedLanguages as $lang) {
            if ($lang === $currentLang) {
                // Current language uses current URL
                $urls[$lang] = $currentPath;
            } else {
                // Other languages need URL translation
                $urls[$lang] = $this->getTranslatedUrl($currentUrl, $lang);
            }
        }
        
        return $urls;
    }
}