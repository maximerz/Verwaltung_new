<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Filter
$filter_user = $_GET['user'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_table = $_GET['table'] ?? '';
$limit = $_GET['limit'] ?? 50;

// Query bauen
$where = [];
$params = [];

if ($filter_user) {
    $where[] = "username LIKE ?";
    $params[] = "%$filter_user%";
}
if ($filter_action) {
    $where[] = "action = ?";
    $params[] = $filter_action;
}
if ($filter_table) {
    $where[] = "table_name = ?";
    $params[] = $filter_table;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $PDO->prepare("SELECT * FROM audit_log $where_sql ORDER BY timestamp DESC LIMIT ?");
$params[] = (int)$limit;
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Statistiken
$stmt = $PDO->query("SELECT action, COUNT(*) as cnt FROM audit_log GROUP BY action ORDER BY cnt DESC");
$action_stats = $stmt->fetchAll();

$stmt = $PDO->query("SELECT table_name, COUNT(*) as cnt FROM audit_log GROUP BY table_name ORDER BY cnt DESC");
$table_stats = $stmt->fetchAll();

$page_title = 'Audit-Log';
include 'includes/header.php';
?>

<div class="dashboard-card">
    <h2 class="section-title mb-4">
        <i class="fas fa-clipboard-list me-2"></i>Audit-Log - Alle Aktivit\u00e4ten
    </h2>

    <!-- Statistiken -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="alert alert-info">
                <h5><i class="fas fa-chart-bar me-2"></i>Aktionen</h5>
                <?php foreach ($action_stats as $stat): ?>
                    <span class="status-badge badge-info me-2 mb-2">
                        <?= htmlspecialchars($stat['action']) ?>: <?= $stat['cnt'] ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="alert alert-warning">
                <h5><i class="fas fa-table me-2"></i>Tabellen</h5>
                <?php foreach ($table_stats as $stat): ?>
                    <span class="status-badge badge-warning me-2 mb-2">
                        <?= htmlspecialchars($stat['table_name']) ?>: <?= $stat['cnt'] ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <input type="text" name="user" class="form-control" placeholder="Benutzer..." value="<?= htmlspecialchars($filter_user) ?>">
        </div>
        <div class="col-md-3">
            <select name="action" class="form-select">
                <option value="">Alle Aktionen</option>
                <option value="LOGIN" <?= $filter_action === 'LOGIN' ? 'selected' : '' ?>>Login</option>
                <option value="LOGOUT" <?= $filter_action === 'LOGOUT' ? 'selected' : '' ?>>Logout</option>
                <option value="INSERT" <?= $filter_action === 'INSERT' ? 'selected' : '' ?>>Erstellen</option>
                <option value="UPDATE" <?= $filter_action === 'UPDATE' ? 'selected' : '' ?>>Bearbeiten</option>
                <option value="DELETE" <?= $filter_action === 'DELETE' ? 'selected' : '' ?>>L\u00f6schen</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="table" class="form-select">
                <option value="">Alle Tabellen</option>
                <option value="users" <?= $filter_table === 'users' ? 'selected' : '' ?>>Benutzer</option>
                <option value="kundensystem" <?= $filter_table === 'kundensystem' ? 'selected' : '' ?>>Kunden</option>
                <option value="bestellungen" <?= $filter_table === 'bestellungen' ? 'selected' : '' ?>>Bestellungen</option>
                <option value="lagerartikel" <?= $filter_table === 'lagerartikel' ? 'selected' : '' ?>>Lager</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn-primary-modern action-btn w-100">
                <i class="fas fa-filter me-1"></i>Filtern
            </button>
        </div>
    </form>

    <!-- Logs -->
    <div class="modern-table">
        <table class="table">
            <thead>
                <tr>
                    <th>Zeit</th>
                    <th>Benutzer</th>
                    <th>Aktion</th>
                    <th>Tabelle</th>
                    <th>ID</th>
                    <th>IP</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><small><?= date('d.m.Y H:i:s', strtotime($log['timestamp'])) ?></small></td>
                    <td><strong><?= htmlspecialchars($log['username']) ?></strong></td>
                    <td>
                        <?php
                        $badge_class = 'badge-info';
                        if ($log['action'] === 'DELETE') $badge_class = 'badge-danger';
                        elseif ($log['action'] === 'INSERT') $badge_class = 'badge-success';
                        elseif ($log['action'] === 'LOGIN') $badge_class = 'badge-primary';
                        ?>
                        <span class="status-badge <?= $badge_class ?>">
                            <?= htmlspecialchars($log['action']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($log['table_name']) ?></td>
                    <td><?= htmlspecialchars($log['record_id']) ?></td>
                    <td><small><?= htmlspecialchars($log['ip_address']) ?></small></td>
                    <td>
                        <?php if ($log['old_value'] || $log['new_value']): ?>
                            <button class="btn btn-sm btn-info" onclick="showDetails(<?= $log['id'] ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function showDetails(logId) {
    // Hier k\u00f6nntest du ein Modal mit Details \u00f6ffnen
    alert('Details f\u00fcr Log-ID: ' + logId);
}
</script>

<?php include 'includes/footer.php'; ?>
