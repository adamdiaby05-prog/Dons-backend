@echo off
echo ========================================
echo  Serveur de test simple pour DONS
echo ========================================
echo.
echo Ce serveur sera accessible sur:
echo - localhost:8000
echo - 127.0.0.1:8000
echo.
echo Endpoints disponibles:
echo - GET  /api/test
echo - GET  /api/payments/test  
echo - POST /api/payments/initiate
echo.
echo Appuyez sur Ctrl+C pour arreter le serveur
echo.

php -S 127.0.0.1:8000 simple_test_server.php

pause
