<?php

namespace App\Controllers;

use App\Core\RequestObject;
use App\Models\ProjectModel;
use App\Models\PersonalInfoModel;
use App\Models\LanguageModel;
use App\Models\CompetencyModel;
use App\Models\UserModel;
use App\Services\TranslationService;
use App\Services\ValidationService;

/**
 * AdminController - Handles administrative actions
 * 
 * This controller is responsible for rendering and processing
 * administrative pages, including project and personal info management.
 */
class AdminController extends BaseController
{
    private ProjectModel $projectModel;
    private PersonalInfoModel $personalInfoModel;
    private LanguageModel $languageModel;
    private CompetencyModel $competencyModel;
    private UserModel $userModel;
    private \App\Services\Database $database;
    
    /**
     * Create a new AdminController instance
     */
    public function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->personalInfoModel = new PersonalInfoModel();
        $this->languageModel = new LanguageModel();
        $this->competencyModel = new CompetencyModel();
        $this->userModel = new UserModel();
        $this->database = new \App\Services\Database();
    }
    
    /**
     * Display the admin dashboard
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function dashboard(RequestObject $request): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get project counts
        $currentProjects = $this->projectModel->getAllProjects($langCode, 'current');
        $pastProjects = $this->projectModel->getAllProjects($langCode, 'past');
        
        // Render the dashboard
        echo $this->render('admin/dashboard', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'currentProjectCount' => count($currentProjects),
            'pastProjectCount' => count($pastProjects)
        ]);
    }
    
    /**
     * Display the projects list
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function projects(RequestObject $request): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get filter parameters
        $status = $request->getQuery('status');
        $type = $request->getQuery('type');
        $search = $request->getQuery('search');
        
        // Get all projects with filters
        $projects = $this->projectModel->getAllProjects($langCode, $status);
        
        // Manual filter for search if not handled by model
        if ($search) {
            $projects = array_filter($projects, function($p) use ($search) {
                return stripos($p->title, $search) !== false || stripos($p->description, $search) !== false;
            });
        }
        
        // Render the projects list
        echo $this->render('admin/projects', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'projects' => $projects,
            'languages' => $this->languageModel->getActiveLanguages()
        ]);
    }

    /**
     * Display the projects list (deprecated name)
     */
    public function projectList(RequestObject $request): void
    {
        $this->projects($request);
    }
    
    /**
     * Display the new project form
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function newProject(RequestObject $request): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get all languages
        $languages = $this->languageModel->getActiveLanguages();

        // Get categories for skill selection
        $categories = $this->competencyModel->getAllCategoriesWithCompetencies($langCode);
        
        // Render the new project form
        echo $this->render('admin/project-form', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'languages' => $languages,
            'categories' => $categories,
            'project' => null,
            'projectTranslations' => [],
            'formData' => [],
            'isNew' => true
        ]);
    }
    
    /**
     * Process new project creation
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function createProject(RequestObject $request): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get form data
        $type = $request->getPost('type', '');
        $status = $request->getPost('status', '');
        $startDate = $request->getPost('start_date', '');
        $endDate = $request->getPost('end_date', '');
        $githubUrl = $request->getPost('github_url', '');
        $websiteUrl = $request->getPost('website_url', '');
        
        // Get translations for each language
        $languages = $this->languageModel->getActiveLanguages();
        $translations = [];
        
        foreach ($languages as $language) {
            $translations[$language->code] = [
                'title' => $request->getPost("title_{$language->code}", ''),
                'subtitle' => $request->getPost("subtitle_{$language->code}", ''),
                'description' => $request->getPost("description_{$language->code}", ''),
                'skills' => $request->getPost("skills_{$language->code}", '')
            ];
        }
        
        // Validate form data
        $validator = new ValidationService();
        
        $validator->required($type, 'type', $translationService->translate('project.error.type_required'));
        $validator->required($status, 'status', $translationService->translate('project.error.status_required'));
        $validator->required($startDate, 'start_date', $translationService->translate('project.error.start_date_required'));
        $validator->date($startDate, 'start_date', $translationService->translate('project.error.invalid_start_date'));
        
        if (!empty($endDate)) {
            $validator->date($endDate, 'end_date', $translationService->translate('project.error.invalid_end_date'));
            
            if ($startDate > $endDate) {
                $validator->addError('end_date', $translationService->translate('project.error.end_date_before_start'));
            }
        }
        
        if (!empty($githubUrl)) {
            $validator->url($githubUrl, 'github_url', $translationService->translate('project.error.invalid_github_url'));
        }
        
        if (!empty($websiteUrl)) {
            $validator->url($websiteUrl, 'website_url', $translationService->translate('project.error.invalid_website_url'));
        }
        
        // Validate translations
        foreach ($languages as $language) {
            $validator->required(
                $translations[$language->code]['title'],
                "title_{$language->code}",
                $translationService->translate('project.error.title_required', ['language' => $language->name])
            );
            
            $validator->required(
                $translations[$language->code]['description'],
                "description_{$language->code}",
                $translationService->translate('project.error.description_required', ['language' => $language->name])
            );
            
            $validator->required(
                $translations[$language->code]['skills'],
                "skills_{$language->code}",
                $translationService->translate('project.error.skills_required', ['language' => $language->name])
            );
        }
        
        // If there are validation errors, redisplay the form
        if ($validator->hasErrors()) {
            // Combine all form data
            $formData = [
                'type' => $type,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'github_url' => $githubUrl,
                'website_url' => $websiteUrl
            ];
            
            foreach ($languages as $language) {
                $formData["title_{$language->code}"] = $translations[$language->code]['title'];
                $formData["subtitle_{$language->code}"] = $translations[$language->code]['subtitle'];
                $formData["description_{$language->code}"] = $translations[$language->code]['description'];
                $formData["skills_{$language->code}"] = $translations[$language->code]['skills'];
            }
            
            // Get categories for skill selection
            $categories = $this->competencyModel->getAllCategoriesWithCompetencies($langCode);

            // Render the form with errors
            echo $this->render('admin/project-form', [
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode,
                'languages' => $languages,
                'categories' => $categories,
                'project' => null,
                'projectTranslations' => [],
                'formData' => $formData,
                'isNew' => true,
                'error' => $validator->getErrors()
            ]);
            return;
        }
        
        // Create project data
        $projectData = [
            'type' => $type,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate ?: null,
            'github_url' => $githubUrl ?: null,
            'website_url' => $websiteUrl ?: null
        ];
        
        try {
            // Create the project
            $projectId = $this->projectModel->createProject($projectData, $translations);
            
            // Set success message
            $_SESSION['success'] = [
                $translationService->translate('project.success.created')
            ];
            
            // Redirect to projects list
            header('Location: /admin/projects');
            exit;
        } catch (\Exception $e) {
            // Log the error
            error_log('Error creating project: ' . $e->getMessage());
            
            // Combine all form data
            $formData = [
                'type' => $type,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'github_url' => $githubUrl,
                'website_url' => $websiteUrl
            ];
            
            foreach ($languages as $language) {
                $formData["title_{$language->code}"] = $translations[$language->code]['title'];
                $formData["subtitle_{$language->code}"] = $translations[$language->code]['subtitle'];
                $formData["description_{$language->code}"] = $translations[$language->code]['description'];
                $formData["skills_{$language->code}"] = $translations[$language->code]['skills'];
            }
            
            // Get categories for skill selection
            $categories = $this->competencyModel->getAllCategoriesWithCompetencies($langCode);

            // Render the form with error
            echo $this->render('admin/project-form', [
                'request' => $request,
                'translations' => $translationService,
                'language' => $langCode,
                'languages' => $languages,
                'categories' => $categories,
                'project' => null,
                'projectTranslations' => [],
                'formData' => $formData,
                'isNew' => true,
                'error' => [$translationService->translate('project.error.create_failed')]
            ]);
        }
    }
    
    /**
     * Display the edit project form
     * 
     * @param RequestObject $request Current request information
     * @param int $id Project ID
     * @return void
     */
    public function editProject(RequestObject $request, int $id): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        // Get project
        $project = $this->projectModel->getProjectById($id, $langCode);
        
        if (!$project) {
            // Project not found
            $_SESSION['error'] = [
                $translationService->translate('project.error.not_found')
            ];
            
            // Redirect to projects list
            header('Location: /admin/projects');
            exit;
        }
        
        // Get all languages
        $languages = $this->languageModel->getActiveLanguages();
        
        // Get project translations
        $projectTranslations = $this->projectModel->getProjectTranslations($id);

        // Get categories for skill selection
        $categories = $this->competencyModel->getAllCategoriesWithCompetencies($langCode);
        
        // Render the edit project form
        echo $this->render('admin/project-form', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'languages' => $languages,
            'categories' => $categories,
            'project' => $project,
            'projectTranslations' => $projectTranslations,
            'formData' => [],
            'isNew' => false
        ]);
    }
    
    /**
     * Process project update
     * 
     * @param RequestObject $request Current request information
     * @param int $id Project ID
     * @return void
     */
    public function updateProject(RequestObject $request, int $id): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        $translationService = new TranslationService($langCode);
        $project = $this->projectModel->getProjectById($id, $langCode);
        
        if (!$project) {
            $_SESSION['error'] = [$translationService->translate('project.error.not_found')];
            header('Location: /admin/projects');
            exit;
        }

        $type = $request->getPost('type', '');
        $status = $request->getPost('status', '');
        $startDate = $request->getPost('start_date', '');
        $endDate = $request->getPost('end_date', '');
        $githubUrl = $request->getPost('github_url', '');
        $websiteUrl = $request->getPost('website_url', '');

        $languages = $this->languageModel->getActiveLanguages();
        $translations = [];
        foreach ($languages as $language) {
            $translations[$language->code] = [
                'title' => $request->getPost("title_{$language->code}", ''),
                'subtitle' => $request->getPost("subtitle_{$language->code}", ''),
                'description' => $request->getPost("description_{$language->code}", ''),
                'skills' => $request->getPost("skills_{$language->code}", '')
            ];
        }

        // Validate form data
        $validator = new ValidationService();
        $validator->required($type, 'type', $translationService->translate('project.error.type_required'));
        $validator->required($status, 'status', $translationService->translate('project.error.status_required'));
        $validator->required($startDate, 'start_date', $translationService->translate('project.error.start_date_required'));

        if ($validator->hasErrors()) {
            $_SESSION['error'] = $validator->getErrors();
            header("Location: /admin/project/edit/$id");
            exit;
        }

        $projectData = [
            'type' => $type,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate ?: null,
            'github_url' => $githubUrl ?: null,
            'website_url' => $websiteUrl ?: null
        ];

        try {
            $this->projectModel->updateProject($id, $projectData, $translations);
            $_SESSION['success'] = [$translationService->translate('project.success.updated')];
            header('Location: /admin/projects');
            exit;
        } catch (\Exception $e) {
            error_log('Error updating project: ' . $e->getMessage());
            $_SESSION['error'] = [$translationService->translate('project.error.update_failed')];
            header("Location: /admin/project/edit/$id");
            exit;
        }
    }
    
    /**
     * Display edit category form
     */
    public function editCompetencyCategory(RequestObject $request, int $id): void
    {
        if (!$request->isAuthenticated()) { $this->redirectToLogin($request); return; }
        $langCode = $request->getLanguageCode();
        $languages = $this->languageModel->getActiveLanguages();
        
        $category = $this->database->fetchOne('SELECT * FROM competency_categories WHERE id = :id', ['id' => $id]);
        $translations = $this->database->fetchAll('SELECT ct.*, l.code FROM competency_category_translations ct JOIN languages l ON ct.language_id = l.id WHERE ct.category_id = :id', ['id' => $id]);
        
        $translationsByLang = [];
        foreach($translations as $t) { $translationsByLang[$t->code] = $t; }

        echo $this->render('admin/competency-category-form', [
            'request' => $request,
            'languages' => $languages,
            'category' => $category,
            'translationsByLang' => $translationsByLang,
            'isNew' => false
        ]);
    }

    /**
     * Display new category form
     */
    public function newCompetencyCategory(RequestObject $request): void
    {
        if (!$request->isAuthenticated()) { $this->redirectToLogin($request); return; }
        echo $this->render('admin/competency-category-form', [
            'request' => $request,
            'languages' => $this->languageModel->getActiveLanguages(),
            'category' => null,
            'translationsByLang' => [],
            'isNew' => true
        ]);
    }

    /**
     * Display edit skill form
     */
    public function editCompetency(RequestObject $request, int $id): void
    {
        if (!$request->isAuthenticated()) { $this->redirectToLogin($request); return; }
        $languages = $this->languageModel->getActiveLanguages();
        $competency = $this->database->fetchOne('SELECT * FROM competencies WHERE id = :id', ['id' => $id]);
        $translationsData = $this->database->fetchAll('SELECT ct.*, l.code FROM competency_translations ct JOIN languages l ON ct.language_id = l.id WHERE ct.competency_id = :id', ['id' => $id]);
        
        $translationsByLang = [];
        foreach($translationsData as $t) { $translationsByLang[$t->code] = $t; }

        echo $this->render('admin/competency-form', [
            'request' => $request,
            'languages' => $languages,
            'competency' => $competency,
            'translationsByLang' => $translationsByLang,
            'categories' => $this->competencyModel->getAllCategoriesWithCompetencies($request->getLanguageCode()),
            'isNew' => false
        ]);
    }

    /**
     * Display new skill form
     */
    public function newCompetency(RequestObject $request): void
    {
        if (!$request->isAuthenticated()) { $this->redirectToLogin($request); return; }
        echo $this->render('admin/competency-form', [
            'request' => $request,
            'languages' => $this->languageModel->getActiveLanguages(),
            'competency' => null,
            'translationsByLang' => [],
            'cat_id' => $request->getQuery('category_id'),
            'categories' => $this->competencyModel->getAllCategoriesWithCompetencies($request->getLanguageCode()),
            'isNew' => true
        ]);
    }

    /**
     * Process skill (competency) creation/update
     */
    public function updateCompetency(RequestObject $request, ?int $id = null): void
    {
        if (!$request->isAuthenticated()) { $this->redirectToLogin($request); return; }
        
        $langCode = $request->getLanguageCode();
        $translationService = new TranslationService($langCode);
        
        $categoryId = (int)$request->getPost('category_id');
        $slug = $request->getPost('slug', '');
        $color = $request->getPost('color', '#3b82f6');
        
        $languages = $this->languageModel->getActiveLanguages();
        $translations = [];
        foreach ($languages as $lang) {
            $translations[$lang->code] = $request->getPost("name_{$lang->code}", '');
        }

        try {
            if ($id) {
                $this->competencyModel->updateCompetency($id, $categoryId, $slug, $color, $translations);
                $_SESSION['success'] = ["Skill updated successfully"];
            } else {
                $this->competencyModel->addCompetency($categoryId, $slug, $color, 0, $translations);
                $_SESSION['success'] = ["Skill created successfully"];
            }
            header('Location: /admin/competencies');
            exit;
        } catch (\Exception $e) {
            $_SESSION['error'] = ["Error saving skill: " . $e->getMessage()];
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    /**
     * Process category creation/update
     */
    public function updateCompetencyCategory(RequestObject $request, ?int $id = null): void
    {
        if (!$request->isAuthenticated()) { $this->redirectToLogin($request); return; }
        
        $slug = $request->getPost('slug', '');
        $icon = $request->getPost('icon', '');
        
        $languages = $this->languageModel->getActiveLanguages();
        $translations = [];
        foreach ($languages as $lang) {
            $translations[$lang->code] = $request->getPost("name_{$lang->code}", '');
        }

        try {
            if ($id) {
                $this->competencyModel->updateCategory($id, $slug, $icon, $translations);
                $_SESSION['success'] = ["Category updated successfully"];
            } else {
                $this->competencyModel->addCategory($slug, $icon, $translations);
                $_SESSION['success'] = ["Category created successfully"];
            }
            header('Location: /admin/competencies');
            exit;
        } catch (\Exception $e) {
            $_SESSION['error'] = ["Error saving category: " . $e->getMessage()];
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    /**
     * Process skill deletion
     */
    public function deleteCompetency(RequestObject $request, int $id): void
    {
        if (!$request->isAuthenticated()) { $this->redirectToLogin($request); return; }
        try {
            $this->competencyModel->deleteCompetency($id);
            $_SESSION['success'] = ["Skill deleted"];
        } catch (\Exception $e) {
            $_SESSION['error'] = ["Error deleting skill"];
        }
        header('Location: /admin/competencies');
        exit;
    }

    /**
     * Process category deletion
     */
    public function deleteCompetencyCategory(RequestObject $request, int $id): void
    {
        if (!$request->isAuthenticated()) { $this->redirectToLogin($request); return; }
        try {
            $this->competencyModel->deleteCategory($id);
            $_SESSION['success'] = ["Category deleted"];
        } catch (\Exception $e) {
            $_SESSION['error'] = ["Error deleting category"];
        }
        header('Location: /admin/competencies');
        exit;
    }

    /**
     * Process project deletion
     * 
     * @param RequestObject $request Current request information
     * @param int $id Project ID
     * @return void
     */
    public function deleteProject(RequestObject $request, int $id): void
    {
        // Check if user is authenticated
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }
        
        // Get current language
        $langCode = $request->getLanguageCode();
        
        // Initialize translation service
        $translationService = new TranslationService($langCode);
        
        try {
            // Check if project exists
            $project = $this->projectModel->getProjectById($id, $langCode);
            
            if (!$project) {
                // Project not found
                $_SESSION['error'] = [
                    $translationService->translate('project.error.not_found')
                ];
                
                // Redirect to projects list
                header('Location: /admin/projects');
                exit;
            }
            
            // Delete the project
            $this->projectModel->deleteProject($id);
            
            // Set success message
            $_SESSION['success'] = [
                $translationService->translate('project.success.deleted')
            ];
            
            // Redirect to projects list
            header('Location: /admin/projects');
            exit;
        } catch (\Exception $e) {
            // Log the error
            error_log('Error deleting project: ' . $e->getMessage());
            
            // Set error message
            $_SESSION['error'] = [
                $translationService->translate('project.error.delete_failed')
            ];
            
            // Redirect to projects list
            header('Location: /admin/projects');
            exit;
        }
    }
    
    /**
     * Display competencies management page
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    public function competencies(RequestObject $request): void
    {
        if (!$request->isAuthenticated()) {
            $this->redirectToLogin($request);
            return;
        }

        $langCode = $request->getLanguageCode();
        $translationService = new TranslationService($langCode);
        
        $categories = $this->competencyModel->getAllCategoriesWithCompetencies($langCode);
        $languages = $this->languageModel->getActiveLanguages();

        echo $this->render('admin/competencies', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'categories' => $categories,
            'languages' => $languages
        ]);
    }

    /**
     * Display the personal info management form
     */
    public function personalInfo(RequestObject $request): void
    {
        if (!$request->isAuthenticated()) { $this->redirectToLogin($request); return; }
        
        $langCode = $request->getLanguageCode();
        $translationService = new TranslationService($langCode);
        
        $personalInfo = $this->personalInfoModel->getPersonalInfo($langCode);
        $languages = $this->languageModel->getActiveLanguages();
        $personalInfoTranslations = $this->personalInfoModel->getPersonalInfoTranslations($personalInfo->id);
        
        echo $this->render('admin/profile', [
            'request' => $request,
            'translations' => $translationService,
            'language' => $langCode,
            'languages' => $languages,
            'personalInfo' => $personalInfo,
            'personalInfoTranslations' => $personalInfoTranslations
        ]);
    }

    /**
     * Update personal info and translations
     */
    public function updatePersonalInfo(RequestObject $request): void
    {
        if (!$request->isAuthenticated()) { $this->redirectToLogin($request); return; }
        
        $langCode = $request->getLanguageCode();
        $translationService = new TranslationService($langCode);

        $personalData = [
            'email' => $request->getPost('email'),
            'github_url' => $request->getPost('github_url'),
            'linkedin_url' => $request->getPost('linkedin_url'),
            'discord_url' => $request->getPost('discord_url'),
            'profile_picture_url' => $request->getPost('profile_picture_url')
        ];

        $languages = $this->languageModel->getActiveLanguages();
        $translations = [];
        foreach ($languages as $lang) {
            $translations[$lang->code] = [
                'about_text' => $request->getPost("about_{$lang->code}")
            ];
        }

        try {
            $this->personalInfoModel->updatePersonalInfo($personalData, $translations);
            $_SESSION['success'] = ["Bio updated successfully"];
        } catch (\Exception $e) {
            $_SESSION['error'] = ["Failed to update bio: " . $e->getMessage()];
        }

        header('Location: /admin/personal-info');
        exit;
    }

    /**
     * Display the user profile management form
     */
    public function profile(RequestObject $request): void
    {
        if (!$request->isAuthenticated()) { $this->redirectToLogin($request); return; }
        
        $langCode = $request->getLanguageCode();
        $translationService = new TranslationService($langCode);
        
        $userId = $request->getUserId();
        
        // Ensure user is not null to avoid 500
        if (!$userId || !($user = $this->userModel->getUserById($userId))) {
            $_SESSION['error'] = ["User not found or not authenticated"];
            header('Location: /login');
            exit;
        }

        $this->renderView('admin/profile-user', [
            'request' => $request,
            'translations' => $translationService,
            'user' => $user
        ]);
    }

    /**
     * Update user credentials (email/password)
     */
    public function updateProfile(RequestObject $request): void
    {
        if (!$request->isAuthenticated()) { $this->redirectToLogin($request); return; }
        
        $userId = $request->getUserId();
        $newEmail = $request->getPost('email');
        $currentPassword = $request->getPost('current_password');
        $newPassword = $request->getPost('new_password');
        $confirmPassword = $request->getPost('confirm_password');

        try {
            // Update email if changed
            $user = $this->userModel->getUserById($userId);
            if ($newEmail && $newEmail !== $user->email) {
                $this->userModel->updateUser($userId, ['userEmail' => $newEmail]);
                
                // Refresh session/token data
                $tokenService = new \App\Services\TokenService(\App\Config\ConfigManager::getInstance()->getConfigFor($this), new \App\Services\CacheService());
                $tokenService->createJWT($userId, 1); // This refreshes the cookie
                
                // Clear the permissions cache which contains the old email
                $cacheService = new \App\Services\CacheService();
                $cacheKey = "user_permissions_{$userId}_1";
                $cacheService->delete($cacheKey);
                
                $_SESSION['success'][] = "Email updated successfully. Information refreshed.";
            }

            // Update password if requested
            if (!empty($currentPassword) && !empty($newPassword)) {
                if ($newPassword !== $confirmPassword) {
                    throw new \Exception("New passwords do not match");
                }
                
                if ($this->userModel->changePassword($userId, $currentPassword, $newPassword)) {
                    $_SESSION['success'][] = "Password updated successfully";
                } else {
                    throw new \Exception("Invalid current password");
                }
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = [$e->getMessage()];
        }

        header('Location: /admin/profile');
        exit;
    }

    /**
     * Redirect to login page
     * 
     * @param RequestObject $request Current request information
     * @return void
     */
    private function redirectToLogin(RequestObject $request): void
    {
        // Save current URL for redirection after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header('Location: /login');
        exit;
    }
}
