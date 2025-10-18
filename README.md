# 🚀 DONS Backend API

API backend pour le système de dons avec intégration Barapay.

## 📋 Vue d'ensemble

Ce repository contient l'API backend qui gère :
- L'intégration Barapay pour les paiements
- La gestion des transactions
- La base de données PostgreSQL
- Les callbacks de paiement

## 🔧 Configuration

### Prérequis
- PHP 8.2+
- PostgreSQL
- Composer (optionnel)

### Variables d'environnement
```bash
# Base de données
DB_HOST=localhost
DB_NAME=dons_database
DB_USER=postgres
DB_PASSWORD=0000

# Barapay
BARAPAY_CLIENT_ID=wjb7lzQVialbcwMNTPD1IojrRzPIIl
BARAPAY_CLIENT_SECRET=eXSMVquRfnUi6u5epkKFbxym1bZxSjgfHMxJlGGKq9j1amulx97Cj4QB7vZFzuyRUm4UC9mCHYhfzWn34arIyW4G2EU9vcdcQsb1
```

## 🚀 Installation

### 1. Cloner le repository
```bash
git clone <repository-url>
cd dons-backend
```

### 2. Configurer la base de données
```bash
# Créer la base de données
createdb dons_database

# Exécuter le script SQL
psql -d dons_database -f script-complet-dons.sql
```

### 3. Démarrer le serveur
```bash
# Développement
php -S localhost:8001

# Production (avec Apache/Nginx)
# Configurer le serveur web pour pointer vers le dossier public
```

## 📁 Structure du projet

```
backend/
├── api_barapay_authentic.php    # Endpoint principal Barapay
├── barapay_simple_integration.php # Intégration Barapay
├── barapay_callback.php         # Callback de paiement
├── logs/                        # Logs de l'application
├── public/                      # Fichiers publics
└── README.md                    # Cette documentation
```

## 🔌 Endpoints API

### POST `/api_barapay_authentic.php`
Créer un paiement Barapay

**Requête:**
```json
{
  "amount": 5000,
  "phone_number": "+225 05 05 97 98 84",
  "network": "wave"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Paiement créé avec succès",
  "order_no": "DONS_20251018_1234567890_1234",
  "amount": 5000,
  "currency": "XOF",
  "network": "wave",
  "phone_number": "+225 05 05 97 98 84",
  "checkout_url": "https://barapay.net/merchant/payment?grant_id=123&token=abc",
  "payment_method": "Bpay",
  "saved_to_db": true
}
```

### POST `/barapay_callback.php`
Callback de paiement (configuré dans Barapay)

**Headers:**
- `X-Bpay-Signature`: Signature HMAC SHA256

**Réponse:**
```json
{
  "status": "success",
  "message": "Paiement traité avec succès",
  "order_no": "DONS_20251018_1234567890_1234",
  "transaction_id": "BPAY_123456789"
}
```

## 🗄️ Base de données

### Table `payments`
```sql
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    order_no VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    network VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'XOF',
    status VARCHAR(20) DEFAULT 'pending',
    transaction_id VARCHAR(255),
    reason TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
```

## 🔐 Sécurité

### Vérification de signature
Les callbacks Barapay incluent une signature HMAC SHA256 pour vérifier l'authenticité.

### CORS
L'API est configurée pour accepter les requêtes depuis le frontend React.

### Logging
Tous les événements sont loggés dans le dossier `logs/`.

## 🧪 Tests

### Test de l'API
```bash
curl -X POST http://localhost:8001/api_barapay_authentic.php \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 5000,
    "phone_number": "+225 05 05 97 98 84",
    "network": "wave"
  }'
```

### Test de callback
```bash
curl -X POST http://localhost:8001/barapay_callback.php \
  -H "Content-Type: application/json" \
  -H "X-Bpay-Signature: your_signature" \
  -d '{
    "status": "success",
    "orderNo": "DONS_20251018_1234567890_1234",
    "transactionId": "BPAY_123456789",
    "amount": 5000,
    "currency": "XOF"
  }'
```

## 🚀 Déploiement

### Développement
```bash
php -S localhost:8001
```

### Production
1. Configurer Apache/Nginx
2. Installer PHP 8.2+
3. Configurer PostgreSQL
4. Définir les variables d'environnement
5. Configurer les URLs de callback dans Barapay

### Docker (optionnel)
```bash
docker build -t dons-backend .
docker run -p 8001:8001 dons-backend
```

## 📊 Monitoring

### Logs disponibles
- `logs/barapay_api.log` - Requêtes API
- `logs/barapay_callbacks.log` - Callbacks de paiement

### Métriques importantes
- Taux de succès des paiements
- Temps de réponse API
- Erreurs de signature
- Échecs de callback

## 🆘 Dépannage

### Erreurs courantes

#### "Credentials manquants"
- Vérifier les constantes BARAPAY_CLIENT_ID et BARAPAY_CLIENT_SECRET

#### "Erreur de base de données"
- Vérifier la connexion PostgreSQL
- Vérifier les permissions de la base de données

#### "Signature invalide"
- Vérifier le CLIENT_SECRET
- Vérifier les headers du callback

### Support
- 📧 Email: support@dons.com
- 📚 Documentation: [barapay.net/docs](https://barapay.net/docs)

## 📈 Évolutions futures

### Fonctionnalités prévues
- [ ] API REST complète
- [ ] Authentification JWT
- [ ] Rate limiting
- [ ] Monitoring avancé
- [ ] Tests automatisés

---

**Version:** 1.0.0  
**Dernière mise à jour:** 2025-01-18  
**Auteur:** Équipe DONS  
**Licence:** MIT