<?php

/**
 * Callback Barapay - Traitement des retours de paiement
 * URL de callback configurée dans votre compte Barapay
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

// Inclure l'intégration Barapay
require_once 'barapay_simple_integration.php';

// Fonction pour logger les callbacks
function logCallback($message, $data = []) {
    $logFile = __DIR__ . '/logs/barapay_callbacks.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    error_log(date('Y-m-d H:i:s') . " - $message - " . json_encode($data) . "\n", 3, $logFile);
}

// Fonction pour mettre à jour le statut d'un paiement en base de données
function updatePaymentStatus($orderNo, $status, $transactionId = null, $reason = null) {
    try {
        // Configuration de la base de données
        $host = 'localhost';
        $dbname = 'dons_database';
        $username = 'postgres';
        $password = '0000';
        
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Mettre à jour le statut du paiement
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET status = ?, transaction_id = ?, reason = ?, updated_at = NOW()
            WHERE order_no = ?
        ");
        
        $result = $stmt->execute([$status, $transactionId, $reason, $orderNo]);
        
        if ($result) {
            logCallback("Statut mis à jour", [
                'order_no' => $orderNo,
                'status' => $status,
                'transaction_id' => $transactionId,
                'reason' => $reason
            ]);
            return true;
        } else {
            logCallback("Erreur mise à jour statut", ['order_no' => $orderNo]);
            return false;
        }
        
    } catch (PDOException $e) {
        logCallback("Erreur base de données", [
            'error' => $e->getMessage(),
            'order_no' => $orderNo
        ]);
        return false;
    }
}

// Fonction pour envoyer un email de confirmation
function sendConfirmationEmail($orderNo, $transactionId, $amount, $currency, $phoneNumber) {
    // TODO: Implémenter l'envoi d'email
    // Pour l'instant, on log juste l'information
    logCallback("Email de confirmation à envoyer", [
        'order_no' => $orderNo,
        'transaction_id' => $transactionId,
        'amount' => $amount,
        'currency' => $currency,
        'phone' => $phoneNumber
    ]);
}

try {
    // Récupérer les données brutes du callback
    $rawData = file_get_contents('php://input');
    
    // Fonction pour récupérer les headers (compatible avec tous les environnements)
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    } else {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
    }
    
    // Logger les données reçues
    logCallback("Callback reçu", [
        'headers' => $headers,
        'raw_data' => $rawData,
        'method' => $_SERVER['REQUEST_METHOD'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    // Vérifier que c'est une requête POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    // Décoder les données JSON
    if (empty($rawData)) {
        throw new Exception('Aucune donnée reçue');
    }
    
    $data = json_decode($rawData, true, 512, JSON_THROW_ON_ERROR);
    
    // Vérifier la signature si présente
    if (isset($headers['X-Bpay-Signature'])) {
        $signature = $headers['X-Bpay-Signature'];
        $expectedSignature = hash_hmac('sha256', $rawData, BARAPAY_CLIENT_SECRET);
        
        if ($signature !== $expectedSignature) {
            logCallback("Signature invalide", [
                'received' => $signature,
                'expected' => $expectedSignature
            ]);
            throw new Exception('Signature invalide');
        }
        
        logCallback("Signature vérifiée avec succès");
    }

    // Traiter la réponse selon le statut
    if (!isset($data['status'])) {
        throw new Exception('Statut manquant dans la réponse');
    }

    $orderNo = $data['orderNo'] ?? null;
    $transactionId = $data['transactionId'] ?? null;
    $amount = $data['amount'] ?? null;
    $currency = $data['currency'] ?? null;

    switch ($data['status']) {
        case 'success':
            // Paiement réussi
            logCallback("Paiement réussi", [
                'order_no' => $orderNo,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'currency' => $currency
            ]);
            
            // Mettre à jour la base de données
            updatePaymentStatus($orderNo, 'paid', $transactionId);
            
            // Envoyer email de confirmation
            if ($orderNo && $transactionId && $amount && $currency) {
                sendConfirmationEmail($orderNo, $transactionId, $amount, $currency, '');
            }
            
            http_response_code(200);
            echo json_encode([
                'status' => 'success', 
                'message' => 'Paiement traité avec succès',
                'order_no' => $orderNo,
                'transaction_id' => $transactionId
            ]);
            break;

        case 'failed':
            // Paiement échoué
            $reason = $data['reason'] ?? 'Raison inconnue';
            
            logCallback("Paiement échoué", [
                'order_no' => $orderNo,
                'reason' => $reason,
                'amount' => $amount,
                'currency' => $currency
            ]);
            
            // Mettre à jour la base de données
            updatePaymentStatus($orderNo, 'failed', null, $reason);
            
            http_response_code(200);
            echo json_encode([
                'status' => 'error', 
                'message' => 'Paiement échoué',
                'order_no' => $orderNo,
                'reason' => $reason
            ]);
            break;

        case 'pending':
            // Paiement en attente
            logCallback("Paiement en attente", [
                'order_no' => $orderNo,
                'amount' => $amount,
                'currency' => $currency
            ]);
            
            // Mettre à jour la base de données
            updatePaymentStatus($orderNo, 'pending');
            
            http_response_code(200);
            echo json_encode([
                'status' => 'pending', 
                'message' => 'Paiement en attente',
                'order_no' => $orderNo
            ]);
            break;

        default:
            throw new Exception('Statut de paiement inconnu: ' . $data['status']);
    }

} catch (Exception $e) {
    logCallback("Erreur inattendue", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Erreur interne du serveur'
    ]);
}