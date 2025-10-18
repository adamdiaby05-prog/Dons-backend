<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "=== VÉRIFICATION DE LA TABLE PAYMENTS ===\n";
    
    // Vérifier la structure de la table
    $columns = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'payments'");
    echo "Colonnes de la table payments:\n";
    foreach($columns as $column) {
        echo "- " . $column->column_name . " (" . $column->data_type . ")\n";
    }
    
    // Compter les paiements
    $count = DB::table('payments')->count();
    echo "\nNombre total de paiements: " . $count . "\n";
    
    if($count > 0) {
        echo "\nPremiers paiements:\n";
        $payments = DB::table('payments')->take(10)->get();
        foreach($payments as $payment) {
            echo "ID: " . $payment->id . 
                 ", Montant: " . $payment->amount . 
                 ", Statut: " . $payment->status . 
                 ", Méthode: " . $payment->payment_method . 
                 ", Téléphone: " . $payment->phone_number . "\n";
        }
        
        // Statistiques par statut
        echo "\nStatistiques par statut:\n";
        $statusStats = DB::table('payments')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();
        foreach($statusStats as $stat) {
            echo "- " . $stat->status . ": " . $stat->count . " paiements\n";
        }
        
        // Statistiques par méthode de paiement
        echo "\nStatistiques par méthode:\n";
        $methodStats = DB::table('payments')
            ->select('payment_method', DB::raw('count(*) as count'))
            ->groupBy('payment_method')
            ->get();
        foreach($methodStats as $stat) {
            echo "- " . $stat->payment_method . ": " . $stat->count . " paiements\n";
        }
        
    } else {
        echo "\nAucun paiement trouvé dans la table.\n";
    }
    
} catch(Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?>
