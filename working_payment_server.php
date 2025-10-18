<?php
// Serveur ultra simple qui fonctionne
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
header('Content-Type: application/json');

// Gérer les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Endpoint pour l'inscription
if ($request_uri === '/api/register' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    echo json_encode([
        'success' => 1,
        'message' => 'Inscription réussie',
        'user' => [
            'id' => rand(1000, 9999),
            'first_name' => $input['first_name'] ?? 'Test',
            'last_name' => $input['last_name'] ?? 'User',
            'phone_number' => $input['phone_number'] ?? '0701234567',
            'email' => $input['email'] ?? 'test@example.com',
            'phone_verified' => false
        ]
    ]);
    exit();
}

// Endpoint pour la connexion
if ($request_uri === '/api/login' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = 'token_' . bin2hex(random_bytes(16));
    
    echo json_encode([
        'success' => 1,
        'message' => 'Connexion réussie',
        'token' => $token,
        'user' => [
            'id' => 1,
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'phone_number' => $input['phone_number'] ?? '0701234567',
            'email' => 'admin@test.com',
            'phone_verified' => true,
            'is_admin' => true
        ]
    ]);
    exit();
}

// Endpoint pour récupérer les données utilisateur
if ($request_uri === '/api/user' && $method === 'GET') {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? '';
    
    if (strpos($auth_header, 'Bearer ') === 0) {
        echo json_encode([
            'id' => 1,
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'phone_number' => '0701234567',
            'email' => 'admin@test.com',
            'phone_verified' => true,
            'is_admin' => true
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => 0, 'message' => 'Token requis']);
    }
    exit();
}

// Endpoint pour créer un nouveau paiement (POST)
if ($request_uri === '/api/payments' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(422);
        echo json_encode(['success' => 0, 'message' => 'Données JSON invalides']);
        exit();
    }
    
    // Simuler l'enregistrement en base
    $payment_reference = 'REF' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    
    echo json_encode([
        'success' => 1,
        'message' => 'Paiement enregistré avec succès',
        'payment' => [
            'id' => rand(100, 999),
            'payment_reference' => $payment_reference,
            'amount' => (float)$input['amount'],
            'status' => $input['status'] ?? 'completed',
            'payment_method' => $input['payment_method'],
            'phone_number' => $input['phone_number']
        ]
    ]);
    exit();
}

// Endpoint pour récupérer les paiements (GET)
if (strpos($request_uri, '/api/payments') === 0 && $method === 'GET') {
    // Données simulées pour les paiements
    $payments = [
        [
            'id' => 1,
            'payment_reference' => 'REF804325',
            'amount' => 5000,
            'status' => 'completed',
            'payment_method' => 'orange_money',
            'phone_number' => '0701234567',
            'created_at' => '2025-08-23 14:40:23',
            'user_name' => 'Utilisateur 1'
        ],
        [
            'id' => 2,
            'payment_reference' => 'REF580455',
            'amount' => 5000,
            'status' => 'completed',
            'payment_method' => 'orange_money',
            'phone_number' => '0701234568',
            'created_at' => '2025-08-23 14:40:23',
            'user_name' => 'Utilisateur 2'
        ],
        [
            'id' => 3,
            'payment_reference' => 'REF219200',
            'amount' => 5000,
            'status' => 'completed',
            'payment_method' => 'orange_money',
            'phone_number' => '0701234569',
            'created_at' => '2025-08-23 14:40:23',
            'user_name' => 'Utilisateur 3'
        ],
        [
            'id' => 4,
            'payment_reference' => 'REF898474',
            'amount' => 10000,
            'status' => 'completed',
            'payment_method' => 'orange_money',
            'phone_number' => '0701234567',
            'created_at' => '2025-08-23 14:40:23',
            'user_name' => 'Utilisateur 4'
        ],
        [
            'id' => 5,
            'payment_reference' => 'REF331530',
            'amount' => 10000,
            'status' => 'completed',
            'payment_method' => 'orange_money',
            'phone_number' => '0701234568',
            'created_at' => '2025-08-23 14:40:23',
            'user_name' => 'Utilisateur 5'
        ]
    ];
    
    echo json_encode([
        'success' => 1,
        'data' => $payments,
        'pagination' => [
            'current_page' => 1,
            'total_pages' => 1,
            'total_items' => count($payments)
        ]
    ]);
    exit();
}

// Endpoint pour les campagnes
if ($request_uri === '/api/campaign' && $method === 'GET') {
    $campaigns = [
        [
            'id' => 1,
            'title' => 'Education',
            'description' => 'Soutenir l\'éducation des enfants',
            'image' => '/assets/images/education.jpg',
            'target_amount' => 1000000,
            'current_amount' => 250000,
            'status' => 'active',
            'created_at' => '2025-01-01T00:00:00Z'
        ],
        [
            'id' => 2,
            'title' => 'Infrastructure',
            'description' => 'Améliorer les infrastructures locales',
            'image' => '/assets/images/infrastructure.jpg',
            'target_amount' => 2000000,
            'current_amount' => 500000,
            'status' => 'active',
            'created_at' => '2025-01-01T00:00:00Z'
        ],
        [
            'id' => 3,
            'title' => 'Santé',
            'description' => 'Améliorer l\'accès aux soins de santé',
            'image' => '/assets/images/sante.jpg',
            'target_amount' => 1500000,
            'current_amount' => 300000,
            'status' => 'active',
            'created_at' => '2025-01-01T00:00:00Z'
        ],
        [
            'id' => 4,
            'title' => 'Économie',
            'description' => 'Soutenir l\'entrepreneuriat local',
            'image' => '/assets/images/economie.jpg',
            'target_amount' => 800000,
            'current_amount' => 200000,
            'status' => 'active',
            'created_at' => '2025-01-01T00:00:00Z'
        ]
    ];
    echo json_encode(['success' => 1, 'data' => $campaigns]);
    exit();
}

// Endpoint pour les groupes
if ($request_uri === '/api/admin/groups' && $method === 'GET') {
    $groups = [
        ['id' => 1, 'name' => 'Groupe Test 1', 'description' => 'Description du groupe 1'],
        ['id' => 2, 'name' => 'Groupe Test 2', 'description' => 'Description du groupe 2']
    ];
    echo json_encode(['success' => 1, 'data' => $groups]);
    exit();
}

// Endpoint de test
if ($request_uri === '/api/test' && $method === 'GET') {
    echo json_encode([
        'message' => 'API DONS fonctionnelle',
        'timestamp' => date('c'),
        'status' => 'success'
    ]);
    exit();
}

// Endpoint par défaut
http_response_code(404);
echo json_encode([
    'error' => 'Endpoint non trouvé',
    'request_uri' => $request_uri,
    'method' => $method
]);
?>
