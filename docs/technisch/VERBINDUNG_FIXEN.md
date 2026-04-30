# Verbindung zum ERP-Projekt - Fehlerbehebung

Diese Anleitung hilft dir, wenn das ERP-Projekt nicht erreichbar ist.

---

## Schnell-Check (in 2 Minuten)

Führe diese Schritte der Reihe nach aus:

| Schritt | Befehl/Prüfung | Erwartetes Ergebnis |
|---------|----------------|---------------------|
| 1 | Server-Terminal prüfen | Läuft noch? |
| 2 | `hostname -I` | IP-Adresse anzeigen |
| 3 | `curl localhost:8000/web_oberflaeche.php` | HTML-Inhalt |
| 4 | Firewall prüfen | Port 8000 offen |

---

## Detaillierte Fehlerbehebung

### 1. Server läuft nicht mehr

**Symptom:** Browser zeigt "Verbindung abgelehnt" oder "Connection refused"

**Prüfung:**
```bash
# Prozess prüfen
ps aux | grep php

# Oder testen ob Port belegt ist
netstat -tuln | grep 8000
```

**Lösung:**
```bash
# Server neu starten
cd /home/maxi/Dokumente/Projekts/projekt1
./start-network.sh
```

---

### 2. Falsche IP-Adresse

**Symptom:** Server läuft, aber anderer PC kann nicht zugreifen

**Prüfung:**
```bash
# Aktuelle IP anzeigen
hostname -I
```

**Lösung:**

1. Terminal schließen (Strg+C)
2. Script erneut starten:
```bash
./start-network.sh
```

3. Neue IP-Adresse notieren

---

### 3. Firewall blockiert Port

**Symptom:** Server läuft, lokaler Zugriff funktioniert, Netzwerkzugriff nicht

**Prüfung (Linux):**
```bash
sudo ufw status
```

**Prüfung (Windows):**
```cmd
netsh advfirewall firewall show rule name="PHP Server Port 8000"
```

**Lösung (Linux):**
```bash
sudo ufw allow 8000/tcp
sudo ufw enable
```

**Lösung (Windows - als Administrator):**
```cmd
netsh advfirewall firewall add rule name="PHP Server Port 8000" dir=in action=allow protocol=TCP localport=8000
```

---

### 4. Port bereits belegt

**Symptom:** "Address already in use" Fehler

**Prüfung:**
```bash
# Wer belegt Port 8000?
sudo lsof -i :8000

# Oder
netstat -tuln | grep 8000
```

**Lösung:**

1. Anderen Prozess beenden:
```bash
sudo kill -9 <PID>
```

2. Oder anderen Port verwenden:
```bash
# Port 8080 statt 8000
php -S 0.0.0.0:8080 -t /home/maxi/Dokumente/Projekts/projekt1
```

---

### 5. Datenbank-Probleme

**Symptom:** "Database connection failed" oder weiße Seite

**Prüfung:**
```bash
# Existiert die Datenbank?
ls -la projekt1.db
```

**Lösung:**

1. Datenbank-Datei prüfen:
```bash
# Wenn nicht vorhanden, neu erstellen
touch projekt1.db
chmod 666 projekt1.db
```

2. Oder Backup wiederherstellen:
```bash
# Neuestes Backup finden
ls -la backups/

# Backup entpacken
gunzip backups/backup_20260211_142710.db.gz

# Backup einspielen
cp projekt1.db projekt1.db.backup
cp backups/backup_20260211_142710.db projekt1.db
```

---

### 6. PHP nicht installiert

**Symptom:** "php: command not found"

**Prüfung:**
```bash
php --version
```

**Lösung:**

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install php php-sqlite3 php-curl
```

**Arch/Manjaro:**
```bash
sudo pacman -S php php-sqlite
```

---

### 7. Netzwerk-Probleme

**Symptom:** Ping funktioniert nicht

**Prüfung:**
```bash
# Vom anderen PC aus
ping <SERVER-IP>

# Port testen
telnet <SERVER-IP> 8000

# Oder mit nc
nc -zv <SERVER-IP> 8000
```

**Lösung:**

1. Beide PCs im gleichen Netzwerk?
2. Router/AP prüfen (AP-Isolation deaktivieren)
3. VPN prüfen (kann Zugriff blockieren)

---

## Zugriff URLs

| Zugriff von | URL |
|-------------|-----|
| Lokal | `http://localhost:8000/web_oberflaeche.php` |
| Lokal (Alternativ) | `http://127.0.0.1:8000/web_oberflaeche.php` |
| Netzwerk | `http://192.168.X.X:8000/web_oberflaeche.php` |

Ersetze `192.168.X.X` mit deiner tatsächlichen IP-Adresse!

---

## Automatischer Systemtest

Führe den Systemtest aus:
```bash
php system_test.php
```

Oder erstelle einen eigenen Test:
```bash
php -r "
echo 'PHP Version: ' . phpversion() . PHP_EOL;
echo 'SQLite: ' . (extension_loaded('sqlite3') ? 'OK' : 'FEHLER') . PHP_EOL;
echo 'PDO SQLite: ' . (extension_loaded('pdo_sqlite') ? 'OK' : 'FEHLER') . PHP_EOL;
"
```

---


## Häufige Fehlermeldungen

| Fehler | Ursache | Lösung |
|--------|---------|--------|
| "Connection refused" | Server nicht gestartet | `./start-network.sh` starten |
| "Connection timed out" | Firewall blockiert | Firewall-Regel hinzufügen |
| "Unable to connect" | Falsche IP | Korrekte IP verwenden |
| "Database not found" | projekt1.db fehlt | Datenbank neu erstellen |
| "Permission denied" | Falsche Rechte | `chmod 666 projekt1.db` |
| "Seite nicht gefunden" oder "SSL Error" | **HTTPS verwendet** | **UNBEDINGT `http://` nutzen, NICHT `https://`** |

---

## Notfall-Wiederherstellung

Wenn nichts funktioniert:

```bash
# 1. Server stoppen (Strg+C im Terminal)

# 2. Zur Projekt-Ordner wechseln
cd /home/maxi/Dokumente/Projekts/projekt1

# 3. Datenbank-Rechte prüfen
chmod 666 projekt1.db

# 4. Server neu starten
./start-network.sh

# 5. Im Browser testen
firefox http://localhost:8000/web_oberflaeche.php
```

---

## Kontakt bei weiteren Problemen

Notiere folgende Informationen:

1. Welcher Fehler erscheint?
2. Lokal oder Netzwerk?
3. Ausgabe von `hostname -I`
4. Ausgabe von `php --version`
5. Letzte Meldung im Server-Terminal

---

**Tipp:** Halte das Terminal mit dem laufenden Server immer im Blick. Bei Fehlern werden dort Meldungen angezeigt!

