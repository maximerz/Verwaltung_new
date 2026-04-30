<?php
session_start();
require_once 'db_connection.php';
require_once __DIR__ . '/../../includes/document_templates.php';

// Mock CURL-Funktionen falls Extension fehlt
if (!extension_loaded('curl')) {
    function curl_init($url = null) { return false; }
    function curl_setopt($ch, $option, $value) { return true; }
    function curl_exec($ch) { return false; }
    function curl_getinfo($ch, $opt = 0) { return 0; }
    function curl_error($ch) { return ''; }
    function curl_close($ch) { return true; }
    
    define('CURLOPT_CONNECTTIMEOUT', 78);
    define('CURLOPT_TIMEOUT', 13);
    define('CURLOPT_RETURNTRANSFER', 19913);
    define('CURLOPT_USERAGENT', 10018);
    define('CURLOPT_FAILONERROR', 45);
    define('CURLOPT_FOLLOWLOCATION', 52);
    define('CURLOPT_COOKIEJAR', 10082);
    define('CURLOPT_FRESH_CONNECT', 74);
    define('CURLOPT_FORBID_REUSE', 75);
    define('CURLOPT_HEADER', 42);
    define('CURLOPT_HTTPHEADER', 10023);
    define('CURLOPT_SSL_VERIFYPEER', 64);
    define('CURLOPT_SSL_VERIFYHOST', 81);
    define('CURLINFO_HTTP_CODE', 2097154);
    define('CURLOPT_MAXREDIRS', 68);
    define('CURLOPT_PROTOCOLS', 181);
    define('CURLPROTO_HTTP', 1);
    define('CURLPROTO_HTTPS', 2);
    define('CURLPROTO_FTP', 4);
    define('CURLPROTO_FTPS', 8);
}

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
    $template = get_document_template($PDO, 'bestellung');
    $template_vars = [
        'kunde_name' => trim(($order['vorname'] ?? '') . ' ' . ($order['nachname'] ?? '')),
        'dokument_nummer' => $order['auftragsnummer'],
        'datum' => date('d.m.Y H:i'),
        'faelligkeit' => '',
    ];

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
        h1 { color: ' . htmlspecialchars($template['primary_color']) . '; text-align: center; font-size: 20pt; }
        h2 { color: #333; font-size: 14pt; margin-top: 10px; }
        h3 { color: #666; font-size: 12pt; margin-top: 8px; }
        table { border-collapse: collapse; width: 100%; }
        th { background-color: ' . htmlspecialchars($template['primary_color']) . '; color: white; padding: 8px; text-align: left; }
        td { padding: 6px; border-bottom: 1px solid #ddd; }
        .total { text-align: right; font-weight: bold; font-size: 12pt; }
        .footer { margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid ' . htmlspecialchars($template['primary_color']) . '; }
    </style>
    
    <h1>' . htmlspecialchars($template['header_title']) . '</h1>
    <h2>Bestellung ' . htmlspecialchars($order['auftragsnummer']) . '</h2>
    <p><strong>Bestätigt am:</strong> ' . date('d.m.Y H:i') . '</p>
    <p><strong>' . htmlspecialchars($template['firmenname']) . '</strong><br>' . nl2br(htmlspecialchars($template['firmenadresse'])) . '</p>
    
    <h3>Kundendaten:</h3>
    <p><strong>Name:</strong> ' . htmlspecialchars($order['vorname'] . ' ' . $order['nachname']) . '</p>
    <p><strong>E-Mail:</strong> ' . htmlspecialchars($order['email']) . '</p>
    <p><strong>Firma:</strong> ' . htmlspecialchars($order['firmenname'] ?? 'Keine Firma') . '</p>
    <p><strong>Adresse:</strong> ' . htmlspecialchars(($order['strasse'] ?? '') . ', ' . ($order['ort'] ?? '')) . '</p>
    
    <h3>Bestelldetails:</h3>
    <p><strong>Bestellnummer:</strong> ' . htmlspecialchars($order['bestellungsnummer'] ?? 'N/A') . '</p>
    <p><strong>Bestellungsname:</strong> ' . htmlspecialchars($order['bestellungsname'] ?? 'N/A') . '</p>
    <p><strong>Angebotsnummer:</strong> ' . htmlspecialchars($order['angebotsnummer'] ?? 'N/A') . '</p>
    <p><strong>Lieferzeit:</strong> ' . htmlspecialchars($order['lieferzeit'] ?? 'N/A') . ' Tage</p>
    
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
        <h4>' . htmlspecialchars($template['name']) . ':</h4>
        <p>' . render_document_template_html($template['intro_text'], $template_vars) . '</p>
        <p>Die Bestellung wird innerhalb von ' . htmlspecialchars($order['lieferzeit']) . ' Werktagen bearbeitet.</p>
        <p>' . render_document_template_html($template['footer_text'], $template_vars) . '</p>
    </div>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('Bestellbestaetigung_' . $order['auftragsnummer'] . '.pdf', 'I');

} catch (Exception $e) {
    die("Fehler: " . htmlspecialchars($e->getMessage()));
}
