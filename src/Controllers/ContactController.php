<?php
// src/Controllers/ContactController.php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Models\PersonalInfoModel;
use App\Services\TranslationService;

/**
 * ContactController - Handles contact page display and form processing
 * 
 * This controller is responsible for rendering the standalone contact page
 * and processing contact form submissions with mailto links.
 */
class ContactController extends BaseController
{
    private PersonalInfoModel $personalInfoModel;
    
    /**
     * Create a new ContactController instance
     */
    public function __construct()
    {
        $this->personalInfoModel = new PersonalInfoModel();
    }
    
    /**
     * Display the standalone contact page in French
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function contactPage(RequestObject $request): void
    {
        // Set language to French
        $request->setLanguageCode('fr');
        $_SESSION['language'] = 'fr';
        
        // Render the standalone contact page with French content
        $this->renderStandaloneContactPage($request, 'fr');
    }
    
    /**
     * Display the standalone contact page in English
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function contactPageEn(RequestObject $request): void
    {
        // Set language to English
        $request->setLanguageCode('en');
        $_SESSION['language'] = 'en';
        
        // Render the standalone contact page with English content
        $this->renderStandaloneContactPage($request, 'en');
    }
    
    /**
     * Common method to render the standalone contact page with the specified language
     * 
     * @param RequestObject $request Current request information
     * @param string $langCode Language code (en/fr)
     * @return void
     */
    private function renderStandaloneContactPage(RequestObject $request, string $langCode): void
    {
        // Initialize translation service for the specified language
        $translationService = new TranslationService($langCode);
        
        // Get personal info for contact details
        $personalInfo = $this->personalInfoModel->getPersonalInfo($langCode);
        
        // Render the standalone contact page with all necessary data
        echo $this->render('contact/standalone', [
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
            echo $this->render('contact/standalone', [
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
        
        // Define recipient for the contact form
        $recipient = 'nathan.polette@gmail.com';
        
        // Prepare the subject and body for mailto link
        $encodedSubject = urlencode($subject);
        $encodedMessage = urlencode($message);
        $encodedEmail = urlencode($email);
        
        // Create a mailto URL with the form data
        $mailtoUrl = "mailto:{$recipient}?subject={$encodedSubject}&body={$encodedMessage}";
        
        // Add sender's email in the body if provided
        if (!empty($email)) {
            $mailtoUrl .= urlencode("\n\nFrom: {$email}");
        }
        
        // Set success notification
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => $translationService->translate('contact.success'),
            'icon' => 'check-circle',
            'title' => $translationService->translate('contact.success_title'),
            'timestamp' => time()
        ];
        
        // Set cookie to redirect back after email client opens
        setcookie('contact_redirect', '1', time() + 60, '/');
        
        // Redirect to the mailto URL to open client's email program
        header("Location: {$mailtoUrl}");
        exit;
    }
}