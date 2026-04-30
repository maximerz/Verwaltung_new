<?php
session_start();
require_once 'db_connection.php';

$error = '';
$success = '';

if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Benutzername und Passwort sind erforderlich.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwörter stimmen nicht überein.';
    } elseif (strlen($password) < 4) {
        $error = 'Passwort muss mindestens 4 Zeichen lang sein.';
    } else {
        try {
            $stmt = $PDO->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);

            if ($stmt->fetch()) {
                $error = 'Benutzername bereits vergeben.';
            } else {
                $stmt = $PDO->prepare("INSERT INTO user_requests (username, password) VALUES (?, ?)");
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                $success = 'Registrierungsanfrage gesendet. Warten Sie auf die Freigabe durch den Administrator.';
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $error = 'Benutzername bereits angefragt oder vorhanden.';
            } else {
                $error = 'Fehler beim Senden der Anfrage: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Registrierung - Projekt1 ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <script>
        document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
    </script>
</head>
<body class="auth-page">
    <div class="auth-shell">
        <section class="auth-hero">
            <div>
                <span class="auth-kicker"><i class="fas fa-user-plus"></i>Neuer Zugang</span>
                <h1>Registrierung mit klarerem Einstieg und konsistenter Oberfläche.</h1>
                <p>Neue Benutzer werden weiterhin über eine Freigabeanfrage angelegt. Die Seite ist jetzt optisch an Dashboard und Navigation gekoppelt.</p>
                <div class="auth-highlights">
                    <div class="auth-highlight">
                        <span class="auth-highlight-icon"><i class="fas fa-user-check"></i></span>
                        <div>
                            <strong>Freigabeprozess bleibt bestehen</strong>
                            <p>Registrierungen landen weiterhin als Anfrage beim Administrator.</p>
                        </div>
                    </div>
                    <div class="auth-highlight">
                        <span class="auth-highlight-icon"><i class="fas fa-palette"></i></span>
                        <div>
                            <strong>Neues Farbsystem</strong>
                            <p>Orange, Petrol und dunkles Blau ersetzen das alte Türkis-Glass-Design.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="auth-links">
                <a class="quick-link-chip" href="login.php"><i class="fas fa-arrow-left"></i>Zum Login</a>
                <button type="button" class="theme-toggle" onclick="toggleAuthTheme()">
                    <i class="fas fa-moon" id="authThemeIcon"></i>
                    <span>Theme</span>
                </button>
            </div>
        </section>

        <section class="auth-card">
            <div class="auth-card-header">
                <h2>Registrierungsanfrage senden</h2>
                <p>Geben Sie Benutzernamen und Passwort an. Ein Administrator muss die Anfrage anschließend freigeben.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post" action="" class="auth-form">
                <div class="input-shell">
                    <i class="fas fa-user"></i>
                    <input type="text" class="form-control" name="username" placeholder="Benutzername" required>
                </div>
                <div class="input-shell">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" name="password" placeholder="Passwort" required>
                </div>
                <div class="input-shell">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Passwort bestätigen" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-paper-plane"></i>
                    <span>Anfrage senden</span>
                </button>
            </form>

            <div class="auth-links">
                <a href="login.php"><i class="fas fa-arrow-left me-1"></i>Zurück zum Login</a>
                <a href="impressum.php"><i class="fas fa-circle-info me-1"></i>Impressum</a>
            </div>
        </section>
    </div>

    <script>
        function toggleAuthTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', nextTheme);
            localStorage.setItem('theme', nextTheme);
            syncThemeIcon();
        }

        function syncThemeIcon() {
            const icon = document.getElementById('authThemeIcon');
            if (icon) {
                icon.className = document.documentElement.getAttribute('data-theme') === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }

        document.addEventListener('DOMContentLoaded', syncThemeIcon);
    </script>
</body>
</html>
