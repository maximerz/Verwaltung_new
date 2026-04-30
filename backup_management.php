<?php
session_start();
require_once 'db_connection.php';
require_once 'includes/audit_log.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header('Location: login.php');
    exit();
}

$backup_dir = __DIR__ . '/backups';
$backup_script = __DIR__ . '/scripts/backup.sh';
$message = '';
$message_type = 'success';

function is_valid_backup_name(string $filename): bool
{
    return (bool) preg_match('/^backup_\d{8}_\d{6}\.db\.gz$/', $filename);
}

function get_backups(string $backup_dir): array
{
    if (!is_dir($backup_dir)) {
        return [];
    }

    $files = glob($backup_dir . '/backup_*.db.gz');
    rsort($files);

    return array_map(static function ($file) {
        return [
            'path' => $file,
            'name' => basename($file),
            'size' => filesize($file) ?: 0,
            'modified_at' => filemtime($file) ?: 0,
        ];
    }, $files ?: []);
}

if (isset($_GET['download'])) {
    $filename = basename((string) $_GET['download']);
    $filepath = $backup_dir . '/' . $filename;

    if (!is_valid_backup_name($filename) || !is_file($filepath)) {
        http_response_code(404);
        exit('Backup nicht gefunden.');
    }

    audit_log($PDO, 'BACKUP_DOWNLOAD', 'system', 0, null, ['file' => $filename]);

    header('Content-Description: File Transfer');
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    readfile($filepath);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_backup'])) {
        $output = [];
        $return = 1;
        exec('bash ' . escapeshellarg($backup_script) . ' 2>&1', $output, $return);
        if ($return === 0) {
            $message = 'Backup erfolgreich erstellt.';
            $message_type = 'success';
            audit_log($PDO, 'BACKUP', 'system', 0, null, ['status' => 'success']);
        } else {
            $message = 'Backup fehlgeschlagen: ' . implode(' | ', $output);
            $message_type = 'danger';
            audit_log($PDO, 'BACKUP', 'system', 0, null, ['status' => 'failed']);
        }
    }

    if (isset($_POST['delete_backup'])) {
        $filename = basename((string) $_POST['delete_backup']);
        $filepath = $backup_dir . '/' . $filename;

        if (!is_valid_backup_name($filename) || !is_file($filepath)) {
            $message = 'Backup-Datei nicht gefunden.';
            $message_type = 'danger';
        } elseif (unlink($filepath)) {
            $message = 'Backup gelöscht: ' . $filename;
            $message_type = 'success';
            audit_log($PDO, 'BACKUP_DELETE', 'system', 0, null, ['file' => $filename]);
        } else {
            $message = 'Backup konnte nicht gelöscht werden.';
            $message_type = 'danger';
        }
    }
}

$backups = get_backups($backup_dir);
$backup_count = count($backups);
$backup_total_size = array_sum(array_column($backups, 'size'));

$page_title = 'Backup-Verwaltung';
include 'includes/header.php';
?>

<div class="dashboard-card">
    <div class="section-header">
        <div>
            <h2 class="section-title"><i class="fas fa-database"></i>Backup-Verwaltung</h2>
            <p class="text-muted mb-0">Backups erstellen, herunterladen und bei Bedarf wieder entfernen.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i>Admin</a>
            <form method="POST">
                <button type="submit" name="create_backup" class="btn btn-primary">
                    <i class="fas fa-save"></i>Neues Backup
                </button>
            </form>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?> mb-4"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="dashboard-card">
        <div class="section-header">
            <div>
                <h4 class="mb-2"><i class="fas fa-plus-circle me-2"></i>Neues Backup erstellen</h4>
                <p class="text-muted mb-0">Erstellt sofort ein komprimiertes Datenbank-Backup im Backup-Ordner.</p>
            </div>
            <form method="POST">
                <button type="submit" name="create_backup" class="btn btn-primary">
                    <i class="fas fa-save"></i>Backup jetzt erstellen
                </button>
            </form>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $backup_count ?></div>
            <div class="stat-label"><i class="fas fa-box-archive"></i>Backups</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($backup_total_size / 1024 / 1024, 2, ',', '.') ?> MB</div>
            <div class="stat-label"><i class="fas fa-hard-drive"></i>Gesamtgröße</div>
        </div>
    </div>

    <div class="modern-table">
        <table class="table">
            <thead>
                <tr>
                    <th>Datei</th>
                    <th>Erstellt</th>
                    <th>Größe</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($backups === []): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                            <span class="text-muted">Noch keine Backups vorhanden.</span>
                            <form method="POST" class="mt-4">
                                <button type="submit" name="create_backup" class="btn btn-primary">
                                    <i class="fas fa-save"></i>Erstes Backup erstellen
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($backup['name']) ?></strong>
                            </td>
                            <td><?= date('d.m.Y H:i:s', $backup['modified_at']) ?></td>
                            <td><?= number_format($backup['size'] / 1024, 1, ',', '.') ?> KB</td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="backup_management.php?download=<?= rawurlencode($backup['name']) ?>" class="action-btn btn-primary-modern">
                                        <i class="fas fa-download"></i>Download
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Backup wirklich löschen?');">
                                        <button type="submit" name="delete_backup" value="<?= htmlspecialchars($backup['name']) ?>" class="action-btn btn-danger-modern">
                                            <i class="fas fa-trash"></i>Löschen
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
