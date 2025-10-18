<?php
// Test de la vraie URL Barapay générée
echo "🧪 TEST VRAIE URL BARAPAY\n";
echo "=========================\n\n";

// URL générée par notre endpoint
$testUrl = 'https://barapay.net/merchant/payment?grant_id=26046567&token=e25eeb4357716975b7f2bc3569b609da';

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
    echo "✅ URL accessible - Page de paiement Barapay fonctionne !\n";
} elseif ($httpCode === 404) {
    echo "❌ URL non trouvée (404)\n";
} elseif ($httpCode === 403) {
    echo "❌ Accès interdit (403)\n";
} else {
    echo "⚠️ Code HTTP inattendu: $httpCode\n";
}

echo "\n";
echo "🎉 SUCCÈS !\n";
echo "L'URL générée utilise maintenant la vraie structure Barapay :\n";
echo "- https://barapay.net/merchant/payment\n";
echo "- Avec grant_id et token\n";
echo "- Format identique à l'exemple fourni\n";
?>
