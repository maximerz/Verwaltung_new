<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Erweiterte Tabelle für Lieferscheine
try {
    $PDO->exec("CREATE TABLE IF NOT EXISTS lieferscheine (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lieferschein_nr TEXT UNIQUE NOT NULL,
        bestellung_id INTEGER NOT NULL,
        kundennummer TEXT,
        einsatzart TEXT,
        datum DATE,
        unterschrift_data TEXT,
        unterschrift_name TEXT,
        bemerkung TEXT,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $PDO->exec("CREATE TABLE IF NOT EXISTS lieferschein_techniker (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lieferschein_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        techniker_name TEXT NOT NULL,
        uhrzeit_von TIME,
        uhrzeit_bis TIME
    )");
    
    $PDO->exec("CREATE TABLE IF NOT EXISTS lieferschein_positionen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lieferschein_id INTEGER NOT NULL,
        artikel_id INTEGER,
        artikelname TEXT NOT NULL,
        menge REAL NOT NULL,
        grund_einsatz TEXT,
        durchgefuehrte_arbeiten TEXT,
        bemerkung TEXT
    )");
    
    $PDO->exec("CREATE TABLE IF NOT EXISTS lieferschein_seriennummern (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        position_id INTEGER NOT NULL,
        seriennummer TEXT NOT NULL,
        foto_data TEXT,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

$bestellung_id = $_GET['bestellung_id'] ?? null;
$lieferschein_id = $_GET['lieferschein_id'] ?? null;

// Lieferschein speichern
if (isset($_POST['save_lieferschein'])) {
    try {
        $lieferschein_nr = $_POST['lieferschein_nr'];
        $bestellung_id = $_POST['bestellung_id'];
        $kundennummer = $_POST['kundennummer'];
        $techniker = $_POST['techniker'];
        $einsatzart = $_POST['einsatzart'];
        $datum = $_POST['datum'];
        $uhrzeit_von = $_POST['uhrzeit_von'];
        $uhrzeit_bis = $_POST['uhrzeit_bis'];
        $unterschrift_data = $_POST['unterschrift_data'] ?? '';
        $unterschrift_name = $_POST['unterschrift_name'] ?? '';
        $bemerkung = $_POST['bemerkung'] ?? '';
    
    if ($lieferschein_id) {
        $stmt = $PDO->prepare("UPDATE lieferscheine SET lieferschein_nr=?, einsatzart=?, datum=?, unterschrift_data=?, unterschrift_name=?, bemerkung=? WHERE id=?");
        $stmt->execute([$lieferschein_nr, $einsatzart, $datum, $unterschrift_data, $unterschrift_name, $bemerkung, $lieferschein_id]);
    } else {
        $stmt = $PDO->prepare("INSERT INTO lieferscheine (lieferschein_nr, bestellung_id, kundennummer, einsatzart, datum, unterschrift_data, unterschrift_name, bemerkung) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$lieferschein_nr, $bestellung_id, $kundennummer, $einsatzart, $datum, $unterschrift_data, $unterschrift_name, $bemerkung]);
        $lieferschein_id = $PDO->lastInsertId();
    }
    
    // Techniker speichern
    $stmt = $PDO->prepare("DELETE FROM lieferschein_techniker WHERE lieferschein_id = ?");
    $stmt->execute([$lieferschein_id]);
    
    if (isset($_POST['techniker_ids']) && is_array($_POST['techniker_ids'])) {
        foreach ($_POST['techniker_ids'] as $index => $user_id) {
            if (!empty($user_id)) {
                $stmt = $PDO->prepare("INSERT INTO lieferschein_techniker (lieferschein_id, user_id, techniker_name, uhrzeit_von, uhrzeit_bis) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $lieferschein_id,
                    $user_id,
                    $_POST['techniker_namen'][$index],
                    $_POST['techniker_von'][$index],
                    $_POST['techniker_bis'][$index]
                ]);
            }
        }
    }
    
    // Positionen speichern
    $stmt = $PDO->prepare("DELETE FROM lieferschein_positionen WHERE lieferschein_id = ?");
    $stmt->execute([$lieferschein_id]);
    
    if (isset($_POST['artikel_ids']) && is_array($_POST['artikel_ids'])) {
        foreach ($_POST['artikel_ids'] as $index => $artikel_id) {
            if (!empty($_POST['artikelnamen'][$index])) {
                $stmt = $PDO->prepare("INSERT INTO lieferschein_positionen (lieferschein_id, artikel_id, artikelname, menge, grund_einsatz, durchgefuehrte_arbeiten, bemerkung) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $lieferschein_id,
                    $artikel_id ?: null,
                    $_POST['artikelnamen'][$index],
                    $_POST['mengen'][$index],
                    $_POST['grund_einsatz'][$index] ?? '',
                    $_POST['arbeiten'][$index] ?? '',
                    $_POST['bemerkungen'][$index] ?? ''
                ]);
                
                $position_id = $PDO->lastInsertId();
                
                // Seriennummern speichern
                if (isset($_POST['seriennummern'][$index]) && is_array($_POST['seriennummern'][$index])) {
                    foreach ($_POST['seriennummern'][$index] as $sn_index => $seriennummer) {
                        if (!empty($seriennummer)) {
                            $foto = $_POST['seriennummer_fotos'][$index][$sn_index] ?? '';
                            $stmt = $PDO->prepare("INSERT INTO lieferschein_seriennummern (position_id, seriennummer, foto_data) VALUES (?, ?, ?)");
                            $stmt->execute([$position_id, $seriennummer, $foto]);
                        }
                    }
                }
            }
        }
    }
    
    // Weiterleitung
    // Bestellung auf "erledigt" setzen
    $stmt = $PDO->prepare("UPDATE bestellungen SET status = 'erledigt' WHERE idbestellung = ?");
    $stmt->execute([$bestellung_id]);
    
    header("Location: kunde_details.php?id=" . ($_GET['kunde_id'] ?? '') . "&success=Lieferschein gespeichert");
    exit;
    } catch (Exception $e) {
        die("Fehler beim Speichern: " . $e->getMessage());
    }
}

// Bestellung laden
if ($bestellung_id && !$lieferschein_id) {
    $stmt = $PDO->prepare("SELECT b.*, k.vorname, k.nachname, k.email FROM bestellungen b LEFT JOIN kundensystem k ON b.kundennummer = k.kundennummer WHERE b.idbestellung = ?");
    $stmt->execute([$bestellung_id]);
    $bestellung = $stmt->fetch();
    
    // Positionen aus Angebot laden
    $stmt = $PDO->prepare("SELECT * FROM angebot_positionen WHERE angebot_id = ?");
    $stmt->execute([$bestellung_id]);
    $positionen = $stmt->fetchAll();
}

// Existierenden Lieferschein laden
if ($lieferschein_id) {
    $stmt = $PDO->prepare("SELECT * FROM lieferscheine WHERE id = ?");
    $stmt->execute([$lieferschein_id]);
    $lieferschein = $stmt->fetch();
    
    // Techniker laden
    $stmt = $PDO->prepare("SELECT * FROM lieferschein_techniker WHERE lieferschein_id = ?");
    $stmt->execute([$lieferschein_id]);
    $techniker_liste = $stmt->fetchAll();
    
    $stmt = $PDO->prepare("SELECT * FROM lieferschein_positionen WHERE lieferschein_id = ?");
    $stmt->execute([$lieferschein_id]);
    $positionen = $stmt->fetchAll();
    
    // Seriennummern für jede Position laden
    foreach ($positionen as &$pos) {
        $stmt = $PDO->prepare("SELECT * FROM lieferschein_seriennummern WHERE position_id = ?");
        $stmt->execute([$pos['id']]);
        $pos['seriennummern'] = $stmt->fetchAll();
    }
    
    if ($lieferschein) {
        $bestellung_id = $lieferschein['bestellung_id'];
        $stmt = $PDO->prepare("SELECT b.*, k.vorname, k.nachname FROM bestellungen b LEFT JOIN kundensystem k ON b.kundennummer = k.kundennummer WHERE b.idbestellung = ?");
        $stmt->execute([$bestellung_id]);
        $bestellung = $stmt->fetch();
    }
}

// Alle User für Techniker-Auswahl laden
try {
    $stmt = $PDO->prepare("SELECT id, username, role FROM users ORDER BY username");
    $stmt->execute();
    $alle_user = $stmt->fetchAll();
} catch (Exception $e) {
    $alle_user = [];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#14b8a6">
    <title>Lieferschein erstellen</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/assets/images/logo.webp">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="includes/navigation.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        .container-main {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--bg-card);
            padding: var(--spacing-xl);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
        }

        .section-card {
            background: var(--bg-secondary);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--border);
        }

        .position-card {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .signature-pad {
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            cursor: crosshair;
            background: var(--bg-card);
        }

        .camera-preview {
            max-width: 100%;
            border: 2px dashed var(--border);
            border-radius: var(--radius-md);
            min-height: 200px;
            background: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
        }

        .techniker-item {
            background: var(--bg-secondary);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
        }

        .form-check {
            margin-right: var(--spacing-lg);
        }

        .btn-add {
            background: var(--color-success);
            color: white;
            border: none;
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 500;
        }

        .btn-remove {
            background: var(--color-danger);
            color: white;
            border: none;
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 12px;
        }
    </style>
    <?php include 'includes/table-style.php'; ?>
</head>
<body>
    <div class="container-main">
        <h1>📋 Lieferschein erstellen</h1>
        <a href="web_oberflaeche.php" class="btn btn-secondary mb-4">🏠 Zurück</a>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if ($bestellung): ?>
        <div class="alert alert-warning" data-offline-sync-badge hidden></div>

        <form method="POST" id="lieferscheinForm" data-offline-sync="lieferschein">
            <input type="hidden" name="bestellung_id" value="<?= $bestellung_id ?>">
            <input type="hidden" name="kundennummer" value="<?= $bestellung['kundennummer'] ?>">
            <input type="hidden" name="unterschrift_data" id="unterschrift_data">

            <!-- Kopfdaten -->
            <div class="section-card">
                <h3>📄 Lieferschein-Daten</h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Lieferschein-Nr. *</label>
                        <input type="text" name="lieferschein_nr" class="form-control" value="<?= htmlspecialchars($lieferschein['lieferschein_nr'] ?? 'LS-' . date('Ymd') . '-' . rand(1000, 9999)) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Datum *</label>
                        <input type="date" name="datum" class="form-control" value="<?= $lieferschein['datum'] ?? date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kunde</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($bestellung['vorname'] . ' ' . $bestellung['nachname']) ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Einsatzart & Techniker -->
            <div class="section-card">
                <h3>⏰ Einsatzdetails & Techniker</h3>
                <div class="row g-3 mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Einsatzart *</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="einsatzart" value="Voll" <?= ($lieferschein['einsatzart'] ?? '') === 'Voll' ? 'checked' : '' ?> required>
                                <label class="form-check-label">Voll</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="einsatzart" value="Anteilig" <?= ($lieferschein['einsatzart'] ?? '') === 'Anteilig' ? 'checked' : '' ?>>
                                <label class="form-check-label">Anteilig</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="einsatzart" value="Kurz" <?= ($lieferschein['einsatzart'] ?? '') === 'Kurz' ? 'checked' : '' ?>>
                                <label class="form-check-label">Kurz</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="einsatzart" value="Notfall" <?= ($lieferschein['einsatzart'] ?? '') === 'Notfall' ? 'checked' : '' ?>>
                                <label class="form-check-label">Notfall</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h5>Techniker</h5>
                <div id="techniker-container">
                    <?php 
                    if (!isset($techniker_liste) || empty($techniker_liste)) {
                        $techniker_liste = [['user_id' => '', 'techniker_name' => '', 'uhrzeit_von' => '', 'uhrzeit_bis' => '']];
                    }
                    foreach ($techniker_liste as $tech_index => $tech): 
                    ?>
                    <div class="techniker-item mb-3" style="background: var(--bg-secondary); padding: 15px; border-radius: 8px; border: 1px solid var(--border);">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Techniker *</label>
                                <select name="techniker_ids[]" class="form-select techniker-select" required onchange="updateTechnikerName(this)">
                                    <option value="">-- Techniker wählen --</option>
                                    <?php if (!empty($alle_user)): ?>
                                        <?php foreach ($alle_user as $user): ?>
                                            <option value="<?= $user['id'] ?>" data-name="<?= htmlspecialchars($user['username']) ?>" <?= ($tech['user_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($user['username']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>Keine User gefunden</option>
                                    <?php endif; ?>
                                </select>
                                <input type="hidden" name="techniker_namen[]" class="techniker-name" value="<?= htmlspecialchars($tech['techniker_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Von *</label>
                                <input type="time" name="techniker_von[]" class="form-control" value="<?= $tech['uhrzeit_von'] ?? '' ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Bis *</label>
                                <input type="time" name="techniker_bis[]" class="form-control" value="<?= $tech['uhrzeit_bis'] ?? '' ?>" required>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-danger w-100" onclick="removeTechniker(this)">🗑️</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-success" onclick="addTechniker()">➕ Techniker hinzufügen</button>
            </div>

            <!-- Positionen -->
            <div class="section-card">
                <h3>📦 Artikel & Arbeiten</h3>
                <div id="positionen-container">
                    <?php if (!empty($positionen)): ?>
                        <?php foreach ($positionen as $index => $pos): ?>
                        <div class="position-card">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Position <?= $index + 1 ?></h5>
                                <button type="button" class="btn btn-sm btn-danger" onclick="removePosition(this)">🗑️</button>
                            </div>
                            <div class="row g-3">
                                <input type="hidden" name="artikel_ids[]" value="<?= $pos['id'] ?? '' ?>">
                                <div class="col-md-6">
                                    <label class="form-label">Artikel *</label>
                                    <input type="text" name="artikelnamen[]" class="form-control" value="<?= htmlspecialchars($pos['artikel'] ?? $pos['artikelname'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Menge *</label>
                                    <input type="number" name="mengen[]" class="form-control" step="0.01" value="<?= $pos['menge'] ?? 1 ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-success w-100" onclick="addSeriennummer(this)">➕ Seriennummer</button>
                                </div>
                                
                                <!-- Seriennummern Container -->
                                <div class="col-12">
                                    <div class="seriennummern-container" style="background: var(--bg-secondary); padding: 15px; border-radius: 8px; border: 1px solid var(--border);">
                                        <h6>Seriennummern:</h6>
                                        <div class="seriennummern-list">
                                            <?php 
                                            $seriennummern = $pos['seriennummern'] ?? [];
                                            if (empty($seriennummern)) {
                                                $seriennummern = [['seriennummer' => '', 'foto_data' => '']];
                                            }
                                            foreach ($seriennummern as $sn): 
                                            ?>
                                            <div class="seriennummer-item mb-2">
                                                <div class="row g-2">
                                                    <div class="col-md-6">
                                                        <input type="text" name="seriennummern[<?= $index ?>][]" class="form-control" placeholder="Seriennummer" value="<?= htmlspecialchars($sn['seriennummer'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="hidden" name="seriennummer_fotos[<?= $index ?>][]" class="sn-foto-data" value="<?= htmlspecialchars($sn['foto_data'] ?? '') ?>">
                                                        <button type="button" class="btn btn-sm btn-primary" onclick="openCameraForSN(this)">📷 Foto</button>
                                                        <span class="sn-photo-status <?= !empty($sn['foto_data']) ? 'text-success' : 'text-muted' ?>"><?= !empty($sn['foto_data']) ? '✓ Foto vorhanden' : '' ?></span>
                                                        <div class="sn-ocr-status small text-muted"></div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeSN(this)">🗑️</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Grund für Einsatz</label>
                                    <input type="text" name="grund_einsatz[]" class="form-control" value="<?= htmlspecialchars($pos['grund_einsatz'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Durchgeführte Arbeiten</label>
                                    <input type="text" name="arbeiten[]" class="form-control" value="<?= htmlspecialchars($pos['durchgefuehrte_arbeiten'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Bemerkung</label>
                                    <textarea name="bemerkungen[]" class="form-control" rows="2"><?= htmlspecialchars($pos['bemerkung'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">Keine Artikel gefunden. Bitte fügen Sie manuell Positionen hinzu.</div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-success" onclick="addPosition()">➕ Position hinzufügen</button>
            </div>

            <!-- Unterschrift -->
            <div class="section-card">
                <h3>✍️ Unterschrift Kunde</h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name des Unterzeichners *</label>
                        <input type="text" name="unterschrift_name" class="form-control" value="<?= htmlspecialchars($lieferschein['unterschrift_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Unterschrift *</label>
                        <canvas id="signature-pad" class="signature-pad" width="800" height="200"></canvas>
                        <div class="mt-2">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="clearSignature()">🗑️ Löschen</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bemerkung -->
            <div class="section-card">
                <label class="form-label">Allgemeine Bemerkung</label>
                <textarea name="bemerkung" class="form-control" rows="3"><?= htmlspecialchars($lieferschein['bemerkung'] ?? '') ?></textarea>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" name="save_lieferschein" class="btn btn-primary btn-lg">💾 Lieferschein speichern</button>
                <?php if ($lieferschein_id): ?>
                    <a href="lieferschein_pdf.php?id=<?= $lieferschein_id ?>" class="btn btn-success btn-lg" target="_blank">🖨️ Lieferschein drucken</a>
                <?php endif; ?>
                <a href="web_oberflaeche.php" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-warning">Keine Bestellung gefunden!</div>
        <?php endif; ?>
    </div>

    <input type="file" id="serial-photo-input" accept="image/*" capture="environment" style="display:none;">

    <!-- Kamera Modal -->
    <div class="modal fade" id="cameraModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">📷 Seriennummer fotografieren</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <video id="camera-stream" class="camera-preview w-100" autoplay></video>
                    <canvas id="camera-canvas" style="display:none;"></canvas>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-primary" onclick="capturePhoto()">📸 Foto aufnehmen</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script src="/assets/js/pwa.js"></script>
    <script src="/assets/js/offline-sync.js"></script>
    <script>
        let positionCounter = <?= count($positionen ?? []) ?>;
        let currentPhotoInput = null;
        let cameraStream = null;
        const cameraModal = new bootstrap.Modal(document.getElementById('cameraModal'));
        
        // Alle User für Techniker-Dropdown
        const alleUser = <?= !empty($alle_user) ? json_encode($alle_user) : '[]' ?>;
        
        // Select2 initialisieren
        $(document).ready(function() {
            initSelect2();
        });
        
        function initSelect2() {
            $('.techniker-select').select2({
                theme: 'bootstrap-5',
                placeholder: '-- Techniker wählen --',
                allowClear: true,
                width: '100%'
            });
        }

        // Signature Pad
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas);

        function clearSignature() {
            signaturePad.clear();
        }

        document.getElementById('lieferscheinForm').addEventListener('submit', function(e) {
            if (!signaturePad.isEmpty()) {
                document.getElementById('unterschrift_data').value = signaturePad.toDataURL();
            }
        });
        
        // Techniker
        function addTechniker() {
            const container = document.getElementById('techniker-container');
            const div = document.createElement('div');
            div.className = 'techniker-item mb-3';
            div.style.cssText = 'background: var(--bg-secondary); padding: 15px; border-radius: 8px; border: 1px solid var(--border);';
            
            let optionsHTML = '<option value="">-- Techniker wählen --</option>';
            alleUser.forEach(user => {
                optionsHTML += `<option value="${user.id}" data-name="${user.username}">${user.username}</option>`;
            });
            
            div.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Techniker *</label>
                        <select name="techniker_ids[]" class="form-select techniker-select" required onchange="updateTechnikerName(this)">
                            ${optionsHTML}
                        </select>
                        <input type="hidden" name="techniker_namen[]" class="techniker-name">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Von *</label>
                        <input type="time" name="techniker_von[]" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bis *</label>
                        <input type="time" name="techniker_bis[]" class="form-control" required>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-danger w-100" onclick="removeTechniker(this)">🗑️</button>
                    </div>
                </div>
            `;
            container.appendChild(div);
            
            // Select2 für neues Dropdown initialisieren
            $(div).find('.techniker-select').select2({
                theme: 'bootstrap-5',
                placeholder: '-- Techniker wählen --',
                allowClear: true,
                width: '100%'
            });
        }
        
        function removeTechniker(btn) {
            const container = document.getElementById('techniker-container');
            if (container.querySelectorAll('.techniker-item').length > 1) {
                btn.closest('.techniker-item').remove();
            } else {
                alert('Mindestens ein Techniker muss vorhanden sein!');
            }
        }
        
        function updateTechnikerName(select) {
            const selectedOption = select.options[select.selectedIndex];
            const nameInput = select.parentElement.querySelector('.techniker-name');
            if (selectedOption && selectedOption.dataset.name) {
                nameInput.value = selectedOption.dataset.name;
            }
        }

        // Positionen
        function addPosition() {
            positionCounter++;
            const container = document.getElementById('positionen-container');
            const div = document.createElement('div');
            div.className = 'position-card';
            div.innerHTML = `
                <div class="d-flex justify-content-between mb-3">
                    <h5>Position ${positionCounter}</h5>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removePosition(this)">🗑️</button>
                </div>
                <div class="row g-3">
                    <input type="hidden" name="artikel_ids[]" value="">
                    <div class="col-md-6">
                        <label class="form-label">Artikel *</label>
                        <input type="text" name="artikelnamen[]" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Menge *</label>
                        <input type="number" name="mengen[]" class="form-control" step="0.01" value="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-success w-100" onclick="addSeriennummer(this)">➕ Seriennummer</button>
                    </div>
                    <div class="col-12">
                        <div class="seriennummern-container" style="background: var(--bg-secondary); padding: 15px; border-radius: 8px; border: 1px solid var(--border);">
                            <h6>Seriennummern:</h6>
                            <div class="seriennummern-list">
                                <div class="seriennummer-item mb-2">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <input type="text" name="seriennummern[${positionCounter-1}][]" class="form-control" placeholder="Seriennummer">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="hidden" name="seriennummer_fotos[${positionCounter-1}][]" class="sn-foto-data">
                                            <button type="button" class="btn btn-sm btn-primary" onclick="openCameraForSN(this)">📷 Foto</button>
                                            <span class="sn-photo-status text-muted"></span>
                                            <div class="sn-ocr-status small text-muted"></div>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeSN(this)">🗑️</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Grund für Einsatz</label>
                        <input type="text" name="grund_einsatz[]" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Durchgeführte Arbeiten</label>
                        <input type="text" name="arbeiten[]" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Bemerkung</label>
                        <textarea name="bemerkungen[]" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            `;
            container.appendChild(div);
        }

        function removePosition(btn) {
            if (confirm('Position wirklich entfernen?')) {
                btn.closest('.position-card').remove();
            }
        }
        
        function addSeriennummer(btn) {
            const posCard = btn.closest('.position-card');
            const snList = posCard.querySelector('.seriennummern-list');
            const posIndex = Array.from(document.querySelectorAll('.position-card')).indexOf(posCard);
            
            const div = document.createElement('div');
            div.className = 'seriennummer-item mb-2';
            div.innerHTML = `
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" name="seriennummern[${posIndex}][]" class="form-control" placeholder="Seriennummer">
                    </div>
                    <div class="col-md-4">
                        <input type="hidden" name="seriennummer_fotos[${posIndex}][]" class="sn-foto-data">
                        <button type="button" class="btn btn-sm btn-primary" onclick="openCameraForSN(this)">📷 Foto</button>
                        <span class="sn-photo-status text-muted"></span>
                        <div class="sn-ocr-status small text-muted"></div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeSN(this)">🗑️</button>
                    </div>
                </div>
            `;
            snList.appendChild(div);
        }
        
        function removeSN(btn) {
            const snList = btn.closest('.seriennummern-list');
            if (snList.querySelectorAll('.seriennummer-item').length > 1) {
                btn.closest('.seriennummer-item').remove();
            } else {
                alert('Mindestens eine Seriennummer muss vorhanden sein!');
            }
        }

        // Kamera
        function openCameraForSN(btn) {
            currentPhotoInput = btn.parentElement.querySelector('.sn-foto-data');
            const fileInput = document.getElementById('serial-photo-input');

            if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                fileInput.click();
                return;
            }

            cameraModal.show();
            
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                .then(stream => {
                    cameraStream = stream;
                    document.getElementById('camera-stream').srcObject = stream;
                })
                .catch(() => {
                    cameraModal.hide();
                    fileInput.click();
                });
        }

        function storeSerialPhoto(photoData) {
            if (!currentPhotoInput || !photoData) {
                return;
            }

            currentPhotoInput.value = photoData;

            const status = currentPhotoInput.parentElement.querySelector('.sn-photo-status');
            if (status) {
                status.className = 'sn-photo-status text-success';
                status.textContent = '✓ Foto vorhanden';
            }

            extractSerialNumberFromPhoto(photoData);
        }

        function getCurrentSerialNumberInput() {
            const item = currentPhotoInput ? currentPhotoInput.closest('.seriennummer-item') : null;
            return item ? item.querySelector('input[name^="seriennummern"]') : null;
        }

        function getCurrentOcrStatus() {
            const item = currentPhotoInput ? currentPhotoInput.closest('.seriennummer-item') : null;
            return item ? item.querySelector('.sn-ocr-status') : null;
        }

        function normalizeOcrToken(token) {
            return token
                .replace(/[|]/g, 'I')
                .replace(/[^\w./-]/g, '')
                .replace(/^[.:/\-]+/, '')
                .replace(/[.:/\-]+$/, '')
                .trim();
        }

        function findSerialNumberCandidate(text) {
            const normalizedText = text
                .replace(/[|]/g, 'I')
                .replace(/[：]/g, ':')
                .replace(/\r?\n/g, ' ')
                .replace(/\s+/g, ' ');

            const labelPattern = /(?:\bS\s*[\/.-]?\s*N\b|\b5\s*[\/.-]?\s*N\b|\bSerien\s*(?:nummer|nr\.?|no\.?)\b|\bSerial\s*(?:number|no\.?)?\b)\s*[:#\-]?\s*/ig;
            const matches = Array.from(normalizedText.matchAll(labelPattern));

            for (const labelMatch of matches) {
                const afterLabel = normalizedText.slice(labelMatch.index + labelMatch[0].length);
                const valueMatch = afterLabel.match(/([A-Z0-9][A-Z0-9._/-]{3,39})/i);
                if (!valueMatch) {
                    continue;
                }

                const candidate = normalizeOcrToken(valueMatch[1]);
                if (candidate.length >= 4 && candidate.length <= 40 && /\d/.test(candidate)) {
                    return candidate;
                }
            }

            return '';
        }

        async function extractSerialNumberFromPhoto(photoData) {
            const serialInput = getCurrentSerialNumberInput();
            const ocrStatus = getCurrentOcrStatus();

            if (!serialInput || !window.Tesseract) {
                if (ocrStatus) {
                    ocrStatus.textContent = 'Texterkennung nicht verfügbar';
                }
                return;
            }

            if (ocrStatus) {
                ocrStatus.className = 'sn-ocr-status small text-muted';
                ocrStatus.textContent = 'Seriennummer wird erkannt...';
            }

            try {
                const result = await Tesseract.recognize(photoData, 'eng');
                const candidate = findSerialNumberCandidate(result.data.text || '');

                if (!candidate) {
                    if (ocrStatus) {
                        ocrStatus.className = 'sn-ocr-status small text-warning';
                        ocrStatus.textContent = 'Keine Seriennummer neben SN/Seriennummer erkannt';
                    }
                    return;
                }

                if (!serialInput.value.trim() || confirm(`Seriennummer "${candidate}" übernehmen?`)) {
                    serialInput.value = candidate;
                }

                if (ocrStatus) {
                    ocrStatus.className = 'sn-ocr-status small text-success';
                    ocrStatus.textContent = `Erkannt: ${candidate}`;
                }
            } catch (error) {
                if (ocrStatus) {
                    ocrStatus.className = 'sn-ocr-status small text-warning';
                    ocrStatus.textContent = 'Texterkennung fehlgeschlagen';
                }
            }
        }

        function capturePhoto() {
            const video = document.getElementById('camera-stream');
            const canvas = document.getElementById('camera-canvas');
            if (!video.videoWidth || !video.videoHeight) {
                alert('Noch kein Kamerabild verfügbar. Bitte kurz warten und erneut versuchen.');
                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            
            const photoData = canvas.toDataURL('image/jpeg');
            storeSerialPhoto(photoData);
            
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
            }
            cameraModal.hide();
            alert('Foto aufgenommen!');
        }

        document.getElementById('serial-photo-input').addEventListener('change', function() {
            const file = this.files && this.files[0];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function(event) {
                const image = new Image();
                image.onload = function() {
                    const maxSize = 1600;
                    let width = image.width;
                    let height = image.height;

                    if (width > height && width > maxSize) {
                        height = Math.round(height * maxSize / width);
                        width = maxSize;
                    } else if (height > maxSize) {
                        width = Math.round(width * maxSize / height);
                        height = maxSize;
                    }

                    const canvas = document.getElementById('camera-canvas');
                    canvas.width = width;
                    canvas.height = height;
                    canvas.getContext('2d').drawImage(image, 0, 0, width, height);

                    storeSerialPhoto(canvas.toDataURL('image/jpeg', 0.82));
                    alert('Foto aufgenommen!');
                };
                image.src = event.target.result;
            };
            reader.readAsDataURL(file);
            this.value = '';
        });

        document.getElementById('cameraModal').addEventListener('hidden.bs.modal', function() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</body>
</html>
