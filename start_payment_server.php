<?php
// Serveur simple pour l'API de paiement avec PostgreSQL
$host = 'localhost';
$port = 8000;

echo "Démarrage du serveur de paiement sur http://$host:$port\n";
echo "API de paiement disponible sur: http://$host:$port/api_payment_database.php\n";
echo "Appuyez sur Ctrl+C pour arrêter le serveur\n\n";

// Fonction pour gérer les requêtes
function handleRequest() {
    $requestUri = $_SERVER['REQUEST_URI'];
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    
    // Router simple
    if ($requestUri === '/api_payment_database.php' || $requestUri === '/api/payment') {
        include 'api_payment_database.php';
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint non trouvé',
            'available_endpoints' => [
                '/api_payment_database.php' => 'API de paiement avec PostgreSQL'
            ]
        ]);
    }
}

// Démarrer le serveur
if (php_sapi_name() === 'cli') {
    // Mode CLI - démarrer le serveur intégré
    echo "Serveur démarré en mode CLI\n";
    echo "Testez l'API avec: curl -X POST http://localhost:8000/api_payment_database.php\n";
    echo "Ou ouvrez: http://localhost:8000/api_payment_database.php dans votre navigateur\n";
} else {
    // Mode web - traiter la requête
    handleRequest();
}
?>
