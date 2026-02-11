<?php
/**
 * ERP-System Automatische Installation
 * 
 * Dieses Skript installiert automatisch:
 * - Alle Datenbanktabellen
 * - Admin-Benutzer (admin / ERP!)
 * - Beispieldaten
 * - Verzeichnisstruktur
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sicherheitscheck: Nach Installation löschen!
if (file_exists('INSTALLATION_COMPLETE')) {
    die('⚠️ Installation bereits abgeschlossen! Bitte löschen Sie install.php aus Sicherheitsgründen.');
}

$errors = [];
$success = [];

// Schritt 1: Verzeichnisse erstellen
$directories = [
    'uploads',
    'uploads/lieferscheine',
    'backups',
    'assets',
    'assets/css',
    'assets/images'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            $success[] = "✓ Verzeichnis erstellt: $dir";
        } else {
            $errors[] = "✗ Fehler beim Erstellen von: $dir";
        }
    } else {
        $success[] = "✓ Verzeichnis existiert: $dir";
    }
}

// Schritt 2: Datenbank-Verbindung
try {
    $db_file = __DIR__ . '/projekt1.db';
    $PDO = new PDO('sqlite:' . $db_file);
    $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $success[] = "✓ Datenbank-Verbindung hergestellt";
} catch (PDOException $e) {
    $errors[] = "✗ Datenbank-Fehler: " . $e->getMessage();
    die(implode('<br>', $errors));
}

// Schritt 3: Tabellen erstellen
$tables = [
    // Firmen
    "CREATE TABLE IF NOT EXISTS firma (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        firmenname TEXT NOT NULL,
        strasse TEXT,
        ort TEXT
    )",
    
    // Kunden
    "CREATE TABLE IF NOT EXISTS kundensystem (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kundennummer INTEGER UNIQUE,
        vorname TEXT,
        nachname TEXT,
        email TEXT,
        firma_id INTEGER,
        FOREIGN KEY (firma_id) REFERENCES firma(id)
    )",
    
    // Bestellungen
    "CREATE TABLE IF NOT EXISTS bestellungen (
        idbestellung INTEGER PRIMARY KEY AUTOINCREMENT,
        kundennummer INTEGER,
        bestellungsnummer TEXT,
        bestellungsname TEXT,
        angebotsnummer TEXT,
        auftragsnummer TEXT,
        auslieferung TEXT,
        lieferschein TEXT,
        lieferzeit INTEGER DEFAULT 14,
        status TEXT DEFAULT 'angebot',
        gesamtpreis REAL DEFAULT 0,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Angebot-Positionen
    "CREATE TABLE IF NOT EXISTS angebot_positionen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        angebot_id INTEGER,
        artikel TEXT,
        beschreibung TEXT,
        menge INTEGER,
        einzelpreis REAL,
        gesamtpreis REAL,
        FOREIGN KEY (angebot_id) REFERENCES bestellungen(idbestellung)
    )",
    
    // Produkte
    "CREATE TABLE IF NOT EXISTS produkte (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        beschreibung TEXT,
        preis REAL,
        kategorie TEXT,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Rechnungen
    "CREATE TABLE IF NOT EXISTS rechnungen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        rechnungsnummer TEXT UNIQUE,
        bestellung_id INTEGER,
        kunde_id INTEGER,
        rechnungsdatum DATE,
        faelligkeitsdatum DATE,
        nettobetrag REAL,
        mwst_betrag REAL,
        bruttobetrag REAL,
        status TEXT DEFAULT 'offen',
        zahlungsdatum DATE,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Kontenplan
    "CREATE TABLE IF NOT EXISTS kontenplan (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kontonummer TEXT UNIQUE,
        kontoname TEXT,
        kontotyp TEXT,
        aktiv INTEGER DEFAULT 1
    )",
    
    // Buchungen
    "CREATE TABLE IF NOT EXISTS buchungen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        buchungsdatum DATE,
        belegnummer TEXT,
        konto_soll TEXT,
        konto_haben TEXT,
        betrag REAL,
        beschreibung TEXT,
        kategorie TEXT,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Benutzer
    "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'user',
        can_manage_users INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Kundennotizen
    "CREATE TABLE IF NOT EXISTS kundennotizen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kunde_id INTEGER,
        notiz TEXT,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Lieferscheine
    "CREATE TABLE IF NOT EXISTS lieferscheine (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kunde_id INTEGER,
        dateiname TEXT,
        original_name TEXT,
        upload_datum DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Lagerartikel
    "CREATE TABLE IF NOT EXISTS lagerartikel (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        artikelnummer TEXT UNIQUE NOT NULL,
        artikelname TEXT NOT NULL,
        beschreibung TEXT,
        kategorie TEXT,
        einheit TEXT DEFAULT 'Stück',
        bestand REAL DEFAULT 0,
        mindestbestand REAL DEFAULT 0,
        lagerort TEXT,
        einkaufspreis REAL DEFAULT 0,
        verkaufspreis REAL DEFAULT 0,
        lieferant_id INTEGER,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Lagerbewegungen
    "CREATE TABLE IF NOT EXISTS lagerbewegungen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        artikel_id INTEGER NOT NULL,
        bewegungstyp TEXT NOT NULL,
        menge REAL NOT NULL,
        referenz TEXT,
        bemerkung TEXT,
        benutzer_id INTEGER,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Lieferanten
    "CREATE TABLE IF NOT EXISTS lieferanten (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lieferantennummer TEXT UNIQUE NOT NULL,
        firmenname TEXT NOT NULL,
        ansprechpartner TEXT,
        email TEXT,
        telefon TEXT,
        strasse TEXT,
        plz TEXT,
        ort TEXT,
        land TEXT DEFAULT 'Deutschland',
        zahlungsziel INTEGER DEFAULT 30,
        lieferzeit INTEGER DEFAULT 7,
        bemerkung TEXT,
        aktiv INTEGER DEFAULT 1,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Audit-Log
    "CREATE TABLE IF NOT EXISTS audit_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        user_id INTEGER,
        username TEXT,
        action TEXT NOT NULL,
        entity_type TEXT NOT NULL,
        entity_id TEXT,
        ip_address TEXT,
        user_agent TEXT,
        details TEXT
    )",
    
    // Privacy Settings
    "CREATE TABLE IF NOT EXISTS privacy_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $sql) {
    try {
        $PDO->exec($sql);
        $success[] = "✓ Tabelle erstellt";
    } catch (PDOException $e) {
        $errors[] = "✗ Tabellen-Fehler: " . $e->getMessage();
    }
}

// Schritt 4: Admin-Benutzer erstellen
try {
    $admin_password = password_hash('ERP!', PASSWORD_ARGON2ID);
    $stmt = $PDO->prepare("INSERT OR IGNORE INTO users (username, password, role, can_manage_users) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin', $admin_password, 'admin', 1]);
    $success[] = "✓ Admin-Benutzer erstellt (Benutzername: admin, Passwort: ERP!)";
} catch (PDOException $e) {
    $errors[] = "✗ Admin-Fehler: " . $e->getMessage();
}

// Schritt 5: Standard-Kontenplan
$konten = [
    ['1000', 'Kasse', 'Aktiva'],
    ['1200', 'Bank', 'Aktiva'],
    ['1400', 'Forderungen', 'Aktiva'],
    ['1600', 'Vorräte', 'Aktiva'],
    ['2000', 'Verbindlichkeiten', 'Passiva'],
    ['2800', 'Eigenkapital', 'Passiva'],
    ['3400', 'Umsatzerlöse', 'Ertrag'],
    ['3800', 'Umsatzsteuer', 'Ertrag'],
    ['4000', 'Wareneinkauf', 'Aufwand'],
    ['6000', 'Personalkosten', 'Aufwand'],
    ['6300', 'Miete', 'Aufwand']
];

foreach ($konten as $konto) {
    try {
        $stmt = $PDO->prepare("INSERT OR IGNORE INTO kontenplan (kontonummer, kontoname, kontotyp) VALUES (?, ?, ?)");
        $stmt->execute($konto);
    } catch (PDOException $e) {
        // Ignorieren wenn bereits vorhanden
    }
}
$success[] = "✓ Standard-Kontenplan erstellt";

// Schritt 6: Datenschutz-Einstellungen
$privacy = [
    ['data_retention_days', '2555', 'Aufbewahrungsfrist für Kundendaten (7 Jahre)'],
    ['auto_anonymize', '1', 'Automatische Anonymisierung nach Aufbewahrungsfrist'],
    ['audit_log_retention', '3650', 'Aufbewahrung Audit-Logs (10 Jahre)'],
    ['encryption_enabled', '1', 'Verschlüsselung sensibler Daten aktiviert'],
    ['backup_encryption', '1', 'Backup-Verschlüsselung aktiviert']
];

foreach ($privacy as $setting) {
    try {
        $stmt = $PDO->prepare("INSERT OR IGNORE INTO privacy_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
        $stmt->execute($setting);
    } catch (PDOException $e) {
        // Ignorieren
    }
}
$success[] = "✓ Datenschutz-Einstellungen konfiguriert";

// Schritt 7: Beispieldaten (optional)
try {
    // Beispiel-Firma
    $stmt = $PDO->prepare("INSERT OR IGNORE INTO firma (id, firmenname, strasse, ort) VALUES (1, 'Musterfirma GmbH', 'Musterstraße 123', '12345 Musterstadt')");
    $stmt->execute();
    
    // Beispiel-Produkte
    $produkte = [
        ['Laptop Business Pro', 'Hochwertiger Business-Laptop', 899.00, 'Hardware'],
        ['Office Software Lizenz', 'Jahreslizenz Office-Paket', 149.00, 'Software'],
        ['IT-Support Stunde', 'Professioneller IT-Support', 85.00, 'Dienstleistung']
    ];
    
    foreach ($produkte as $produkt) {
        $stmt = $PDO->prepare("INSERT OR IGNORE INTO produkte (name, beschreibung, preis, kategorie) VALUES (?, ?, ?, ?)");
        $stmt->execute($produkt);
    }
    
    $success[] = "✓ Beispieldaten erstellt";
} catch (PDOException $e) {
    // Optional, Fehler ignorieren
}

// Schritt 8: Installations-Marker erstellen
file_put_contents('INSTALLATION_COMPLETE', date('Y-m-d H:i:s'));
$success[] = "✓ Installation abgeschlossen!";

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP-System Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, rgba(26,26,46,0.97) 0%, rgba(22,33,62,0.97) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            background: linear-gradient(135deg, #C9A227 0%, #D4AF37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.5rem;
            margin-bottom: 30px;
            text-align: center;
        }
        .success-box {
            background: #d4edda;
            border-left: 5px solid #28a745;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            color: #155724;
        }
        .error-box {
            background: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            color: #721c24;
        }
        .info-box {
            background: #d1ecf1;
            border-left: 5px solid #0c5460;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            color: #0c5460;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #C9A227 0%, #D4AF37 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            margin: 10px 5px;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(201,162,39,0.3);
        }
        .btn-danger {
            background: linear-gradient(135deg, #EF5350 0%, #E53935 100%);
        }
        .text-center { text-align: center; }
        ul { list-style: none; padding: 0; }
        li { padding: 8px 0; }
        .warning {
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 ERP-System Installation</h1>
        
        <?php if (empty($errors)): ?>
            <div class="success-box">
                <h2>✅ Installation erfolgreich abgeschlossen!</h2>
            </div>
            
            <div class="info-box">
                <h3>📋 Installierte Komponenten:</h3>
                <ul>
                    <?php foreach ($success as $msg): ?>
                        <li><?= $msg ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="info-box">
                <h3>🔐 Zugangsdaten:</h3>
                <p><strong>Benutzername:</strong> admin</p>
                <p><strong>Passwort:</strong> ERP!</p>
                <p><strong>URL:</strong> <a href="login.php">login.php</a></p>
            </div>
            
            <div class="warning">
                <h3>⚠️ WICHTIG - Sicherheitshinweise:</h3>
                <ol style="list-style: decimal; padding-left: 20px;">
                    <li><strong>Löschen Sie install.php sofort!</strong> (Sicherheitsrisiko)</li>
                    <li>Ändern Sie das Admin-Passwort nach dem ersten Login</li>
                    <li>Aktivieren Sie HTTPS für Produktivbetrieb</li>
                    <li>Setzen Sie Umgebungsvariable ERP_ENCRYPTION_KEY</li>
                    <li>Konfigurieren Sie regelmäßige Backups</li>
                    <li>Prüfen Sie Dateirechte (uploads/, backups/)</li>
                </ol>
            </div>
            
            <div class="text-center" style="margin-top: 30px;">
                <a href="login.php" class="btn">🔓 Zum Login</a>
                <a href="?delete_installer=1" class="btn btn-danger" onclick="return confirm('install.php wirklich löschen?')">🗑️ Installer löschen</a>
            </div>
            
        <?php else: ?>
            <div class="error-box">
                <h2>❌ Fehler bei der Installation</h2>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="success-box">
                    <h3>Erfolgreich:</h3>
                    <ul>
                        <?php foreach ($success as $msg): ?>
                            <li><?= $msg ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="text-center" style="margin-top: 30px;">
                <a href="install.php" class="btn">🔄 Erneut versuchen</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Installer löschen wenn angefordert
if (isset($_GET['delete_installer']) && file_exists('INSTALLATION_COMPLETE')) {
    if (unlink(__FILE__)) {
        header('Location: login.php?installed=1');
        exit;
    }
}
?>
