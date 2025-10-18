<?php
// Test simple de l'API Barapay
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🧪 TEST API BARAPAY SIMPLE\n";
echo "==========================\n\n";

// Configuration des identifiants Barapay
$clientId = 'wjb7lzQVialbcwMNTPD1IojrRzPIIl';
$clientSecret = 'eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1';

// Données de test
$paymentData = [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'amount' => 1000,
    'currency' => 'XOF',
    'order_no' => 'TEST' . time(),
    'payment_method' => 'Bpay',
    'success_url' => 'http://localhost:3000/success',
    'cancel_url' => 'http://localhost:3000/network',
    'phone_number' => '+225 0701234567',
    'network' => 'mtn',
    'description' => 'Test de paiement'
];

echo "Données envoyées:\n";
echo json_encode($paymentData, JSON_PRETTY_PRINT) . "\n\n";

// URL de l'API Barapay
$barapayApiUrl = 'https://barapay.net/api/v1/payments/create';

echo "URL de l'API: $barapayApiUrl\n\n";

// Envoyer la requête
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
curl_setopt($ch, CURLOPT_VERBOSE, true);

echo "Envoi de la requête...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
if ($curlError) {
    echo "Erreur cURL: $curlError\n";
}
echo "Réponse:\n";
echo $response . "\n";
?>
