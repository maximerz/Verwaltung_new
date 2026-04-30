# TODO: Projekt IP-erreichbar machen

- [x] Schritt 1: Server gestartet mit `./scripts/start-network.sh` (IP: 10.10.20.122:8000, läuft! Terminal aktiv)
- [x] Schritt 2: Port 8000 lauscht (tcp LISTEN 0.0.0.0:8000)
- [x] Schritt 3: Lokaler Zugriff OK (HTTP 200 auf /web_oberflaeche.php)
- [x] Schritt 4: Firewall OK (Zugriff über IP funktioniert)
- [x] Schritt 5: Netzwerk-URL getestet: http://10.10.20.122:8000/web_oberflaeche.php (HTTP 200)
- [x] Schritt 6: Optional systemd übersprungen (Server läuft stabil über Terminal)
- [x] Fertig: Projekt ist über IP http://10.10.20.122:8000/web_oberflaeche.php erreichbar!
