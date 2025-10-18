<?php
// Page de succès de paiement Barapay - selon la documentation
header('Content-Type: text/html; charset=UTF-8');

// Récupérer les paramètres de retour de Barapay
$payment_id = $_GET['payment_id'] ?? '';
$reference = $_GET['ref'] ?? '';
$amount = $_GET['amount'] ?? '';
$status = $_GET['status'] ?? 'success';

// Mettre à jour le statut du paiement dans le fichier
if ($payment_id || $reference) {
    $payments_file = __DIR__ . '/payments.json';
    if (file_exists($payments_file)) {
        $payments = json_decode(file_get_contents($payments_file), true) ?: [];
        
        // Trouver et mettre à jour le paiement
        foreach ($payments as &$payment) {
            if (($payment_id && $payment['id'] === $payment_id) || 
                ($reference && $payment['barapay_reference'] === $reference)) {
                $payment['status'] = 'completed';
                $payment['completed_at'] = date('Y-m-d H:i:s');
                $payment['barapay_status'] = $status;
                break;
            }
        }
        
        file_put_contents($payments_file, json_encode($payments, JSON_PRETTY_PRINT));
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement Réussi - DONS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
        }
        h1 {
            color: #28a745;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .payment-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        .detail-value {
            color: #28a745;
            font-weight: bold;
        }
        .btn {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #218838;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>
        <h1>Paiement Réussi !</h1>
        <p class="subtitle">Votre paiement a été traité avec succès via Barapay</p>
        
        <div class="payment-details">
            <div class="detail-row">
                <span class="detail-label">Référence :</span>
                <span class="detail-value"><?php echo htmlspecialchars($reference ?: $payment_id); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Montant :</span>
                <span class="detail-value"><?php echo htmlspecialchars($amount); ?> FCFA</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Statut :</span>
                <span class="detail-value">Complété</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date :</span>
                <span class="detail-value"><?php echo date('d/m/Y H:i:s'); ?></span>
            </div>
        </div>
        
        <p style="color: #28a745; font-weight: bold; margin: 20px 0;">
            ✅ Paiement confirmé par Barapay
        </p>
        
        <div>
            <a href="http://localhost:3000/#/client/candidate-presentation" class="btn">
                Continuer vers l'application
            </a>
            <a href="http://localhost:3000/dashboard.html" class="btn btn-secondary">
                Voir le tableau de bord
            </a>
        </div>
        
        <p style="margin-top: 30px; color: #666; font-size: 14px;">
            Merci d'avoir utilisé DONS pour votre paiement !
        </p>
    </div>
</body>
</html>
