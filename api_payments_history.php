<?php
// API pour récupérer l'historique des paiements
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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Endpoint pour récupérer l'historique des paiements
if ($method === 'GET') {
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
        ],
        [
            'id' => 'PAY_003',
            'amount' => 7500,
            'phone_number' => '0709876543',
            'network' => 'Wave',
            'status' => 'failed',
            'timestamp' => date('c', strtotime('-30 minutes')),
            'reference' => 'REF_003'
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

// Endpoint par défaut
http_response_code(405);
echo json_encode([
    'success' => false,
    'message' => 'Méthode non autorisée',
    'error' => 'Seule la méthode GET est autorisée'
]);
?>
