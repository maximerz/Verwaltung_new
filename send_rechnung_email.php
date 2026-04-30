<?php
session_start();
require_once 'db_connection.php';

$rechnung_id = $_GET['rechnung_id'] ?? '';

if (empty($rechnung_id)) {
    header('Location: finanzbuchhaltung.php');
    exit;
}

try {
    // Rechnungsdaten laden
    $stmt = $PDO->prepare("
        SELECT r.*, k.vorname, k.nachname, k.email, f.firmenname
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
    
    if (empty($rechnung['email'])) {
        throw new Exception("Keine E-Mail-Adresse für diesen Kunden hinterlegt");
    }
    
    // E-Mail-Inhalt
    $to = $rechnung['email'];
    $subject = "Rechnung " . $rechnung['rechnungsnummer'];
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: #5B7DB1; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 20px; }
            .info-box { background: #f8f9fa; padding: 15px; margin: 20px 0; border-left: 4px solid #5B7DB1; border-radius: 0 8px 8px 0; }
            .button { background: #5B7DB1; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block; margin: 20px 0; }
            .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 0.9em; color: #666; margin-top: 30px; border-radius: 0 0 10px 10px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Rechnung " . htmlspecialchars($rechnung['rechnungsnummer']) . "</h1>
        </div>
        
        <div class='content'>
            <p>Sehr geehrte/r " . htmlspecialchars($rechnung['vorname'] . ' ' . $rechnung['nachname']) . ",</p>
            
            <p>vielen Dank für Ihre Bestellung. Anbei erhalten Sie Ihre Rechnung.</p>
            
            <div class='info-box'>
                <h3>📋 Rechnungsdetails:</h3>
                <p><strong>Rechnungsnummer:</strong> " . htmlspecialchars($rechnung['rechnungsnummer']) . "</p>
                <p><strong>Rechnungsdatum:</strong> " . date('d.m.Y', strtotime($rechnung['rechnungsdatum'])) . "</p>
                <p><strong>Fälligkeitsdatum:</strong> " . date('d.m.Y', strtotime($rechnung['faelligkeitsdatum'])) . "</p>
                <p><strong>Rechnungsbetrag:</strong> " . number_format($rechnung['bruttobetrag'], 2, ',', '.') . " € (brutto)</p>
            </div>
            
            <div class='info-box'>
                <h3>💳 Zahlungsinformationen:</h3>
                <p><strong>Bankverbindung:</strong><br>
                IBAN: DE89 3704 0044 0532 0130 00<br>
                BIC: COBADEFFXXX<br>
                Bank: Commerzbank AG</p>
                <p><strong>Verwendungszweck:</strong> " . htmlspecialchars($rechnung['rechnungsnummer']) . "</p>
            </div>
            
            <p>Bitte überweisen Sie den Rechnungsbetrag bis zum <strong>" . date('d.m.Y', strtotime($rechnung['faelligkeitsdatum'])) . "</strong> unter Angabe der Rechnungsnummer.</p>
            
            <a href='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/generate_rechnung_pdf.php?rechnung_id=" . $rechnung_id . "' class='button'>📄 Rechnung als PDF anzeigen</a>
            
            <p>Bei Fragen zu dieser Rechnung stehen wir Ihnen gerne zur Verfügung.</p>
            
            <p>Mit freundlichen Grüßen<br><strong>Ihr Team</strong></p>
        </div>
        
        <div class='footer'>
            <p>Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese E-Mail.</p>
            <p>Ihre Firma GmbH | Musterstraße 123 | 12345 Musterstadt</p>
        </div>
    </body>
    </html>
    ";
    
    // E-Mail-Header
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@firma.de" . "\r\n";
    $headers .= "Reply-To: info@firma.de" . "\r\n";
    
    // E-Mail senden
    if (mail($to, $subject, $message, $headers)) {
        $success_message = "✅ Rechnung wurde erfolgreich per E-Mail an " . htmlspecialchars($rechnung['email']) . " versendet!";
        
        // Log in Datenbank (optional)
        try {
            $stmt = $PDO->prepare("INSERT INTO email_log (empfaenger, betreff, gesendet_am, typ, referenz_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$to, $subject, date('Y-m-d H:i:s'), 'rechnung', $rechnung_id]);
        } catch (Exception $e) {
            // Log-Fehler ignorieren
        }
    } else {
        throw new Exception("E-Mail konnte nicht versendet werden. Bitte überprüfen Sie die Server-Konfiguration.");
    }
    
} catch (Exception $e) {
    $error_message = "❌ Fehler: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rechnung per E-Mail versenden</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #F0F2F5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }
        .message-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            max-width: 600px;
            text-align: center;
        }
        .success { color: #28a745; font-size: 3em; }
        .error { color: #dc3545; font-size: 3em; }
        .btn-custom {
            background: #5B7DB1;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            margin: 10px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(91, 125, 177, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <div class="message-box">
        <?php if (isset($success_message)): ?>
            <div class="success">✅</div>
            <h2>E-Mail erfolgreich versendet!</h2>
            <p><?= $success_message ?></p>
            <div style="margin-top: 30px;">
                <a href="generate_rechnung_pdf.php?rechnung_id=<?= $rechnung_id ?>" class="btn-custom">📄 Rechnung anzeigen</a>
                <a href="finanzbuchhaltung.php" class="btn-custom">🏠 Zurück zur Übersicht</a>
            </div>
        <?php else: ?>
            <div class="error">❌</div>
            <h2>Fehler beim Versenden</h2>
            <p><?= $error_message ?? 'Unbekannter Fehler' ?></p>
            <div style="margin-top: 30px;">
                <a href="javascript:history.back()" class="btn-custom">⬅️ Zurück</a>
                <a href="finanzbuchhaltung.php" class="btn-custom">🏠 Zur Übersicht</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
