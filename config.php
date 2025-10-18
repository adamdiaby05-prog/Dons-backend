<?php

/**
 * Configuration Backend - DONS
 * Configuration pour l'API backend et l'intégration Barapay
 */

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'dons_database');
define('DB_USER', 'postgres');
define('DB_PASS', '0000');
define('DB_PORT', '5432');

// Configuration Barapay
define('BARAPAY_CLIENT_ID', 'wjb7lzQVialbcwMNTPD1IojrRzPIIl');
define('BARAPAY_CLIENT_SECRET', 'eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1');
define('BARAPAY_BASE_URL', 'https://barapay.net');

// Configuration des URLs
define('FRONTEND_URL', 'http://localhost:3000');
define('BACKEND_URL', 'http://localhost:8001');

// URLs de redirection
define('SUCCESS_URL', FRONTEND_URL . '/payment-success');
define('CANCEL_URL', FRONTEND_URL . '/payment-cancel');
define('CALLBACK_URL', BACKEND_URL . '/barapay_callback.php');

// Configuration des logs
define('LOG_DIR', __DIR__ . '/logs');
define('API_LOG_FILE', LOG_DIR . '/api.log');
define('BARAPAY_LOG_FILE', LOG_DIR . '/barapay.log');
define('CALLBACK_LOG_FILE', LOG_DIR . '/callbacks.log');

// Créer le dossier logs s'il n'existe pas
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0777, true);
}

// Configuration CORS
function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Fonction pour logger
function logMessage($message, $data = [], $logFile = null) {
    if (!$logFile) {
        $logFile = API_LOG_FILE;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] $message";
    
    if (!empty($data)) {
        $formattedMessage .= " - " . json_encode($data);
    }
    
    $formattedMessage .= "\n";
    
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

// Fonction pour obtenir la connexion à la base de données
function getDatabaseConnection() {
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        logMessage("Erreur de connexion à la base de données", ['error' => $e->getMessage()]);
        throw new Exception("Erreur de connexion à la base de données");
    }
}

// Configuration des environnements
define('ENVIRONMENT', 'development'); // development, production

if (ENVIRONMENT === 'production') {
    // Désactiver l'affichage des erreurs en production
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    // Activer l'affichage des erreurs en développement
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
