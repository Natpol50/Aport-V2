<?php
/**
 * Database Connection Diagnostic Script
 * 
 * This script tests the database connection and reports any issues.
 * Use this to diagnose connection problems without affecting the main application.
 * 
 * Usage: php db-diagnostic.php
 */

// Define the application's root directory
define('ROOT_DIR', dirname(__DIR__));

// Check if .env file exists
if (!file_exists(ROOT_DIR . '/.env')) {
    echo "ERROR: .env file not found. Please create a .env file with your database configuration.\n";
    echo "Example .env file content:\n";
    echo "DB_HOST=localhost\n";
    echo "DB_USER=username\n";
    echo "DB_PASSWORD=password\n";
    echo "DB_NAME=portfolio_db\n";
    echo "DB_PORT=3306\n";
    exit(1);
}

// Require the Composer autoloader
if (!file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    echo "ERROR: Composer autoloader not found. Please run 'composer install' first.\n";
    exit(1);
}

require ROOT_DIR . '/vendor/autoload.php';

// Load environment variables
try {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
    $dotenv->load();
} catch (Exception $e) {
    echo "ERROR: Failed to load .env file: " . $e->getMessage() . "\n";
    exit(1);
}

// Database connection parameters
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbUser = $_ENV['DB_USER'] ?? 'portfolio_user';
$dbPassword = $_ENV['DB_PASSWORD'] ?? '';
$dbName = $_ENV['DB_NAME'] ?? 'portfolio_db';
$dbPort = $_ENV['DB_PORT'] ?? '3306';

echo "Database Connection Diagnostic\n";
echo "=============================\n\n";

echo "Connection details:\n";
echo "- Host: $dbHost\n";
echo "- Port: $dbPort\n";
echo "- User: $dbUser\n";
echo "- Password: " . (empty($dbPassword) ? "[empty]" : "[set]") . "\n";
echo "- Database: $dbName\n\n";

