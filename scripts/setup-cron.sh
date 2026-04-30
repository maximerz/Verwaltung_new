#!/bin/bash

# Richtet den stündlichen IP-Monitor als Cron-Job ein

PROJECT_DIR="/home/maxi/Dokumente/Projekts/projekt1"
SCRIPT_PATH="$PROJECT_DIR/ip-monitor.sh"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "=========================================="
echo "Cron-Job für IP-Monitor einrichten"
echo "=========================================="
echo ""

# Prüfen ob Script existiert
if [ ! -f "$SCRIPT_PATH" ]; then
    echo "Fehler: ip-monitor.sh nicht gefunden!"
    exit 1
fi

# Prüfen ob Script ausführbar ist
if [ ! -x "$SCRIPT_PATH" ]; then
    echo "Script wird ausführbar gemacht..."
    chmod +x "$SCRIPT_PATH"
fi

# Cron-Job hinzufügen (stündlich)
CRON_JOB="0 * * * * $SCRIPT_PATH > /dev/null 2>&1"

# Prüfen ob bereits vorhanden
if crontab -l 2>/dev/null | grep -q "ip-monitor.sh"; then
    echo -e "${YELLOW}Cron-Job existiert bereits!${NC}"
    echo "Aktuelle Crontab:"
    crontab -l | grep ip-monitor
    echo ""
    echo "Zum Entfernen: crontab -e und Zeile löschen"
else
    # Cron-Job hinzufügen
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    
    echo -e "${GREEN}Cron-Job erfolgreich eingerichtet!${NC}"
    echo ""
    echo "Der IP-Monitor wird jetzt jede Stunde (Minute 0) ausgeführt."
    echo ""
    echo "Cron-Job: $CRON_JOB"
    echo ""
    
    # Cron-Job anzeigen
    echo "Aktuelle Crontab:"
    crontab -l
fi

echo ""
echo "=========================================="
echo "Manuelle Tests"
echo "=========================================="
echo ""
echo "IP-Monitor jetzt testen:"
echo "  $SCRIPT_PATH"
echo ""
echo "Server-Status anzeigen:"
echo "  $PROJECT_DIR/show-ip.sh"
echo ""

