<?php
require_once 'db_connection.php';

// Tabelle für Angebotspositionen erstellen
try {
    $PDO->exec("CREATE TABLE IF NOT EXISTS angebot_positionen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        angebot_id INTEGER NOT NULL,
        artikel_id INTEGER,
        artikelname TEXT NOT NULL,
        menge REAL NOT NULL,
        einzelpreis REAL NOT NULL,
        gesamtpreis REAL NOT NULL,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (angebot_id) REFERENCES bestellungen(idbestellung),
        FOREIGN KEY (artikel_id) REFERENCES lagerartikel(id)
    )");
    
    echo "Tabelle angebot_positionen erstellt!";
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}
?>
