@echo off
echo ========================================
echo  Serveur de test simple pour DONS
echo ========================================
echo.
echo Ce serveur sera accessible sur:
echo - localhost:8000
echo - 192.168.1.7:8000 (votre IP locale)
echo - Depuis votre appareil mobile (meme reseau)
echo.
echo Endpoints disponibles:
echo - GET  /api/test
echo - GET  /api/payments/test  
echo - POST /api/payments/initiate
echo.
echo Appuyez sur Ctrl+C pour arreter le serveur
echo.

php -S 0.0.0.0:8000 -t . simple_server.php

pause 