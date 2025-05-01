<?php

namespace App\Services;

use App\Models\LanguageModel;

/**
 * TranslationService - Handles text translations
 * 
 * This service provides translation functionality for static texts,
 * supporting multiple languages and caching for performance.
 * 
 * Enhanced with fallback translations to prevent placeholder text.
 */
class TranslationService
{
    private LanguageModel $languageModel;
    private CacheService $cacheService;
    private array $translations = [];
    private string $currentLanguage;
    
    // Default translations as fallback when database fails
    private array $fallbackTranslations = [
        'en' => [
            'site.title' => 'Polette Nathan - Portfolioss',
            'meta.description' => 'Nathan Polette\'s personal portfolio - Computer Engineering Student',
            'nav.projects' => 'Projects',
            'nav.contact' => 'Contact',
            'nav.admin' => 'Admin',
            'nav.logout' => 'Logout',
            'nav.aria_label' => 'Main navigation',
            'footer.about_title' => 'About me',
            'footer.contact_title' => 'Contacts & accounts',
            'footer.rights_reserved' => 'All rights reserved. (design & code by Asha Geyon)',
            'home.subtitle' => 'Computer Engineering Student',
            'home.contact_button' => 'Contact me',
            'home.projects_title' => 'My projects',
            'home.current_projects' => 'Current projects',
            'home.past_projects' => 'Past projects',
            'home.no_current_projects' => 'No current projects to display.',
            'home.no_past_projects' => 'No past projects to display.',
            'home.intro_label' => 'Introduction',
            'contact.title' => 'Contact',
            'contact.meta_description' => 'Contact Nathan Polette',
            'contact.heading' => 'Contact Me',
            'contact.info_heading' => 'Contact Information',
            'contact.form_heading' => 'Send me a message',
            'contact.email' => 'Email',
            'contact.your_email' => 'Your Email',
            'contact.subject' => 'Subject',
            'contact.message' => 'Message',
            'contact.submit' => 'Send',
            'contact.required_fields' => 'Required fields',
            'contact.email_placeholder' => 'Your email address',
            'contact.subject_placeholder' => 'What is this about?',
            'contact.message_placeholder' => 'Your message...',
            'contact.success' => 'Your message has been sent successfully.',
            'contact.error.subject_required' => 'Subject is required.',
            'contact.error.message_required' => 'Message is required.',
            'contact.error.invalid_email' => 'Please enter a valid email address.',
            'login.title' => 'Login',
            'login.email' => 'Email',
            'login.password' => 'Password',
            'login.remember_me' => 'Remember me',
            'login.submit' => 'Login',
            'login.forgot_password' => 'Forgot password?',
            'admin.dashboard' => 'Dashboard',
            'admin.profile' => 'Profile',
            'admin.projects' => 'Projects',
            'admin.project_title' => 'Title',
            'admin.project_type' => 'Type',
            'admin.project_status' => 'Status',
            'admin.date' => 'Date',
            'admin.actions' => 'Actions',
            'admin.edit' => 'Edit',
            'admin.delete' => 'Delete',
            'admin.confirm_delete' => 'Are you sure you want to delete this item?',
            'admin.personal_info' => 'Personal Information',
            'admin.edit_profile' => 'Edit Profile',
            'admin.add_project' => 'Add Project',
            'admin.current_projects' => 'Current Projects',
            'admin.past_projects' => 'Past Projects',
            'admin.total_projects' => 'Total Projects',
            'admin.manage' => 'Manage',
            'admin.view_site' => 'View Site',
            'admin.logout' => 'Logout',
            'admin.no_projects' => 'No projects found',
            'admin.get_started_by_creating' => 'Get started by creating a new project',
            'admin.since' => 'Since',
            'admin.status_current' => 'Current',
            'admin.status_past' => 'Past',
            'admin.status_canceled' => 'Canceled',
            'error.not_found' => 'Page Not Found',
            'error.forbidden' => 'Access Denied',
            'error.server_error' => 'Server Error',
            'error.404_message' => 'The page you are looking for does not exist or has been moved.',
            'error.go_back' => 'Go Back',
            'error.back_to_home' => 'Back to Home',
            'error.404_description' => '404 - Page not found',
            'error.403_description' => '403 - Access denied',
            'error.500_description' => '500 - Server error'
        ],
        'fr' => [
            'site.title' => 'Polette Nathan - Portfoliosss',
            'meta.description' => 'Portfolio personnel de Nathan Polette - Étudiant en ingénierie informatique',
            'nav.projects' => 'Projets',
            'nav.contact' => 'Contact',
            'nav.admin' => 'Admin',
            'nav.logout' => 'Déconnexion',
            'nav.aria_label' => 'Navigation principale',
            'footer.about_title' => 'Un petit résumé rapide',
            'footer.contact_title' => 'Contacts & comptes',
            'footer.rights_reserved' => 'Tous droits réservés. (design & code par Asha Geyon)',
            'home.subtitle' => 'Étudiant en ingénierie informatique',
            'home.contact_button' => 'Me contacter',
            'home.projects_title' => 'Mes projets',
            'home.current_projects' => 'Projets en cours',
            'home.past_projects' => 'Projets passés',
            'home.no_current_projects' => 'Aucun projet en cours à afficher.',
            'home.no_past_projects' => 'Aucun projet passé à afficher.',
            'home.intro_label' => 'Introduction',
            'contact.title' => 'Contact',
            'contact.meta_description' => 'Contacter Nathan Polette',
            'contact.heading' => 'Me Contacter',
            'contact.info_heading' => 'Informations de contact',
            'contact.form_heading' => 'Envoyez-moi un message',
            'contact.email' => 'Email',
            'contact.your_email' => 'Votre Email',
            'contact.subject' => 'Sujet',
            'contact.message' => 'Message',
            'contact.submit' => 'Envoyer',
            'contact.required_fields' => 'Champs obligatoires',
            'contact.email_placeholder' => 'Votre adresse email',
            'contact.subject_placeholder' => 'De quoi s\'agit-il?',
            'contact.message_placeholder' => 'Votre message...',
            'contact.success' => 'Votre message a été envoyé avec succès.',
            'contact.error.subject_required' => 'Le sujet est obligatoire.',
            'contact.error.message_required' => 'Le message est obligatoire.',
            'contact.error.invalid_email' => 'Veuillez entrer une adresse email valide.',
            'login.title' => 'Connexion',
            'login.email' => 'Email',
            'login.password' => 'Mot de passe',
            'login.remember_me' => 'Se souvenir de moi',
            'login.submit' => 'Se connecter',
            'login.forgot_password' => 'Mot de passe oublié?',
            'admin.dashboard' => 'Tableau de bord',
            'admin.profile' => 'Profil',
            'admin.projects' => 'Projets',
            'admin.project_title' => 'Titre',
            'admin.project_type' => 'Type',
            'admin.project_status' => 'Statut',
            'admin.date' => 'Date',
            'admin.actions' => 'Actions',
            'admin.edit' => 'Modifier',
            'admin.delete' => 'Supprimer',
            'admin.confirm_delete' => 'Êtes-vous sûr de vouloir supprimer cet élément?',
            'admin.personal_info' => 'Informations personnelles',
            'admin.edit_profile' => 'Modifier le profil',
            'admin.add_project' => 'Ajouter un projet',
            'admin.current_projects' => 'Projets en cours',
            'admin.past_projects' => 'Projets passés',
            'admin.total_projects' => 'Total des projets',
            'admin.manage' => 'Gérer',
            'admin.view_site' => 'Voir le site',
            'admin.logout' => 'Déconnexion',
            'admin.no_projects' => 'Aucun projet trouvé',
            'admin.get_started_by_creating' => 'Commencez par créer un nouveau projet',
            'admin.since' => 'Depuis',
            'admin.status_current' => 'En cours',
            'admin.status_past' => 'Terminé',
            'admin.status_canceled' => 'Annulé',
            'error.not_found' => 'Page non trouvée',
            'error.forbidden' => 'Accès refusé',
            'error.server_error' => 'Erreur serveur',
            'error.404_message' => 'La page que vous recherchez n\'existe pas ou a été déplacée.',
            'error.go_back' => 'Retour',
            'error.back_to_home' => 'Retour à l\'accueil',
            'error.404_description' => '404 - Page non trouvée',
            'error.403_description' => '403 - Accès refusé',
            'error.500_description' => '500 - Erreur serveur'
        ]
    ];
    
