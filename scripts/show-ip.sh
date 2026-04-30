#!/bin/bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/server-env.sh"

CONFIG_FILE="$PROJECT_DIR/ip-config.txt"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

CURRENT_IP="$(get_primary_ip)"

echo "=========================================="
echo "ERP Server Status"
echo "=========================================="
echo ""

if [ -z "$CURRENT_IP" ]; then
    echo -e "${RED}Keine IP-Adresse gefunden${NC}"
    exit 1
fi

if pgrep -f "$(server_process_pattern)" > /dev/null; then
    SERVER_STATUS="${GREEN}LÄUFT${NC}"
else
    SERVER_STATUS="${RED}STOPP${NC}"
fi

echo -e "IP-Adresse:  ${GREEN}$CURRENT_IP${NC}"
echo -e "Bind:        $SERVER_HOST"
echo -e "Port:        $SERVER_PORT"
echo -e "Server:      $SERVER_STATUS"
echo ""
echo "=========================================="
echo "Zugriffs-URLs"
echo "=========================================="
echo ""
echo "Lokal:"
echo "  http://localhost:$SERVER_PORT/$SERVER_ENTRYPOINT"
echo "  http://127.0.0.1:$SERVER_PORT/$SERVER_ENTRYPOINT"
echo ""
echo "Netzwerk:"
echo -e "  ${YELLOW}http://$CURRENT_IP:$SERVER_PORT/$SERVER_ENTRYPOINT${NC}"
echo ""

if [ -f "$CONFIG_FILE" ]; then
    echo "=========================================="
    echo "Konfiguration"
    echo "=========================================="
    cat "$CONFIG_FILE"
    echo ""
fi
