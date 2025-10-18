<?php
// Script pour afficher tous les paiements de la base de données
$host = 'localhost';
$port = '5432';
$dbname = 'dons_database';
$username = 'postgres';
$password = '0000';

try {
    // Connexion à PostgreSQL
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🗄️  BASE DE DONNÉES POSTGRESQL - PAIEMENTS DONS\n";
    echo "================================================\n\n";
    
    // Récupérer tous les paiements
    $stmt = $pdo->query("
        SELECT id, amount, phone_number, network, status, created_at 
        FROM payments 
        ORDER BY created_at DESC
    ");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($payments)) {
        echo "❌ Aucun paiement trouvé dans la base de données.\n";
        echo "💡 Les paiements apparaîtront ici après validation sur le site.\n";
    } else {
        echo "📊 TOTAL DES PAIEMENTS: " . count($payments) . "\n\n";
        
        foreach ($payments as $payment) {
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "🆔 ID: " . $payment['id'] . "\n";
            echo "💰 MONTANT: " . number_format($payment['amount'], 0, ',', ' ') . " FCFA\n";
            echo "📱 TÉLÉPHONE: " . $payment['phone_number'] . "\n";
            echo "🌐 RÉSEAU: " . $payment['network'] . "\n";
            echo "✅ STATUT: " . $payment['status'] . "\n";
            echo "📅 DATE: " . date('d/m/Y H:i:s', strtotime($payment['created_at'])) . "\n";
            echo "\n";
        }
        
        // Calculer le total des montants
        $totalStmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
        $total = $totalStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "💎 TOTAL COLLECTÉ: " . number_format($total['total'], 0, ',', ' ') . " FCFA\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERREUR DE CONNEXION: " . $e->getMessage() . "\n";
    echo "💡 Vérifiez que PostgreSQL est démarré et que la base 'dons_database' existe.\n";
}
?>
