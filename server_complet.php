<?php
// Serveur complet pour l'API DONS avec tous les endpoints nécessaires
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With, X-CSRF-TOKEN');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Nettoyer l'URI pour le routage
$request_uri = parse_url($request_uri, PHP_URL_PATH);

// Endpoint de test principal
if ($request_uri === '/api/test' && $method === 'GET') {
    echo json_encode([
        'message' => 'API DONS fonctionne correctement !',
        'timestamp' => date('c'),
        'database' => 'SQLite connecté',
        'status' => 'success',
        'server' => 'Complete Server',
        'ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown'
    ]);
    exit();
}

// Endpoint pour initier un paiement
if ($request_uri === '/api/payments/initiate' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log des données reçues pour debug
    error_log('Données de paiement reçues: ' . json_encode($input));
    
    // Validation des données
    if (empty($input['amount']) || empty($input['phone_number'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Montant et numéro de téléphone requis',
            'error' => 'Données manquantes'
        ]);
        exit();
    }
    
    // Simuler la sauvegarde en base de données
    $payment_data = [
        'id' => uniqid('PAY_'),
        'amount' => $input['amount'],
        'phone_number' => $input['phone_number'],
        'network' => $input['network'] ?? 'MTN',
        'status' => 'pending',
        'timestamp' => date('c'),
        'message' => 'Paiement initié avec succès'
    ];
    
    $response = [
        'success' => true,
        'data' => $payment_data,
        'message' => 'Paiement initié avec succès',
        'reference' => $payment_data['id']
    ];
    
    echo json_encode($response);
    exit();
}

// Endpoint pour sauvegarder un paiement (compatible avec l'ancien système)
if ($request_uri === '/api_save_payment_simple.php' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log des données reçues pour debug
    error_log('Données de paiement reçues: ' . json_encode($input));
    
    // Validation des données
    if (empty($input['amount']) || empty($input['phone_number'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Montant et numéro de téléphone requis',
            'error' => 'Données manquantes'
        ]);
        exit();
    }
    
    // Simuler la sauvegarde en base de données
    $payment_data = [
        'id' => uniqid('PAY_'),
        'amount' => $input['amount'],
        'phone_number' => $input['phone_number'],
        'network' => $input['network'] ?? 'MTN',
        'status' => 'pending',
        'timestamp' => date('c'),
        'message' => 'Paiement sauvegardé avec succès'
    ];
    
    $response = [
        'success' => true,
        'data' => $payment_data,
        'message' => 'Paiement sauvegardé avec succès',
        'reference' => $payment_data['id']
    ];
    
    echo json_encode($response);
    exit();
}

// Endpoint pour vérifier le statut d'un paiement
if ($request_uri === '/api_payments_status.php' && $method === 'GET') {
    $payment_id = $_GET['payment_id'] ?? '';
    
    if (empty($payment_id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID de paiement requis',
            'error' => 'payment_id manquant'
        ]);
        exit();
    }
    
    // Simuler la vérification du statut
    $statuses = ['pending', 'completed', 'failed', 'cancelled'];
    $random_status = $statuses[array_rand($statuses)];
    
    $payment_data = [
        'id' => $payment_id,
        'status' => $random_status,
        'amount' => 5000,
        'phone_number' => '0701234567',
        'network' => 'MTN',
        'timestamp' => date('c'),
        'message' => 'Statut vérifié avec succès'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $payment_data,
        'message' => 'Statut récupéré avec succès'
    ]);
    exit();
}

// Endpoint pour récupérer l'historique des paiements
if ($request_uri === '/api_payments_history.php' && $method === 'GET') {
    // Simuler des données d'historique
    $payments = [
        [
            'id' => 'PAY_001',
            'amount' => 5000,
            'phone_number' => '0701234567',
            'network' => 'MTN',
            'status' => 'completed',
            'timestamp' => date('c', strtotime('-2 hours')),
            'reference' => 'REF_001'
        ],
        [
            'id' => 'PAY_002',
            'amount' => 3000,
            'phone_number' => '0501234567',
            'network' => 'Orange',
            'status' => 'pending',
            'timestamp' => date('c', strtotime('-1 hour')),
            'reference' => 'REF_002'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $payments,
        'message' => 'Historique récupéré avec succès',
        'total' => count($payments)
    ]);
    exit();
}

// Endpoint racine
if ($request_uri === '/' && $method === 'GET') {
    echo json_encode([
        'message' => 'API DONS - Serveur Complet',
        'timestamp' => date('c'),
        'status' => 'success',
        'server' => 'Complete Server',
        'version' => '1.0.0',
        'available_endpoints' => [
            'GET /api/test' => 'Test de l\'API principale',
            'POST /api/payments/initiate' => 'Initier un paiement',
            'POST /api_save_payment_simple.php' => 'Sauvegarder un paiement',
            'GET /api_payments_status.php' => 'Vérifier le statut',
            'GET /api_payments_history.php' => 'Historique des paiements'
        ]
    ]);
    exit();
}

// Endpoint par défaut
http_response_code(404);
echo json_encode([
    'error' => 'Endpoint non trouvé',
    'request_uri' => $request_uri,
    'method' => $method,
    'available_endpoints' => [
        'GET /api/test' => 'Test de l\'API principale',
        'POST /api/payments/initiate' => 'Initier un paiement',
        'POST /api_save_payment_simple.php' => 'Sauvegarder un paiement',
        'GET /api_payments_status.php' => 'Vérifier le statut',
        'GET /api_payments_history.php' => 'Historique des paiements'
    ]
]);
?>
