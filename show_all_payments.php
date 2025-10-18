<?php
// Script pour afficher TOUS les paiements de la table
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Configuration Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "    TOUS LES PAIEMENTS TROUVÉS\n";
echo "    Base de données: DONS PostgreSQL\n";
echo "========================================\n\n";

try {
    // Récupérer TOUS les paiements
    $allPayments = DB::table('payments')
        ->orderBy('created_at', 'desc')
        ->get();

    echo "📊 NOMBRE TOTAL DE PAIEMENTS: " . $allPayments->count() . "\n\n";

    if ($allPayments->count() > 0) {
        echo "📋 LISTE COMPLÈTE DES PAIEMENTS:\n";
        echo "========================================\n";
        
        $totalAmount = 0;
        $counter = 1;
        
        foreach ($allPayments as $payment) {
            echo "🔸 PAIEMENT #{$counter}\n";
            echo "   ID: {$payment->id}\n";
            echo "   Référence: {$payment->payment_reference}\n";
            echo "   Montant: {$payment->amount} FCFA\n";
            echo "   Méthode: {$payment->payment_method}\n";
            echo "   Téléphone: {$payment->phone_number}\n";
            echo "   Statut: {$payment->status}\n";
            echo "   Créé le: {$payment->created_at}\n";
            if ($payment->processed_at) {
                echo "   Traité le: {$payment->processed_at}\n";
            }
            if ($payment->gateway_response) {
                echo "   Réponse: {$payment->gateway_response}\n";
            }
            echo "   Contribution ID: {$payment->contribution_id}\n";
            echo "   ----------------------------------------\n";
            
            $totalAmount += $payment->amount;
            $counter++;
        }

        echo "\n💰 RÉSUMÉ FINANCIER:\n";
        echo "========================================\n";
        echo "Total des paiements: {$totalAmount} FCFA\n";
        echo "Nombre de paiements: " . $allPayments->count() . "\n";
        echo "Moyenne par paiement: " . round($totalAmount / $allPayments->count(), 2) . " FCFA\n";

        // Statistiques détaillées
        echo "\n📈 STATISTIQUES DÉTAILLÉES:\n";
        echo "========================================\n";
        
        // Par statut
        $statusStats = DB::table('payments')
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('status')
            ->get();
            
        echo "Par statut:\n";
        foreach ($statusStats as $stat) {
            echo "• {$stat->status}: {$stat->count} paiements ({$stat->total} FCFA)\n";
        }

        // Par méthode
        $methodStats = DB::table('payments')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();
            
        echo "\nPar méthode de paiement:\n";
        foreach ($methodStats as $stat) {
            echo "• {$stat->payment_method}: {$stat->count} paiements ({$stat->total} FCFA)\n";
        }

        // Par mois
        $monthlyStats = DB::table('payments')
            ->select(DB::raw('DATE_TRUNC(\'month\', created_at) as month'), 
                    DB::raw('COUNT(*) as count'), 
                    DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('DATE_TRUNC(\'month\', created_at)'))
            ->orderBy('month', 'desc')
            ->get();
            
        echo "\nPar mois:\n";
        foreach ($monthlyStats as $stat) {
            $monthName = date('F Y', strtotime($stat->month));
            echo "• {$monthName}: {$stat->count} paiements ({$stat->total} FCFA)\n";
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
