<?php

/**
 * Intégration Barapay - Backend
 * Appels directs à l'API Barapay
 */

// Configuration des credentials Barapay
define('BARAPAY_CLIENT_ID', 'wjb7lzQVialbcwMNTPD1IojrRzPIIl');
define('BARAPAY_CLIENT_SECRET', 'eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1');
define('BARAPAY_BASE_URL', 'https://barapay.net');

/**
 * Fonction pour créer un paiement Barapay
 * 
 * @param float $amount Montant du paiement en centimes
 * @param string $currency Devise (XOF, USD, EUR, etc.)
 * @param string $orderNo Numéro de commande unique
 * @param string $paymentMethod Méthode de paiement (Bpay, Orange, MTN, Wave, etc.)
 * @param string $successUrl URL de succès
 * @param string $cancelUrl URL d'annulation
 * @return string URL de redirection vers le paiement
 * @throws Exception
 */
function createBarapayPayment($amount, $currency = 'XOF', $orderNo = null, $paymentMethod = 'Bpay', $successUrl = null, $cancelUrl = null) {
    try {
        // URLs par défaut
        if (!$successUrl) {
            $successUrl = 'http://localhost:3000/payment-success';
        }
        if (!$cancelUrl) {
            $cancelUrl = 'http://localhost:3000/payment-cancel';
        }
        
        // Générer un numéro de commande si non fourni
        if (!$orderNo) {
            $orderNo = 'DONS_' . date('Ymd') . '_' . time() . '_' . rand(1000, 9999);
        }

        // Étape 1: Obtenir le token d'accès
        $accessToken = getBarapayAccessToken();
        
        // Étape 2: Créer la transaction
        $paymentUrl = createBarapayTransaction($accessToken, $amount, $currency, $orderNo, $paymentMethod, $successUrl, $cancelUrl);
        
        return $paymentUrl;

    } catch (Exception $e) {
        error_log("Erreur Barapay : " . $e->getMessage());
        throw $e;
    }
}

/**
 * Obtenir le token d'accès Barapay
 */
function getBarapayAccessToken() {
    $url = BARAPAY_BASE_URL . '/merchant/api/verify';
    
    $payload = [
        'client_id' => BARAPAY_CLIENT_ID,
        'client_secret' => BARAPAY_CLIENT_SECRET
    ];

    $response = makeBarapayRequest($url, 'POST', $payload);
    
    if (!isset($response['status']) || $response['status'] !== 'success' || !isset($response['data']['access_token'])) {
        $message = isset($response['message']) ? $response['message'] : 'Erreur d\'authentification inconnue';
        throw new Exception('Erreur d\'authentification Barapay: ' . $message);
    }

    return $response['data']['access_token'];
}

/**
 * Créer une transaction Barapay
 */
function createBarapayTransaction($accessToken, $amount, $currency, $orderNo, $paymentMethod, $successUrl, $cancelUrl) {
    $url = BARAPAY_BASE_URL . '/merchant/api/transaction-info';
    
    $payload = [
        'payer' => $paymentMethod,
        'amount' => $amount,
        'currency' => $currency,
        'successUrl' => $successUrl,
        'cancelUrl' => $cancelUrl,
        'orderNo' => $orderNo
    ];

    $headers = [
        'Authorization: Bearer ' . $accessToken
    ];

    $response = makeBarapayRequest($url, 'POST', $payload, $headers);
    
    if (!isset($response['status']) || $response['status'] !== 'success') {
        $message = isset($response['message']) ? $response['message'] : 'Erreur inconnue lors de la création du paiement';
        throw new Exception('Erreur de création de paiement: ' . $message);
    }

    if (!isset($response['data']['approvedUrl'])) {
        throw new Exception('L\'URL de paiement est manquante dans la réponse');
    }

    return $response['data']['approvedUrl'];
}

/**
 * Faire une requête HTTP vers l'API Barapay
 */
function makeBarapayRequest($url, $method = 'POST', $payload = [], $headers = []) {
    $ch = curl_init();
    
    $defaultHeaders = [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: BarapayIntegration/1.0 PHP/' . PHP_VERSION,
        'X-Requested-With: XMLHttpRequest'
    ];
    
    $headers = array_merge($defaultHeaders, $headers);
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers
    ];

    if ($method === 'POST' && !empty($payload)) {
        $options[CURLOPT_POSTFIELDS] = json_encode($payload);
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);

    if ($error) {
        throw new Exception("Erreur cURL: $error");
    }

    if ($response === false) {
        throw new Exception('Réponse invalide de l\'API Barapay');
    }

    if ($httpCode >= 400) {
        throw new Exception("Erreur HTTP $httpCode. Réponse: " . $response);
    }

    $jsonResponse = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Erreur de décodage JSON: ' . json_last_error_msg() . '. Réponse brute: ' . $response);
    }

    return $jsonResponse;
}

/**
 * Fonction pour traiter le callback de paiement
 */
function handlePaymentCallback() {
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
        
        // Logger les données reçues (debug)
        error_log("Callback Barapay reçu - Headers: " . json_encode($headers) . " - Data: " . $rawData);

        // Décoder les données JSON
        $data = json_decode($rawData, true, 512, JSON_THROW_ON_ERROR);
        
        // Vérifier la signature si présente
        if (isset($headers['X-Bpay-Signature'])) {
            $signature = $headers['X-Bpay-Signature'];
            $expectedSignature = hash_hmac('sha256', $rawData, BARAPAY_CLIENT_SECRET);
            
            if ($signature !== $expectedSignature) {
                throw new Exception('Signature invalide');
            }
        }

        // Traiter la réponse selon le statut
        if (!isset($data['status'])) {
            throw new Exception('Statut manquant dans la réponse');
        }

        $result = [
            'status' => $data['status'],
            'orderNo' => $data['orderNo'] ?? null,
            'transactionId' => $data['transactionId'] ?? null,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? null
        ];

        switch ($data['status']) {
            case 'success':
                error_log("Paiement réussi - Order: " . $result['orderNo'] . " - Transaction: " . $result['transactionId']);
                break;

            case 'failed':
                $result['reason'] = $data['reason'] ?? 'Raison inconnue';
                error_log("Paiement échoué - Order: " . $result['orderNo'] . " - Raison: " . $result['reason']);
                break;

            case 'pending':
                error_log("Paiement en attente - Order: " . $result['orderNo']);
                break;

            default:
                throw new Exception('Statut de paiement inconnu: ' . $data['status']);
        }

        return $result;

    } catch (Exception $e) {
        error_log("Erreur dans callback: " . $e->getMessage());
        throw $e;
    }
}
