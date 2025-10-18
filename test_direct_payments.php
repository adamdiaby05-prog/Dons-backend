<?php
// Test direct des paiements dans la base de données
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Charger l'application Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST DIRECT DES PAIEMENTS ===\n\n";

try {
    // 1. Créer un nouveau paiement
    echo "1. Création d'un nouveau paiement...\n";
    $payment_reference = 'REF' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    
    // Récupérer une contribution existante
    $contribution = DB::table('contributions')->first();
    $contribution_id = $contribution ? $contribution->id : 1;
    
    $now = date('Y-m-d H:i:s');
    $payment_id = DB::table('payments')->insertGetId([
        'contribution_id' => $contribution_id,
        'payment_reference' => $payment_reference,
        'amount' => 30000.00,
        'payment_method' => 'orange_money',
        'phone_number' => '0701234570',
        'status' => 'completed',
        'gateway_response' => json_encode(['status' => 'success']),
        'processed_at' => $now,
        'created_at' => $now,
        'updated_at' => $now
    ]);
    
    echo "✅ Paiement créé avec l'ID: $payment_id\n";
    echo "✅ Référence: $payment_reference\n\n";
    
    // 2. Récupérer tous les paiements
    echo "2. Récupération de tous les paiements...\n";
    $payments = DB::table('payments')
        ->orderBy('created_at', 'desc')
        ->get();
    
    echo "✅ Nombre total de paiements: " . $payments->count() . "\n\n";
    
    foreach ($payments as $payment) {
        echo "  - ID: {$payment->id}\n";
        echo "    Référence: {$payment->payment_reference}\n";
        echo "    Montant: {$payment->amount} FCFA\n";
        echo "    Statut: {$payment->status}\n";
        echo "    Méthode: {$payment->payment_method}\n";
        echo "    Téléphone: {$payment->phone_number}\n";
        echo "    Date: {$payment->created_at}\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
}
?>
