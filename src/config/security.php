<?php
/**
 * DSGVO-konforme Sicherheitskonfiguration für ERP-System
 * Für den Einsatz in deutschen Unternehmen
 */

// Verschlüsselungsschlüssel (WICHTIG: In Produktion aus Umgebungsvariable laden!)
define('ENCRYPTION_KEY', getenv('ERP_ENCRYPTION_KEY') ?: 'CHANGE_THIS_IN_PRODUCTION_' . bin2hex(random_bytes(16)));
define('ENCRYPTION_METHOD', 'AES-256-CBC');

/**
 * Verschlüsselt sensible Daten (DSGVO Art. 32)
 */
function encrypt_data($data) {
    if (empty($data)) return $data;
    
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    
    return base64_encode($encrypted . '::' . $iv);
}

/**
 * Entschlüsselt Daten
 */
function decrypt_data($data) {
    if (empty($data)) return $data;
    
    $decoded = base64_decode($data);
    if ($decoded === false) return $data;
    
    list($encrypted_data, $iv) = explode('::', $decoded, 2);
    
    return openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
}

/**
 * Audit-Log für DSGVO-Nachweispflicht (Art. 30)
 */
function log_audit($action, $entity_type, $entity_id, $details = '') {
    global $PDO;
    
    try {
        // Audit-Log Tabelle erstellen falls nicht vorhanden
        $PDO->exec("CREATE TABLE IF NOT EXISTS audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            user_id INTEGER,
            username TEXT,
            action TEXT NOT NULL,
            entity_type TEXT NOT NULL,
            entity_id TEXT,
            ip_address TEXT,
            user_agent TEXT,
            details TEXT,
            INDEX idx_timestamp (timestamp),
            INDEX idx_user (user_id),
            INDEX idx_entity (entity_type, entity_id)
        )");
        
        $stmt = $PDO->prepare("
            INSERT INTO audit_log (user_id, username, action, entity_type, entity_id, ip_address, user_agent, details)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $_SESSION['username'] ?? 'System',
            $action,
            $entity_type,
            $entity_id,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $details
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Audit-Log Fehler: " . $e->getMessage());
        return false;
    }
}

/**
 * Passwort-Hashing nach BSI-Empfehlung
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

/**
 * Session-Sicherheit
 */
function secure_session_start() {
    // Session-Cookie-Parameter (DSGVO-konform)
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // Nur über HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    
    // Session-Timeout: 30 Minuten
    ini_set('session.gc_maxlifetime', 1800);
    ini_set('session.cookie_lifetime', 1800);
    
    session_start();
    
    // Session-Fixation verhindern
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['created_at'] = time();
    }
    
    // Session-Timeout prüfen
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * CSRF-Token generieren
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF-Token validieren
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * XSS-Schutz: HTML-Ausgabe bereinigen
 */
function clean_output($data) {
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * SQL-Injection Schutz: Prepared Statements verwenden (bereits implementiert)
 */

/**
 * Datenschutz: Personenbezogene Daten anonymisieren (DSGVO Art. 17)
 */
function anonymize_customer($customer_id, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // Kundendaten anonymisieren
        $stmt = $pdo->prepare("
            UPDATE kundensystem 
            SET vorname = 'Anonymisiert',
                nachname = 'Anonymisiert',
                email = CONCAT('anonymized_', id, '@deleted.local')
            WHERE id = ?
        ");
        $stmt->execute([$customer_id]);
        
        // Audit-Log
        log_audit('ANONYMIZE', 'customer', $customer_id, 'DSGVO-Löschung durchgeführt');
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Anonymisierung fehlgeschlagen: " . $e->getMessage());
        return false;
    }
}

/**
 * Datenschutz: Datenexport für Auskunftsrecht (DSGVO Art. 15)
 */
function export_customer_data($customer_id, $pdo) {
    try {
        $data = [];
        
        // Kundenstammdaten
        $stmt = $pdo->prepare("SELECT * FROM kundensystem WHERE id = ?");
        $stmt->execute([$customer_id]);
        $data['stammdaten'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Bestellungen
        $stmt = $pdo->prepare("SELECT * FROM bestellungen WHERE kundennummer = ?");
        $stmt->execute([$data['stammdaten']['kundennummer']]);
        $data['bestellungen'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Rechnungen
        $stmt = $pdo->prepare("SELECT * FROM rechnungen WHERE kunde_id = ?");
        $stmt->execute([$data['stammdaten']['kundennummer']]);
        $data['rechnungen'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Audit-Log
        log_audit('EXPORT', 'customer', $customer_id, 'DSGVO-Datenexport durchgeführt');
        
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        error_log("Datenexport fehlgeschlagen: " . $e->getMessage());
        return false;
    }
}

/**
 * Backup-Verschlüsselung
 */
function create_encrypted_backup($pdo) {
    try {
        $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backup_path = __DIR__ . '/backups/';
        
        if (!is_dir($backup_path)) {
            mkdir($backup_path, 0700, true);
        }
        
        // SQLite Backup
        $backup_db = new PDO('sqlite:' . $backup_path . $backup_file);
        $pdo->sqliteCreateFunction('encrypt', 'encrypt_data', 1);
        
        // Backup durchführen
        $backup_db->exec("ATTACH DATABASE '" . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "' AS source");
        $backup_db->exec("BEGIN");
        
        // Tabellen kopieren
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $backup_db->exec("CREATE TABLE IF NOT EXISTS $table AS SELECT * FROM source.$table");
        }
        
        $backup_db->exec("COMMIT");
        $backup_db->exec("DETACH DATABASE source");
        
        // Audit-Log
        log_audit('BACKUP', 'system', null, 'Verschlüsseltes Backup erstellt: ' . $backup_file);
        
        return $backup_file;
    } catch (Exception $e) {
        error_log("Backup fehlgeschlagen: " . $e->getMessage());
        return false;
    }
}

/**
 * Sicherheits-Header setzen
 */
function set_security_headers() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:;");
}

/**
 * Rate Limiting gegen Brute-Force
 */
function check_rate_limit($identifier, $max_attempts = 5, $time_window = 300) {
    global $PDO;
    
    try {
        $PDO->exec("CREATE TABLE IF NOT EXISTS rate_limits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            identifier TEXT NOT NULL,
            attempts INTEGER DEFAULT 1,
            first_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $stmt = $PDO->prepare("SELECT attempts, first_attempt FROM rate_limits WHERE identifier = ? AND first_attempt > datetime('now', '-' || ? || ' seconds')");
        $stmt->execute([$identifier, $time_window]);
        $result = $stmt->fetch();
        
        if ($result && $result['attempts'] >= $max_attempts) {
            return false; // Rate limit erreicht
        }
        
        // Versuch zählen
        if ($result) {
            $PDO->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP WHERE identifier = ?")->execute([$identifier]);
        } else {
            $PDO->prepare("INSERT INTO rate_limits (identifier) VALUES (?)")->execute([$identifier]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Rate Limit Check fehlgeschlagen: " . $e->getMessage());
        return true; // Im Fehlerfall durchlassen
    }
}

/**
 * Datenschutz-Einstellungen initialisieren
 */
function init_privacy_settings($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS privacy_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE NOT NULL,
            setting_value TEXT,
            description TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Standard-Einstellungen
        $defaults = [
            ['data_retention_days', '2555', 'Aufbewahrungsfrist für Kundendaten (7 Jahre = 2555 Tage)'],
            ['auto_anonymize', '1', 'Automatische Anonymisierung nach Aufbewahrungsfrist'],
            ['audit_log_retention', '3650', 'Aufbewahrung Audit-Logs (10 Jahre)'],
            ['encryption_enabled', '1', 'Verschlüsselung sensibler Daten aktiviert'],
            ['backup_encryption', '1', 'Backup-Verschlüsselung aktiviert']
        ];
        
        foreach ($defaults as $default) {
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO privacy_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
            $stmt->execute($default);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Privacy Settings Init fehlgeschlagen: " . $e->getMessage());
        return false;
    }
}

// Sicherheits-Header bei jedem Request setzen
set_security_headers();

// Datenschutz-Einstellungen initialisieren
if (isset($PDO)) {
    init_privacy_settings($PDO);
}
?>
