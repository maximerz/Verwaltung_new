<?php
session_start();
require_once 'db_connection.php';

$order_id = $_GET['order_id'] ?? '';

if (empty($order_id)) {
    header('Location: web_oberflaeche.php');
    exit;
}

try {
    // Bestelldaten laden
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
    
    // Positionen laden
    $stmt = $PDO->prepare("SELECT * FROM angebot_positionen WHERE angebot_id = ?");
    $stmt->execute([$order_id]);
    $positionen = $stmt->fetchAll();
    
    $netto = $order['gesamtpreis'] ?? 0;
    $mwst = $netto * 0.19;
    $brutto = $netto * 1.19;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bestellbestätigung - <?= htmlspecialchars($order['auftragsnummer']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #C9A227; padding-bottom: 20px; }
        .info { margin-bottom: 30px; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table th { background-color: #C9A227; color: white; }
        .print-btn { margin: 20px 0; text-align: center; }
        .print-btn button { 
            background: #C9A227; color: white; border: none; padding: 15px 30px; 
            font-size: 16px; cursor: pointer; border-radius: 5px; margin: 0 10px;
        }
        .print-btn button:hover { background: #D4AF37; }
        @media print { .print-btn { display: none; } }
        .total { text-align: right; font-weight: bold; font-size: 1.2em; margin-top: 20px; }
        .footer { margin-top: 50px; padding: 20px; background-color: #f8f9fa; border-left: 4px solid #C9A227; }
        .signature { margin-top: 40px; text-align: center; border-top: 1px solid #ddd; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="print-btn">
        <button onclick="window.print()">🖨️ Drucken</button>
        <button onclick="window.close()">❌ Schließen</button>
        <button onclick="window.location.href='web_oberflaeche.php'">🏠 Zurück zur Übersicht</button>
    </div>
    
    <div class="header">
        <h1>📋 BESTELLBESTÄTIGUNG</h1>
        <h2>Bestellung <?= htmlspecialchars($order['auftragsnummer']) ?></h2>
        <p><strong>Bestätigt am:</strong> <?= date('d.m.Y H:i') ?></p>
    </div>
    
    <div class="info">
        <h3>👤 Kundendaten:</h3>
        <p><strong>Name:</strong> <?= htmlspecialchars($order['vorname'] . ' ' . $order['nachname']) ?></p>
        <p><strong>E-Mail:</strong> <?= htmlspecialchars($order['email']) ?></p>
        <p><strong>Firma:</strong> <?= htmlspecialchars($order['firmenname'] ?? 'Keine Firma') ?></p>
        <p><strong>Adresse:</strong> <?= htmlspecialchars(($order['strasse'] ?? '') . ', ' . ($order['ort'] ?? '')) ?></p>
    </div>
    
    <div class="info">
        <h3>📦 Bestelldetails:</h3>
        <p><strong>Bestellnummer:</strong> <?= htmlspecialchars($order['bestellungsnummer']) ?></p>
        <p><strong>Bestellungsname:</strong> <?= htmlspecialchars($order['bestellungsname']) ?></p>
        <p><strong>Angebotsnummer:</strong> <?= htmlspecialchars($order['angebotsnummer']) ?></p>
        <p><strong>Auftragsnummer:</strong> <?= htmlspecialchars($order['auftragsnummer']) ?></p>
        <p><strong>Lieferzeit:</strong> <?= htmlspecialchars($order['lieferzeit']) ?> Tage</p>
    </div>
    
    <div class="info">
        <h3>🛒 Bestellte Produkte:</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Artikel</th>
                    <th>Beschreibung</th>
                    <th>Menge</th>
                    <th>Einzelpreis</th>
                    <th>Gesamtpreis</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($positionen as $position): ?>
                <tr>
                    <td><?= htmlspecialchars($position['artikel']) ?></td>
                    <td><?= htmlspecialchars($position['beschreibung']) ?></td>
                    <td><?= htmlspecialchars($position['menge']) ?></td>
                    <td><?= number_format($position['einzelpreis'], 2, ',', '.') ?> €</td>
                    <td><?= number_format($position['gesamtpreis'], 2, ',', '.') ?> €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="total">
            <p>Gesamtsumme (netto): <?= number_format($netto, 2, ',', '.') ?> €</p>
            <p>MwSt. (19%): <?= number_format($mwst, 2, ',', '.') ?> €</p>
            <p><strong>Gesamtsumme (brutto): <?= number_format($brutto, 2, ',', '.') ?> €</strong></p>
        </div>
    </div>
    
    <div class="footer">
        <h4>📝 Bestellbestätigung:</h4>
        <p>Hiermit bestätigen wir Ihre Bestellung <strong><?= htmlspecialchars($order['auftragsnummer']) ?></strong> vom <?= date('d.m.Y') ?>.</p>
        <p>• Die Bestellung wird innerhalb von <?= htmlspecialchars($order['lieferzeit']) ?> Werktagen bearbeitet</p>
        <p>• Sie erhalten eine separate Versandbestätigung</p>
        <p>• Bei Fragen wenden Sie sich gerne an uns</p>
    </div>
    
    <div class="signature">
        <p><strong>Vielen Dank für Ihre Bestellung!</strong></p>
        <p>Wir freuen uns auf die Zusammenarbeit.</p>
    </div>
</body>
</html>
<?php
} catch (Exception $e) {
    echo "Fehler beim Generieren der Bestellbestätigung: " . htmlspecialchars($e->getMessage());
}
?>
