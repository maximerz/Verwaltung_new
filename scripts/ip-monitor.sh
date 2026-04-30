#!/bin/bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/server-env.sh"

CONFIG_FILE="$PROJECT_DIR/ip-config.txt"
LOG_FILE="$PROJECT_DIR/ip-monitor.log"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
}

restart_server() {
    log_message "Server wird neu gestartet..."

    pkill -f "$(server_process_pattern)" 2>/dev/null || true
    sleep 2

    cd "$PROJECT_DIR"
    nohup php -S "$SERVER_HOST:$SERVER_PORT" -t "$SERVER_DOCROOT" >> "$LOG_FILE" 2>&1 &
    sleep 2

    local new_ip
    new_ip="$(get_primary_ip)"

    cat > "$CONFIG_FILE" <<EOF
PORT=$SERVER_PORT
HOST=$SERVER_HOST
IP=$new_ip
ENTRYPOINT=$SERVER_ENTRYPOINT
LAST_UPDATE=$(date '+%Y-%m-%d %H:%M:%S')
STATUS=running
EOF

    log_message "Server läuft unter $(get_server_url)"
    echo -e "${GREEN}Server neu gestartet${NC}"
    echo "URL: $(get_server_url)"
}

echo "=========================================="
echo "IP-Monitor für ERP-Server"
echo "=========================================="
echo ""

CURRENT_IP="$(get_primary_ip)"

if [ -z "$CURRENT_IP" ]; then
    log_message "FEHLER: Konnte keine IP-Adresse ermitteln"
    echo -e "${RED}Fehler: Keine IP-Adresse gefunden${NC}"
    exit 1
fi

if [ -f "$CONFIG_FILE" ]; then
    OLD_IP="$(grep '^IP=' "$CONFIG_FILE" | cut -d'=' -f2)"

    echo "Alte IP: $OLD_IP"
    echo "Aktuelle IP: $CURRENT_IP"
    echo ""

    if [ "$CURRENT_IP" != "$OLD_IP" ]; then
        echo -e "${YELLOW}IP-Adresse hat sich geändert${NC}"
        log_message "IP-Änderung: $OLD_IP -> $CURRENT_IP"
        restart_server
    elif ! pgrep -f "$(server_process_pattern)" > /dev/null; then
        echo "Server läuft nicht - wird gestartet..."
        log_message "Server nicht gefunden - wird gestartet"
        restart_server
    else
        log_message "IP unverändert ($CURRENT_IP), Server läuft"
        cat > "$CONFIG_FILE" <<EOF
PORT=$SERVER_PORT
HOST=$SERVER_HOST
IP=$CURRENT_IP
ENTRYPOINT=$SERVER_ENTRYPOINT
LAST_UPDATE=$(date '+%Y-%m-%d %H:%M:%S')
STATUS=running
EOF
    fi
else
    echo "Erste Konfiguration wird erstellt..."
    cat > "$CONFIG_FILE" <<EOF
PORT=$SERVER_PORT
HOST=$SERVER_HOST
IP=$CURRENT_IP
ENTRYPOINT=$SERVER_ENTRYPOINT
LAST_UPDATE=$(date '+%Y-%m-%d %H:%M:%S')
STATUS=initial
EOF

    log_message "Erste Konfiguration erstellt mit IP: $CURRENT_IP"

    if ! pgrep -f "$(server_process_pattern)" > /dev/null; then
        restart_server
    fi
fi

echo ""
echo "Aktuelle URL: $(get_server_url)"
echo "Log-Datei: $LOG_FILE"
echo ""
