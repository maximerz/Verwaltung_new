<?php
require_once 'db_connection.php';

try {
    // Tabellen löschen
    $PDO->exec("DROP TABLE IF EXISTS lieferschein_seriennummern");
    $PDO->exec("DROP TABLE IF EXISTS lieferschein_positionen");
    $PDO->exec("DROP TABLE IF EXISTS lieferschein_techniker");
    $PDO->exec("DROP TABLE IF EXISTS lieferscheine");
    
    echo "✓ Alte Tabellen gelöscht<br>";
    
    // Neu erstellen
    $PDO->exec("CREATE TABLE lieferscheine (
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
    echo "✓ Tabelle lieferscheine erstellt<br>";
    
    $PDO->exec("CREATE TABLE lieferschein_techniker (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lieferschein_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        techniker_name TEXT NOT NULL,
        uhrzeit_von TIME,
        uhrzeit_bis TIME
    )");
    echo "✓ Tabelle lieferschein_techniker erstellt<br>";
    
    $PDO->exec("CREATE TABLE lieferschein_positionen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lieferschein_id INTEGER NOT NULL,
        artikel_id INTEGER,
        artikelname TEXT NOT NULL,
        menge REAL NOT NULL,
        grund_einsatz TEXT,
        durchgefuehrte_arbeiten TEXT,
        bemerkung TEXT
    )");
    echo "✓ Tabelle lieferschein_positionen erstellt<br>";
    
    $PDO->exec("CREATE TABLE lieferschein_seriennummern (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        position_id INTEGER NOT NULL,
        seriennummer TEXT NOT NULL,
        foto_data TEXT,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Tabelle lieferschein_seriennummern erstellt<br>";
    
    echo "<br><h3>✓ Alle Tabellen erfolgreich erstellt!</h3>";
    echo "<p><a href='lieferschein_editor.php?bestellung_id=10'>→ Lieferschein erstellen</a></p>";
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}
?>
