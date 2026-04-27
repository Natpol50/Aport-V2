<?php

/**
 * Migration Script: Add missing UI text keys
 */

define('ROOT_DIR', dirname(__DIR__));
require ROOT_DIR . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
$dotenv->load();

$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbUser = $_ENV['DB_USER'] ?? 'portfolio_user';
$dbPassword = $_ENV['DB_PASSWORD'] ?? '';
$dbName = $_ENV['DB_NAME'] ?? 'portfolio_db';
$dbPort = $_ENV['DB_PORT'] ?? '3306';

try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $missingKeys = [
        'admin.manage_skills' => [
            'en' => 'Manage Skills',
            'fr' => 'Gérer les compétences'
        ]
    ];

    // Get language IDs
    $stmt = $pdo->query("SELECT id, code FROM languages");
    $langs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $reverseLangs = array_flip($langs); // code => id

    foreach ($missingKeys as $key => $translations) {
        // Check if key exists
        $stmt = $pdo->prepare("SELECT id FROM ui_texts WHERE text_key = ?");
        $stmt->execute([$key]);
        $textId = $stmt->fetchColumn();

        if (!$textId) {
            $stmt = $pdo->prepare("INSERT INTO ui_texts (text_key) VALUES (?)");
            $stmt->execute([$key]);
            $textId = $pdo->lastInsertId();
            echo "Added key: $key\n";
        }

        foreach ($translations as $langCode => $text) {
            if (isset($reverseLangs[$langCode])) {
                $langId = $reverseLangs[$langCode];
                // Check if translation exists
                $stmt = $pdo->prepare("SELECT id FROM ui_text_translations WHERE ui_text_id = ? AND language_id = ?");
                $stmt->execute([$textId, $langId]);
                if (!$stmt->fetchColumn()) {
                    $stmt = $pdo->prepare("INSERT INTO ui_text_translations (ui_text_id, language_id, text) VALUES (?, ?, ?)");
                    $stmt->execute([$textId, $langId, $text]);
                    echo "  Added $langCode translation for $key\n";
                }
            }
        }
    }

    echo "Migration complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
