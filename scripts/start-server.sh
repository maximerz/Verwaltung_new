#!/bin/bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/server-env.sh"

cd "$PROJECT_DIR"

LOCAL_IP="$(get_primary_ip)"

echo "=========================================="
echo "PHP Server"
echo "=========================================="
echo ""
echo "Bind-Adresse: $SERVER_HOST"
echo "Port: $SERVER_PORT"
echo "Dokumentenstamm: $SERVER_DOCROOT"
echo ""
echo "Lokaler Zugriff:"
echo "  http://localhost:$SERVER_PORT/$SERVER_ENTRYPOINT"
echo "  http://127.0.0.1:$SERVER_PORT/$SERVER_ENTRYPOINT"
echo ""
echo "Netzwerk-Zugriff:"
if [ -n "$LOCAL_IP" ]; then
    echo "  http://$LOCAL_IP:$SERVER_PORT/$SERVER_ENTRYPOINT"
else
    echo "  Keine lokale IP erkannt"
fi
echo ""
echo "Server läuft auf allen Netzwerk-Interfaces ($SERVER_HOST:$SERVER_PORT)"
echo "Drücke Strg+C zum Beenden"
echo ""

php -S "$SERVER_HOST:$SERVER_PORT" -t "$SERVER_DOCROOT"
