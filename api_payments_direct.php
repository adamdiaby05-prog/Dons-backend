<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration de la base de données PostgreSQL
$host = 'localhost';
$port = '5432';
$dbname = 'dons';
$user = 'postgres';
$password = '0000';

try {
    // Connexion à PostgreSQL
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les paiements avec le code 0000
    $query = "SELECT 
        p.id,
        p.payment_reference,
        p.amount,
        p.payment_method,
        p.phone_number,
        p.status,
        p.created_at,
        COALESCE(g.name, 'Groupe par défaut') as group_name
    FROM payments p
    LEFT JOIN contributions c ON p.contribution_id = c.id
    LEFT JOIN groups g ON c.group_id = g.id
    ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour le frontend
    $formattedPayments = array_map(function($payment) {
        return [
            'id' => $payment['id'],
            'payment_reference' => $payment['payment_reference'],
            'amount' => floatval($payment['amount']),
            'payment_method' => $payment['payment_method'],
            'phone_number' => $payment['phone_number'],
            'status' => $payment['status'],
            'group_name' => $payment['group_name'],
            'created_at' => $payment['created_at']
        ];
    }, $payments);
    
    // Réponse JSON
    echo json_encode([
        'success' => true,
        'data' => $formattedPayments,
        'pagination' => [
            'current_page' => 1,
            'total_pages' => 1,
            'total_items' => count($formattedPayments)
        ],
        'message' => 'Paiements récupérés depuis PostgreSQL avec succès',
        'database' => 'PostgreSQL connecté',
        'code' => '0000'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de connexion à la base de données: ' . $e->getMessage(),
        'database' => 'PostgreSQL non connecté'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
