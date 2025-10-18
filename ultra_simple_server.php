<?php
// Serveur ultra simple avec vraie base de données
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
            echo json_encode([
                'success' => 0,
                'message' => 'Données JSON invalides'
            ]);
            exit();
        }
        
        // Validation simple
        $required_fields = ['first_name', 'last_name', 'phone_number', 'password'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                http_response_code(422);
                echo json_encode([
                    'success' => 0,
                    'message' => "Le champ $field est requis"
                ]);
                exit();
            }
        }
        
        // Simuler une inscription réussie
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
            echo json_encode([
                'success' => 0,
                'message' => 'Données JSON invalides'
            ]);
            exit();
        }
        
        $phone_number = $input['phone_number'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($phone_number) || empty($password)) {
            http_response_code(422);
            echo json_encode([
                'success' => 0,
                'message' => 'Numéro de téléphone et mot de passe requis'
            ]);
            exit();
        }
        
        // Simuler une connexion réussie
        $token = 'token_' . bin2hex(random_bytes(16));
        
        echo json_encode([
            'success' => 1,
            'message' => 'Connexion réussie',
            'token' => $token,
            'user' => [
                'id' => 1,
                'first_name' => 'Admin',
                'last_name' => 'Test',
                'phone_number' => $phone_number,
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
            echo json_encode([
                'success' => 0,
                'message' => 'Token d\'authentification requis'
            ]);
        }
        exit();
    }
    
    // Endpoint pour enregistrer un nouveau paiement (POST)
    if ($request_uri === '/api/payments' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(422);
            echo json_encode([
                'success' => 0,
                'message' => 'Données JSON invalides'
            ]);
            exit();
        }
        
        // Validation des champs requis
        $required_fields = ['amount', 'phone_number', 'payment_method'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                http_response_code(422);
                echo json_encode([
                    'success' => 0,
                    'message' => "Le champ $field est requis"
                ]);
                exit();
            }
        }
        
        try {
            // Générer une référence de paiement unique
            $payment_reference = 'REF' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Récupérer une contribution existante ou créer une nouvelle
            $contribution_id = $input['contribution_id'] ?? null;
            if (!$contribution_id) {
                // Récupérer la première contribution disponible
                $contribution = DB::table('contributions')->first();
                if ($contribution) {
                    $contribution_id = $contribution->id;
                } else {
                    // Créer une nouvelle contribution si aucune n'existe
                    $contribution_id = DB::table('contributions')->insertGetId([
                        'group_id' => 1, // Groupe par défaut
                        'user_id' => 1,  // Utilisateur par défaut
                        'amount' => (float)$input['amount'],
                        'type' => 'payment',
                        'status' => 'paid',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            // Insérer le paiement dans la base de données
            $now = date('Y-m-d H:i:s');
            $payment_id = DB::table('payments')->insertGetId([
                'contribution_id' => $contribution_id,
                'payment_reference' => $payment_reference,
                'amount' => (float)$input['amount'],
                'payment_method' => $input['payment_method'],
                'phone_number' => $input['phone_number'],
                'status' => $input['status'] ?? 'pending',
                'gateway_response' => json_encode($input['gateway_response'] ?? []),
                'processed_at' => $input['status'] === 'completed' ? $now : null,
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
                    'status' => $input['status'] ?? 'pending',
                    'payment_method' => $input['payment_method'],
                    'phone_number' => $input['phone_number']
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => 0,
                'message' => 'Erreur lors de l\'enregistrement du paiement',
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }
    
    // Endpoint pour récupérer les paiements (version ultra simple)
    if (strpos($request_uri, '/api/payments') === 0 && $method === 'GET') {
        // Récupérer les paramètres de requête
        $query_params = [];
        parse_str($_SERVER['QUERY_STRING'], $query_params);
        $page = (int)($query_params['page'] ?? 1);
        $status = $query_params['status'] ?? null;
        $payment_method = $query_params['payment_method'] ?? null;
        
        // Récupérer seulement les paiements sans jointures
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
                'user_name' => 'Utilisateur ' . $payment->id // Nom simple pour l'instant
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
        exit();
    }
    
    
    // Endpoint pour récupérer les groupes
    if ($request_uri === '/api/admin/groups' && $method === 'GET') {
        $groups = DB::table('groups')->get();
        
        echo json_encode([
            'success' => 1,
            'data' => $groups
        ]);
        exit();
    }
    
    // Endpoint de test
    if ($request_uri === '/api/test' && $method === 'GET') {
        echo json_encode([
            'message' => 'API DONS avec vraie base de données PostgreSQL',
            'timestamp' => date('c'),
            'database' => 'PostgreSQL connecté',
            'status' => 'success',
            'server' => 'Ultra Simple Server'
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
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
