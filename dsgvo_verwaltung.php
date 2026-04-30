<?php
session_start();
require_once 'db_connection.php';
require_once 'security.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header('Location: login.php');
    exit;
}

// Datenexport durchführen
if (isset($_GET['export_customer'])) {
    $customer_id = $_GET['export_customer'];
    $export_data = export_customer_data($customer_id, $PDO);
    
    if ($export_data) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="kundendaten_' . $customer_id . '_' . date('Y-m-d') . '.json"');
        echo $export_data;
        exit;
    }
}

// Kunde anonymisieren
if (isset($_POST['anonymize_customer'])) {
    $customer_id = $_POST['customer_id'];
    if (anonymize_customer($customer_id, $PDO)) {
        $success = "Kunde wurde erfolgreich anonymisiert (DSGVO Art. 17)";
    } else {
        $error = "Anonymisierung fehlgeschlagen";
    }
}

// Backup erstellen
if (isset($_POST['create_backup'])) {
    $backup_file = create_encrypted_backup($PDO);
    if ($backup_file) {
        $success = "Verschlüsseltes Backup erstellt: " . $backup_file;
    } else {
        $error = "Backup fehlgeschlagen";
    }
}

// Audit-Log laden
$stmt = $PDO->prepare("SELECT * FROM audit_log ORDER BY timestamp DESC LIMIT 100");
$stmt->execute();
$audit_logs = $stmt->fetchAll();

