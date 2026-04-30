#!/bin/bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/server-env.sh"

LOG_FILE="/tmp/erp-server.log"
CONFIG_FILE="$PROJECT_DIR/ip-config.txt"

echo "$(date): ERP Server wird gestartet..." >> "$LOG_FILE"

sleep 5

pkill -f "$(server_process_pattern)" 2>/dev/null || true

cd "$PROJECT_DIR"

nohup php -S "$SERVER_HOST:$SERVER_PORT" -t "$SERVER_DOCROOT" >> "$LOG_FILE" 2>&1 &
SERVER_PID=$!
echo "$SERVER_PID" > /tmp/erp-server.pid

CURRENT_IP="$(get_primary_ip)"

cat > "$CONFIG_FILE" <<EOF
PORT=$SERVER_PORT
HOST=$SERVER_HOST
IP=$CURRENT_IP
ENTRYPOINT=$SERVER_ENTRYPOINT
LAST_UPDATE=$(date '+%Y-%m-%d %H:%M:%S')
STATUS=running
PID=$SERVER_PID
EOF

echo "$(date): ERP Server gestartet auf $SERVER_HOST:$SERVER_PORT (PID: $SERVER_PID)" >> "$LOG_FILE"
echo "$(date): Zugriff lokal über http://localhost:$SERVER_PORT/$SERVER_ENTRYPOINT" >> "$LOG_FILE"
if [ -n "$CURRENT_IP" ]; then
    echo "$(date): Zugriff im Netzwerk über http://$CURRENT_IP:$SERVER_PORT/$SERVER_ENTRYPOINT" >> "$LOG_FILE"
fi

exit 0
