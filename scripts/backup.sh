#!/bin/bash
# Automatisches Backup-Script für ERP-System

# Konfiguration
BACKUP_DIR="/home/maxi/Dokumente/Projekts/projekt1/backups"
DB_FILE="/home/maxi/Dokumente/Projekts/projekt1/projekt1.db"
RETENTION_DAYS=30

# Erstelle Backup-Verzeichnis falls nicht vorhanden
mkdir -p "$BACKUP_DIR"

# Datum für Dateinamen
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/backup_$DATE.db"

# Erstelle Backup
echo "Erstelle Backup: $BACKUP_FILE"
cp "$DB_FILE" "$BACKUP_FILE"

# Komprimiere Backup
gzip "$BACKUP_FILE"
echo "Backup komprimiert: $BACKUP_FILE.gz"

# Lösche alte Backups (älter als RETENTION_DAYS)
find "$BACKUP_DIR" -name "backup_*.db.gz" -mtime +$RETENTION_DAYS -delete
echo "Alte Backups gelöscht (älter als $RETENTION_DAYS Tage)"

# Zeige Backup-Größe
du -h "$BACKUP_FILE.gz"

echo "✓ Backup erfolgreich erstellt!"
