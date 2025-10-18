@echo off
echo Demarrage du serveur de paiement DONS
echo =====================================
echo.
echo Configuration:
echo - Host: localhost
echo - Port: 8000
echo - Base de donnees: PostgreSQL
echo - Mot de passe: 0000
echo.
echo API disponible sur: http://localhost:8000/api_payment_database.php
echo.
echo Appuyez sur Ctrl+C pour arreter le serveur
echo.

php -S localhost:8000 start_payment_server.php