    /**
     * Create a new TranslationService instance
     * 
     * @param string $language Language code
     * @param LanguageModel|null $languageModel Language model
     * @param CacheService|null $cacheService Cache service
     */
    public function __construct(string $language, ?LanguageModel $languageModel = null, ?CacheService $cacheService = null)
    {
        $this->currentLanguage = $language;
        $this->languageModel = $languageModel ?? new LanguageModel();
        $this->cacheService = $cacheService ?? new CacheService();
        
        // Load translations for the current language
        $this->loadTranslations($language);
    }
    
    /**
     * Translate a key
     * 
     * @param string $key Translation key
     * @param array $params Parameters to replace in the translation
     * @return string Translated string or key if translation not found
     */
    public function translate(string $key, array $params = []): string
    {
        // Get translation
        $translation = $this->translations[$key] ?? $key;
        
        // Replace parameters
        foreach ($params as $name => $value) {
            $translation = str_replace(":$name", $value, $translation);
        }
        
        return $translation;
    }
    
    /**
     * Load translations for a language
     * 
     * @param string $language Language code
     * @return void
     */
    private function loadTranslations(string $language): void
    {
        // Try to get from cache first
        $cacheKey = "translations_$language";
        $cached = $this->cacheService->get($cacheKey);
        
        if ($cached !== null) {
            $this->translations = $cached;
            return;
        }
        
        try {
            // If not in cache, load from database
            $dbTranslations = $this->languageModel->getUiTexts($language);
            
            // If database translations are empty, use fallback
            if (empty($dbTranslations)) {
                $this->translations = $this->getFallbackTranslations($language);
                
                // Log that we're using fallback translations
                error_log("Warning: Using fallback translations for language: $language");
            } else {
                $this->translations = $dbTranslations;
            }
        } catch (\Exception $e) {
            // If database access fails, use fallback translations
            error_log("Error loading translations from database: " . $e->getMessage());
            $this->translations = $this->getFallbackTranslations($language);
        }
        
        // Cache for 1 hour
        $this->cacheService->set($cacheKey, $this->translations, 3600);
    }
    
