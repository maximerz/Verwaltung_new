<?php
// Audit-Log Funktionen für Compliance & Nachvollziehbarkeit

function audit_log($pdo, $action, $table_name, $record_id, $old_value = null, $new_value = null) {
    try {
        // Audit-Log Tabelle erstellen falls nicht vorhanden
        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            username TEXT,
            action TEXT NOT NULL,
            table_name TEXT NOT NULL,
            record_id INTEGER,
            old_value TEXT,
            new_value TEXT,
            ip_address TEXT,
            user_agent TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $user_id = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'System';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, username, action, table_name, record_id, old_value, new_value, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $username,
            $action,
            $table_name,
            $record_id,
            $old_value ? json_encode($old_value) : null,
            $new_value ? json_encode($new_value) : null,
            $ip,
            $user_agent
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Audit-Log Fehler: " . $e->getMessage());
        return false;
    }
}

// Hilfsfunktion: Hole alte Werte vor Update
function get_record_before_update($pdo, $table, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}
?>
