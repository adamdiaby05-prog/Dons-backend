<?php
// Endpoint pour générer un lien de paiement Barapay et rediriger l'utilisateur
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

// Inclure le SDK Barapay
require_once __DIR__ . '/../frontend-react/bpay_sdk/php/vendor/autoload.php';

// Activer l'affichage des erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

use Bpay\Api\Payer;
use Bpay\Api\Amount;
use Bpay\Api\Transaction;
use Bpay\Api\RedirectUrls;
use Bpay\Api\Payment;

// Configuration des identifiants Barapay
$clientId = 'wjb7lzQVialbcwMNTPD1IojrRzPIIl';
$clientSecret = 'eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1';

// URL de base de votre domaine (à adapter selon votre configuration)
$baseUrl = 'http://localhost:3000'; // URL de votre frontend React

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
    
    // Créer l'objet Payer
    $payer = new Payer();
    $payer->setPaymentMethod('Bpay');
    
    // Créer l'objet Amount
    $amountIns = new Amount();
    $amountIns->setTotal($amount)->setCurrency('XOF'); // Devise XOF pour l'Afrique de l'Ouest
    
    // Créer l'objet Transaction
    $trans = new Transaction();
    $trans->setAmount($amountIns)
          ->setOrderNo('DONS' . time()); // Numéro unique de commande
    
    // Créer les URLs de redirection
    $urls = new RedirectUrls();
    $urls->setSuccessUrl($baseUrl . '/success') // URL de succès
         ->setCancelUrl($baseUrl . '/network'); // URL d'annulation
    
    // Créer l'objet Payment
    $payment = new Payment();
    $payment->setCredentials([
        'client_id' => $clientId,
        'client_secret' => $clientSecret
    ])->setRedirectUrls($urls)
      ->setPayer($payer)
      ->setTransaction($trans);
    
    // Créer le paiement et obtenir l'URL de redirection
    $payment->create();
    $checkoutUrl = $payment->getApprovedUrl();
    
    // Retourner l'URL de redirection
    echo json_encode([
        'success' => true,
        'checkout_url' => $checkoutUrl,
        'message' => 'Lien de paiement généré avec succès'
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
