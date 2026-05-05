<?php
session_start();
require_once __DIR__ . '/../db_connection.php';

$action = $_POST['action'] ?? '';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login.php');
    exit;
}

// LDAP-Konfiguration speichern
if ($action === 'save_ldap') {
    $stmt = $PDO->prepare("DELETE FROM ldap_config");
    $stmt->execute();
    
    $stmt = $PDO->prepare("INSERT INTO ldap_config (server, port, base_dn, bind_dn, bind_password, user_filter, enabled, ssl_enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['server'],
        $_POST['port'],
        $_POST['base_dn'],
        $_POST['bind_dn'],
        $_POST['bind_password'],
        $_POST['user_filter'],
        isset($_POST['enabled']) ? 1 : 0,
        isset($_POST['ssl_enabled']) ? 1 : 0
    ]);
    
    $success = "LDAP-Konfiguration gespeichert!";
}

// LDAP-Verbindung testen
if ($action === 'test_ldap') {
    $server = $_POST['server'];
    $port = $_POST['port'];
    $bind_dn = $_POST['bind_dn'];
    $bind_password = $_POST['bind_password'];
    
    if (function_exists('ldap_connect')) {
        $ldap = ldap_connect($server, $port);
        if ($ldap) {
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            if (ldap_bind($ldap, $bind_dn, $bind_password)) {
                $test_result = "✅ LDAP-Verbindung erfolgreich!";
            } else {
                $test_result = "❌ LDAP-Authentifizierung fehlgeschlagen: " . ldap_error($ldap);
            }
            ldap_close($ldap);
        } else {
            $test_result = "❌ LDAP-Verbindung fehlgeschlagen!";
        }
    } else {
        $test_result = "❌ LDAP-Extension nicht installiert!";
    }
}

// Aktuelle Konfiguration laden
$stmt = $PDO->prepare("SELECT * FROM ldap_config LIMIT 1");
$stmt->execute();
$config = $stmt->fetch() ?: [];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>LDAP-Konfiguration - ERP System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #8b1538, #a91b47); min-height: 100vh; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { max-width: 150px; height: auto; margin-bottom: 15px; }
        h1, h2 { color: #8b1538; }
        .form-section { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #8b1538; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; }
        .form-group input:focus { border-color: #8b1538; outline: none; }
        .btn { background: #8b1538; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #a91b47; }
        .btn-test { background: #17a2b8; }
        .btn-secondary { background: #6c757d; }
        .success { color: green; font-weight: bold; margin: 15px 0; }
        .error { color: red; font-weight: bold; margin: 15px 0; }
        .info-box { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #2196f3; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input[type="checkbox"] { width: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 LDAP-Konfiguration</h1>
        </div>

        <div style="text-align: center; margin-bottom: 20px;">
            <a href="/user_management.php" class="btn btn-secondary">⬅️ Zurück zur Benutzerverwaltung</a>
        </div>

        <?php if (isset($success)): ?>
            <p class="success"><?= $success ?></p>
        <?php endif; ?>
        <?php if (isset($test_result)): ?>
            <p class="<?= strpos($test_result, '✅') !== false ? 'success' : 'error' ?>"><?= $test_result ?></p>
        <?php endif; ?>

        <div class="info-box">
            <h3>ℹ️ LDAP-Integration</h3>
            <p>Mit LDAP können sich Benutzer mit ihren Active Directory / LDAP-Anmeldedaten anmelden. 
            Die Benutzer werden automatisch im System erstellt, wenn sie sich das erste Mal anmelden.</p>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="save_ldap">
            
            <div class="form-section">
                <h2>🌐 Server-Konfiguration</h2>
                
                <div class="form-group">
                    <label>LDAP-Server</label>
                    <input type="text" name="server" value="<?= htmlspecialchars($config['server'] ?? '') ?>" placeholder="ldap.example.com oder 192.168.1.100" required>
                </div>
                
                <div class="form-group">
                    <label>Port</label>
                    <input type="number" name="port" value="<?= $config['port'] ?? 389 ?>" min="1" max="65535" required>
                    <small>Standard: 389 (LDAP), 636 (LDAPS)</small>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="ssl_enabled" id="ssl_enabled" <?= ($config['ssl_enabled'] ?? 0) ? 'checked' : '' ?>>
                    <label for="ssl_enabled">SSL/TLS verwenden (LDAPS)</label>
                </div>
            </div>

            <div class="form-section">
                <h2>🔑 Authentifizierung</h2>
                
                <div class="form-group">
                    <label>Base DN</label>
                    <input type="text" name="base_dn" value="<?= htmlspecialchars($config['base_dn'] ?? '') ?>" placeholder="dc=example,dc=com" required>
                    <small>Basis-Distinguished Name für Benutzersuche</small>
                </div>
                
                <div class="form-group">
                    <label>Bind DN</label>
                    <input type="text" name="bind_dn" value="<?= htmlspecialchars($config['bind_dn'] ?? '') ?>" placeholder="cn=admin,dc=example,dc=com">
                    <small>Service-Account für LDAP-Abfragen (optional)</small>
                </div>
                
                <div class="form-group">
                    <label>Bind Passwort</label>
                    <input type="password" name="bind_password" value="<?= htmlspecialchars($config['bind_password'] ?? '') ?>" placeholder="Passwort für Service-Account">
                </div>
                
                <div class="form-group">
                    <label>Benutzer-Filter</label>
                    <input type="text" name="user_filter" value="<?= htmlspecialchars($config['user_filter'] ?? '(uid=%s)') ?>" required>
                    <small>LDAP-Filter für Benutzersuche. %s wird durch den Benutzernamen ersetzt</small>
                </div>
            </div>

            <div class="form-section">
                <h2>⚙️ Einstellungen</h2>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="enabled" id="enabled" <?= ($config['enabled'] ?? 0) ? 'checked' : '' ?>>
                    <label for="enabled">LDAP-Authentifizierung aktivieren</label>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn">💾 Konfiguration speichern</button>
            </div>
        </form>

        <form method="POST" style="margin-top: 20px;">
            <input type="hidden" name="action" value="test_ldap">
            <input type="hidden" name="server" value="<?= htmlspecialchars($config['server'] ?? '') ?>">
            <input type="hidden" name="port" value="<?= $config['port'] ?? 389 ?>">
            <input type="hidden" name="bind_dn" value="<?= htmlspecialchars($config['bind_dn'] ?? '') ?>">
            <input type="hidden" name="bind_password" value="<?= htmlspecialchars($config['bind_password'] ?? '') ?>">
            
            <div style="text-align: center;">
                <button type="submit" class="btn btn-test">🔍 Verbindung testen</button>
            </div>
        </form>

        <div class="info-box" style="margin-top: 30px;">
            <h3>📋 Beispiel-Konfigurationen</h3>
            <p><strong>Active Directory:</strong><br>
            Server: dc.company.com<br>
            Base DN: dc=company,dc=com<br>
            Bind DN: cn=service,cn=Users,dc=company,dc=com<br>
            User Filter: (sAMAccountName=%s)</p>
            
            <p><strong>OpenLDAP:</strong><br>
            Server: ldap.company.com<br>
            Base DN: ou=users,dc=company,dc=com<br>
            Bind DN: cn=admin,dc=company,dc=com<br>
            User Filter: (uid=%s)</p>
        </div>
    </div>
</body>
</html>
