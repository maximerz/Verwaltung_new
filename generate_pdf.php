<?php
session_start();
require_once 'db_connection.php';
require_once 'vendor/autoload.php';

$order_id = $_GET['order_id'] ?? '';

if (empty($order_id)) {
    header('Location: web_oberflaeche.php');
    exit;
}

try {
    $stmt = $PDO->prepare("
        SELECT b.*, k.vorname, k.nachname, k.email, f.firmenname, f.strasse, f.ort 
        FROM bestellungen b 
        LEFT JOIN kundensystem k ON b.kundennummer = k.kundennummer 
        LEFT JOIN firma f ON k.firma_id = f.id 
        WHERE b.idbestellung = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception("Bestellung nicht gefunden");
    }
    
    $stmt = $PDO->prepare("SELECT * FROM angebot_positionen WHERE angebot_id = ?");
    $stmt->execute([$order_id]);
    $positionen = $stmt->fetchAll();
    
    $netto = $order['gesamtpreis'] ?? 0;
    $mwst = $netto * 0.19;
    $brutto = $netto * 1.19;

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('ERP System');
    $pdf->SetAuthor('Ihr Unternehmen');
    $pdf->SetTitle('Bestellbestätigung ' . $order['auftragsnummer']);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    $html = '
    <style>
        h1 { color: #C9A227; text-align: center; font-size: 20pt; }
        h2 { color: #333; font-size: 14pt; margin-top: 10px; }
        h3 { color: #666; font-size: 12pt; margin-top: 8px; }
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #C9A227; color: white; padding: 8px; text-align: left; }
        td { padding: 6px; border-bottom: 1px solid #ddd; }
        .total { text-align: right; font-weight: bold; font-size: 12pt; }
        .footer { margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #C9A227; }
    </style>
    
    <h1>BESTELLBESTÄTIGUNG</h1>
    <h2>Bestellung ' . htmlspecialchars($order['auftragsnummer']) . '</h2>
    <p><strong>Bestätigt am:</strong> ' . date('d.m.Y H:i') . '</p>
    
    <h3>Kundendaten:</h3>
    <p><strong>Name:</strong> ' . htmlspecialchars($order['vorname'] . ' ' . $order['nachname']) . '</p>
    <p><strong>E-Mail:</strong> ' . htmlspecialchars($order['email']) . '</p>
    <p><strong>Firma:</strong> ' . htmlspecialchars($order['firmenname'] ?? 'Keine Firma') . '</p>
    <p><strong>Adresse:</strong> ' . htmlspecialchars(($order['strasse'] ?? '') . ', ' . ($order['ort'] ?? '')) . '</p>
    
    <h3>Bestelldetails:</h3>
    <p><strong>Bestellnummer:</strong> ' . htmlspecialchars($order['bestellungsnummer']) . '</p>
    <p><strong>Bestellungsname:</strong> ' . htmlspecialchars($order['bestellungsname']) . '</p>
    <p><strong>Angebotsnummer:</strong> ' . htmlspecialchars($order['angebotsnummer']) . '</p>
    <p><strong>Lieferzeit:</strong> ' . htmlspecialchars($order['lieferzeit']) . ' Tage</p>
    
    <h3>Bestellte Produkte:</h3>
    <table>
        <thead>
            <tr>
                <th>Artikel</th>
                <th>Beschreibung</th>
                <th>Menge</th>
                <th>Einzelpreis</th>
                <th>Gesamtpreis</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($positionen as $position) {
        $html .= '<tr>
            <td>' . htmlspecialchars($position['artikel']) . '</td>
            <td>' . htmlspecialchars($position['beschreibung']) . '</td>
            <td>' . htmlspecialchars($position['menge']) . '</td>
            <td>' . number_format($position['einzelpreis'], 2, ',', '.') . ' €</td>
            <td>' . number_format($position['gesamtpreis'], 2, ',', '.') . ' €</td>
        </tr>';
    }
    
    $html .= '</tbody>
    </table>
    
    <div class="total">
        <p>Gesamtsumme (netto): ' . number_format($netto, 2, ',', '.') . ' €</p>
        <p>MwSt. (19%): ' . number_format($mwst, 2, ',', '.') . ' €</p>
        <p><strong>Gesamtsumme (brutto): ' . number_format($brutto, 2, ',', '.') . ' €</strong></p>
    </div>
    
    <div class="footer">
        <h4>Bestellbestätigung:</h4>
        <p>Hiermit bestätigen wir Ihre Bestellung <strong>' . htmlspecialchars($order['auftragsnummer']) . '</strong> vom ' . date('d.m.Y') . '.</p>
        <p>Die Bestellung wird innerhalb von ' . htmlspecialchars($order['lieferzeit']) . ' Werktagen bearbeitet.</p>
        <p><strong>Vielen Dank für Ihre Bestellung!</strong></p>
    </div>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('Bestellbestaetigung_' . $order['auftragsnummer'] . '.pdf', 'I');

} catch (Exception $e) {
    die("Fehler: " . htmlspecialchars($e->getMessage()));
}
