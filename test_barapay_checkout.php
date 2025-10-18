<?php
// Test de l'URL de checkout Barapay générée
header('Content-Type: application/json');

// Configuration Barapay RÉELLE
define('BARAPAY_CLIENT_ID', 'wjb7lzQVialbcwMNTPD1IojrRzPIIl');
define('BARAPAY_CLIENT_SECRET', 'eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1');

// Simuler un paiement
$amount = 5000;
$phone = '123456789';
$reference = 'DONS-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

// URL de checkout selon la documentation Barapay
$checkoutUrl = 'https://barapay.net/checkout?' . http_build_query([
    'client_id' => BARAPAY_CLIENT_ID,
    'amount' => $amount,
    'currency' => 'XOF',
    'phone' => $phone,
    'ref' => $reference
]);

echo json_encode([
    'test' => 'URL de checkout Barapay générée',
    'checkout_url' => $checkoutUrl,
    'parameters' => [
        'client_id' => BARAPAY_CLIENT_ID,
        'amount' => $amount,
        'currency' => 'XOF',
        'phone' => $phone,
        'ref' => $reference
    ],
    'note' => 'Cette URL devrait fonctionner selon la documentation Barapay'
]);
?>

