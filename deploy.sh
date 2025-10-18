#!/bin/bash

# 🚀 Script de déploiement Backend DONS
# Ce script déploie l'API backend sur un serveur

set -e  # Arrêter en cas d'erreur

echo "🚀 Déploiement du Backend DONS..."

# Configuration
BACKEND_DIR="/var/www/dons-backend"
NGINX_CONFIG="/etc/nginx/sites-available/dons-backend"
NGINX_ENABLED="/etc/nginx/sites-enabled/dons-backend"
PHP_FPM_CONFIG="/etc/php/8.2/fpm/pool.d/dons-backend.conf"

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERREUR: $1${NC}"
    exit 1
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] ATTENTION: $1${NC}"
}

# Vérifier les prérequis
check_prerequisites() {
    log "Vérification des prérequis..."
    
    # Vérifier PHP
    if ! command -v php &> /dev/null; then
        error "PHP n'est pas installé"
    fi
    
    # Vérifier PostgreSQL
    if ! command -v psql &> /dev/null; then
        error "PostgreSQL n'est pas installé"
    fi
    
    # Vérifier Nginx
    if ! command -v nginx &> /dev/null; then
        error "Nginx n'est pas installé"
    fi
    
    log "✅ Prérequis vérifiés"
}

# Installer les dépendances système
install_system_deps() {
    log "Installation des dépendances système..."
    
    # Mettre à jour les paquets
    sudo apt update
    
    # Installer PHP et extensions
    sudo apt install -y php8.2 php8.2-fpm php8.2-pgsql php8.2-curl php8.2-json
    
    # Installer PostgreSQL
    sudo apt install -y postgresql postgresql-contrib
    
    # Installer Nginx
    sudo apt install -y nginx
    
    log "✅ Dépendances système installées"
}

# Configurer PostgreSQL
setup_postgresql() {
    log "Configuration de PostgreSQL..."
    
    # Créer la base de données
    sudo -u postgres createdb dons_database 2>/dev/null || warning "Base de données existe déjà"
    
    # Créer l'utilisateur
    sudo -u postgres psql -c "CREATE USER dons_user WITH PASSWORD 'dons_password';" 2>/dev/null || warning "Utilisateur existe déjà"
    
    # Donner les permissions
    sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE dons_database TO dons_user;"
    
    # Exécuter le script SQL
    if [ -f "script-complet-dons.sql" ]; then
        sudo -u postgres psql -d dons_database -f script-complet-dons.sql
        log "✅ Script SQL exécuté"
    else
        warning "Script SQL non trouvé"
    fi
}

# Configurer PHP-FPM
setup_php_fpm() {
    log "Configuration de PHP-FPM..."
    
    # Créer le pool PHP-FPM
    sudo tee $PHP_FPM_CONFIG > /dev/null <<EOF
[dons-backend]
user = www-data
group = www-data
listen = /run/php/php8.2-fpm-dons-backend.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
EOF

    # Redémarrer PHP-FPM
    sudo systemctl restart php8.2-fpm
    log "✅ PHP-FPM configuré"
}

# Configurer Nginx
setup_nginx() {
    log "Configuration de Nginx..."
    
    # Créer la configuration Nginx
    sudo tee $NGINX_CONFIG > /dev/null <<EOF
server {
    listen 80;
    server_name dons-api.local;
    root $BACKEND_DIR;
    index index.php;

    # Logs
    access_log /var/log/nginx/dons-backend-access.log;
    error_log /var/log/nginx/dons-backend-error.log;

    # CORS
    add_header 'Access-Control-Allow-Origin' '*' always;
    add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
    add_header 'Access-Control-Allow-Headers' 'Content-Type, Authorization' always;

    # Gestion des requêtes OPTIONS
    if (\$request_method = 'OPTIONS') {
        add_header 'Access-Control-Allow-Origin' '*';
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
        add_header 'Access-Control-Allow-Headers' 'Content-Type, Authorization';
        add_header 'Access-Control-Max-Age' 1728000;
        add_header 'Content-Type' 'text/plain; charset=utf-8';
        add_header 'Content-Length' 0;
        return 204;
    }

    # API endpoints
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm-dons-backend.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # Sécurité
    location ~ /\. {
        deny all;
    }
}
EOF

    # Activer le site
    sudo ln -sf $NGINX_CONFIG $NGINX_ENABLED
    
    # Tester la configuration
    sudo nginx -t
    
    # Redémarrer Nginx
    sudo systemctl restart nginx
    
    log "✅ Nginx configuré"
}

# Déployer l'application
deploy_app() {
    log "Déploiement de l'application..."
    
    # Créer le répertoire
    sudo mkdir -p $BACKEND_DIR
    
    # Copier les fichiers
    sudo cp -r . $BACKEND_DIR/
    
    # Définir les permissions
    sudo chown -R www-data:www-data $BACKEND_DIR
    sudo chmod -R 755 $BACKEND_DIR
    
    # Créer le dossier logs
    sudo mkdir -p $BACKEND_DIR/logs
    sudo chown -R www-data:www-data $BACKEND_DIR/logs
    
    log "✅ Application déployée"
}

# Configurer les services
setup_services() {
    log "Configuration des services..."
    
    # Activer les services
    sudo systemctl enable nginx
    sudo systemctl enable php8.2-fpm
    sudo systemctl enable postgresql
    
    # Démarrer les services
    sudo systemctl start nginx
    sudo systemctl start php8.2-fpm
    sudo systemctl start postgresql
    
    log "✅ Services configurés"
}

# Tester le déploiement
test_deployment() {
    log "Test du déploiement..."
    
    # Tester l'API
    response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api_barapay_authentic.php)
    
    if [ "$response" = "400" ] || [ "$response" = "405" ]; then
        log "✅ API accessible (code: $response)"
    else
        warning "API retourne un code inattendu: $response"
    fi
    
    # Tester la base de données
    if sudo -u postgres psql -d dons_database -c "SELECT 1;" > /dev/null 2>&1; then
        log "✅ Base de données accessible"
    else
        error "Base de données non accessible"
    fi
}

# Fonction principale
main() {
    log "🚀 Début du déploiement Backend DONS"
    
    check_prerequisites
    install_system_deps
    setup_postgresql
    setup_php_fpm
    setup_nginx
    deploy_app
    setup_services
    test_deployment
    
    log "🎉 Déploiement terminé avec succès!"
    log "📡 API disponible sur: http://localhost"
    log "📊 Base de données: dons_database"
    log "📝 Logs: /var/log/nginx/dons-backend-*.log"
}

# Exécuter le script
main "$@"