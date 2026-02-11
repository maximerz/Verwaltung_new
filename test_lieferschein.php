<?php
require_once 'db_connection.php';

echo "<h2>Lieferschein-System Test</h2>";

// Prüfe ob Tabellen existieren
$tables = ['lieferscheine', 'lieferschein_techniker', 'lieferschein_positionen', 'lieferschein_seriennummern'];

foreach ($tables as $table) {
    try {
        $stmt = $PDO->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✓ Tabelle '$table' existiert ($count Einträge)<br>";
    } catch (Exception $e) {
        echo "✗ Tabelle '$table' fehlt oder hat Fehler: " . $e->getMessage() . "<br>";
    }
}

// Prüfe Bestellungen
try {
    $stmt = $PDO->query("SELECT COUNT(*) FROM bestellungen");
    $count = $stmt->fetchColumn();
    echo "<br>✓ $count Bestellungen gefunden<br>";
    
    if ($count > 0) {
        $stmt = $PDO->query("SELECT idbestellung, bestellungsnummer, auftragsnummer FROM bestellungen LIMIT 5");
        echo "<br><strong>Beispiel-Bestellungen:</strong><br>";
        while ($row = $stmt->fetch()) {
            echo "- ID: {$row['idbestellung']}, Bestellung: {$row['bestellungsnummer']}, Auftrag: {$row['auftragsnummer']}<br>";
            echo "  <a href='lieferschein_editor.php?bestellung_id={$row['idbestellung']}' target='_blank'>→ Lieferschein erstellen</a><br>";
        }
    }
} catch (Exception $e) {
    echo "✗ Fehler bei Bestellungen: " . $e->getMessage() . "<br>";
}

// Prüfe existierende Lieferscheine
try {
    $stmt = $PDO->query("SELECT * FROM lieferscheine ORDER BY erstellt_am DESC LIMIT 5");
    $lieferscheine = $stmt->fetchAll();
    
    if (count($lieferscheine) > 0) {
        echo "<br><strong>Existierende Lieferscheine:</strong><br>";
        foreach ($lieferscheine as $ls) {
            echo "- {$ls['lieferschein_nr']} (Datum: {$ls['datum']})<br>";
            echo "  <a href='lieferschein_pdf.php?id={$ls['id']}' target='_blank'>→ PDF anzeigen</a><br>";
        }
    } else {
        echo "<br>Noch keine Lieferscheine erstellt.<br>";
    }
} catch (Exception $e) {
    echo "<br>✗ Fehler bei Lieferscheinen: " . $e->getMessage() . "<br>";
}

echo "<br><br><a href='web_oberflaeche.php'>← Zurück zur Übersicht</a>";
?>
