<?php
session_start();
require_once 'db_connection.php';

$kunde_id = $_GET['kunde_id'] ?? null;
$angebot_id = $_GET['angebot_id'] ?? null;
$kunde = null;
$angebot = null;
$positionen = [];

if ($kunde_id) {
    $stmt = $PDO->prepare("
        SELECT k.*, f.firmenname, f.strasse, f.ort 
        FROM kundensystem k 
        LEFT JOIN firma f ON k.firma_id = f.id 
        WHERE k.id = ?
    ");
    $stmt->execute([$kunde_id]);
    $kunde = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($angebot_id) {
    $stmt = $PDO->prepare("SELECT * FROM bestellungen WHERE idbestellung = ?");
    $stmt->execute([$angebot_id]);
    $angebot = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($angebot) {
        $stmt = $PDO->prepare("SELECT * FROM angebot_positionen WHERE angebot_id = ?");
        $stmt->execute([$angebot_id]);
        $positionen = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $PDO->prepare("
            SELECT k.*, f.firmenname, f.strasse, f.ort 
            FROM kundensystem k 
            LEFT JOIN firma f ON k.firma_id = f.id 
            WHERE k.kundennummer = ?
        ");
        $stmt->execute([$angebot['kundennummer']]);
        $kunde = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($_POST) {
    try {
        if ($angebot_id) {
            $stmt = $PDO->prepare("UPDATE bestellungen SET bestellungsname = ?, lieferzeit = ? WHERE idbestellung = ?");
            $stmt->execute([$_POST['bestellungsname'], $_POST['lieferzeit'], $angebot_id]);
            $stmt = $PDO->prepare("DELETE FROM angebot_positionen WHERE angebot_id = ?");
            $stmt->execute([$angebot_id]);
        } else {
            $angebotsnummer = 'Angebot#' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $bestellnummer = 'B' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            $stmt = $PDO->prepare("INSERT INTO bestellungen (kundennummer, bestellungsnummer, bestellungsname, angebotsnummer, status, lieferzeit) VALUES (?, ?, ?, ?, 'angebot', ?)");
            $stmt->execute([$kunde['kundennummer'], $bestellnummer, $_POST['bestellungsname'], $angebotsnummer, $_POST['lieferzeit']]);
            $angebot_id = $PDO->lastInsertId();
        }
        
        $gesamtsumme = 0;
        $lager_warnungen = [];
        
        if (isset($_POST['artikel']) && is_array($_POST['artikel'])) {
            for ($i = 0; $i < count($_POST['artikel']); $i++) {
                $artikel_name = $_POST['artikel'][$i] === 'custom' ? ($_POST['artikel_custom'][$i] ?? '') : $_POST['artikel'][$i];
                if (!empty($artikel_name)) {
                    $menge = (int)$_POST['menge'][$i];
                    $einzelpreis = (float)$_POST['einzelpreis'][$i];
                    $gesamtpreis = $menge * $einzelpreis;
                    $gesamtsumme += $gesamtpreis;
                    
                    $stmt = $PDO->prepare("INSERT INTO angebot_positionen (angebot_id, artikel, beschreibung, menge, einzelpreis, gesamtpreis) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$angebot_id, $artikel_name, $_POST['beschreibung'][$i] ?? '', $menge, $einzelpreis, $gesamtpreis]);
                    
                    // Lager prüfen und reservieren
                    $stmt_lager = $PDO->prepare("SELECT id, bestand, reserviert FROM lagerartikel WHERE artikelname = ?");
                    $stmt_lager->execute([$artikel_name]);
                    $lager_artikel = $stmt_lager->fetch();
                    
                    if ($lager_artikel) {
                        $verfuegbar = $lager_artikel['bestand'] - $lager_artikel['reserviert'];
                        
                        if ($verfuegbar >= $menge) {
                            // Reservieren
                            $stmt_res = $PDO->prepare("UPDATE lagerartikel SET reserviert = reserviert + ? WHERE id = ?");
                            $stmt_res->execute([$menge, $lager_artikel['id']]);
                            
                            $stmt_log = $PDO->prepare("INSERT INTO lager_reservierungen (artikel_id, menge, reserviert_fuer, angebot_id, status) VALUES (?, ?, ?, ?, 'reserviert')");
                            $stmt_log->execute([$lager_artikel['id'], $menge, 'Angebot #' . $angebot_id, $angebot_id]);
                        } else {
                            $lager_warnungen[] = "<strong>$artikel_name</strong>: Nur $verfuegbar verfügbar, benötigt: $menge - Bitte nachbestellen!";
                        }
                    } else {
                        $lager_warnungen[] = "<strong>$artikel_name</strong>: Nicht im Lager vorhanden - Bitte anlegen und bestellen!";
                    }
                }
            }
        }
        
        $stmt = $PDO->prepare("UPDATE bestellungen SET gesamtpreis = ? WHERE idbestellung = ?");
        $stmt->execute([$gesamtsumme, $angebot_id]);
        
        // Wenn Lager-Warnungen vorhanden, zeige diese an
        if (!empty($lager_warnungen)) {
            $_SESSION['lager_warnungen'] = $lager_warnungen;
            $_SESSION['angebot_id'] = $angebot_id;
        }
        
        $redirect_url = isset($_GET['kunde_id']) ? 'kunde_details.php?id=' . $_GET['kunde_id'] : 'web_oberflaeche.php';
        header("Location: $redirect_url");
        exit;
    } catch (Exception $e) {
        echo "Fehler: " . $e->getMessage();
        exit;
    }
}

$stmt_prod = $PDO->prepare("SELECT * FROM produkte ORDER BY kategorie, name");
$stmt_prod->execute();
$produkte_list = $stmt_prod->fetchAll();
?>
<?php $page_title = $angebot_id ? 'Angebot bearbeiten' : 'Neues Angebot erstellen'; include 'includes/header.php'; ?>
<?php include 'includes/table-style.php'; ?>

<style>
.position-row { background: var(--light); padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; border: 2px solid var(--border); }
.artikel-container { position: relative; }
.artikel-dropdown { position: absolute; z-index: 1000; background: white; border: 2px solid var(--border); border-radius: 8px; max-height: 300px; overflow-y: auto; width: 100%; display: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
.artikel-option { padding: 0.75rem; cursor: pointer; transition: background 0.2s; }
.artikel-option:hover { background: var(--light); }
.row { display: flex; gap: 1rem; flex-wrap: wrap; }
.col { flex: 1; min-width: 200px; }
.col-small { flex: 0 0 80px; }
</style>

<div class="dashboard-card">
    <h2 class="section-title mb-4">
        <i class="fas fa-file-invoice me-2"></i><?= $angebot_id ? 'Angebot bearbeiten' : 'Neues Angebot erstellen' ?>
    </h2>

    <?php if ($kunde): ?>
    <div class="alert alert-info mb-4">
        <h5 class="mb-2"><i class="fas fa-user me-2"></i><?= htmlspecialchars($kunde['vorname'] . ' ' . $kunde['nachname']) ?></h5>
        <p class="mb-1"><strong>Kundennummer:</strong> <?= htmlspecialchars($kunde['kundennummer']) ?></p>
        <p class="mb-0"><strong>Firma:</strong> <?= htmlspecialchars($kunde['firmenname'] ?? 'Keine Firma') ?></p>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="row mb-4">
            <div class="col">
                <label class="form-label"><i class="fas fa-tag me-1"></i>Bestellungsname</label>
                <input type="text" class="form-control" name="bestellungsname" value="<?= htmlspecialchars($angebot['bestellungsname'] ?? 'Angebot für ' . ($kunde['firmenname'] ?? $kunde['nachname'] ?? '')) ?>" required>
            </div>
            <div class="col">
                <label class="form-label"><i class="fas fa-clock me-1"></i>Lieferzeit (Tage)</label>
                <input type="number" class="form-control" name="lieferzeit" value="<?= $angebot['lieferzeit'] ?? '14' ?>" min="1" required>
            </div>
        </div>

        <h4 class="mb-3"><i class="fas fa-box me-2"></i>Positionen</h4>
        <div id="positionen">
                <?php if (!empty($positionen)): ?>
                    <?php foreach ($positionen as $position): ?>
                <div class="position-row">
                    <div class="row">
                        <div class="col">
                            <label class="form-label">Artikel</label>
                            <div class="artikel-container">
                                <input type="text" class="form-control artikel-search" placeholder="Produkt suchen..." value="<?= htmlspecialchars($position['artikel']) ?>" autocomplete="off">
                                <div class="artikel-dropdown">
                                            <?php foreach($produkte_list as $prod): ?>
                                                <div class="artikel-option" data-value="<?= htmlspecialchars($prod['name']) ?>" data-preis="<?= $prod['preis'] ?>" data-beschreibung="<?= htmlspecialchars($prod['beschreibung']) ?>">
                                                    <?= htmlspecialchars($prod['name']) ?> (<?= number_format($prod['preis'], 2, ',', '.') ?> €)
                                                </div>
                                            <?php endforeach; ?>
                                            <div class="artikel-option" data-value="custom">Benutzerdefiniert...</div>
                                </div>
                            </div>
                            <input type="hidden" class="artikel-value" name="artikel[]" value="<?= htmlspecialchars($position['artikel']) ?>" required>
                            <input type="text" class="form-control artikel-input" name="artikel_custom[]" placeholder="Artikel eingeben" style="display: none; margin-top: 5px;">
                        </div>
                        <div class="col-small">
                            <label class="form-label">Menge</label>
                            <select class="form-select" name="menge[]" required>
                                        <?php for($i = 1; $i <= 100; $i++): ?>
                                            <option value="<?= $i ?>" <?= $position['menge'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                        <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label">Einzelpreis (€)</label>
                            <input type="number" class="form-control" name="einzelpreis[]" value="<?= $position['einzelpreis'] ?>" step="0.01" min="0" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Beschreibung</label>
                            <input type="text" class="form-control" name="beschreibung[]" value="<?= htmlspecialchars($position['beschreibung']) ?>">
                        </div>
                        <div class="col-small">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-danger w-100 remove-position"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="position-row">
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label>Artikel</label>
                                    <div class="artikel-container">
                                        <input type="text" class="artikel-search" placeholder="Produkt suchen..." autocomplete="off">
                                        <div class="artikel-dropdown">
                                            <?php foreach($produkte_list as $prod): ?>
                                                <div class="artikel-option" data-value="<?= htmlspecialchars($prod['name']) ?>" data-preis="<?= $prod['preis'] ?>" data-beschreibung="<?= htmlspecialchars($prod['beschreibung']) ?>">
                                                    <?= htmlspecialchars($prod['name']) ?> (<?= number_format($prod['preis'], 2, ',', '.') ?> €)
                                                </div>
                                            <?php endforeach; ?>
                                            <div class="artikel-option" data-value="custom">Benutzerdefiniert...</div>
                                        </div>
                                    </div>
                                    <input type="hidden" class="artikel-value" name="artikel[]" required>
                                    <input type="text" class="artikel-input" name="artikel_custom[]" placeholder="Artikel eingeben" style="display: none; margin-top: 5px;">
                                </div>
                            </div>
                            <div class="col-small">
                                <div class="form-group">
                                    <label>Menge</label>
                                    <select name="menge[]" required>
                                        <?php for($i = 1; $i <= 100; $i++): ?>
                                            <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label>Einzelpreis (€)</label>
                                    <input type="number" name="einzelpreis[]" value="100.00" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label>Beschreibung</label>
                                    <input type="text" name="beschreibung[]" value="Hochwertiges Produkt">
                                </div>
                            </div>
                            <div class="col-small">
                                <button type="button" class="btn btn-danger remove-position">-</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
        </div>

        <button type="button" id="add-position" class="btn btn-success mb-4">
            <i class="fas fa-plus me-2"></i>Produkt hinzufügen
        </button>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-check me-2"></i><?= $angebot_id ? 'Aktualisieren' : 'Erstellen' ?>
            </button>
            <a href="<?= isset($_GET['kunde_id']) ? 'kunde_details.php?id=' . $_GET['kunde_id'] : 'web_oberflaeche.php' ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Zurück
            </a>
        </div>
    </form>
</div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Durchsuchbares Artikel-Dropdown
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('artikel-search')) {
                    const container = e.target.closest('.artikel-container');
                    const dropdown = container.querySelector('.artikel-dropdown');
                    const searchTerm = e.target.value.toLowerCase();
                    const options = dropdown.querySelectorAll('.artikel-option');
                    
                    let hasVisible = false;
                    options.forEach(option => {
                        const text = option.textContent.toLowerCase();
                        if (text.includes(searchTerm) || option.dataset.value === 'custom') {
                            option.style.display = 'block';
                            hasVisible = true;
                        } else {
                            option.style.display = 'none';
                        }
                    });
                    
                    dropdown.style.display = hasVisible && searchTerm ? 'block' : 'none';
                }
            });
            
            // Artikel-Option auswählen
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('artikel-option')) {
                    const row = e.target.closest('.position-row');
                    const searchInput = row.querySelector('.artikel-search');
                    const hiddenInput = row.querySelector('.artikel-value');
                    const customInput = row.querySelector('.artikel-input');
                    const preisInput = row.querySelector('input[name="einzelpreis[]"]');
                    const beschreibungInput = row.querySelector('input[name="beschreibung[]"]');
                    const dropdown = e.target.closest('.artikel-dropdown');
                    
                    if (e.target.dataset.value === 'custom') {
                        searchInput.value = 'Benutzerdefiniert';
                        hiddenInput.value = 'custom';
                        customInput.style.display = 'block';
                        customInput.required = true;
                    } else {
                        searchInput.value = e.target.textContent;
                        hiddenInput.value = e.target.dataset.value;
                        customInput.style.display = 'none';
                        customInput.required = false;
                        
                        if (e.target.dataset.preis) {
                            preisInput.value = e.target.dataset.preis;
                        }
                        if (e.target.dataset.beschreibung) {
                            beschreibungInput.value = e.target.dataset.beschreibung;
                        }
                    }
                    
                    dropdown.style.display = 'none';
                }
            });
            
            // Dropdown schließen bei Klick außerhalb
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.artikel-container')) {
                    document.querySelectorAll('.artikel-dropdown').forEach(dropdown => {
                        dropdown.style.display = 'none';
                    });
                }
            });
            
            // Position hinzufügen
            document.getElementById('add-position').addEventListener('click', function() {
                const template = document.querySelector('.position-row').cloneNode(true);
                template.querySelectorAll('input').forEach(input => {
                    if (input.type === 'number') input.value = '100.00';
                    else if (input.type === 'text') input.value = '';
                    else if (input.type === 'hidden') input.value = '';
                });
                template.querySelector('.artikel-dropdown').style.display = 'none';
                template.querySelector('.artikel-input').style.display = 'none';
                template.querySelector('select[name="menge[]"]').selectedIndex = 0;
                document.getElementById('positionen').appendChild(template);
            });
            
            // Position entfernen
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-position')) {
                    if (document.querySelectorAll('.position-row').length > 1) {
                        e.target.closest('.position-row').remove();
                    }
                }
            });
        });
    </script>
<?php include 'includes/footer.php'; ?>