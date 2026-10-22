@echo off
REM Lance la plateforme en local (presentation, essais). Ne pas utiliser en production :
REM sur le serveur en ligne, c'est le serveur web (Apache / Nginx) qui s'en charge.
title Plateforme de gestion - serveur local
cd /d "%~dp0"
echo.
echo  Plateforme demarree. Ouvrez Chrome a l'adresse :  http://127.0.0.1:8010
echo  (laissez cette fenetre ouverte pendant l'utilisation ; fermez-la pour arreter)
echo.
start "" "http://127.0.0.1:8010"
php artisan serve --port=8010
pause
