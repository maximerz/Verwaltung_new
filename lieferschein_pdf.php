<?php
session_start();
require_once 'db_connection.php';
require_once 'includes/document_templates.php';

$lieferschein_id = $_GET['id'] ?? '';

if (empty($lieferschein_id)) {
    header('Location: web_oberflaeche.php');
    exit;
}

try {
    // Lieferschein laden
    $stmt = $PDO->prepare("SELECT * FROM lieferscheine WHERE id = ?");
    $stmt->execute([$lieferschein_id]);
    $lieferschein = $stmt->fetch();
    
    if (!$lieferschein) {
        throw new Exception("Lieferschein nicht gefunden");
    }
    
    // Kunde laden
    $stmt = $PDO->prepare("SELECT k.vorname, k.nachname, k.email, k.kundennummer, f.firmenname, f.strasse, f.ort FROM kundensystem k LEFT JOIN firma f ON k.firma_id = f.id WHERE k.kundennummer = ?");
    $stmt->execute([$lieferschein['kundennummer']]);
    $kunde = $stmt->fetch();
    
    if (!$kunde) {
        // Fallback: Versuche ohne firma_id
        $stmt = $PDO->prepare("SELECT vorname, nachname, email, kundennummer FROM kundensystem WHERE kundennummer = ?");
        $stmt->execute([$lieferschein['kundennummer']]);
        $kunde = $stmt->fetch();
    }
    
    // Techniker laden
    $stmt = $PDO->prepare("SELECT * FROM lieferschein_techniker WHERE lieferschein_id = ?");
    $stmt->execute([$lieferschein_id]);
    $techniker = $stmt->fetchAll();
    
    // Positionen laden
    $stmt = $PDO->prepare("SELECT * FROM lieferschein_positionen WHERE lieferschein_id = ?");
    $stmt->execute([$lieferschein_id]);
    $positionen = $stmt->fetchAll();
    
    // Seriennummern für Positionen laden
    foreach ($positionen as &$pos) {
        $stmt = $PDO->prepare("SELECT * FROM lieferschein_seriennummern WHERE position_id = ?");
        $stmt->execute([$pos['id']]);
        $pos['seriennummern'] = $stmt->fetchAll();
    }

    $template = get_document_template($PDO, 'lieferschein');
    $template_vars = [
        'kunde_name' => trim(($kunde['vorname'] ?? '') . ' ' . ($kunde['nachname'] ?? '')),
        'dokument_nummer' => $lieferschein['lieferschein_nr'],
        'datum' => date('d.m.Y', strtotime($lieferschein['datum'])),
        'faelligkeit' => '',
    ];
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Lieferschein - ' . htmlspecialchars($lieferschein['lieferschein_nr']) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid ' . htmlspecialchars($template['primary_color']) . '; padding-bottom: 20px; }
            .info-box { background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid ' . htmlspecialchars($template['primary_color']) . '; }
            .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .table th, .table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            .table th { background-color: ' . htmlspecialchars($template['primary_color']) . '; color: white; }
            .print-btn { margin: 20px 0; text-align: center; }
            .print-btn button { background: ' . htmlspecialchars($template['primary_color']) . '; color: white; border: none; padding: 15px 30px; font-size: 16px; cursor: pointer; border-radius: 5px; margin: 0 10px; }
            .print-btn button:hover { background: ' . htmlspecialchars($template['accent_color']) . '; }
            @media print { .print-btn { display: none; } }
            .signature { margin-top: 50px; }
            .signature img { max-width: 300px; border: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class="print-btn">
            <button onclick="window.print()">🖨️ Drucken / PDF</button>
            <button onclick="window.close()">❌ Schließen</button>
            <button onclick="window.location.href=\'web_oberflaeche.php\'">🏠 Zurück</button>
        </div>
        
        <div class="header">
            <h1>📋 ' . htmlspecialchars($template['header_title']) . '</h1>
            <h2>' . htmlspecialchars($lieferschein['lieferschein_nr']) . '</h2>
            <p><strong>Datum:</strong> ' . date('d.m.Y', strtotime($lieferschein['datum'])) . '</p>
            <p><strong>' . htmlspecialchars($template['firmenname']) . '</strong><br>' . nl2br(htmlspecialchars($template['firmenadresse'])) . '</p>
        </div>
        
        <div class="info-box">
            <p>' . render_document_template_html($template['intro_text'], $template_vars) . '</p>
            <h3>👤 Kunde:</h3>
            <p><strong>' . htmlspecialchars($kunde['vorname'] . ' ' . $kunde['nachname']) . '</strong></p>
            <p>' . htmlspecialchars($kunde['firmenname'] ?? '') . '</p>
            <p>' . htmlspecialchars($kunde['strasse'] ?? '') . '</p>
            <p>' . htmlspecialchars($kunde['ort'] ?? '') . '</p>
        </div>
        
        <div class="info-box">
            <h3>👷 Techniker & Zeiten:</h3>
            <p><strong>Einsatzart:</strong> ' . htmlspecialchars($lieferschein['einsatzart']) . '</p>';
    
    foreach ($techniker as $tech) {
        $html .= '<p>• ' . htmlspecialchars($tech['techniker_name']) . ' (' . htmlspecialchars($tech['uhrzeit_von']) . ' - ' . htmlspecialchars($tech['uhrzeit_bis']) . ')</p>';
    }
    
    $html .= '</div>
        
        <h3>📦 Gelieferte Artikel:</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Artikel</th>
                    <th>Menge</th>
                    <th>Seriennummern</th>
                    <th>Arbeiten</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($positionen as $pos) {
        $seriennummern = array_column($pos['seriennummern'], 'seriennummer');
        $html .= '<tr>
            <td><strong>' . htmlspecialchars($pos['artikelname']) . '</strong><br>
                <small>' . htmlspecialchars($pos['grund_einsatz']) . '</small></td>
            <td>' . htmlspecialchars($pos['menge']) . '</td>
            <td>' . htmlspecialchars(implode(', ', $seriennummern)) . '</td>
            <td>' . htmlspecialchars($pos['durchgefuehrte_arbeiten']) . '</td>
        </tr>';
    }
    
    $html .= '</tbody>
        </table>';
    
    if ($lieferschein['bemerkung']) {
        $html .= '<div class="info-box">
            <h3>📝 Bemerkung:</h3>
            <p>' . nl2br(htmlspecialchars($lieferschein['bemerkung'])) . '</p>
        </div>';
    }
    
    if ($lieferschein['unterschrift_data']) {
        $html .= '<div class="signature">
            <h3>✍️ Unterschrift Kunde:</h3>
            <p><strong>Name:</strong> ' . htmlspecialchars($lieferschein['unterschrift_name']) . '</p>
            <img src="' . htmlspecialchars($lieferschein['unterschrift_data']) . '" alt="Unterschrift">
        </div>';
    }
    
    $html .= '
        <div style="margin-top: 50px; text-align: center; border-top: 1px solid #ddd; padding-top: 20px;">
            <p><strong>' . render_document_template_html($template['footer_text'], $template_vars) . '</strong></p>
        </div>
    </body>
    </html>';
    
    echo $html;
    
    // Automatischer Druckdialog wenn print=1 Parameter gesetzt ist
    if (isset($_GET['print']) && $_GET['print'] == '1') {
        echo '<script>window.onload = function() { window.print(); }</script>';
    }
    
} catch (Exception $e) {
    echo "Fehler beim Generieren des Lieferscheins: " . $e->getMessage();
}
?>
