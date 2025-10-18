<?php
// API pour vérifier le statut des paiements
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

// Endpoint pour vérifier le statut d'un paiement
if ($method === 'GET') {
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

// Endpoint par défaut
http_response_code(405);
echo json_encode([
    'success' => false,
    'message' => 'Méthode non autorisée',
    'error' => 'Seule la méthode GET est autorisée'
]);
?>
