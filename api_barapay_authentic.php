<?php

/**
 * API Barapay Authentic - Endpoint pour l'intégration Barapay réelle
 * Utilise le SDK Barapay officiel avec les credentials fournis
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Inclure l'intégration Barapay simplifiée
require_once 'barapay_simple_integration.php';

// Fonction pour logger les requêtes
function logRequest($message, $data = []) {
    $logFile = __DIR__ . '/logs/barapay_api.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    error_log(date('Y-m-d H:i:s') . " - $message - " . json_encode($data) . "\n", 3, $logFile);
}

// Fonction pour sauvegarder le paiement en base de données
function savePaymentToDatabase($paymentData) {
    try {
        // Configuration de la base de données
        $host = 'localhost';
        $dbname = 'dons_database';
        $username = 'postgres';
        $password = '0000';
        
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Insérer le paiement
        $stmt = $pdo->prepare("
            INSERT INTO payments (
                order_no, phone_number, network, amount, currency, 
                status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $paymentData['order_no'],
            $paymentData['phone_number'],
            $paymentData['network'],
            $paymentData['amount'],
            $paymentData['currency'],
            'pending'
        ]);
        
        logRequest("Paiement sauvegardé en base de données", $paymentData);
        return true;
        
    } catch (PDOException $e) {
        logRequest("Erreur base de données", ['error' => $e->getMessage()]);
        return false;
    }
}

// Fonction pour générer un numéro de commande unique
function generateOrderNumber() {
    return 'DONS_' . date('Ymd') . '_' . time() . '_' . rand(1000, 9999);
}

try {
    // Vérifier que c'est une requête POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Récupérer les données JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Données JSON invalides');
    }

    // Valider les données requises
    $requiredFields = ['amount', 'phone_number', 'network'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Champ requis manquant: $field");
        }
    }

    $amount = floatval($data['amount']);
    $phoneNumber = $data['phone_number'];
    $network = $data['network'];
    
    // Validation du montant
    if ($amount <= 0) {
        throw new Exception('Le montant doit être supérieur à 0');
    }
    
    if ($amount < 100) {
        throw new Exception('Le montant minimum est de 100 FCFA');
    }
    
    if ($amount > 1000000) {
        throw new Exception('Le montant maximum est de 1,000,000 FCFA');
    }

    // Générer un numéro de commande unique
    $orderNo = generateOrderNumber();
    
    // Barapay attend le montant en francs CFA (pas de conversion nécessaire)
    $amountInCents = intval($amount);
    
    // Déterminer la méthode de paiement selon le réseau
    $paymentMethodMap = [
        'mtn' => 'MTN',
        'moov' => 'MOOV', 
        'orange' => 'ORANGE',
        'wave' => 'Bpay'
    ];
    
    $paymentMethod = $paymentMethodMap[$network] ?? 'Bpay';
    
    logRequest("Tentative de création de paiement", [
        'order_no' => $orderNo,
        'amount' => $amount,
        'amount_cents' => $amountInCents,
        'phone' => $phoneNumber,
        'network' => $network,
        'payment_method' => $paymentMethod
    ]);

    // URLs de redirection
    $successUrl = 'http://localhost:3000/payment-success';
    $cancelUrl = 'http://localhost:3000/payment-cancel';
    
    // Créer le paiement avec Barapay
    $paymentUrl = createBarapayPayment(
        $amountInCents,     // Montant en centimes
        'XOF',              // Devise (Franc CFA)
        $orderNo,           // Numéro de commande
        $paymentMethod,     // Méthode de paiement
        $successUrl,        // URL de succès
        $cancelUrl          // URL d'annulation
    );

    // Préparer les données pour la sauvegarde
    $paymentData = [
        'order_no' => $orderNo,
        'phone_number' => $phoneNumber,
        'network' => $network,
        'amount' => $amount,
        'currency' => 'XOF',
        'payment_method' => $paymentMethod,
        'checkout_url' => $paymentUrl
    ];

    // Sauvegarder en base de données
    $saved = savePaymentToDatabase($paymentData);
    
    if (!$saved) {
        logRequest("Avertissement: Paiement créé mais non sauvegardé en base");
    }

    // Réponse de succès
    $response = [
        'success' => true,
        'message' => 'Paiement créé avec succès',
        'order_no' => $orderNo,
        'amount' => $amount,
        'currency' => 'XOF',
        'network' => $network,
        'phone_number' => $phoneNumber,
        'checkout_url' => $paymentUrl,
        'payment_method' => $paymentMethod,
        'saved_to_db' => $saved
    ];

    logRequest("Paiement créé avec succès", $response);
    
    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    logRequest("Erreur générale", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage(),
        'error_type' => 'server_error'
    ], JSON_PRETTY_PRINT);
}