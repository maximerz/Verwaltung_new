<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Tabelle erstellen
$PDO->exec("CREATE TABLE IF NOT EXISTS api_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    api_key TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Neuen API-Key erstellen
if (isset($_POST['create_key'])) {
    $name = $_POST['name'];
    $api_key = bin2hex(random_bytes(32));
    
    $stmt = $PDO->prepare("INSERT INTO api_keys (api_key, name) VALUES (?, ?)");
    $stmt->execute([$api_key, $name]);
    $success = "API-Key erstellt: $api_key";
}

// API-Key deaktivieren
if (isset($_GET['deactivate'])) {
    $stmt = $PDO->prepare("UPDATE api_keys SET active = 0 WHERE id = ?");
    $stmt->execute([$_GET['deactivate']]);
    $success = "API-Key deaktiviert!";
}

// Alle Keys laden
$stmt = $PDO->query("SELECT * FROM api_keys ORDER BY created_at DESC");
$api_keys = $stmt->fetchAll();

$page_title = 'API-Verwaltung';
include 'includes/header.php';
?>

<div class="dashboard-card">
    <h2 class="section-title mb-4">
        <i class="fas fa-key me-2"></i>API-Verwaltung
    </h2>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="alert alert-info mb-4">
        <h5><i class="fas fa-info-circle me-2"></i>API-Endpunkte</h5>
        <ul class="mb-0">
            <li><code>GET /api/index.php?api_key=YOUR_KEY</code> - Kunden abrufen</li>
            <li><code>GET /api/index.php?api_key=YOUR_KEY</code> - Bestellungen abrufen</li>
            <li><code>GET /api/index.php?api_key=YOUR_KEY</code> - Lagerbestand abrufen</li>
            <li><code>GET /api/index.php?api_key=YOUR_KEY</code> - Statistiken abrufen</li>
        </ul>
    </div>

    <form method="POST" class="mb-4">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Name/Beschreibung</label>
                <input type="text" name="name" class="form-control" placeholder="z.B. E-Commerce Integration" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" name="create_key" class="btn-success-modern action-btn w-100">
                    <i class="fas fa-plus me-1"></i>API-Key erstellen
                </button>
            </div>
        </div>
    </form>

    <div class="modern-table">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>API-Key</th>
                    <th>Status</th>
                    <th>Erstellt</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($api_keys as $key): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($key['name']) ?></strong></td>
                    <td><code><?= htmlspecialchars($key['api_key']) ?></code></td>
                    <td>
                        <?php if ($key['active']): ?>
                            <span class="status-badge badge-success">Aktiv</span>
                        <?php else: ?>
                            <span class="status-badge" style="background: rgba(239,68,68,0.15); color: #ef4444;">Inaktiv</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d.m.Y H:i', strtotime($key['created_at'])) ?></td>
                    <td>
                        <?php if ($key['active']): ?>
                            <a href="?deactivate=<?= $key['id'] ?>" class="action-btn btn-danger-modern" onclick="return confirm('API-Key deaktivieren?')">
                                <i class="fas fa-ban me-1"></i>Deaktivieren
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
