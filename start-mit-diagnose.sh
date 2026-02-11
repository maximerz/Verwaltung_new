#!/bin/bash

echo "=========================================="
echo "Netzwerk-Diagnose"
echo "=========================================="
echo ""

# 1. Prüfe alle IP-Adressen
echo "1. Deine IP-Adressen:"
echo "-------------------"
ip addr show | grep "inet " | grep -v "127.0.0.1"
echo ""

# 2. Prüfe ob Port 8000 frei ist
echo "2. Port 8000 Status:"
echo "-------------------"
if lsof -i :8000 > /dev/null 2>&1; then
    echo "Port 8000 ist bereits belegt!"
    lsof -i :8000
else
    echo "Port 8000 ist frei"
fi
echo ""

# 3. Prüfe Firewall
echo "3. Firewall Status:"
echo "-------------------"
if command -v ufw &> /dev/null; then
    sudo ufw status | grep 8000 || echo "Port 8000 nicht in UFW freigegeben"
else
    echo "UFW nicht installiert"
fi
echo ""

# 4. Prüfe iptables
echo "4. iptables Regeln für Port 8000:"
echo "-------------------"
sudo iptables -L -n | grep 8000 || echo "Keine iptables-Regeln für Port 8000"
echo ""

echo "=========================================="
echo "Firewall-Regel erstellen..."
echo "=========================================="
echo ""

# Firewall öffnen
sudo ufw allow 8000/tcp 2>/dev/null && echo "UFW: Port 8000 freigegeben" || echo "UFW nicht aktiv"
sudo iptables -I INPUT -p tcp --dport 8000 -j ACCEPT 2>/dev/null && echo "iptables: Port 8000 freigegeben" || true

echo ""
echo "=========================================="
echo "Starte Server..."
echo "=========================================="
echo ""

cd /home/maxi/Dokumente/Projekts/projekt1

echo "Server-IP: 10.10.20.122"
echo "Port: 8000"
echo ""
echo "Zugriff von anderen PCs:"
echo "  http://10.10.20.122:8000/web_oberflaeche.php"
echo ""
echo "Server läuft auf 0.0.0.0:8000"
echo "Drücke Strg+C zum Beenden"
echo ""

php -S 0.0.0.0:8000 -t /home/maxi/Dokumente/Projekts/projekt1
