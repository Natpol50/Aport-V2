<?php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Models\ProjectModel;
use App\Models\PersonalInfoModel;
use App\Services\TranslationService;

/**
 * HomeController - Handles main site pages
 * 
 * This controller is responsible for rendering the main public-facing
 * pages of the website, including the home page and contact page.
 * 
 * Improved with better language handling for English routes.
 */
class HomeController extends BaseController
{
    private ProjectModel $projectModel;
    private PersonalInfoModel $personalInfoModel;
    
    /**
     * Create a new HomeController instance
     */
    public function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->personalInfoModel = new PersonalInfoModel();
    }
    
    /**
     * Display the home page (French version is default)
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function index(RequestObject $request): void
    {
        // Set language to French
        $request->setLanguageCode('fr');
        $_SESSION['language'] = 'fr';
        
        // Render the home page with French content
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
        
        // Render the home page with English content
        $this->renderHomePage($request, 'en');
    }
    
    /**
     * Common method to render the home page with specified language
     * 
     * @param RequestObject $request Current request information
     * @param string $langCode Language code
     * @return void
     */
    private function renderHomePage(RequestObject $request, string $langCode): void
    {
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get current projects
        $currentProjects = $this->projectModel->getAllProjects($langCode, 'current');
        
        // Get past projects
        $pastProjects = $this->projectModel->getAllProjects($langCode, 'past');
        
        // Get personal info
        $personalInfo = $this->personalInfoModel->getPersonalInfo($langCode);
        
        // Render the home page
        echo $this->render('home/index', [
            'request' => $request,
            'currentProjects' => $currentProjects,
            'pastProjects' => $pastProjects,
            'personalInfo' => $personalInfo,
            'translations' => $translationService,
            'language' => $langCode,
            'is_home_page' => true
        ]);
    }
    
    /**
     * Display the French contact page
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
     * Display the English contact page
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
     * Common method to render the contact page with specified language
     * 
     * @param RequestObject $request Current request information
     * @param string $langCode Language code
     * @return void
     */
    private function renderContactPage(RequestObject $request, string $langCode): void
    {
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get personal info for contact details
        $personalInfo = $this->personalInfoModel->getPersonalInfo($langCode);
        
        // Render the contact page
        echo $this->render('home/contact', [
            'request' => $request,
            'personalInfo' => $personalInfo,
            'translations' => $translationService,
            'language' => $langCode
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
        
        // Simple validation
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
        
        // If there are errors, redisplay the form with error messages
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
                ]
            ]);
            return;
        }
        
        // In a real application, this would send an email
        // For this example, we'll just show a success message
        
        // Set success message in session
        $_SESSION['success'] = [
            $translationService->translate('contact.success')
        ];
        
        // Redirect back to the contact page based on language
        $contactUrl = ($langCode === 'en') ? '/contact-en' : '/contact';
        header("Location: $contactUrl");
        exit;
    }
}