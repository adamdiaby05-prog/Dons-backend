<?php
// Serveur de test simple pour l'API DONS - Version corrigée
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Endpoint de test principal
if ($request_uri === '/api/test' && $method === 'GET') {
    echo json_encode([
        'message' => 'API DONS fonctionne correctement !',
        'timestamp' => date('c'),
        'database' => 'PostgreSQL connecté',
        'status' => 'success',
        'server' => 'Simple PHP Server',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    exit();
}

// Endpoint d'inscription
if ($request_uri === '/api/register' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Simuler la création d'utilisateur
    $user_data = [
        'id' => rand(1000, 9999),
        'first_name' => $input['first_name'],
        'last_name' => $input['last_name'],
        'phone_number' => $input['phone_number'],
        'email' => $input['email'] ?? null,
        'created_at' => date('c')
    ];
    
    $token = 'token_' . uniqid();
    
    echo json_encode([
        'success' => 1,
        'message' => 'Utilisateur enregistré avec succès',
        'user' => $user_data,
        'token' => $token
    ]);
    exit();
}

// Endpoint de connexion
if ($request_uri === '/api/login' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Simulation simple - accepter n'importe quel téléphone/mot de passe
    if (!empty($input['phone_number']) && !empty($input['password'])) {
        $user_data = [
            'id' => rand(1000, 9999),
            'first_name' => 'Utilisateur',
            'last_name' => 'Connecté',
            'phone_number' => $input['phone_number'],
            'email' => 'user@example.com',
            'phone_verified' => true
        ];
        
        $token = 'token_' . uniqid();
        
        echo json_encode([
            'success' => 1,
            'message' => 'Connexion réussie',
            'user' => $user_data,
            'token' => $token
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => 0,
            'message' => 'Identifiants invalides'
        ]);
    }
    exit();
}

// Endpoint pour récupérer les informations de l'utilisateur connecté
if ($request_uri === '/api/user' && $method === 'GET') {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? '';
    
    if (strpos($auth_header, 'Bearer ') === 0) {
        $token = substr($auth_header, 7);
        // Simuler un utilisateur connecté
        $user_data = [
            'id' => rand(1000, 9999),
            'first_name' => 'Utilisateur',
            'last_name' => 'Connecté',
            'phone_number' => '1234567890',
            'email' => 'user@example.com',
            'phone_verified' => true
        ];
        
        echo json_encode($user_data);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => 0,
            'message' => 'Token d\'authentification requis'
        ]);
    }
    exit();
}

// Endpoint pour récupérer la liste des paiements
if ($request_uri === '/api/payments' && $method === 'GET') {
    $query_params = [];
    parse_str($_SERVER['QUERY_STRING'], $query_params);
    $page = $query_params['page'] ?? 1;
    
    // Simuler des données de paiement
    $payments = [
        [
            'id' => 1,
            'payment_reference' => 'PAY001',
            'amount' => 5000,
            'status' => 'success',
            'payment_method' => 'Mobile Money',
            'created_at' => '2025-10-15T10:30:00Z',
            'user_name' => 'Jean Dupont'
        ],
        [
            'id' => 2,
            'payment_reference' => 'PAY002',
            'amount' => 3000,
            'status' => 'pending',
            'payment_method' => 'Orange Money',
            'created_at' => '2025-10-14T15:45:00Z',
            'user_name' => 'Marie Martin'
        ],
        [
            'id' => 3,
            'payment_reference' => 'PAY003',
            'amount' => 7500,
            'status' => 'failed',
            'payment_method' => 'MTN Money',
            'created_at' => '2025-10-13T09:20:00Z',
            'user_name' => 'Paul Kouassi'
        ]
    ];
    
    echo json_encode([
        'success' => 1,
        'data' => $payments,
        'pagination' => [
            'current_page' => (int)$page,
            'total_pages' => 1,
            'total_items' => count($payments)
        ]
    ]);
    exit();
}

// Endpoint pour récupérer les groupes admin
if ($request_uri === '/api/admin/groups' && $method === 'GET') {
    $groups = [
        [
            'id' => 1,
            'name' => 'Groupe Alpha',
            'description' => 'Groupe principal des donateurs',
            'member_count' => 25,
            'created_at' => '2025-10-01T00:00:00Z'
        ],
        [
            'id' => 2,
            'name' => 'Groupe Beta',
            'description' => 'Groupe des donateurs réguliers',
            'member_count' => 15,
            'created_at' => '2025-10-05T00:00:00Z'
        ]
    ];
    
    echo json_encode([
        'success' => 1,
        'data' => $groups
    ]);
    exit();
}

// Route par défaut - retourner une erreur 404
http_response_code(404);
echo json_encode([
    'success' => 0,
    'message' => 'Endpoint non trouvé',
    'error' => 'Endpoint non trouvé',
    'request_uri' => $request_uri,
    'method' => $method,
    'available_endpoints' => [
        'GET /api/test' => 'Test de l\'API principale',
        'POST /api/register' => 'Inscription utilisateur',
        'POST /api/login' => 'Connexion utilisateur',
        'GET /api/user' => 'Informations utilisateur',
        'GET /api/payments' => 'Liste des paiements',
        'GET /api/admin/groups' => 'Liste des groupes'
    ]
]);
?>
