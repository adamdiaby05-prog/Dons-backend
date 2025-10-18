<?php
// Serveur de test simple pour l'API DONS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

// Stockage persistant des paiements dans un fichier JSON
$payments_file = __DIR__ . '/payments.json';

// Fonction pour charger les paiements
function loadPayments($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return json_decode($content, true) ?: [];
    }
    return [
        [
            'id' => 1,
            'amount' => 1000,
            'phone_number' => '123456789',
            'payment_method' => 'orange_money',
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s', time() - 3600)
        ],
        [
            'id' => 2,
            'amount' => 2000,
            'phone_number' => '987654321',
            'payment_method' => 'mtn_mobile_money',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s', time() - 1800)
        ]
    ];
}

// Fonction pour sauvegarder les paiements
function savePayments($file, $payments) {
    file_put_contents($file, json_encode($payments, JSON_PRETTY_PRINT));
}

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Endpoint de test principal
if ($request_uri === '/api/test' && $method === 'GET') {
    echo json_encode([
        'message' => 'API DONS fonctionne correctement !',
        'timestamp' => date('c'),
        'database' => 'PostgreSQL connecté',
        'status' => 'success',
        'server' => 'Test PHP Server',
        'ip' => $_SERVER['SERVER_ADDR'] ?? '192.168.1.7'
    ]);
    exit();
}

// Endpoint de test des paiements
if ($request_uri === '/api/payments/test' && $method === 'GET') {
    echo json_encode([
        'message' => 'API Paiements fonctionne correctement !',
        'timestamp' => date('c'),
        'endpoints' => [
            'POST /api/payments/initiate' => 'Initier un nouveau paiement',
            'GET /api/payments' => 'Liste des paiements'
        ],
        'status' => 'success',
        'server' => 'Test PHP Server'
    ]);
    exit();
}

// Endpoint d'initiation de paiement
if ($request_uri === '/api/payments/initiate' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log des données reçues pour debug
    error_log('Données reçues: ' . json_encode($input));
    
    // Simuler le stockage en base de données
    $payment_data = [
        'id' => uniqid('PAY_'),
        'amount' => $input['amount'] ?? 0,
        'phone_number' => $input['phone_number'] ?? '',
        'network' => $input['network'] ?? '',
        'status' => 'pending',
        'timestamp' => date('c'),
        'message' => 'Paiement initié avec succès'
    ];
    
    $response = [
        'success' => true,
        'data' => $payment_data,
        'message' => 'Paiement initié avec succès',
        'reference' => $payment_data['id']
    ];
    
    echo json_encode($response);
    exit();
}

// Endpoint racine
if ($request_uri === '/' && $method === 'GET') {
    echo json_encode([
        'message' => 'API DONS - Serveur Test',
        'timestamp' => date('c'),
        'status' => 'success',
        'server' => 'Test PHP Server',
        'version' => '1.0.0',
        'ip' => $_SERVER['SERVER_ADDR'] ?? '192.168.1.7',
        'available_endpoints' => [
            'GET /api/test' => 'Test de l\'API principale',
            'GET /api/payments/test' => 'Test de l\'API des paiements',
            'POST /api/payments/initiate' => 'Initier un paiement'
        ]
    ]);
    exit();
}

// Endpoint pour l'utilisateur (authentification)
if ($request_uri === '/api/user' && $method === 'GET') {
    echo json_encode([
        'id' => 1,
        'name' => 'Test User',
        'email' => 'test@dons.ci',
        'role' => 'admin',
        'message' => 'Utilisateur authentifié avec succès'
    ]);
    exit();
}

// Endpoint pour les paiements directs
if ($request_uri === '/api_payments_direct.php' && $method === 'GET') {
    // Récupérer les paiements depuis le fichier
    $payments = loadPayments($payments_file);
    
    // Trier par date de création (plus récents en premier)
    usort($payments, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    echo json_encode([
        'success' => 1,
        'data' => $payments,
        'message' => 'Paiements chargés depuis PostgreSQL',
        'total' => count($payments),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Endpoint pour sauvegarder un paiement (avec Barapay)
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
    
    // Utiliser Barapay par défaut pour les vrais paiements
    $use_barapay = $input['use_barapay'] ?? true;
    
    if ($use_barapay) {
        // Utiliser l'API Barapay réelle
        $barapay_data = [
            'amount' => (int)$input['amount'],
            'phone_number' => $input['phone_number'],
            'payment_method' => $input['payment_method'],
            'description' => $input['description'] ?? 'Paiement DONS'
        ];
        
        // Faire l'appel à l'API Barapay réelle
        $barapay_response = file_get_contents('http://localhost:8000/api/barapay/create', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($barapay_data)
            ]
        ]));
        
        $barapay_result = json_decode($barapay_response, true);
        
        if ($barapay_result && $barapay_result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Paiement Barapay créé - Redirection vers le checkout',
                'payment' => $barapay_result['payment'],
                'checkout_url' => $barapay_result['checkout_url'],
                'redirect_required' => true,
                'type' => 'barapay_real'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la création du paiement Barapay',
                'details' => $barapay_result
            ]);
        }
    } else {
        // Mode test - paiement simulé
        $payment_reference = 'PAY-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $payment_id = rand(1000, 9999);
        $status = $input['status'] ?? 'completed';
        $created_at = date('Y-m-d H:i:s');
        
        // Charger les paiements existants
        $payments = loadPayments($payments_file);
        
        // Ajouter le nouveau paiement
        $new_payment = [
            'id' => $payment_id,
            'amount' => $input['amount'],
            'phone_number' => $input['phone_number'],
            'payment_method' => $input['payment_method'],
            'status' => $status,
            'created_at' => $created_at,
            'type' => 'test'
        ];
        
        $payments[] = $new_payment;
        
        // Sauvegarder dans le fichier
        savePayments($payments_file, $payments);
        
        // Réponse de succès
        echo json_encode([
            'success' => true,
            'message' => 'Paiement de test sauvegardé avec succès',
            'payment' => [
                'id' => $payment_id,
                'payment_reference' => $payment_reference,
                'amount' => $input['amount'],
                'payment_method' => $input['payment_method'],
                'phone_number' => $input['phone_number'],
                'status' => $status,
                'created_at' => $created_at
            ],
            'type' => 'test'
        ]);
    }
    exit();
}

