<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Rechnungen erstellen
if ($_POST['action'] === 'create_rechnung' && isset($_POST['bestellung_id'])) {
    $bestellung_id = $_POST['bestellung_id'];
    $stmt = $PDO->prepare("SELECT * FROM bestellungen WHERE idbestellung = ?");
    $stmt->execute([$bestellung_id]);
    $bestellung = $stmt->fetch();
    
    if ($bestellung && $bestellung['status'] === 'bestaetigt') {
        $rechnungsnummer = 'RE' . date('Y') . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $netto = $bestellung['gesamtpreis'];
        $mwst = $netto * 0.19;
        $brutto = $netto + $mwst;
        
        $stmt = $PDO->prepare("INSERT INTO rechnungen (rechnungsnummer, bestellung_id, kunde_id, rechnungsdatum, faelligkeitsdatum, nettobetrag, mwst_betrag, bruttobetrag) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$rechnungsnummer, $bestellung_id, $bestellung['kundennummer'], date('Y-m-d'), date('Y-m-d', strtotime('+30 days')), $netto, $mwst, $brutto]);
        
        // Buchung erstellen
        $stmt = $PDO->prepare("INSERT INTO buchungen (buchungsdatum, belegnummer, konto_soll, konto_haben, betrag, beschreibung, kategorie) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([date('Y-m-d'), $rechnungsnummer, '1400', '3400', $netto, 'Warenverkauf', 'Umsatz']);
        $stmt->execute([date('Y-m-d'), $rechnungsnummer, '1400', '3800', $mwst, 'Umsatzsteuer', 'Steuer']);
        
        $success = "Rechnung $rechnungsnummer erstellt!";
    }
}

// Zahlung buchen
if ($_POST['action'] === 'zahlung_buchen' && isset($_POST['rechnung_id'])) {
    $rechnung_id = $_POST['rechnung_id'];
    $stmt = $PDO->prepare("UPDATE rechnungen SET status = 'bezahlt', zahlungsdatum = ? WHERE id = ?");
    $stmt->execute([date('Y-m-d'), $rechnung_id]);
    
    $stmt = $PDO->prepare("SELECT * FROM rechnungen WHERE id = ?");
    $stmt->execute([$rechnung_id]);
    $rechnung = $stmt->fetch();
    
    // Zahlungseingang buchen
    $stmt = $PDO->prepare("INSERT INTO buchungen (buchungsdatum, belegnummer, konto_soll, konto_haben, betrag, beschreibung, kategorie) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([date('Y-m-d'), $rechnung['rechnungsnummer'], '1200', '1400', $rechnung['bruttobetrag'], 'Zahlungseingang', 'Zahlung']);
    
    $success = "Zahlung gebucht!";
}

// Konto hinzufügen/bearbeiten
if ($_POST['action'] === 'save_konto') {
    $konto_id = $_POST['konto_id'] ?? null;
    $kontonummer = $_POST['kontonummer'];
    $kontoname = $_POST['kontoname'];
    $kontotyp = $_POST['kontotyp'];
    $aktiv = isset($_POST['aktiv']) ? 1 : 0;
    
    if ($konto_id) {
        $stmt = $PDO->prepare("UPDATE kontenplan SET kontonummer = ?, kontoname = ?, kontotyp = ?, aktiv = ? WHERE id = ?");
        $stmt->execute([$kontonummer, $kontoname, $kontotyp, $aktiv, $konto_id]);
        $success = "Konto aktualisiert!";
    } else {
        $stmt = $PDO->prepare("INSERT INTO kontenplan (kontonummer, kontoname, kontotyp, aktiv) VALUES (?, ?, ?, ?)");
        $stmt->execute([$kontonummer, $kontoname, $kontotyp, $aktiv]);
        $success = "Konto hinzugefügt!";
    }
}

// Konto löschen
if (isset($_GET['delete_konto'])) {
    $stmt = $PDO->prepare("DELETE FROM kontenplan WHERE id = ?");
    $stmt->execute([$_GET['delete_konto']]);
    $success = "Konto gelöscht!";
}

// Statistiken
$stmt = $PDO->prepare("SELECT SUM(bruttobetrag) as gesamt_rechnungen FROM rechnungen");
$stmt->execute();
$gesamt_rechnungen = $stmt->fetch()['gesamt_rechnungen'] ?? 0;

$stmt = $PDO->prepare("SELECT SUM(bruttobetrag) as offene_rechnungen FROM rechnungen WHERE status = 'offen'");
$stmt->execute();
$offene_rechnungen = $stmt->fetch()['offene_rechnungen'] ?? 0;

$stmt = $PDO->prepare("SELECT SUM(bruttobetrag) as bezahlte_rechnungen FROM rechnungen WHERE status = 'bezahlt'");
$stmt->execute();
$bezahlte_rechnungen = $stmt->fetch()['bezahlte_rechnungen'] ?? 0;

// Offene Bestellungen für Rechnungserstellung
$stmt = $PDO->prepare("SELECT b.*, k.vorname, k.nachname, f.firmenname FROM bestellungen b LEFT JOIN kundensystem k ON b.kundennummer = k.kundennummer LEFT JOIN firma f ON k.firma_id = f.id WHERE b.status = 'bestaetigt' AND b.idbestellung NOT IN (SELECT bestellung_id FROM rechnungen WHERE bestellung_id IS NOT NULL)");
$stmt->execute();
$offene_bestellungen = $stmt->fetchAll();

// Rechnungen
$stmt = $PDO->prepare("SELECT r.*, k.vorname, k.nachname, f.firmenname FROM rechnungen r LEFT JOIN kundensystem k ON r.kunde_id = k.kundennummer LEFT JOIN firma f ON k.firma_id = f.id ORDER BY r.rechnungsdatum DESC");
$stmt->execute();
$rechnungen = $stmt->fetchAll();

// Buchungen
$stmt = $PDO->prepare("SELECT b.*, ks.kontoname as soll_name, kh.kontoname as haben_name FROM buchungen b LEFT JOIN kontenplan ks ON b.konto_soll = ks.kontonummer LEFT JOIN kontenplan kh ON b.konto_haben = kh.kontonummer ORDER BY b.buchungsdatum DESC LIMIT 20");
$stmt->execute();
$buchungen = $stmt->fetchAll();

// Konto zum Bearbeiten laden
$edit_konto = null;
if (isset($_GET['edit_konto'])) {
    $stmt = $PDO->prepare("SELECT * FROM kontenplan WHERE id = ?");
    $stmt->execute([$_GET['edit_konto']]);
    $edit_konto = $stmt->fetch();
}
?>
<?php $page_title = 'Finanzbuchhaltung'; include 'includes/header.php'; ?>
<?php include 'includes/table-style.php'; ?>

<style>
.tabs { display: flex; gap: 0.5rem; margin-bottom: 2rem; flex-wrap: wrap; }
.tab { padding: 0.75rem 1.5rem; background: white; border: 2px solid var(--border); border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s; }
.tab.active { background: var(--primary); color: white; border-color: var(--primary); }
.tab:hover { border-color: var(--primary); }
.tab-content { display: none; }
.tab-content.active { display: block; }
.form-section { background: var(--light); padding: 2rem; border-radius: 12px; margin-bottom: 2rem; }
</style>

<div class="dashboard-card">
    <h2 class="section-title mb-4">
        <i class="fas fa-euro-sign me-2"></i>Finanzbuchhaltung
    </h2>

    <?php if (isset($success)): ?>
        <div class="alert alert-success mb-4"><?= $success ?></div>
    <?php endif; ?>

    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-number"><?= number_format($gesamt_rechnungen, 2, ',', '.') ?> €</div>
            <div class="stat-label"><i class="fas fa-file-invoice me-1"></i>Gesamte Rechnungen</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($offene_rechnungen, 2, ',', '.') ?> €</div>
            <div class="stat-label"><i class="fas fa-clock me-1"></i>Offene Forderungen</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($bezahlte_rechnungen, 2, ',', '.') ?> €</div>
            <div class="stat-label"><i class="fas fa-check-circle me-1"></i>Bezahlte Rechnungen</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($offene_bestellungen) ?></div>
            <div class="stat-label"><i class="fas fa-file-alt me-1"></i>Zu fakturieren</div>
        </div>
    </div>

    <div class="tabs">
        <button class="tab active" onclick="showTab('rechnungen')"><i class="fas fa-file-invoice me-1"></i>Rechnungen</button>
        <button class="tab" onclick="showTab('buchungen')"><i class="fas fa-book me-1"></i>Buchungen</button>
        <button class="tab" onclick="showTab('fakturierung')"><i class="fas fa-file-alt me-1"></i>Fakturierung</button>
        <button class="tab" onclick="showTab('konten')"><i class="fas fa-university me-1"></i>Kontenplan</button>
    </div>

    <div id="rechnungen" class="tab-content active">
        <h4 class="mb-3"><i class="fas fa-file-invoice me-2"></i>Rechnungsübersicht</h4>
        <div class="modern-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rechnungsnr.</th>
                        <th>Kunde</th>
                        <th>Datum</th>
                        <th>Fällig</th>
                        <th>Netto</th>
                        <th>MwSt</th>
                        <th>Brutto</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rechnungen as $rechnung): ?>
                    <tr>
                        <td><?= htmlspecialchars($rechnung['rechnungsnummer']) ?></td>
                        <td><?= htmlspecialchars($rechnung['vorname'] . ' ' . $rechnung['nachname']) ?><br><small><?= htmlspecialchars($rechnung['firmenname'] ?? '') ?></small></td>
                        <td><?= date('d.m.Y', strtotime($rechnung['rechnungsdatum'])) ?></td>
                        <td><?= date('d.m.Y', strtotime($rechnung['faelligkeitsdatum'])) ?></td>
                        <td><?= number_format($rechnung['nettobetrag'], 2, ',', '.') ?> €</td>
                        <td><?= number_format($rechnung['mwst_betrag'], 2, ',', '.') ?> €</td>
                        <td><?= number_format($rechnung['bruttobetrag'], 2, ',', '.') ?> €</td>
                        <td>
                            <?php if ($rechnung['status'] === 'offen'): ?>
                                <span style="color: red;">⏳ Offen</span>
                            <?php else: ?>
                                <span style="color: green;">✅ Bezahlt</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($rechnung['status'] === 'offen'): ?>
                                <a href="generate_rechnung_pdf.php?rechnung_id=<?= $rechnung['id'] ?>" class="action-btn btn-primary-modern" target="_blank"><i class="fas fa-file-pdf me-1"></i>PDF</a>
                                <a href="send_rechnung_email.php?rechnung_id=<?= $rechnung['id'] ?>" class="action-btn btn-info"><i class="fas fa-envelope me-1"></i>E-Mail</a>
                                <a href="rechnung_formular.php?rechnung_id=<?= $rechnung['id'] ?>" class="action-btn btn-warning-modern"><i class="fas fa-edit me-1"></i>Bearbeiten</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="zahlung_buchen">
                                    <input type="hidden" name="rechnung_id" value="<?= $rechnung['id'] ?>">
                                    <button type="submit" class="action-btn btn-success-modern"><i class="fas fa-check me-1"></i>Zahlung</button>
                                </form>
                            <?php else: ?>
                                <a href="generate_rechnung_pdf.php?rechnung_id=<?= $rechnung['id'] ?>" class="action-btn btn-primary-modern" target="_blank"><i class="fas fa-file-pdf me-1"></i>PDF</a>
                                <a href="send_rechnung_email.php?rechnung_id=<?= $rechnung['id'] ?>" class="action-btn btn-info"><i class="fas fa-envelope me-1"></i>E-Mail</a>
                                <span class="status-badge badge-success">✓ Bezahlt</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="buchungen" class="tab-content">
        <h4 class="mb-3"><i class="fas fa-book me-2"></i>Buchungsjournal</h4>
        <div class="modern-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Beleg</th>
                        <th>Soll-Konto</th>
                        <th>Haben-Konto</th>
                        <th>Betrag</th>
                        <th>Beschreibung</th>
                        <th>Kategorie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($buchungen as $buchung): ?>
                    <tr>
                        <td><?= date('d.m.Y', strtotime($buchung['buchungsdatum'])) ?></td>
                        <td><?= htmlspecialchars($buchung['belegnummer']) ?></td>
                        <td><?= $buchung['konto_soll'] ?> - <?= htmlspecialchars($buchung['soll_name']) ?></td>
                        <td><?= $buchung['konto_haben'] ?> - <?= htmlspecialchars($buchung['haben_name']) ?></td>
                        <td><?= number_format($buchung['betrag'], 2, ',', '.') ?> €</td>
                        <td><?= htmlspecialchars($buchung['beschreibung']) ?></td>
                        <td><?= htmlspecialchars($buchung['kategorie']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="fakturierung" class="tab-content">
        <h4 class="mb-3"><i class="fas fa-file-alt me-2"></i>Rechnungen erstellen</h4>
        <div class="modern-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Bestellnr.</th>
                        <th>Kunde</th>
                        <th>Bestellungsname</th>
                        <th>Betrag</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($offene_bestellungen as $bestellung): ?>
                    <tr>
                        <td><?= htmlspecialchars($bestellung['bestellungsnummer']) ?></td>
                        <td><?= htmlspecialchars($bestellung['vorname'] . ' ' . $bestellung['nachname']) ?><br><small><?= htmlspecialchars($bestellung['firmenname'] ?? '') ?></small></td>
                        <td><?= htmlspecialchars($bestellung['bestellungsname']) ?></td>
                        <td><?= number_format($bestellung['gesamtpreis'], 2, ',', '.') ?> €</td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="create_rechnung">
                                <input type="hidden" name="bestellung_id" value="<?= $bestellung['idbestellung'] ?>">
                                <a href="rechnung_formular.php?bestellung_id=<?= $bestellung['idbestellung'] ?>" class="action-btn btn-primary-modern"><i class="fas fa-file-invoice me-1"></i>Rechnung erstellen</a>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="konten" class="tab-content">
        <h4 class="mb-3"><i class="fas fa-university me-2"></i>Kontenplan</h4>
        
        <div class="mb-4">
            <button onclick="toggleKontoForm()" class="btn btn-success" id="toggleBtn"><i class="fas fa-plus me-2"></i>Neues Konto hinzufügen</button>
        </div>
        
        <div class="form-section" id="kontoForm" style="display: <?= $edit_konto ? 'block' : 'none' ?>;">
            <h5><?= $edit_konto ? 'Konto bearbeiten' : 'Neues Konto hinzufügen' ?></h5>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="save_konto">
                <?php if ($edit_konto): ?>
                    <input type="hidden" name="konto_id" value="<?= $edit_konto['id'] ?>">
                <?php endif; ?>
                
                <div class="col-md-3">
                    <label class="form-label">Kontonummer</label>
                    <input type="text" class="form-control" name="kontonummer" value="<?= htmlspecialchars($edit_konto['kontonummer'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kontoname</label>
                    <input type="text" class="form-control" name="kontoname" value="<?= htmlspecialchars($edit_konto['kontoname'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kontotyp</label>
                    <select class="form-select" name="kontotyp" required>
                        <option value="Aktiva" <?= ($edit_konto['kontotyp'] ?? '') === 'Aktiva' ? 'selected' : '' ?>>Aktiva</option>
                        <option value="Passiva" <?= ($edit_konto['kontotyp'] ?? '') === 'Passiva' ? 'selected' : '' ?>>Passiva</option>
                        <option value="Aufwand" <?= ($edit_konto['kontotyp'] ?? '') === 'Aufwand' ? 'selected' : '' ?>>Aufwand</option>
                        <option value="Ertrag" <?= ($edit_konto['kontotyp'] ?? '') === 'Ertrag' ? 'selected' : '' ?>>Ertrag</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Aktiv</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" name="aktiv" <?= ($edit_konto['aktiv'] ?? 1) ? 'checked' : '' ?>>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i><?= $edit_konto ? 'Aktualisieren' : 'Hinzufügen' ?></button>
                    <?php if ($edit_konto): ?>
                        <a href="finanzbuchhaltung.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i>Abbrechen</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <?php
        $stmt = $PDO->prepare("SELECT * FROM kontenplan ORDER BY kontonummer");
        $stmt->execute();
        $konten = $stmt->fetchAll();
        ?>
        <div class="modern-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kontonummer</th>
                        <th>Kontoname</th>
                        <th>Kontotyp</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($konten as $konto): ?>
                    <tr>
                        <td><?= htmlspecialchars($konto['kontonummer']) ?></td>
                        <td><?= htmlspecialchars($konto['kontoname']) ?></td>
                        <td><?= htmlspecialchars($konto['kontotyp']) ?></td>
                        <td><?= $konto['aktiv'] ? '✅ Aktiv' : '❌ Inaktiv' ?></td>
                    <td>
                        <a href="finanzbuchhaltung.php?edit_konto=<?= $konto['id'] ?>" class="action-btn btn-warning-modern"><i class="fas fa-edit me-1"></i>Bearbeiten</a>
                        <a href="finanzbuchhaltung.php?delete_konto=<?= $konto['id'] ?>" class="action-btn btn-danger-modern" onclick="return confirm('Konto wirklich löschen?')"><i class="fas fa-trash me-1"></i>Löschen</a>
                    </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}

function toggleKontoForm() {
    const form = document.getElementById('kontoForm');
    const btn = document.getElementById('toggleBtn');
    if (form.style.display === 'none') {
        form.style.display = 'block';
        btn.innerHTML = '<i class="fas fa-minus me-2"></i>Formular schließen';
    } else {
        form.style.display = 'none';
        btn.innerHTML = '<i class="fas fa-plus me-2"></i>Neues Konto hinzufügen';
    }
}
</script>

<?php include 'includes/footer.php'; ?>