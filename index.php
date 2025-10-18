<?php
// Point d'entrée principal pour l'API de paiement
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With, X-CSRF-TOKEN');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Router vers l'endpoint Barapay pour les paiements
if ($_SERVER['REQUEST_URI'] === '/api_barapay_payment.php' || strpos($_SERVER['REQUEST_URI'], 'api_barapay_payment') !== false) {
    require_once __DIR__ . '/api_barapay_payment.php';
    exit();
}

// Configuration de la base de données PostgreSQL
$host = 'localhost';
$port = '5432';
$dbname = 'dons_database';
$username = 'postgres';
$password = '0000';

try {
    // Connexion à PostgreSQL
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la table payments si elle n'existe pas
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS payments (
            id SERIAL PRIMARY KEY,
            amount DECIMAL(10,2) NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            network VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createTableSQL);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données',
        'error' => $e->getMessage()
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Endpoint pour sauvegarder un paiement
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log des données reçues pour debug
    error_log('Données de paiement reçues: ' . json_encode($input));
    
    // Validation des données
    if (empty($input['amount']) || empty($input['phone_number'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Montant et numéro de téléphone requis',
            'error' => 'Données manquantes',
            'received_data' => $input
        ]);
        exit();
    }
    
    try {
        // Insérer le paiement dans la base de données
        $stmt = $pdo->prepare("
            INSERT INTO payments (amount, phone_number, network, status) 
            VALUES (?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $input['amount'],
            $input['phone_number'],
            $input['network'] ?? 'MTN',
            'completed'
        ]);
        
        if ($result) {
            $payment_id = $pdo->lastInsertId();
            
            $response = [
                'success' => true,
                'data' => [
                    'id' => $payment_id,
                    'amount' => $input['amount'],
                    'phone_number' => $input['phone_number'],
                    'network' => $input['network'] ?? 'MTN',
                    'status' => 'completed',
                    'timestamp' => date('c')
                ],
                'message' => 'Paiement enregistré avec succès dans la base de données',
                'reference' => $payment_id
            ];
            
            echo json_encode($response);
        } else {
            throw new Exception('Erreur lors de l\'insertion');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement du paiement',
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

// Endpoint pour récupérer les paiements
if ($method === 'GET') {
    try {
        $stmt = $pdo->query("
            SELECT id, amount, phone_number, network, status, created_at 
            FROM payments 
            ORDER BY created_at DESC
        ");
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $payments,
            'message' => 'Paiements récupérés avec succès',
            'count' => count($payments)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la récupération des paiements',
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

// Endpoint par défaut
http_response_code(405);
echo json_encode([
    'success' => false,
    'message' => 'Méthode non autorisée',
    'error' => 'Seules les méthodes GET et POST sont autorisées'
]);
?>