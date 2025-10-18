<?php
// Script pour afficher toutes les tables et leur contenu
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Charger l'application Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== AFFICHAGE COMPLET DE TOUTES LES TABLES ===\n\n";

try {
    // 1. Table USERS
    echo "📋 TABLE USERS (Utilisateurs) :\n";
    echo str_repeat("=", 80) . "\n";
    $users = DB::table('users')->get();
    echo "Nombre total d'utilisateurs : " . $users->count() . "\n\n";
    
    if ($users->count() > 0) {
        echo "ID | Prénom | Nom | Téléphone | Email | Admin | Vérifié | Créé le\n";
        echo str_repeat("-", 100) . "\n";
        foreach ($users as $user) {
            $admin = isset($user->is_admin) && $user->is_admin ? 'OUI' : 'NON';
            $verified = isset($user->phone_verified) && $user->phone_verified ? 'OUI' : 'NON';
            $created = isset($user->created_at) ? date('d/m/Y H:i', strtotime($user->created_at)) : 'N/A';
            echo sprintf("%-3d | %-8s | %-8s | %-10s | %-20s | %-5s | %-8s | %s\n", 
                $user->id, 
                $user->first_name ?? 'N/A', 
                $user->last_name ?? 'N/A', 
                $user->phone_number ?? 'N/A', 
                $user->email ?? 'N/A', 
                $admin, 
                $verified, 
                $created
            );
        }
    }
    echo "\n\n";
    
    // 2. Table GROUPS
    echo "📋 TABLE GROUPS (Groupes) :\n";
    echo str_repeat("=", 80) . "\n";
    $groups = DB::table('groups')->get();
    echo "Nombre total de groupes : " . $groups->count() . "\n\n";
    
    if ($groups->count() > 0) {
        echo "ID | Nom | Description | Créé par | Créé le\n";
        echo str_repeat("-", 80) . "\n";
        foreach ($groups as $group) {
            $created = isset($group->created_at) ? date('d/m/Y H:i', strtotime($group->created_at)) : 'N/A';
            echo sprintf("%-3d | %-20s | %-30s | %-8s | %s\n", 
                $group->id, 
                $group->name ?? 'N/A', 
                $group->description ?? 'N/A', 
                $group->created_by ?? 'N/A', 
                $created
            );
        }
    }
    echo "\n\n";
    
    // 3. Table GROUP_MEMBERS
    echo "📋 TABLE GROUP_MEMBERS (Membres des groupes) :\n";
    echo str_repeat("=", 80) . "\n";
    $members = DB::table('group_members')->get();
    echo "Nombre total de membres : " . $members->count() . "\n\n";
    
    if ($members->count() > 0) {
        echo "ID | Groupe ID | User ID | Rôle | Statut | Rejoint le\n";
        echo str_repeat("-", 60) . "\n";
        foreach ($members as $member) {
            $joined = isset($member->joined_at) ? date('d/m/Y H:i', strtotime($member->joined_at)) : 'N/A';
            echo sprintf("%-3d | %-8s | %-7s | %-8s | %-6s | %s\n", 
                $member->id, 
                $member->group_id ?? 'N/A', 
                $member->user_id ?? 'N/A', 
                $member->role ?? 'N/A', 
                $member->status ?? 'N/A', 
                $joined
            );
        }
    }
    echo "\n\n";
    
    // 4. Table PAYMENTS
    echo "📋 TABLE PAYMENTS (Paiements) :\n";
    echo str_repeat("=", 80) . "\n";
    $payments = DB::table('payments')->get();
    echo "Nombre total de paiements : " . $payments->count() . "\n\n";
    
    if ($payments->count() > 0) {
        echo "ID | User ID | Montant | Statut | Méthode | Référence | Créé le\n";
        echo str_repeat("-", 80) . "\n";
        foreach ($payments as $payment) {
            $created = isset($payment->created_at) ? date('d/m/Y H:i', strtotime($payment->created_at)) : 'N/A';
            echo sprintf("%-3d | %-7s | %-8s | %-8s | %-10s | %-10s | %s\n", 
                $payment->id, 
                $payment->user_id ?? 'N/A', 
                number_format($payment->amount ?? 0, 0, ',', ' ') . ' FCFA', 
                $payment->status ?? 'N/A', 
                $payment->payment_method ?? 'N/A', 
                $payment->payment_reference ?? 'N/A', 
                $created
            );
        }
    }
    echo "\n\n";
    
    // 5. Table CONTRIBUTIONS
    echo "📋 TABLE CONTRIBUTIONS (Contributions) :\n";
    echo str_repeat("=", 80) . "\n";
    $contributions = DB::table('contributions')->get();
    echo "Nombre total de contributions : " . $contributions->count() . "\n\n";
    
    if ($contributions->count() > 0) {
        echo "ID | Groupe ID | User ID | Montant | Type | Statut | Créé le\n";
        echo str_repeat("-", 70) . "\n";
        foreach ($contributions as $contribution) {
            $created = isset($contribution->created_at) ? date('d/m/Y H:i', strtotime($contribution->created_at)) : 'N/A';
            echo sprintf("%-3d | %-8s | %-7s | %-8s | %-8s | %-8s | %s\n", 
                $contribution->id, 
                $contribution->group_id ?? 'N/A', 
                $contribution->user_id ?? 'N/A', 
                number_format($contribution->amount ?? 0, 0, ',', ' ') . ' FCFA', 
                $contribution->type ?? 'N/A', 
                $contribution->status ?? 'N/A', 
                $created
            );
        }
    }
    echo "\n\n";
    
    // 6. Table NOTIFICATIONS
    echo "📋 TABLE NOTIFICATIONS (Notifications) :\n";
    echo str_repeat("=", 80) . "\n";
    $notifications = DB::table('notifications')->get();
    echo "Nombre total de notifications : " . $notifications->count() . "\n\n";
    
    if ($notifications->count() > 0) {
        echo "ID | User ID | Type | Titre | Lu | Créé le\n";
        echo str_repeat("-", 60) . "\n";
        foreach ($notifications as $notification) {
            $created = isset($notification->created_at) ? date('d/m/Y H:i', strtotime($notification->created_at)) : 'N/A';
            $read = isset($notification->read_at) ? 'OUI' : 'NON';
            echo sprintf("%-3d | %-7s | %-8s | %-20s | %-3s | %s\n", 
                $notification->id, 
                $notification->user_id ?? 'N/A', 
                $notification->type ?? 'N/A', 
                $notification->title ?? 'N/A', 
                $read, 
                $created
            );
        }
    } else {
        echo "Aucune notification trouvée.\n";
    }
    echo "\n\n";
    
    // 7. Table MIGRATIONS
    echo "📋 TABLE MIGRATIONS (Migrations) :\n";
    echo str_repeat("=", 80) . "\n";
    $migrations = DB::table('migrations')->orderBy('batch')->get();
    echo "Nombre total de migrations : " . $migrations->count() . "\n\n";
    
    if ($migrations->count() > 0) {
        echo "Batch | Migration | Appliquée le\n";
        echo str_repeat("-", 50) . "\n";
        foreach ($migrations as $migration) {
            echo sprintf("%-5s | %-50s | %s\n", 
                $migration->batch, 
                $migration->migration, 
                'Réussi'
            );
        }
    }
    echo "\n\n";
    
    // 8. RÉSUMÉ STATISTIQUES
    echo "📊 RÉSUMÉ STATISTIQUES :\n";
    echo str_repeat("=", 80) . "\n";
    echo "• Utilisateurs : " . DB::table('users')->count() . "\n";
    echo "• Groupes : " . DB::table('groups')->count() . "\n";
    echo "• Membres de groupes : " . DB::table('group_members')->count() . "\n";
    echo "• Paiements : " . DB::table('payments')->count() . "\n";
    echo "• Contributions : " . DB::table('contributions')->count() . "\n";
    echo "• Notifications : " . DB::table('notifications')->count() . "\n";
    echo "• Migrations : " . DB::table('migrations')->count() . "\n";
    
    // Calcul du montant total des paiements
    $totalPayments = DB::table('payments')->sum('amount');
    echo "• Montant total des paiements : " . number_format($totalPayments, 0, ',', ' ') . " FCFA\n";
    
    // Calcul du montant total des contributions
    $totalContributions = DB::table('contributions')->sum('amount');
    echo "• Montant total des contributions : " . number_format($totalContributions, 0, ',', ' ') . " FCFA\n";
    
    echo "\n✅ Affichage terminé !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
?>
