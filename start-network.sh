#!/bin/bash

# Netzwerkzugriff aktivieren - PHP Built-in Server
# Ermöglicht Zugriff von anderen PCs im Netzwerk

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "=========================================="
echo "Netzwerkzugriff aktivieren"
echo "=========================================="
echo ""

# Projekt-Verzeichnis
PROJECT_DIR="/home/maxi/Dokumente/Projekts/projekt1"
cd "$PROJECT_DIR"

# Hole lokale IP-Adresse
LOCAL_IP=$(hostname -I | awk '{print $1}')

echo -e "${GREEN}Server-IP: $LOCAL_IP${NC}"
echo ""
echo "Andere PCs können zugreifen über:"
echo -e "${YELLOW}http://$LOCAL_IP:8000/web_oberflaeche.php${NC}"
echo ""
echo "Firewall-Regel wird erstellt..."

# Firewall-Regel für Port 8000
sudo ufw allow 8000/tcp 2>/dev/null || echo "UFW nicht aktiv oder nicht installiert"

echo ""
echo "Server startet auf 0.0.0.0:8000 (alle Netzwerk-Interfaces)"
echo "Drücke Strg+C zum Beenden"
echo ""

# Starte Server auf allen Interfaces
php -S 0.0.0.0:8000 -t "$PROJECT_DIR"
