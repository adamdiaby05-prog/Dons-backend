<?php
// API Barapay pour les paiements réels
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

// Configuration Barapay
define('BARAPAY_CLIENT_ID', 'wjb7lzQVialbcwMNTPD1IojrRzPIIl');
define('BARAPAY_CLIENT_SECRET', 'eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1');
define('BARAPAY_BASE_URL', 'https://api.barapay.ci'); // URL de l'API Barapay

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Fonction pour faire des requêtes à l'API Barapay
function makeBarapayRequest($endpoint, $data = null, $method = 'GET') {
    $url = BARAPAY_BASE_URL . $endpoint;
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . BARAPAY_CLIENT_SECRET,
        'X-Client-ID: ' . BARAPAY_CLIENT_ID
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'Erreur cURL: ' . $error
        ];
    }
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'data' => json_decode($response, true),
        'raw_response' => $response
    ];
}

// Endpoint pour initier un paiement Barapay
if ($request_uri === '/api/barapay/initiate' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Données JSON invalides'
        ]);
        exit();
    }
    
    // Valider les données requises
    $required_fields = ['amount', 'phone_number', 'payment_method'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Champ requis manquant: $field"
            ]);
            exit();
        }
    }
    
    // Préparer les données pour Barapay
    $barapay_data = [
        'amount' => (int)$input['amount'],
        'phone_number' => $input['phone_number'],
        'payment_method' => $input['payment_method'],
        'description' => $input['description'] ?? 'Paiement DONS',
        'reference' => 'DONS-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
        'callback_url' => 'http://localhost:8000/api/barapay/callback'
    ];
    
    // Faire la requête à Barapay
    $response = makeBarapayRequest('/payments/initiate', $barapay_data, 'POST');
    
    if ($response['success']) {
        // Sauvegarder le paiement en attente
        $payment_data = [
            'id' => uniqid('PAY_'),
            'barapay_reference' => $barapay_data['reference'],
            'amount' => $input['amount'],
            'phone_number' => $input['phone_number'],
            'payment_method' => $input['payment_method'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'barapay_response' => $response['data']
        ];
        
        // Sauvegarder dans le fichier
        $payments_file = __DIR__ . '/payments.json';
        $payments = file_exists($payments_file) ? json_decode(file_get_contents($payments_file), true) : [];
        $payments[] = $payment_data;
        file_put_contents($payments_file, json_encode($payments, JSON_PRETTY_PRINT));
        
        echo json_encode([
            'success' => true,
            'message' => 'Paiement initié avec Barapay',
            'payment' => $payment_data,
            'barapay_response' => $response['data']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de l\'initiation du paiement Barapay',
            'details' => $response
        ]);
    }
    exit();
}

// Endpoint pour vérifier le statut d'un paiement
if ($request_uri === '/api/barapay/status' && $method === 'GET') {
    $reference = $_GET['reference'] ?? '';
    
    if (empty($reference)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Référence de paiement requise'
        ]);
        exit();
    }
    
    // Vérifier le statut avec Barapay
    $response = makeBarapayRequest('/payments/status/' . $reference);
    
    echo json_encode([
        'success' => $response['success'],
        'reference' => $reference,
        'status' => $response['data'] ?? null,
        'details' => $response
    ]);
    exit();
}

// Endpoint pour le callback Barapay (webhook)
if ($request_uri === '/api/barapay/callback' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log du callback
    error_log('Barapay Callback: ' . json_encode($input));
    
    // Mettre à jour le statut du paiement
    if (isset($input['reference']) && isset($input['status'])) {
        $payments_file = __DIR__ . '/payments.json';
        if (file_exists($payments_file)) {
            $payments = json_decode(file_get_contents($payments_file), true);
            
            foreach ($payments as &$payment) {
                if ($payment['barapay_reference'] === $input['reference']) {
                    $payment['status'] = $input['status'];
                    $payment['updated_at'] = date('Y-m-d H:i:s');
                    $payment['barapay_callback'] = $input;
                    break;
                }
            }
            
            file_put_contents($payments_file, json_encode($payments, JSON_PRETTY_PRINT));
        }
    }
    
    echo json_encode(['success' => true]);
    exit();
}

// Endpoint par défaut
http_response_code(404);
echo json_encode([
    'error' => 'Endpoint Barapay non trouvé',
    'request_uri' => $request_uri,
    'method' => $method,
    'available_endpoints' => [
        'POST /api/barapay/initiate' => 'Initier un paiement Barapay',
        'GET /api/barapay/status' => 'Vérifier le statut d\'un paiement',
        'POST /api/barapay/callback' => 'Webhook Barapay'
    ]
]);
?>

