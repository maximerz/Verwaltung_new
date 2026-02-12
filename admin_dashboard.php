<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: login.php");
    exit();
}

// Backup erstellen
if (isset($_POST['create_backup'])) {
    exec('bash ' . __DIR__ . '/backup.sh 2>&1', $output, $return);
    $backup_message = $return === 0 ? "Backup erfolgreich erstellt!" : "Backup fehlgeschlagen!";
}

// Statistiken
$stats = [];
$stmt = $PDO->query("SELECT COUNT(*) as cnt FROM audit_log");
$stats['audit_logs'] = $stmt->fetch()['cnt'];

$stmt = $PDO->query("SELECT COUNT(*) as cnt FROM api_keys WHERE active = 1");
$stats['api_keys'] = $stmt->fetch()['cnt'];

$stmt = $PDO->query("SELECT COUNT(*) as cnt FROM users");
$stats['users'] = $stmt->fetch()['cnt'];

// Letzte Audit-Logs
$stmt = $PDO->query("SELECT * FROM audit_log ORDER BY timestamp DESC LIMIT 10");
$recent_logs = $stmt->fetchAll();

// Backups
$backups = [];
if (is_dir(__DIR__ . '/backups')) {
    $files = glob(__DIR__ . '/backups/backup_*.db.gz');
    rsort($files);
    $backups = array_slice($files, 0, 5);
}

$page_title = 'Admin Dashboard';
include 'includes/header.php';
?>

<div class="dashboard-card">
    <h2 class="section-title mb-4">
        <i class="fas fa-shield-alt me-2"></i>Admin Dashboard
    </h2>

    <?php if (isset($backup_message)): ?>
        <div class="alert alert-success"><?= $backup_message ?></div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-3">
            <a href="user_management.php" class="text-decoration-none">
                <div class="stat-card" style="background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%);">
                    <div class="stat-number"><?= $stats['users'] ?></div>
                    <div class="stat-label"><i class="fas fa-users me-2"></i>Benutzer</div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="api_verwaltung.php" class="text-decoration-none">
                <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="stat-number"><?= $stats['api_keys'] ?></div>
                    <div class="stat-label"><i class="fas fa-key me-2"></i>API-Keys</div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="#audit-log" class="text-decoration-none">
                <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <div class="stat-number"><?= $stats['audit_logs'] ?></div>
                    <div class="stat-label"><i class="fas fa-clipboard-list me-2"></i>Audit-Logs</div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="system_test.php" target="_blank" class="text-decoration-none">
                <div class="stat-card" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                    <div class="stat-number"><i class="fas fa-vial"></i></div>
                    <div class="stat-label"><i class="fas fa-check-circle me-2"></i>System-Test</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Admin Tools -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="dashboard-card">
                <h4 class="mb-3"><i class="fas fa-database me-2"></i>Backup-Verwaltung</h4>
                
                <form method="POST" class="mb-3">
                    <button type="submit" name="create_backup" class="btn-success-modern action-btn w-100">
                        <i class="fas fa-save me-2"></i>Jetzt Backup erstellen
                    </button>
                </form>

                <h5 class="mb-2">Letzte Backups:</h5>
                <?php if (!empty($backups)): ?>
                    <div class="list-group">
                        <?php foreach ($backups as $backup): ?>
                            <?php 
                            $filename = basename($backup);
                            $size = filesize($backup);
                            $date = filemtime($backup);
                            ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-file-archive me-2"></i>
                                    <small><?= $filename ?></small>
                                </div>
                                <div>
                                    <span class="badge bg-secondary"><?= round($size/1024, 1) ?> KB</span>
                                    <small class="text-muted ms-2"><?= date('d.m.Y H:i', $date) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Keine Backups vorhanden</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-6">
            <div class="dashboard-card">
                <h4 class="mb-3"><i class="fas fa-tools me-2"></i>System-Tools</h4>
                
                <div class="d-grid gap-2">
                    <a href="api_verwaltung.php" class="action-btn btn-primary-modern">
                        <i class="fas fa-key me-2"></i>API-Keys verwalten
                    </a>
                    <a href="audit_log_viewer.php" class="action-btn btn-info">
                        <i class="fas fa-clipboard-list me-2"></i>Audit-Log anzeigen
                    </a>
                    <a href="system_test.php" target="_blank" class="action-btn btn-success-modern">
                        <i class="fas fa-vial me-2"></i>System-Tests ausführen
                    </a>
                    <a href="user_management.php" class="action-btn btn-warning-modern">
                        <i class="fas fa-users-cog me-2"></i>Benutzerverwaltung
                    </a>
                    <a href="dsgvo_verwaltung.php" class="action-btn" style="background: #64748b; color: white;">
                        <i class="fas fa-shield-alt me-2"></i>DSGVO-Verwaltung
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Log -->
    <div class="dashboard-card" id="audit-log">
        <h4 class="mb-3"><i class="fas fa-history me-2"></i>Letzte Aktivitäten (Audit-Log)</h4>
        
        <?php if (!empty($recent_logs)): ?>
            <div class="modern-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Zeitstempel</th>
                            <th>Benutzer</th>
                            <th>Aktion</th>
                            <th>Tabelle</th>
                            <th>Datensatz-ID</th>
                            <th>IP-Adresse</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                        <tr>
                            <td><?= date('d.m.Y H:i:s', strtotime($log['timestamp'])) ?></td>
                            <td><strong><?= htmlspecialchars($log['username']) ?></strong></td>
                            <td>
                                <span class="status-badge badge-info">
                                    <?= htmlspecialchars($log['action']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($log['table_name']) ?></td>
                            <td><?= htmlspecialchars($log['record_id']) ?></td>
                            <td><small><?= htmlspecialchars($log['ip_address']) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">Keine Audit-Logs vorhanden</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
