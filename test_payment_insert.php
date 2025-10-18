<?php
// Test d'insertion d'un paiement
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Charger l'application Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST D'INSERTION D'UN PAIEMENT ===\n\n";

try {
    // Test de connexion
    echo "1. Test de connexion à PostgreSQL...\n";
    $connection = DB::connection();
    echo "✅ Connexion réussie\n\n";
    
    // Test d'insertion
    echo "2. Test d'insertion d'un paiement...\n";
    $now = date('Y-m-d H:i:s');
    $payment_reference = 'REF' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    
    $payment_id = DB::table('payments')->insertGetId([
        'contribution_id' => null,
        'payment_reference' => $payment_reference,
        'amount' => 15000.00,
        'payment_method' => 'orange_money',
        'phone_number' => '0701234567',
        'status' => 'completed',
        'gateway_response' => json_encode(['status' => 'success']),
        'processed_at' => $now,
        'created_at' => $now,
        'updated_at' => $now
    ]);
    
    echo "✅ Paiement inséré avec l'ID: $payment_id\n";
    echo "✅ Référence: $payment_reference\n\n";
    
    // Vérifier l'insertion
    echo "3. Vérification de l'insertion...\n";
    $payment = DB::table('payments')->where('id', $payment_id)->first();
    if ($payment) {
        echo "✅ Paiement trouvé dans la base de données\n";
        echo "   - ID: {$payment->id}\n";
        echo "   - Référence: {$payment->payment_reference}\n";
        echo "   - Montant: {$payment->amount} FCFA\n";
        echo "   - Statut: {$payment->status}\n";
        echo "   - Méthode: {$payment->payment_method}\n";
        echo "   - Téléphone: {$payment->phone_number}\n";
    } else {
        echo "❌ Paiement non trouvé\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
}
?>
