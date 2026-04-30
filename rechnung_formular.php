<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$bestellung_id = $_GET['bestellung_id'] ?? null;
$rechnung_id = $_GET['rechnung_id'] ?? null;
$bestellung = null;
$rechnung = null;
$positionen = [];

// Bestellung laden für neue Rechnung
if ($bestellung_id) {
    $stmt = $PDO->prepare("
        SELECT b.*, k.vorname, k.nachname, k.email, f.firmenname, f.strasse, f.ort 
        FROM bestellungen b 
        LEFT JOIN kundensystem k ON b.kundennummer = k.kundennummer 
        LEFT JOIN firma f ON k.firma_id = f.id 
        WHERE b.idbestellung = ?
    ");
    $stmt->execute([$bestellung_id]);
    $bestellung = $stmt->fetch();
    
    if ($bestellung) {
        $stmt = $PDO->prepare("SELECT * FROM angebot_positionen WHERE angebot_id = ?");
        $stmt->execute([$bestellung_id]);
        $positionen = $stmt->fetchAll();
    }
}

// Bestehende Rechnung laden
if ($rechnung_id) {
    $stmt = $PDO->prepare("
        SELECT r.*, b.*, k.vorname, k.nachname, k.email, f.firmenname, f.strasse, f.ort 
        FROM rechnungen r
        LEFT JOIN bestellungen b ON r.bestellung_id = b.idbestellung
        LEFT JOIN kundensystem k ON r.kunde_id = k.kundennummer 
        LEFT JOIN firma f ON k.firma_id = f.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$rechnung_id]);
    $rechnung = $stmt->fetch();
    
    if ($rechnung && $rechnung['bestellung_id']) {
        $stmt = $PDO->prepare("SELECT * FROM angebot_positionen WHERE angebot_id = ?");
        $stmt->execute([$rechnung['bestellung_id']]);
        $positionen = $stmt->fetchAll();
    }
}

