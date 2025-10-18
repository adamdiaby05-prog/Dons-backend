<?php
// Test de la requête SQL pour les paiements
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Charger l'application Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST DE LA REQUÊTE PAIEMENTS ===\n\n";

try {
    echo "1. Test de la requête avec jointures...\n";
    
    $payments = DB::table('payments')
        ->leftJoin('contributions', 'payments.contribution_id', '=', 'contributions.id')
        ->leftJoin('users', 'contributions.user_id', '=', 'users.id')
        ->select(
            'payments.id',
            'payments.amount',
            'payments.status',
            'payments.payment_method',
            'payments.payment_reference',
            'payments.phone_number',
            'payments.created_at',
            DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as user_name")
        )
        ->orderBy('payments.created_at', 'desc')
        ->get();
    
    echo "Nombre de paiements trouvés : " . $payments->count() . "\n\n";
    
    foreach ($payments as $payment) {
        echo "Paiement ID: {$payment->id}\n";
        echo "  Montant: {$payment->amount} FCFA\n";
        echo "  Statut: {$payment->status}\n";
        echo "  Méthode: {$payment->payment_method}\n";
        echo "  Référence: {$payment->payment_reference}\n";
        echo "  Téléphone: {$payment->phone_number}\n";
        echo "  Utilisateur: " . (trim($payment->user_name) ?: 'Utilisateur inconnu') . "\n";
        echo "  Créé le: {$payment->created_at}\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
}
?>
