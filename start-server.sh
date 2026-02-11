#!/bin/bash

# Server mit Netzwerkzugriff starten
# Deine IP: 10.10.20.122

cd /home/maxi/Dokumente/Projekts/projekt1

echo "=========================================="
echo "PHP Server mit Netzwerkzugriff"
echo "=========================================="
echo ""
echo "Server-IP: 10.10.20.122"
echo "Port: 8000"
echo ""
echo "Zugriff von diesem PC:"
echo "  http://localhost:8000/web_oberflaeche.php"
echo "  http://127.0.0.1:8000/web_oberflaeche.php"
echo ""
echo "Zugriff von anderen PCs im Netzwerk:"
echo "  http://10.10.20.122:8000/web_oberflaeche.php"
echo ""
echo "Server läuft auf allen Netzwerk-Interfaces (0.0.0.0:8000)"
echo "Drücke Strg+C zum Beenden"
echo ""

# Starte Server auf allen Interfaces
php -S 0.0.0.0:8000 -t /home/maxi/Dokumente/Projekts/projekt1
