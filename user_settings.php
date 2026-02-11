<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Tabelle für 2FA erweitern
try {
    $PDO->exec("ALTER TABLE users ADD COLUMN two_factor_secret TEXT");
    $PDO->exec("ALTER TABLE users ADD COLUMN two_factor_enabled INTEGER DEFAULT 0");
} catch (Exception $e) {}

// User-Daten laden
$stmt = $PDO->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Passwort ändern
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if (password_verify($current, $user['password'])) {
        if ($new === $confirm && strlen($new) >= 6) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $PDO->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            $message = "Passwort erfolgreich geändert!";
        } else {
            $error = "Neue Passwörter stimmen nicht überein oder sind zu kurz!";
        }
    } else {
        $error = "Aktuelles Passwort ist falsch!";
    }
}

// 2FA aktivieren
if (isset($_POST['enable_2fa'])) {
    require_once 'vendor/autoload.php';
    $google2fa = new \PragmaRX\Google2FA\Google2FA();
    $secret = $google2fa->generateSecretKey();
    
    $stmt = $PDO->prepare("UPDATE users SET two_factor_secret = ?, two_factor_enabled = 0 WHERE id = ?");
    $stmt->execute([$secret, $user_id]);
    
    $qrCodeUrl = $google2fa->getQRCodeUrl(
        'ERP System',
        $user['username'],
        $secret
    );
    
    header('Location: user_settings.php?setup_2fa=1');
    exit;
}

// 2FA verifizieren und aktivieren
if (isset($_POST['verify_2fa'])) {
    require_once 'vendor/autoload.php';
    $google2fa = new \PragmaRX\Google2FA\Google2FA();
    $code = $_POST['2fa_code'];
    
    if ($google2fa->verifyKey($user['two_factor_secret'], $code)) {
        $stmt = $PDO->prepare("UPDATE users SET two_factor_enabled = 1 WHERE id = ?");
        $stmt->execute([$user_id]);
        $message = "2FA erfolgreich aktiviert!";
    } else {
        $error = "Ungültiger Code!";
    }
}

// 2FA deaktivieren
if (isset($_POST['disable_2fa'])) {
    $stmt = $PDO->prepare("UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?");
    $stmt->execute([$user_id]);
    $message = "2FA deaktiviert!";
}

// User neu laden
$stmt = $PDO->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzereinstellungen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --bg-main: #f8fafc;
            --bg-card: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --border: #e2e8f0;
        }
        
            --primary: #3b82f6;
            --bg-main: #0f172a;
            --bg-card: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border: #334155;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            transition: all 0.3s;
        }
        
        .settings-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .settings-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
        }
        
        .settings-header {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }
        
        .form-label {
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .form-control {
            background: var(--bg-main);
            border: 1px solid var(--border);
            color: var(--text-primary);
        }
        
        .form-control:focus {
            background: var(--bg-card);
            border-color: var(--primary);
            color: var(--text-primary);
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--primary);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .qr-code {
            text-align: center;
            padding: 2rem;
            background: var(--bg-main);
            border-radius: 8px;
            margin: 1rem 0;
        }
    </style>
</head>
    <div class="settings-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="settings-header mb-0"><i class="fas fa-cog me-2"></i>Einstellungen</h1>
            <a href="web_oberflaeche.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Zurück</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="settings-card">
            <h3 class="h5 mb-3"><i class="fas fa-moon me-2"></i>Darstellung</h3>
            <form method="POST">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">Dunkles Design aktivieren</p>
                    </div>
                    <label class="toggle-switch">
                        <span class="slider"></span>
                    </label>
                </div>
            </form>
        </div>

        <!-- Passwort ändern -->
        <div class="settings-card">
            <h3 class="h5 mb-3"><i class="fas fa-key me-2"></i>Passwort ändern</h3>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Aktuelles Passwort</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Neues Passwort</label>
                    <input type="password" name="new_password" class="form-control" minlength="6" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Passwort bestätigen</label>
                    <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Passwort ändern
                </button>
            </form>
        </div>

        <!-- 2FA -->
        <div class="settings-card">
            <h3 class="h5 mb-3"><i class="fas fa-shield-alt me-2"></i>Zwei-Faktor-Authentifizierung</h3>
            
            <?php if (!$user['two_factor_enabled'] && !$user['two_factor_secret']): ?>
                <p class="text-muted">Erhöhen Sie die Sicherheit Ihres Kontos mit 2FA.</p>
                <form method="POST">
                    <button type="submit" name="enable_2fa" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>2FA aktivieren
                    </button>
                </form>
            
            <?php elseif ($user['two_factor_secret'] && !$user['two_factor_enabled']): ?>
                <div class="qr-code">
                    <?php
                    require_once 'vendor/autoload.php';
                    $google2fa = new \PragmaRX\Google2FA\Google2FA();
                    $qrCodeUrl = $google2fa->getQRCodeUrl('ERP System', $user['username'], $user['two_factor_secret']);
                    ?>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($qrCodeUrl) ?>" alt="QR Code">
                    <p class="mt-3">Scannen Sie den QR-Code mit Ihrer Authenticator-App</p>
                    <p class="text-muted">Secret: <code><?= $user['two_factor_secret'] ?></code></p>
                </div>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Bestätigungscode eingeben</label>
                        <input type="text" name="2fa_code" class="form-control" placeholder="000000" maxlength="6" required>
                    </div>
                    <button type="submit" name="verify_2fa" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Aktivieren
                    </button>
                </form>
            
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>2FA ist aktiviert
                </div>
                <form method="POST">
                    <button type="submit" name="disable_2fa" class="btn btn-danger" onclick="return confirm('2FA wirklich deaktivieren?')">
                        <i class="fas fa-times me-2"></i>2FA deaktivieren
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Benutzerinfo -->
        <div class="settings-card">
            <h3 class="h5 mb-3"><i class="fas fa-user me-2"></i>Kontoinformationen</h3>
            <p><strong>Benutzername:</strong> <?= htmlspecialchars($user['username']) ?></p>
            <p><strong>Rolle:</strong> <?= htmlspecialchars($user['role']) ?></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