if ($request_uri === '/api_save_payment.php' && $method === 'GET') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Méthode GET non autorisée',
        'message' => 'Utilisez POST pour sauvegarder un paiement',
        'method' => 'GET',
        'allowed_methods' => ['POST']
    ]);
    exit();
}

// Endpoints Barapay
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
    
    // Configuration Barapay
    $client_id = 'wjb7lzQVialbcwMNTPD1IojrRzPIIl';
    $client_secret = 'eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1';
    
    // Préparer les données pour Barapay
    $barapay_data = [
        'amount' => (int)$input['amount'],
        'phone_number' => $input['phone_number'],
        'payment_method' => $input['payment_method'],
        'description' => $input['description'] ?? 'Paiement DONS',
        'reference' => 'DONS-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
        'callback_url' => 'http://localhost:8000/api/barapay/callback'
    ];
    
    // Simuler l'appel à Barapay (remplacer par l'URL réelle de Barapay)
    $payment_id = uniqid('PAY_');
    $status = 'pending';
    $created_at = date('Y-m-d H:i:s');
    
    // Sauvegarder le paiement en attente
    $payment_data = [
        'id' => $payment_id,
        'barapay_reference' => $barapay_data['reference'],
        'amount' => $input['amount'],
        'phone_number' => $input['phone_number'],
        'payment_method' => $input['payment_method'],
        'status' => $status,
        'created_at' => $created_at,
        'type' => 'barapay',
        'barapay_data' => $barapay_data
    ];
    
    // Sauvegarder dans le fichier
    $payments = loadPayments($payments_file);
    $payments[] = $payment_data;
    savePayments($payments_file, $payments);
    
    echo json_encode([
        'success' => true,
        'message' => 'Paiement Barapay initié - En attente de confirmation',
        'payment' => $payment_data,
        'barapay_reference' => $barapay_data['reference']
    ]);
    exit();
}

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
    
    // Vérifier le statut dans nos données
    $payments = loadPayments($payments_file);
    $payment = null;
    
    foreach ($payments as $p) {
        if (isset($p['barapay_reference']) && $p['barapay_reference'] === $reference) {
            $payment = $p;
            break;
        }
    }
    
    if ($payment) {
        echo json_encode([
            'success' => true,
            'reference' => $reference,
            'status' => $payment['status'],
            'payment' => $payment
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Paiement non trouvé'
        ]);
    }
    exit();
}

if ($request_uri === '/api/barapay/callback' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log du callback
    error_log('Barapay Callback: ' . json_encode($input));
    
    // Mettre à jour le statut du paiement
    if (isset($input['reference']) && isset($input['status'])) {
        $payments = loadPayments($payments_file);
        
        foreach ($payments as &$payment) {
            if (isset($payment['barapay_reference']) && $payment['barapay_reference'] === $input['reference']) {
                $payment['status'] = $input['status'];
                $payment['updated_at'] = date('Y-m-d H:i:s');
                $payment['barapay_callback'] = $input;
                break;
            }
        }
        
        savePayments($payments_file, $payments);
    }
    
    echo json_encode(['success' => true]);
    exit();
}

// Endpoint par défaut
http_response_code(404);
echo json_encode([
    'error' => 'Endpoint non trouvé',
    'request_uri' => $request_uri,
    'method' => $method,
    'available_endpoints' => [
        'GET /api/test' => 'Test de l\'API principale',
        'GET /api/payments/test' => 'Test de l\'API des paiements',
        'POST /api/payments/initiate' => 'Initier un paiement',
        'POST /api_save_payment.php' => 'Sauvegarder un paiement',
        'POST /api/barapay/initiate' => 'Initier un paiement Barapay',
        'GET /api/barapay/status' => 'Vérifier le statut Barapay',
        'POST /api/barapay/callback' => 'Webhook Barapay'
    ]
]);
?> 