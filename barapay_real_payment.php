<?php
// API Barapay réelle selon la documentation officielle
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

// Inclure les classes Barapay selon la documentation
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

// Configuration Barapay selon vos identifiants
define('BARAPAY_CLIENT_ID', 'wjb7lzQVialbcwMNTPD1IojrRzPIIl');
define('BARAPAY_CLIENT_SECRET', 'eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1');

// Endpoint pour créer un paiement Barapay selon la documentation
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
    
    try {
        // Implémentation exacte selon la documentation Barapay
        
        // 1. Payer Object
        $payer = new Payer();
        $payer->setPaymentMethod('PayMoney'); // selon la doc: "preferably, your system name"
        
        // 2. Amount Object
        $amountIns = new Amount();
        $amountIns->setTotal($input['amount'])->setCurrency('XOF'); // XOF pour Franc CFA
        
        // 3. Transaction Object
        $trans = new Transaction();
        $trans->setAmount($amountIns);
        
        // 4. RedirectUrls Object
        $urls = new RedirectUrls();
        $urls->setSuccessUrl('http://localhost:3000/#/payment/success')
             ->setCancelUrl('http://localhost:3000/#/payment/cancel');
        
        // 5. Payment Object
        $payment = new Payment();
        $payment->setCredentials([
            'client_id' => BARAPAY_CLIENT_ID,
            'client_secret' => BARAPAY_CLIENT_SECRET
        ])->setRedirectUrls($urls)
          ->setPayer($payer)
          ->setTransaction($trans);
        
        // Créer le paiement selon la documentation
        $payment->create();
        
        // Récupérer l'URL de checkout Barapay
        $checkoutUrl = $payment->getApprovedUrl();
        
        // Sauvegarder le paiement en attente
        $payment_data = [
            'id' => uniqid('PAY_'),
            'amount' => $input['amount'],
            'phone_number' => $input['phone_number'] ?? '',
            'payment_method' => $input['payment_method'] ?? 'PayMoney',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'type' => 'barapay_real',
            'checkout_url' => $checkoutUrl,
            'barapay_reference' => 'DONS-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)
        ];
        
        // Sauvegarder dans le fichier
        $payments_file = __DIR__ . '/payments.json';
        $payments = [];
        if (file_exists($payments_file)) {
            $payments = json_decode(file_get_contents($payments_file), true) ?: [];
        }
        $payments[] = $payment_data;
        file_put_contents($payments_file, json_encode($payments, JSON_PRETTY_PRINT));
        
        echo json_encode([
            'success' => true,
            'message' => 'Paiement Barapay créé avec succès',
            'payment' => $payment_data,
            'checkout_url' => $checkoutUrl,
            'redirect_required' => true,
            'barapay_reference' => $payment_data['barapay_reference']
        ]);
        
    } catch (Exception $ex) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur Barapay: ' . $ex->getMessage()
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
    
    // Vérifier le statut dans notre base de données
    $payments_file = __DIR__ . '/payments.json';
    if (file_exists($payments_file)) {
        $payments = json_decode(file_get_contents($payments_file), true) ?: [];
        
        foreach ($payments as $payment) {
            if ($payment['barapay_reference'] === $reference) {
                echo json_encode([
                    'success' => true,
                    'reference' => $reference,
                    'status' => $payment['status'],
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

// Endpoint de test
if ($request_uri === '/api/test' && $method === 'GET') {
    echo json_encode([
        'message' => 'API Barapay réelle fonctionne !',
        'timestamp' => date('c'),
        'barapay_configured' => true,
        'client_id' => BARAPAY_CLIENT_ID,
        'currency' => 'XOF',
        'status' => 'ready'
    ]);
    exit();
}

// Endpoint par défaut
http_response_code(404);
echo json_encode([
    'error' => 'Endpoint Barapay non trouvé',
    'request_uri' => $request_uri,
    'method' => $method,
    'available_endpoints' => [
        'POST /api/barapay/create' => 'Créer un paiement Barapay réel',
        'GET /api/barapay/status' => 'Vérifier le statut d\'un paiement',
        'POST /api/barapay/callback' => 'Callback Barapay (webhook)',
        'GET /api/test' => 'Test de l\'API'
    ]
]);
?>
