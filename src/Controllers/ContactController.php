<?php
// src/Controllers/ContactController.php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Models\PersonalInfoModel;
use App\Services\TranslationService;
use App\Services\EmailService;

/**
 * ContactController - Handles contact page display and form processing
 * 
 * This controller is responsible for rendering the standalone contact page
 * and processing contact form submissions with email notifications.
 */
class ContactController extends BaseController
{
    private PersonalInfoModel $personalInfoModel;
    private EmailService $emailService;
    
    /**
     * Create a new ContactController instance
     */
    public function __construct()
    {
        $this->personalInfoModel = new PersonalInfoModel();
        $this->emailService = new EmailService();
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
        
        // Define recipients for the contact form
        $recipients = [
            'nathan.polette@gmail.com',
            'nathan.polette@viacesi.fr',
            'nathan.polette@koesio.com'
        ];
        
        // Send the contact form email to all recipients
        $emailResults = [];
        $allSuccessful = true;
        
        foreach ($recipients as $recipient) {
            $result = $this->emailService->sendContactFormHtml($subject, $message, $email, $recipient);
            $emailResults[$recipient] = $result;
            if (!$result) {
                $allSuccessful = false;
            }
        }
        
        // Set enhanced notification message based on email sending results
        if ($allSuccessful) {
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => $translationService->translate('contact.success'),
                'icon' => 'check-circle',
                'title' => $translationService->translate('contact.success_title'),
                'timestamp' => time()
            ];
        } else {
            // Create a more detailed error message for logging purposes
            $failedRecipients = array_keys(array_filter($emailResults, function($result) {
                return $result === false;
            }));
            
            $errorDetails = count($failedRecipients) > 0 
                ? sprintf(' (%s)', implode(', ', $failedRecipients)) 
                : '';
            
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => $translationService->translate('contact.error') . $errorDetails,
                'icon' => 'exclamation-triangle',
                'title' => $translationService->translate('contact.error_title'),
                'timestamp' => time()
            ];
        }
        
        // Redirect back to the contact page in the correct language
        $contactUrl = ($langCode === 'en') ? '/contact-en-standalone' : '/contact-standalone';
        $this->redirect($contactUrl);
    }
}