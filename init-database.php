<?php
/**
 * Script d'initialisation de la base de données DONS
 * Exécute le script SQL pour créer toutes les tables
 */

header('Content-Type: application/json; charset=utf-8');

// Configuration de la base de données
$db_config = [
    'host' => $_ENV['DB_HOST'] ?? 'dons-database-nl3z8n',
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'database' => $_ENV['DB_DATABASE'] ?? 'Dons',
    'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? '9zctibtytwmv640w'
];

try {
    // Connexion à la base de données
    $dsn = "pgsql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lire le script SQL
    $sql_file = __DIR__ . '/database/init.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("Fichier SQL non trouvé : $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    if (!$sql_content) {
        throw new Exception("Impossible de lire le fichier SQL");
    }
    
    // Diviser le script en requêtes individuelles
    $queries = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($query) {
            return !empty($query) && !preg_match('/^--/', $query);
        }
    );
    
    $executed_queries = 0;
    $errors = [];
    
    foreach ($queries as $query) {
        if (empty(trim($query))) continue;
        
        try {
            $pdo->exec($query);
            $executed_queries++;
        } catch (PDOException $e) {
            // Ignorer les erreurs de tables déjà existantes
            if (strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = [
                    'query' => substr($query, 0, 100) . '...',
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    // Vérifier les tables créées
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Compter les enregistrements dans chaque table
    $table_counts = [];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $table_counts[$table] = $result['count'];
        } catch (Exception $e) {
            $table_counts[$table] = 'Error: ' . $e->getMessage();
        }
    }
    
    $response = [
        'status' => 'success',
        'message' => 'Base de données initialisée avec succès',
        'database' => $db_config['database'],
        'host' => $db_config['host'],
        'executed_queries' => $executed_queries,
        'tables_created' => count($tables),
        'tables' => $tables,
        'table_counts' => $table_counts,
        'errors' => $errors,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur lors de l\'initialisation de la base de données',
        'error' => $e->getMessage(),
        'config' => $db_config,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
