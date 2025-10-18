<?php
// Test simple du SDK Barapay
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test du SDK Barapay...\n";

try {
    // Vérifier si le fichier autoload existe
    $autoloadPath = __DIR__ . '/../frontend-react/bpay_sdk/php/vendor/autoload.php';
    echo "Chemin autoload: $autoloadPath\n";
    
    if (file_exists($autoloadPath)) {
        echo "✅ Fichier autoload trouvé\n";
        require_once $autoloadPath;
        echo "✅ Autoload chargé\n";
        
        // Tester les classes
        if (class_exists('Bpay\Api\Payer')) {
            echo "✅ Classe Payer trouvée\n";
        } else {
            echo "❌ Classe Payer non trouvée\n";
        }
        
        if (class_exists('Bpay\Api\Amount')) {
            echo "✅ Classe Amount trouvée\n";
        } else {
            echo "❌ Classe Amount non trouvée\n";
        }
        
    } else {
        echo "❌ Fichier autoload non trouvé\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
?>
