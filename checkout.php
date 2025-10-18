<?php
// Page de checkout Barapay simple
$reference = $_GET['ref'] ?? 'DONS-' . date('Ymd') . '-' . rand(1000, 9999);
$amount = $_GET['amount'] ?? '5000';
$phone = $_GET['phone'] ?? '';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barapay - Paiement</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .checkout-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .logo {
            width: 80px;
            height: 80px;
            background: #4CAF50;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .payment-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 18px;
            color: #4CAF50;
        }
        .btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin: 10px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .status {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="logo">BP</div>
        <h1>Barapay Checkout</h1>
        <p class="subtitle">Paiement sécurisé</p>
        
        <div class="payment-details">
            <div class="detail-row">
                <span>Référence:</span>
                <span><?php echo htmlspecialchars($reference); ?></span>
            </div>
            <div class="detail-row">
                <span>Montant:</span>
                <span><?php echo htmlspecialchars($amount); ?> FCFA</span>
            </div>
            <div class="detail-row">
                <span>Téléphone:</span>
                <span><?php echo htmlspecialchars($phone); ?></span>
            </div>
            <div class="detail-row">
                <span>Total à payer:</span>
                <span><?php echo htmlspecialchars($amount); ?> FCFA</span>
            </div>
        </div>
        
        <div id="status" class="status pending">
            ⏳ En attente de confirmation...
        </div>
        
        <button class="btn" onclick="simulatePayment()">
            💳 Confirmer le paiement
        </button>
        
        <button class="btn btn-secondary" onclick="cancelPayment()">
            ❌ Annuler
        </button>
    </div>

    <script>
        function simulatePayment() {
            const status = document.getElementById('status');
            status.innerHTML = '⏳ Traitement en cours...';
            status.className = 'status pending';
            
            // Simuler le traitement
            setTimeout(() => {
                status.innerHTML = '✅ Paiement confirmé avec succès!';
                status.className = 'status success';
                
                // Rediriger vers la page de succès après 2 secondes
                setTimeout(() => {
                    window.location.href = 'http://localhost:3000/#/payment/success?ref=<?php echo $reference; ?>&amount=<?php echo $amount; ?>';
                }, 2000);
            }, 3000);
        }
        
        function cancelPayment() {
            if (confirm('Êtes-vous sûr de vouloir annuler ce paiement ?')) {
                window.location.href = 'http://localhost:3000/#/payment/cancel?ref=<?php echo $reference; ?>';
            }
        }
        
        // Auto-redirection après 30 secondes si pas d'action
        setTimeout(() => {
            if (confirm('Temps écoulé. Voulez-vous confirmer le paiement maintenant ?')) {
                simulatePayment();
            } else {
                cancelPayment();
            }
        }, 30000);
    </script>
</body>
</html>

