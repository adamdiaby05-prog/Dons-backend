<?php
// Script de test pour l'API
$url = 'http://localhost:8000/api_save_payment_simple.php';
$data = [
    'amount' => 1000,
    'phone_number' => '0701234567',
    'network' => 'MTN'
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

echo "Réponse du serveur:\n";
echo $result . "\n";
?>
