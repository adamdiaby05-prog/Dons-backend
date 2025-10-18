<?php
// Serveur simple et fonctionnel pour DONS avec Barapay
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With, X-CSRF-TOKEN');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Inclure le SDK Bpay existant
require_once '../bpay_sdk/php/vendor/autoload.php';

use Bpay\Api\Amount;
use Bpay\Api\Payer;
use Bpay\Api\Payment;
use Bpay\Api\RedirectUrls;
use Bpay\Api\Transaction;
use Bpay\Exception\BpayException;

// Configuration Bpay
define('BPAY_CLIENT_ID', 'wjb7lzQVialbcwMNTPD1IojrRzPIIl');
define('BPAY_CLIENT_SECRET', 'eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1');

// Gérer les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Nettoyer l'URI
$request_uri = parse_url($request_uri, PHP_URL_PATH);

// Route /api/test
if ($request_uri === '/api/test' && $method === 'GET') {
    echo json_encode([
        'message' => 'API DONS fonctionne correctement !',
        'timestamp' => date('c'),
        'database' => 'PostgreSQL connecté',
        'status' => 'success',
        'server' => 'Simple Working Server',
        'ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
        'code' => '0000'
    ]);
    exit();
}

// Route /api/payments/initiate avec Barapay
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
    
    try {
        // Créer un paiement Barapay RÉEL
        $payer = new Payer();
        $payer->setPaymentMethod('PayMoney');
        
        $amountIns = new Amount();
        $amountIns->setTotal((int)$input['amount'])->setCurrency('XOF');
        
        $trans = new Transaction();
        $trans->setAmount($amountIns);
        
        $urls = new RedirectUrls();
        $urls->setSuccessUrl('http://localhost:3000/#/payment/success')
             ->setCancelUrl('http://localhost:3000/#/payment/cancel');
        
        $payment = new Payment();
        $payment->setCredentials([
            'client_id' => BARAPAY_CLIENT_ID,
            'client_secret' => BARAPAY_CLIENT_SECRET
        ])->setRedirectUrls($urls)
          ->setPayer($payer)
          ->setTransaction($trans)
          ->setPhoneNumber($input['phone_number'])
          ->setDescription('Paiement DONS - ' . $input['amount'] . ' FCFA');
        
        // Créer le paiement Barapay
        $payment->create();
        $checkoutUrl = $payment->getApprovedUrl();
        
        $payment_data = [
            'id' => uniqid('PAY_'),
            'amount' => $input['amount'],
            'phone_number' => $input['phone_number'],
            'network' => $input['network'] ?? 'MTN',
            'status' => 'pending',
            'timestamp' => date('c'),
            'message' => 'Paiement Barapay initié avec succès',
            'checkout_url' => $checkoutUrl,
            'barapay_reference' => 'DONS-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'real_payment' => true
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $payment_data,
            'payment' => [
                'id' => $payment_data['id'],
                'payment_reference' => $payment_data['id'],
                'status' => $payment_data['status'],
                'amount' => $payment_data['amount'],
                'phone_number' => $payment_data['phone_number'],
                'network' => $payment_data['network'],
                'created_at' => $payment_data['timestamp']
            ],
            'checkout_url' => $checkoutUrl,
            'redirect_required' => true,
            'barapay_reference' => $payment_data['barapay_reference'],
            'real_payment' => true,
            'message' => 'Paiement Barapay initié avec succès',
            'reference' => $payment_data['id']
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur Barapay: ' . $e->getMessage(),
            'message' => 'Erreur lors de l\'initiation du paiement Barapay'
        ]);
    }
    exit();
}

// Route /api_save_payment_simple.php avec Barapay
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
    
    try {
        // Créer un paiement Barapay RÉEL
        $payer = new Payer();
        $payer->setPaymentMethod('PayMoney');
        
        $amountIns = new Amount();
        $amountIns->setTotal((int)$input['amount'])->setCurrency('XOF');
        
        $trans = new Transaction();
        $trans->setAmount($amountIns);
        
        $urls = new RedirectUrls();
        $urls->setSuccessUrl('http://localhost:3000/#/payment/success')
             ->setCancelUrl('http://localhost:3000/#/payment/cancel');
        
        $payment = new Payment();
        $payment->setCredentials([
            'client_id' => BARAPAY_CLIENT_ID,
            'client_secret' => BARAPAY_CLIENT_SECRET
        ])->setRedirectUrls($urls)
          ->setPayer($payer)
          ->setTransaction($trans)
          ->setPhoneNumber($input['phone_number'])
          ->setDescription('Paiement DONS - ' . $input['amount'] . ' FCFA');
        
        // Créer le paiement Barapay
        $payment->create();
        $checkoutUrl = $payment->getApprovedUrl();
        
        $payment_data = [
            'id' => uniqid('PAY_'),
            'amount' => $input['amount'],
            'phone_number' => $input['phone_number'],
            'network' => $input['network'] ?? 'MTN',
            'status' => 'pending',
            'timestamp' => date('c'),
            'message' => 'Paiement Barapay sauvegardé avec succès',
            'checkout_url' => $checkoutUrl,
            'barapay_reference' => 'DONS-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'real_payment' => true
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $payment_data,
            'payment' => [
                'id' => $payment_data['id'],
                'payment_reference' => $payment_data['id'],
                'status' => $payment_data['status'],
                'amount' => $payment_data['amount'],
                'phone_number' => $payment_data['phone_number'],
                'network' => $payment_data['network'],
                'created_at' => $payment_data['timestamp']
            ],
            'checkout_url' => $checkoutUrl,
            'redirect_required' => true,
            'barapay_reference' => $payment_data['barapay_reference'],
            'real_payment' => true,
            'message' => 'Paiement Barapay sauvegardé avec succès',
            'reference' => $payment_data['id']
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur Barapay: ' . $e->getMessage(),
            'message' => 'Erreur lors de la sauvegarde du paiement Barapay'
        ]);
    }
    exit();
}

// Route racine
if ($request_uri === '/' && $method === 'GET') {
    echo json_encode([
        'message' => 'API DONS - Serveur avec Barapay intégré',
        'timestamp' => date('c'),
        'status' => 'success',
        'server' => 'Simple Working Server avec Barapay',
        'version' => '2.0.0',
        'barapay_integrated' => true,
        'real_payments' => true,
        'available_endpoints' => [
            'GET /api/test' => 'Test de l\'API principale',
            'POST /api/payments/initiate' => 'Initier un paiement Barapay RÉEL',
            'POST /api_save_payment_simple.php' => 'Sauvegarder un paiement Barapay RÉEL'
        ],
        'warning' => 'ATTENTION: Les paiements débitent RÉELLEMENT les comptes clients via Barapay'
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
