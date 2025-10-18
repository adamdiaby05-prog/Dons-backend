<?php
// Test de la structure JSON pour l'application Flutter
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Simuler une réponse de paiement avec la structure attendue par Flutter
$payment_data = [
    'id' => 'PAY_' . uniqid(),
    'amount' => 102,
    'phone_number' => '237123456789',
    'network' => 'MTN',
    'status' => 'pending',
    'timestamp' => date('c'),
    'message' => 'Paiement initié avec succès'
];

// Structure JSON corrigée pour Flutter
$response = [
    'success' => true,
    'data' => $payment_data,
    'payment' => [
        'id' => $payment_data['id'],
        'payment_reference' => $payment_data['id'],
        'status' => $payment_data['status'],
        'amount' => $payment_data['amount'],
        'phone_number' => $payment_data['phone_number'],
        'network' => $payment_data['network'],
        'created_at' => $payment_data['timestamp']
    ],
    'message' => 'Paiement initié avec succès',
    'reference' => $payment_data['id']
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>

