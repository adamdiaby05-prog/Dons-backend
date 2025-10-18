<?php
// Serveur qui utilise la vraie base de données PostgreSQL
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
    // Endpoint pour récupérer les données utilisateur
    if ($request_uri === '/api/user' && $method === 'GET') {
        $headers = getallheaders();
        $auth_header = $headers['Authorization'] ?? '';
        
        if (strpos($auth_header, 'Bearer ') === 0) {
            $token = substr($auth_header, 7);
            
            // Simuler un utilisateur connecté (pour le moment)
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
    
    // Endpoint pour récupérer les paiements depuis la vraie base de données
    if ($request_uri === '/api/payments' && $method === 'GET') {
        $query_params = [];
        parse_str($_SERVER['QUERY_STRING'], $query_params);
        $page = $query_params['page'] ?? 1;
        
        // Récupérer les vrais paiements de la base de données
        $payments = DB::table('payments')
            ->leftJoin('contributions', 'payments.contribution_id', '=', 'contributions.id')
            ->leftJoin('users', 'contributions.user_id', '=', 'users.id')
            ->select(
                'payments.id',
                'payments.amount',
                'payments.status',
                'payments.payment_method',
                'payments.payment_reference',
                'payments.phone_number',
                'payments.created_at',
                DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as user_name")
            )
            ->orderBy('payments.created_at', 'desc')
            ->get();
        
        // Formater les données
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
                'user_name' => trim($payment->user_name) ?: 'Utilisateur inconnu'
            ];
        }
        
        echo json_encode([
            'success' => 1,
            'data' => $formatted_payments,
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => 1,
                'total_items' => count($formatted_payments)
            ]
        ]);
        exit();
    }
    
    // Endpoint pour récupérer les groupes depuis la vraie base de données
    if ($request_uri === '/api/admin/groups' && $method === 'GET') {
        $groups = DB::table('groups')
            ->leftJoin('group_members', 'groups.id', '=', 'group_members.group_id')
            ->select(
                'groups.id',
                'groups.name',
                'groups.description',
                'groups.created_at',
                DB::raw('COUNT(group_members.id) as member_count')
            )
            ->groupBy('groups.id', 'groups.name', 'groups.description', 'groups.created_at')
            ->get();
        
        echo json_encode([
            'success' => 1,
            'data' => $groups
        ]);
        exit();
    }
    
    // Endpoint pour récupérer les contributions depuis la vraie base de données
    if ($request_uri === '/api/contributions' && $method === 'GET') {
        $contributions = DB::table('contributions')
            ->leftJoin('users', 'contributions.user_id', '=', 'users.id')
            ->leftJoin('groups', 'contributions.group_id', '=', 'groups.id')
            ->select(
                'contributions.id',
                'contributions.amount',
                'contributions.status',
                'contributions.type',
                'contributions.created_at',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
                'groups.name as group_name'
            )
            ->orderBy('contributions.created_at', 'desc')
            ->get();
        
        echo json_encode([
            'success' => 1,
            'data' => $contributions
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
            'server' => 'Real Database Server'
        ]);
        exit();
    }
    
    // Endpoint par défaut
    http_response_code(404);
    echo json_encode([
        'error' => 'Endpoint non trouvé',
        'request_uri' => $request_uri,
        'method' => $method,
        'available_endpoints' => [
            'GET /api/test' => 'Test de l\'API',
            'GET /api/user' => 'Données utilisateur',
            'GET /api/payments' => 'Liste des paiements (vraie base)',
            'GET /api/admin/groups' => 'Liste des groupes (vraie base)',
            'GET /api/contributions' => 'Liste des contributions (vraie base)'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ]);
}
?>
