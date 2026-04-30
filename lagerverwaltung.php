<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Tabellen erstellen
try {
    try {
        $PDO->exec("DROP TABLE IF EXISTS lagerartikel_old");
        $PDO->exec("ALTER TABLE lagerartikel RENAME TO lagerartikel_old");
    } catch (Exception $e) {}
    
    $PDO->exec("CREATE TABLE IF NOT EXISTS lagerartikel (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        artikelnummer TEXT UNIQUE NOT NULL,
        artikelname TEXT NOT NULL,
        modellnummer TEXT,
        barcode TEXT,
        beschreibung TEXT,
        kategorie TEXT,
        einheit TEXT DEFAULT 'Stück',
        bestand REAL DEFAULT 0,
        reserviert REAL DEFAULT 0,
        mindestbestand REAL DEFAULT 0,
        lagerort TEXT,
        einkaufspreis REAL DEFAULT 0,
        verkaufspreis REAL DEFAULT 0,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $PDO->exec("CREATE TABLE IF NOT EXISTS lager_reservierungen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        artikel_id INTEGER NOT NULL,
        menge REAL NOT NULL,
        reserviert_fuer TEXT NOT NULL,
        angebot_id INTEGER,
        status TEXT DEFAULT 'reserviert',
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

// Artikel loeschen
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $PDO->prepare("DELETE FROM lagerartikel WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Artikel geloescht!";
    header("Location: lagerverwaltung.php?tab=artikel&success=" . urlencode($success));
    exit;
}

// Artikel speichern
if (isset($_POST['action']) && $_POST['action'] === 'save_artikel') {
    $id = $_POST['artikel_id'] ?? null;
    $artikelnummer = $_POST['artikelnummer'];
    $artikelname = $_POST['artikelname'];
    $modellnummer = $_POST['modellnummer'] ?? '';
    $barcode = $_POST['barcode'] ?? '';
    $beschreibung = $_POST['beschreibung'] ?? '';
    $kategorie = $_POST['kategorie'] ?? '';
    $einheit = $_POST['einheit'] ?? 'Stueck';
    $bestand = (float)$_POST['bestand'];
    $mindestbestand = (float)$_POST['mindestbestand'];
    $lagerort = $_POST['lagerort'] ?? '';
    $einkaufspreis = (float)$_POST['einkaufspreis'];
    $verkaufspreis = (float)$_POST['verkaufspreis'];
    
    if ($id) {
        $stmt = $PDO->prepare("UPDATE lagerartikel SET artikelnummer=?, artikelname=?, modellnummer=?, barcode=?, beschreibung=?, kategorie=?, einheit=?, bestand=?, mindestbestand=?, lagerort=?, einkaufspreis=?, verkaufspreis=? WHERE id=?");
        $stmt->execute([$artikelnummer, $artikelname, $modellnummer, $barcode, $beschreibung, $kategorie, $einheit, $bestand, $mindestbestand, $lagerort, $einkaufspreis, $verkaufspreis, $id]);
        $success = "Artikel aktualisiert!";
    } else {
        $stmt = $PDO->prepare("INSERT INTO lagerartikel (artikelnummer, artikelname, modellnummer, barcode, beschreibung, kategorie, einheit, bestand, mindestbestand, lagerort, einkaufspreis, verkaufspreis) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$artikelnummer, $artikelname, $modellnummer, $barcode, $beschreibung, $kategorie, $einheit, $bestand, $mindestbestand, $lagerort, $einkaufspreis, $verkaufspreis]);
        $success = "Artikel hinzugefuegt!";
    }
    
    header("Location: lagerverwaltung.php?tab=artikel&success=" . urlencode($success));
    exit;
}

// Bestand aendern
if (isset($_POST['action']) && $_POST['action'] === 'update_bestand') {
    $artikel_id = $_POST['artikel_id'];
    $neue_menge = (float)$_POST['neue_menge'];
    
    $stmt = $PDO->prepare("UPDATE lagerartikel SET bestand = ? WHERE id = ?");
    $stmt->execute([$neue_menge, $artikel_id]);
    $success = "Bestand aktualisiert!";
    
    header("Location: lagerverwaltung.php?tab=artikel&success=" . urlencode($success));
    exit;
}

// Aktiver Tab
$active_tab = $_GET['tab'] ?? 'artikel';

// Artikel laden
$stmt = $PDO->query("SELECT *, (bestand - reserviert) as verfuegbar FROM lagerartikel ORDER BY artikelname");
$artikel = $stmt->fetchAll();

// Artikel zum Bearbeiten
$edit_artikel = null;
if (isset($_GET['edit'])) {
    $stmt = $PDO->prepare("SELECT * FROM lagerartikel WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_artikel = $stmt->fetch();
    if ($edit_artikel) {
        $active_tab = 'neuerartikel';
    }
}

// Success-Message aus URL
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Nachbestellliste
$stmt = $PDO->query("SELECT * FROM lagerartikel WHERE (bestand - reserviert) <= mindestbestand ORDER BY (bestand - reserviert) ASC");
$nachbestellen = $stmt->fetchAll();

// Reservierungen
$stmt = $PDO->query("SELECT r.*, a.artikelname, a.artikelnummer FROM lager_reservierungen r LEFT JOIN lagerartikel a ON r.artikel_id = a.id WHERE r.status = 'reserviert' ORDER BY r.erstellt_am DESC");
$reservierungen = $stmt->fetchAll();

// Statistiken
$stmt = $PDO->query("SELECT COUNT(*) as anzahl FROM lagerartikel");
$anzahl_artikel = $stmt->fetch()['anzahl'];

$stmt = $PDO->query("SELECT COUNT(*) as anzahl FROM lagerartikel WHERE (bestand - reserviert) <= mindestbestand");
$artikel_nachbestellen = $stmt->fetch()['anzahl'];

$stmt = $PDO->query("SELECT SUM(bestand * einkaufspreis) as wert FROM lagerartikel");
$lagerwert = $stmt->fetch()['wert'] ?? 0;

$stmt = $PDO->query("SELECT SUM(reserviert) as gesamt FROM lagerartikel");
$gesamt_reserviert = $stmt->fetch()['gesamt'] ?? 0;
?>
<?php $page_title = 'ERP System'; include 'includes/header.php'; ?>

<div class="stats-grid">
<?php include 'includes/table-style.php'; ?>
    <div class="stat-card">
        <div class="stat-value"><?= $anzahl_artikel ?></div>
        <div>Artikel im Lager</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $artikel_nachbestellen ?></div>
        <div>Nachbestellen</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format($gesamt_reserviert, 0) ?></div>
        <div>Reserviert</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format($lagerwert, 0) ?> EUR</div>
        <div>Lagerwert</div>
    </div>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link <?= $active_tab === 'artikel' ? 'active' : '' ?>" href="?tab=artikel">📋 Artikel</a></li>
    <li class="nav-item"><a class="nav-link <?= $active_tab === 'nachbestellen' ? 'active' : '' ?>" href="?tab=nachbestellen">⚠️ Nachbestellen (<?= $artikel_nachbestellen ?>)</a></li>
    <li class="nav-item"><a class="nav-link <?= $active_tab === 'reservierungen' ? 'active' : '' ?>" href="?tab=reservierungen">🔒 Reservierungen</a></li>
    <li class="nav-item"><a class="nav-link <?= $active_tab === 'neuerartikel' ? 'active' : '' ?>" href="?tab=neuerartikel">➕ Neuer Artikel</a></li>
</ul>

<?php if ($active_tab === 'artikel'): ?>
<div id="artikel">
    <h2>📋 Lagerartikel</h2>
    <div class="modern-table">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Artikelnr.</th>
                <th>Artikelname</th>
                <th>Kategorie</th>
                <th>Bestand</th>
                <th>Reserviert</th>
                <th>Verfuegbar</th>
                <th>Einheit</th>
                <th>VK-Preis</th>
                <th>Status</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($artikel as $art): 
                $verfuegbar = $art['bestand'] - $art['reserviert'];
            ?>
            <tr>
                <td><?= htmlspecialchars($art['artikelnummer']) ?></td>
                <td><strong><?= htmlspecialchars($art['artikelname']) ?></strong></td>
                <td><?= htmlspecialchars($art['kategorie']) ?></td>
                <td><strong><?= number_format($art['bestand'], 0) ?></strong></td>
                <td><?= number_format($art['reserviert'], 0) ?></td>
                <td><strong><?= number_format($verfuegbar, 0) ?></strong></td>
                <td><?= htmlspecialchars($art['einheit']) ?></td>
                <td><?= number_format($art['verkaufspreis'], 2) ?> EUR</td>
                <td>
                    <?php if ($verfuegbar <= 0): ?>
                        <span class="badge-danger">Nicht verfuegbar</span>
                    <?php elseif ($verfuegbar <= $art['mindestbestand']): ?>
                        <span class="badge-warning">Nachbestellen</span>
                    <?php else: ?>
                        <span class="badge-success">Verfuegbar</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?edit=<?= $art['id'] ?>&tab=neuerartikel" class="btn btn-sm btn-primary">Bearbeiten</a>
                    <button class="btn btn-sm btn-success" onclick="updateBestand(<?= $art['id'] ?>, '<?= htmlspecialchars($art['artikelname']) ?>', <?= $art['bestand'] ?>)">Bestand</button>
                    <a href="?delete=<?= $art['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Artikel wirklich loeschen?')">Loeschen</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if ($active_tab === 'nachbestellen'): ?>
<div id="nachbestellen">
    <h2>⚠️ Nachbestellliste</h2>
    <p class="text-muted">Artikel, die nachbestellt werden muessen</p>
    <div class="modern-table">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Artikelnr.</th>
                <th>Artikelname</th>
                <th>Bestand</th>
                <th>Reserviert</th>
                <th>Verfuegbar</th>
                <th>Mindestbestand</th>
                <th>Nachbestellen</th>
                <th>EK-Preis</th>
                <th>Gesamt</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($nachbestellen as $art): 
                $verfuegbar = $art['bestand'] - $art['reserviert'];
                $nachbestell_menge = max(0, $art['mindestbestand'] - $verfuegbar + 10);
            ?>
            <tr>
                <td><?= htmlspecialchars($art['artikelnummer']) ?></td>
                <td><strong><?= htmlspecialchars($art['artikelname']) ?></strong></td>
                <td><?= number_format($art['bestand'], 0) ?></td>
                <td><?= number_format($art['reserviert'], 0) ?></td>
                <td><strong class="text-danger"><?= number_format($verfuegbar, 0) ?></strong></td>
                <td><?= number_format($art['mindestbestand'], 0) ?></td>
                <td><strong class="text-primary"><?= number_format($nachbestell_menge, 0) ?> <?= $art['einheit'] ?></strong></td>
                <td><?= number_format($art['einkaufspreis'], 2) ?> EUR</td>
                <td><strong><?= number_format($nachbestell_menge * $art['einkaufspreis'], 2) ?> EUR</strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if ($active_tab === 'reservierungen'): ?>
<div id="reservierungen">
    <h2>🔒 Aktive Reservierungen</h2>
    <table class="table table-hover">
        <thead class="table-warning">
            <tr>
                <th>Datum</th>
                <th>Artikel</th>
                <th>Menge</th>
                <th>Reserviert fuer</th>
                <th>Angebot-ID</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservierungen as $res): ?>
            <tr>
                <td><?= date('d.m.Y H:i', strtotime($res['erstellt_am'])) ?></td>
                <td><?= htmlspecialchars($res['artikelname']) ?> (<?= $res['artikelnummer'] ?>)</td>
                <td><strong><?= number_format($res['menge'], 0) ?></strong></td>
                <td><?= htmlspecialchars($res['reserviert_fuer']) ?></td>
                <td><?= $res['angebot_id'] ?? '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($active_tab === 'neuerartikel'): ?>
<div id="neuerartikel">
    <h2><?= $edit_artikel ? 'Artikel bearbeiten' : 'Neuer Artikel' ?></h2>
    
    <?php if (!$edit_artikel): ?>
    <!-- Produkt-Suche -->
    <div class="alert alert-info mb-4">
        <h5>💡 Aus Produktkatalog importieren</h5>
        <div class="input-group">
            <input type="text" id="produktSuche" class="form-control" placeholder="Produkt suchen..." onkeyup="sucheProdukt()">
            <button class="btn btn-primary" type="button" onclick="sucheProdukt()">Suchen</button>
        </div>
        <div id="produktErgebnisse" class="mt-3"></div>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="row g-3" id="artikelForm">
        <input type="hidden" name="action" value="save_artikel">
        <?php if ($edit_artikel): ?>
            <input type="hidden" name="artikel_id" value="<?= $edit_artikel['id'] ?>">
        <?php endif; ?>
        
        <div class="col-md-6">
            <label class="form-label">Artikelnummer *</label>
            <input type="text" name="artikelnummer" class="form-control" value="<?= htmlspecialchars($edit_artikel['artikelnummer'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Artikelname *</label>
            <input type="text" name="artikelname" class="form-control" value="<?= htmlspecialchars($edit_artikel['artikelname'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Modellnummer</label>
            <input type="text" name="modellnummer" id="modellnummer" class="form-control" value="<?= htmlspecialchars($edit_artikel['modellnummer'] ?? '') ?>" placeholder="z.B. ABC-123">
        </div>
        <div class="col-md-6">
            <label class="form-label">Barcode/EAN</label>
            <input type="text" name="barcode" id="barcode" class="form-control" value="<?= htmlspecialchars($edit_artikel['barcode'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Beschreibung</label>
            <textarea name="beschreibung" class="form-control" rows="2"><?= htmlspecialchars($edit_artikel['beschreibung'] ?? '') ?></textarea>
        </div>
        <div class="col-md-4">
            <label class="form-label">Kategorie</label>
            <input type="text" name="kategorie" class="form-control" value="<?= htmlspecialchars($edit_artikel['kategorie'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Einheit</label>
            <select name="einheit" class="form-select">
                <option value="Stueck" <?= ($edit_artikel['einheit'] ?? '') === 'Stueck' ? 'selected' : '' ?>>Stueck</option>
                <option value="kg" <?= ($edit_artikel['einheit'] ?? '') === 'kg' ? 'selected' : '' ?>>kg</option>
                <option value="Liter" <?= ($edit_artikel['einheit'] ?? '') === 'Liter' ? 'selected' : '' ?>>Liter</option>
                <option value="Meter" <?= ($edit_artikel['einheit'] ?? '') === 'Meter' ? 'selected' : '' ?>>Meter</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Lagerort</label>
            <input type="text" name="lagerort" class="form-control" value="<?= htmlspecialchars($edit_artikel['lagerort'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Bestand</label>
            <input type="number" name="bestand" class="form-control" step="0.01" value="<?= $edit_artikel['bestand'] ?? '0' ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Mindestbestand</label>
            <input type="number" name="mindestbestand" class="form-control" step="0.01" value="<?= $edit_artikel['mindestbestand'] ?? '10' ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Einkaufspreis (EUR)</label>
            <input type="number" name="einkaufspreis" class="form-control" step="0.01" value="<?= $edit_artikel['einkaufspreis'] ?? '0' ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Verkaufspreis (EUR)</label>
            <input type="number" name="verkaufspreis" class="form-control" step="0.01" value="<?= $edit_artikel['verkaufspreis'] ?? '0' ?>">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Speichern</button>
            <?php if ($edit_artikel): ?>
                <a href="lagerverwaltung.php?tab=artikel" class="btn btn-secondary">Abbrechen</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Modal fuer Bestandsaenderung -->
<div class="modal fade" id="bestandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Bestand aendern</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_bestand">
                    <input type="hidden" name="artikel_id" id="modal_artikel_id">
                    <div class="mb-3">
                        <label class="form-label">Neuer Bestand</label>
                        <input type="number" name="neue_menge" id="modal_menge" class="form-control" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateBestand(id, name, aktuell) {
    document.getElementById('modal_artikel_id').value = id;
    document.getElementById('modal_menge').value = aktuell;
    document.getElementById('modalTitle').textContent = 'Bestand aendern: ' + name;
    new bootstrap.Modal(document.getElementById('bestandModal')).show();
}

function sucheProdukt() {
    const suche = document.getElementById('produktSuche').value;
    if (suche.length < 2) {
        document.getElementById('produktErgebnisse').innerHTML = '';
        return;
    }
    
    fetch('api_produkt_suche.php?q=' + encodeURIComponent(suche))
        .then(response => response.json())
        .then(data => {
            let html = '';
            if (data.length > 0) {
                html = '<div class="list-group">';
                data.forEach(produkt => {
                    html += `
                        <a href="#" class="list-group-item list-group-item-action" onclick="importProdukt(${produkt.id}, '${produkt.name}', '${produkt.beschreibung}', ${produkt.preis}, '${produkt.kategorie}'); return false;">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${produkt.name}</h6>
                                <small>${produkt.preis} EUR</small>
                            </div>
                            <p class="mb-1">${produkt.beschreibung || ''}</p>
                            <small>Kategorie: ${produkt.kategorie || '-'}</small>
                        </a>
                    `;
                });
                html += '</div>';
            } else {
                html = '<div class="alert alert-warning">Keine Produkte gefunden</div>';
            }
            document.getElementById('produktErgebnisse').innerHTML = html;
        })
        .catch(error => {
            console.error('Fehler:', error);
            document.getElementById('produktErgebnisse').innerHTML = '<div class="alert alert-danger">Fehler beim Laden</div>';
        });
}

function importProdukt(id, name, beschreibung, preis, kategorie) {
    document.querySelector('[name="artikelnummer"]').value = 'ART-' + id;
    document.querySelector('[name="artikelname"]').value = name;
    document.querySelector('[name="beschreibung"]').value = beschreibung || '';
    document.querySelector('[name="kategorie"]').value = kategorie || '';
    document.querySelector('[name="verkaufspreis"]').value = preis;
    document.querySelector('[name="einkaufspreis"]').value = (preis * 0.6).toFixed(2);
    
    document.getElementById('produktErgebnisse').innerHTML = '<div class="alert alert-success">Produkt importiert: ' + name + '</div>';
    document.getElementById('produktSuche').value = '';
    document.querySelector('[name="bestand"]').focus();
}
</script>

<?php include 'includes/footer.php'; ?>

