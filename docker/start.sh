#!/bin/bash

# 🚀 Script de démarrage pour Backend Laravel DONS

set -e

echo "🚀 Démarrage du Backend Laravel DONS..."

# Attendre que la base de données soit prête
echo "⏳ Attente de la base de données..."
until pg_isready -h dons-database-nl3z8n -p 5432 -U postgres -d Dons; do
    echo "Base de données non disponible - attente..."
    sleep 2
done

echo "✅ Base de données connectée"

# Générer la clé d'application si elle n'existe pas
if [ ! -f .env ]; then
    echo "📝 Création du fichier .env..."
    cp .env.example .env
fi

# Générer APP_KEY si nécessaire
if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=$" .env; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate --force
fi

# Exécuter les migrations
echo "🗄️ Exécution des migrations..."
php artisan migrate --force

# Créer les liens symboliques
echo "🔗 Création des liens symboliques..."
php artisan storage:link

# Optimiser l'application
echo "⚡ Optimisation de l'application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Démarrer les services
echo "🚀 Démarrage des services..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
