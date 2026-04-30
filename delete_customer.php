<?php
session_start();
require_once 'db_connection.php';
require_once 'security.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$kunde_id = $_GET['id'] ?? null;
$confirmed = $_POST['confirmed'] ?? false;

if (!$kunde_id) {
    header('Location: kunden_verwaltung.php');
    exit;
}

// Kundendaten laden
$stmt = $PDO->prepare("SELECT k.*, f.firmenname FROM kundensystem k LEFT JOIN firma f ON k.firma_id = f.id WHERE k.id = ?");
$stmt->execute([$kunde_id]);
$kunde = $stmt->fetch();

if (!$kunde) {
    header('Location: kunden_verwaltung.php?error=not_found');
    exit;
}

// Prüfen ob Geschäftsdaten vorhanden
$stmt = $PDO->prepare("SELECT COUNT(*) as anzahl FROM bestellungen WHERE kundennummer = ?");
$stmt->execute([$kunde['kundennummer']]);
$bestellungen = $stmt->fetch()['anzahl'];

$stmt = $PDO->prepare("SELECT COUNT(*) as anzahl FROM rechnungen WHERE kunde_id = ?");
$stmt->execute([$kunde['kundennummer']]);
$rechnungen = $stmt->fetch()['anzahl'];

$has_business_data = ($bestellungen > 0 || $rechnungen > 0);

