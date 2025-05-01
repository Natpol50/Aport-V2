<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\Database;
use App\Services\TokenService;
use App\Services\CacheService;
use App\Models\UserModel;
use App\Core\RequestObject;
use App\Exceptions\AuthenticationException;
use App\Services\TranslationService;

/**
 * AuthController - Handles authentication actions
 * 
 * This class has been simplified to remove registration functionality
 * and focus only on login, logout, and password reset for the admin user.
 */
class AuthController extends BaseController
{
    private UserModel $userModel;
    private TokenService $tokenService;
    private CacheService $cacheService;
    
    /**
     * Create a new AuthController instance
     */
    public function __construct()
    {
        // Initialize dependencies
        $database = new Database();
        
        $this->userModel = new UserModel($database);
        
        // Initialize cache service
        $this->cacheService = new CacheService();
        
        // Create config for token service
        $configManager = \App\Config\ConfigManager::getInstance();
        $tokenConfig = $configManager->getConfigFor($this);
        
        // Create token service with config and cache service
        $this->tokenService = new TokenService($tokenConfig, $this->cacheService);
    }
    
    /**
     * Display login form
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function loginForm(RequestObject $request): void
    {
        // If user is already authenticated, redirect to admin dashboard
        if ($request->isAuthenticated()) {
            header('Location: /admin');
            exit;
        }
        
        // Get language code from request
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Render login form with empty arrays for messages
        echo $this->render('auth/login', [
            'error' => [],
            'success' => [],
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'formData' => [] // Empty form data
        ]);
    }
    
    /**
     * Process login attempt
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function login(RequestObject $request): void
    {
        // If user is already authenticated, redirect to admin dashboard
        if ($request->isAuthenticated()) {
            header('Location: /admin');
            exit;
        }
        
        // Get language code from request
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get login credentials
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        
        // Save form data to repopulate the form if there's an error
        $formData = [
            'email' => $email,
            'remember_me' => $rememberMe
        ];
        
        $errorMessages = [];
        $successMessages = [];
        
        try {
            // Validate credentials
            $user = $this->userModel->verifyCredentials($email, $password);
            
            if (!$user) {
                // Add error message
                $errorMessages[] = $translationService->translate('login.error.invalid_credentials');
                
                // Render the login form with error and preserved form data
                echo $this->render('auth/login', [
                    'error' => $errorMessages,
                    'success' => $successMessages,
                    'request' => $request,
                    'translations' => $translationService,
                    'language' => $langCode,
                    'formData' => $formData
                ]);
                exit;
            }
            
            // Create JWT token
            $token = $this->tokenService->createJWT($user->userId);
            
            // Create refresh token if "remember me" is checked
            if ($rememberMe) {
                $this->tokenService->createRefreshToken($user->userId);
            }
            
            // Redirect to admin dashboard
            header('Location: /admin');
            exit;
        } catch (AuthenticationException $e) {
            // Log the error
            error_log('Authentication error: ' . $e->getMessage());
            
            // Add error to array
            $errorMessages[] = $translationService->translate('login.error.auth_error');
            
            // Render the login form with error and preserved form data
            echo $this->render('auth/login', [
                'error' => $errorMessages,
                'success' => $successMessages,
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode,
                'formData' => $formData
            ]);
            exit;
        }
    }
    
    /**
     * Process logout
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function logout(RequestObject $request): void
    {
        // Get language code from request
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Clear JWT token cookie
        $this->tokenService->logout();
        
        // Set up success message for login form
        $successMessages = [$translationService->translate('login.logout_success')];
        
        // Render login form with success message
        echo $this->render('auth/login', [
            'error' => [],
            'success' => $successMessages,
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'formData' => []
        ]);
        exit;
    }

    /**
     * Display forgot password form
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function forgotPasswordForm(RequestObject $request): void
    {
        // If user is already authenticated, redirect to admin dashboard
        if ($request->isAuthenticated()) {
            header('Location: /admin');
            exit;
        }
        
        // Get language code from request
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Render forgot password form with empty arrays
        echo $this->render('auth/forgot-password', [
            'error' => [],
            'success' => [],
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'formData' => []
        ]);
    }
    
    /**
     * Process forgot password attempt
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function forgotPassword(RequestObject $request): void
    {
        // If user is already authenticated, redirect to admin dashboard
        if ($request->isAuthenticated()) {
            header('Location: /admin');
            exit;
        }
        
        // Get language code from request
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        $email = $_POST['email'] ?? '';
        
        // Save form data
        $formData = [
            'email' => $email
        ];
        
        // Initialize message arrays
        $successMessages = [];
        $errorMessages = [];
        
        // Validate email exists
        $user = $this->userModel->getUserByEmail($email);
        
        // For security, don't indicate whether email exists or not
        $successMessages[] = $translationService->translate('forgot_password.email_sent');

        if (!$user) {
            // Still show success message even if user doesn't exist (security)
            echo $this->render('auth/reset-code', [
                'success' => $successMessages,
                'error' => $errorMessages,
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode,
                'email' => $email
            ]);
        } else {
            try {
                // In a real implementation, we would generate a code and send it via email
                // For demo purposes, we'll use a hardcoded code: 123456
                
                // Redirect to the code verification page
                echo $this->render('auth/reset-code', [
                    'error' => $errorMessages,
                    'success' => $successMessages,
                    'email' => $email,
                    'request' => $request,
                    'translations' => $translationService,
                    'language' => $langCode
                ]);
            } catch (\Exception $e) {
                // Log the error
                error_log('Forgot password error: ' . $e->getMessage());
                
                // Add error message
                $errorMessages[] = $translationService->translate('forgot_password.error');
                
                // Render forgot password form with error message
                echo $this->render('auth/forgot-password', [
                    'success' => $successMessages,
                    'error' => $errorMessages,
                    'request' => $request,
                    'translations' => $translationService,
                    'language' => $langCode,
                    'formData' => $formData
                ]);
            }
        }
    }
    
    /**
     * Process code verification for password reset
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function verifyResetCode(RequestObject $request): void
    {
        // If user is already authenticated, redirect to admin dashboard
        if ($request->isAuthenticated()) {
            header('Location: /admin');
            exit;
        }
        
        // Get language code from request
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        $email = $_POST['email'] ?? '';
        $resetCode = $_POST['resetCode'] ?? '';
        
        // Initialize message arrays
        $errorMessages = [];
        $successMessages = [];
        
        // For demo purposes, check if code is 123456
        if ($resetCode !== '123456') {
            $errorMessages[] = $translationService->translate('reset_code.invalid');
            
            // Send back to code verification page
            echo $this->render('auth/reset-code', [
                'error' => $errorMessages,
                'email' => $email,
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode
            ]);
            exit;
        }
        
        // Generate a token (in a real implementation, this would be more secure)
        $token = md5($email . time());
        
        // Render reset password form
        echo $this->render('auth/reset-password', [
            'token' => $token,
            'email' => $email,
            'error' => $errorMessages,
            'success' => $successMessages,
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode
        ]);
    }
    
    /**
     * Display reset password form
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function resetPasswordForm(RequestObject $request): void
    {
        // If user is already authenticated, redirect to admin dashboard
        if ($request->isAuthenticated()) {
            header('Location: /admin');
            exit;
        }
        
        // Get language code from request
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        $token = $_GET['token'] ?? '';
        $email = $_GET['email'] ?? '';
        
        // Initialize message arrays
        $errorMessages = [];
        $successMessages = [];
        
        // Validate token (would require implementation)
        if (empty($token) || empty($email)) {
            $errorMessages[] = $translationService->translate('reset_password.invalid_link');
            
            // Render forgot password form with error
            echo $this->render('auth/forgot-password', [
                'error' => $errorMessages,
                'success' => $successMessages,
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode,
                'formData' => []
            ]);
            exit;
        }
        
        // Render reset password form
        echo $this->render('auth/reset-password', [
            'token' => $token,
            'email' => $email,
            'error' => $errorMessages,
            'success' => $successMessages,
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode
        ]);
    }
    
    /**
     * Process reset password attempt
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function resetPassword(RequestObject $request): void
    {
        // If user is already authenticated, redirect to admin dashboard
        if ($request->isAuthenticated()) {
            header('Location: /admin');
            exit;
        }
        
        // Get language code from request
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get form data
        $token = $_POST['token'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        
        // Initialize message arrays
        $errorMessages = [];
        $successMessages = [];
        
        // Validate token and email
        if (empty($token) || empty($email)) {
            $errorMessages[] = $translationService->translate('reset_password.invalid_data');
            
            // Render forgot password form with error
            echo $this->render('auth/forgot-password', [
                'error' => $errorMessages,
                'success' => [],
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode,
                'formData' => []
            ]);
            exit;
        }
        
        // Validate passwords match
        if ($password !== $confirmPassword) {
            $errorMessages[] = $translationService->translate('reset_password.passwords_mismatch');
            
            echo $this->render('auth/reset-password', [
                'token' => $token,
                'email' => $email,
                'error' => $errorMessages,
                'success' => $successMessages,
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode
            ]);
            exit;
        }
        
        // Validate password length
        if (strlen($password) < 8) {
            $errorMessages[] = $translationService->translate('reset_password.password_too_short');
            
            echo $this->render('auth/reset-password', [
                'token' => $token,
                'email' => $email,
                'error' => $errorMessages,
                'success' => $successMessages,
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode
            ]);
            exit;
        }
        
        try {
            // Get user by email
            $user = $this->userModel->getUserByEmail($email);
            
            if (!$user) {
                $errorMessages[] = $translationService->translate('reset_password.user_not_found');
                
                echo $this->render('auth/forgot-password', [
                    'error' => $errorMessages,
                    'success' => [],
                    'request' => $request,
                    'translations' => $translationService,
                    'language' => $langCode,
                    'formData' => []
                ]);
                exit;
            }
            
            // In a real application, update the user's password here
            // $this->userModel->updateUser($user->userId, ['userPassword' => $password]);
            
            // Set success message for login form
            $successMessages[] = $translationService->translate('reset_password.success');
            
            // Render login form with success message
            echo $this->render('auth/login', [
                'error' => [],
                'success' => $successMessages,
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode,
                'formData' => ['email' => $email]
            ]);
            exit;
        } catch (\Exception $e) {
            // Log the error
            error_log('Password reset error: ' . $e->getMessage());
            
            // Add error message
            $errorMessages[] = $translationService->translate('reset_password.error');
            
            // Return to reset password form
            echo $this->render('auth/reset-password', [
                'token' => $token,
                'email' => $email,
                'error' => $errorMessages,
                'success' => [],
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode
            ]);
            exit;
        }
    }
}