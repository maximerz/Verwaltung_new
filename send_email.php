<?php
session_start();
require_once 'db_connection.php';
require_once 'includes/lager_reservierung.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['order_id'] ?? null;
$type = $_GET['type'] ?? 'angebot'; // angebot oder bestellung

if (!$order_id) {
    die('Keine Bestellung angegeben');
}

// Bestellung und Kunde laden
$stmt = $PDO->prepare("SELECT b.*, k.vorname, k.nachname, k.email FROM bestellungen b LEFT JOIN kundensystem k ON b.kundennummer = k.kundennummer WHERE b.idbestellung = ?");
$stmt->execute([$order_id]);
$bestellung = $stmt->fetch();

if (!$bestellung) {
    die('Bestellung nicht gefunden');
}

// E-Mail senden
if (isset($_POST['send_email'])) {
    $empfaenger = $_POST['empfaenger'];
    $betreff = $_POST['betreff'];
    $nachricht = $_POST['nachricht'];
    $pdf_anhang = isset($_POST['pdf_anhang']);
    
    // E-Mail Header
    $headers = "From: noreply@kundensystem.de\r\n";
    $headers .= "Reply-To: noreply@kundensystem.de\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    if ($pdf_anhang) {
        // Mit PDF-Anhang
        $boundary = md5(time());
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        
        $email_body = "--{$boundary}\r\n";
        $email_body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $email_body .= nl2br($nachricht) . "\r\n\r\n";
        
        // PDF generieren und anhängen
        $pdf_file = $type === 'angebot' ? 'generate_angebot_pdf.php' : 'generate_pdf.php';
        $pdf_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/{$pdf_file}?order_id={$order_id}";
        
        // PDF-Inhalt holen (vereinfacht - in Produktion besser mit file_get_contents)
        $pdf_content = @file_get_contents($pdf_url);
        
        if ($pdf_content) {
            $pdf_encoded = chunk_split(base64_encode($pdf_content));
            $pdf_name = $type === 'angebot' ? "Angebot_{$bestellung['angebotsnummer']}.pdf" : "Bestellung_{$bestellung['bestellungsnummer']}.pdf";
            
            $email_body .= "--{$boundary}\r\n";
            $email_body .= "Content-Type: application/pdf; name=\"{$pdf_name}\"\r\n";
            $email_body .= "Content-Transfer-Encoding: base64\r\n";
            $email_body .= "Content-Disposition: attachment; filename=\"{$pdf_name}\"\r\n\r\n";
            $email_body .= $pdf_encoded . "\r\n";
        }
        
        $email_body .= "--{$boundary}--";
    } else {
        // Nur Text
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_body = nl2br($nachricht);
    }
    
    // E-Mail versenden
    if (mail($empfaenger, $betreff, $email_body, $headers)) {
        $success = "E-Mail erfolgreich versendet!";
        
        // Bei Angebot: Automatische Lagerreservierung
        if ($type === 'angebot') {
            $reservierung = reserviereLagerbestand($order_id, $PDO);
            if ($reservierung['success']) {
                $success .= "<br>" . $reservierung['message'];
            } else {
                $success .= "<br><strong>Warnung:</strong> " . $reservierung['message'];
                if (isset($reservierung['fehler'])) {
                    $success .= "<br>" . implode('<br>', $reservierung['fehler']);
                }
            }
        }
    } else {
        $error = "E-Mail konnte nicht versendet werden. Bitte prüfen Sie die Server-Konfiguration.";
    }
}

// Standard-Nachricht vorbereiten
$standard_nachricht = $type === 'angebot' 
    ? "Sehr geehrte/r {$bestellung['vorname']} {$bestellung['nachname']},\n\nanbei erhalten Sie unser Angebot {$bestellung['angebotsnummer']}.\n\nBei Fragen stehen wir Ihnen gerne zur Verfügung.\n\nMit freundlichen Grüßen\nIhr Team"
    : "Sehr geehrte/r {$bestellung['vorname']} {$bestellung['nachname']},\n\nvielen Dank für Ihre Bestellung {$bestellung['bestellungsnummer']}.\n\nAnbei erhalten Sie die Auftragsbestätigung.\n\nMit freundlichen Grüßen\nIhr Team";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>E-Mail versenden</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --primary: linear-gradient(135deg, #14b8a6 0%, #06b6d4 100%); }
        body { background: linear-gradient(135deg, #cbd5e1 0%, #94a3b8 100%); min-height: 100vh; padding: 20px; }
        .container-main { max-width: 800px; margin: 0 auto; background: rgba(255,255,255,0.98); padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(20,184,166,0.15); border: 2px solid rgba(20,184,166,0.1); }
        h1 { background: var(--primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 700; }
        .btn-primary { background: var(--primary); border: none; }
    </style>
</head>
<body>
    <div class="container-main">
        <h1>📧 E-Mail versenden</h1>
        <a href="web_oberflaeche.php" class="btn btn-secondary mb-4">🏠 Zurück</a>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-body">
                <h5>Empfänger-Informationen</h5>
                <p><strong>Kunde:</strong> <?= htmlspecialchars($bestellung['vorname'] . ' ' . $bestellung['nachname']) ?></p>
                <p><strong>E-Mail:</strong> <?= htmlspecialchars($bestellung['email']) ?></p>
                <p><strong>Dokument:</strong> <?= $type === 'angebot' ? 'Angebot ' . $bestellung['angebotsnummer'] : 'Bestellung ' . $bestellung['bestellungsnummer'] ?></p>
            </div>
        </div>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Empfänger E-Mail *</label>
                <input type="email" name="empfaenger" class="form-control" value="<?= htmlspecialchars($bestellung['email']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Betreff *</label>
                <input type="text" name="betreff" class="form-control" value="<?= $type === 'angebot' ? 'Ihr Angebot ' . $bestellung['angebotsnummer'] : 'Ihre Bestellung ' . $bestellung['bestellungsnummer'] ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Nachricht *</label>
                <textarea name="nachricht" class="form-control" rows="10" required><?= htmlspecialchars($standard_nachricht) ?></textarea>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" name="pdf_anhang" class="form-check-input" id="pdf_anhang" checked>
                <label class="form-check-label" for="pdf_anhang">
                    PDF als Anhang mitsenden
                </label>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" name="send_email" class="btn btn-primary btn-lg">📧 E-Mail jetzt versenden</button>
                <a href="web_oberflaeche.php" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
