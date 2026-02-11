<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Produkt hinzufügen/bearbeiten
if ($_POST) {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'];
    $beschreibung = $_POST['beschreibung'];
    $preis = $_POST['preis'];
    $kategorie = $_POST['kategorie'];
    
    if ($id) {
        $stmt = $PDO->prepare("UPDATE produkte SET name = ?, beschreibung = ?, preis = ?, kategorie = ? WHERE id = ?");
        $stmt->execute([$name, $beschreibung, $preis, $kategorie, $id]);
        $success = "Produkt aktualisiert!";
    } else {
        $stmt = $PDO->prepare("INSERT INTO produkte (name, beschreibung, preis, kategorie) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $beschreibung, $preis, $kategorie]);
        $success = "Produkt hinzugefügt!";
    }
}

// Produkt löschen
if (isset($_GET['delete'])) {
    $stmt = $PDO->prepare("DELETE FROM produkte WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $success = "Produkt gelöscht!";
}

// Produkte laden
$search = $_GET['search'] ?? '';
$where = $search ? "WHERE name LIKE ? OR beschreibung LIKE ? OR kategorie LIKE ?" : "";
$params = $search ? ["%$search%", "%$search%", "%$search%"] : [];

$stmt = $PDO->prepare("SELECT * FROM produkte $where ORDER BY kategorie, name");
$stmt->execute($params);
$produkte = $stmt->fetchAll();

// Produkt zum Bearbeiten laden
$edit_produkt = null;
if (isset($_GET['edit'])) {
    $stmt = $PDO->prepare("SELECT * FROM produkte WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_produkt = $stmt->fetch();
}

// Statistiken
$stmt = $PDO->query("SELECT COUNT(*) as anzahl FROM produkte");
$anzahl_produkte = $stmt->fetch()['anzahl'];

$stmt = $PDO->query("SELECT COUNT(DISTINCT kategorie) as anzahl FROM produkte WHERE kategorie IS NOT NULL AND kategorie != ''");
$anzahl_kategorien = $stmt->fetch()['anzahl'];

$stmt = $PDO->query("SELECT AVG(preis) as durchschnitt FROM produkte");
$durchschnittspreis = $stmt->fetch()['durchschnitt'] ?? 0;

$page_title = 'Produktkatalog';
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
        <div class="stat-number"><?= $anzahl_produkte ?></div>
        <div class="stat-label"><i class="fas fa-box me-2"></i>Produkte</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $anzahl_kategorien ?></div>
        <div class="stat-label"><i class="fas fa-tags me-2"></i>Kategorien</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= number_format($durchschnittspreis, 2) ?> €</div>
        <div class="stat-label"><i class="fas fa-euro-sign me-2"></i>Ø Preis</div>
    </div>
</div>

<!-- Produkt Form -->
<div class="dashboard-card">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-<?= $edit_produkt ? 'edit' : 'plus' ?> me-2"></i>
            <?= $edit_produkt ? 'Produkt bearbeiten' : 'Neues Produkt' ?>
        </h2>
    </div>
    
    <form method="POST" class="row g-3">
        <?php if ($edit_produkt): ?>
            <input type="hidden" name="id" value="<?= $edit_produkt['id'] ?>">
        <?php endif; ?>
        
        <div class="col-md-6">
            <label class="form-label"><i class="fas fa-tag me-1"></i>Produktname</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($edit_produkt['name'] ?? '') ?>" required>
        </div>
        
        <div class="col-md-3">
            <label class="form-label"><i class="fas fa-euro-sign me-1"></i>Preis</label>
            <input type="number" name="preis" class="form-control" step="0.01" value="<?= $edit_produkt['preis'] ?? '' ?>" required>
        </div>
        
        <div class="col-md-3">
            <label class="form-label"><i class="fas fa-folder me-1"></i>Kategorie</label>
            <input type="text" name="kategorie" class="form-control" value="<?= htmlspecialchars($edit_produkt['kategorie'] ?? '') ?>" placeholder="z.B. Hardware">
        </div>
        
        <div class="col-12">
            <label class="form-label"><i class="fas fa-align-left me-1"></i>Beschreibung</label>
            <textarea name="beschreibung" class="form-control" rows="3"><?= htmlspecialchars($edit_produkt['beschreibung'] ?? '') ?></textarea>
        </div>
        
        <div class="col-12">
            <button type="submit" class="btn-primary-modern action-btn">
                <i class="fas fa-save me-1"></i><?= $edit_produkt ? 'Aktualisieren' : 'Hinzufügen' ?>
            </button>
            <?php if ($edit_produkt): ?>
                <a href="produktkatalog.php" class="action-btn" style="background: #6c757d; color: white;">
                    <i class="fas fa-times me-1"></i>Abbrechen
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Produktliste -->
<div class="dashboard-card">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-list me-2"></i>Produktliste
        </h2>
        <div class="search-container">
            <form method="GET" class="d-flex">
                <input type="text" name="search" class="form-control search-input" placeholder="Produkt suchen..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>
    </div>
    
    <div class="modern-table">
        <table class="table">
            <thead>
                <tr>
                    <th><i class="fas fa-tag me-1"></i>Name</th>
                    <th><i class="fas fa-align-left me-1"></i>Beschreibung</th>
                    <th><i class="fas fa-euro-sign me-1"></i>Preis</th>
                    <th><i class="fas fa-folder me-1"></i>Kategorie</th>
                    <th><i class="fas fa-cogs me-1"></i>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($produkte) > 0): ?>
                    <?php foreach ($produkte as $produkt): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($produkt['name']) ?></strong></td>
                        <td><?= htmlspecialchars($produkt['beschreibung']) ?></td>
                        <td><span class="fw-bold text-success"><?= number_format($produkt['preis'], 2, ',', '.') ?> €</span></td>
                        <td><span class="status-badge badge-info"><?= htmlspecialchars($produkt['kategorie']) ?></span></td>
                        <td>
                            <a href="produktkatalog.php?edit=<?= $produkt['id'] ?>" class="action-btn btn-warning-modern">
                                <i class="fas fa-edit me-1"></i>Bearbeiten
                            </a>
                            <a href="produktkatalog.php?delete=<?= $produkt['id'] ?>" class="action-btn btn-danger-modern" onclick="return confirm('Produkt wirklich löschen?')">
                                <i class="fas fa-trash me-1"></i>Löschen
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted"><?= $search ? 'Keine Produkte gefunden für: "' . htmlspecialchars($search) . '"' : 'Keine Produkte vorhanden.' ?></p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>