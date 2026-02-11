<?php
// Database configuration - Using SQLite for simplicity
$PDO = null;

try {
    // Use SQLite database
    $PDO = new PDO('sqlite:projekt1.db');
    $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


    // Create tables if they don't exist
    $PDO->exec("CREATE TABLE IF NOT EXISTS firma (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        firmenname TEXT NOT NULL,
        strasse TEXT,
        ort TEXT
    )");

    $PDO->exec("CREATE TABLE IF NOT EXISTS kundensystem (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kundennummer INTEGER,
        vorname TEXT,
        nachname TEXT,
        email TEXT,
        firma_id INTEGER,
        FOREIGN KEY (firma_id) REFERENCES firma(id)
    )");

    $PDO->exec("CREATE TABLE IF NOT EXISTS bestellungen (
        idbestellung INTEGER PRIMARY KEY AUTOINCREMENT,
        kundennummer INTEGER,
        bestellungsnummer TEXT,
        bestellungsname TEXT,
        angebotsnummer TEXT,
        auftragsnummer TEXT,
        auslieferung TEXT,
        lieferschein TEXT,
        lieferzeit TEXT,
        status TEXT DEFAULT 'neu',
        gesamtpreis DECIMAL(10,2)
    )");

    // Tabelle für Angebots-Positionen erstellen
    $PDO->exec("CREATE TABLE IF NOT EXISTS angebot_positionen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        angebot_id INTEGER,
        artikel TEXT,
        beschreibung TEXT,
        menge INTEGER,
        einzelpreis DECIMAL(10,2),
        gesamtpreis DECIMAL(10,2),
        FOREIGN KEY (angebot_id) REFERENCES bestellungen(idbestellung)
    )");

    // Tabelle für Benutzeranfragen erstellen
    $PDO->exec("CREATE TABLE IF NOT EXISTS user_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Users Tabelle erweitern
    $PDO->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'user',
        can_manage_users INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabelle für Kundennotizen
    $PDO->exec("CREATE TABLE IF NOT EXISTS kundennotizen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kunde_id INTEGER,
        notiz TEXT,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (kunde_id) REFERENCES kundensystem(id)
    )");

    // Tabelle für Lieferscheine
    $PDO->exec("CREATE TABLE IF NOT EXISTS lieferscheine (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kunde_id INTEGER,
        dateiname TEXT,
        original_name TEXT,
        upload_datum DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (kunde_id) REFERENCES kundensystem(id)
    )");

    // Tabelle für Bestellungen erweitern um Erstellungsdatum
    try {
        $PDO->exec("ALTER TABLE bestellungen ADD COLUMN erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP");
    } catch (Exception $e) {
        // Spalte existiert bereits
    }

    // Tabelle für Produkte
    $PDO->exec("CREATE TABLE IF NOT EXISTS produkte (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        beschreibung TEXT,
        preis DECIMAL(10,2),
        kategorie TEXT,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabelle für Rechnungen
    $PDO->exec("CREATE TABLE IF NOT EXISTS rechnungen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        rechnungsnummer TEXT UNIQUE,
        bestellung_id INTEGER,
        kunde_id INTEGER,
        rechnungsdatum DATE,
        faelligkeitsdatum DATE,
        nettobetrag DECIMAL(10,2),
        mwst_betrag DECIMAL(10,2),
        bruttobetrag DECIMAL(10,2),
        status TEXT DEFAULT 'offen',
        zahlungsdatum DATE,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (bestellung_id) REFERENCES bestellungen(idbestellung),
        FOREIGN KEY (kunde_id) REFERENCES kundensystem(id)
    )");

    // Tabelle für Buchungen
    $PDO->exec("CREATE TABLE IF NOT EXISTS buchungen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        buchungsdatum DATE,
        belegnummer TEXT,
        konto_soll TEXT,
        konto_haben TEXT,
        betrag DECIMAL(10,2),
        beschreibung TEXT,
        kategorie TEXT,
        erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabelle für Kontenplan
    $PDO->exec("CREATE TABLE IF NOT EXISTS kontenplan (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kontonummer TEXT UNIQUE,
        kontoname TEXT,
        kontotyp TEXT,
        aktiv INTEGER DEFAULT 1
    )");

    // Standard-Konten einfügen falls leer
    $stmt = $PDO->prepare("SELECT COUNT(*) as count FROM kontenplan");
    $stmt->execute();
    if ($stmt->fetch()['count'] == 0) {
        $standard_konten = [
            ['1000', 'Kasse', 'Aktiva'],
            ['1200', 'Bank', 'Aktiva'],
            ['1400', 'Forderungen', 'Aktiva'],
            ['3400', 'Warenerlöse 19%', 'Ertrag'],
            ['3800', 'Umsatzsteuer 19%', 'Verbindlichkeit'],
            ['4400', 'Wareneinkauf 19%', 'Aufwand'],
            ['1576', 'Vorsteuer 19%', 'Aktiva'],
            ['2000', 'Verbindlichkeiten', 'Passiva']
        ];
        foreach ($standard_konten as $konto) {
            $stmt = $PDO->prepare("INSERT INTO kontenplan (kontonummer, kontoname, kontotyp) VALUES (?, ?, ?)");
            $stmt->execute($konto);
        }
    }

    // LDAP-Konfiguration
    $PDO->exec("CREATE TABLE IF NOT EXISTS ldap_config (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        server TEXT,
        port INTEGER DEFAULT 389,
        base_dn TEXT,
        bind_dn TEXT,
        bind_password TEXT,
        user_filter TEXT DEFAULT '(uid=%s)',
        enabled INTEGER DEFAULT 0,
        ssl_enabled INTEGER DEFAULT 0
    )");

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Make sure $PDO is available globally
if (!$PDO) {
    die("No database connection available.");
}
?>