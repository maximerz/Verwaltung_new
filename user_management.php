<?php
session_start();
require_once 'db_connection.php';
require_once 'includes/2fa_functions.php';

// Initialize 2FA system settings and columns
init_2fa_system_settings($PDO);
add_2fa_user_columns($PDO);

// Check if Admin is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';

// Handle system-wide 2FA mandate
if (isset($_POST['toggle_system_2fa'])) {
    $current_setting = $_POST['current_setting'] ?? '0';
    $new_value = ($current_setting == '1') ? '0' : '1';
    
    $stmt = $PDO->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = '2fa_mandatory'");
    $stmt->execute([$new_value]);
    
    $message = $new_value == '1' ? "2FA wird jetzt für alle Benutzer erzwungen!" : "2FA-Pflicht für alle Benutzer deaktiviert!";
}

// User edit
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
        } elseif ($action === 'toggle_2fa_mandatory') {
            $stmt = $PDO->prepare("UPDATE users SET two_factor_mandatory = CASE WHEN two_factor_mandatory = 1 THEN 0 ELSE 1 END WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = "2FA-Pflicht für Benutzer geändert!";
        } elseif ($action === 'delete') {
            $stmt = $PDO->prepare("DELETE FROM users WHERE id = ? AND username != 'admin'");
            $stmt->execute([$user_id]);
            $message = "Benutzer gelöscht!";
        } elseif ($action === 'reset_2fa_remember') {
            $stmt = $PDO->prepare("UPDATE users SET two_factor_remember_token = NULL, two_factor_remember_expires = NULL WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = "2FA-Erinnerung zurückgesetzt! Benutzer muss sich erneut verifizieren.";
        }
    }
}

// Get system 2FA setting
$stmt = $PDO->prepare("SELECT setting_value FROM system_settings WHERE setting_key = '2fa_mandatory'");
$stmt->execute();
$system_2fa_setting = $stmt->fetch();
$system_2fa_mandatory = ($system_2fa_setting && $system_2fa_setting['setting_value'] == '1');

// Load all users
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

            <!-- System-wide 2FA Toggle -->
            <div class="settings-card mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><i class="fas fa-shield-alt me-2"></i>2FA-Pflicht systemweit</h5>
                        <p class="text-muted mb-0">Wenn aktiviert, müssen alle Benutzer 2FA verwenden</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="current_setting" value="<?php echo $system_2fa_mandatory ? '1' : '0'; ?>">
                        <button type="submit" name="toggle_system_2fa" class="btn <?php echo $system_2fa_mandatory ? 'btn-danger' : 'btn-success'; ?>">
                            <i class="fas fa-<?php echo $system_2fa_mandatory ? 'times' : 'check'; ?> me-2"></i>
                            <?php echo $system_2fa_mandatory ? 'Deaktivieren' : 'Aktivieren'; ?>
                        </button>
                    </form>
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
                            <th><i class="fas fa-mobile-alt"></i> 2FA Status</th>
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
<span class="badge <?php 
$role_class = match($user['role']) {
    'admin' => 'bg-danger',
    'template_editor' => 'bg-warning text-dark',
    default => 'bg-primary'
};
echo $role_class; 
?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['two_factor_enabled'] == 1): ?>
                                        <span class="badge bg-success" title="2FA aktiviert">
                                            <i class="fas fa-check-circle me-1"></i> Aktiv
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary" title="2FA nicht aktiviert">
                                            <i class="fas fa-times-circle me-1"></i> Inaktiv
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['two_factor_mandatory'] == 1): ?>
                                        <span class="badge bg-warning text-dark" title="2FA vom Admin erzwungen">
                                            <i class="fas fa-lock me-1"></i> Pflicht
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($user['two_factor_last_verified'])): ?>
                                        <?php 
                                            $last_verified = strtotime($user['two_factor_last_verified']);
                                            $days_since = (time() - $last_verified) / (24 * 60 * 60);
                                        ?>
                                        <?php if ($days_since > 30): ?>
                                            <span class="badge bg-danger" title="2FA muss erneuert werden">
                                                <i class="fas fa-exclamation-triangle me-1"></i> Erneuern
                                            </span>
                                        <?php else: ?>
                                            <small class="text-muted d-block" title="Zuletzt verifiziert: <?php echo date('d.m.Y H:i', $last_verified); ?>">
                                                ✓ <?php echo round(30 - $days_since); ?> Tage gültig
                                            </small>
                                        <?php endif; ?>
                                    <?php endif; ?>
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
                                                <option value="template_editor" <?php echo $user['role'] === 'template_editor' ? 'selected' : ''; ?>>📄 Template Editor</option>
                                            </select>
                                            <input type="hidden" name="action" value="change_role">
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" class="me-1">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="action" value="toggle_user_management" class="btn btn-warning btn-sm">
                                                <i class="fas fa-key"></i> Berechtigung
                                            </button>
                                        </form>
                                        
                                        <?php if ($user['two_factor_enabled'] == 1 || $system_2fa_mandatory): ?>
                                            <form method="POST" style="display: inline;" class="me-1">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="action" value="toggle_2fa_mandatory" class="btn btn-<?php echo $user['two_factor_mandatory'] ? 'danger' : 'info'; ?> btn-sm" title="2FA-Pflicht erzwingen">
                                                    <i class="fas fa-shield-alt"></i> <?php echo $user['two_factor_mandatory'] ? 'Pflicht aus' : 'Pflicht an'; ?>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;" class="me-1">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="action" value="reset_2fa_remember" class="btn btn-secondary btn-sm" title="2FA-Erinnerung zurücksetzen">
                                                    <i class="fas fa-clock"></i> Reset
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
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