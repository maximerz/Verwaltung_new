<?php
session_start();

// Session-Konfiguration verbessern
ini_set('session.cookie_lifetime', 3600);
ini_set('session.gc_maxlifetime', 3600);

require_once 'db_connection.php';
require_once 'includes/2fa_functions.php';

init_2fa_system_settings($PDO);
add_2fa_user_columns($PDO);
cleanup_expired_2fa_tokens($PDO);

if (isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'web_oberflaeche.php';</script>";
    exit();
}

try {
    $stmt = $PDO->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    $stmt->execute();

    if (!$stmt->fetch()) {
        $PDO->exec("CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'user',
            can_manage_users INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $PDO->prepare("INSERT INTO users (username, password, role, can_manage_users) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', password_hash('Mika.#2020!!', PASSWORD_DEFAULT), 'admin', 1]);
    } else {
        $stmt = $PDO->prepare("SELECT id FROM users WHERE username = 'admin'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $stmt = $PDO->prepare("INSERT INTO users (username, password, role, can_manage_users) VALUES (?, ?, ?, ?)");
            $stmt->execute(['admin', password_hash('Mika.#2020!!', PASSWORD_DEFAULT), 'admin', 1]);
        }
    }
} catch (PDOException $e) {
    $error = "Fehler beim Erstellen der Benutzer-Tabelle: " . $e->getMessage();
}

if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    session_unset();
    session_destroy();
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

$error = '';
$step = 1;
$username = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && !isset($_POST['2fa_code'])) {
    $username = $_POST['username'];
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $PDO->prepare("SELECT id, password, role, can_manage_users, two_factor_enabled, two_factor_secret, two_factor_mandatory FROM users WHERE LOWER(username) = LOWER(?)");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $_SESSION['temp_user_id'] = $user['id'];
                $_SESSION['temp_username'] = $username;
                $_SESSION['temp_role'] = $user['role'] ?? 'user';
                $_SESSION['temp_can_manage_users'] = $user['can_manage_users'] ?? 0;

                $system_mandatory = false;
                $stmt = $PDO->prepare("SELECT setting_value FROM system_settings WHERE setting_key = '2fa_mandatory'");
                $stmt->execute();
                $system_setting = $stmt->fetch();
                $system_mandatory = ($system_setting && $system_setting['setting_value'] == '1');

                $two_factor_required = (
                    $user['two_factor_enabled'] == 1 ||
                    $user['two_factor_mandatory'] == 1 ||
                    $system_mandatory
                );

                if ($two_factor_required && !empty($user['two_factor_secret'])) {
                    // Try to get token from cookie OR localStorage
                    $remember_token = $_COOKIE['2fa_remember'] ?? '';
                    
                    // If no cookie, try localStorage (set by previous login)
                    if (empty($remember_token)) {
                        // Token will be checked via JavaScript on page load
                        // For now, proceed to 2FA step
                    } else {
                        // Validate token from cookie
                        $stmt = $PDO->prepare("
                            SELECT id FROM users
                            WHERE id = ? AND two_factor_remember_token = ?
                            AND two_factor_remember_expires > datetime('now')
                        ");
                        $stmt->execute([$user['id'], $remember_token]);

                        if ($stmt->fetch()) {
                            // Valid token found, check if renewal is needed
                            $needs_renewal = check_and_force_2fa_renewal($PDO, $user['id']);
                            
                            if (!$needs_renewal) {
                                finalize_login($user, $username);
                                exit;
                            } else {
                                // Token exists but renewal needed - clear and require 2FA
                                clear_2fa_remember_token($PDO, $user['id']);
                            }
                        }
                    }

                    // Check if renewal is needed anyway
                    $needs_renewal = check_and_force_2fa_renewal($PDO, $user['id']);
                    if ($needs_renewal) {
                        clear_2fa_remember_token($PDO, $user['id']);
                    }

                    $step = 2;
                } else {
                    finalize_login($user, $username);
                    exit;
                }
            } else {
                require_once 'includes/audit_log.php';
                audit_log($PDO, 'LOGIN_FAILED', 'users', null, null, ['username' => $username]);
                $error = "Falsches Passwort.";
            }
        } else {
            $error = "Benutzername nicht gefunden.";
        }
    } catch (PDOException $e) {
        $error = "Datenbankfehler: " . $e->getMessage();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['2fa_code'])) {
    $user_id = $_SESSION['temp_user_id'] ?? null;
    $remember = $_POST['remember_2fa'] ?? false;

    if (!$user_id) {
        $error = "Session abgelaufen. Bitte erneut anmelden.";
        $step = 1;
    } else {
        try {
            $stmt = $PDO->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && !empty($user['two_factor_secret'])) {
                require_once 'vendor/autoload.php';
                $google2fa = new \PragmaRX\Google2FA\Google2FA();
                $code = $_POST['2fa_code'];

                if ($google2fa->verifyKey($user['two_factor_secret'], $code)) {
                    // Update verification timestamp
                    update_2fa_verified_time($PDO, $user_id);

                    // Set remember token if checkbox is checked
                    $remember_token_set = false;
                    if ($remember) {
                        $remember_token_set = set_2fa_remember_token($PDO, $user_id, 30);
                        // Store in localStorage for cross-tab compatibility
                        echo '<script>
                            if (window.localStorage) {
                                localStorage.setItem("2fa_remember", "1");
                                localStorage.setItem("2fa_remember_date", "' . date('Y-m-d H:i:s') . '");
                            }
                        </script>';
                    }

                    finalize_login($user, $_SESSION['temp_username']);
                    exit;
                } else {
                    $error = "Ungültiger Code. Bitte erneut versuchen.";
                    $step = 2;
                }
            } else {
                $error = "Benutzer nicht gefunden oder kein 2FA konfiguriert.";
                $step = 1;
            }
        } catch (Exception $e) {
            $error = "Fehler bei der 2FA-Verifizierung: " . $e->getMessage();
            $step = 2;
        }
    }
}