// Formular verarbeiten
if ($_POST) {
    try {
        $rechnungsnummer = $_POST['rechnungsnummer'];
        $rechnungsdatum = $_POST['rechnungsdatum'];
        $faelligkeitsdatum = $_POST['faelligkeitsdatum'];
        $nettobetrag = (float)$_POST['nettobetrag'];
        $mwst_satz = (float)$_POST['mwst_satz'] / 100;
        $mwst_betrag = $nettobetrag * $mwst_satz;
        $bruttobetrag = $nettobetrag + $mwst_betrag;
        
        if ($rechnung_id) {
            // Rechnung aktualisieren
            $stmt = $PDO->prepare("
                UPDATE rechnungen SET 
                rechnungsnummer = ?, rechnungsdatum = ?, faelligkeitsdatum = ?, 
                nettobetrag = ?, mwst_betrag = ?, bruttobetrag = ?
                WHERE id = ?
            ");
            $stmt->execute([$rechnungsnummer, $rechnungsdatum, $faelligkeitsdatum, $nettobetrag, $mwst_betrag, $bruttobetrag, $rechnung_id]);
        } else {
            // Neue Rechnung erstellen
            $kunde_id = $bestellung['kundennummer'];
            $stmt = $PDO->prepare("
                INSERT INTO rechnungen 
                (rechnungsnummer, bestellung_id, kunde_id, rechnungsdatum, faelligkeitsdatum, nettobetrag, mwst_betrag, bruttobetrag) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$rechnungsnummer, $bestellung_id, $kunde_id, $rechnungsdatum, $faelligkeitsdatum, $nettobetrag, $mwst_betrag, $bruttobetrag]);
            
            // Buchung erstellen
            $stmt = $PDO->prepare("INSERT INTO buchungen (buchungsdatum, belegnummer, konto_soll, konto_haben, betrag, beschreibung, kategorie) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$rechnungsdatum, $rechnungsnummer, '1400', '3400', $nettobetrag, 'Warenverkauf', 'Umsatz']);
            $stmt->execute([$rechnungsdatum, $rechnungsnummer, '1400', '3800', $mwst_betrag, 'Umsatzsteuer', 'Steuer']);
        }
        
        header('Location: finanzbuchhaltung.php');
        exit;
    } catch (Exception $e) {
        $error = "Fehler: " . $e->getMessage();
    }
}

// Standardwerte setzen
$data = $rechnung ?: $bestellung;
$rechnungsnummer = $rechnung['rechnungsnummer'] ?? ('RE' . date('Y') . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT));
$rechnungsdatum = $rechnung['rechnungsdatum'] ?? date('Y-m-d');
$faelligkeitsdatum = $rechnung['faelligkeitsdatum'] ?? date('Y-m-d', strtotime('+30 days'));
$nettobetrag = $rechnung['nettobetrag'] ?? ($data['gesamtpreis'] ?? 0);
$mwst_satz = 19;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rechnung <?= $rechnung_id ? 'bearbeiten' : 'erstellen' ?> - ERP System</title>
    <style>
        :root {
            --primary: linear-gradient(135deg, #5B7DB1 0%, #D4AF37 100%);
            --shadow: 0 15px 50px rgba(201,162,39,0.15);
        }
        body { font-family: 'Inter', Arial, sans-serif; margin: 0; padding: 20px; background: #F0F2F5; min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; background: rgba(255,255,255,0.98); padding: 30px; border-radius: 25px; box-shadow: var(--shadow); border: 2px solid rgba(255,255,255,0.3); }
        .header { text-align: center; margin-bottom: 30px; }
        h1, h2 { background: var(--primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700; }
        .form-section { background: rgba(201,162,39,0.1); padding: 20px; border-radius: 15px; margin: 20px 0; border-left: 5px solid #5B7DB1; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 12px; transition: all 0.3s ease; }
        .form-group input:focus { border-color: #5B7DB1; outline: none; box-shadow: 0 0 0 0.2rem rgba(201,162,39,0.25); }
        .btn { background: var(--primary); color: white; padding: 12px 24px; border: none; border-radius: 50px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.3s ease; }
        .btn:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
        .btn-secondary { background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; border-radius: 15px; overflow: hidden; }
        .table th, .table td { border: 1px solid #e9ecef; padding: 12px; text-align: left; }
        .table th { background: var(--primary); color: white; font-weight: 600; }
        .table tr:nth-child(even) { background: rgba(201,162,39,0.05); }
        .row { display: flex; gap: 20px; }
        .col { flex: 1; }
        .error { color: #E53935; font-weight: bold; padding: 12px; background: rgba(229,57,53,0.1); border-radius: 12px; border-left: 4px solid #E53935; margin: 15px 0; }
        .total-box { background: var(--primary); color: white; padding: 15px; border-radius: 15px; text-align: right; box-shadow: var(--shadow); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Rechnung <?= $rechnung_id ? 'bearbeiten' : 'erstellen' ?></h1>
        </div>

        <div style="text-align: center; margin-bottom: 20px;">
            <a href="finanzbuchhaltung.php" class="btn btn-secondary">⬅️ Zurück zur Finanzbuchhaltung</a>
        </div>

        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST" id="rechnungsform">
            <div class="form-section">
                <h2>📋 Rechnungsdaten</h2>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Rechnungsnummer</label>
                            <input type="text" name="rechnungsnummer" value="<?= htmlspecialchars($rechnungsnummer) ?>" required>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Rechnungsdatum</label>
                            <input type="date" name="rechnungsdatum" value="<?= $rechnungsdatum ?>" required>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Fälligkeitsdatum</label>
                            <input type="date" name="faelligkeitsdatum" value="<?= $faelligkeitsdatum ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($data): ?>
            <div class="form-section">
                <h2>👤 Kundendaten</h2>
                <div class="row">
                    <div class="col">
                        <p><strong>Name:</strong> <?= htmlspecialchars($data['vorname'] . ' ' . $data['nachname']) ?></p>
                        <p><strong>E-Mail:</strong> <?= htmlspecialchars($data['email']) ?></p>
                    </div>
                    <div class="col">
                        <p><strong>Firma:</strong> <?= htmlspecialchars($data['firmenname'] ?? 'Privatkunde') ?></p>
                        <p><strong>Adresse:</strong> <?= htmlspecialchars(($data['strasse'] ?? '') . ', ' . ($data['ort'] ?? '')) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($positionen)): ?>
            <div class="form-section">
                <h2>📦 Rechnungspositionen</h2>
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
                            <td><?= $position['menge'] ?></td>
                            <td><?= number_format($position['einzelpreis'], 2, ',', '.') ?> €</td>
                            <td><?= number_format($position['gesamtpreis'], 2, ',', '.') ?> €</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="form-section">
                <h2>💰 Rechnungsbeträge</h2>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Nettobetrag (€)</label>
                            <input type="number" name="nettobetrag" id="nettobetrag" value="<?= number_format($nettobetrag, 2, '.', '') ?>" step="0.01" required onchange="calculateTotal()">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>MwSt.-Satz (%)</label>
                            <select name="mwst_satz" id="mwst_satz" onchange="calculateTotal()">
                                <option value="0">0%</option>
                                <option value="7">7%</option>
                                <option value="19" selected>19%</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="total-box">
                    <div id="calculation">
                        <p>Nettobetrag: <span id="netto_display"><?= number_format($nettobetrag, 2, ',', '.') ?></span> €</p>
                        <p>MwSt. (19%): <span id="mwst_display"><?= number_format($nettobetrag * 0.19, 2, ',', '.') ?></span> €</p>
                        <p><strong>Bruttobetrag: <span id="brutto_display"><?= number_format($nettobetrag * 1.19, 2, ',', '.') ?></span> €</strong></p>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn">💾 <?= $rechnung_id ? 'Rechnung aktualisieren' : 'Rechnung erstellen' ?></button>
                <a href="finanzbuchhaltung.php" class="btn btn-secondary">❌ Abbrechen</a>
            </div>
        </form>
    </div>

    <script>
        function calculateTotal() {
            const netto = parseFloat(document.getElementById('nettobetrag').value) || 0;
            const mwstSatz = parseFloat(document.getElementById('mwst_satz').value) / 100;
            const mwstBetrag = netto * mwstSatz;
            const brutto = netto + mwstBetrag;
            
            document.getElementById('netto_display').textContent = netto.toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('mwst_display').textContent = mwstBetrag.toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('brutto_display').textContent = brutto.toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        
        // Initial calculation
        calculateTotal();
    </script>
</body>
</html>