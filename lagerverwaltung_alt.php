<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Tabellen erstellen falls nicht vorhanden
try {
    $PDO->exec("CREATE TABLE IF NOT EXISTS lagerartikel (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        artikelnummer TEXT UNIQUE NOT NULL,
        artikelname TEXT NOT NULL,
        beschreibung TEXT,
        kategorie TEXT,
        einheit TEXT DEFAULT 'Stück',
        bestand REAL DEFAULT 0,
        mindestbestand REAL DEFAULT 0,
        lagerort TEXT,
        einkaufspreis REAL DEFAULT 0,
        verkaufspreis REAL DEFAULT 0,
        lieferant_id INTEGER,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $PDO->exec("CREATE TABLE IF NOT EXISTS lagerbewegungen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        artikel_id INTEGER NOT NULL,
        bewegungstyp TEXT NOT NULL,
        menge REAL NOT NULL,
        referenz TEXT,
        bemerkung TEXT,
        benutzer_id INTEGER,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $PDO->exec("CREATE TABLE IF NOT EXISTS lager_reservierungen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        artikel_id INTEGER NOT NULL,
        menge REAL NOT NULL,
        reserviert_fuer TEXT NOT NULL,
        angebot_id INTEGER,
        bestellung_id INTEGER,
        status TEXT DEFAULT 'reserviert',
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Spalte für reservierte Menge hinzufügen falls nicht vorhanden
    try {
        $PDO->exec("ALTER TABLE lagerartikel ADD COLUMN reserviert REAL DEFAULT 0");
    } catch (Exception $e) {}
    
    try {
        $PDO->exec("ALTER TABLE lagerartikel ADD COLUMN verfuegbar REAL DEFAULT 0");
    } catch (Exception $e) {}
} catch (Exception $e) {
    // Tabellen existieren bereits
}

// Artikel hinzufügen/bearbeiten
if ($_POST['action'] === 'save_artikel') {
    $id = $_POST['artikel_id'] ?? null;
    $artikelnummer = $_POST['artikelnummer'];
    $artikelname = $_POST['artikelname'];
    $beschreibung = $_POST['beschreibung'] ?? '';
    $kategorie = $_POST['kategorie'] ?? '';
    $einheit = $_POST['einheit'] ?? 'Stück';
    $mindestbestand = (float)$_POST['mindestbestand'];
    $lagerort = $_POST['lagerort'] ?? '';
    $einkaufspreis = (float)$_POST['einkaufspreis'];
    $verkaufspreis = (float)$_POST['verkaufspreis'];
    
    if ($id) {
        $stmt = $PDO->prepare("UPDATE lagerartikel SET artikelnummer=?, artikelname=?, beschreibung=?, kategorie=?, einheit=?, mindestbestand=?, lagerort=?, einkaufspreis=?, verkaufspreis=? WHERE id=?");
        $stmt->execute([$artikelnummer, $artikelname, $beschreibung, $kategorie, $einheit, $mindestbestand, $lagerort, $einkaufspreis, $verkaufspreis, $id]);
        $success = "Artikel aktualisiert!";
    } else {
        $stmt = $PDO->prepare("INSERT INTO lagerartikel (artikelnummer, artikelname, beschreibung, kategorie, einheit, mindestbestand, lagerort, einkaufspreis, verkaufspreis) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$artikelnummer, $artikelname, $beschreibung, $kategorie, $einheit, $mindestbestand, $lagerort, $einkaufspreis, $verkaufspreis]);
        $success = "Artikel hinzugefügt!";
    }
}

// Lagerbewegung buchen
if ($_POST['action'] === 'buche_bewegung') {
    $artikel_id = $_POST['artikel_id'];
    $bewegungstyp = $_POST['bewegungstyp'];
    $menge = (float)$_POST['menge'];
    $referenz = $_POST['referenz'] ?? '';
    $bemerkung = $_POST['bemerkung'] ?? '';
    
    // Bewegung eintragen
    $stmt = $PDO->prepare("INSERT INTO lagerbewegungen (artikel_id, bewegungstyp, menge, referenz, bemerkung, benutzer_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$artikel_id, $bewegungstyp, $menge, $referenz, $bemerkung, $_SESSION['user_id']]);
    
    // Bestand aktualisieren
    if ($bewegungstyp === 'Eingang') {
        $stmt = $PDO->prepare("UPDATE lagerartikel SET bestand = bestand + ? WHERE id = ?");
    } else {
        $stmt = $PDO->prepare("UPDATE lagerartikel SET bestand = bestand - ? WHERE id = ?");
    }
    $stmt->execute([$menge, $artikel_id]);
    
    $success = "Lagerbewegung gebucht!";
}

// Artikel laden
$search = $_GET['search'] ?? '';
$where = $search ? "WHERE artikelname LIKE ? OR artikelnummer LIKE ? OR kategorie LIKE ?" : "";
$params = $search ? ["%$search%", "%$search%", "%$search%"] : [];

$stmt = $PDO->prepare("SELECT * FROM lagerartikel $where ORDER BY artikelname");
$stmt->execute($params);
$artikel = $stmt->fetchAll();

// Artikel zum Bearbeiten
$edit_artikel = null;
if (isset($_GET['edit'])) {
    $stmt = $PDO->prepare("SELECT * FROM lagerartikel WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_artikel = $stmt->fetch();
}

// Statistiken
$stmt = $PDO->query("SELECT COUNT(*) as anzahl FROM lagerartikel");
$anzahl_artikel = $stmt->fetch()['anzahl'];

$stmt = $PDO->query("SELECT COUNT(*) as anzahl FROM lagerartikel WHERE bestand <= mindestbestand");
$artikel_unter_mindestbestand = $stmt->fetch()['anzahl'];

$stmt = $PDO->query("SELECT SUM(bestand * einkaufspreis) as wert FROM lagerartikel");
$lagerwert = $stmt->fetch()['wert'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lagerverwaltung - ERP System</title>
    <style>
        :root {
            --primary: linear-gradient(135deg, #C9A227 0%, #D4AF37 100%);
            --shadow: 0 15px 50px rgba(201,162,39,0.15);
        }
        body { font-family: 'Inter', Arial, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, rgba(26,26,46,0.97) 0%, rgba(22,33,62,0.97) 50%, rgba(30,30,50,0.97) 100%); min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; background: rgba(255,255,255,0.98); padding: 30px; border-radius: 25px; box-shadow: var(--shadow); border: 2px solid rgba(255,255,255,0.3); }
        .header { text-align: center; margin-bottom: 30px; }
        h1, h2, h3 { background: var(--primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0; }
        .stat-card { background: var(--primary); color: white; padding: 20px; border-radius: 15px; text-align: center; box-shadow: var(--shadow); }
        .stat-value { font-size: 2em; font-weight: bold; margin-bottom: 10px; }
        .stat-label { font-size: 1.1em; opacity: 0.9; }
        .tabs { display: flex; margin-bottom: 20px; gap: 10px; }
        .tab { padding: 10px 20px; background: #e9ecef; border: none; cursor: pointer; border-radius: 50px; font-weight: 600; transition: all 0.3s; }
        .tab.active { background: var(--primary); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .form-section { margin: 30px 0; padding: 20px; background: rgba(201,162,39,0.1); border-radius: 15px; border-left: 5px solid #C9A227; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 12px; transition: all 0.3s ease; }
        .form-group input:focus, .form-group select:focus { border-color: #C9A227; outline: none; box-shadow: 0 0 0 0.2rem rgba(201,162,39,0.25); }
        .btn { background: var(--primary); color: white; padding: 10px 20px; border: none; border-radius: 50px; cursor: pointer; margin: 5px; font-weight: 600; transition: all 0.3s ease; text-decoration: none; display: inline-block; }
        .btn:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
        .btn-danger { background: linear-gradient(135deg, #EF5350 0%, #E53935 100%); }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; border-radius: 15px; overflow: hidden; }
        .table th, .table td { border: 1px solid #e9ecef; padding: 12px; text-align: left; }
        .table th { background: var(--primary); color: white; font-weight: 600; }
        .table tr:nth-child(even) { background: rgba(201,162,39,0.05); }
        .row { display: flex; gap: 15px; }
        .col { flex: 1; }
        .success { color: #00B894; font-weight: bold; padding: 12px; background: rgba(0,184,148,0.1); border-radius: 12px; border-left: 4px solid #00B894; margin: 15px 0; }
        .warning { background: #FFA726; color: white; padding: 5px 10px; border-radius: 50px; font-size: 0.9em; }
        .nav-buttons { text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Lagerverwaltung</h1>
        </div>

        <div class="nav-buttons">
            <a href="web_oberflaeche.php" class="btn">🏠 Hauptmenü</a>
        </div>

        <?php if (isset($success)): ?>
            <p class="success"><?= $success ?></p>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $anzahl_artikel ?></div>
                <div class="stat-label">Artikel im Lager</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $artikel_unter_mindestbestand ?></div>
                <div class="stat-label">Unter Mindestbestand</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($lagerwert, 2, ',', '.') ?> €</div>
                <div class="stat-label">Lagerwert</div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('artikel')">📋 Artikel</button>
            <button class="tab" onclick="showTab('bewegungen')">📊 Bewegungen</button>
            <button class="tab" onclick="showTab('neu')">➕ Neuer Artikel</button>
        </div>

        <div id="artikel" class="tab-content active">
            <h2>📋 Lagerartikel</h2>
            
            <div style="margin-bottom: 20px;">
                <form method="GET" style="display: flex; gap: 10px;">
                    <input type="text" name="search" placeholder="Artikel suchen..." value="<?= htmlspecialchars($search) ?>" style="flex: 1; padding: 10px; border: 2px solid #C9A227; border-radius: 50px;">
                    <button type="submit" class="btn">🔍 Suchen</button>
                    <?php if ($search): ?>
                        <a href="lagerverwaltung.php" class="btn" style="background: #6c757d;">Zurücksetzen</a>
                    <?php endif; ?>
                </form>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Artikelnr.</th>
                        <th>Artikelname</th>
                        <th>Kategorie</th>
                        <th>Bestand</th>
                        <th>Einheit</th>
                        <th>Lagerort</th>
                        <th>EK-Preis</th>
                        <th>VK-Preis</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($artikel as $art): ?>
                    <tr>
                        <td><?= htmlspecialchars($art['artikelnummer']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($art['artikelname']) ?></strong>
                            <?php if ($art['bestand'] <= $art['mindestbestand']): ?>
                                <span class="warning">⚠️ Nachbestellen</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($art['kategorie']) ?></td>
                        <td><strong><?= number_format($art['bestand'], 2) ?></strong></td>
                        <td><?= htmlspecialchars($art['einheit']) ?></td>
                        <td><?= htmlspecialchars($art['lagerort']) ?></td>
                        <td><?= number_format($art['einkaufspreis'], 2, ',', '.') ?> €</td>
                        <td><?= number_format($art['verkaufspreis'], 2, ',', '.') ?> €</td>
                        <td>
                            <a href="lagerverwaltung.php?edit=<?= $art['id'] ?>" class="btn" style="padding: 5px 10px;">✏️</a>
                            <button onclick="showBewegungForm(<?= $art['id'] ?>, '<?= htmlspecialchars($art['artikelname']) ?>')" class="btn" style="padding: 5px 10px;">📦</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="bewegungen" class="tab-content">
            <h2>📊 Lagerbewegungen</h2>
            <?php
            $stmt = $PDO->prepare("SELECT lb.*, la.artikelname, la.artikelnummer FROM lagerbewegungen lb LEFT JOIN lagerartikel la ON lb.artikel_id = la.id ORDER BY lb.erstellt_am DESC LIMIT 50");
            $stmt->execute();
            $bewegungen = $stmt->fetchAll();
            ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Artikel</th>
                        <th>Typ</th>
                        <th>Menge</th>
                        <th>Referenz</th>
                        <th>Bemerkung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bewegungen as $bew): ?>
                    <tr>
                        <td><?= date('d.m.Y H:i', strtotime($bew['erstellt_am'])) ?></td>
                        <td><?= htmlspecialchars($bew['artikelname']) ?></td>
                        <td>
                            <?php if ($bew['bewegungstyp'] === 'Eingang'): ?>
                                <span style="color: green;">➕ Eingang</span>
                            <?php else: ?>
                                <span style="color: red;">➖ Ausgang</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($bew['menge'], 2) ?></td>
                        <td><?= htmlspecialchars($bew['referenz']) ?></td>
                        <td><?= htmlspecialchars($bew['bemerkung']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="neu" class="tab-content">
            <h2><?= $edit_artikel ? 'Artikel bearbeiten' : 'Neuer Artikel' ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="save_artikel">
                <?php if ($edit_artikel): ?>
                    <input type="hidden" name="artikel_id" value="<?= $edit_artikel['id'] ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Artikelnummer *</label>
                            <input type="text" name="artikelnummer" value="<?= htmlspecialchars($edit_artikel['artikelnummer'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Artikelname *</label>
                            <input type="text" name="artikelname" value="<?= htmlspecialchars($edit_artikel['artikelname'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Beschreibung</label>
                    <textarea name="beschreibung" rows="3"><?= htmlspecialchars($edit_artikel['beschreibung'] ?? '') ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Kategorie</label>
                            <input type="text" name="kategorie" value="<?= htmlspecialchars($edit_artikel['kategorie'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Einheit</label>
                            <select name="einheit">
                                <option value="Stück" <?= ($edit_artikel['einheit'] ?? '') === 'Stück' ? 'selected' : '' ?>>Stück</option>
                                <option value="kg" <?= ($edit_artikel['einheit'] ?? '') === 'kg' ? 'selected' : '' ?>>kg</option>
                                <option value="Liter" <?= ($edit_artikel['einheit'] ?? '') === 'Liter' ? 'selected' : '' ?>>Liter</option>
                                <option value="Meter" <?= ($edit_artikel['einheit'] ?? '') === 'Meter' ? 'selected' : '' ?>>Meter</option>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Mindestbestand</label>
                            <input type="number" name="mindestbestand" step="0.01" value="<?= $edit_artikel['mindestbestand'] ?? '10' ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Lagerort</label>
                            <input type="text" name="lagerort" value="<?= htmlspecialchars($edit_artikel['lagerort'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Einkaufspreis (€)</label>
                            <input type="number" name="einkaufspreis" step="0.01" value="<?= $edit_artikel['einkaufspreis'] ?? '0' ?>">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Verkaufspreis (€)</label>
                            <input type="number" name="verkaufspreis" step="0.01" value="<?= $edit_artikel['verkaufspreis'] ?? '0' ?>">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn">💾 Speichern</button>
                <?php if ($edit_artikel): ?>
                    <a href="lagerverwaltung.php" class="btn" style="background: #6c757d;">Abbrechen</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Modal für Lagerbewegung -->
    <div id="bewegungModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 15px; min-width: 500px;">
            <h3 id="modalTitle">Lagerbewegung buchen</h3>
            <form method="POST">
                <input type="hidden" name="action" value="buche_bewegung">
                <input type="hidden" name="artikel_id" id="modal_artikel_id">
                
                <div class="form-group">
                    <label>Bewegungstyp</label>
                    <select name="bewegungstyp" required>
                        <option value="Eingang">➕ Wareneingang</option>
                        <option value="Ausgang">➖ Warenausgang</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Menge</label>
                    <input type="number" name="menge" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>Referenz (z.B. Lieferschein-Nr.)</label>
                    <input type="text" name="referenz">
                </div>
                
                <div class="form-group">
                    <label>Bemerkung</label>
                    <textarea name="bemerkung" rows="2"></textarea>
                </div>
                
                <button type="submit" class="btn">💾 Buchen</button>
                <button type="button" class="btn" style="background: #6c757d;" onclick="document.getElementById('bewegungModal').style.display='none'">Abbrechen</button>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function showBewegungForm(artikelId, artikelName) {
            document.getElementById('modal_artikel_id').value = artikelId;
            document.getElementById('modalTitle').textContent = 'Lagerbewegung: ' + artikelName;
            document.getElementById('bewegungModal').style.display = 'block';
        }
    </script>
</body>
</html>