function finalize_login($user, $username) {
    global $PDO;

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $user['role'] ?? 'user';
    $_SESSION['can_manage_users'] = $user['can_manage_users'] ?? 0;

    if (!empty($user['two_factor_secret'])) {
        update_2fa_verified_time($PDO, $user['id']);
    }

    require_once 'includes/audit_log.php';
    audit_log($PDO, 'LOGIN', 'users', $user['id'], null, ['username' => $username]);

    session_write_close();
    session_start();

    echo "<script>setTimeout(function(){ window.location.href = 'web_oberflaeche.php'; }, 1000);</script>";
    $error = "<div class='alert alert-success'>Login erfolgreich! Sie werden weitergeleitet...</div>";
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
    <title>Login - Projekt1 ERP</title>
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
                <span class="auth-kicker"><i class="fas fa-shield-halved"></i>Sicherer Zugang</span>
                <h1>ERP-Zentrale für Vertrieb, Kunden und Prozesse.</h1>
                <p>Die Oberfläche wurde auf ein neues Farbsystem, klarere Navigation und einen ruhigen Einstieg umgestellt. Der Login bleibt funktional, wirkt aber deutlich moderner.</p>
                <div class="auth-highlights">
                    <div class="auth-highlight">
                        <span class="auth-highlight-icon"><i class="fas fa-route"></i></span>
                        <div>
                            <strong>Klarere Wege</strong>
                            <p>Verwaltung, Aufträge und Reporting folgen jetzt einem einheitlichen Layout.</p>
                        </div>
                    </div>
                    <div class="auth-highlight">
                        <span class="auth-highlight-icon"><i class="fas fa-lock"></i></span>
                        <div>
                            <strong>2FA integriert</strong>
                            <p>Die bestehende Zwei-Faktor-Anmeldung bleibt erhalten und ist direkt in den Flow eingebunden.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="auth-links">
                <a class="quick-link-chip" href="register.php"><i class="fas fa-user-plus"></i>Registrieren</a>
                <button type="button" class="theme-toggle" onclick="toggleAuthTheme()">
                    <i class="fas fa-moon" id="authThemeIcon"></i>
                    <span>Theme</span>
                </button>
            </div>
        </section>

        <section class="auth-card">
            <div class="auth-card-header">
                <h2><?= $step == 1 ? 'Anmelden' : 'Zwei-Faktor-Prüfung' ?></h2>
                <p><?= $step == 1 ? 'Melden Sie sich mit Ihrem Benutzerkonto an.' : 'Bestätigen Sie den Code aus Ihrer Authenticator-App.' ?></p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert <?= strpos($error, 'alert-success') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <i class="fas fa-<?= strpos($error, 'alert-success') !== false ? 'check-circle' : 'triangle-exclamation' ?> me-2"></i>
                    <?= strpos($error, 'alert-success') !== false ? $error : htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <form method="post" action="" class="auth-form">
                    <div class="input-shell">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" name="username" placeholder="Benutzername" required value="<?= htmlspecialchars($username) ?>">
                    </div>
                    <div class="input-shell">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" name="password" placeholder="Passwort" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-arrow-right"></i>
                        <span>Weiter</span>
                    </button>
                </form>
                <div class="auth-links">
                    <a href="register.php"><i class="fas fa-user-plus me-1"></i>Benutzer anfragen</a>
                    <a href="datenschutz.php"><i class="fas fa-user-shield me-1"></i>Datenschutz</a>
                </div>
            <?php else: ?>
                <form method="post" action="" class="auth-form">
                    <div class="input-shell">
                        <i class="fas fa-key"></i>
                        <input type="text" class="form-control" name="2fa_code" placeholder="000000" maxlength="6" required autofocus>
                    </div>
                    <label class="remember-toggle" for="remember_2fa">
                        <input class="remember-toggle-input" type="checkbox" id="remember_2fa" name="remember_2fa" value="1">
                        <span class="remember-toggle-box" aria-hidden="true"></span>
                        <span class="remember-toggle-copy">
                            <strong>30 Tage merken</strong>
                            <span>Beim nächsten Login keine erneute 2FA-Abfrage auf diesem Gerät.</span>
                        </span>
                    </label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-check"></i>
                        <span>Anmelden</span>
                    </button>
                </form>
                <div class="auth-links">
                    <a href="login.php"><i class="fas fa-arrow-left me-1"></i>Zurück</a>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div id="cookieConsent" class="surface-card" style="display:none; position:fixed; left:1rem; right:1rem; bottom:1rem; max-width:1180px; margin:0 auto; padding:1rem 1.2rem; z-index:1200;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <strong><i class="fas fa-cookie-bite me-2"></i>Cookie-Hinweis</strong>
                <p class="mb-0 text-muted">Es werden nur technisch notwendige Cookies verwendet. Details stehen in der <a href="datenschutz.php">Datenschutzerklärung</a>.</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="acceptCookies()" class="btn btn-primary" type="button">Akzeptieren</button>
                <button onclick="declineCookies()" class="btn btn-secondary" type="button">Schließen</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Check for 2FA remember token on page load
        document.addEventListener('DOMContentLoaded', function() {
            const hasLocalStorage2FA = localStorage.getItem('2fa_remember') === '1';
            const hasCookie2FA = document.cookie.indexOf('2fa_remember') !== -1;
            
            // If we have a valid token (from cookie or localStorage), try to auto-login
            // This is handled server-side, but we show feedback here
            console.log('2FA remember check:', { hasLocalStorage2FA, hasCookie2FA });
        });

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

        function checkCookieConsent() {
            if (!localStorage.getItem('cookieConsent')) {
                document.getElementById('cookieConsent').style.display = 'block';
            }
        }

        function acceptCookies() {
            localStorage.setItem('cookieConsent', 'accepted');
            document.getElementById('cookieConsent').style.display = 'none';
        }

        function declineCookies() {
            localStorage.setItem('cookieConsent', 'declined');
            document.getElementById('cookieConsent').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            syncThemeIcon();
            checkCookieConsent();
        });
    </script>
</body>
</html>
