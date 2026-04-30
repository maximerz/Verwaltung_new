<?php
session_start();
require_once 'db_connection.php';
require_once 'includes/audit_log.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Filter
$filter_user = $_GET['user'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_table = $_GET['table'] ?? '';
$filter_date = $_GET['date'] ?? '';
$limit = $_GET['limit'] ?? 100;

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
if ($filter_date) {
    $where[] = "DATE(timestamp) = ?";
    $params[] = $filter_date;
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

$stmt = $PDO->query("SELECT username, COUNT(*) as cnt FROM audit_log GROUP BY username ORDER BY cnt DESC LIMIT 10");
$user_stats = $stmt->fetchAll();

$page_title = 'Audit-Log';
include 'includes/header.php';
?>

<div class="dashboard-card">
    <h2 class="section-title mb-4">
        <i class="fas fa-clipboard-list me-2"></i>Audit-Log - Alle Aktivitäten
    </h2>

    <!-- Statistiken -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="alert alert-info">
                <h5><i class="fas fa-chart-bar me-2"></i>Aktionen</h5>
                <?php foreach ($action_stats as $stat): ?>
                    <span class="status-badge badge-info me-2 mb-2">
                        <?= htmlspecialchars($stat['action']) ?>: <?= $stat['cnt'] ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-warning">
                <h5><i class="fas fa-table me-2"></i>Tabellen</h5>
                <?php foreach ($table_stats as $stat): ?>
                    <span class="status-badge badge-warning me-2 mb-2">
                        <?= htmlspecialchars($stat['table_name']) ?>: <?= $stat['cnt'] ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-success">
                <h5><i class="fas fa-users me-2"></i>Top Benutzer</h5>
                <?php foreach ($user_stats as $stat): ?>
                    <span class="status-badge badge-success me-2 mb-2">
                        <?= htmlspecialchars($stat['username']) ?>: <?= $stat['cnt'] ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-2">
            <input type="text" name="user" class="form-control" placeholder="Benutzer..." value="<?= htmlspecialchars($filter_user) ?>">
        </div>
        <div class="col-md-2">
            <select name="action" class="form-select">
                <option value="">Alle Aktionen</option>
                <option value="LOGIN" <?= $filter_action === 'LOGIN' ? 'selected' : '' ?>>Login</option>
                <option value="LOGOUT" <?= $filter_action === 'LOGOUT' ? 'selected' : '' ?>>Logout</option>
                <option value="INSERT" <?= $filter_action === 'INSERT' ? 'selected' : '' ?>>Erstellen</option>
                <option value="UPDATE" <?= $filter_action === 'UPDATE' ? 'selected' : '' ?>>Bearbeiten</option>
                <option value="DELETE" <?= $filter_action === 'DELETE' ? 'selected' : '' ?>>Löschen</option>
                <option value="BACKUP" <?= $filter_action === 'BACKUP' ? 'selected' : '' ?>>Backup</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="table" class="form-select">
                <option value="">Alle Tabellen</option>
                <option value="users" <?= $filter_table === 'users' ? 'selected' : '' ?>>Benutzer</option>
                <option value="kundensystem" <?= $filter_table === 'kundensystem' ? 'selected' : '' ?>>Kunden</option>
                <option value="bestellungen" <?= $filter_table === 'bestellungen' ? 'selected' : '' ?>>Bestellungen</option>
                <option value="lagerartikel" <?= $filter_table === 'lagerartikel' ? 'selected' : '' ?>>Lager</option>
                <option value="api_keys" <?= $filter_table === 'api_keys' ? 'selected' : '' ?>>API-Keys</option>
                <option value="system" <?= $filter_table === 'system' ? 'selected' : '' ?>>System</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
        </div>
        <div class="col-md-2">
            <select name="limit" class="form-select">
                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 Einträge</option>
                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 Einträge</option>
                <option value="500" <?= $limit == 500 ? 'selected' : '' ?>>500 Einträge</option>
                <option value="1000" <?= $limit == 1000 ? 'selected' : '' ?>>1000 Einträge</option>
            </select>
        </div>
        <div class="col-md-2">
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
                        elseif ($log['action'] === 'BACKUP') $badge_class = 'badge-warning';
                        ?>
                        <span class="status-badge <?= $badge_class ?>">
                            <?= htmlspecialchars($log['action']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($log['table_name']) ?></td>
                    <td><?= htmlspecialchars($log['record_id']) ?></td>
                    <td><small><?= htmlspecialchars($log['ip_address']) ?></small></td>
                    <td>
                        <?php if ($log['new_value']): ?>
                            <button class="btn btn-sm btn-info" onclick="alert('<?= htmlspecialchars(substr($log['new_value'], 0, 100)) ?>')">
                                <i class="fas fa-eye"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="alert alert-info mt-3">
        <strong>Angezeigt:</strong> <?= count($logs) ?> von insgesamt <?= $PDO->query("SELECT COUNT(*) FROM audit_log")->fetchColumn() ?> Einträgen
    </div>
</div>

<?php include 'includes/footer.php'; ?>