// Löschung durchführen
if ($_POST && $confirmed === 'DELETE_CONFIRMED') {
    try {
        $PDO->beginTransaction();
        
        if ($has_business_data) {
            // Nur Personendaten anonymisieren, Geschäftsdaten behalten
            $stmt = $PDO->prepare("
                UPDATE kundensystem 
                SET vorname = 'Gelöscht',
                    nachname = 'Gelöscht',
                    email = CONCAT('deleted_', id, '@anonymized.local')
                WHERE id = ?
            ");
            $stmt->execute([$kunde_id]);
            
            log_audit('DELETE_ANONYMIZE', 'customer', $kunde_id, 'Kunde anonymisiert (Geschäftsdaten behalten wegen Aufbewahrungspflicht)');
            
            $PDO->commit();
            header('Location: kunden_verwaltung.php?success=anonymized');
        } else {
            // Vollständige Löschung möglich
            $stmt = $PDO->prepare("DELETE FROM kundensystem WHERE id = ?");
            $stmt->execute([$kunde_id]);
            
            log_audit('DELETE_COMPLETE', 'customer', $kunde_id, 'Kunde vollständig gelöscht (keine Geschäftsdaten vorhanden)');
            
            $PDO->commit();
            header('Location: kunden_verwaltung.php?success=deleted');
        }
        exit;
    } catch (Exception $e) {
        $PDO->rollBack();
        $error = "Fehler beim Löschen: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Kunde löschen - ERP System</title>
    <style>
        :root { --primary: linear-gradient(135deg, #5B7DB1 0%, #D4AF37 100%); --danger: linear-gradient(135deg, #EF5350 0%, #E53935 100%); --shadow: 0 15px 50px rgba(201,162,39,0.15); }
        body { font-family: 'Inter', Arial, sans-serif; margin: 0; padding: 20px; background: #F0F2F5; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 700px; width: 100%; background: rgba(255,255,255,0.98); padding: 40px; border-radius: 25px; box-shadow: var(--shadow); }
        h1, h2 { background: var(--danger); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700; text-align: center; }
        .warning-box { background: #fff3cd; border-left: 5px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 10px; color: #856404; }
        .danger-box { background: #f8d7da; border-left: 5px solid #dc3545; padding: 20px; margin: 20px 0; border-radius: 10px; color: #721c24; }
        .info-box { background: #d1ecf1; border-left: 5px solid #0c5460; padding: 20px; margin: 20px 0; border-radius: 10px; color: #0c5460; }
        .kunde-info { background: rgba(201,162,39,0.1); padding: 20px; border-radius: 15px; margin: 20px 0; }
        .btn { padding: 15px 30px; border: none; border-radius: 50px; font-weight: 600; cursor: pointer; margin: 10px 5px; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-secondary { background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
        .checkbox-container { margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 10px; }
        .checkbox-container label { display: flex; align-items: center; gap: 10px; font-weight: 600; cursor: pointer; }
        .checkbox-container input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
        .text-center { text-align: center; }
        ul { line-height: 2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚠️ Kunde löschen</h1>
        
        <?php if (isset($error)): ?>
            <div class="danger-box">
                <strong>Fehler:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="kunde-info">
            <h3>Zu löschender Kunde:</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($kunde['vorname'] . ' ' . $kunde['nachname']) ?></p>
            <p><strong>E-Mail:</strong> <?= htmlspecialchars($kunde['email']) ?></p>
            <p><strong>Firma:</strong> <?= htmlspecialchars($kunde['firmenname'] ?? 'Keine Firma') ?></p>
            <p><strong>Kundennummer:</strong> <?= htmlspecialchars($kunde['kundennummer']) ?></p>
        </div>

        <?php if ($has_business_data): ?>
            <div class="danger-box">
                <h3>⚠️ WICHTIGER HINWEIS - Gesetzliche Aufbewahrungspflicht</h3>
                <p><strong>Dieser Kunde hat Geschäftsdaten (Bestellungen/Rechnungen)!</strong></p>
                <ul>
                    <li><strong>Bestellungen:</strong> <?= $bestellungen ?></li>
                    <li><strong>Rechnungen:</strong> <?= $rechnungen ?></li>
                </ul>
            </div>

            <div class="warning-box">
                <h3>📋 Gesetzliche Aufbewahrungsfristen (Deutschland)</h3>
                <p><strong>Gemäß Handelsgesetzbuch (HGB) und Abgabenordnung (AO):</strong></p>
                <ul>
                    <li><strong>§ 147 AO:</strong> Rechnungen müssen <strong>10 Jahre</strong> aufbewahrt werden</li>
                    <li><strong>§ 257 HGB:</strong> Geschäftsbriefe müssen <strong>6 Jahre</strong> aufbewahrt werden</li>
                    <li><strong>§ 257 HGB:</strong> Buchungsbelege müssen <strong>10 Jahre</strong> aufbewahrt werden</li>
                </ul>
                <p style="margin-top: 15px; font-weight: bold;">
                    ⚖️ Diese Fristen haben Vorrang vor dem DSGVO-Löschrecht (Art. 17 Abs. 3 lit. b DSGVO)!
                </p>
            </div>

            <div class="info-box">
                <h3>🔒 Was wird gelöscht?</h3>
                <p><strong>Personenbezogene Daten werden anonymisiert:</strong></p>
                <ul>
                    <li>✓ Vorname → "Gelöscht"</li>
                    <li>✓ Nachname → "Gelöscht"</li>
                    <li>✓ E-Mail → "deleted_[id]@anonymized.local"</li>
                </ul>
                <p><strong>Geschäftsdaten bleiben erhalten:</strong></p>
                <ul>
                    <li>✓ Bestellungen (mit Kundennummer)</li>
                    <li>✓ Rechnungen (mit Kundennummer)</li>
                    <li>✓ Buchungen (für Buchhaltung)</li>
                </ul>
                <p style="margin-top: 15px;">
                    <strong>Rechtliche Grundlage:</strong> Art. 17 Abs. 3 lit. b DSGVO - 
                    "Das Recht auf Löschung besteht nicht, soweit die Verarbeitung erforderlich ist zur Erfüllung einer rechtlichen Verpflichtung."
                </p>
            </div>

            <form method="POST" id="deleteForm">
                <div class="checkbox-container">
                    <label>
                        <input type="checkbox" id="confirm1" required>
                        Ich bestätige, dass ich über die gesetzlichen Aufbewahrungsfristen informiert wurde
                    </label>
                </div>
                
                <div class="checkbox-container">
                    <label>
                        <input type="checkbox" id="confirm2" required>
                        Ich verstehe, dass Geschäftsdaten aus rechtlichen Gründen erhalten bleiben müssen
                    </label>
                </div>
                
                <div class="checkbox-container">
                    <label>
                        <input type="checkbox" id="confirm3" required>
                        Ich möchte die personenbezogenen Daten dieses Kunden unwiderruflich anonymisieren
                    </label>
                </div>

                <input type="hidden" name="confirmed" value="DELETE_CONFIRMED">
                
                <div class="text-center" style="margin-top: 30px;">
                    <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                        🗑️ Kunde anonymisieren (Geschäftsdaten behalten)
                    </button>
                    <a href="kunde_details.php?id=<?= $kunde_id ?>" class="btn btn-secondary">
                        ❌ Abbrechen
                    </a>
                </div>
            </form>

        <?php else: ?>
            <div class="info-box">
                <h3>✅ Vollständige Löschung möglich</h3>
                <p>Dieser Kunde hat keine Geschäftsdaten (Bestellungen/Rechnungen).</p>
                <p>Eine vollständige Löschung ist daher rechtlich zulässig.</p>
            </div>

            <form method="POST" id="deleteForm">
                <div class="checkbox-container">
                    <label>
                        <input type="checkbox" id="confirm1" required>
                        Ich möchte diesen Kunden unwiderruflich löschen
                    </label>
                </div>

                <input type="hidden" name="confirmed" value="DELETE_CONFIRMED">
                
                <div class="text-center" style="margin-top: 30px;">
                    <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                        🗑️ Kunde vollständig löschen
                    </button>
                    <a href="kunde_details.php?id=<?= $kunde_id ?>" class="btn btn-secondary">
                        ❌ Abbrechen
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Alle Checkboxen müssen aktiviert sein
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        const deleteBtn = document.getElementById('deleteBtn');
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                deleteBtn.disabled = !allChecked;
            });
        });
        
        // Finale Bestätigung
        document.getElementById('deleteForm').addEventListener('submit', (e) => {
            const hasBusinessData = <?= $has_business_data ? 'true' : 'false' ?>;
            const message = hasBusinessData 
                ? 'LETZTE WARNUNG!\n\nDie personenbezogenen Daten werden unwiderruflich anonymisiert.\nGeschäftsdaten bleiben aus rechtlichen Gründen erhalten.\n\nFortfahren?' 
                : 'LETZTE WARNUNG!\n\nDer Kunde wird vollständig und unwiderruflich gelöscht.\n\nFortfahren?';
            
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
