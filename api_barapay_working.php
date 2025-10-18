<?php
// Endpoint de travail pour Barapay (version fonctionnelle)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With, X-CSRF-TOKEN');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Récupérer les données du paiement
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['amount']) || empty($input['phone_number']) || empty($input['network'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Montant, numéro de téléphone et réseau requis'
        ]);
        exit();
    }
    
    $amount = floatval($input['amount']);
    $phoneNumber = $input['phone_number'];
    $network = $input['network'];
    
    // Générer un numéro de commande unique
    $orderNo = 'DONS' . time() . rand(1000, 9999);
    
    // Pour l'instant, nous allons simuler l'URL de paiement
    // En production, vous devrez utiliser la vraie API Barapay
    $checkoutUrl = 'https://barapay.net/checkout?order=' . $orderNo . '&amount=' . $amount . '&currency=XOF';
    
    // Enregistrer le paiement en attente dans la base de données
    try {
        $host = 'localhost';
        $port = '5432';
        $dbname = 'dons_database';
        $username = 'postgres';
        $password = '0000';
        
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("INSERT INTO payments (amount, phone_number, network, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$amount, $phoneNumber, $network, 'pending']);
        
    } catch (PDOException $e) {
        error_log('Erreur DB: ' . $e->getMessage());
    }
    
    // Retourner l'URL de paiement
    echo json_encode([
        'success' => true,
        'checkout_url' => $checkoutUrl,
        'order_no' => $orderNo,
        'message' => 'Lien de paiement généré avec succès',
        'note' => 'URL de test - En production, utilisez la vraie API Barapay'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la génération du lien de paiement',
        'error' => $e->getMessage()
    ]);
}
?>
