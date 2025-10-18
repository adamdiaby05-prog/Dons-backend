<?php
// Script pour afficher les données de la table payments
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Configuration Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "    DONNÉES DE LA TABLE PAYMENTS\n";
echo "    Base de données: DONS PostgreSQL\n";
echo "========================================\n\n";

try {
    // Vérifier si la table existe
    $tableExists = DB::select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'payments')");
    
    if (!$tableExists[0]->exists) {
        echo "❌ La table 'payments' n'existe pas dans la base de données.\n";
        exit;
    }

    // Compter le nombre total de paiements
    $totalPayments = DB::table('payments')->count();
    echo "📊 Nombre total de paiements: {$totalPayments}\n\n";

    if ($totalPayments > 0) {
        // Afficher tous les paiements avec le code "0000"
        echo "🔍 PAIEMENTS AVEC LE CODE '0000':\n";
        echo "----------------------------------------\n";
        
        $payments0000 = DB::table('payments')
            ->where('payment_reference', '0000')
            ->get();
            
        if ($payments0000->count() > 0) {
            foreach ($payments0000 as $payment) {
                echo "ID: {$payment->id}\n";
                echo "Référence: {$payment->payment_reference}\n";
                echo "Montant: {$payment->amount} FCFA\n";
                echo "Méthode: {$payment->payment_method}\n";
                echo "Téléphone: {$payment->phone_number}\n";
                echo "Statut: {$payment->status}\n";
                echo "Créé le: {$payment->created_at}\n";
                if ($payment->processed_at) {
                    echo "Traité le: {$payment->processed_at}\n";
                }
                echo "----------------------------------------\n";
            }
        } else {
            echo "Aucun paiement trouvé avec le code '0000'\n";
        }

        // Afficher les 5 derniers paiements
        echo "\n📋 5 DERNIERS PAIEMENTS:\n";
        echo "----------------------------------------\n";
        
        $recentPayments = DB::table('payments')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        foreach ($recentPayments as $payment) {
            echo "• {$payment->payment_reference} | {$payment->amount} FCFA | {$payment->status} | {$payment->payment_method}\n";
        }

        // Statistiques par statut
        echo "\n📈 STATISTIQUES PAR STATUT:\n";
        echo "----------------------------------------\n";
        
        $statusStats = DB::table('payments')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();
            
        foreach ($statusStats as $stat) {
            echo "• {$stat->status}: {$stat->count} paiements\n";
        }

        // Statistiques par méthode de paiement
        echo "\n💳 STATISTIQUES PAR MÉTHODE:\n";
        echo "----------------------------------------\n";
        
        $methodStats = DB::table('payments')
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();
            
        foreach ($methodStats as $stat) {
            echo "• {$stat->payment_method}: {$stat->count} paiements\n";
        }

    } else {
        echo "📭 Aucun paiement trouvé dans la table.\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "    FIN DE L'AFFICHAGE\n";
echo "========================================\n";
?>
