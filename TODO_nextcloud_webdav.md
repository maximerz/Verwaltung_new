# TODO: Nextcloud WebDAV Integration (Phase 1)

## Phase 1 – Upload von TCPDF-PDFs nach Nextcloud (optional via URL)

### 1) Konfiguration
- [x] `src/config/nextcloud_config.php` anlegen (Base URL, Username, App-Passwort, Root-Ordner)
- [ ] Hinweis in README/Docs ergänzen (wo App-Passwort herkommt)

### 2) WebDAV Helper
- [x] `src/utils/nextcloud_webdav.php` anlegen
  - [x] MKCOL (Ordner anlegen)
  - [x] PUT (Datei hochladen)

### 3) PDF-Generierung: Stream + Optional Upload
- [x] `src/utils/generate_pdf.php` so angepasst, dass die PDF weiterhin im Browser gestreamt wird
- [x] optional Upload aktivieren via `?nextcloud=1`
- [ ] Optional: Fehler/Status (Upload fehlgeschlagen) robust im UI loggen

### 4) Remote Struktur (Phase 1)
- [x] Pfad-Schema: `ERP/<kundennummer>/<docKind>/<filename>`
- [ ] docKind finalisieren (aktuell z.B. `Rechnungen/Bestellungen`)

### 5) Testing
- [ ] Manuell testen:
  - `generate_pdf.php?order_id=...&nextcloud=1`
  - Upload in Nextcloud Ordner prüfen
- [ ] Prüfen, ob PHP/cURL Extension vorhanden ist (sonst Integration nicht möglich)

