<?php
// Test pour vérifier les paiements de la vraie base de données
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Charger l'application Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST DES PAIEMENTS DE LA VRAIE BASE DE DONNÉES ===\n\n";

try {
    // Test de connexion
    echo "1. Test de connexion à PostgreSQL...\n";
    $connection = DB::connection();
    echo "✅ Connexion réussie\n\n";
    
    // Récupérer les paiements
    echo "2. Récupération des paiements...\n";
    $payments = DB::table('payments')->get();
    echo "Nombre de paiements trouvés : " . $payments->count() . "\n\n";
    
    if ($payments->count() > 0) {
        echo "Paiements :\n";
        foreach ($payments as $payment) {
            echo "  - ID: {$payment->id}, Montant: {$payment->amount}, Statut: {$payment->status}, Méthode: {$payment->payment_method}\n";
        }
    }
    
    // Test avec jointure
    echo "\n3. Test avec jointure users...\n";
    $payments_with_users = DB::table('payments')
        ->leftJoin('users', 'payments.user_id', '=', 'users.id')
        ->select(
            'payments.id',
            'payments.amount',
            'payments.status',
            'payments.payment_method',
            'payments.payment_reference',
            'payments.created_at',
            DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as user_name")
        )
        ->get();
    
    echo "Paiements avec utilisateurs :\n";
    foreach ($payments_with_users as $payment) {
        echo "  - ID: {$payment->id}, Montant: {$payment->amount}, Utilisateur: {$payment->user_name}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
}
?>
