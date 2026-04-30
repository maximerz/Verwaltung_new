<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Angebot mit Produkten erstellen
if (isset($_POST['create_angebot'])) {
    $kundennummer = $_POST['kundennummer'];
    $angebotsnummer = 'ANG-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Gesamtpreis berechnen
    $gesamtpreis = 0;
    if (isset($_POST['produkte']) && is_array($_POST['produkte'])) {
        foreach ($_POST['produkte'] as $index => $artikel_id) {
            if (!empty($artikel_id) && !empty($_POST['mengen'][$index])) {
                $menge = (float)$_POST['mengen'][$index];
                $preis = (float)($_POST['preise'][$index] ?? 0);
                $gesamtpreis += $menge * $preis;
            }
        }
    }
    
    // Angebot erstellen
    $stmt = $PDO->prepare("INSERT INTO bestellungen (kundennummer, angebotsnummer, bestellunsname, status, gesamtpreis) VALUES (?, ?, ?, 'angebot', ?)");
    $stmt->execute([$kundennummer, $angebotsnummer, $_POST['angebotname'], $gesamtpreis]);
    $angebot_id = $PDO->lastInsertId();
    
    // Positionen speichern
    if (isset($_POST['produkte']) && is_array($_POST['produkte'])) {
        foreach ($_POST['produkte'] as $index => $artikel_id) {
            if (!empty($artikel_id) && !empty($_POST['mengen'][$index])) {
                $menge = (float)$_POST['mengen'][$index];
                $preis = (float)($_POST['preise'][$index] ?? 0);
                $artikelname = $_POST['artikelnamen'][$index] ?? '';
                
                // Position speichern
                $stmt = $PDO->prepare("INSERT INTO angebot_positionen (angebot_id, artikel_id, artikelname, menge, einzelpreis, gesamtpreis) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$angebot_id, $artikel_id, $artikelname, $menge, $preis, $menge * $preis]);
                
                // Reservierung erstellen
                $stmt = $PDO->prepare("INSERT INTO lager_reservierungen (artikel_id, menge, reserviert_fuer, angebot_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$artikel_id, $menge, "Angebot $angebotsnummer", $angebot_id]);
                
                // Reservierte Menge aktualisieren
                $stmt = $PDO->prepare("UPDATE lagerartikel SET reserviert = reserviert + ? WHERE id = ?");
                $stmt->execute([$menge, $artikel_id]);
            }
        }
    }
    
    $success = "Angebot erstellt und Produkte reserviert!";
    header("Location: web_oberflaeche.php?success=" . urlencode($success));
    exit;
}

// Kunden laden
$stmt = $PDO->query("SELECT * FROM kundensystem ORDER BY nachname, vorname");
$kunden = $stmt->fetchAll();

// Lagerartikel laden
$stmt = $PDO->query("SELECT *, (bestand - reserviert) as verfuegbar FROM lagerartikel WHERE (bestand - reserviert) > 0 ORDER BY artikelname");
$lagerartikel = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Angebot mit Produkten erstellen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: linear-gradient(135deg, #5B7DB1 0%, #D4AF37 100%);
        }
        body { background: linear-gradient(135deg, rgba(26,26,46,0.97) 0%, rgba(22,33,62,0.97) 50%); min-height: 100vh; padding: 20px; }
        .container-main { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 25px; }
        h1 { background: var(--primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 700; }
        .produkt-zeile { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 10px; }
        .btn-primary { background: var(--primary); border: none; }
    </style>
</head>
<body>
    <div class="container-main">
        <h1>📝 Angebot mit Produkten erstellen</h1>
        <a href="web_oberflaeche.php" class="btn btn-secondary mb-4">🏠 Zurück</a>

        <form method="POST">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Kunde *</label>
                    <select name="kundennummer" class="form-select" required>
                        <option value="">Kunde wählen...</option>
                        <?php foreach ($kunden as $kunde): ?>
                            <option value="<?= $kunde['kundennummer'] ?>">
                                <?= htmlspecialchars($kunde['vorname'] . ' ' . $kunde['nachname']) ?> (<?= $kunde['kundennummer'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Angebotsname *</label>
                    <input type="text" name="angebotname" class="form-control" required>
                </div>
            </div>

            <h3>📦 Produkte auswählen</h3>
            <div id="produkte-container">
                <div class="produkt-zeile">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Produkt</label>
                            <select name="produkte[]" class="form-select produkt-select" onchange="updateVerfuegbar(this)">
                                <option value="">Produkt wählen...</option>
                                <?php foreach ($lagerartikel as $art): ?>
                                    <option value="<?= $art['id'] ?>" data-verfuegbar="<?= $art['verfuegbar'] ?>" data-preis="<?= $art['verkaufspreis'] ?>" data-name="<?= htmlspecialchars($art['artikelname']) ?>">
                                        <?= htmlspecialchars($art['artikelname']) ?> - Verfügbar: <?= number_format($art['verfuegbar'], 0) ?> <?= $art['einheit'] ?> (<?= number_format($art['verkaufspreis'], 2) ?> €)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="artikelnamen[]" class="artikelname-hidden">
                            <input type="hidden" name="preise[]" class="preis-hidden">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Menge</label>
                            <input type="number" name="mengen[]" class="form-control menge-input" step="0.01" min="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Verfügbar</label>
                            <input type="text" class="form-control verfuegbar-anzeige" readonly>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-danger w-100" onclick="removeProdukt(this)">🗑️</button>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-success mb-4" onclick="addProdukt()">➕ Weiteres Produkt</button>

            <div class="d-grid gap-2">
                <button type="submit" name="create_angebot" class="btn btn-primary btn-lg">💾 Angebot erstellen und Produkte reservieren</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const produkteHtml = `<?php foreach ($lagerartikel as $art): ?>
            <option value="<?= $art['id'] ?>" data-verfuegbar="<?= $art['verfuegbar'] ?>" data-preis="<?= $art['verkaufspreis'] ?>" data-name="<?= htmlspecialchars($art['artikelname']) ?>">
                <?= htmlspecialchars($art['artikelname']) ?> - Verfügbar: <?= number_format($art['verfuegbar'], 0) ?> <?= $art['einheit'] ?> (<?= number_format($art['verkaufspreis'], 2) ?> €)
            </option>
        <?php endforeach; ?>`;

        function addProdukt() {
            const container = document.getElementById('produkte-container');
            const div = document.createElement('div');
            div.className = 'produkt-zeile';
            div.innerHTML = `
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Produkt</label>
                        <select name="produkte[]" class="form-select produkt-select" onchange="updateVerfuegbar(this)">
                            <option value="">Produkt wählen...</option>
                            ${produkteHtml}
                        </select>
                        <input type="hidden" name="artikelnamen[]" class="artikelname-hidden">
                        <input type="hidden" name="preise[]" class="preis-hidden">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Menge</label>
                        <input type="number" name="mengen[]" class="form-control menge-input" step="0.01" min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Verfügbar</label>
                        <input type="text" class="form-control verfuegbar-anzeige" readonly>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-danger w-100" onclick="removeProdukt(this)">🗑️</button>
                    </div>
                </div>
            `;
            container.appendChild(div);
        }

        function removeProdukt(btn) {
            btn.closest('.produkt-zeile').remove();
        }

        function updateVerfuegbar(select) {
            const option = select.options[select.selectedIndex];
            const verfuegbar = option.dataset.verfuegbar || '';
            const preis = option.dataset.preis || '';
            const name = option.dataset.name || '';
            const zeile = select.closest('.produkt-zeile');
            zeile.querySelector('.verfuegbar-anzeige').value = verfuegbar;
            zeile.querySelector('.preis-hidden').value = preis;
            zeile.querySelector('.artikelname-hidden').value = name;
        }
    </script>
</body>
</html>
