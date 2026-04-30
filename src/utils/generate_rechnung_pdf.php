<?php
session_start();
require_once 'db_connection.php';
require_once __DIR__ . '/../../includes/document_templates.php';

$rechnung_id = $_GET['rechnung_id'] ?? '';

if (empty($rechnung_id)) {
    header('Location: finanzbuchhaltung.php');
    exit;
}

try {
    // Rechnungsdaten laden
    $stmt = $PDO->prepare("
        SELECT r.*, k.vorname, k.nachname, k.email, f.firmenname, f.strasse, f.ort, f.plz
        FROM rechnungen r 
        LEFT JOIN kundensystem k ON r.kunde_id = k.kundennummer 
        LEFT JOIN firma f ON k.firma_id = f.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$rechnung_id]);
    $rechnung = $stmt->fetch();
    
    if (!$rechnung) {
        throw new Exception("Rechnung nicht gefunden");
    }
    
    // Bestellung und Positionen laden
    $stmt = $PDO->prepare("SELECT * FROM bestellungen WHERE idbestellung = ?");
    $stmt->execute([$rechnung['bestellung_id']]);
    $bestellung = $stmt->fetch();
    
    $stmt = $PDO->prepare("SELECT * FROM angebot_positionen WHERE angebot_id = ?");
    $stmt->execute([$rechnung['bestellung_id']]);
    $positionen = $stmt->fetchAll();

    $template = get_document_template($PDO, 'rechnung');
    $template_vars = [
        'kunde_name' => trim(($rechnung['vorname'] ?? '') . ' ' . ($rechnung['nachname'] ?? '')),
        'dokument_nummer' => $rechnung['rechnungsnummer'],
        'datum' => date('d.m.Y', strtotime($rechnung['rechnungsdatum'])),
        'faelligkeit' => date('d.m.Y', strtotime($rechnung['faelligkeitsdatum'])),
    ];
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Rechnung - ' . htmlspecialchars($rechnung['rechnungsnummer']) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
            .header { display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 3px solid ' . htmlspecialchars($template['primary_color']) . '; padding-bottom: 20px; }
            .company { text-align: left; }
            .invoice-info { text-align: right; }
            .customer { margin: 30px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid ' . htmlspecialchars($template['primary_color']) . '; }
            .table { width: 100%; border-collapse: collapse; margin: 30px 0; }
            .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
            .table th { background-color: ' . htmlspecialchars($template['primary_color']) . '; color: white; }
            .totals { margin-top: 20px; text-align: right; }
            .totals table { margin-left: auto; width: 300px; }
            .totals td { padding: 8px; }
            .totals .final { font-size: 1.3em; font-weight: bold; background: #f8f9fa; }
            .print-btn { margin: 20px 0; text-align: center; }
            .print-btn button { 
                background: ' . htmlspecialchars($template['primary_color']) . '; color: white; border: none; padding: 15px 30px; 
                font-size: 16px; cursor: pointer; border-radius: 5px; margin: 0 10px;
            }
            .print-btn button:hover { background: ' . htmlspecialchars($template['accent_color']) . '; }
            @media print { .print-btn { display: none; } }
            .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 0.9em; color: #666; text-align: center; }
            .payment-info { margin: 30px 0; padding: 15px; background: #e8f5e9; border-left: 4px solid #4caf50; }
        </style>
    </head>
    <body>
        <div class="print-btn">
            <button onclick="window.print()">🖨️ Drucken / PDF</button>
            <button onclick="sendEmail()">📧 Per E-Mail senden</button>
            <button onclick="window.close()">❌ Schließen</button>
            <button onclick="window.location.href=\'finanzbuchhaltung.php\'">🏠 Zurück</button>
        </div>
        
        <div class="header">
            <div class="company">
                <h2>' . htmlspecialchars($template['firmenname']) . '</h2>
                <p>' . nl2br(htmlspecialchars($template['firmenadresse'])) . '</p>
            </div>
            <div class="invoice-info">
                <h1 style="color: ' . htmlspecialchars($template['primary_color']) . '; margin: 0;">' . htmlspecialchars($template['header_title']) . '</h1>
                <p style="font-size: 1.2em; margin: 10px 0;"><strong>' . htmlspecialchars($rechnung['rechnungsnummer']) . '</strong></p>
                <p>Datum: ' . date('d.m.Y', strtotime($rechnung['rechnungsdatum'])) . '<br>
                Fällig: ' . date('d.m.Y', strtotime($rechnung['faelligkeitsdatum'])) . '</p>
            </div>
        </div>
        
        <div class="customer">
            <h3>Rechnungsempfänger:</h3>
            <p><strong>' . htmlspecialchars($rechnung['vorname'] . ' ' . $rechnung['nachname']) . '</strong></p>';
    
    if ($rechnung['firmenname']) {
        $html .= '<p>' . htmlspecialchars($rechnung['firmenname']) . '</p>';
    }
    
    $html .= '<p>' . htmlspecialchars($rechnung['strasse'] ?? '') . '<br>
            ' . htmlspecialchars(($rechnung['plz'] ?? '') . ' ' . ($rechnung['ort'] ?? '')) . '</p>
        </div>
        
        <p>' . render_document_template_html($template['intro_text'], $template_vars) . '</p>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Pos.</th>
                    <th>Artikel</th>
                    <th>Beschreibung</th>
                    <th style="text-align: right;">Menge</th>
                    <th style="text-align: right;">Einzelpreis</th>
                    <th style="text-align: right;">Gesamtpreis</th>
                </tr>
            </thead>
            <tbody>';
    
    $pos_nr = 1;
    foreach ($positionen as $position) {
        $html .= '<tr>
            <td>' . $pos_nr++ . '</td>
            <td>' . htmlspecialchars($position['artikel']) . '</td>
            <td>' . htmlspecialchars($position['beschreibung']) . '</td>
            <td style="text-align: right;">' . htmlspecialchars($position['menge']) . '</td>
            <td style="text-align: right;">' . number_format($position['einzelpreis'], 2, ',', '.') . ' €</td>
            <td style="text-align: right;">' . number_format($position['gesamtpreis'], 2, ',', '.') . ' €</td>
        </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <div class="totals">
            <table>
                <tr>
                    <td>Zwischensumme (netto):</td>
                    <td style="text-align: right;"><strong>' . number_format($rechnung['nettobetrag'], 2, ',', '.') . ' €</strong></td>
                </tr>
                <tr>
                    <td>zzgl. MwSt. (19%):</td>
                    <td style="text-align: right;">' . number_format($rechnung['mwst_betrag'], 2, ',', '.') . ' €</td>
                </tr>
                <tr class="final">
                    <td><strong>Rechnungsbetrag (brutto):</strong></td>
                    <td style="text-align: right;"><strong>' . number_format($rechnung['bruttobetrag'], 2, ',', '.') . ' €</strong></td>
                </tr>
            </table>
        </div>
        
        <div class="payment-info">
            <h3>💳 Zahlungsinformationen:</h3>
            <p>' . render_document_template_html($template['payment_info'], $template_vars) . '</p>
        </div>
        
        <p>Bitte überweisen Sie den Rechnungsbetrag bis zum Fälligkeitsdatum unter Angabe der Rechnungsnummer.</p>
        
        <div class="footer">
            <p>' . render_document_template_html($template['footer_text'], $template_vars) . '</p>
        </div>
        
        <script>
            function sendEmail() {
                if (confirm("Rechnung per E-Mail an ' . htmlspecialchars($rechnung['email']) . ' senden?")) {
                    window.location.href = "send_rechnung_email.php?rechnung_id=' . $rechnung_id . '";
                }
            }
        </script>
    </body>
    </html>';
    
    echo $html;
    
} catch (Exception $e) {
    echo "Fehler beim Generieren der Rechnung: " . $e->getMessage();
}
?>
