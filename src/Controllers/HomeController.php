<?php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Models\ProjectModel;
use App\Models\PersonalInfoModel;
use App\Models\CompetencyModel; // Add this line
use App\Services\TranslationService;

/**
 * HomeController - Handles main site pages with enhanced language support
 */
class HomeController extends BaseController
{
    private ProjectModel $projectModel;
    private PersonalInfoModel $personalInfoModel;
    private CompetencyModel $competencyModel; // Add this line
    
    /**
     * Create a new HomeController instance
     * Initialize required models
     */
    public function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->personalInfoModel = new PersonalInfoModel();
        $this->competencyModel = new CompetencyModel(); // Add this line
    }

    
    /**
     * Display the home page (default language - French)
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function index(RequestObject $request): void
    {
        // Set language to French for default home page
        $request->setLanguageCode('fr');
        $_SESSION['language'] = 'fr';
        
        // Render the French version of the home page
        $this->renderHomePage($request, 'fr');
    }
    
    /**
     * Display the English home page
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function indexEn(RequestObject $request): void
    {
        // Set language to English
        $request->setLanguageCode('en');
        $_SESSION['language'] = 'en';
        
        // Render the English version of the home page
        $this->renderHomePage($request, 'en');
    }
    
    /**
     * Common method to render the home page with the specified language
     * 
     * @param RequestObject $request Current request information
     * @param string $langCode Language code (en/fr)
     * @return void
     */
    private function renderHomePage(RequestObject $request, string $langCode): void
    {
        // Initialize translation service for the specified language
        $translationService = new TranslationService($langCode);
        
        // Get current projects for the specified language
        $currentProjects = $this->projectModel->getAllProjects($langCode, 'current');
        
        // Get past projects for the specified language
        $pastProjects = $this->projectModel->getAllProjects($langCode, 'past');
        
        // Get personal info for the specified language
        $personalInfo = $this->personalInfoModel->getPersonalInfo($langCode);
        
        // Get competency categories with their competencies
        $competencyCategories = $this->competencyModel->getAllCategoriesWithCompetencies($langCode);
        
        // Render the home page with all necessary data
        echo $this->render('home/index', [
            'request' => $request,
            'currentProjects' => $currentProjects,
            'pastProjects' => $pastProjects,
            'personalInfo' => $personalInfo,
            'competencyCategories' => $competencyCategories, // Add this line
            'translations' => $translationService,
            'language' => $langCode,
            'current_year' => date('Y')
        ]);
    }
    
    /**
     * Display the contact page in French
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function contact(RequestObject $request): void
    {
        // Set language to French
        $request->setLanguageCode('fr');
        $_SESSION['language'] = 'fr';
        
        // Render the contact page with French content
        $this->renderContactPage($request, 'fr');
    }
    
    /**
     * Display the contact page in English
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function contactEn(RequestObject $request): void
    {
        // Set language to English
        $request->setLanguageCode('en');
        $_SESSION['language'] = 'en';
        
        // Render the contact page with English content
        $this->renderContactPage($request, 'en');
    }
    
    /**
     * Common method to render the contact page with the specified language
     * 
     * @param RequestObject $request Current request information
     * @param string $langCode Language code (en/fr)
     * @return void
     */
    private function renderContactPage(RequestObject $request, string $langCode): void
    {
        // Initialize translation service for the specified language
        $translationService = new TranslationService($langCode);
        
        // Get personal info for contact details
        $personalInfo = $this->personalInfoModel->getPersonalInfo($langCode);
        
        // Render the contact page with all necessary data
        echo $this->render('home/contact', [
            'request' => $request,
            'personalInfo' => $personalInfo,
            'translations' => $translationService,
            'language' => $langCode,
            'current_year' => date('Y')
        ]);
    }
    
    /**
     * Process contact form submission
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function submitContact(RequestObject $request): void
    {
        // Get form data
        $subject = $request->getPost('subject', '');
        $message = $request->getPost('message', '');
        $email = $request->getPost('email', '');
        
        // Get language code
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get personal info for contact details
        $personalInfo = $this->personalInfoModel->getPersonalInfo($langCode);
        
        // Form validation
        $errors = [];
        
        if (empty($subject)) {
            $errors[] = $translationService->translate('contact.error.subject_required');
        }
        
        if (empty($message)) {
            $errors[] = $translationService->translate('contact.error.message_required');
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = $translationService->translate('contact.error.invalid_email');
        }
        
        // If there are validation errors, redisplay the form
        if (!empty($errors)) {
            echo $this->render('home/contact', [
                'request' => $request,
                'personalInfo' => $personalInfo,
                'translations' => $translationService,
                'language' => $langCode,
                'error' => $errors,
                'formData' => [
                    'subject' => $subject,
                    'message' => $message,
                    'email' => $email
                ],
                'current_year' => date('Y')
            ]);
            return;
        }
        
        // In a real application, send an email here
        // For this example, just show a success message
        
        // Set success message in session
        $_SESSION['success'] = [
            $translationService->translate('contact.success')
        ];
        
        // Redirect back to the contact page in the correct language
        $contactUrl = ($langCode === 'en') ? '/contact-en' : '/contact';
        $this->redirect($contactUrl);
    }
}