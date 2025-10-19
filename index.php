<?php
/**
 * API Backend DONS - Point d'entrée principal
 * Système de gestion des dons avec intégration Barapay
 */

// Configuration CORS COMPLÈTE - DOIT ÊTRE EN PREMIER
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control, Pragma');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

// Gérer les requêtes OPTIONS (preflight) - CRITIQUE pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration de la base de données
$db_config = [
    'host' => $_ENV['DB_HOST'] ?? 'dons-database-nl3z8n',
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'database' => $_ENV['DB_DATABASE'] ?? 'Dons',
    'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? '9zctibtytwmv640w'
];

// Fonction de connexion à la base de données
function getDatabaseConnection($config) {
    try {
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

// Router simple
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Page d'accueil de l'API
if ($path === '/' || $path === '') {
    $response = [
        'status' => 'success',
        'message' => 'API Backend DONS - Système de gestion des dons',
        'version' => '1.0.0',
        'endpoints' => [
            'GET /' => 'Informations sur l\'API',
            'GET /health' => 'Vérification de la santé de l\'API',
            'GET /database' => 'Test de connexion à la base de données',
            'POST /api/payments' => 'Créer un nouveau paiement',
            'GET /api/payments' => 'Lister les paiements',
            'GET /api/payments/{id}' => 'Obtenir un paiement spécifique'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Endpoint de santé
if ($path === '/health') {
    $response = [
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'uptime' => 'Running',
        'database' => 'Connected'
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Test de connexion à la base de données
if ($path === '/database') {
    $pdo = getDatabaseConnection($db_config);
    
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = 'public'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response = [
                'status' => 'success',
                'message' => 'Connexion à la base de données réussie',
                'database' => $db_config['database'],
                'host' => $db_config['host'],
                'tables_count' => $result['table_count'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            $response = [
                'status' => 'error',
                'message' => 'Erreur lors de la requête à la base de données',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Impossible de se connecter à la base de données',
            'config' => $db_config,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// API des paiements
if (strpos($path, '/api/payments') === 0) {
    $pdo = getDatabaseConnection($db_config);
    
    if (!$pdo) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Impossible de se connecter à la base de données'
        ]);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if ($path === '/api/payments') {
                // Lister tous les paiements
                try {
                    $stmt = $pdo->query("SELECT * FROM payments ORDER BY created_at DESC LIMIT 10");
                    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        'status' => 'success',
                        'data' => $payments,
                        'count' => count($payments)
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Erreur lors de la récupération des paiements',
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                // Paiement spécifique
                $payment_id = basename($path);
                try {
                    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
                    $stmt->execute([$payment_id]);
                    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($payment) {
                        echo json_encode([
                            'status' => 'success',
                            'data' => $payment
                        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    } else {
                        http_response_code(404);
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Paiement non trouvé'
                        ]);
                    }
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Erreur lors de la récupération du paiement',
                        'error' => $e->getMessage()
                    ]);
                }
            }
            break;
            
        case 'POST':
            // Créer un nouveau paiement
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['amount'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Données manquantes. Montant requis.'
                ]);
                exit;
            }
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO payments (amount, currency, payment_method, status, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $input['amount'],
                    $input['currency'] ?? 'XOF',
                    $input['payment_method'] ?? 'barapay',
                    $input['status'] ?? 'pending'
                ]);
                
                $payment_id = $pdo->lastInsertId();
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Paiement créé avec succès',
                    'payment_id' => $payment_id
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Erreur lors de la création du paiement',
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'status' => 'error',
                'message' => 'Méthode non autorisée'
            ]);
    }
    exit;
}

// Route non trouvée
http_response_code(404);
echo json_encode([
    'status' => 'error',
    'message' => 'Endpoint non trouvé',
    'path' => $path,
    'available_endpoints' => [
        'GET /' => 'Informations sur l\'API',
        'GET /health' => 'Vérification de la santé',
        'GET /database' => 'Test de base de données',
        'GET /api/payments' => 'Lister les paiements',
        'POST /api/payments' => 'Créer un paiement'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
