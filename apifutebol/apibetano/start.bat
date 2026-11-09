@echo off
cd /d "%~dp0"
echo.
echo  API Betano Sports
echo  http://127.0.0.1:8080/api
echo.
php -d open_basedir= -S 127.0.0.1:8080 router.php
