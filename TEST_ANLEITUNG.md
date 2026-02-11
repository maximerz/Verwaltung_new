# 🧪 ERP System - Test-Anleitung

## Neu implementierte Features

### ✅ 1. Audit-Log System (Compliance & Nachvollziehbarkeit)
**Was es macht:** Protokolliert alle wichtigen Änderungen im System

**Testen:**
1. Öffne eine beliebige Seite (z.B. Kunde bearbeiten)
2. Ändere Daten und speichere
3. Prüfe in der Datenbank: `SELECT * FROM audit_log ORDER BY timestamp DESC LIMIT 10`
4. Du solltest die Änderung mit User, Zeitstempel und alten/neuen Werten sehen

---

### ✅ 2. Automatisches Backup-System
**Was es macht:** Erstellt automatische Backups der Datenbank

**Testen:**
```bash
# Manuelles Backup erstellen
cd /home/maxi/Dokumente/Projekts/projekt1
./backup.sh

# Prüfe ob Backup erstellt wurde
ls -lh backups/

# Automatisches Backup einrichten (täglich um 2 Uhr nachts)
crontab -e
# Füge hinzu:
0 2 * * * /home/maxi/Dokumente/Projekts/projekt1/backup.sh
```

**Backup wiederherstellen:**
```bash
# Backup entpacken
gunzip backups/backup_YYYYMMDD_HHMMSS.db.gz

# Datenbank ersetzen
cp backups/backup_YYYYMMDD_HHMMSS.db database.db
```

---

### ✅ 3. REST-API für Integrationen
**Was es macht:** Ermöglicht externen Systemen Zugriff auf Daten

**Testen:**
1. Öffne im Browser: `http://localhost/projekt1/api_verwaltung.php`
2. Erstelle einen neuen API-Key (z.B. "Test-Integration")
3. Kopiere den generierten API-Key

**API-Endpunkte testen:**
```bash
# Ersetze YOUR_API_KEY mit deinem Key

# Alle Kunden abrufen
curl "http://localhost/projekt1/api/index.php?api_key=YOUR_API_KEY" \
  -H "Accept: application/json"

# Statistiken abrufen
curl "http://localhost/projekt1/api/index.php?api_key=YOUR_API_KEY" \
  -H "Accept: application/json"

# Lagerbestand abrufen
curl "http://localhost/projekt1/api/index.php?api_key=YOUR_API_KEY" \
  -H "Accept: application/json"
```

**Im Browser testen:**
- `http://localhost/projekt1/api/index.php?api_key=YOUR_API_KEY`

---

### ✅ 4. Vollständige Test-Suite
**Was es macht:** Prüft alle System-Komponenten

**Testen:**
1. Öffne im Browser: `http://localhost/projekt1/system_test.php`
2. Alle Tests sollten grün (✓) sein
3. Bei roten Tests (✗) siehst du die Fehlermeldung

---

## 🎯 Schnell-Test (5 Minuten)

1. **System-Test ausführen:**
   - Öffne: `http://localhost/projekt1/system_test.php`
   - Erwartung: Alle Tests grün ✓

2. **Backup erstellen:**
   ```bash
   cd /home/maxi/Dokumente/Projekts/projekt1
   ./backup.sh
   ls -lh backups/
   ```
   - Erwartung: Neue .db.gz Datei im backups/ Ordner

3. **API-Key erstellen:**
   - Öffne: `http://localhost/projekt1/api_verwaltung.php`
   - Klicke "API-Key erstellen"
   - Kopiere den Key

4. **API testen:**
   - Öffne: `http://localhost/projekt1/api/index.php?api_key=DEIN_KEY`
   - Erwartung: JSON-Daten werden angezeigt

5. **Audit-Log prüfen:**
   - Ändere einen Kunden
   - Öffne: `http://localhost/projekt1/system_test.php`
   - Prüfe Audit-Log Tabelle

---

## 📊 Was wurde implementiert?

| Feature | Status | Priorität |
|---------|--------|-----------|
| ✅ Audit-Log System | Fertig | Hoch |
| ✅ Automatische Backups | Fertig | Hoch |
| ✅ REST-API | Fertig | Mittel |
| ✅ API-Key Verwaltung | Fertig | Mittel |
| ✅ Test-Suite | Fertig | Hoch |
| ✅ Lager-Reservierung | Fertig | Hoch |
| ✅ Status-Workflow | Fertig | Mittel |

---

## 🚀 Nächste Schritte (Optional)

### Chargenverfolgung hinzufügen:
```sql
CREATE TABLE chargen (
    id INTEGER PRIMARY KEY,
    chargennummer TEXT UNIQUE,
    artikel_id INTEGER,
    menge REAL,
    eingang_datum DATE,
    ablauf_datum DATE
);
```

### Qualitätsprüfungen:
```sql
CREATE TABLE qualitaetspruefungen (
    id INTEGER PRIMARY KEY,
    artikel_id INTEGER,
    pruefplan TEXT,
    ergebnis TEXT,
    geprueft_von INTEGER,
    geprueft_am DATETIME
);
```

### E-Mail-Benachrichtigungen:
- Bei niedrigem Lagerbestand
- Bei neuen Bestellungen
- Bei Lieferschein-Erstellung

---

## ❓ Probleme?

**Backup funktioniert nicht:**
```bash
chmod +x backup.sh
mkdir -p backups
```

**API gibt 401 Fehler:**
- Prüfe ob API-Key korrekt ist
- Prüfe ob API-Key aktiv ist

**Tests schlagen fehl:**
- Prüfe Datenbankverbindung
- Prüfe Dateiberechtigungen
- Öffne `system_test.php` für Details

---

## 📞 Support

Bei Fragen oder Problemen:
1. Öffne `system_test.php` für Diagnose
2. Prüfe Logs in der Datenbank
3. Teste API-Endpunkte einzeln
