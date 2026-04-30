#!/bin/bash

echo "=== ERP Server Autostart Installation ==="
echo ""

# Prüfe ob Script ausführbar ist
if [ ! -x "autostart-erp.sh" ]; then
    chmod +x autostart-erp.sh
    echo "✓ Script ausführbar gemacht"
fi

# Kopiere Service-Datei
sudo cp erp-server.service /etc/systemd/system/
echo "✓ Service-Datei kopiert"

# Lade systemd neu
sudo systemctl daemon-reload
echo "✓ systemd neu geladen"

# Aktiviere Service
sudo systemctl enable erp-server.service
echo "✓ Service aktiviert (startet beim Booten)"

# Starte Service jetzt
sudo systemctl start erp-server.service
echo "✓ Service gestartet"

# Zeige Status
echo ""
echo "=== Status ==="
sudo systemctl status erp-server.service --no-pager

echo ""
echo "=== Fertig! ==="
echo "Der ERP-Server startet jetzt automatisch beim Systemstart."
echo ""
echo "Nützliche Befehle:"
echo "  sudo systemctl status erp-server   # Status anzeigen"
echo "  sudo systemctl stop erp-server     # Server stoppen"
echo "  sudo systemctl start erp-server    # Server starten"
echo "  sudo systemctl restart erp-server  # Server neu starten"
echo "  tail -f /tmp/erp-server.log        # Log anzeigen"
