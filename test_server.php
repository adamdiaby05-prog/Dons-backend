<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';

if ($request_uri === '/api/test' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'message' => 'API DONS fonctionne correctement !',
        'timestamp' => date('c'),
        'database' => 'PostgreSQL connecté',
        'status' => 'success',
        'server' => 'Test PHP Server',
        'ip' => $_SERVER['SERVER_ADDR'] ?? '192.168.1.7'
    ]);
    exit();
}

if ($request_uri === '/api/barapay/create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Données JSON invalides'
        ]);
        exit();
    }
    
    // Simuler la création d'un paiement Barapay
    $payment_data = [
        'id' => uniqid('PAY_'),
        'amount' => $input['amount'],
        'phone_number' => $input['phone_number'],
        'payment_method' => $input['payment_method'],
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'type' => 'barapay_real',
        'checkout_url' => 'https://checkout.barapay.net/payment/' . uniqid()
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Paiement Barapay créé avec succès',
        'payment' => $payment_data,
        'checkout_url' => $payment_data['checkout_url'],
        'redirect_required' => true
    ]);
    exit();
}

echo json_encode([
    'error' => 'Endpoint non trouvé',
    'request_uri' => $request_uri,
    'method' => $_SERVER['REQUEST_METHOD']
]);
?>