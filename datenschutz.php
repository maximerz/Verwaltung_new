<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Datenschutzerklärung - DSGVO konform">
    <title>Datenschutzerklärung - ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'includes/modern-light-style.php'; ?>
    <style>
        :root {
            --primary: #14b8a6;
            --dark: #1f2937;
            --text: #374151;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --bg: #ffffff;
            --bg-muted: #f9fafb;
        }

        [data-theme="dark"] {
            --primary: #14b8a6;
            --dark: #f3f4f6;
            --text: #e5e7eb;
            --text-muted: #d1d5db;
            --border: #374151;
            --bg: #111827;
            --bg-muted: #1f2937;
        }

        body {
            padding: 2rem 0;
            background-color: var(--bg);
            color: var(--text);
        }

        .container {
            max-width: 1000px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            color: var(--text);
            margin-left: -0.25rem;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, #0d9488 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 1rem;
            margin: -2rem 0 3rem 0;
            box-shadow: 0 10px 30px rgba(20, 184, 166, 0.2);
        }

        .page-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .page-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.95;
            font-size: 1.1rem;
        }

        h1 {
            color: var(--text);
            margin-bottom: 1rem;
            font-weight: 700;
            font-size: 2rem;
        }

        h2 {
            color: var(--text);
            margin-top: 2.5rem;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: 600;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        h3 {
            color: var(--text);
            font-size: 1.05rem;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        p {
            line-height: 1.8;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        ul, ol {
            line-height: 1.8;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            padding-left: 2rem;
        }

        li {
            margin-bottom: 0.5rem;
        }

        a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Card System */
        .card {
            background: var(--bg-muted);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 2rem;
            margin: 1.5rem 0;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        [data-theme="dark"] .card:hover {
            box-shadow: 0 10px 30px rgba(20, 184, 166, 0.1);
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, #0d9488 100%);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .card-content h3 {
            margin-top: 0;
        }

        .info-box {
            background: var(--bg-muted);
            border-left: 4px solid var(--primary);
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin: 1.5rem 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .info-box strong {
            color: var(--text);
        }

        .table-responsive {
            margin: 1.5rem 0;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        table {
            background: var(--bg-muted);
            font-size: 0.95rem;
            width: 100%;
            margin: 0;
        }

        th {
            background: var(--primary);
            color: white;
            font-weight: 600;
            padding: 1rem;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-muted);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .highlight-box {
            background: var(--bg-muted);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .highlight-box ul {
            list-style: none;
            padding-left: 0;
        }

        .highlight-box li::before {
            content: "✓ ";
            color: #16a34a;
            font-weight: bold;
            margin-right: 0.5rem;
        }

        .divider {
            height: 1px;
            background: var(--border);
            margin: 2rem 0;
        }

        .footer-section {
            background: var(--bg-muted);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 2rem;
            margin-top: 3rem;
            text-align: center;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            h1 { font-size: 1.5rem; }
            h2 { font-size: 1.1rem; }
            body { padding: 1rem 0; }
            .page-header {
                padding: 2rem 1.5rem;
            }
            .page-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="web_oberflaeche.php" class="btn-back">← Zurück zum Dashboard</a>
        
        <h1>🔒 Datenschutzerklärung</h1>
        <p><strong>Stand:</strong> <?= date('d. F Y') ?></p>
        <p>Diese Datenschutzerklärung erläutert, wie Ihre Daten verarbeitet werden und welche Rechte Sie haben.</p>

        <!-- 1. Verantwortlicher -->
        <h2 data-icon="👤">1. Verantwortlicher für die Datenverarbeitung</h2>
        <div class="info-box">
            <p><strong>Kontakt:</strong></p>
            <p>
                Maximilian Merz<br>
                Lindenstraße 37<br>
                95233 Helmbrechts<br>
                Germany<br>
                <br>
                E-Mail: <a href="mailto:maximilian.merz@hotmail.com">maximilian.merz@hotmail.com</a><br>
                Telefon: <a href="tel:017661127121">017661127121</a>
            </p>
        </div>

        <!-- 2. Erhobene Daten -->
        <h2>2. Welche Daten wir verarbeiten</h2>
        <p>Wir verarbeiten folgende Kategorien personenbezogener Daten:</p>
        <ul>
            <li><strong>Kundenangaben:</strong> Name, Vorname, E-Mail-Adresse, Telefon, Adresse</li>
            <li><strong>Geschäftsdaten:</strong> Rechnungs- und Versandinformationen, Zahlungsdaten</li>
            <li><strong>Bestellinformationen:</strong> Bestellverlauf, Lieferscheine, Angebote</li>
            <li><strong>Benutzerdaten:</strong> Benutzername, Passwort (gehashed), Benutzerrolle</li>
            <li><strong>Technische Daten:</strong> IP-Adresse, Browser-Typ, Zugriffsverlauf</li>
            <li><strong>Cookie-Daten:</strong> Basierend auf Ihrer Cookie-Einwilligung</li>
        </ul>

        <!-- 3. Zwecke der Verarbeitung -->
        <h2>3. Zwecke der Datenverarbeitung</h2>
        <p>Ihre Daten werden für folgende Zwecke verarbeitet:</p>
        <ul>
            <li>Erfüllung von Kundenaufträgen und Verträgen</li>
            <li>Rechnungsstellung und Zahlungsabwicklung</li>
            <li>Benutzerverwaltung und Authentifizierung</li>
            <li>Geschäftskorrespondenz und Kundenservice</li>
            <li>Sicherheit und Schutz vor Missbrauch</li>
            <li>Einhaltung rechtlicher Verpflichtungen</li>
            <li>Analyse und Verbesserung unserer Dienste</li>
        </ul>

        <!-- 4. Rechtliche Grundlagen -->
        <h2>4. Rechtliche Grundlagen (DSGVO)</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Verarbeitungstyp</th>
                        <th>Rechtliche Grundlage</th>
                        <th>Artikel DSGVO</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Vertragserfüllung</td>
                        <td>Notwendig zur Erfüllung eines Vertrags</td>
                        <td>Art. 6 Abs. 1 b)</td>
                    </tr>
                    <tr>
                        <td>Rechtliche Verpflichtung</td>
                        <td>Erfüllung einer rechtlichen Pflicht</td>
                        <td>Art. 6 Abs. 1 c)</td>
                    </tr>
                    <tr>
                        <td>Berechtigte Interessen</td>
                        <td>Erforderlich für berechtigte Interessen</td>
                        <td>Art. 6 Abs. 1 f)</td>
                    </tr>
                    <tr>
                        <td>Cookie-Zustimmung</td>
                        <td>Freiwillige Einwilligung</td>
                        <td>Art. 7</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 5. Speicherdauer -->
        <h2>5. Speicherdauer Ihrer Daten</h2>
        <p>Wir speichern Ihre Daten nur so lange wie nötig:</p>
        <ul>
            <li><strong>Kundenbeziehung:</strong> Während der aktiven Geschäftsbeziehung + 3 Jahre</li>
            <li><strong>Rechnungen & Belege:</strong> 10 Jahre (HGB § 257)</li>
            <li><strong>Umsatzsteuerbelege:</strong> 10 Jahre (UStG § 90)</li>
            <li><strong>Session-Cookies:</strong> Bis Sie sich abmelden</li>
            <li><strong>Persistente Cookies:</strong> Max. 365 Tage (mit Einwilligung)</li>
            <li><strong>Audit-Logs:</strong> 90 Tage aus Sicherheitsgründen</li>
        </ul>

        <!-- 6. Ihre Rechte -->
        <h2>6. Ihre Rechte nach der DSGVO</h2>
        <p>Sie haben folgende Rechte bezüglich Ihrer persönlichen Daten:</p>
        <ul>
            <li><strong>Auskunftsrecht (Art. 15):</strong> Sie können jederzeit erfragen, welche Daten wir über Sie speichern</li>
            <li><strong>Berichtigungsrecht (Art. 16):</strong> Unrichtige Daten können korrigiert werden</li>
            <li><strong>Löschungsrecht (Art. 17):</strong> Unter bestimmten Umständen können Daten gelöscht werden</li>
            <li><strong>Recht auf Einschränkung (Art. 18):</strong> Sie können die Verarbeitung einschränken lassen</li>
            <li><strong>Datenübertragbarkeit (Art. 20):</strong> Ihre Daten können in strukturierter Form exportiert werden</li>
            <li><strong>Widerspruchsrecht (Art. 21):</strong> Sie können der Verarbeitung widersprechen</li>
            <li><strong>Recht auf Beschwerde:</strong> Sie können sich an eine Datenschutzbehörde wenden</li>
        </ul>

        <!-- 7. Datensicherheit -->
        <h2>7. Datensicherheit & Verschlüsselung</h2>
        <p>Wir schützen Ihre Daten mit modernen Sicherheitsmaßnahmen:</p>
        <ul>
            <li><strong>AES-256-GCM Verschlüsselung:</strong> Für sensitive Kundendaten</li>
            <li><strong>HTTPS/TLS:</strong> Verschlüsselte Datenübertragung</li>
            <li><strong>Passwort-Hashing:</strong> bcrypt mit Salting</li>
            <li><strong>Zugriffskontrolle:</strong> Rollenbasierte Berechtigungen</li>
            <li><strong>Session-Sicherheit:</strong> Sichere Session-Tokens</li>
            <li><strong>SQL-Injection Schutz:</strong> Prepared Statements</li>
            <li><strong>XSS-Schutz:</strong> HTML-Entity-Encoding</li>
            <li><strong>CSRF-Schutz:</strong> Token-Validierung</li>
            <li><strong>Regelmäßige Backups:</strong> Mit Verschlüsselung</li>
        </ul>

        <!-- 8. Cookies & Tracking -->
        <h2>8. Cookies & Analyse-Tools</h2>
        <p>Diese Website verwendet Cookies für verschiedene Zwecke:</p>
        
        <h3>Notwendige Cookies:</h3>
        <ul>
            <li>Session-Management</li>
            <li>Authentifizierung und Login</li>
            <li>CSRF-Schutz</li>
            <li>Grundlegende Funktionalität</li>
        </ul>

        <h3>Optionale Cookies (mit Ihrer Einwilligung):</h3>
        <ul>
            <li><strong>Funktionale Cookies:</strong> Theme-Präferenzen, Sprache</li>
            <li><strong>Analyse-Cookies:</strong> Nutzungsstatistiken (z.B. Google Analytics)</li>
            <li><strong>Marketing-Cookies:</strong> Retargeting und Werbung</li>
        </ul>

        <p>Sie können Ihre Cookie-Einstellungen jederzeit über das Cookie-Banner anpassen.</p>

        <!-- 9. Datenweitergabe -->
        <h2>9. Weitergabe an Dritte</h2>
        <p>Ihre Daten werden normalerweise nicht an Dritte weitergegeben, außer:</p>
        <ul>
            <li>Mit Ihrer ausdrücklichen Einwilligung</li>
            <li>Wenn gesetzlich erforderlich (z.B. Behörden, Gerichte)</li>
            <li>Zur Vertragserfüllung (z.B. Logistik-Partner)</li>
            <li>Wenn notwendig für die Strafverfolgung</li>
        </ul>

        <!-- 10. Externe Links & Datenschutz -->
        <h2>10. Externe Links & Datenschutz</h2>
        <p>
            Diese Website enthält möglicherweise Links zu externen Websites. Wir haben keine Kontrolle über 
            deren Datenschutzrichtlinien. Bitte lesen Sie die Datenschutzerklärung dieser Seiten, 
            bevor Sie Ihre Daten weitergeben.
        </p>

        <!-- 11. Datenschutzbeauftragter -->
        <h2 data-icon="🔒">11. Kontakt zum Datenschutz</h2>
        <div class="info-box">
            <p><strong>Datenschutzbeauftragter:</strong></p>
            <p>
                Name: Maximilian Merz<br>
                E-Mail: <a href="mailto:maximilian.merz@hotmail.com">maximilian.merz@hotmail.com</a><br>
                Telefon: <a href="tel:017661127121">017661127121</a>
            </p>
            <p style="margin-top: 1.5rem;">
                <strong>Beschwerde bei Datenschutzbehörde:</strong><br>
                Landesamt für Datenschutz [Ihr Bundesland]<br>
                <a href="https://www.bfdi.bund.de/" target="_blank">Bundesbeauftragter für Datenschutz und Informationsfreiheit</a>
            </p>
        </div>

        <!-- 12. Änderungen -->
        <h2>12. Änderungen dieser Datenschutzerklärung</h2>
        <p>
            Wir können diese Datenschutzerklärung jederzeit aktualisieren. Die aktuelle Version 
            ist immer auf dieser Seite verfügbar. Wesentliche Änderungen werden Ihnen mitgeteilt.
        </p>

        <!-- Compliance Info -->
        <div class="info-box" style="background: var(--bg-muted); border-left-color: #16a34a;">
            <p>
                ✓ <strong>Diese Datenschutzerklärung entspricht den Anforderungen der:</strong><br>
                • DSGVO (EU-Verordnung 2016/679)<br>
                • BDSG (Bundesdatenschutzgesetz)<br>
                • ePrivacy-Richtlinie (2002/58/EG)<br>
                • TMG (Telemediengesetz)
            </p>
        </div>

        <!-- Footer -->
        <div style="margin-top: 3rem; padding: 1.5rem; text-align: center; color: var(--text-muted); border-top: 1px solid var(--border);">
            <p>
                Zuletzt aktualisiert: <?= date('d.m.Y H:i:s') ?><br>
                <a href="impressum.php">Impressum</a> | 
                <a href="dsgvo_compliance.php">DSGVO-Compliance</a>
            </p>
        </div>
    </div>
</body>
</html>