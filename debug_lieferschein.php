<?php
session_start();
require_once 'db_connection.php';

echo "<h2>Lieferschein Debug</h2>";

// Prüfe POST-Daten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST-Daten empfangen:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Versuche zu speichern
    try {
        $lieferschein_nr = $_POST['lieferschein_nr'] ?? 'TEST';
        $bestellung_id = $_POST['bestellung_id'] ?? 1;
        $kundennummer = $_POST['kundennummer'] ?? 'TEST';
        $einsatzart = $_POST['einsatzart'] ?? 'Voll';
        $datum = $_POST['datum'] ?? date('Y-m-d');
        
        echo "<h3>Versuche zu speichern:</h3>";
        echo "Lieferschein-Nr: $lieferschein_nr<br>";
        echo "Bestellung-ID: $bestellung_id<br>";
        
        $stmt = $PDO->prepare("INSERT INTO lieferscheine (lieferschein_nr, bestellung_id, kundennummer, einsatzart, datum) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$lieferschein_nr, $bestellung_id, $kundennummer, $einsatzart, $datum]);
        
        if ($result) {
            $id = $PDO->lastInsertId();
            echo "<p style='color:green'>✓ Erfolgreich gespeichert! ID: $id</p>";
            echo "<a href='lieferschein_pdf.php?id=$id' target='_blank'>PDF öffnen</a>";
        } else {
            echo "<p style='color:red'>✗ Fehler beim Speichern</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Fehler: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Keine POST-Daten. Bitte über das Formular absenden.</p>";
}

// Zeige existierende Lieferscheine
try {
    $stmt = $PDO->query("SELECT * FROM lieferscheine ORDER BY erstellt_am DESC LIMIT 5");
    $lieferscheine = $stmt->fetchAll();
    
    echo "<h3>Letzte 5 Lieferscheine:</h3>";
    if (count($lieferscheine) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nr</th><th>Datum</th><th>Aktion</th></tr>";
        foreach ($lieferscheine as $ls) {
            echo "<tr>";
            echo "<td>{$ls['id']}</td>";
            echo "<td>{$ls['lieferschein_nr']}</td>";
            echo "<td>{$ls['datum']}</td>";
            echo "<td><a href='lieferschein_pdf.php?id={$ls['id']}' target='_blank'>PDF</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Keine Lieferscheine vorhanden.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Fehler: " . $e->getMessage() . "</p>";
}
?>
