<?php
// API Barapay DIRECTE - Appel direct à l'API officielle Barapay
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Vos accès Barapay RÉELS
define('BARAPAY_CLIENT_ID', 'wjb7lzQVialbcwMNTPD1IojrRzPIIl');
define('BARAPAY_CLIENT_SECRET', 'eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1');

// Fonction pour appeler directement l'API Barapay
function callBarapayAPI($amount, $phone, $description = 'Paiement DONS') {
    $reference = 'DONS-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Données pour l'API Barapay officielle
    $apiData = [
        'client_id' => BARAPAY_CLIENT_ID,
        'client_secret' => BARAPAY_CLIENT_SECRET,
        'amount' => (int)$amount,
        'currency' => 'XOF',
        'phone_number' => $phone,
        'description' => $description,
        'reference' => $reference,
        'success_url' => 'http://localhost:8000/payment-success.php',
        'cancel_url' => 'http://localhost:8000/payment-cancel.php',
        'callback_url' => 'http://localhost:8000/api/barapay/callback'
    ];
    
    // Appel DIRECT à l'API Barapay
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.barapay.net/v1/payments/create');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . BARAPAY_CLIENT_SECRET,
        'X-Client-ID: ' . BARAPAY_CLIENT_ID
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
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
    
    $responseData = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true,
            'checkout_url' => $responseData['checkout_url'] ?? $responseData['redirect_url'] ?? $responseData['payment_url'] ?? 'https://barapay.net/pay',
            'payment_id' => $responseData['payment_id'] ?? $responseData['id'] ?? uniqid('PAY_'),
            'reference' => $reference,
            'data' => $responseData,
            'api_response' => $responseData
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Erreur API Barapay: ' . ($responseData['message'] ?? 'Erreur inconnue'),
            'http_code' => $httpCode,
            'response' => $responseData
        ];
    }
}

// Endpoint pour créer un paiement RÉEL (compatible avec Flutter)
if ($request_uri === '/api_save_payment.php' && $method === 'POST') {
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
        // Appel DIRECT à l'API Barapay
        $result = callBarapayAPI(
            $input['amount'],
            $input['phone_number'] ?? '',
            'Paiement DONS - ' . $input['amount'] . ' FCFA'
        );
        
        if ($result['success']) {
            // Sauvegarder le paiement
            $payment_data = [
                'id' => $result['payment_id'],
                'amount' => $input['amount'],
                'phone_number' => $input['phone_number'] ?? '',
                'payment_method' => $input['payment_method'] ?? 'PayMoney',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'type' => 'barapay_direct_api',
                'checkout_url' => $result['checkout_url'],
                'barapay_reference' => $result['reference'],
                'real_payment' => true,
                'api_response' => $result['api_response']
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
                'message' => 'Paiement Barapay RÉEL créé - API directe',
                'payment' => $payment_data,
                'checkout_url' => $result['checkout_url'],
                'redirect_required' => true,
                'barapay_reference' => $result['reference'],
                'real_payment' => true,
                'direct_api' => true,
                'api_response' => $result['api_response'],
                'warning' => 'ATTENTION: Ce paiement débitera RÉELLEMENT le compte du client'
            ]);
        } else {
            throw new Exception('Erreur API Barapay: ' . $result['error']);
        }
        
    } catch (Exception $ex) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur Barapay DIRECTE: ' . $ex->getMessage()
        ]);
    }
    exit();
}

// Page de succès de paiement
if ($request_uri === '/payment-success.php' && $method === 'GET') {
    include __DIR__ . '/payment-success.php';
    exit();
}

// Page d'annulation de paiement
if ($request_uri === '/payment-cancel.php' && $method === 'GET') {
    include __DIR__ . '/payment-cancel.php';
    exit();
}

// Endpoint pour l'authentification utilisateur (compatible avec Flutter)
if ($request_uri === '/api/user' && $method === 'GET') {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    if ($token && strpos($token, 'token_') === 0) {
        echo json_encode([
            'id' => 1,
            'name' => 'Admin DONS',
            'email' => 'admin@dons.com',
            'role' => 'admin',
            'authenticated' => true,
            'token' => $token
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'error' => 'Token invalide',
            'authenticated' => false
        ]);
    }
    exit();
}

// Endpoint pour récupérer les paiements (compatible avec Flutter)
if ($request_uri === '/api_payments_direct.php' && $method === 'GET') {
    $payments_file = __DIR__ . '/payments.json';
    $payments = [];
    
    if (file_exists($payments_file)) {
        $payments = json_decode(file_get_contents($payments_file), true) ?: [];
    }
    
    echo json_encode([
        'success' => true,
        'payments' => $payments,
        'total' => count($payments),
        'page' => 1,
        'per_page' => 10,
        'real_payments' => true,
        'direct_api' => true
    ]);
    exit();
}

// Endpoint de test
if ($request_uri === '/api/test' && $method === 'GET') {
    echo json_encode([
        'message' => 'API Barapay DIRECTE fonctionne - Appel direct à l\'API officielle !',
        'timestamp' => date('c'),
        'barapay_configured' => true,
        'client_id' => BARAPAY_CLIENT_ID,
        'currency' => 'XOF',
        'status' => 'ready',
        'real_payments' => true,
        'direct_api' => true,
        'api_url' => 'https://api.barapay.net/v1/payments/create',
        'warning' => 'ATTENTION: Les paiements débitent RÉELLEMENT les comptes clients'
    ]);
    exit();
}

// Page d'accueil
if ($request_uri === '/' && $method === 'GET') {
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>DONS - API Barapay DIRECTE</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
                margin: 0;
                padding: 20px;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container {
                background: white;
                border-radius: 15px;
                padding: 40px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                max-width: 600px;
                width: 100%;
                text-align: center;
            }
            .logo {
                width: 80px;
                height: 80px;
                background: #ff6b6b;
                border-radius: 50%;
                margin: 0 auto 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 24px;
                font-weight: bold;
            }
            h1 {
                color: #333;
                margin-bottom: 10px;
            }
            .subtitle {
                color: #666;
                margin-bottom: 30px;
            }
            .warning {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                font-weight: bold;
            }
            .success {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="logo">DONS</div>
            <h1>API Barapay DIRECTE</h1>
            <p class="subtitle">Paiements réels - Appel direct à l'API officielle</p>
            
            <div class="success">
                ✅ Appel direct à l'API Barapay officielle
            </div>
            
            <div class="warning">
                ⚠️ ATTENTION: Les paiements sont RÉELS et débitent vraiment les comptes clients !
            </div>
            
            <p style="margin-top: 30px; color: #ff6b6b; font-weight: bold;">
                🔥 PAIEMENTS RÉELS - API BARAPAY DIRECTE
            </p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Endpoint par défaut
http_response_code(404);
echo json_encode([
    'error' => 'Endpoint Barapay DIRECT non trouvé',
    'request_uri' => $request_uri,
    'method' => $method,
    'real_payments' => true,
    'direct_api' => true
]);
?>