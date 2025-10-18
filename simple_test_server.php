<?php
// Serveur de test ultra-simple pour l'API DONS
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
        'server' => 'Simple Test Server',
        'ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown'
    ]);
    exit();
}

// Endpoint de test des paiements
if ($request_uri === '/api/payments/test' && $method === 'GET') {
    echo json_encode([
        'message' => 'API Paiements fonctionne correctement !',
        'timestamp' => date('c'),
        'endpoints' => [
            'POST /api/payments/initiate' => 'Initier un nouveau paiement',
            'GET /api/payments' => 'Liste des paiements'
        ],
        'status' => 'success',
        'server' => 'Simple Test Server'
    ]);
    exit();
}

// Endpoint d'initiation de paiement
if ($request_uri === '/api/payments/initiate' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log des données reçues pour debug
    error_log('Données reçues: ' . json_encode($input));
    
    // Simuler le stockage en base de données
    $payment_data = [
        'id' => uniqid('PAY_'),
        'amount' => $input['amount'] ?? 0,
        'phone_number' => $input['phone_number'] ?? '',
        'network' => $input['network'] ?? '',
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

// Endpoint racine
if ($request_uri === '/' && $method === 'GET') {
    echo json_encode([
        'message' => 'API DONS - Serveur Simple',
        'timestamp' => date('c'),
        'status' => 'success',
        'server' => 'Simple Test Server',
        'version' => '1.0.0',
        'available_endpoints' => [
            'GET /api/test' => 'Test de l\'API principale',
            'GET /api/payments/test' => 'Test de l\'API des paiements',
            'POST /api/payments/initiate' => 'Initier un paiement'
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
        'GET /api/payments/test' => 'Test de l\'API des paiements',
        'POST /api/payments/initiate' => 'Initier un paiement'
    ]
]);
?>
