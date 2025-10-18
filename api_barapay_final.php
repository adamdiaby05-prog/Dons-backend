<?php
// Endpoint final pour Barapay utilisant le SDK correctement
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

// Activer l'affichage des erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure le SDK Barapay
require_once __DIR__ . '/../frontend-react/bpay_sdk/php/vendor/autoload.php';

use Bpay\Api\Payer;
use Bpay\Api\Amount;
use Bpay\Api\Transaction;
use Bpay\Api\RedirectUrls;
use Bpay\Api\Payment;
use Bpay\Exception\BpayException;

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
    
    // 1. Créer l'objet Amount (montant et devise)
    $amountObj = new Amount();
    $amountObj->setTotal($amount)->setCurrency('XOF'); // Devise XOF pour l'Afrique de l'Ouest
    
    // 2. Créer l'objet Transaction avec le numéro de commande
    $transaction = new Transaction();
    $transaction->setAmount($amountObj)
                ->setOrderNo('DONS' . time() . rand(1000, 9999)); // Numéro unique de commande
    
    // 3. Définir la méthode de paiement
    $payer = new Payer();
    $payer->setPaymentMethod('Bpay'); // Utiliser 'Bpay' comme dans la documentation
    
    // 4. Configurer les URLs de redirection
    $redirectUrls = new RedirectUrls();
    $redirectUrls->setSuccessUrl($baseUrl . '/success')
                 ->setCancelUrl($baseUrl . '/network');
    
    // 5. Créer et configurer le paiement
    $payment = new Payment();
    $payment->setCredentials([
        'client_id' => $clientId,
        'client_secret' => $clientSecret
    ])
    ->setPayer($payer)
    ->setTransaction($transaction)
    ->setRedirectUrls($redirectUrls);
    
    // 6. Créer le paiement
    $payment->create();
    
    // 7. Récupérer l'URL de paiement
    $approvedUrl = $payment->getApprovedUrl();
    
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
        'checkout_url' => $approvedUrl,
        'order_no' => $transaction->getOrderNo(),
        'message' => 'Lien de paiement généré avec succès'
    ]);
    
} catch (BpayException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur Barapay: ' . $e->getMessage(),
        'error' => $e->getMessage()
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
