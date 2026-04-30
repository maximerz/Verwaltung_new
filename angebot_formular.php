<?php
session_start();
require_once 'db_connection.php';
require_once 'includes/audit_log.php';

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

// Konditionen-Vorlagen laden
$stmt = $PDO->prepare("SELECT * FROM konditionen_vorlagen WHERE aktiv = 1 ORDER BY name");
$stmt->execute();
$konditionen_vorlagen = $stmt->fetchAll();

if ($_POST) {
    try {
        // Konditionen zusammenführen
        $konditionen = $_POST['konditionen_custom'] ?? $_POST['konditionen'] ?? '';
        
        if ($angebot_id) {
            $stmt = $PDO->prepare("UPDATE bestellungen SET bestellungsname = ?, lieferzeit = ?, konditionen = ? WHERE idbestellung = ?");
            $stmt->execute([$_POST['bestellungsname'], $_POST['lieferzeit'], $konditionen, $angebot_id]);
            audit_log($PDO, 'UPDATE', 'bestellungen', $angebot_id, null, ['bestellungsname' => $_POST['bestellungsname'], 'lieferzeit' => $_POST['lieferzeit'], 'konditionen' => $konditionen]);
            $stmt = $PDO->prepare("DELETE FROM angebot_positionen WHERE angebot_id = ?");
            $stmt->execute([$angebot_id]);
        } else {
            $angebotsnummer = 'Angebot#' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $bestellnummer = 'B' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            $stmt = $PDO->prepare("INSERT INTO bestellungen (kundennummer, bestellungsnummer, bestellungsname, angebotsnummer, status, lieferzeit, konditionen) VALUES (?, ?, ?, ?, 'angebot', ?, ?)");
            $stmt->execute([$kunde['kundennummer'], $bestellnummer, $_POST['bestellungsname'], $angebotsnummer, $_POST['lieferzeit'], $konditionen]);
            $angebot_id = $PDO->lastInsertId();
            audit_log($PDO, 'INSERT', 'bestellungen', $angebot_id, null, ['angebotsnummer' => $angebotsnummer, 'kunde' => $kunde['kundennummer'], 'konditionen' => $konditionen]);
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
                    
                    $stmt_lager = $PDO->prepare("SELECT id, bestand, reserviert FROM lagerartikel WHERE artikelname = ?");
                    $stmt_lager->execute([$artikel_name]);
                    $lager_artikel = $stmt_lager->fetch();
                    
                    if ($lager_artikel) {
                        $verfuegbar = $lager_artikel['bestand'] - $lager_artikel['reserviert'];
                        
                        if ($verfuegbar >= $menge) {
                            $stmt_res = $PDO->prepare("UPDATE lagerartikel SET reserviert = reserviert + ? WHERE id = ?");
                            $stmt_res->execute([$menge, $lager_artikel['id']]);
                            
                            $stmt_log = $PDO->prepare("INSERT INTO lager_reservierungen (artikel_id, menge, reserviert_fuer, angebot_id, status) VALUES (?, ?, ?, ?, 'reserviert')");
                            $stmt_log->execute([$lager_artikel['id'], $menge, 'Angebot #' . $angebot_id, $angebot_id]);
                        } else {
                            $lager_warnungen[] = "<strong>$artikel_name</strong>: Nur $verfuegbar verfügbar, benötigt: $menge";
                        }
                    }
                }
            }
        }
        
        $stmt = $PDO->prepare("UPDATE bestellungen SET gesamtpreis = ? WHERE idbestellung = ?");
        $stmt->execute([$gesamtsumme, $angebot_id]);
        
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

<style>
.position-row {
    background: var(--bg-card);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    transition: all 0.3s;
}
.position-row:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 16px rgba(0, 217, 192, 0.15);
}
.artikel-container { position: relative; }
.artikel-dropdown {
    position: absolute;
    z-index: 1000;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 10px;
    max-height: 300px;
    overflow-y: auto;
    width: 100%;
    display: none;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    margin-top: 4px;
}
.artikel-option {
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 1px solid var(--border);
    color: var(--text);
}
.artikel-option:last-child { border-bottom: none; }
.artikel-option:hover {
    background: var(--primary-light);
    color: var(--primary);
}
.form-row {
    display: grid;
    grid-template-columns: 2fr 0.5fr 1fr 2fr 0.5fr;
    gap: 1rem;
    align-items: end;
}
@media (max-width: 768px) {
    .form-row { grid-template-columns: 1fr; }
}
.form-label {
    font-weight: 600;
    color: var(--text);
    margin-bottom: 0.5rem;
    display: block;
}
.form-control, .form-select {
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 0.75rem;
    transition: all 0.3s;
    background: var(--bg-card);
    color: var(--text);
}
.form-control:focus, .form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(0, 217, 192, 0.15);
    outline: none;
}
</style>

