<?php
require_once 'db_connection.php';

try {
    $PDO->exec("CREATE TABLE IF NOT EXISTS lieferscheine (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lieferschein_nr TEXT UNIQUE NOT NULL,
        bestellung_id INTEGER NOT NULL,
        kundennummer TEXT,
        einsatzart TEXT,
        datum DATE,
        unterschrift_data TEXT,
        unterschrift_name TEXT,
        bemerkung TEXT,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $PDO->exec("CREATE TABLE IF NOT EXISTS lieferschein_techniker (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lieferschein_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        techniker_name TEXT NOT NULL,
        uhrzeit_von TIME,
        uhrzeit_bis TIME
    )");
    
    $PDO->exec("CREATE TABLE IF NOT EXISTS lieferschein_positionen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lieferschein_id INTEGER NOT NULL,
        artikel_id INTEGER,
        artikelname TEXT NOT NULL,
        menge REAL NOT NULL,
        grund_einsatz TEXT,
        durchgefuehrte_arbeiten TEXT,
        bemerkung TEXT
    )");
    
    $PDO->exec("CREATE TABLE IF NOT EXISTS lieferschein_seriennummern (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        position_id INTEGER NOT NULL,
        seriennummer TEXT NOT NULL,
        foto_data TEXT,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "✓ Tabellen erstellt! <a href='lieferschein_editor.php?bestellung_id=10'>Lieferschein erstellen</a>";
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}
?>
