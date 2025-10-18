<?php
// Script de test pour vérifier l'intégration complète
echo "🧪 TEST D'INTÉGRATION BARAPAY\n";
echo "============================\n\n";

// Test 1: Vérifier que le serveur backend fonctionne
echo "1️⃣ Test du serveur backend...\n";
$testUrl = 'http://localhost:8001/test_simple.php';
$response = file_get_contents($testUrl);
if ($response) {
    echo "✅ Serveur backend fonctionne\n";
} else {
    echo "❌ Serveur backend ne répond pas\n";
    exit(1);
}

// Test 2: Test de l'endpoint mock
echo "\n2️⃣ Test de l'endpoint mock...\n";
$mockData = json_encode([
    'amount' => 1000,
    'phone_number' => '+225 0701234567',
    'network' => 'mtn'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8001/api_barapay_mock.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $mockData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if ($result['success']) {
        echo "✅ Endpoint mock fonctionne\n";
        echo "   URL générée: " . $result['checkout_url'] . "\n";
    } else {
        echo "❌ Endpoint mock échoue: " . $result['message'] . "\n";
    }
} else {
    echo "❌ Endpoint mock ne répond pas (HTTP $httpCode)\n";
}

// Test 3: Vérifier la base de données
echo "\n3️⃣ Test de la base de données...\n";
try {
    $host = 'localhost';
    $port = '5432';
    $dbname = 'dons_database';
    $username = 'postgres';
    $password = '0000';
    
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM payments");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Base de données accessible\n";
    echo "   Nombre de paiements: " . $result['count'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur base de données: " . $e->getMessage() . "\n";
}

echo "\n🎉 Tests terminés!\n";
echo "Pour tester le frontend, allez sur http://localhost:3000\n";
echo "et suivez le flux: Accueil → Réseau → Numéro → Montant → Valider\n";
?>
