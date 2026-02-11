#!/bin/bash
# Live-Schaltung des ERP-Systems

echo "🚀 ERP System Live-Schaltung"
echo "=============================="
echo ""

# Server-IP ermitteln
SERVER_IP=$(hostname -I | awk '{print $1}')
echo "📍 Server-IP: $SERVER_IP"
echo ""

# Prüfe ob Apache läuft
if systemctl is-active --quiet apache2; then
    echo "✓ Apache läuft"
else
    echo "⚠️  Apache läuft nicht - starte Apache..."
    sudo systemctl start apache2
fi

# Erstelle Symlink
echo ""
echo "📁 Erstelle Web-Zugriff..."
if [ ! -L /var/www/html/projekt1 ]; then
    sudo ln -s /home/maxi/Dokumente/Projekts/projekt1 /var/www/html/projekt1
    echo "✓ Symlink erstellt: /var/www/html/projekt1"
else
    echo "✓ Symlink existiert bereits"
fi

# Setze Berechtigungen
echo ""
echo "🔐 Setze Berechtigungen..."
sudo chown -R www-data:www-data /home/maxi/Dokumente/Projekts/projekt1/projekt1.db
sudo chmod 664 /home/maxi/Dokumente/Projekts/projekt1/projekt1.db
sudo chmod 775 /home/maxi/Dokumente/Projekts/projekt1
echo "✓ Berechtigungen gesetzt"

# Apache neu laden
echo ""
echo "🔄 Apache neu laden..."
sudo systemctl reload apache2
echo "✓ Apache neu geladen"

echo ""
echo "✅ System ist jetzt live!"
echo ""
echo "📱 Zugriff über:"
echo "   http://$SERVER_IP/projekt1/web_oberflaeche.php"
echo "   http://$SERVER_IP/projekt1/login.php"
echo ""
echo "🧪 Test-Seiten:"
echo "   http://$SERVER_IP/projekt1/system_test.php"
echo "   http://$SERVER_IP/projekt1/api_verwaltung.php"
echo ""
echo "🔌 API-Zugriff:"
echo "   http://$SERVER_IP/projekt1/api/index.php?api_key=YOUR_KEY"
echo ""
