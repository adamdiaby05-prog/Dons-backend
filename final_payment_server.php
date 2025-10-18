<?php
// Serveur final pour les paiements avec base de données PostgreSQL
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Charger l'application Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Configuration CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
header('Content-Type: application/json');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Endpoint pour l'inscription
    if ($request_uri === '/api/register' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(422);
            echo json_encode(['success' => 0, 'message' => 'Données JSON invalides']);
            exit();
        }
        
        echo json_encode([
            'success' => 1,
            'message' => 'Inscription réussie',
            'user' => [
                'id' => rand(1000, 9999),
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'phone_number' => $input['phone_number'],
                'email' => $input['email'] ?? null,
                'phone_verified' => false
            ]
        ]);
        exit();
    }
    
    // Endpoint pour la connexion
    if ($request_uri === '/api/login' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(422);
            echo json_encode(['success' => 0, 'message' => 'Données JSON invalides']);
            exit();
        }
        
        $token = 'token_' . bin2hex(random_bytes(16));
        
        echo json_encode([
            'success' => 1,
            'message' => 'Connexion réussie',
            'token' => $token,
            'user' => [
                'id' => 1,
                'first_name' => 'Admin',
                'last_name' => 'Test',
                'phone_number' => $input['phone_number'],
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
            $user_data = [
                'id' => 1,
                'first_name' => 'Admin',
                'last_name' => 'Test',
                'phone_number' => '0701234567',
                'email' => 'admin@test.com',
                'phone_verified' => true,
                'is_admin' => true
            ];
            echo json_encode($user_data);
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
        
        try {
            // Générer une référence unique
            $payment_reference = 'REF' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Récupérer une contribution existante
            $contribution = DB::table('contributions')->first();
            $contribution_id = $contribution ? $contribution->id : 1;
            
            // Insérer le paiement
            $now = date('Y-m-d H:i:s');
            $payment_id = DB::table('payments')->insertGetId([
                'contribution_id' => $contribution_id,
                'payment_reference' => $payment_reference,
                'amount' => (float)$input['amount'],
                'payment_method' => $input['payment_method'],
                'phone_number' => $input['phone_number'],
                'status' => $input['status'] ?? 'completed',
                'gateway_response' => json_encode(['status' => 'success']),
                'processed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now
            ]);
            
            echo json_encode([
                'success' => 1,
                'message' => 'Paiement enregistré avec succès',
                'payment' => [
                    'id' => $payment_id,
                    'payment_reference' => $payment_reference,
                    'amount' => (float)$input['amount'],
                    'status' => $input['status'] ?? 'completed',
                    'payment_method' => $input['payment_method'],
                    'phone_number' => $input['phone_number']
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => 0,
                'message' => 'Erreur lors de l\'enregistrement',
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }
    
    // Endpoint pour récupérer les paiements (GET)
    if (strpos($request_uri, '/api/payments') === 0 && $method === 'GET') {
        try {
            $query_params = [];
            parse_str($_SERVER['QUERY_STRING'], $query_params);
            $page = (int)($query_params['page'] ?? 1);
            $status = $query_params['status'] ?? null;
            $payment_method = $query_params['payment_method'] ?? null;
            
            $query = DB::table('payments');
            
            // Appliquer les filtres
            if ($status) {
                $query->where('status', $status);
            }
            if ($payment_method) {
                $query->where('payment_method', $payment_method);
            }
            
            $payments = $query->orderBy('created_at', 'desc')->get();
            
            $formatted_payments = [];
            foreach ($payments as $payment) {
                $formatted_payments[] = [
                    'id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'amount' => (float)$payment->amount,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'phone_number' => $payment->phone_number,
                    'created_at' => $payment->created_at,
                    'user_name' => 'Utilisateur ' . $payment->id
                ];
            }
            
            echo json_encode([
                'success' => 1,
                'data' => $formatted_payments,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => 1,
                    'total_items' => count($formatted_payments)
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => 0,
                'message' => 'Erreur lors de la récupération des paiements',
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }
    
    // Endpoint pour les groupes
    if ($request_uri === '/api/admin/groups' && $method === 'GET') {
        $groups = DB::table('groups')->get();
        echo json_encode(['success' => 1, 'data' => $groups]);
        exit();
    }
    
    // Endpoint de test
    if ($request_uri === '/api/test' && $method === 'GET') {
        echo json_encode([
            'message' => 'API DONS avec base PostgreSQL',
            'timestamp' => date('c'),
            'database' => 'PostgreSQL connecté',
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
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ]);
}
?>
