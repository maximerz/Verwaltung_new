<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$kunde_id = $_GET['id'] ?? '';
if (!$kunde_id) {
    header('Location: kunden_verwaltung.php');
    exit;
}

// Notiz hinzufügen
if (isset($_POST['action']) && $_POST['action'] === 'add_note' && !empty($_POST['notiz'])) {
    $stmt = $PDO->prepare("INSERT INTO kundennotizen (kunde_id, notiz) VALUES (?, ?)");
    $stmt->execute([$kunde_id, $_POST['notiz']]);
    $success = "Notiz erfolgreich hinzugefügt!";
}

// Lieferschein Upload
if (isset($_POST['action']) && $_POST['action'] === 'upload' && isset($_FILES['lieferschein'])) {
    $upload_dir = 'uploads/lieferscheine/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file = $_FILES['lieferschein'];
    $filename = time() . '_' . $file['name'];
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $stmt = $PDO->prepare("INSERT INTO lieferscheine (kunde_id, dateiname, original_name) VALUES (?, ?, ?)");
        $stmt->execute([$kunde_id, $filename, $file['name']]);
        $success = "Lieferschein erfolgreich hochgeladen!";
    } else {
        $error = "Fehler beim Hochladen!";
    }
}

// Kundendaten laden
$stmt = $PDO->prepare("
    SELECT k.*, f.firmenname, f.strasse, f.ort 
    FROM kundensystem k 
    LEFT JOIN firma f ON k.firma_id = f.id 
    WHERE k.id = ?
");
$stmt->execute([$kunde_id]);
$kunde = $stmt->fetch();

if (!$kunde) {
    header('Location: kunden_verwaltung.php');
    exit;
}

// Angebote/Bestellungen laden - nach Status getrennt
$stmt = $PDO->prepare("SELECT * FROM bestellungen WHERE kundennummer = ? AND status = 'angebot' ORDER BY idbestellung DESC");
$stmt->execute([$kunde['kundennummer']]);
$angebote = $stmt->fetchAll();

$stmt = $PDO->prepare("SELECT * FROM bestellungen WHERE kundennummer = ? AND status = 'bestaetigt' ORDER BY idbestellung DESC");
$stmt->execute([$kunde['kundennummer']]);
$bestellungen = $stmt->fetchAll();

$stmt = $PDO->prepare("SELECT * FROM bestellungen WHERE kundennummer = ? AND status = 'erledigt' ORDER BY idbestellung DESC");
$stmt->execute([$kunde['kundennummer']]);
$erledigte = $stmt->fetchAll();

// Lieferscheine laden - verwende kundennummer statt kunde_id
try {
    $stmt = $PDO->prepare("SELECT * FROM lieferscheine WHERE kundennummer = ? ORDER BY datum DESC");
    $stmt->execute([$kunde['kundennummer']]);
    $lieferscheine_db = $stmt->fetchAll();
} catch (Exception $e) {
    $lieferscheine_db = [];
}

// Hochgeladene Dokumente
try {
    $stmt = $PDO->prepare("SELECT * FROM lieferschein_uploads WHERE kunde_id = ? ORDER BY upload_datum DESC");
    $stmt->execute([$kunde_id]);
    $lieferscheine = $stmt->fetchAll();
} catch (Exception $e) {
    $lieferscheine = [];
}

// Rechnungen laden
try {
    $stmt = $PDO->prepare("SELECT * FROM rechnungen WHERE kunde_id = ? ORDER BY rechnungsdatum DESC");
    $stmt->execute([$kunde['kundennummer']]);
    $rechnungen = $stmt->fetchAll();
} catch (Exception $e) {
    $rechnungen = [];
}

// Notizen laden
try {
    $stmt = $PDO->prepare("SELECT * FROM kundennotizen WHERE kunde_id = ? ORDER BY erstellt_am DESC");
    $stmt->execute([$kunde_id]);
    $notizen = $stmt->fetchAll();
} catch (Exception $e) {
    $notizen = [];
}
?>
<?php $page_title = 'Kundendetails'; include 'includes/header.php'; ?>
<?php include 'includes/table-style.php'; ?>

<div class="dashboard-card">
    <h2 class="section-title mb-4">
        <i class="fas fa-user me-2"></i>Kundendetails
    </h2>

    <div class="mb-4">
        <a href="kunden_verwaltung.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Zurück zur Kundenliste</a>
        <a href="angebot_formular.php?kunde_id=<?= $kunde_id ?>" class="btn btn-success"><i class="fas fa-file-invoice me-2"></i>Neues Angebot</a>
        <a href="delete_customer.php?id=<?= $kunde_id ?>" class="btn btn-danger" onclick="return confirm('Kunde wirklich löschen?')"><i class="fas fa-trash me-2"></i>Kunde löschen</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success mb-4"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger mb-4"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['lager_warnungen']) && !empty($_SESSION['lager_warnungen'])): ?>
        <div class="alert alert-warning mb-4">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Lager-Hinweise</h5>
            <p>Das Angebot wurde erstellt, aber folgende Artikel müssen nachbestellt werden:</p>
            <ul>
                <?php foreach ($_SESSION['lager_warnungen'] as $warnung): ?>
                    <li><?= $warnung ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="lagerverwaltung.php" class="btn btn-sm btn-warning mt-2">
                <i class="fas fa-warehouse me-1"></i>Zur Lagerverwaltung
            </a>
        </div>
        <?php unset($_SESSION['lager_warnungen']); ?>
    <?php endif; ?>

    <div class="alert alert-info mb-4">
        <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Kundendaten</h5>
        <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($kunde['vorname'] . ' ' . $kunde['nachname']) ?></p>
        <p class="mb-1"><strong>E-Mail:</strong> <?= htmlspecialchars($kunde['email']) ?></p>
        <p class="mb-1"><strong>Kundennummer:</strong> <?= htmlspecialchars($kunde['kundennummer']) ?></p>
        <p class="mb-1"><strong>Firma:</strong> <?= htmlspecialchars($kunde['firmenname'] ?? 'Privatkunde') ?></p>
        <?php if ($kunde['strasse'] || $kunde['ort']): ?>
            <p class="mb-0"><strong>Adresse:</strong> <?= htmlspecialchars(($kunde['strasse'] ?? '') . ', ' . ($kunde['ort'] ?? '')) ?></p>
        <?php endif; ?>
    </div>

    <h4 class="mb-3"><i class="fas fa-file-invoice me-2"></i>Angebote & Bestellungen</h4>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#angebote-tab" type="button">
                <i class="fas fa-file-invoice me-1"></i>Angebote (<?= count($angebote) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#bestellungen-tab" type="button">
                <i class="fas fa-shopping-cart me-1"></i>Bestellungen (<?= count($bestellungen) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#erledigte-tab" type="button">
                <i class="fas fa-check-circle me-1"></i>Erledigt (<?= count($erledigte) ?>)
            </button>
        </li>
    </ul>
    
    <div class="tab-content">
        <!-- Angebote Tab -->
        <div class="tab-pane fade show active" id="angebote-tab">
            <?php if ($angebote): ?>
                <div class="modern-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Angebotsnummer</th>
                                <th>Bestellungsname</th>
                                <th>Gesamtpreis</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($angebote as $angebot): ?>
                            <tr>
                                <td><?= htmlspecialchars($angebot['angebotsnummer']) ?></td>
                                <td><?= htmlspecialchars($angebot['bestellungsname']) ?></td>
                                <td><?= number_format($angebot['gesamtpreis'], 2, ',', '.') ?> €</td>
                                <td>
                                    <a href="generate_angebot_pdf.php?order_id=<?= $angebot['idbestellung'] ?>" class="action-btn btn-primary-modern" target="_blank"><i class="fas fa-file-pdf me-1"></i>PDF</a>
                                    <a href="angebot_formular.php?angebot_id=<?= $angebot['idbestellung'] ?>&kunde_id=<?= $kunde_id ?>" class="action-btn btn-warning-modern"><i class="fas fa-edit me-1"></i>Bearbeiten</a>
                                    <a href="confirm_angebot.php?order_id=<?= $angebot['idbestellung'] ?>" class="action-btn btn-success-modern"><i class="fas fa-check me-1"></i>Bestätigen</a>
                                    <a href="delete_order.php?order_id=<?= $angebot['idbestellung'] ?>&redirect=kunde_details.php?id=<?= $kunde_id ?>" class="action-btn btn-danger-modern" onclick="return confirm('Angebot wirklich löschen?')"><i class="fas fa-trash me-1"></i>Löschen</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Keine Angebote vorhanden.</p>
            <?php endif; ?>
        </div>
        
        <!-- Bestellungen Tab -->
        <div class="tab-pane fade" id="bestellungen-tab">
            <?php if ($bestellungen): ?>
                <div class="modern-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Bestellnummer</th>
                                <th>Bestellungsname</th>
                                <th>Gesamtpreis</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bestellungen as $best): ?>
                            <tr>
                                <td><?= htmlspecialchars($best['bestellungsnummer']) ?></td>
                                <td><?= htmlspecialchars($best['bestellungsname']) ?></td>
                                <td><?= number_format($best['gesamtpreis'], 2, ',', '.') ?> €</td>
                                <td>
                                    <a href="generate_pdf.php?order_id=<?= $best['idbestellung'] ?>" class="action-btn btn-primary-modern" target="_blank"><i class="fas fa-file-pdf me-1"></i>PDF</a>
                                    <a href="lieferschein_editor.php?bestellung_id=<?= $best['idbestellung'] ?>&kunde_id=<?= $kunde_id ?>" class="action-btn btn-info"><i class="fas fa-truck me-1"></i>Lieferschein</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Keine offenen Bestellungen vorhanden.</p>
            <?php endif; ?>
        </div>
        
        <!-- Erledigte Tab -->
        <div class="tab-pane fade" id="erledigte-tab">
            <?php if ($erledigte): ?>
                <div class="modern-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Bestellnummer</th>
                                <th>Bestellungsname</th>
                                <th>Gesamtpreis</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($erledigte as $erl): ?>
                            <tr>
                                <td><?= htmlspecialchars($erl['bestellungsnummer']) ?></td>
                                <td><?= htmlspecialchars($erl['bestellungsname']) ?></td>
                                <td><?= number_format($erl['gesamtpreis'], 2, ',', '.') ?> €</td>
                                <td>
                                    <a href="generate_pdf.php?order_id=<?= $erl['idbestellung'] ?>" class="action-btn btn-primary-modern" target="_blank"><i class="fas fa-file-pdf me-1"></i>Bestätigung</a>
                                    <?php
                                    $stmt_ls = $PDO->prepare("SELECT id, lieferschein_nr FROM lieferscheine WHERE bestellung_id = ? ORDER BY erstellt_am DESC LIMIT 1");
                                    $stmt_ls->execute([$erl['idbestellung']]);
                                    $lieferschein = $stmt_ls->fetch();
                                    if ($lieferschein):
                                    ?>
                                        <a href="lieferschein_pdf.php?id=<?= $lieferschein['id'] ?>" class="action-btn btn-success-modern" target="_blank" title="<?= htmlspecialchars($lieferschein['lieferschein_nr']) ?>">
                                            <i class="fas fa-file-alt me-1"></i>Lieferschein PDF
                                        </a>
                                        <a href="lieferschein_editor.php?lieferschein_id=<?= $lieferschein['id'] ?>&kunde_id=<?= $kunde_id ?>" class="action-btn btn-warning-modern">
                                            <i class="fas fa-edit me-1"></i>LS bearbeiten
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Keine erledigten Bestellungen vorhanden.</p>
            <?php endif; ?>
        </div>
    </div>
</div>



<?php include 'includes/footer.php'; ?>