#!/bin/bash

PROJECT_DIR="/home/maxi/Dokumente/Projekts/projekt1"
SERVER_HOST="${SERVER_HOST:-0.0.0.0}"
SERVER_PORT="${SERVER_PORT:-8000}"
SERVER_DOCROOT="${SERVER_DOCROOT:-$PROJECT_DIR}"
SERVER_ENTRYPOINT="${SERVER_ENTRYPOINT:-web_oberflaeche.php}"

get_primary_ip() {
    local ip

    if command -v ip >/dev/null 2>&1; then
        ip=$(ip route get 1.1.1.1 2>/dev/null | awk '/src/ {for (i=1; i<=NF; i++) if ($i == "src") { print $(i+1); exit }}')
    fi

    if [ -z "$ip" ]; then
        ip=$(hostname -I 2>/dev/null | awk '{print $1}')
    fi

    echo "$ip"
}

get_server_url() {
    local ip
    ip=$(get_primary_ip)
    if [ -n "$ip" ]; then
        echo "http://$ip:$SERVER_PORT/$SERVER_ENTRYPOINT"
    else
        echo "http://localhost:$SERVER_PORT/$SERVER_ENTRYPOINT"
    fi
}

server_process_pattern() {
    echo "php -S.*:$SERVER_PORT"
}
