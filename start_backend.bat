@echo off
echo Démarrage du serveur backend DONS avec PostgreSQL...
echo.

REM Vérifier si PHP est installé
php --version >nul 2>&1
if errorlevel 1 (
    echo Erreur: PHP n'est pas installé ou pas dans le PATH
    echo Veuillez installer PHP et l'ajouter au PATH
    pause
    exit /b 1
)

REM Vérifier si Composer est installé
composer --version >nul 2>&1
if errorlevel 1 (
    echo Erreur: Composer n'est pas installé ou pas dans le PATH
    echo Veuillez installer Composer et l'ajouter au PATH
    pause
    exit /b 1
)

REM Installer les dépendances
echo Installation des dépendances PHP...
composer install

REM Générer la clé d'application
echo Génération de la clé d'application...
php artisan key:generate

REM Créer la base de données PostgreSQL (si elle n'existe pas)
echo Vérification de la base de données PostgreSQL...
echo Assurez-vous que PostgreSQL est démarré et que la base de données 'dons_db' existe
echo.

REM Exécuter les migrations
echo Exécution des migrations...
php artisan migrate

REM Démarrer le serveur
echo Démarrage du serveur Laravel sur http://192.168.100.7:8000
echo.
echo Accès depuis votre téléphone: http://192.168.100.7:8000
echo Appuyez sur Ctrl+C pour arrêter le serveur
echo.
php artisan serve --host=0.0.0.0 --port=8000

pause
