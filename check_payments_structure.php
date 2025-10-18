<?php
// Vérifier la structure de la table payments
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Charger l'application Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== STRUCTURE DE LA TABLE PAYMENTS ===\n\n";

try {
    // Vérifier les colonnes de la table payments
    $columns = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'payments'");
    
    echo "Colonnes de la table payments :\n";
    foreach ($columns as $column) {
        echo "  - {$column->column_name} ({$column->data_type})\n";
    }
    
    echo "\nDonnées de la table payments :\n";
    $payments = DB::table('payments')->get();
    foreach ($payments as $payment) {
        echo "  - ID: {$payment->id}\n";
        foreach ($payment as $key => $value) {
            echo "    {$key}: {$value}\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
?>
