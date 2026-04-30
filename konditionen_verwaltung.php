<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Kondition hinzufügen
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = $_POST['name'] ?? '';
    $beschreibung = $_POST['beschreibung'] ?? '';
    $rabatt_prozent = $_POST['rabatt_prozent'] ?? 0;
    
    if (!empty($name)) {
        $stmt = $PDO->prepare("INSERT INTO konditionen_vorlagen (name, beschreibung, rabatt_prozent) VALUES (?, ?, ?)");
        $stmt->execute([$name, $beschreibung, $rabatt_prozent]);
        $success = "Kondition erfolgreich hinzugefügt!";
    }
}

// Kondition löschen
if (isset($_GET['delete'])) {
    $stmt = $PDO->prepare("DELETE FROM konditionen_vorlagen WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $success = "Kondition erfolgreich gelöscht!";
}

// Alle Konditionen laden
$stmt = $PDO->prepare("SELECT * FROM konditionen_vorlagen ORDER BY name");
$stmt->execute();
$konditionen = $stmt->fetchAll();
?>
<?php $page_title = 'Konditionen-Verwaltung'; include 'includes/header.php'; ?>
<?php include 'includes/table-style.php'; ?>

<div class="dashboard-card">
    <h2 class="section-title mb-4">
        <i class="fas fa-handshake me-2"></i>Konditionen-Verwaltung
    </h2>

    <div class="mb-4">
        <a href="web_oberflaeche.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Zurück zur Übersicht</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success mb-4"><?= $success ?></div>
    <?php endif; ?>

    <!-- Neue Kondition hinzufügen -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Neue Kondition hinzufügen</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" placeholder="z.B. ServicePlus, Event-Rabatt" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rabatt (%)</label>
                        <input type="number" class="form-control" name="rabatt_prozent" value="0" step="0.01" min="0" max="100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Beschreibung</label>
                        <input type="text" class="form-control" name="beschreibung" placeholder="Optionale Beschreibung">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-plus me-2"></i>Hinzufügen</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bestehende Konditionen -->
    <h4 class="mb-3"><i class="fas fa-list me-2"></i>Bestehende Konditionen</h4>
    
    <?php if ($konditionen): ?>
        <div class="modern-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Beschreibung</th>
                        <th>Rabatt</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($konditionen as $k): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($k['name']) ?></strong></td>
                        <td><?= htmlspecialchars($k['beschreibung'] ?? '-') ?></td>
                        <td><?= $k['rabatt_prozent'] > 0 ? $k['rabatt_prozent'] . '%' : '-' ?></td>
                        <td>
                            <a href="konditionen_verwaltung.php?delete=<?= $k['id'] ?>" class="action-btn btn-danger-modern" onclick="return confirm('Kondition wirklich löschen?')">
                                <i class="fas fa-trash me-1"></i>Löschen
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted">Noch keine Konditionen vorhanden.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

