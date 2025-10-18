<?php
// Script de test pour l'endpoint de paiement
$url = 'http://192.168.1.7:8000/api/payments/initiate';

$data = [
    'amount' => 500,
    'phone_number' => '+22512345678',
    'network' => 'mtn_momo'
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Erreur lors de la requête\n";
} else {
    echo "Réponse reçue:\n";
    echo $result . "\n";
    
    // Décoder et afficher la structure
    $response = json_decode($result, true);
    echo "\nStructure de la réponse:\n";
    print_r($response);
}
?> 