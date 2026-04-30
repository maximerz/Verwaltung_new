#!/bin/bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/server-env.sh"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

cd "$PROJECT_DIR"

LOCAL_IP="$(get_primary_ip)"

echo "=========================================="
echo "Netzwerkzugriff aktivieren"
echo "=========================================="
echo ""

if [ -z "$LOCAL_IP" ]; then
    echo -e "${RED}Warnung: Konnte keine lokale IP-Adresse ermitteln${NC}"
else
    echo -e "${GREEN}Server-IP: $LOCAL_IP${NC}"
fi

echo "Bind-Adresse: $SERVER_HOST"
echo "Port: $SERVER_PORT"
echo ""
echo "Erreichbar über:"
echo -e "${YELLOW}http://localhost:$SERVER_PORT/$SERVER_ENTRYPOINT${NC}"
if [ -n "$LOCAL_IP" ]; then
    echo -e "${YELLOW}http://$LOCAL_IP:$SERVER_PORT/$SERVER_ENTRYPOINT${NC}"
fi
echo ""

if command -v ufw >/dev/null 2>&1; then
    echo "Firewall-Regel für Port $SERVER_PORT wird versucht zu setzen..."
    sudo ufw allow "$SERVER_PORT/tcp" 2>/dev/null || echo "UFW-Regel konnte nicht automatisch gesetzt werden"
    echo ""
fi

echo "Server startet auf $SERVER_HOST:$SERVER_PORT"
echo "Drücke Strg+C zum Beenden"
echo ""

php -S "$SERVER_HOST:$SERVER_PORT" -t "$SERVER_DOCROOT"
