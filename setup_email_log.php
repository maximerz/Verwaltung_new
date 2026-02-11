<?php
// E-Mail-Log-Tabelle erstellen
require_once 'db_connection.php';

try {
    $PDO->exec("CREATE TABLE IF NOT EXISTS email_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        empfaenger TEXT NOT NULL,
        betreff TEXT NOT NULL,
        gesendet_am DATETIME NOT NULL,
        typ TEXT,
        referenz_id INTEGER,
        status TEXT DEFAULT 'gesendet'
    )");
    
    echo "✅ E-Mail-Log-Tabelle erfolgreich erstellt!";
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage();
}
?>
