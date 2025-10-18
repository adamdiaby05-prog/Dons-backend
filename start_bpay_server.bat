@echo off
echo ========================================
echo  Serveur DONS avec Bpay SDK intégré
echo ========================================
echo.
echo Ce serveur utilise votre SDK Bpay existant
echo accessible sur: localhost:8000
echo.
echo Endpoints disponibles:
echo - GET  /api/test
echo - POST /api/payments/initiate
echo - POST /api_save_payment_simple.php
echo.
echo Appuyez sur Ctrl+C pour arreter le serveur
echo.

php -S localhost:8000 bpay_integrated_server.php

pause

