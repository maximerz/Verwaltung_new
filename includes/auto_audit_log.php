<?php
// Auto-Audit-Log: Automatisches Logging f\u00fcr alle DB-\u00c4nderungen
// Dieses Script muss in db_connection.php eingebunden werden

function auto_audit_log_query($pdo, $query, $params = []) {
    // Pr\u00fcfe ob Audit-Log aktiviert ist
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Extrahiere Aktion und Tabelle aus Query
    $action = null;
    $table = null;
    
    if (preg_match('/^(INSERT|UPDATE|DELETE)/i', trim($query), $matches)) {
        $action = strtoupper($matches[1]);
        
        if (preg_match('/(?:FROM|INTO|UPDATE)\s+(\w+)/i', $query, $table_matches)) {
            $table = $table_matches[1];
        }
        
        // Nur loggen wenn es eine relevante Tabelle ist
        $relevant_tables = ['kundensystem', 'bestellungen', 'users', 'lagerartikel', 'lieferanten', 'firma'];
        
        if ($table && in_array($table, $relevant_tables)) {
            require_once __DIR__ . '/audit_log.php';
            
            $record_id = null;
            $old_value = null;
            $new_value = null;
            
            // Bei UPDATE: Hole alte Werte
            if ($action === 'UPDATE' && preg_match('/WHERE\s+id\s*=\s*[?:]/i', $query)) {
                // ID aus Parametern holen (meist letzter Parameter bei UPDATE)
                $record_id = end($params);
                $old_value = get_record_before_update($pdo, $table, $record_id);
            }
            
            // Bei INSERT: Neue Werte aus Parametern
            if ($action === 'INSERT') {
                $new_value = $params;
            }
            
            // Bei DELETE: ID aus Parametern
            if ($action === 'DELETE' && preg_match('/WHERE\s+id\s*=\s*[?:]/i', $query)) {
                $record_id = $params[0] ?? null;
                $old_value = get_record_before_update($pdo, $table, $record_id);
            }
            
            // Log erstellen
            audit_log($pdo, $action, $table, $record_id, $old_value, $new_value);
        }
    }
}
?>
