# ERP System - Projektstruktur

## 📁 Verzeichnisstruktur

```
projekt1/
├── api/                    # REST API Endpunkte
│   ├── index.php
│   └── *.php              # API-Module
│
├── assets/                # Öffentliche Assets
│   ├── css/              # Stylesheets
│   ├── images/           # Bilder & Logos
│   └── style.css
│
├── backups/              # Datenbank-Backups
│
├── docs/                 # Dokumentation
│   ├── technisch/        # Technische Dokumentation
│   │   ├── README.md
│   │   ├── INSTALLATION.md
│   │   ├── TEST_ANLEITUNG.md
│   │   └── *.md
│   └── benutzer/         # Benutzer-Dokumentation
│
├── includes/            # Wiederverwendbare PHP-Komponenten
│   ├── header.php
│   ├── footer.php
│   ├── navigation.php
│   ├── 2fa_functions.php
│   └── *.php
│
├── scripts/             # Shell-Skripte & Cron-Jobs
│   ├── backup.sh
│   ├── setup-cron.sh
│   ├── start-server.sh
│   └── *.sh
│
├── src/                 # Kern-Anwendungslogik
│   ├── config/          # Konfigurationsdateien
│   │   ├── encryption.php
│   │   ├── ldap_config.php
│   │   └── security.php
│   │
│   ├── models/          # Datenmodelle (空 - für zukünftige Nutzung)
│   │
│   ├── services/        # Geschäftslogik (空 - für zukünftige Nutzung)
│   │
│   ├── utils/           # Utility-Funktionen
│   │   ├── db_connection.php
│   │   ├── generate_pdf.php
│   │   ├── generate_angebot_pdf.php
│   │   └── generate_rechnung_pdf.php
│   │
│   └── config/
│
├── uploads/             # Benutzer-Uploads
│   └── lieferscheine/
│
├── vendor/              # Composer-Abhängigkeiten
│
├── *.php               # Hauptseiten (Web-Interface)
│
├── projekt1.db         # SQLite Datenbank
│
└── composer.json       # PHP Abhängigkeiten
```

## 🔄 Rückwärts-Kompatibilität

Einige Dateien wurden nach `src/` verschoben, aber es gibt Kompatibilitäts-Wrapper im Hauptverzeichnis:
- `db_connection.php` → verweist auf `src/db_connection.php`
- `generate_pdf.php` → verweist auf `src/utils/generate_pdf.php`
- `generate_angebot_pdf.php` → verweist auf `src/utils/generate_angebot_pdf.php`
- `generate_rechnung_pdf.php` → verweist auf `src/utils/generate_rechnung_pdf.php`

**Hinweis:** Alle bestehenden Links und Inkludierungen funktionieren weiterhin!

## 🚀 Schnellstart

### Webserver starten
```bash
./scripts/start-server.sh
# oder
./scripts/start-network.sh
```

### Backup erstellen
```bash
./scripts/backup.sh
```

### Im Browser öffnen
```
http://localhost:8000/web_oberflaeche.php
```

## 📝 Wichtige Hauptseiten

| Datei | Beschreibung |
|-------|---------------|
| `web_oberflaeche.php` | Haupt-Dashboard |
| `kunden_verwaltung.php` | Kundenverwaltung |
| `lagerverwaltung.php` | Lagerverwaltung |
| `finanzbuchhaltung.php` | Finanzbuchhaltung |
| `produktkatalog.php` | Produktkatalog |
| `admin_dashboard.php` | Admin-Bereich |

## 🛠️ Technologie-Stack

- **Backend:** PHP 7.4+ / PHP 8.1+
- **Datenbank:** SQLite (Standard) / MySQL (optional)
- **Frontend:** HTML5, CSS3, JavaScript (Bootstrap 5)
- **PDF-Generation:** TCPDF / FPDF
- **Authentifizierung:** Session-basiert mit 2FA-Unterstützung

## 📄 Lizenz

Eigenes Projekt - Maxi

