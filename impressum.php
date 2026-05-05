<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Impressum - Gesetzliche Angaben gemäß TMG und DSGVO">
    <title>Impressum - ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

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
            margin-bottom: 2rem;
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

        h2::before {
            content: attr(data-icon);
            font-size: 1.5rem;
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

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }

        .info-item {
            background: var(--bg-muted);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.5rem;
        }

        .info-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
            line-height: 1.6;
            word-break: break-word;
        }

        .info-box {
            background: var(--bg-muted);
            border-left: 4px solid var(--primary);
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin: 2rem 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .info-box strong {
            color: var(--text);
        }

        .contact-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }

        @media (max-width: 768px) {
            .contact-section {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 2rem 1.5rem;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            h1 { font-size: 1.5rem; }
            h2 { font-size: 1.1rem; }
        }

        .compliance-checklist {
            background: var(--bg-muted);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .compliance-checklist ul {
            list-style: none;
            padding-left: 0;
        }

        .compliance-checklist li {
            margin-bottom: 0.75rem;
        }

        .compliance-checklist li::before {
            content: "✓ ";
            color: #16a34a;
            font-weight: bold;
            margin-right: 0.5rem;
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

        .divider {
            height: 1px;
            background: var(--border);
            margin: 2rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="web_oberflaeche.php" class="btn-back">← Zurück zum Dashboard</a>
        
        <div class="page-header">
            <h1>📋 Impressum</h1>
            <p>Gesetzliche Angaben gemäß § 5 TMG</p>
        </div>

        <!-- Unternehmensangaben -->
        <h2>🏢 Angaben gemäß § 5 TMG</h2>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">👤 Inhaber / Geschäftsführer</div>
                <div class="info-value">Maximilian Merz</div>
            </div>

            <div class="info-item">
                <div class="info-label">📍 Geschäftsadresse</div>
                <div class="info-value">
                    Lindenstraße 37<br>
                    95233 Helmbrechts<br>
                    Deutschland
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">📞 Telefon</div>
                <div class="info-value">
                    <a href="tel:017661127121">+49 176 6112 7121</a>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">📧 E-Mail</div>
                <div class="info-value">
                    <a href="mailto:maximilian.merz@hotmail.com">maximilian.merz@hotmail.com</a>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Handelsregister & Steuern -->
        <h2>📊 Registereintrag & Steuernummer</h2>
        
        <div class="card">
            <div class="card-header">
                <div class="card-icon">📋</div>
                <div class="card-content">
                    <h3>Handelsregister</h3>
                    <p>Eintragung im Handelsregister:</p>
                    <p><strong>Registergericht:</strong> [Amtsgericht]<br>
                    <strong>Registernummer:</strong> [HRB/HRA Nummer]</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-icon">💰</div>
                <div class="card-content">
                    <h3>Steuerdaten</h3>
                    <p><strong>Steuernummer:</strong> [Steuernummer]<br>
                    <strong>Umsatzsteuer-ID (USt-IdNr.):</strong> [USt-IdNr. gemäß § 27 a UStG]</p>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Datenschutz & Compliance -->
        <h2>🔒 Datenschutz & DSGVO</h2>
        
        <div class="card">
            <div class="card-header">
                <div class="card-icon">🛡️</div>
                <div class="card-content">
                    <h3>Datenschutzerklärung</h3>
                    <p>Für detaillierte Informationen zum Schutz Ihrer personenbezogenen Daten besuchen Sie bitte unsere <strong><a href="datenschutz.php">Datenschutzerklärung</a></strong>.</p>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Cookie-Richtlinie -->
        <h2>🍪 Cookie-Richtlinie</h2>
        
        <div class="card">
            <p>Diese Website verwendet Cookies zu verschiedenen Zwecken:</p>
            <ul>
                <li><strong>Notwendige Cookies:</strong> Für die grundlegende Funktionalität erforderlich</li>
                <li><strong>Funktionale Cookies:</strong> Zur Verbesserung der Benutzererfahrung</li>
                <li><strong>Analyse-Cookies:</strong> Zur Nutzungsanalyse und Optimierung</li>
                <li><strong>Marketing-Cookies:</strong> Für Retargeting und Werbezwecke</li>
            </ul>
            <p>Sie können Ihre Cookie-Einstellungen jederzeit über das Cookie-Banner anpassen.</p>
        </div>

        <div class="divider"></div>

        <!-- Haftungsausschluss -->
        <h2>⚖️ Haftungsausschluss (Disclaimer)</h2>

        <div class="card">
            <h3>Haftung für Inhalte</h3>
            <p>Die Inhalte unserer Seiten wurden mit größter Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte können wir jedoch keine Gewähr übernehmen. Als Diensteanbieter sind wir gemäß § 7 Abs. 1 TMG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich.</p>
        </div>

        <div class="card">
            <h3>Haftung für Links</h3>
            <p>Unser Angebot enthält Links zu externen Webseiten Dritter, auf deren Inhalte wir keinen Einfluss haben. Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen. Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich.</p>
        </div>

        <div class="card">
            <h3>Urheberrecht</h3>
            <p>Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. Die Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der Grenzen des Urheberrechts bedürfen der schriftlichen Zustimmung des Autors oder Erstellers.</p>
        </div>

        <div class="divider"></div>

        <!-- Compliance Checkliste -->
        <h2>✓ Gesetzliche Compliance</h2>
        
        <div class="compliance-checklist">
            <ul>
                <li>DSGVO-Konformität (EU-Verordnung 2016/679)</li>
                <li>TMG-Konformität (Telemediengesetz)</li>
                <li>ePrivacy-Richtlinie (Cookie-Einwilligung)</li>
                <li>Verbraucherschutz nach BGB</li>
                <li>Sichere Datenübertragung (HTTPS/TLS)</li>
                <li>AES-256-GCM Verschlüsselung für sensitive Daten</li>
            </ul>
        </div>

        <div class="divider"></div>

        <!-- Kontakt -->
        <h2>📞 Kontakt & Datenschutzbeauftragter</h2>

        <div class="contact-section">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">✉️</div>
                    <div class="card-content">
                        <h3>Technischer Support</h3>
                        <p><a href="mailto:maximilian.merz@hotmail.com">maximilian.merz@hotmail.com</a></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-icon">📱</div>
                    <div class="card-content">
                        <h3>Telefonische Erreichbarkeit</h3>
                        <p><a href="tel:017661127121">+49 176 6112 7121</a></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Disclaimer -->
        <div class="info-box" style="border-left-color: #ef4444; margin: 2rem 0;">
            <p>
                <strong>⚠️ Wichtiger Hinweis:</strong> Dieses Impressum ist mit aktuellen Daten ausgefüllt. Bitte überprüfen Sie regelmäßig die Aktualität und aktualisieren Sie bei Bedarf. Ein falsches oder unvollständiges Impressum kann zu Abmahnungen führen. Konsultieren Sie im Zweifelsfall einen Rechtsanwalt.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer-section">
            <p>
                <strong>Letzte Aktualisierung:</strong> <?= date('d.m.Y H:i:s') ?><br>
                <a href="datenschutz.php">Datenschutzerklärung</a> | 
                <a href="dsgvo_compliance.php">DSGVO-Compliance</a> |
                <a href="web_oberflaeche.php">Dashboard</a>
            </p>
        </div>
    </div>
</body>
</html>