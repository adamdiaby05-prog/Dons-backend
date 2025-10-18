<?php
// Script pour vérifier le contenu des tables
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Charger l'application Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CONTENU DÉTAILLÉ DES TABLES ===\n\n";

try {
    // 1. Table groups
    echo "1. TABLE GROUPS :\n";
    $groups = DB::table('groups')->get();
    if ($groups->count() > 0) {
        foreach ($groups as $group) {
            echo "   - ID: {$group->id}, Nom: {$group->name}, Description: {$group->description}\n";
        }
    } else {
        echo "   Aucun groupe trouvé\n";
    }
    echo "\n";
    
    // 2. Table group_members
    echo "2. TABLE GROUP_MEMBERS :\n";
    $members = DB::table('group_members')->get();
    if ($members->count() > 0) {
        foreach ($members as $member) {
            echo "   - Groupe ID: {$member->group_id}, User ID: {$member->user_id}, Rôle: {$member->role}\n";
        }
    } else {
        echo "   Aucun membre de groupe trouvé\n";
    }
    echo "\n";
    
    // 3. Table payments
    echo "3. TABLE PAYMENTS :\n";
    $payments = DB::table('payments')->get();
    if ($payments->count() > 0) {
        foreach ($payments as $payment) {
            echo "   - ID: {$payment->id}, Montant: {$payment->amount}, Statut: {$payment->status}, Méthode: {$payment->payment_method}\n";
        }
    } else {
        echo "   Aucun paiement trouvé\n";
    }
    echo "\n";
    
    // 4. Table contributions
    echo "4. TABLE CONTRIBUTIONS :\n";
    $contributions = DB::table('contributions')->get();
    if ($contributions->count() > 0) {
        foreach ($contributions as $contribution) {
            echo "   - ID: {$contribution->id}, Groupe ID: {$contribution->group_id}, User ID: {$contribution->user_id}, Montant: {$contribution->amount}\n";
        }
    } else {
        echo "   Aucune contribution trouvée\n";
    }
    echo "\n";
    
    // 5. Table notifications
    echo "5. TABLE NOTIFICATIONS :\n";
    $notifications = DB::table('notifications')->get();
    if ($notifications->count() > 0) {
        foreach ($notifications as $notification) {
            echo "   - ID: {$notification->id}, User ID: {$notification->user_id}, Type: {$notification->type}, Titre: {$notification->title}\n";
        }
    } else {
        echo "   Aucune notification trouvée\n";
    }
    echo "\n";
    
    // 6. Statistiques générales
    echo "6. STATISTIQUES GÉNÉRALES :\n";
    echo "   - Utilisateurs : " . DB::table('users')->count() . "\n";
    echo "   - Groupes : " . DB::table('groups')->count() . "\n";
    echo "   - Membres de groupes : " . DB::table('group_members')->count() . "\n";
    echo "   - Paiements : " . DB::table('payments')->count() . "\n";
    echo "   - Contributions : " . DB::table('contributions')->count() . "\n";
    echo "   - Notifications : " . DB::table('notifications')->count() . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
?>
