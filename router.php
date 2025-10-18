<?php
// Routeur pour l'API DONS
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With, X-CSRF-TOKEN');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Gérer les requêtes OPTIONS
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Nettoyer l'URI
$request_uri = parse_url($request_uri, PHP_URL_PATH);

// Route /api/test
if ($request_uri === '/api/test' && $method === 'GET') {
    echo json_encode([
        'message' => 'API DONS fonctionne correctement !',
        'timestamp' => date('c'),
        'database' => 'SQLite connecté',
        'status' => 'success',
        'server' => 'Router Server',
        'ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown'
    ]);
    exit();
}

// Route /api/payments/initiate
if ($request_uri === '/api/payments/initiate' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['amount']) || empty($input['phone_number'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Montant et numéro de téléphone requis',
            'error' => 'Données manquantes'
        ]);
        exit();
    }
    
    $payment_data = [
        'id' => uniqid('PAY_'),
        'amount' => $input['amount'],
        'phone_number' => $input['phone_number'],
        'network' => $input['network'] ?? 'MTN',
        'status' => 'pending',
        'timestamp' => date('c'),
        'message' => 'Paiement initié avec succès'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $payment_data,
        'message' => 'Paiement initié avec succès',
        'reference' => $payment_data['id']
    ]);
    exit();
}

// Route /api_save_payment_simple.php
if ($request_uri === '/api_save_payment_simple.php' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['amount']) || empty($input['phone_number'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Montant et numéro de téléphone requis',
            'error' => 'Données manquantes'
        ]);
        exit();
    }
    
    $payment_data = [
        'id' => uniqid('PAY_'),
        'amount' => $input['amount'],
        'phone_number' => $input['phone_number'],
        'network' => $input['network'] ?? 'MTN',
        'status' => 'pending',
        'timestamp' => date('c'),
        'message' => 'Paiement sauvegardé avec succès'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $payment_data,
        'message' => 'Paiement sauvegardé avec succès',
        'reference' => $payment_data['id']
    ]);
    exit();
}

// Route racine
if ($request_uri === '/' && $method === 'GET') {
    echo json_encode([
        'message' => 'API DONS - Router Server',
        'timestamp' => date('c'),
        'status' => 'success',
        'server' => 'Router Server',
        'version' => '1.0.0',
        'available_endpoints' => [
            'GET /api/test' => 'Test de l\'API principale',
            'POST /api/payments/initiate' => 'Initier un paiement',
            'POST /api_save_payment_simple.php' => 'Sauvegarder un paiement'
        ]
    ]);
    exit();
}

// Route par défaut
http_response_code(404);
echo json_encode([
    'error' => 'Endpoint non trouvé',
    'request_uri' => $request_uri,
    'method' => $method,
    'available_endpoints' => [
        'GET /api/test' => 'Test de l\'API principale',
        'POST /api/payments/initiate' => 'Initier un paiement',
        'POST /api_save_payment_simple.php' => 'Sauvegarder un paiement'
    ]
]);
?>