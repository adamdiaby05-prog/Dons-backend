<?php
// Script pour vérifier la base de données PostgreSQL
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Charger l'application Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VÉRIFICATION DE LA BASE DE DONNÉES POSTGRESQL ===\n\n";

try {
    // Test de connexion
    echo "1. Test de connexion à PostgreSQL...\n";
    $connection = DB::connection();
    $pdo = $connection->getPdo();
    echo "✅ Connexion réussie à PostgreSQL\n\n";
    
    // Informations sur la base de données
    echo "2. Informations sur la base de données :\n";
    $databaseName = $connection->getDatabaseName();
    echo "   - Nom de la base : $databaseName\n";
    echo "   - Driver : " . $connection->getDriverName() . "\n";
    echo "   - Host : " . config('database.connections.pgsql.host') . "\n";
    echo "   - Port : " . config('database.connections.pgsql.port') . "\n\n";
    
    // Vérifier les tables
    echo "3. Tables disponibles :\n";
    $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    foreach ($tables as $table) {
        echo "   - " . $table->table_name . "\n";
    }
    echo "\n";
    
    // Vérifier la table users
    if (Schema::hasTable('users')) {
        echo "4. Contenu de la table 'users' :\n";
        $users = DB::table('users')->get();
        echo "   Nombre d'utilisateurs : " . $users->count() . "\n";
        
        if ($users->count() > 0) {
            echo "   Utilisateurs :\n";
            foreach ($users as $user) {
                echo "   - ID: {$user->id}, Nom: {$user->first_name} {$user->last_name}, Téléphone: {$user->phone_number}, Email: {$user->email}\n";
            }
        }
        echo "\n";
    }
    
    // Vérifier les migrations
    echo "5. État des migrations :\n";
    $migrations = DB::table('migrations')->get();
    echo "   Migrations appliquées : " . $migrations->count() . "\n";
    foreach ($migrations as $migration) {
        echo "   - " . $migration->migration . " (appliquée le " . $migration->batch . ")\n";
    }
    echo "\n";
    
    // Test de requête simple
    echo "6. Test de requête :\n";
    $result = DB::select("SELECT version() as version");
    echo "   Version PostgreSQL : " . $result[0]->version . "\n\n";
    
    echo "✅ Base de données PostgreSQL fonctionnelle !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur de connexion à la base de données :\n";
    echo "   " . $e->getMessage() . "\n";
    echo "\nVérifiez que PostgreSQL est démarré et que les paramètres de connexion sont corrects.\n";
}
?>
