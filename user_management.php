<?php
session_start();
require_once 'db_connection.php';

// Prüfen ob Admin eingeloggt ist
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';

// Benutzer bearbeiten
if ($_POST) {
    $user_id = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    if ($user_id && $action) {
        if ($action === 'toggle_user_management') {
            $stmt = $PDO->prepare("UPDATE users SET can_manage_users = CASE WHEN can_manage_users = 1 THEN 0 ELSE 1 END WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = "Berechtigung geändert!";
        } elseif ($action === 'change_role') {
            $new_role = $_POST['new_role'] ?? 'user';
            $stmt = $PDO->prepare("UPDATE users SET role = ? WHERE id = ? AND username != 'admin'");
            $stmt->execute([$new_role, $user_id]);
            $message = "Rolle geändert!";
        } elseif ($action === 'delete') {
            $stmt = $PDO->prepare("DELETE FROM users WHERE id = ? AND username != 'admin'");
            $stmt->execute([$user_id]);
            $message = "Benutzer gelöscht!";
        }
    }
}

// Alle Benutzer laden
$stmt = $PDO->prepare("SELECT * FROM users ORDER BY username");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $page_title = 'ERP System'; include 'includes/header.php'; ?><div class="container">
<?php include 'includes/table-style.php'; ?>
        <div class="container-main">
            <div class="header-title">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-user-cog"></i> Benutzerverwaltung</h1>
                        <p class="mb-0">Berechtigungen und Benutzer verwalten</p>
                    </div>
                    <div>
                        <a href="web_oberflaeche.php" class="btn btn-light me-2">
                            <i class="fas fa-arrow-left"></i> Zurück
                        </a>
                        <a href="admin_requests.php" class="btn btn-light me-2">
                            <i class="fas fa-clipboard-list"></i> Anfragen
                        </a>
                        <a href="api_verwaltung.php" class="btn btn-light me-2">
                            <i class="fas fa-key"></i> API
                        </a>
                        <a href="system_test.php" class="btn btn-light me-2" target="_blank">
                            <i class="fas fa-vial"></i> Tests
                        </a>
                        <a href="ldap_config.php" class="btn btn-light">
                            <i class="fas fa-server"></i> LDAP
                        </a>
                    </div>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Benutzername</th>
                            <th><i class="fas fa-shield-alt"></i> Rolle</th>
                            <th><i class="fas fa-users-cog"></i> Benutzerverwaltung</th>
                            <th><i class="fas fa-calendar"></i> Erstellt am</th>
                            <th><i class="fas fa-tools"></i> Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $user['can_manage_users'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $user['can_manage_users'] ? 'Ja' : 'Nein'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['username'] !== 'admin'): ?>
                                        <form method="POST" style="display: inline;" class="me-2">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="new_role" class="form-select form-select-sm d-inline-block" style="width: auto; display: inline-block;" onchange="this.form.submit()">
                                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>👤 User</option>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>🔒 Admin</option>
                                            </select>
                                            <input type="hidden" name="action" value="change_role">
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" class="me-1">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="action" value="toggle_user_management" class="btn btn-warning btn-sm">
                                                <i class="fas fa-key"></i> Berechtigung
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Benutzer wirklich löschen?')">
                                                <i class="fas fa-trash"></i> Löschen
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Admin-Account</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>