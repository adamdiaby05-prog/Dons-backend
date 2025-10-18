<?php
// API simple pour sauvegarder les paiements
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

// Endpoint pour sauvegarder un paiement
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log des données reçues pour debug
    error_log('Données de paiement reçues: ' . json_encode($input));
    
    // Validation des données
    if (empty($input['amount']) || empty($input['phone_number'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Montant et numéro de téléphone requis',
            'error' => 'Données manquantes',
            'received_data' => $input
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
    
    // Simuler la sauvegarde (dans un vrai projet, vous utiliseriez une base de données)
    $response = [
        'success' => true,
        'data' => $payment_data,
        'message' => 'Paiement sauvegardé avec succès',
        'reference' => $payment_data['id']
    ];
    
    echo json_encode($response);
    exit();
}

// Endpoint pour récupérer les paiements
if ($method === 'GET') {
    // Simuler des données de paiement
    $payments = [
        [
            'id' => 'PAY_001',
            'amount' => 5000,
            'phone_number' => '0701234567',
            'network' => 'MTN',
            'status' => 'completed',
            'timestamp' => date('c', strtotime('-1 hour'))
        ],
        [
            'id' => 'PAY_002',
            'amount' => 3000,
            'phone_number' => '0501234567',
            'network' => 'Orange',
            'status' => 'pending',
            'timestamp' => date('c', strtotime('-30 minutes'))
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $payments,
        'message' => 'Paiements récupérés avec succès'
    ]);
    exit();
}

// Endpoint par défaut
http_response_code(405);
echo json_encode([
    'success' => false,
    'message' => 'Méthode non autorisée',
    'error' => 'Seules les méthodes GET et POST sont autorisées'
]);
?>
