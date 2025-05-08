<?php

/**
 * Database Initializer Script
 * 
 * This script ensures the database is properly set up with required tables and initial data.
 * Run this script if you're experiencing issues with database connections or missing data.
 * 
 * Usage: php db-initializer.php
 */

// Define the application's root directory
define('ROOT_DIR', dirname(__DIR__));

// Require the Composer autoloader
require ROOT_DIR . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
$dotenv->load();

// Database connection parameters
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbUser = $_ENV['DB_USER'] ?? 'portfolio_user';
$dbPassword = $_ENV['DB_PASSWORD'] ?? '';
$dbName = $_ENV['DB_NAME'] ?? 'portfolio_db';
$dbPort = $_ENV['DB_PORT'] ?? '3306';

echo "Database Initializer Script\n";
echo "===========================\n\n";

try {
    echo "Connecting to database server...\n";
    // Connect to MySQL without specifying a database
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connection successful!\n";
    
    // Check if database exists
    echo "Checking if database '$dbName' exists...\n";
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    $databaseExists = $stmt->rowCount() > 0;
    
    if (!$databaseExists) {
        echo "Database '$dbName' not found. Creating...\n";
        $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Database created successfully.\n";
    } else {
        echo "Database '$dbName' already exists.\n";
    }
    
    // Connect to the specific database
    echo "Connecting to '$dbName'...\n";
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check for essential tables
    $requiredTables = [
        'users', 'languages', 'projects', 'project_translations', 
        'personal_info', 'personal_info_translations', 'ui_texts', 'ui_text_translations'
    ];
    
    echo "Checking database tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missingTables = array_diff($requiredTables, $existingTables);
    
    if (!empty($missingTables)) {
        echo "Missing tables found: " . implode(', ', $missingTables) . "\n";
        echo "Creating database schema...\n";
        
        // Database schema
        $schema = <<<SQL
-- Main database schema for multilingual portfolio website

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Languages supported by the system
CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(5) NOT NULL UNIQUE COMMENT 'ISO language code (e.g., "en", "fr")',
    name VARCHAR(50) NOT NULL COMMENT 'Language name in its own language',
    is_active BOOLEAN DEFAULT TRUE
);

-- Projects/Experiences
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL COMMENT 'Project type (e.g., "personal", "cesi", etc.)',
    status VARCHAR(20) NOT NULL COMMENT 'Current, past, or canceled',
    start_date DATE NOT NULL,
    end_date DATE NULL COMMENT 'NULL if ongoing',
    github_url VARCHAR(255) NULL,
    website_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Project translations (multilingual content)
CREATE TABLE IF NOT EXISTS project_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    language_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) NULL,
    description TEXT NOT NULL,
    skills TEXT NOT NULL COMMENT 'Comma-separated list of skills',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id),
    UNIQUE KEY (project_id, language_id)
);

-- Personal info (about section)
CREATE TABLE IF NOT EXISTS personal_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    github_url VARCHAR(255) NOT NULL,
    linkedin_url VARCHAR(255) NOT NULL,
    discord_url VARCHAR(255) NULL,
    profile_picture_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Personal info translations
CREATE TABLE IF NOT EXISTS personal_info_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personal_info_id INT NOT NULL,
    language_id INT NOT NULL,
    about_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personal_info_id) REFERENCES personal_info(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id),
    UNIQUE KEY (personal_info_id, language_id)
);

-- Static UI Texts (for multilingual interface elements)
CREATE TABLE IF NOT EXISTS ui_texts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text_key VARCHAR(100) NOT NULL COMMENT 'Unique identifier for the text',
    context VARCHAR(100) NULL COMMENT 'Where this text is used'
);

