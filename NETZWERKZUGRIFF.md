# Netzwerkzugriff einrichten

## Schnellstart

### Linux/Mac:
```bash
./start-network.sh
```

### Windows:
```
Doppelklick auf: start-network.bat
(oder als Administrator für Firewall-Regel)
```

## Was passiert?

1. ✅ Server startet auf **0.0.0.0:8000** (alle Netzwerk-Interfaces)
2. ✅ Firewall-Regel wird erstellt (Port 8000)
3. ✅ Deine lokale IP-Adresse wird angezeigt
4. ✅ Andere PCs können zugreifen

## Zugriff von anderen PCs

### Deine Server-IP herausfinden:

**Linux/Mac:**
```bash
hostname -I
# oder
ip addr show
```

**Windows:**
```cmd
ipconfig
```

### URL für andere PCs:
```
http://DEINE-SERVER-IP:8000/web_oberflaeche.php

Beispiel:
http://192.168.1.100:8000/web_oberflaeche.php
```

## Firewall-Einstellungen

### Linux (UFW):
```bash
sudo ufw allow 8000/tcp
sudo ufw status
```

### Windows:
```
Systemsteuerung → Windows Defender Firewall
→ Erweiterte Einstellungen → Eingehende Regeln
→ Neue Regel → Port → TCP → 8000
```

Oder automatisch mit:
```cmd
netsh advfirewall firewall add rule name="PHP Server Port 8000" dir=in action=allow protocol=TCP localport=8000
```

## Produktionsumgebung (Apache/Nginx/IIS)

Für Produktionsserver siehe die Installationspakete:
- `ServerInstallation/Windows/` für Windows Server mit IIS
- `ServerInstallation/Debian/` für Linux Server mit Apache/Nginx

Diese sind bereits für Netzwerkzugriff konfiguriert!

## Testen

### Vom Server selbst:
```
http://localhost:8000/web_oberflaeche.php
```

### Von anderem PC im Netzwerk:
```
http://SERVER-IP:8000/web_oberflaeche.php
```

### Verbindung testen:
```bash
# Von anderem PC
ping SERVER-IP
telnet SERVER-IP 8000
```

## Häufige Probleme

### Problem: Andere PCs können nicht zugreifen

**Lösung 1: Firewall prüfen**
```bash
# Linux
sudo ufw status
sudo ufw allow 8000/tcp

# Windows
netsh advfirewall firewall show rule name="PHP Server Port 8000"
```

**Lösung 2: Server auf richtigem Interface**
```bash
# Muss 0.0.0.0:8000 sein, nicht 127.0.0.1:8000
php -S 0.0.0.0:8000
```

**Lösung 3: Netzwerk prüfen**
- Sind beide PCs im gleichen Netzwerk?
- Ping funktioniert? `ping SERVER-IP`
- Router-Einstellungen (AP-Isolation deaktivieren)

### Problem: Firewall-Regel kann nicht erstellt werden

**Lösung:**
- Windows: Als Administrator ausführen
- Linux: `sudo` verwenden
- Oder manuell in Firewall-Einstellungen

## Sicherheit

### Entwicklungsumgebung:
- ✅ Nur im lokalen Netzwerk verwenden
- ✅ Nicht ins Internet exponieren
- ✅ Für Tests und Entwicklung

### Produktionsumgebung:
- ❌ PHP Built-in Server NICHT für Produktion
- ✅ Apache/Nginx/IIS verwenden
- ✅ HTTPS aktivieren
- ✅ Firewall richtig konfigurieren
- ✅ Siehe ServerInstallation-Pakete

## Netzwerk-Topologie

```
┌─────────────────┐
│  Dein PC        │
│  (Server)       │
│  192.168.1.100  │
│  Port 8000      │
└────────┬────────┘
         │
    ┌────┴────┐
    │ Router  │
    └────┬────┘
         │
    ┌────┴──────────────────┐
    │                       │
┌───┴────┐            ┌─────┴───┐
│ PC 1   │            │  PC 2   │
│ Client │            │ Client  │
└────────┘            └─────────┘

Alle PCs greifen zu auf:
http://192.168.1.100:8000/web_oberflaeche.php
```

## Erweiterte Konfiguration

### Anderen Port verwenden:
```bash
# Port 8080 statt 8000
php -S 0.0.0.0:8080

# Firewall anpassen
sudo ufw allow 8080/tcp
```

### Nur bestimmte IP-Adressen erlauben:
```bash
# Linux (iptables)
sudo iptables -A INPUT -p tcp --dport 8000 -s 192.168.1.0/24 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 8000 -j DROP
```

### Logs aktivieren:
```bash
php -S 0.0.0.0:8000 -t /pfad/zum/projekt 2>&1 | tee server.log
```

## Support

Bei Problemen:
1. Server läuft? → Terminal zeigt "Listening on 0.0.0.0:8000"
2. Firewall offen? → `sudo ufw status` oder Windows Firewall prüfen
3. Richtige IP? → `hostname -I` oder `ipconfig`
4. Ping funktioniert? → `ping SERVER-IP` von anderem PC
5. Port erreichbar? → `telnet SERVER-IP 8000` von anderem PC

---

**Viel Erfolg! 🚀**
