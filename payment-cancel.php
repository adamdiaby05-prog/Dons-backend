<?php
// Page d'annulation de paiement Barapay - selon la documentation
header('Content-Type: text/html; charset=UTF-8');

// Récupérer les paramètres de retour de Barapay
$payment_id = $_GET['payment_id'] ?? '';
$reference = $_GET['ref'] ?? '';
$amount = $_GET['amount'] ?? '';
$status = $_GET['status'] ?? 'cancelled';

// Mettre à jour le statut du paiement dans le fichier
if ($payment_id || $reference) {
    $payments_file = __DIR__ . '/payments.json';
    if (file_exists($payments_file)) {
        $payments = json_decode(file_get_contents($payments_file), true) ?: [];
        
        // Trouver et mettre à jour le paiement
        foreach ($payments as &$payment) {
            if (($payment_id && $payment['id'] === $payment_id) || 
                ($reference && $payment['barapay_reference'] === $reference)) {
                $payment['status'] = 'cancelled';
                $payment['cancelled_at'] = date('Y-m-d H:i:s');
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
    <title>Paiement Annulé - DONS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
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
        .cancel-icon {
            width: 80px;
            height: 80px;
            background: #dc3545;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
        }
        h1 {
            color: #dc3545;
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
            color: #dc3545;
            font-weight: bold;
        }
        .btn {
            background: #dc3545;
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
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .info-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="cancel-icon">✗</div>
        <h1>Paiement Annulé</h1>
        <p class="subtitle">Votre paiement a été annulé ou a échoué</p>
        
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
                <span class="detail-value">Annulé</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date :</span>
                <span class="detail-value"><?php echo date('d/m/Y H:i:s'); ?></span>
            </div>
        </div>
        
        <div class="info-box">
            <strong>ℹ️ Information :</strong><br>
            Votre paiement a été annulé. Aucun montant n'a été débité de votre compte mobile money.
        </div>
        
        <p style="color: #dc3545; font-weight: bold; margin: 20px 0;">
            ❌ Paiement annulé par Barapay
        </p>
        
        <div>
            <a href="http://localhost:3000/#/client/amount" class="btn">
                Réessayer le paiement
            </a>
            <a href="http://localhost:3000/#/client/network-selection" class="btn btn-secondary">
                Retour à la sélection
            </a>
        </div>
        
        <p style="margin-top: 30px; color: #666; font-size: 14px;">
            Vous pouvez réessayer votre paiement à tout moment.
        </p>
    </div>
</body>
</html>
