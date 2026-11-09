@echo off
setlocal
title Sportsbook API
cd /d "%~dp0"

echo.
echo  ========================================
echo   Sportsbook API - Node.js
echo  ========================================
echo.

where node >nul 2>&1
if errorlevel 1 (
  echo  [ERRO] Node.js nao encontrado no PATH.
  echo  Instale em: https://nodejs.org/
  echo.
  pause
  exit /b 1
)

for /f "tokens=*" %%v in ('node -v') do echo  Node: %%v
echo  Pasta: %cd%
echo.

if not exist "src\index.js" (
  echo  [ERRO] Arquivo src\index.js nao encontrado.
  echo.
  pause
  exit /b 1
)

if not exist "node_modules\express" (
  echo  Instalando dependencias (npm install)...
  call npm install
  if errorlevel 1 (
    echo.
    echo  [ERRO] npm install falhou.
    pause
    exit /b 1
  )
  echo.
)

REM Libera a porta 8090 se ja estiver em uso (processo antigo)
echo  Verificando porta 8090...
for /f "tokens=5" %%p in ('netstat -ano ^| findstr ":8090" ^| findstr "LISTENING"') do (
  echo  Porta 8090 em uso pelo PID %%p - encerrando...
  taskkill /F /PID %%p >nul 2>&1
)
timeout /t 1 /nobreak >nul

echo  Painel:  http://127.0.0.1:8090/
echo  API:     http://127.0.0.1:8090/api
echo  Health:  http://127.0.0.1:8090/api/health?deep=1
echo.
echo  Deixe esta janela ABERTA enquanto usar a API.
echo  Ctrl+C para parar.
echo  ========================================
echo.

node src\index.js
set ERR=%ERRORLEVEL%

echo.
if not "%ERR%"=="0" (
  echo  [ERRO] Servidor encerrou com codigo %ERR%.
  echo  Se a porta estiver ocupada, feche o outro processo ou mude PORT no config.
) else (
  echo  Servidor encerrado.
)
echo.
pause
exit /b %ERR%
