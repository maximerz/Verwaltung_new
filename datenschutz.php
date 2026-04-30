<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datenschutzerklärung - ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <?php include 'includes/modern-light-style.php'; ?>
    <style>
        body { padding: 2rem 0; }
        .container { max-width: 900px; }
        .btn-back { margin-bottom: 2rem; text-decoration: none; }
        h3 { font-size: 1.1rem; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <a href="web_oberflaeche.php" class="btn-back">← Zurück</a>
        
        <h1>Datenschutzerklärung</h1>
        <p><strong>Stand:</strong> <?= date('d.m.Y') ?></p>
        
        <h2>1. Verantwortlicher</h2>
        <p>Verantwortlich für die Datenverarbeitung ist der Betreiber dieses ERP-Systems.</p>
        
        <h2>2. Erhobene Daten</h2>
        <p>Wir verarbeiten folgende personenbezogene Daten:</p>
        <ul>
            <li>Kundendaten (Name, E-Mail, Adresse)</li>
            <li>Firmendaten</li>
            <li>Bestellinformationen</li>
            <li>Rechnungsdaten</li>
            <li>Benutzerdaten (Login-Informationen)</li>
        </ul>
        
        <h2>3. Zweck der Verarbeitung</h2>
        <p>Die Datenverarbeitung erfolgt zur:</p>
        <ul>
            <li>Verwaltung von Kundenbeziehungen</li>
            <li>Abwicklung von Bestellungen und Rechnungen</li>
            <li>Erfüllung vertraglicher Pflichten</li>
            <li>Einhaltung gesetzlicher Aufbewahrungsfristen</li>
        </ul>
        
        <h2>4. Datensicherheit</h2>
        <p>Ihre Daten werden verschlüsselt gespeichert:</p>
        <ul>
            <li><strong>AES-256-GCM Verschlüsselung</strong> für personenbezogene Daten</li>
            <li><strong>Sichere Passwort-Hashes</strong> (bcrypt)</li>
            <li><strong>HTTPS-Verschlüsselung</strong> bei Datenübertragung</li>
            <li><strong>Zugriffskontrolle</strong> durch Benutzerauthentifizierung</li>
            <li><strong>Regelmäßige Backups</strong> mit Verschlüsselung</li>
        </ul>
        
        <h2>5. Ihre Rechte</h2>
        <p>Sie haben folgende Rechte:</p>
        <ul>
            <li><strong>Auskunftsrecht</strong> (Art. 15 DSGVO)</li>
            <li><strong>Recht auf Berichtigung</strong> (Art. 16 DSGVO)</li>
            <li><strong>Recht auf Löschung</strong> (Art. 17 DSGVO)</li>
            <li><strong>Recht auf Einschränkung</strong> (Art. 18 DSGVO)</li>
            <li><strong>Recht auf Datenübertragbarkeit</strong> (Art. 20 DSGVO)</li>
            <li><strong>Widerspruchsrecht</strong> (Art. 21 DSGVO)</li>
        </ul>
        
        <h2>6. Speicherdauer</h2>
        <p>Daten werden gespeichert:</p>
        <ul>
            <li>Solange für die Geschäftsbeziehung erforderlich</li>
            <li>Gemäß gesetzlicher Aufbewahrungsfristen (z.B. 10 Jahre für Rechnungen)</li>
            <li>Bis zum Widerruf der Einwilligung</li>
        </ul>
        
        <h2>7. Datenweitergabe</h2>
        <p>Ihre Daten werden nicht an Dritte weitergegeben, außer:</p>
        <ul>
            <li>Bei gesetzlicher Verpflichtung</li>
            <li>Mit Ihrer ausdrücklichen Einwilligung</li>
            <li>Zur Vertragserfüllung notwendig</li>
        </ul>
        
        <h2>8. Technische Maßnahmen</h2>
        <ul>
            <li>Verschlüsselte Datenbank (SQLite mit AES-256)</li>
            <li>Session-Management mit sicheren Cookies</li>
            <li>Schutz vor SQL-Injection durch Prepared Statements</li>
            <li>XSS-Schutz durch htmlspecialchars()</li>
            <li>CSRF-Schutz durch Session-Tokens</li>
        </ul>
        
        <h2>9. Kontakt</h2>
        <p>Bei Fragen zum Datenschutz wenden Sie sich bitte an den Systemadministrator.</p>
        
        <div style="margin-top: 3rem; padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
            <p><strong>Hinweis:</strong> Diese Datenschutzerklärung entspricht den Anforderungen der DSGVO (EU-Verordnung 2016/679).</p>
        </div>
    </div>
</body>
</html>