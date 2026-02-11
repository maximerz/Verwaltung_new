# 📘 ERP-System Installationsanleitung

## Inhaltsverzeichnis
1. [Systemanforderungen](#systemanforderungen)
2. [Schnellinstallation](#schnellinstallation)
3. [Manuelle Installation](#manuelle-installation)
4. [Webserver-Konfiguration](#webserver-konfiguration)
5. [Produktiv-Deployment](#produktiv-deployment)
6. [Troubleshooting](#troubleshooting)

---

## 1. Systemanforderungen

### Minimum:
- **PHP:** 7.4 oder höher (empfohlen: PHP 8.1+)
- **Webserver:** Apache 2.4+ oder Nginx 1.18+
- **Datenbank:** SQLite 3 (bereits in PHP integriert)
- **Speicher:** 512 MB RAM
- **Festplatte:** 100 MB freier Speicher

### Empfohlen für Produktivbetrieb:
- **PHP:** 8.1+ mit Argon2ID-Support
- **Webserver:** Apache/Nginx mit SSL/TLS
- **RAM:** 2 GB+
- **Festplatte:** 1 GB+ (für Backups und Uploads)

### Erforderliche PHP-Erweiterungen:
```bash
php -m | grep -E "pdo|sqlite|openssl|json|mbstring|session"
```

Benötigt:
- ✅ PDO
- ✅ pdo_sqlite
- ✅ openssl
- ✅ json
- ✅ mbstring
- ✅ session

---

## 2. Schnellinstallation (Automatisch)

### Schritt 1: Dateien hochladen
```bash
# Alle Projektdateien in das Webserver-Verzeichnis kopieren
# z.B. /var/www/html/erp/ oder C:\xampp\htdocs\erp\
```

### Schritt 2: Berechtigungen setzen (Linux/Mac)
```bash
cd /var/www/html/erp/
chmod 755 .
chmod 755 *.php
chmod 777 uploads/
chmod 777 backups/
chmod 666 projekt1.db  # Falls bereits vorhanden
```

### Schritt 3: Installation ausführen
```
1. Browser öffnen
2. Navigieren zu: http://ihre-domain.de/erp/install.php
3. Warten bis Installation abgeschlossen ist
4. Zugangsdaten notieren:
   - Benutzername: admin
   - Passwort: ERP!
5. install.php SOFORT löschen!
```

### Schritt 4: Erster Login
```
1. Öffnen: http://ihre-domain.de/erp/login.php
2. Einloggen mit: admin / ERP!
3. Passwort SOFORT ändern unter: Admin → Benutzerverwaltung
```

---

## 3. Manuelle Installation

### Schritt 1: Verzeichnisstruktur erstellen
```bash
mkdir -p uploads/lieferscheine
mkdir -p backups
mkdir -p assets/css
mkdir -p assets/images
chmod 777 uploads/ backups/
```

### Schritt 2: Datenbank initialisieren
```bash
# SQLite-Datenbank wird automatisch erstellt
# Alternativ manuell:
sqlite3 projekt1.db < setup_database.sql
```

### Schritt 3: Admin-Benutzer erstellen
```php
<?php
require_once 'db_connection.php';
$password = password_hash('ERP!', PASSWORD_ARGON2ID);
$stmt = $PDO->prepare("INSERT INTO users (username, password, role, can_manage_users) VALUES (?, ?, ?, ?)");
$stmt->execute(['admin', $password, 'admin', 1]);
?>
```

---

## 4. Webserver-Konfiguration

### Apache (.htaccess)
```apache
# Bereits in .htaccess vorhanden:

# PHP-Einstellungen
php_value upload_max_filesize 50M
php_value post_max_size 50M
php_value max_execution_time 300
php_value session.gc_maxlifetime 1800

# Sicherheit
<Files "projekt1.db">
    Require all denied
</Files>

<Files "*.log">
    Require all denied
</Files>

# HTTPS erzwingen (Produktiv)
# RewriteEngine On
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Nginx (nginx.conf)
```nginx
server {
    listen 80;
    server_name ihre-domain.de;
    root /var/www/html/erp;
    index login.php;

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Datenbank schützen
    location ~ projekt1\.db$ {
        deny all;
    }

    # Uploads erlauben
    location /uploads/ {
        allow all;
    }

    # Backups schützen
    location /backups/ {
        deny all;
    }

    # HTTPS erzwingen (Produktiv)
    # return 301 https://$server_name$request_uri;
}
```

### Systemd Service (Dauerhaft laufen lassen)
```bash
# /etc/systemd/system/erp-webserver.service
[Unit]
Description=ERP System Web Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html/erp
ExecStart=/usr/bin/php -S 0.0.0.0:8080 -t /var/www/html/erp
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

```bash
# Service aktivieren
sudo systemctl daemon-reload
sudo systemctl enable erp-webserver
sudo systemctl start erp-webserver
sudo systemctl status erp-webserver

# Zugriff: http://server-ip:8080
```

---

## 5. Produktiv-Deployment

### Checkliste vor Go-Live:

#### 1. Sicherheit
```bash
# ✅ install.php gelöscht
rm install.php

# ✅ Admin-Passwort geändert
# Über: Admin → Benutzerverwaltung

# ✅ Verschlüsselungsschlüssel setzen
export ERP_ENCRYPTION_KEY="IHR_SICHERER_SCHLÜSSEL_HIER"
# In /etc/environment oder .env-Datei

# ✅ Dateirechte prüfen
chmod 755 *.php
chmod 777 uploads/ backups/
chmod 600 projekt1.db

# ✅ HTTPS aktivieren (Let's Encrypt)
sudo certbot --apache -d ihre-domain.de
```

#### 2. Performance
```bash
# PHP OPcache aktivieren
sudo nano /etc/php/8.1/apache2/php.ini

opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2

# Apache/Nginx neu starten
sudo systemctl restart apache2
```

#### 3. Backup-Strategie
```bash
# Cronjob für tägliche Backups
crontab -e

# Täglich um 2 Uhr nachts
0 2 * * * /usr/bin/php /var/www/html/erp/backup_script.php

# Backup-Skript erstellen:
cat > backup_script.php << 'EOF'
<?php
require_once 'db_connection.php';
require_once 'security.php';
$backup = create_encrypted_backup($PDO);
echo "Backup erstellt: $backup\n";
EOF
```

#### 4. Monitoring
```bash
# Log-Dateien überwachen
tail -f /var/log/apache2/error.log
tail -f /var/log/php8.1-fpm.log

# Disk Space prüfen
df -h /var/www/html/erp/

# Datenbank-Größe
ls -lh projekt1.db
```

#### 5. DSGVO-Compliance
```
✅ Cookie-Banner aktiv
✅ Datenschutzerklärung vorhanden (datenschutz.php)
✅ Impressum vorhanden (impressum.php)
✅ Verschlüsselung aktiviert
✅ Audit-Log läuft
✅ Backup-Verschlüsselung aktiv
```

---

## 6. Troubleshooting

### Problem: "Permission denied" beim Schreiben
```bash
# Lösung: Berechtigungen korrigieren
sudo chown -R www-data:www-data /var/www/html/erp/
sudo chmod 777 uploads/ backups/
```

### Problem: "Database locked"
```bash
# Lösung: SQLite-Timeout erhöhen
# In db_connection.php:
$PDO->setAttribute(PDO::ATTR_TIMEOUT, 30);
```

### Problem: Session-Fehler
```bash
# Lösung: Session-Verzeichnis prüfen
sudo chmod 1733 /var/lib/php/sessions
sudo chown root:www-data /var/lib/php/sessions
```

### Problem: "Cannot modify header information"
```bash
# Lösung: Keine Ausgabe vor session_start()
# Prüfen Sie alle PHP-Dateien auf Leerzeichen/BOM vor <?php
```

### Problem: Webserver startet nicht
```bash
# Apache-Fehler prüfen
sudo apache2ctl configtest
sudo systemctl status apache2
sudo journalctl -xe

# Nginx-Fehler prüfen
sudo nginx -t
sudo systemctl status nginx
```

---

## 7. Erste Schritte nach Installation

### 1. System konfigurieren
```
1. Login als admin
2. Admin → DSGVO → Einstellungen prüfen
3. Admin → Benutzerverwaltung → Weitere Benutzer anlegen
4. Finanzbuchhaltung → Kontenplan → An Unternehmen anpassen
```

### 2. Stammdaten anlegen
```
1. Kunden → Neuer Kunde → Erste Kunden anlegen
2. Produkte → Neues Produkt → Produktkatalog aufbauen
3. Lager → Neuer Artikel → Lagerartikel erfassen
4. Lieferanten → Neuer Lieferant → Lieferanten erfassen
```

### 3. Workflow testen
```
1. Kunde anlegen
2. Angebot erstellen
3. Angebot in Bestellung umwandeln
4. Rechnung erstellen
5. Zahlung buchen
```

---

## 8. Support & Updates

### Dokumentation
- **Benutzerhandbuch:** README.md
- **API-Dokumentation:** /docs/api.md
- **DSGVO-Dokumentation:** Admin → DSGVO

### Logs & Debugging
```bash
# PHP-Fehler aktivieren (nur Development!)
# In php.ini:
display_errors = On
error_reporting = E_ALL
log_errors = On
error_log = /var/log/php_errors.log
```

### Backup & Restore
```bash
# Backup erstellen
cp projekt1.db projekt1_backup_$(date +%Y%m%d).db
tar -czf erp_backup_$(date +%Y%m%d).tar.gz *.php uploads/ projekt1.db

# Restore
tar -xzf erp_backup_20240101.tar.gz
```

---

## 9. Produktiv-URLs

Nach erfolgreicher Installation:

- **Login:** https://ihre-domain.de/erp/login.php
- **Dashboard:** https://ihre-domain.de/erp/web_oberflaeche.php
- **Admin-Bereich:** https://ihre-domain.de/erp/user_management.php
- **DSGVO:** https://ihre-domain.de/erp/dsgvo_verwaltung.php

---

## 10. Wichtige Hinweise

⚠️ **SICHERHEIT:**
- Ändern Sie SOFORT das Admin-Passwort!
- Löschen Sie install.php nach der Installation!
- Aktivieren Sie HTTPS für Produktivbetrieb!
- Setzen Sie einen sicheren Encryption Key!

✅ **DSGVO:**
- System ist DSGVO-konform konfiguriert
- Cookie-Banner ist aktiv
- Audit-Log läuft automatisch
- Verschlüsselung ist aktiviert

📞 **Support:**
- Bei Problemen: Logs prüfen
- Dokumentation lesen
- Backup vor Änderungen erstellen

---

**Viel Erfolg mit Ihrem neuen ERP-System! 🚀**
