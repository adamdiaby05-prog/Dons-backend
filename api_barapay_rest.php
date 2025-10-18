<?php
// Endpoint utilisant l'API REST Barapay directement (sans SDK)
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

// Configuration des identifiants Barapay
$clientId = 'wjb7lzQVialbcwMNTPD1IojrRzPIIl';
$clientSecret = 'eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1';

// URL de base de votre domaine
$baseUrl = 'http://localhost:3000';

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
    
    // Données pour l'API Barapay
    $paymentData = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'amount' => $amount,
        'currency' => 'XOF',
        'order_no' => $orderNo,
        'payment_method' => 'Bpay',
        'success_url' => $baseUrl . '/success',
        'cancel_url' => $baseUrl . '/network',
        'phone_number' => $phoneNumber,
        'network' => $network,
        'description' => 'Don pour la campagne électorale'
    ];
    
    // URL de l'API Barapay (URL réelle)
    $barapayApiUrl = 'https://barapay.net/api/v1/payments/create';
    
    // Envoyer la requête à l'API Barapay
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $barapayApiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $clientSecret,
        'X-Client-ID: ' . $clientId
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception('Erreur cURL: ' . $curlError);
    }
    
    // Log de la réponse pour debug
    error_log("Barapay API Response (HTTP $httpCode): " . $response);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        
        if (isset($result['checkout_url']) || isset($result['payment_url']) || isset($result['redirect_url'])) {
            $checkoutUrl = $result['checkout_url'] ?? $result['payment_url'] ?? $result['redirect_url'];
            
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
            
            echo json_encode([
                'success' => true,
                'checkout_url' => $checkoutUrl,
                'order_no' => $orderNo,
                'message' => 'Lien de paiement généré avec succès'
            ]);
        } else {
            throw new Exception('URL de paiement non trouvée dans la réponse: ' . $response);
        }
    } else {
        throw new Exception('Erreur API Barapay (HTTP ' . $httpCode . '): ' . $response);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la génération du lien de paiement',
        'error' => $e->getMessage()
    ]);
}
?>
