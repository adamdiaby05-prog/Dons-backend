<?php
// API Barapay réelle selon la documentation officielle
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

// Inclure les classes Barapay
require_once 'barapay_sdk/Payer.php';
require_once 'barapay_sdk/Amount.php';
require_once 'barapay_sdk/Transaction.php';
require_once 'barapay_sdk/RedirectUrls.php';
require_once 'barapay_sdk/Payment.php';

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Configuration Barapay
define('BARAPAY_CLIENT_ID', 'wjb7lzQVialbcwMNTPD1IojrRzPIIl');
define('BARAPAY_CLIENT_SECRET', 'eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1');

// Endpoint pour créer un paiement Barapay
if ($request_uri === '/api/barapay/create' && $method === 'POST') {
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
    
    try {
        // Créer les objets Barapay selon la documentation
        $payer = new Payer();
        $payer->setPaymentMethod('PayMoney'); // Nom du système selon la doc
        
        $amount = new Amount();
        $amount->setTotal($input['amount'])->setCurrency('XOF'); // Devise CFA
        
        $transaction = new Transaction();
        $transaction->setAmount($amount);
        
        $urls = new RedirectUrls();
        $urls->setSuccessUrl('http://localhost:3000/#/payment/success')
             ->setCancelUrl('http://localhost:3000/#/payment/cancel');
        
        $payment = new Payment();
        $payment->setCredentials([
            'client_id' => BARAPAY_CLIENT_ID,
            'client_secret' => BARAPAY_CLIENT_SECRET
        ])->setRedirectUrls($urls)
          ->setPayer($payer)
          ->setTransaction($transaction);
        
        // Créer le paiement
        $payment->create();
        
        // Sauvegarder le paiement en attente
        $payment_data = [
            'id' => uniqid('PAY_'),
            'amount' => $input['amount'],
            'phone_number' => $input['phone_number'],
            'payment_method' => $input['payment_method'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'type' => 'barapay_real',
            'checkout_url' => $payment->getApprovedUrl()
        ];
        
        // Sauvegarder dans le fichier
        $payments_file = __DIR__ . '/payments.json';
        $payments = file_exists($payments_file) ? json_decode(file_get_contents($payments_file), true) : [];
        $payments[] = $payment_data;
        file_put_contents($payments_file, json_encode($payments, JSON_PRETTY_PRINT));
        
        echo json_encode([
            'success' => true,
            'message' => 'Paiement Barapay créé avec succès',
            'payment' => $payment_data,
            'checkout_url' => $payment->getApprovedUrl(),
            'redirect_required' => true
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la création du paiement Barapay: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Endpoint pour vérifier le statut d'un paiement
if ($request_uri === '/api/barapay/status' && $method === 'GET') {
    $payment_id = $_GET['payment_id'] ?? '';
    
    if (empty($payment_id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de paiement requis'
        ]);
        exit();
    }
    
    // Vérifier le statut dans nos données
    $payments_file = __DIR__ . '/payments.json';
    if (file_exists($payments_file)) {
        $payments = json_decode(file_get_contents($payments_file), true);
        
        foreach ($payments as $payment) {
            if ($payment['id'] === $payment_id) {
                echo json_encode([
                    'success' => true,
                    'payment' => $payment
                ]);
                exit();
            }
        }
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Paiement non trouvé'
    ]);
    exit();
}

// Endpoint pour le callback Barapay (webhook)
if ($request_uri === '/api/barapay/callback' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log du callback
    error_log('Barapay Callback: ' . json_encode($input));
    
    // Mettre à jour le statut du paiement
    if (isset($input['payment_id']) && isset($input['status'])) {
        $payments_file = __DIR__ . '/payments.json';
        if (file_exists($payments_file)) {
            $payments = json_decode(file_get_contents($payments_file), true);
            
            foreach ($payments as &$payment) {
                if ($payment['id'] === $input['payment_id']) {
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
        'POST /api/barapay/create' => 'Créer un paiement Barapay',
        'GET /api/barapay/status' => 'Vérifier le statut d\'un paiement',
        'POST /api/barapay/callback' => 'Webhook Barapay'
    ]
]);
?>

