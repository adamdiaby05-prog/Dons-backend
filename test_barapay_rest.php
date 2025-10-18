<?php
// Test de l'endpoint Barapay REST
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🧪 TEST ENDPOINT BARAPAY REST\n";
echo "=============================\n\n";

// Simuler les données POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Simuler le contenu JSON
$testData = [
    'amount' => 1000,
    'phone_number' => '+225 0701234567',
    'network' => 'mtn'
];

// Simuler php://input
$GLOBALS['test_input'] = json_encode($testData);

// Redéfinir file_get_contents pour simuler l'input
function mock_file_get_contents($filename) {
    if ($filename === 'php://input') {
        return $GLOBALS['test_input'];
    }
    return file_get_contents($filename);
}

// Inclure l'endpoint
ob_start();
include 'api_barapay_rest.php';
$output = ob_get_clean();

echo "Réponse de l'endpoint:\n";
echo $output . "\n";
?>
