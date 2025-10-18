<?php
// Test de connexion à PostgreSQL
$host = 'localhost';
$port = '5432';
$dbname = 'dons_database';
$username = 'postgres';
$password = '0000';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion PostgreSQL réussie\n";
    echo "Base de données: $dbname\n";
    echo "Utilisateur: $username\n";
    
    // Créer la table payments si elle n'existe pas
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
    
} catch (PDOException $e) {
    echo "❌ Erreur de connexion: " . $e->getMessage() . "\n";
    echo "Vérifiez que PostgreSQL est démarré et que la base 'dons_database' existe\n";
}
?>