    /**
     * Get fallback translations for a language
     * 
     * @param string $language Language code
     * @return array Fallback translations
     */
    private function getFallbackTranslations(string $language): array
    {
        // Return fallback translations for the specified language or English as ultimate fallback
        return $this->fallbackTranslations[$language] ?? $this->fallbackTranslations['en'] ?? [];
    }
    
    /**
     * Change the current language
     * 
     * @param string $language Language code
     * @return void
     */
    public function setLanguage(string $language): void
    {
        if ($this->currentLanguage !== $language) {
            $this->currentLanguage = $language;
            $this->loadTranslations($language);
        }
    }
    
    /**
     * Get the current language
     * 
     * @return string Current language code
     */
    public function getLanguage(): string
    {
        return $this->currentLanguage;
    }
    
    /**
     * Check if a translation exists
     * 
     * @param string $key Translation key
     * @return bool True if translation exists
     */
    public function has(string $key): bool
    {
        return isset($this->translations[$key]);
    }
    
    /**
     * Get all translations for the current language
     * 
     * @return array All translations
     */
    public function getAll(): array
    {
        return $this->translations;
    }
    
    /**
     * Clear translation cache
     * 
     * @param string|null $language Specific language to clear, null for all
     * @return bool True if successful
     */
    public function clearCache(?string $language = null): bool
    {
        if ($language) {
            return $this->cacheService->delete("translations_$language");
        }
        
        // Get all languages
        $languages = $this->languageModel->getActiveLanguages();
        
        foreach ($languages as $lang) {
            $this->cacheService->delete("translations_{$lang->code}");
        }
        
        return true;
    }
}