-- UI text translations
CREATE TABLE IF NOT EXISTS ui_text_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ui_text_id INT NOT NULL,
    language_id INT NOT NULL,
    text TEXT NOT NULL,
    FOREIGN KEY (ui_text_id) REFERENCES ui_texts(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id),
    UNIQUE KEY (ui_text_id, language_id)
);
SQL;
        
        // Execute the schema SQL
        $pdo->exec($schema);
        echo "Schema created successfully.\n";
    } else {
        echo "All required tables exist.\n";
    }
    
    // Check if languages are initialized
    echo "Checking for language data...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM languages");
    $languageCount = (int)$stmt->fetchColumn();
    
    if ($languageCount === 0) {
        echo "No languages found. Initializing language data...\n";
        $pdo->exec("INSERT INTO languages (code, name) VALUES ('en', 'English'), ('fr', 'Français')");
        echo "Language data initialized.\n";
    } else {
        echo "Language data already exists ($languageCount languages).\n";
    }
    
    // Check if an admin user exists
    echo "Checking for admin user...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = (int)$stmt->fetchColumn();
    
    if ($userCount === 0) {
        echo "No users found. Creating admin user...\n";
        
        // Default admin credentials (password: Admin@123)
        $defaultPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
        
        $pdo->exec("INSERT INTO users (username, email, password_hash, first_name, last_name) 
                   VALUES ('admin', 'admin@example.com', '$defaultPassword', 'Admin', 'User')");
        
        echo "Admin user created with following credentials:\n";
        echo "Email: admin@example.com\n";
        echo "Password: Admin@123\n";
        echo "Please change these credentials after your first login!\n";
    } else {
        echo "Users already exist ($userCount users).\n";
    }
    
    // Check if personal info exists
    echo "Checking for personal info...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM personal_info");
    $personalInfoCount = (int)$stmt->fetchColumn();
    
    if ($personalInfoCount === 0) {
        echo "No personal info found. Creating default personal info...\n";
        
        // Insert default personal info
        $stmt = $pdo->prepare("INSERT INTO personal_info (email, github_url, linkedin_url, discord_url, profile_picture_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'contact@example.com',
            'https://github.com/username',
            'https://linkedin.com/in/username',
            'https://discord.com/users/username',
            '/assets/img/AshaLogo.png'
        ]);
        
        // Get the inserted ID
        $personalInfoId = $pdo->lastInsertId();
        
        // Get language IDs
        $englishId = null;
        $frenchId = null;
        
        $stmt = $pdo->query("SELECT id, code FROM languages");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['code'] === 'en') {
                $englishId = $row['id'];
            } elseif ($row['code'] === 'fr') {
                $frenchId = $row['id'];
            }
        }
        
        // Insert translations
        if ($englishId) {
            $aboutTextEN = "Hi, I'm Nathan Polette,\nbetter known by the username Asha Geyon (Natpol50).\nI've been developing and learning for almost\n6 years now with the goal of becoming\nas versatile as possible.\nHave a challenge or an idea? Feel free to contact me!\n(By the way, I also draw and do design from time to time)";
            
            $stmt = $pdo->prepare("INSERT INTO personal_info_translations (personal_info_id, language_id, about_text) VALUES (?, ?, ?)");
            $stmt->execute([$personalInfoId, $englishId, $aboutTextEN]);
        }
        
        if ($frenchId) {
            $aboutTextFR = "Salut, je suis Nathan Polette,\nplus connu sous le pseudonyme Asha Geyon (Natpol50).\nCela fait maintenant presque 6 ans que je\ndéveloppe et j'apprends dans l'optique de\ndevenir le plus polyvalent possible.\nUn défi, une idée ? N'hésitez pas à me contacter !\n(Au fait, je dessine aussi et fais du design de temps en temps)";
            
            $stmt = $pdo->prepare("INSERT INTO personal_info_translations (personal_info_id, language_id, about_text) VALUES (?, ?, ?)");
            $stmt->execute([$personalInfoId, $frenchId, $aboutTextFR]);
        }
        
        echo "Default personal info created.\n";
    } else {
        echo "Personal info already exists.\n";
    }
    
    // Check for sample projects
    echo "Checking for projects...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects");
    $projectCount = (int)$stmt->fetchColumn();
    
    if ($projectCount === 0) {
        echo "No projects found. Creating sample projects...\n";
        
        // Get language IDs
        $englishId = null;
        $frenchId = null;
        
        $stmt = $pdo->query("SELECT id, code FROM languages");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['code'] === 'en') {
                $englishId = $row['id'];
            } elseif ($row['code'] === 'fr') {
                $frenchId = $row['id'];
            }
        }
        
        // Insert 16 sample projects
        for ($i = 1; $i <= 16; $i++) {
            $status = ($i % 2 === 0) ? 'current' : 'past'; // Alternate between current and past
            $startDate = date('Y-m-d', strtotime("-" . rand(1, 1000) . " days"));
            $endDate = $status === 'past' ? date('Y-m-d', strtotime($startDate . " + " . rand(30, 365) . " days")) : null;
            
            $stmt = $pdo->prepare("INSERT INTO projects (type, status, start_date, end_date, github_url, website_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                'personal',
                $status,
                $startDate,
                $endDate,
                'https://github.com/username/project' . $i,
                'https://example.com/project' . $i
            ]);
            
            $projectId = $pdo->lastInsertId();
            
            // Insert translations
            if ($englishId) {
                $stmt = $pdo->prepare("INSERT INTO project_translations (project_id, language_id, title, subtitle, description, skills) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $projectId,
                    $englishId,
                    "Project $i",
                    "Subtitle for Project $i",
                    "Description for Project $i in English. This is a sample project to demonstrate multilingual support.",
                    "PHP, MySQL, JavaScript"
                ]);
            }
            
            if ($frenchId) {
                $stmt = $pdo->prepare("INSERT INTO project_translations (project_id, language_id, title, subtitle, description, skills) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $projectId,
                    $frenchId,
                    "Projet $i",
                    "Sous-titre pour le Projet $i",
                    "Description pour le Projet $i en français. Ceci est un projet exemple pour démontrer le support multilingue.",
                    "PHP, MySQL, JavaScript"
                ]);
            }
        }
        
        echo "16 sample projects created successfully.\n";
    } else {
        echo "Projects already exist ($projectCount projects).\n";
    }
    
    // Check for UI texts (translations)
    echo "Checking for UI texts...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM ui_texts");
    $uiTextCount = (int)$stmt->fetchColumn();
    
    if ($uiTextCount === 0) {
        echo "No UI texts found. Installing default translations...\n";
        
        // Get language IDs
        $englishId = null;
        $frenchId = null;
        
        $stmt = $pdo->query("SELECT id, code FROM languages");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['code'] === 'en') {
                $englishId = $row['id'];
            } elseif ($row['code'] === 'fr') {
                $frenchId = $row['id'];
            }
        }
        
        // Define essential UI texts
        $essentialTexts = [
            'site.title' => [
                'en' => 'Polette Nathan - Portfolio',
                'fr' => 'Polette Nathan - Portfolio'
            ],
            'meta.description' => [
                'en' => 'Nathan Polette\'s personal portfolio - Computer Engineering Student',
                'fr' => 'Portfolio personnel de Nathan Polette - Étudiant en ingénierie informatique'
            ],
            'nav.projects' => [
                'en' => 'Projects',
                'fr' => 'Projets'
            ],
            'nav.contact' => [
                'en' => 'Contact',
                'fr' => 'Contact'
            ],
            'nav.admin' => [
                'en' => 'Admin',
                'fr' => 'Admin'
            ],
            'nav.logout' => [
                'en' => 'Logout',
                'fr' => 'Déconnexion'
            ],
            'nav.aria_label' => [
                'en' => 'Main navigation',
                'fr' => 'Navigation principale'
            ],
            'footer.about_title' => [
                'en' => 'About me',
                'fr' => 'Un petit résumé rapide'
            ],
            'footer.contact_title' => [
                'en' => 'Contacts & accounts',
                'fr' => 'Contacts & comptes'
            ],
            'footer.rights_reserved' => [
                'en' => 'All rights reserved. (design & code by Asha Geyon)',
                'fr' => 'Tous droits réservés. (design & code par Asha Geyon)'
            ],
            'home.subtitle' => [
                'en' => 'Computer Engineering Student',
                'fr' => 'Étudiant en ingénierie informatique'
            ],
            'home.contact_button' => [
                'en' => 'Contact me',
                'fr' => 'Me contacter'
            ],
            'home.projects_title' => [
                'en' => 'My projects',
                'fr' => 'Mes projets'
            ],
            'home.current_projects' => [
                'en' => 'Current projects',
                'fr' => 'Projets en cours'
            ],
            'home.past_projects' => [
                'en' => 'Past projects',
                'fr' => 'Projets passés'
            ],
            'home.no_current_projects' => [
                'en' => 'No current projects to display.',
                'fr' => 'Aucun projet en cours à afficher.'
            ],
            'home.no_past_projects' => [
                'en' => 'No past projects to display.',
                'fr' => 'Aucun projet passé à afficher.'
            ],
            'home.intro_label' => [
                'en' => 'Introduction',
                'fr' => 'Introduction'
            ],
            'home.since' => [
                'en' => 'Since',
                'fr' => 'Depuis'
            ],
            'contact.title' => [
                'en' => 'Contact',
                'fr' => 'Contact'
            ],
            'contact.heading' => [
                'en' => 'Contact Me',
                'fr' => 'Me Contacter'
            ],
            'contact.submit' => [
                'en' => 'Send',
                'fr' => 'Envoyer'
            ],
            'login.title' => [
                'en' => 'Login',
                'fr' => 'Connexion'
            ],
            'login.email' => [
                'en' => 'Email',
                'fr' => 'Email'
            ],
            'login.password' => [
                'en' => 'Password',
                'fr' => 'Mot de passe'
            ],
            'login.submit' => [
                'en' => 'Login',
                'fr' => 'Se connecter'
            ],
            'login.error.invalid_credentials' => [
                'en' => 'Invalid email or password.',
                'fr' => 'Email ou mot de passe incorrect.'
            ],
            'login.error.auth_error' => [
                'en' => 'An error occurred during login. Please try again.',
                'fr' => 'Une erreur est survenue lors de la connexion. Veuillez réessayer.'
            ],
            'login.logout_success' => [
                'en' => 'You have been successfully logged out.',
                'fr' => 'Vous avez été déconnecté avec succès.'
            ],
            'admin.dashboard' => [
                'en' => 'Dashboard',
                'fr' => 'Tableau de bord'
            ],
            'admin.projects' => [
                'en' => 'Projects',
                'fr' => 'Projets'
            ],
            'admin.add_project' => [
                'en' => 'Add Project',
                'fr' => 'Ajouter un projet'
            ],
            'admin.edit' => [
                'en' => 'Edit',
                'fr' => 'Modifier'
            ],
            'admin.delete' => [
                'en' => 'Delete',
                'fr' => 'Supprimer'
            ],
            'admin.confirm_delete' => [
                'en' => 'Are you sure you want to delete this item?',
                'fr' => 'Êtes-vous sûr de vouloir supprimer cet élément?'
            ],
            'admin.manage' => [
                'en' => 'Manage projects',
                'fr' => 'Gérer les projets'
            ],
            'admin.total_projects' => [
                'en' => 'existing projects',
                'fr' => 'projets existants'
            ],
            'admin.current_projects' => [
                'en' => 'Current projects',
                'fr' => 'Projets en cours'
            ],
            'admin.past_projects' => [
                'en' => 'Past projects',
                'fr' => 'Projets passés'
            ],
            'admin.quick_actions' => [
                'en' => 'Quick actions',
                'fr' => 'Actions rapides'
            ],
            'admin.no_projects' => [
                'en' => 'No projects available.',
                'fr' => 'Aucun projet disponible.'
            ],
            'admin.language_settings' => [
                'en' => 'Language settings',
                'fr' => 'Paramètres de langue'
            ],
            'admin.language_settings_desc' => [
                'en' => 'So, you added that third language yet ? NO ? Well go fuck yourself',
                'fr' => 'Alors, il est ou ce 3ème langage ? Tu as peur ?'
            ],
            'admin.view_french' => [
                'en' => 'See the website in french',
                'fr' => 'THE FUCK ? COMMENT T\'A TROUVE CA ???'
            ],
            'admin.view_english' => [
                'en' => 'HOW THE ACTUAL FUCK MAN WTH ?!',
                'fr' => 'Voir le site en anglais'
            ],
            'admin.personal_info' => [
                'en' => 'Personal informations',
                'fr' => 'Informations personnelles'
            ],
            'admin.personal_info_desc' => [
                'en' => 'Forgot anything darling ?',
                'fr' => 'Pourquoi tu t\'es fait chier à implémenter ça déjà ?'
            ],
            'admin.user_profile' => [
                'en' => 'User Profile',
                'fr' => 'Profil de l\'utilisateur'
            ],
            'admin.edit_profile' => [
                'en' => 'Edit Profile',
                'fr' => 'Modifier le profil'
            ],
            'admin.view_profile' => [
                'en' => 'View Profile',
                'fr' => 'Voir le profil'
            ],
            'admin.edit_personal_info' => [
                'en' => 'Edit Personal Information',
                'fr' => 'Modifier les informations personnelles'
            ],
            'admin.view_site' => [
                'en' => 'View Site',
                'fr' => 'Voir le site'
            ],
            'admin.logout' => [
                'en' => 'Logout',
                'fr' => 'Déconnexion'
            ],
            'error.server_error' => [
                'en' => 'An unexpected error occurred. Please try again later.',
                'fr' => 'Une erreur inattendue est survenue. Veuillez réessayer plus tard.'
            ],
            'error.not_found' => [
                'en' => 'The requested page was not found.',
                'fr' => 'La page demandée est introuvable.'
            ],
            'error.unauthorized' => [
                'en' => 'You are not authorized to access this page.',
                'fr' => 'Vous n\'êtes pas autorisé à accéder à cette page.'
            ],
            'error.login_required' => [
                'en' => 'You must be logged in to access this page.',
                'fr' => 'Vous devez être connecté pour accéder à cette page.'
            ],
            'error.access_denied' => [
                'en' => 'Access denied.',
                'fr' => 'Accès refusé.'
            ],
            'error.500_message' => [
                'en' => 'An error occurred while processing your request. Nothing do be worried about... I hope.',
                'fr' => 'Une erreur est survenue lors du traitement de votre demande, sûremement une erreur dans le code. SWwgc2UgdHJvdXZlIHF1ZSBmYWlyZSB1biBzaXRlIGVudGnDqHJlbWVudCByw6lhY3RpZiBwb3VyIHBhcyBncmFuZCBjaG9zZSwgYmFoICDDp2EgcmVuZCBsZSBwcm9qZXQgcGx1cyBjb21wbGV4ZSwgZMOpc29sw6kgcG91ciBjZXR0ZSBlcnJldXIu'
            ],
            'error.404_message' => [
                'en' => 'The page you are looking for does not exist.',
                'fr' => 'La page que vous recherchez n\'existe pas. Vous foutez quoi ici ?'
            ],
            'error.401_message' => [
                'en' => 'You are not authorized to view this page.',
                'fr' => 'Vous n\'êtes pas autorisé à voir cette page.'
            ],
            'error.403_message' => [
                'en' => 'You do not have permission to access this page.',
                'fr' => 'Vous n\'avez pas la permission d\'accéder à cette page.'
            ],
            'error.403_title' => [
                'en' => '403 Forbidden',
                'fr' => '403 Interdit'
            ],
            'error.go_back' => [
                'en' => 'Go back to the previous page',
                'fr' => 'Retourner à la page précédente'
            ],
            'error.back_to_home' => [
                'en' => 'Back to home',
                'fr' => 'Retour à l\'accueil'
            ],
            'contact.success' => [
                'en' => 'Your message has been sent successfully.',
                'fr' => 'Votre message a été envoyé avec succès.'
            ],
            'contact.error' => [
                'en' => 'An error occurred while sending your message. Please try again later.',
                'fr' => 'Une erreur est survenue lors de l\'envoi de votre message. Veuillez réessayer plus tard.'
            ],
            'contact.form_heading' => [
                'en' => 'Contact Form',
                'fr' => 'Formulaire de contact'
            ],
            'contact.your_email' => [
                'en' => 'Your email',
                'fr' => 'Votre email'
            ],
            'contact.message' => [
                'en' => 'Your message',
                'fr' => 'Votre message'
            ],
            'contact.subject' => [
                'en' => 'Subject',
                'fr' => 'Sujet'
            ],
            'contact.subject_placeholder' => [
                'en' => 'Enter the subject of your message',
                'fr' => 'Entrez le sujet de votre message'
            ],
            'contact.message_placeholder' => [
                'en' => 'Enter your message here',
                'fr' => 'Entrez votre message ici'
            ],
            'contact.email_placeholder' => [
                'en' => 'Enter your email address',
                'fr' => 'Entrez votre adresse email'
            ],
            'contact.required_fields' => [
                'en' => 'This field is required',
                'fr' => 'Ce champ est requis'
            ],
            'contact.info_heading' => [
                'en' => 'Contact Information',
                'fr' => 'Informations de contact'
            ],
            'contact.info_desc' => [
                'en' => 'Feel free to reach out to me through the following channels:',
                'fr' => 'N\'hésitez pas à me contacter par les canaux suivants :'
            ],
            'login.forgot_password' => [
                'en' => 'Forgot your password?',
                'fr' => 'Mot de passe oublié ?'
            ],
            'login.remember_me' => [
                'en' => 'Remember me',
                'fr' => 'Se souvenir de moi'
            ],
            'admin.filter_by_status' => [
                'en' => 'Filter by status',
                'fr' => 'Filtrer par statut'
            ],
            'admin.search' => [
                'en' => 'Search',
                'fr' => 'Rechercher'
            ],
            'admin.no_results' => [
                'en' => 'No results found.',
                'fr' => 'Aucun résultat trouvé.'
            ],
            'admin.get_started_by_creating' => [
                'en' => 'Get started by creating your first project!',
                'fr' => 'Commencez par créer votre premier projet !'
            ],
            'admin.search_placeholder' => [
                'en' => 'Search projects...',
                'fr' => 'Rechercher des projets...'
            ],
            'admin.all_types' => [
                'en' => 'All types',
                'fr' => 'Tous les types'
            ],
            'admin.all_statuses' => [
                'en' => 'All statuses',
                'fr' => 'Tous les statuts'
            ],
            'admin.status_current' => [
                'en' => 'Current',
                'fr' => 'En cours'
            ],
            'admin.status_past' => [
                'en' => 'Past',
                'fr' => 'Passé'
            ],
            'admin.status_canceled' => [
                'en' => 'Canceled',
                'fr' => 'Annulé'
            ],
            'admin.status_pending' => [
                'en' => 'Pending',
                'fr' => 'En attente'
            ],
        ];
        
        $insertedCount = 0;
        foreach ($essentialTexts as $key => $translations) {
            // Insert text key
            $stmt = $pdo->prepare("INSERT INTO ui_texts (text_key) VALUES (?)");
            $stmt->execute([$key]);
            $textId = $pdo->lastInsertId();
            
            // Insert translations
            if ($englishId && isset($translations['en'])) {
                $stmt = $pdo->prepare("INSERT INTO ui_text_translations (ui_text_id, language_id, text) VALUES (?, ?, ?)");
                $stmt->execute([$textId, $englishId, $translations['en']]);
                $insertedCount++;
            }
            
            if ($frenchId && isset($translations['fr'])) {
                $stmt = $pdo->prepare("INSERT INTO ui_text_translations (ui_text_id, language_id, text) VALUES (?, ?, ?)");
                $stmt->execute([$textId, $frenchId, $translations['fr']]);
                $insertedCount++;
            }
        }
        
        echo "Installed $insertedCount UI text translations.\n";
    } else {
        echo "UI texts already exist ($uiTextCount texts).\n";
    }
    
    echo "\nDatabase initialization completed successfully!\n";
    echo "If you had issues with the website, they should now be resolved.\n";
    echo "You can now log in with your admin account and add/edit projects.\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}