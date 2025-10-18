<?php
// Test de l'URL Barapay générée
echo "🧪 TEST URL BARAPAY\n";
echo "==================\n\n";

// URL générée par notre endpoint
$testUrl = 'https://barapay.net/checkout?order=DONS17607652422673&amount=1000&currency=XOF';

echo "URL testée: $testUrl\n\n";

// Tester l'URL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request seulement

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
if ($curlError) {
    echo "Erreur cURL: $curlError\n";
}

if ($httpCode === 200) {
    echo "✅ URL accessible\n";
} elseif ($httpCode === 404) {
    echo "❌ URL non trouvée (404)\n";
} elseif ($httpCode === 403) {
    echo "❌ Accès interdit (403)\n";
} else {
    echo "⚠️ Code HTTP inattendu: $httpCode\n";
}

echo "\n";
echo "💡 SOLUTION:\n";
echo "L'URL générée est une URL de test. Pour la production, vous devez:\n";
echo "1. Contacter Barapay pour obtenir la vraie URL de l'API\n";
echo "2. Utiliser la vraie API avec les bons paramètres\n";
echo "3. Configurer les callbacks de retour\n";
?>
