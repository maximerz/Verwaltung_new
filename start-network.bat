@echo off
chcp 65001 >nul

:: Netzwerkzugriff aktivieren - PHP Built-in Server
:: Ermöglicht Zugriff von anderen PCs im Netzwerk

echo ==========================================
echo Netzwerkzugriff aktivieren
echo ==========================================
echo.

set "PROJECT_DIR=%~dp0"

:: Hole lokale IP-Adresse
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4"') do (
    set IP=%%a
    goto :found
)
:found
set IP=%IP:~1%

echo [INFO] Server-IP: %IP%
echo.
echo Andere PCs koennen zugreifen ueber:
echo http://%IP%:8000/web_oberflaeche.php
echo.
echo [INFO] Firewall-Regel wird erstellt...

:: Firewall-Regel erstellen (erfordert Admin-Rechte)
netsh advfirewall firewall show rule name="PHP Server Port 8000" >nul 2>&1
if %errorlevel% neq 0 (
    netsh advfirewall firewall add rule name="PHP Server Port 8000" dir=in action=allow protocol=TCP localport=8000 >nul 2>&1
    if %errorlevel% equ 0 (
        echo [OK] Firewall-Regel erstellt
    ) else (
        echo [WARNUNG] Firewall-Regel konnte nicht erstellt werden
        echo Bitte als Administrator ausfuehren oder manuell Port 8000 freigeben
    )
) else (
    echo [OK] Firewall-Regel existiert bereits
)

echo.
echo Server startet auf 0.0.0.0:8000 (alle Netzwerk-Interfaces)
echo Druecke Strg+C zum Beenden
echo.

timeout /t 2 /nobreak >nul

cd /d "%PROJECT_DIR%"
php -S 0.0.0.0:8000
