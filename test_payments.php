<?php
// Test simple pour l'endpoint payments
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if ($request_uri === '/api/payments' && $method === 'GET') {
    $payments = [
        [
            'id' => 1,
            'payment_reference' => 'PAY001',
            'amount' => 5000,
            'status' => 'success',
            'payment_method' => 'Mobile Money',
            'created_at' => '2025-10-15T10:30:00Z',
            'user_name' => 'Jean Dupont'
        ]
    ];
    
    echo json_encode([
        'success' => 1,
        'data' => $payments
    ]);
} else {
    echo json_encode(['error' => 'Endpoint non trouvé', 'uri' => $request_uri]);
}
?>
