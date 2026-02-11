<?php
session_start();

// Session-Konfiguration verbessern
ini_set('session.cookie_lifetime', 3600);
ini_set('session.gc_maxlifetime', 3600);

require_once 'db_connection.php';

// Prüfen ob bereits eingeloggt und weiterleiten
if (isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'web_oberflaeche.php';</script>";
    exit();
}

// Benutzer-Tabelle erstellen falls sie nicht existiert
try {
    // Prüfen ob Tabelle existiert, nur erstellen wenn nicht vorhanden
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
        
        // Standard-Benutzer erstellen
        $stmt = $PDO->prepare("INSERT INTO users (username, password, role, can_manage_users) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', password_hash('Mika.#2020!!', PASSWORD_DEFAULT), 'admin', 1]);
    } else {
        // Prüfen ob admin existiert, falls nicht erstellen
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

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // Benutzer abfragen
        $stmt = $PDO->prepare("SELECT id, password, role, can_manage_users FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Verwende password_verify für gehashte Passwörter
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                // Login erfolgreich
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $user['role'] ?? 'user';
                $_SESSION['can_manage_users'] = $user['can_manage_users'] ?? 0;
                
                // Session explizit speichern
                session_write_close();
                session_start();
                
                // Automatische Weiterleitung mit JavaScript
                echo "<script>setTimeout(function(){ window.location.href = 'web_oberflaeche.php'; }, 1000);</script>";
                $error = "<div class='alert alert-success'>Login erfolgreich! Sie werden weitergeleitet...</div>";
            } else {
                $error = "Falsches Passwort.";
            }
        } else {
            $error = "Benutzername nicht gefunden.";
        }
    } catch (PDOException $e) {
        $error = "Datenbankfehler: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, rgba(26,26,46,0.97) 0%, rgba(22,33,62,0.97) 50%, rgba(30,30,50,0.97) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            margin: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            overflow: hidden;
            animation: slideIn 0.6s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: linear-gradient(135deg, #C9A227 0%, #D4AF37 100%);
            color: white;
            text-align: center;
            padding: 40px 30px;
            margin: 0;
        }
        
        .login-header h2 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .login-header .subtitle {
            margin-top: 10px;
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 1rem;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            border-color: #C9A227;
            box-shadow: 0 0 0 0.2rem rgba(201,162,39,0.25);
            background: white;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #C9A227;
            z-index: 10;
        }
        
        .input-group .form-control {
            padding-left: 50px;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #C9A227 0%, #D4AF37 100%);
            color: white;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(201,162,39,0.4);
            background: linear-gradient(135deg, #B8911F 0%, #C9A227 100%);
        }
        
        .login-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            margin: 20px -30px -40px -30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 20px;
        }
        
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="login-container">
        <div class="login-header">
            <div class="d-flex align-items-center justify-content-center mb-3">
                <div>
                    <h2><i class="fas fa-user-shield"></i> ERP System System</h2>
                </div>
            </div>
            <div class="subtitle">Sicherer Zugang zu Ihrem Kundensystem</div>
        </div>
        
        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="<?php echo strpos($error, 'alert-success') !== false ? '' : 'alert alert-danger'; ?>">
                    <?php if (strpos($error, 'alert-success') === false): ?>
                        <i class="fas fa-exclamation-triangle"></i> 
                    <?php endif; ?>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" class="form-control" name="username" placeholder="Benutzername eingeben" required>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" name="password" placeholder="Passwort eingeben" required>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Anmelden
                </button>
                
                <a href="register.php" class="btn btn-outline-light w-100 mt-3" style="border-color: #C9A227; color: #C9A227;">
                    <i class="fas fa-user-plus"></i> Registrierung
                </a>
            </form>
        </div>
        
        <div class="login-info">
            <small class="text-muted">
                <a href="register.php" class="text-decoration-none" style="color: #C9A227;">
                    <i class="fas fa-user-plus"></i> Neuen Benutzer erstellen
                </a>
            </small>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Cookie Consent Banner -->
    <div id="cookieConsent" style="display: none; position: fixed; bottom: 0; left: 0; right: 0; background: rgba(26,26,46,0.98); color: white; padding: 20px; z-index: 9999; box-shadow: 0 -5px 20px rgba(0,0,0,0.3);">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px;">
                <h4 style="color: #C9A227; margin: 0 0 10px 0;">🍪 Cookie-Hinweis</h4>
                <p style="margin: 0; font-size: 0.95rem; line-height: 1.5;">
                    Wir verwenden technisch notwendige Cookies für die Funktionalität dieses ERP-Systems. 
                    Diese Cookies sind für den Betrieb der Anwendung erforderlich und speichern Ihre Session-Daten.
                    <a href="datenschutz.php" style="color: #C9A227; text-decoration: underline;">Mehr erfahren</a>
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="acceptCookies()" style="background: linear-gradient(135deg, #C9A227 0%, #D4AF37 100%); color: white; border: none; padding: 12px 30px; border-radius: 50px; cursor: pointer; font-weight: 600; font-size: 1rem; transition: all 0.3s;">
                    ✓ Akzeptieren
                </button>
                <button onclick="declineCookies()" style="background: transparent; color: white; border: 2px solid white; padding: 12px 30px; border-radius: 50px; cursor: pointer; font-weight: 600; font-size: 1rem; transition: all 0.3s;">
                    ✗ Ablehnen
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Cookie-Consent prüfen
        function checkCookieConsent() {
            const consent = localStorage.getItem('cookieConsent');
            if (!consent) {
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
            alert('Ohne Cookies kann das ERP-System nicht verwendet werden. Sie werden zur Startseite weitergeleitet.');
            window.location.href = 'datenschutz.php';
        }
        
        // Beim Laden prüfen
        window.addEventListener('load', checkCookieConsent);
    </script>
</body>
</html>