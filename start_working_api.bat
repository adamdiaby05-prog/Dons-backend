@echo off
echo ========================================
echo  Serveur API FONCTIONNEL pour DONS
echo ========================================
echo.
echo Ce serveur sera accessible sur:
echo - localhost:8000
echo.
echo Endpoints disponibles:
echo - GET  /api/test
echo - POST /api/payments/initiate
echo - POST /api_save_payment_simple.php
echo.
echo Appuyez sur Ctrl+C pour arreter le serveur
echo.

php -S localhost:8000 -t . api_test.php

pause