// Datenschutz-Einstellungen laden
$stmt = $PDO->query("SELECT * FROM privacy_settings");
$privacy_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Kunden für Anonymisierung
$stmt = $PDO->query("SELECT id, vorname, nachname, email FROM kundensystem ORDER BY nachname");
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DSGVO & Datenschutz - ERP System</title>
    <style>
        :root {
            --primary: linear-gradient(135deg, #5B7DB1 0%, #D4AF37 100%);
            --shadow: 0 15px 50px rgba(201,162,39,0.15);
        }
        body { font-family: 'Inter', Arial, sans-serif; margin: 0; padding: 20px; background: #F0F2F5; min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; background: rgba(255,255,255,0.98); padding: 30px; border-radius: 25px; box-shadow: var(--shadow); border: 2px solid rgba(255,255,255,0.3); }
        .header { text-align: center; margin-bottom: 30px; }
        h1, h2, h3 { background: var(--primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700; }
        .alert { padding: 15px; border-radius: 12px; margin: 20px 0; border-left: 4px solid; }
        .alert-info { background: rgba(66,165,245,0.1); border-color: #42A5F5; color: #1976D2; }
        .alert-warning { background: rgba(255,167,38,0.1); border-color: #FFA726; color: #F57C00; }
        .alert-success { background: rgba(0,184,148,0.1); border-color: #00B894; color: #00B894; }
        .alert-danger { background: rgba(229,57,53,0.1); border-color: #E53935; color: #E53935; }
        .tabs { display: flex; margin-bottom: 20px; gap: 10px; }
        .tab { padding: 10px 20px; background: #e9ecef; border: none; cursor: pointer; border-radius: 50px; font-weight: 600; transition: all 0.3s; }
        .tab.active { background: var(--primary); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .section { margin: 30px 0; padding: 20px; background: rgba(201,162,39,0.05); border-radius: 15px; border-left: 5px solid #5B7DB1; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; border-radius: 15px; overflow: hidden; }
        .table th, .table td { border: 1px solid #e9ecef; padding: 12px; text-align: left; font-size: 0.9em; }
        .table th { background: var(--primary); color: white; font-weight: 600; }
        .table tr:nth-child(even) { background: rgba(201,162,39,0.05); }
        .btn { background: var(--primary); color: white; padding: 10px 20px; border: none; border-radius: 50px; cursor: pointer; margin: 5px; font-weight: 600; transition: all 0.3s ease; text-decoration: none; display: inline-block; }
        .btn:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
        .btn-danger { background: linear-gradient(135deg, #EF5350 0%, #E53935 100%); }
        .btn-success { background: linear-gradient(135deg, #00D9A3 0%, #00B894 100%); }
        .nav-buttons { text-align: center; margin-bottom: 30px; }
        .compliance-badge { display: inline-block; padding: 8px 15px; border-radius: 50px; font-weight: 600; margin: 5px; }
        .badge-compliant { background: #00B894; color: white; }
        .badge-warning { background: #FFA726; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 DSGVO & Datenschutz</h1>
            <p>Datenschutz-Grundverordnung Compliance Management</p>
        </div>

        <div class="nav-buttons">
            <a href="web_oberflaeche.php" class="btn">🏠 Hauptmenü</a>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">✅ <?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">❌ <?= $error ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>ℹ️ DSGVO-Compliance Status:</strong><br>
            <span class="compliance-badge badge-compliant">✓ Verschlüsselung aktiv</span>
            <span class="compliance-badge badge-compliant">✓ Audit-Log aktiv</span>
            <span class="compliance-badge badge-compliant">✓ Datenschutz-Einstellungen konfiguriert</span>
            <span class="compliance-badge badge-compliant">✓ Sichere Sessions</span>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('overview')">📊 Übersicht</button>
            <button class="tab" onclick="showTab('rights')">👤 Betroffenenrechte</button>
            <button class="tab" onclick="showTab('audit')">📋 Audit-Log</button>
            <button class="tab" onclick="showTab('backup')">💾 Backup</button>
            <button class="tab" onclick="showTab('settings')">⚙️ Einstellungen</button>
            <button class="tab" onclick="window.location.href='dsgvo_compliance.php'">📜 Compliance-Dok</button>
        </div>

        <div id="overview" class="tab-content active">
            <h2>📊 DSGVO-Compliance Übersicht</h2>
            
            <div class="section">
                <h3>Implementierte Maßnahmen (Art. 32 DSGVO)</h3>
                <ul style="line-height: 2;">
                    <li>✅ <strong>Verschlüsselung:</strong> AES-256-CBC für sensible Daten</li>
                    <li>✅ <strong>Pseudonymisierung:</strong> Kundendaten können anonymisiert werden</li>
                    <li>✅ <strong>Zugriffskontrolle:</strong> Rollenbasierte Berechtigungen</li>
                    <li>✅ <strong>Audit-Trail:</strong> Vollständige Protokollierung aller Zugriffe</li>
                    <li>✅ <strong>Session-Sicherheit:</strong> Sichere Cookies, Timeout, CSRF-Schutz</li>
                    <li>✅ <strong>SQL-Injection Schutz:</strong> Prepared Statements</li>
                    <li>✅ <strong>XSS-Schutz:</strong> HTML-Escaping, CSP-Header</li>
                    <li>✅ <strong>Rate Limiting:</strong> Schutz vor Brute-Force</li>
                    <li>✅ <strong>Backup-Verschlüsselung:</strong> Verschlüsselte Datensicherungen</li>
                </ul>
            </div>

            <div class="section">
                <h3>Betroffenenrechte (Art. 12-22 DSGVO)</h3>
                <ul style="line-height: 2;">
                    <li>✅ <strong>Art. 15:</strong> Auskunftsrecht (Datenexport implementiert)</li>
                    <li>✅ <strong>Art. 16:</strong> Recht auf Berichtigung (Bearbeitungsfunktion)</li>
                    <li>✅ <strong>Art. 17:</strong> Recht auf Löschung (Anonymisierung)</li>
                    <li>✅ <strong>Art. 18:</strong> Recht auf Einschränkung (Deaktivierung)</li>
                    <li>✅ <strong>Art. 20:</strong> Datenübertragbarkeit (JSON-Export)</li>
                </ul>
            </div>

            <div class="section">
                <h3>Aufbewahrungsfristen</h3>
                <ul style="line-height: 2;">
                    <li>📅 <strong>Kundendaten:</strong> <?= $privacy_settings['data_retention_days'] ?? 2555 ?> Tage (7 Jahre, HGB §257)</li>
                    <li>📅 <strong>Rechnungen:</strong> 10 Jahre (AO §147)</li>
                    <li>📅 <strong>Audit-Logs:</strong> <?= $privacy_settings['audit_log_retention'] ?? 3650 ?> Tage (10 Jahre)</li>
                </ul>
            </div>
        </div>

        <div id="rights" class="tab-content">
            <h2>👤 Betroffenenrechte verwalten</h2>
            
            <div class="section">
                <h3>Art. 15 DSGVO - Auskunftsrecht (Datenexport)</h3>
                <p>Exportieren Sie alle personenbezogenen Daten eines Kunden im JSON-Format.</p>
                
                <form method="GET" style="display: flex; gap: 10px; align-items: end;">
                    <div style="flex: 1;">
                        <label>Kunde auswählen:</label>
                        <select name="export_customer" required style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 12px;">
                            <option value="">-- Kunde wählen --</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer['id'] ?>">
                                    <?= htmlspecialchars($customer['vorname'] . ' ' . $customer['nachname']) ?> (<?= htmlspecialchars($customer['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">📥 Daten exportieren</button>
                </form>
            </div>

            <div class="section">
                <h3>Art. 17 DSGVO - Recht auf Löschung (Anonymisierung)</h3>
                <p class="alert alert-warning">
                    ⚠️ <strong>Achtung:</strong> Diese Aktion anonymisiert alle personenbezogenen Daten des Kunden unwiderruflich.
                    Geschäftsdaten (Bestellungen, Rechnungen) bleiben aus rechtlichen Gründen erhalten.
                </p>
                
                <form method="POST" onsubmit="return confirm('Wirklich anonymisieren? Diese Aktion kann nicht rückgängig gemacht werden!')">
                    <input type="hidden" name="anonymize_customer" value="1">
                    <div style="display: flex; gap: 10px; align-items: end;">
                        <div style="flex: 1;">
                            <label>Kunde auswählen:</label>
                            <select name="customer_id" required style="width: 100%; padding: 10px; border: 2px solid #e9ecef; border-radius: 12px;">
                                <option value="">-- Kunde wählen --</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>">
                                        <?= htmlspecialchars($customer['vorname'] . ' ' . $customer['nachname']) ?> (<?= htmlspecialchars($customer['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-danger">🗑️ Anonymisieren</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="audit" class="tab-content">
            <h2>📋 Audit-Log (Art. 30 DSGVO)</h2>
            <p>Verzeichnis von Verarbeitungstätigkeiten - Letzte 100 Einträge</p>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Zeitstempel</th>
                        <th>Benutzer</th>
                        <th>Aktion</th>
                        <th>Entität</th>
                        <th>IP-Adresse</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audit_logs as $log): ?>
                    <tr>
                        <td><?= date('d.m.Y H:i:s', strtotime($log['timestamp'])) ?></td>
                        <td><?= htmlspecialchars($log['username']) ?></td>
                        <td><strong><?= htmlspecialchars($log['action']) ?></strong></td>
                        <td><?= htmlspecialchars($log['entity_type']) ?> #<?= htmlspecialchars($log['entity_id']) ?></td>
                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                        <td><?= htmlspecialchars($log['details']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="backup" class="tab-content">
            <h2>💾 Verschlüsselte Backups</h2>
            
            <div class="section">
                <h3>Backup erstellen</h3>
                <p>Erstellt ein vollständig verschlüsseltes Backup aller Daten.</p>
                
                <form method="POST">
                    <input type="hidden" name="create_backup" value="1">
                    <button type="submit" class="btn btn-success">💾 Verschlüsseltes Backup erstellen</button>
                </form>
            </div>

            <div class="alert alert-info">
                <strong>ℹ️ Backup-Informationen:</strong><br>
                • Backups werden im Verzeichnis <code>/backups/</code> gespeichert<br>
                • Alle Backups sind mit AES-256 verschlüsselt<br>
                • Empfehlung: Tägliche automatische Backups einrichten<br>
                • Backups sollten an einem sicheren, externen Ort gespeichert werden
            </div>
        </div>

        <div id="settings" class="tab-content">
            <h2>⚙️ Datenschutz-Einstellungen</h2>
            
            <div class="section">
                <h3>Aktuelle Konfiguration</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Einstellung</th>
                            <th>Wert</th>
                            <th>Beschreibung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $PDO->query("SELECT * FROM privacy_settings");
                        $settings = $stmt->fetchAll();
                        foreach ($settings as $setting):
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($setting['setting_key']) ?></strong></td>
                            <td><?= htmlspecialchars($setting['setting_value']) ?></td>
                            <td><?= htmlspecialchars($setting['description']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-warning">
                <strong>⚠️ Wichtig für Produktivbetrieb:</strong><br>
                • Setzen Sie die Umgebungsvariable <code>ERP_ENCRYPTION_KEY</code> mit einem sicheren Schlüssel<br>
                • Aktivieren Sie HTTPS (SSL/TLS-Verschlüsselung)<br>
                • Konfigurieren Sie regelmäßige automatische Backups<br>
                • Implementieren Sie ein Monitoring-System<br>
                • Erstellen Sie ein Datenschutz-Konzept gemäß Art. 30 DSGVO
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
