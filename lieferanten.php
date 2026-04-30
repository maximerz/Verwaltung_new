<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Tabellen erstellen
try {
    $PDO->exec("CREATE TABLE IF NOT EXISTS lieferanten (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lieferantennummer TEXT UNIQUE NOT NULL,
        firmenname TEXT NOT NULL,
        ansprechpartner TEXT,
        email TEXT,
        telefon TEXT,
        strasse TEXT,
        plz TEXT,
        ort TEXT,
        land TEXT DEFAULT 'Deutschland',
        zahlungsziel INTEGER DEFAULT 30,
        lieferzeit INTEGER DEFAULT 7,
        bemerkung TEXT,
        aktiv INTEGER DEFAULT 1,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $PDO->exec("CREATE TABLE IF NOT EXISTS lieferantenbestellungen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bestellnummer TEXT UNIQUE NOT NULL,
        lieferant_id INTEGER NOT NULL,
        bestelldatum DATE NOT NULL,
        lieferdatum DATE,
        status TEXT DEFAULT 'offen',
        gesamtbetrag REAL DEFAULT 0,
        bemerkung TEXT,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

// Lieferant hinzufügen/bearbeiten
if (isset($_POST['action']) && $_POST['action'] === 'save_lieferant') {
    $id = $_POST['lieferant_id'] ?? null;
    $lieferantennummer = $_POST['lieferantennummer'];
    $firmenname = $_POST['firmenname'];
    $ansprechpartner = $_POST['ansprechpartner'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefon = $_POST['telefon'] ?? '';
    $strasse = $_POST['strasse'] ?? '';
    $plz = $_POST['plz'] ?? '';
    $ort = $_POST['ort'] ?? '';
    $land = $_POST['land'] ?? 'Deutschland';
    $zahlungsziel = (int)$_POST['zahlungsziel'];
    $lieferzeit = (int)$_POST['lieferzeit'];
    $bemerkung = $_POST['bemerkung'] ?? '';
    $aktiv = isset($_POST['aktiv']) ? 1 : 0;
    
    if ($id) {
        $stmt = $PDO->prepare("UPDATE lieferanten SET lieferantennummer=?, firmenname=?, ansprechpartner=?, email=?, telefon=?, strasse=?, plz=?, ort=?, land=?, zahlungsziel=?, lieferzeit=?, bemerkung=?, aktiv=? WHERE id=?");
        $stmt->execute([$lieferantennummer, $firmenname, $ansprechpartner, $email, $telefon, $strasse, $plz, $ort, $land, $zahlungsziel, $lieferzeit, $bemerkung, $aktiv, $id]);
        $success = "Lieferant aktualisiert!";
    } else {
        $stmt = $PDO->prepare("INSERT INTO lieferanten (lieferantennummer, firmenname, ansprechpartner, email, telefon, strasse, plz, ort, land, zahlungsziel, lieferzeit, bemerkung, aktiv) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$lieferantennummer, $firmenname, $ansprechpartner, $email, $telefon, $strasse, $plz, $ort, $land, $zahlungsziel, $lieferzeit, $bemerkung, $aktiv]);
        $success = "Lieferant hinzugefügt!";
    }
}

// Lieferanten laden
$search = $_GET['search'] ?? '';
$where = $search ? "WHERE firmenname LIKE ? OR lieferantennummer LIKE ? OR ort LIKE ?" : "";
$params = $search ? ["%$search%", "%$search%", "%$search%"] : [];

$stmt = $PDO->prepare("SELECT * FROM lieferanten $where ORDER BY firmenname");
$stmt->execute($params);
$lieferanten = $stmt->fetchAll();

// Lieferant zum Bearbeiten
$edit_lieferant = null;
if (isset($_GET['edit'])) {
    $stmt = $PDO->prepare("SELECT * FROM lieferanten WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_lieferant = $stmt->fetch();
}

// Statistiken
$stmt = $PDO->query("SELECT COUNT(*) as anzahl FROM lieferanten WHERE aktiv = 1");
$aktive_lieferanten = $stmt->fetch()['anzahl'];

$stmt = $PDO->query("SELECT COUNT(*) as anzahl FROM lieferantenbestellungen WHERE status = 'offen'");
$offene_bestellungen = $stmt->fetch()['anzahl'];

$page_title = 'Lieferanten';
include 'includes/header.php';
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $aktive_lieferanten ?></div>
        <div class="stat-label"><i class="fas fa-check-circle me-2"></i>Aktive Lieferanten</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $offene_bestellungen ?></div>
        <div class="stat-label"><i class="fas fa-clock me-2"></i>Offene Bestellungen</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= count($lieferanten) ?></div>
        <div class="stat-label"><i class="fas fa-building me-2"></i>Gesamt</div>
    </div>
</div>

<!-- Lieferant Form -->
<div class="dashboard-card" id="lieferantForm" style="<?= !$edit_lieferant ? 'display:none;' : '' ?>">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-<?= $edit_lieferant ? 'edit' : 'plus' ?> me-2"></i>
            <?= $edit_lieferant ? 'Lieferant bearbeiten' : 'Neuer Lieferant' ?>
        </h2>
    </div>
    
    <form method="POST" class="row g-3">
        <input type="hidden" name="action" value="save_lieferant">
        <?php if ($edit_lieferant): ?>
            <input type="hidden" name="lieferant_id" value="<?= $edit_lieferant['id'] ?>">
        <?php endif; ?>
        
        <div class="col-md-6">
            <label class="form-label"><i class="fas fa-hashtag me-1"></i>Lieferantennummer</label>
            <input type="text" name="lieferantennummer" class="form-control" value="<?= htmlspecialchars($edit_lieferant['lieferantennummer'] ?? 'LF' . rand(1000, 9999)) ?>" required>
        </div>
        
        <div class="col-md-6">
            <label class="form-label"><i class="fas fa-building me-1"></i>Firmenname</label>
            <input type="text" name="firmenname" class="form-control" value="<?= htmlspecialchars($edit_lieferant['firmenname'] ?? '') ?>" required>
        </div>
        
        <div class="col-md-4">
            <label class="form-label"><i class="fas fa-user me-1"></i>Ansprechpartner</label>
            <input type="text" name="ansprechpartner" class="form-control" value="<?= htmlspecialchars($edit_lieferant['ansprechpartner'] ?? '') ?>">
        </div>
        
        <div class="col-md-4">
            <label class="form-label"><i class="fas fa-envelope me-1"></i>E-Mail</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_lieferant['email'] ?? '') ?>">
        </div>
        
        <div class="col-md-4">
            <label class="form-label"><i class="fas fa-phone me-1"></i>Telefon</label>
            <input type="text" name="telefon" class="form-control" value="<?= htmlspecialchars($edit_lieferant['telefon'] ?? '') ?>">
        </div>
        
        <div class="col-md-6">
            <label class="form-label"><i class="fas fa-road me-1"></i>Straße</label>
            <input type="text" name="strasse" class="form-control" value="<?= htmlspecialchars($edit_lieferant['strasse'] ?? '') ?>">
        </div>
        
        <div class="col-md-2">
            <label class="form-label"><i class="fas fa-map-pin me-1"></i>PLZ</label>
            <input type="text" name="plz" class="form-control" value="<?= htmlspecialchars($edit_lieferant['plz'] ?? '') ?>">
        </div>
        
        <div class="col-md-2">
            <label class="form-label"><i class="fas fa-city me-1"></i>Ort</label>
            <input type="text" name="ort" class="form-control" value="<?= htmlspecialchars($edit_lieferant['ort'] ?? '') ?>">
        </div>
        
        <div class="col-md-2">
            <label class="form-label"><i class="fas fa-flag me-1"></i>Land</label>
            <input type="text" name="land" class="form-control" value="<?= htmlspecialchars($edit_lieferant['land'] ?? 'Deutschland') ?>">
        </div>
        
        <div class="col-md-3">
            <label class="form-label"><i class="fas fa-calendar me-1"></i>Zahlungsziel (Tage)</label>
            <input type="number" name="zahlungsziel" class="form-control" value="<?= $edit_lieferant['zahlungsziel'] ?? '30' ?>">
        </div>
        
        <div class="col-md-3">
            <label class="form-label"><i class="fas fa-truck me-1"></i>Lieferzeit (Tage)</label>
            <input type="number" name="lieferzeit" class="form-control" value="<?= $edit_lieferant['lieferzeit'] ?? '7' ?>">
        </div>
        
        <div class="col-md-6">
            <label class="form-label"><i class="fas fa-toggle-on me-1"></i>Status</label>
            <div class="form-check form-switch" style="padding-top: 0.5rem;">
                <input class="form-check-input" type="checkbox" name="aktiv" <?= ($edit_lieferant['aktiv'] ?? 1) ? 'checked' : '' ?> style="width: 3rem; height: 1.5rem;">
                <label class="form-check-label ms-2">Aktiv</label>
            </div>
        </div>
        
        <div class="col-12">
            <label class="form-label"><i class="fas fa-comment me-1"></i>Bemerkung</label>
            <textarea name="bemerkung" class="form-control" rows="3"><?= htmlspecialchars($edit_lieferant['bemerkung'] ?? '') ?></textarea>
        </div>
        
        <div class="col-12">
            <button type="submit" class="btn-primary-modern action-btn">
                <i class="fas fa-save me-1"></i>Speichern
            </button>
            <a href="lieferanten.php" class="action-btn" style="background: #6c757d; color: white;">
                <i class="fas fa-times me-1"></i>Abbrechen
            </a>
        </div>
    </form>
</div>

<!-- Lieferantenliste -->
<div class="dashboard-card">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-list me-2"></i>Lieferantenliste
        </h2>
        <div class="d-flex gap-2">
            <button onclick="toggleForm()" class="btn-success-modern action-btn" id="toggleBtn">
                <i class="fas fa-plus me-1"></i>Neuer Lieferant
            </button>
            <div class="search-container">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control search-input" placeholder="Lieferant suchen..." value="<?= htmlspecialchars($search) ?>">
                </form>
            </div>
        </div>
    </div>
    
    <div class="modern-table">
        <table class="table">
            <thead>
                <tr>
                    <th><i class="fas fa-hashtag me-1"></i>Nr.</th>
                    <th><i class="fas fa-building me-1"></i>Firma</th>
                    <th><i class="fas fa-user me-1"></i>Ansprechpartner</th>
                    <th><i class="fas fa-envelope me-1"></i>Kontakt</th>
                    <th><i class="fas fa-map-marker-alt me-1"></i>Ort</th>
                    <th><i class="fas fa-calendar me-1"></i>Zahlung</th>
                    <th><i class="fas fa-truck me-1"></i>Lieferzeit</th>
                    <th><i class="fas fa-toggle-on me-1"></i>Status</th>
                    <th><i class="fas fa-cogs me-1"></i>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($lieferanten) > 0): ?>
                    <?php foreach ($lieferanten as $lief): ?>
                    <tr>
                        <td><span class="status-badge badge-primary"><?= htmlspecialchars($lief['lieferantennummer']) ?></span></td>
                        <td><strong><?= htmlspecialchars($lief['firmenname']) ?></strong></td>
                        <td><?= htmlspecialchars($lief['ansprechpartner']) ?></td>
                        <td>
                            <?php if ($lief['email']): ?>
                                <a href="mailto:<?= htmlspecialchars($lief['email']) ?>" class="text-decoration-none">
                                    <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($lief['email']) ?>
                                </a><br>
                            <?php endif; ?>
                            <?php if ($lief['telefon']): ?>
                                <i class="fas fa-phone me-1"></i><?= htmlspecialchars($lief['telefon']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($lief['ort']) ?></td>
                        <td><?= $lief['zahlungsziel'] ?> Tage</td>
                        <td><?= $lief['lieferzeit'] ?> Tage</td>
                        <td>
                            <?php if ($lief['aktiv']): ?>
                                <span class="status-badge badge-success"><i class="fas fa-check me-1"></i>Aktiv</span>
                            <?php else: ?>
                                <span class="status-badge" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                                    <i class="fas fa-times me-1"></i>Inaktiv
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="lieferanten.php?edit=<?= $lief['id'] ?>" class="action-btn btn-warning-modern">
                                <i class="fas fa-edit me-1"></i>Bearbeiten
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted"><?= $search ? 'Keine Lieferanten gefunden für: "' . htmlspecialchars($search) . '"' : 'Keine Lieferanten vorhanden.' ?></p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleForm() {
        const form = document.getElementById('lieferantForm');
        const btn = document.getElementById('toggleBtn');
        if (form.style.display === 'none') {
            form.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-minus me-1"></i>Formular schließen';
            form.scrollIntoView({ behavior: 'smooth' });
        } else {
            form.style.display = 'none';
            btn.innerHTML = '<i class="fas fa-plus me-1"></i>Neuer Lieferant';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>