// Test database server connection
echo "Step 1: Testing connection to database server...\n";
try {
    $dsn = "mysql:host=$dbHost;port=$dbPort";
    $startTime = microtime(true);
    $pdo = new PDO($dsn, $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $endTime = microtime(true);
    $connectionTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "SUCCESS: Connected to database server (took $connectionTime ms)\n";
} catch (PDOException $e) {
    echo "ERROR: Failed to connect to database server.\n";
    echo "Error message: " . $e->getMessage() . "\n";
    echo "\nPossible solutions:\n";
    echo "1. Check if MySQL server is running\n";
    echo "2. Verify host and port are correct\n";
    echo "3. Check if username and password are correct\n";
    echo "4. Ensure the user has proper connection privileges\n";
    exit(1);
}

// Test database existence
echo "\nStep 2: Checking if database '$dbName' exists...\n";
try {
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    $databaseExists = $stmt->rowCount() > 0;
    
    if ($databaseExists) {
        echo "SUCCESS: Database '$dbName' exists\n";
    } else {
        echo "WARNING: Database '$dbName' does not exist\n";
        echo "You need to create it or run the db-initializer.php script first.\n";
        exit(1);
    }
} catch (PDOException $e) {
    echo "ERROR: Failed to check if database exists.\n";
    echo "Error message: " . $e->getMessage() . "\n";
    exit(1);
}

// Connect to the specific database
echo "\nStep 3: Connecting to database '$dbName'...\n";
try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName";
    $startTime = microtime(true);
    $pdo = new PDO($dsn, $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $endTime = microtime(true);
    $connectionTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "SUCCESS: Connected to database '$dbName' (took $connectionTime ms)\n";
} catch (PDOException $e) {
    echo "ERROR: Failed to connect to database '$dbName'.\n";
    echo "Error message: " . $e->getMessage() . "\n";
    echo "\nPossible solutions:\n";
    echo "1. Check if database name is correct\n";
    echo "2. Ensure user has access to this database\n";
    exit(1);
}

// Check required tables
echo "\nStep 4: Checking required tables...\n";
$requiredTables = [
    'users', 'languages', 'projects', 'project_translations', 
    'personal_info', 'personal_info_translations', 'ui_texts', 'ui_text_translations'
];

try {
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missingTables = array_diff($requiredTables, $existingTables);
    
    if (empty($missingTables)) {
        echo "SUCCESS: All required tables exist\n";
        
        // Check if tables have data
        $tableData = [];
        foreach ($requiredTables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = (int)$stmt->fetchColumn();
            $tableData[$table] = $count;
        }
        
        echo "\nTable record counts:\n";
        foreach ($tableData as $table => $count) {
            echo "- $table: $count " . ($count === 0 ? "(EMPTY)" : "records") . "\n";
        }
        
        // Check for empty critical tables
        $emptyCriticalTables = array_filter($tableData, function($count, $table) {
            return $count === 0 && in_array($table, ['languages', 'users']);
        }, ARRAY_FILTER_USE_BOTH);
        
        if (!empty($emptyCriticalTables)) {
            echo "\nWARNING: Some critical tables are empty. Run db-initializer.php to populate them.\n";
        }
    } else {
        echo "WARNING: Missing required tables: " . implode(', ', $missingTables) . "\n";
        echo "Run db-initializer.php to create these tables.\n";
    }
} catch (PDOException $e) {
    echo "ERROR: Failed to check tables.\n";
    echo "Error message: " . $e->getMessage() . "\n";
    exit(1);
}

// Test a sample query
echo "\nStep 5: Testing a sample query (languages)...\n";
try {
    $startTime = microtime(true);
    $stmt = $pdo->query("SELECT * FROM languages LIMIT 5");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $endTime = microtime(true);
    $queryTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "SUCCESS: Query executed successfully (took $queryTime ms)\n";
    echo "Retrieved " . count($results) . " languages\n";
    
    if (!empty($results)) {
        echo "\nLanguages in system:\n";
        foreach ($results as $language) {
            echo "- " . $language['code'] . ": " . $language['name'] . 
                 ($language['is_active'] ? " (active)" : " (inactive)") . "\n";
        }
    } else {
        echo "WARNING: No languages found in the database.\n";
        echo "Run db-initializer.php to populate initial data.\n";
    }
} catch (PDOException $e) {
    echo "ERROR: Failed to execute sample query.\n";
    echo "Error message: " . $e->getMessage() . "\n";
    exit(1);
}

// Check foreign key constraints
echo "\nStep 6: Checking database engine and foreign key support...\n";
try {
    $stmt = $pdo->query("SHOW TABLE STATUS");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $nonInnoDBTables = [];
    foreach ($tables as $table) {
        if ($table['Engine'] !== 'InnoDB') {
            $nonInnoDBTables[] = $table['Name'];
        }
    }
    
    if (empty($nonInnoDBTables)) {
        echo "SUCCESS: All tables use InnoDB engine (supports foreign keys)\n";
    } else {
        echo "WARNING: These tables don't use InnoDB engine: " . implode(', ', $nonInnoDBTables) . "\n";
        echo "Foreign key constraints may not work correctly. Consider converting them to InnoDB.\n";
    }
} catch (PDOException $e) {
    echo "ERROR: Failed to check table engines.\n";
    echo "Error message: " . $e->getMessage() . "\n";
}

// Check character set and collation
echo "\nStep 7: Checking character set and collation...\n";
try {
    $stmt = $pdo->query("SELECT @@character_set_database, @@collation_database");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Database character set: " . $result['@@character_set_database'] . "\n";
    echo "Database collation: " . $result['@@collation_database'] . "\n";
    
    if ($result['@@character_set_database'] !== 'utf8mb4') {
        echo "WARNING: Database does not use utf8mb4 character set. This may cause issues with special characters.\n";
    }
} catch (PDOException $e) {
    echo "ERROR: Failed to check character set and collation.\n";
    echo "Error message: " . $e->getMessage() . "\n";
}

echo "\nDiagnostic completed successfully!\n";
echo "If you encountered any warnings or errors, please address them before using the application.\n";
echo "Run the db-initializer.php script to fix most common database issues.\n";