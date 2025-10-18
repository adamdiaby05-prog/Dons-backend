<?php
// Test CORS simple pour vérifier la configuration
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With, X-CSRF-TOKEN');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Simuler les endpoints
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
        echo json_encode([
            'success' => 0,
            'message' => 'Token d\'authentification requis'
        ]);
    }
    exit();
}

if ($request_uri === '/api/payments' && $method === 'GET') {
    $page = $_GET['page'] ?? 1;
    echo json_encode([
        'success' => 1,
        'data' => [
            [
                'id' => 1,
                'amount' => 5000,
                'status' => 'completed',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ],
        'pagination' => [
            'current_page' => $page,
            'total' => 1
        ]
    ]);
    exit();
}

// Route par défaut
echo json_encode([
    'message' => 'Test CORS Server',
    'method' => $method,
    'uri' => $request_uri,
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'success'
]);
?>
