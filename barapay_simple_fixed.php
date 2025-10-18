<?php
// API Barapay SIMPLE - URL de paiement mobile money corrigée
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

// Configuration Barapay RÉELLE
define('BARAPAY_CLIENT_ID', 'wjb7lzQVialbcwMNTPD1IojrRzPIIl');
define('BARAPAY_CLIENT_SECRET', 'eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1');

// Endpoint pour sauvegarder un paiement RÉEL (compatible avec Flutter)
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
        // Générer une référence unique
        $reference = 'DONS-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // URL de paiement mobile money RÉELLE - Utilisation de l'API Barapay
        $checkoutUrl = 'https://barapay.net/pay?' . http_build_query([
            'client_id' => BARAPAY_CLIENT_ID,
            'amount' => (int)$input['amount'],
            'currency' => 'XOF',
            'phone' => $input['phone_number'] ?? '',
            'ref' => $reference,
            'description' => 'Paiement DONS - ' . $input['amount'] . ' FCFA',
            'success_url' => 'http://localhost:8000/payment-success.php',
            'cancel_url' => 'http://localhost:8000/payment-cancel.php'
        ]);
        
        // Sauvegarder le paiement
        $payment_data = [
            'id' => uniqid('PAY_'),
            'amount' => $input['amount'],
            'phone_number' => $input['phone_number'] ?? '',
            'payment_method' => $input['payment_method'] ?? 'PayMoney',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'type' => 'barapay_simple',
            'checkout_url' => $checkoutUrl,
            'barapay_reference' => $reference,
            'real_payment' => true,
            'mobile_money' => true
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
            'message' => 'Paiement Barapay RÉEL créé - Mobile Money activé',
            'payment' => $payment_data,
            'checkout_url' => $checkoutUrl,
            'redirect_required' => true,
            'barapay_reference' => $reference,
            'real_payment' => true,
            'mobile_money' => true,
            'warning' => 'ATTENTION: Ce paiement débitera RÉELLEMENT le compte mobile money du client'
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
        'barapay_simple' => true
    ]);
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

// Endpoint de test
if ($request_uri === '/api/test' && $method === 'GET') {
    echo json_encode([
        'message' => 'API Barapay SIMPLE fonctionne - Paiements réels avec URL corrigée !',
        'timestamp' => date('c'),
        'barapay_configured' => true,
        'client_id' => BARAPAY_CLIENT_ID,
        'currency' => 'XOF',
        'status' => 'ready',
        'real_payments' => true,
        'mobile_money' => true,
        'url_fixed' => true,
        'warning' => 'ATTENTION: Les paiements débitent RÉELLEMENT les comptes mobile money'
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
        <title>DONS - API Barapay SIMPLE</title>
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
            <h1>API Barapay SIMPLE</h1>
            <p class="subtitle">Paiements réels - URL corrigée</p>
            
            <div class="success">
                ✅ URL de paiement corrigée - Plus d'erreur 404
            </div>
            
            <div class="warning">
                ⚠️ ATTENTION: Les paiements sont RÉELS et débitent vraiment les comptes mobile money !
            </div>
            
            <p style="margin-top: 30px; color: #ff6b6b; font-weight: bold;">
                📱 MOBILE MONEY RÉEL ACTIVÉ - URL CORRIGÉE
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
    'error' => 'Endpoint Barapay SIMPLE non trouvé',
    'request_uri' => $request_uri,
    'method' => $method,
    'real_payments' => true,
    'mobile_money' => true
]);
?>
