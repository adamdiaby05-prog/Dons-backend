<?php
// Script pour créer la base de données PostgreSQL
$host = 'localhost';
$port = '5432';
$dbname = 'postgres'; // Se connecter à la base par défaut
$username = 'postgres';
$password = '0000';

try {
    // Connexion à PostgreSQL (base par défaut)
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion à PostgreSQL réussie\n";
    
    // Créer la base de données dons_database
    $pdo->exec("CREATE DATABASE dons_database");
    echo "✅ Base de données 'dons_database' créée\n";
    
    // Se reconnecter à la nouvelle base
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=dons_database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la table payments
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS payments (
            id SERIAL PRIMARY KEY,
            amount DECIMAL(10,2) NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            network VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createTableSQL);
    echo "✅ Table 'payments' créée\n";
    
    echo "\n🎉 Base de données configurée avec succès!\n";
    echo "Vous pouvez maintenant démarrer le serveur API.\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "⚠️  La base de données 'dons_database' existe déjà\n";
        echo "Tentative de création de la table...\n";
        
        try {
            $pdo = new PDO("pgsql:host=$host;port=$port;dbname=dons_database", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $createTableSQL = "
                CREATE TABLE IF NOT EXISTS payments (
                    id SERIAL PRIMARY KEY,
                    amount DECIMAL(10,2) NOT NULL,
                    phone_number VARCHAR(20) NOT NULL,
                    network VARCHAR(50) NOT NULL,
                    status VARCHAR(20) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            $pdo->exec($createTableSQL);
            echo "✅ Table 'payments' créée/vérifiée\n";
            echo "\n🎉 Base de données prête!\n";
            
        } catch (PDOException $e2) {
            echo "❌ Erreur lors de la création de la table: " . $e2->getMessage() . "\n";
        }
    } else {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
        echo "Vérifiez que PostgreSQL est démarré et que l'utilisateur 'postgres' existe\n";
    }
}
?>
