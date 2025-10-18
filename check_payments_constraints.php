<?php
// Vérifier les contraintes de la table payments
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Charger l'application Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CONTRAINTES DE LA TABLE PAYMENTS ===\n\n";

try {
    // Vérifier les contraintes NOT NULL
    $constraints = DB::select("
        SELECT column_name, is_nullable, data_type 
        FROM information_schema.columns 
        WHERE table_name = 'payments' 
        ORDER BY ordinal_position
    ");
    
    echo "Colonnes de la table payments :\n";
    foreach ($constraints as $constraint) {
        $nullable = $constraint->is_nullable === 'YES' ? 'NULL' : 'NOT NULL';
        echo "  - {$constraint->column_name} ({$constraint->data_type}) - {$nullable}\n";
    }
    
    // Vérifier s'il y a des contributions existantes
    echo "\nContributions existantes :\n";
    $contributions = DB::table('contributions')->limit(5)->get();
    foreach ($contributions as $contribution) {
        echo "  - ID: {$contribution->id}, Groupe: {$contribution->group_id}, User: {$contribution->user_id}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
?>
