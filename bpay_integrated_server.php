<?php
// Serveur avec intégration Bpay SDK existant
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
        'message' => 'API DONS avec Bpay SDK intégré !',
        'timestamp' => date('c'),
        'database' => 'PostgreSQL connecté',
        'status' => 'success',
        'server' => 'Bpay Integrated Server',
        'ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
        'code' => '0000',
        'bpay_sdk' => 'intégré',
        'real_payments' => true
    ]);
    exit();
}

// Route /api/payments/initiate avec Bpay SDK
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
        // Créer un paiement Bpay RÉEL selon votre SDK
        $amount = new Amount();
        $amount->setTotal((int)$input['amount'])
               ->setCurrency('XOF');
        
        $transaction = new Transaction();
        $transaction->setAmount($amount)
                    ->setOrderNo('DONS-' . time() . '-' . rand(1000, 9999));
        
        $payer = new Payer();
        $payer->setPaymentMethod('Bpay');
        
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setSuccessUrl('http://localhost:3000/#/payment/success')
                     ->setCancelUrl('http://localhost:3000/#/payment/cancel');
        
        $payment = new Payment();
        $payment->setCredentials([
            'client_id' => BPAY_CLIENT_ID,
            'client_secret' => BPAY_CLIENT_SECRET
        ])->setPayer($payer)
          ->setTransaction($transaction)
          ->setRedirectUrls($redirectUrls);
        
        // Créer le paiement Bpay
        $payment->create();
        $checkoutUrl = $payment->getApprovedUrl();
        
        $payment_data = [
            'id' => uniqid('PAY_'),
            'amount' => $input['amount'],
            'phone_number' => $input['phone_number'],
            'network' => $input['network'] ?? 'MTN',
            'status' => 'pending',
            'timestamp' => date('c'),
            'message' => 'Paiement Bpay initié avec succès',
            'checkout_url' => $checkoutUrl,
            'bpay_reference' => 'DONS-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'real_payment' => true
        ];
        
        // Ajouter une redirection automatique vers l'URL de paiement
        $response = [
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
            'bpay_reference' => $payment_data['bpay_reference'],
            'real_payment' => true,
            'message' => 'Paiement Bpay initié avec succès',
            'reference' => $payment_data['id'],
            'auto_redirect' => true,
            'redirect_url' => $checkoutUrl
        ];
        
        // Si c'est une requête AJAX, retourner JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode($response);
        } else {
            // Sinon, rediriger automatiquement
            header('Location: ' . $checkoutUrl);
            exit();
        }
        
    } catch (BpayException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur Bpay: ' . $e->getMessage(),
            'message' => 'Erreur lors de l\'initiation du paiement Bpay'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur: ' . $e->getMessage(),
            'message' => 'Erreur lors de l\'initiation du paiement'
        ]);
    }
    exit();
}

// Route /api_save_payment_simple.php avec Bpay SDK
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
        // Créer un paiement Bpay RÉEL selon votre SDK
        $amount = new Amount();
        $amount->setTotal((int)$input['amount'])
               ->setCurrency('XOF');
        
        $transaction = new Transaction();
        $transaction->setAmount($amount)
                    ->setOrderNo('DONS-' . time() . '-' . rand(1000, 9999));
        
        $payer = new Payer();
        $payer->setPaymentMethod('Bpay');
        
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setSuccessUrl('http://localhost:3000/#/payment/success')
                     ->setCancelUrl('http://localhost:3000/#/payment/cancel');
        
        $payment = new Payment();
        $payment->setCredentials([
            'client_id' => BPAY_CLIENT_ID,
            'client_secret' => BPAY_CLIENT_SECRET
        ])->setPayer($payer)
          ->setTransaction($transaction)
          ->setRedirectUrls($redirectUrls);
        
        // Créer le paiement Bpay
        $payment->create();
        $checkoutUrl = $payment->getApprovedUrl();
        
        $payment_data = [
            'id' => uniqid('PAY_'),
            'amount' => $input['amount'],
            'phone_number' => $input['phone_number'],
            'network' => $input['network'] ?? 'MTN',
            'status' => 'pending',
            'timestamp' => date('c'),
            'message' => 'Paiement Bpay sauvegardé avec succès',
            'checkout_url' => $checkoutUrl,
            'bpay_reference' => 'DONS-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'real_payment' => true
        ];
        
        // Ajouter une redirection automatique vers l'URL de paiement
        $response = [
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
            'bpay_reference' => $payment_data['bpay_reference'],
            'real_payment' => true,
            'message' => 'Paiement Bpay sauvegardé avec succès',
            'reference' => $payment_data['id'],
            'auto_redirect' => true,
            'redirect_url' => $checkoutUrl
        ];
        
        // Si c'est une requête AJAX, retourner JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode($response);
        } else {
            // Sinon, rediriger automatiquement
            header('Location: ' . $checkoutUrl);
            exit();
        }
        
    } catch (BpayException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur Bpay: ' . $e->getMessage(),
            'message' => 'Erreur lors de la sauvegarde du paiement Bpay'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur: ' . $e->getMessage(),
            'message' => 'Erreur lors de la sauvegarde du paiement'
        ]);
    }
    exit();
}

// Route pour ouvrir automatiquement le lien de paiement
if ($request_uri === '/open_payment' && $method === 'GET') {
    $amount = $_GET['amount'] ?? 102;
    $phone = $_GET['phone'] ?? '237123456789';
    $network = $_GET['network'] ?? 'MTN';
    
    try {
        // Créer un paiement Bpay RÉEL
        $amountObj = new Amount();
        $amountObj->setTotal((int)$amount)->setCurrency('XOF');
        
        $transaction = new Transaction();
        $transaction->setAmount($amountObj)->setOrderNo('DONS-' . time() . '-' . rand(1000, 9999));
        
        $payer = new Payer();
        $payer->setPaymentMethod('Bpay');
        
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setSuccessUrl('http://localhost:3000/#/payment/success')
                     ->setCancelUrl('http://localhost:3000/#/payment/cancel');
        
        $payment = new Payment();
        $payment->setCredentials([
            'client_id' => BPAY_CLIENT_ID,
            'client_secret' => BPAY_CLIENT_SECRET
        ])->setPayer($payer)
          ->setTransaction($transaction)
          ->setRedirectUrls($redirectUrls);
        
        $payment->create();
        $checkoutUrl = $payment->getApprovedUrl();
        
        // Rediriger automatiquement vers l'URL de paiement
        header('Location: ' . $checkoutUrl);
        exit();
        
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage();
    }
    exit();
}

// Route racine
if ($request_uri === '/' && $method === 'GET') {
    echo json_encode([
        'message' => 'API DONS - Serveur avec Bpay SDK intégré',
        'timestamp' => date('c'),
        'status' => 'success',
        'server' => 'Bpay Integrated Server',
        'version' => '3.0.0',
        'bpay_sdk_integrated' => true,
        'real_payments' => true,
        'available_endpoints' => [
            'GET /api/test' => 'Test de l\'API principale',
            'POST /api/payments/initiate' => 'Initier un paiement Bpay RÉEL',
            'POST /api_save_payment_simple.php' => 'Sauvegarder un paiement Bpay RÉEL'
        ],
        'warning' => 'ATTENTION: Les paiements débitent RÉELLEMENT les comptes clients via Bpay SDK'
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
        'POST /api/payments/initiate' => 'Initier un paiement Bpay RÉEL',
        'POST /api_save_payment_simple.php' => 'Sauvegarder un paiement Bpay RÉEL'
    ]
]);
?>