<div class="dashboard-card">
    <h2 class="section-title mb-4">
        <i class="fas fa-file-invoice me-2"></i><?= $angebot_id ? 'Angebot bearbeiten' : 'Neues Angebot erstellen' ?>
    </h2>

    <?php if ($kunde && !empty($kunde['konditionen'])): ?>
    <!-- Modal für Konditionen-Hinweis -->
    <div class="modal fade show" id="konditionenModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Hinweis: Kunde hat Konditionen</h5>
                </div>
                <div class="modal-body">
                    <p><strong>Gespeicherte Konditionen für diesen Kunden:</strong></p>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($kunde['konditionen'])) ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="document.getElementById('konditionenModal').style.display='none'">
                        <i class="fas fa-check me-2"></i>OK, verstanden
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($kunde): ?>
    <div class="alert alert-info mb-4">
        <h5 class="mb-2"><i class="fas fa-user me-2"></i><?= htmlspecialchars($kunde['vorname'] . ' ' . $kunde['nachname']) ?></h5>
        <p class="mb-1"><strong>Kundennummer:</strong> <?= htmlspecialchars($kunde['kundennummer']) ?></p>
        <p class="mb-0"><strong>Firma:</strong> <?= htmlspecialchars($kunde['firmenname'] ?? 'Keine Firma') ?></p>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="row mb-4">
            <div class="col-md-8">
                <label class="form-label"><i class="fas fa-tag me-1"></i>Bestellungsname</label>
                <input type="text" class="form-control" name="bestellungsname" value="<?= htmlspecialchars($angebot['bestellungsname'] ?? 'Angebot für ' . ($kunde['firmenname'] ?? $kunde['nachname'] ?? '')) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label"><i class="fas fa-clock me-1"></i>Lieferzeit (Tage)</label>
                <input type="number" class="form-control" name="lieferzeit" value="<?= $angebot['lieferzeit'] ?? '14' ?>" min="1" required>
            </div>
        </div>

        <?php if ($kunde): ?>
        <?php if (!empty($konditionen_vorlagen) || !empty($kunde['konditionen'])): ?>
        <div class="row mb-4">
            <div class="col-12">
                <label class="form-label"><i class="fas fa-handshake me-1"></i>Konditionen</label>
                <select class="form-select" name="konditionen" id="konditionen-select">
                    <option value="">-- Keine Konditionen --</option>
                    <?php if (!empty($kunde['konditionen'])): ?>
                    <option value="<?= htmlspecialchars($kunde['konditionen']) ?>" selected><?= htmlspecialchars($kunde['konditionen']) ?></option>
                    <?php endif; ?>
                    <?php foreach($konditionen_vorlagen as $kv): ?>
                    <option value="<?= htmlspecialchars($kv['name']) ?>" <?= ($angebot['konditionen'] ?? '') === $kv['name'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kv['name']) ?>
                        <?php if($kv['rabatt_prozent'] > 0): ?> (<?= $kv['rabatt_prozent'] ?>% Rabatt)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="mt-2">
                    <input type="text" class="form-control" name="konditionen_custom" id="konditionen-custom" placeholder="Oder eigene Konditionen eingeben..." value="<?= ($angebot['konditionen'] ?? '') && !in_array($angebot['konditionen'] ?? '', array_column($konditionen_vorlagen, 'name')) ? htmlspecialchars($angebot['konditionen']) : '' ?>">
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <h4 class="mb-3"><i class="fas fa-box me-2"></i>Positionen</h4>
        <div id="positionen">
            <?php if (!empty($positionen)): ?>
                <?php foreach ($positionen as $position): ?>
            <div class="position-row">
                <div class="form-row">
                    <div>
                        <label class="form-label">Artikel</label>
                        <div class="artikel-container">
                            <input type="text" class="form-control artikel-search" placeholder="Produkt suchen..." value="<?= htmlspecialchars($position['artikel']) ?>" autocomplete="off">
                            <div class="artikel-dropdown">
                                <?php foreach($produkte_list as $prod): ?>
                                    <div class="artikel-option" data-value="<?= htmlspecialchars(trim($prod['name'])) ?>" data-preis="<?= $prod['preis'] ?>" data-beschreibung="<?= htmlspecialchars(trim($prod['beschreibung'])) ?>">
                                        <strong><?= htmlspecialchars(trim($prod['name'])) ?></strong> - <?= number_format($prod['preis'], 2, ',', '.') ?> €
                                    </div>
                                <?php endforeach; ?>
                                <div class="artikel-option" data-value="custom">Benutzerdefiniert...</div>
                            </div>
                        </div>
                        <input type="hidden" class="artikel-value" name="artikel[]" value="<?= htmlspecialchars($position['artikel']) ?>" required>
                        <input type="text" class="form-control artikel-input" name="artikel_custom[]" placeholder="Artikel eingeben" style="display: none; margin-top: 5px;">
                    </div>
                    <div>
                        <label class="form-label">Menge</label>
                        <select class="form-select" name="menge[]" required>
                            <?php for($i = 1; $i <= 100; $i++): ?>
                                <option value="<?= $i ?>" <?= $position['menge'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Einzelpreis (€)</label>
                        <input type="number" class="form-control" name="einzelpreis[]" value="<?= $position['einzelpreis'] ?>" step="0.01" min="0" required>
                    </div>
                    <div>
                        <label class="form-label">Beschreibung</label>
                        <input type="text" class="form-control" name="beschreibung[]" value="<?= htmlspecialchars($position['beschreibung']) ?>">
                    </div>
                    <div>
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-danger w-100 remove-position"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="position-row">
                    <div class="form-row">
                        <div>
                            <label class="form-label">Artikel</label>
                            <div class="artikel-container">
                                <input type="text" class="form-control artikel-search" placeholder="Produkt suchen..." autocomplete="off">
                                <div class="artikel-dropdown">
                                    <?php foreach($produkte_list as $prod): ?>
                                        <div class="artikel-option" data-value="<?= htmlspecialchars(trim($prod['name'])) ?>" data-preis="<?= $prod['preis'] ?>" data-beschreibung="<?= htmlspecialchars(trim($prod['beschreibung'])) ?>">
                                            <strong><?= htmlspecialchars(trim($prod['name'])) ?></strong> - <?= number_format($prod['preis'], 2, ',', '.') ?> €
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="artikel-option" data-value="custom">Benutzerdefiniert...</div>
                                </div>
                            </div>
                            <input type="hidden" class="artikel-value" name="artikel[]" required>
                            <input type="text" class="form-control artikel-input" name="artikel_custom[]" placeholder="Artikel eingeben" style="display: none; margin-top: 5px;">
                        </div>
                        <div>
                            <label class="form-label">Menge</label>
                            <select class="form-select" name="menge[]" required>
                                <?php for($i = 1; $i <= 100; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Einzelpreis (€)</label>
                            <input type="number" class="form-control" name="einzelpreis[]" value="100.00" step="0.01" min="0" required>
                        </div>
                        <div>
                            <label class="form-label">Beschreibung</label>
                            <input type="text" class="form-control" name="beschreibung[]">
                        </div>
                        <div>
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-danger w-100 remove-position"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <button type="button" id="add-position" class="action-btn btn-success-modern mb-4">
            <i class="fas fa-plus me-2"></i>Weitere Position hinzufügen
        </button>

        <div class="d-grid gap-3">
            <button type="submit" class="action-btn btn-primary-modern" style="padding: 1rem; font-size: 1.1rem;">
                <i class="fas fa-check me-2"></i><?= $angebot_id ? 'Angebot aktualisieren' : 'Angebot erstellen' ?>
            </button>
            <a href="<?= isset($_GET['kunde_id']) ? 'kunde_details.php?id=' . $_GET['kunde_id'] : 'web_oberflaeche.php' ?>" class="action-btn" style="background: #6c757d; color: white; text-align: center; padding: 0.75rem; text-decoration: none;">
                <i class="fas fa-arrow-left me-2"></i>Abbrechen
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('artikel-option') || e.target.closest('.artikel-option')) {
            const option = e.target.classList.contains('artikel-option') ? e.target : e.target.closest('.artikel-option');
            const row = option.closest('.position-row');
            const searchInput = row.querySelector('.artikel-search');
            const hiddenInput = row.querySelector('.artikel-value');
            const customInput = row.querySelector('.artikel-input');
            const preisInput = row.querySelector('input[name="einzelpreis[]"]');
            const beschreibungInput = row.querySelector('input[name="beschreibung[]"]');
            const dropdown = option.closest('.artikel-dropdown');
            
            if (option.dataset.value === 'custom') {
                searchInput.value = 'Benutzerdefiniert';
                hiddenInput.value = 'custom';
                customInput.style.display = 'block';
                customInput.required = true;
            } else {
                searchInput.value = option.dataset.value.trim();
                hiddenInput.value = option.dataset.value.trim();
                customInput.style.display = 'none';
                customInput.required = false;
                
                if (option.dataset.preis) {
                    preisInput.value = parseFloat(option.dataset.preis).toFixed(2);
                }
                if (option.dataset.beschreibung) {
                    beschreibungInput.value = option.dataset.beschreibung.trim();
                }
            }
            
            dropdown.style.display = 'none';
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.artikel-container')) {
            document.querySelectorAll('.artikel-dropdown').forEach(dropdown => {
                dropdown.style.display = 'none';
            });
        }
    });
    
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
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-position') || e.target.closest('.remove-position')) {
            if (document.querySelectorAll('.position-row').length > 1) {
                const btn = e.target.classList.contains('remove-position') ? e.target : e.target.closest('.remove-position');
                btn.closest('.position-row').remove();
            }
        }
    });
});
</script>
<?php include 'includes/footer.php'; ?>
