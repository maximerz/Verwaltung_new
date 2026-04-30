<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>DSGVO-Compliance Dokumentation - ERP System</title>
    <style>
        :root { --primary: linear-gradient(135deg, #5B7DB1 0%, #D4AF37 100%); --shadow: 0 15px 50px rgba(201,162,39,0.15); }
        body { font-family: 'Inter', Arial, sans-serif; margin: 0; padding: 20px; background: #F0F2F5; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; background: rgba(255,255,255,0.98); padding: 40px; border-radius: 25px; box-shadow: var(--shadow); }
        h1, h2, h3 { background: var(--primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700; }
        .section { margin: 30px 0; padding: 25px; background: rgba(201,162,39,0.05); border-radius: 15px; border-left: 5px solid #5B7DB1; }
        .article { margin: 20px 0; padding: 15px; background: white; border-radius: 10px; border-left: 3px solid #5B7DB1; }
        .check { color: #00B894; font-weight: bold; }
        .btn { background: var(--primary); color: white; padding: 12px 24px; border: none; border-radius: 50px; text-decoration: none; display: inline-block; font-weight: 600; margin: 10px 5px; }
        ul { line-height: 2; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { border: 1px solid #e9ecef; padding: 12px; text-align: left; }
        table th { background: var(--primary); color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 DSGVO-Compliance Dokumentation</h1>
        <p style="text-align: center; color: #6c757d; font-size: 1.1rem;">Vollständige Dokumentation der Datenschutz-Grundverordnung Konformität</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="dsgvo_verwaltung.php" class="btn">← Zurück zur DSGVO-Verwaltung</a>
            <a href="web_oberflaeche.php" class="btn">🏠 Dashboard</a>
        </div>

        <div class="section">
            <h2>✅ Compliance-Status: DSGVO-KONFORM</h2>
            <p>Dieses ERP-System erfüllt alle Anforderungen der EU-Datenschutz-Grundverordnung (DSGVO) für den Einsatz in deutschen Unternehmen.</p>
        </div>

        <div class="section">
            <h2>📜 Artikel 5 DSGVO - Grundsätze der Verarbeitung</h2>
            
            <div class="article">
                <h3>Art. 5 Abs. 1 lit. a) - Rechtmäßigkeit, Verarbeitung nach Treu und Glauben, Transparenz</h3>
                <p><span class="check">✓ ERFÜLLT:</span></p>
                <ul>
                    <li>Klare Datenschutzerklärung vorhanden (datenschutz.php)</li>
                    <li>Cookie-Banner mit Einwilligungsmöglichkeit</li>
                    <li>Transparente Information über Datenverarbeitung</li>
                    <li>Benutzer werden über Zweck der Datenerhebung informiert</li>
                </ul>
            </div>

            <div class="article">
                <h3>Art. 5 Abs. 1 lit. b) - Zweckbindung</h3>
                <p><span class="check">✓ ERFÜLLT:</span></p>
                <ul>
                    <li>Daten werden nur für ERP-Zwecke verarbeitet (Kunden-, Bestell-, Rechnungsverwaltung)</li>
                    <li>Keine Weitergabe an Dritte ohne Rechtsgrundlage</li>
                    <li>Zweckänderung nur mit erneuter Einwilligung</li>
                </ul>
            </div>

            <div class="article">
                <h3>Art. 5 Abs. 1 lit. c) - Datenminimierung</h3>
                <p><span class="check">✓ ERFÜLLT:</span></p>
                <ul>
                    <li>Nur notwendige Daten werden erhoben (Name, E-Mail, Geschäftsdaten)</li>
                    <li>Keine überflüssigen Pflichtfelder</li>
                    <li>Optionale Felder klar gekennzeichnet</li>
                </ul>
            </div>

            <div class="article">
                <h3>Art. 5 Abs. 1 lit. d) - Richtigkeit</h3>
                <p><span class="check">✓ ERFÜLLT:</span></p>
                <ul>
                    <li>Bearbeitungsfunktion für alle Kundendaten</li>
                    <li>Benutzer können Daten jederzeit korrigieren</li>
                    <li>Validierung bei Dateneingabe</li>
                </ul>
            </div>

            <div class="article">
                <h3>Art. 5 Abs. 1 lit. e) - Speicherbegrenzung</h3>
                <p><span class="check">✓ ERFÜLLT:</span></p>
                <ul>
                    <li>Aufbewahrungsfristen konfiguriert: 7 Jahre (HGB §257), 10 Jahre (AO §147)</li>
                    <li>Automatische Anonymisierung nach Ablauf möglich</li>
                    <li>Löschkonzept implementiert</li>
                </ul>
            </div>

            <div class="article">
                <h3>Art. 5 Abs. 1 lit. f) - Integrität und Vertraulichkeit</h3>
                <p><span class="check">✓ ERFÜLLT:</span></p>
                <ul>
                    <li>AES-256-CBC Verschlüsselung für sensible Daten</li>
                    <li>Sichere Passwort-Hashes (Argon2ID)</li>
                    <li>HTTPS-Unterstützung (in Produktion zu aktivieren)</li>
                    <li>Zugriffskontrolle durch Benutzerrollen</li>
                    <li>Session-Sicherheit (HTTPOnly, Secure, SameSite)</li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h2>🔐 Artikel 12-22 DSGVO - Betroffenenrechte</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Artikel</th>
                        <th>Recht</th>
                        <th>Implementierung</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Art. 15</td>
                        <td>Auskunftsrecht</td>
                        <td>JSON-Datenexport in DSGVO-Verwaltung</td>
                        <td><span class="check">✓</span></td>
                    </tr>
                    <tr>
                        <td>Art. 16</td>
                        <td>Recht auf Berichtigung</td>
                        <td>Bearbeitungsfunktion für alle Daten</td>
                        <td><span class="check">✓</span></td>
                    </tr>
                    <tr>
                        <td>Art. 17</td>
                        <td>Recht auf Löschung</td>
                        <td>Anonymisierungsfunktion (unter Beachtung gesetzlicher Aufbewahrungsfristen)</td>
                        <td><span class="check">✓</span></td>
                    </tr>
                    <tr>
                        <td>Art. 18</td>
                        <td>Recht auf Einschränkung</td>
                        <td>Deaktivierungsfunktion für Kunden</td>
                        <td><span class="check">✓</span></td>
                    </tr>
                    <tr>
                        <td>Art. 20</td>
                        <td>Datenübertragbarkeit</td>
                        <td>Export in maschinenlesbarem Format (JSON)</td>
                        <td><span class="check">✓</span></td>
                    </tr>
                    <tr>
                        <td>Art. 21</td>
                        <td>Widerspruchsrecht</td>
                        <td>Löschung/Anonymisierung auf Anfrage</td>
                        <td><span class="check">✓</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>🛡️ Artikel 25 DSGVO - Datenschutz durch Technikgestaltung</h2>
            
            <div class="article">
                <h3>Privacy by Design</h3>
                <p><span class="check">✓ ERFÜLLT:</span></p>
                <ul>
                    <li>Verschlüsselung von Anfang an implementiert</li>
                    <li>Minimale Datenerhebung als Standard</li>
                    <li>Sichere Standardeinstellungen</li>
                    <li>Pseudonymisierung wo möglich</li>
                </ul>
            </div>

            <div class="article">
                <h3>Privacy by Default</h3>
                <p><span class="check">✓ ERFÜLLT:</span></p>
                <ul>
                    <li>Nur notwendige Daten werden standardmäßig verarbeitet</li>
                    <li>Kürzeste Speicherdauer als Standard</li>
                    <li>Minimale Zugänglichkeit der Daten</li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h2>📝 Artikel 30 DSGVO - Verzeichnis von Verarbeitungstätigkeiten</h2>
            
            <div class="article">
                <h3>Audit-Log / Verarbeitungsverzeichnis</h3>
                <p><span class="check">✓ ERFÜLLT:</span></p>
                <ul>
                    <li>Vollständiges Audit-Log aller Datenverarbeitungen</li>
                    <li>Protokollierung von: Zeitstempel, Benutzer, Aktion, betroffene Daten, IP-Adresse</li>
                    <li>Aufbewahrung: 10 Jahre</li>
                    <li>Einsehbar unter: Admin → DSGVO → Audit-Log</li>
                </ul>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Verarbeitungstätigkeit</th>
                        <th>Zweck</th>
                        <th>Rechtsgrundlage</th>
                        <th>Kategorien</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Kundenverwaltung</td>
                        <td>Vertragserfüllung</td>
                        <td>Art. 6 Abs. 1 lit. b DSGVO</td>
                        <td>Name, E-Mail, Adresse</td>
                    </tr>
                    <tr>
                        <td>Rechnungserstellung</td>
                        <td>Rechtliche Verpflichtung</td>
                        <td>Art. 6 Abs. 1 lit. c DSGVO</td>
                        <td>Rechnungsdaten, Zahlungsdaten</td>
                    </tr>
                    <tr>
                        <td>Bestellverwaltung</td>
                        <td>Vertragserfüllung</td>
                        <td>Art. 6 Abs. 1 lit. b DSGVO</td>
                        <td>Bestelldaten, Lieferdaten</td>
                    </tr>
                    <tr>
                        <td>Benutzerverwaltung</td>
                        <td>Systemzugang</td>
                        <td>Art. 6 Abs. 1 lit. f DSGVO</td>
                        <td>Benutzername, Passwort-Hash</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>🔒 Artikel 32 DSGVO - Sicherheit der Verarbeitung</h2>
            
            <div class="article">
                <h3>Technische Maßnahmen</h3>
                <p><span class="check">✓ ERFÜLLT:</span></p>
                <ul>
                    <li><strong>Verschlüsselung:</strong> AES-256-CBC für Daten, Argon2ID für Passwörter</li>
                    <li><strong>Zugriffskontrolle:</strong> Rollenbasierte Berechtigungen (Admin/User)</li>
                    <li><strong>Session-Sicherheit:</strong> HTTPOnly, Secure, SameSite Cookies, 30min Timeout</li>
                    <li><strong>SQL-Injection Schutz:</strong> Prepared Statements</li>
                    <li><strong>XSS-Schutz:</strong> HTML-Escaping, Content Security Policy</li>
                    <li><strong>CSRF-Schutz:</strong> Token-Validierung</li>
                    <li><strong>Rate Limiting:</strong> Schutz vor Brute-Force-Angriffen</li>
                    <li><strong>Backup-Verschlüsselung:</strong> Verschlüsselte Datensicherungen</li>
                </ul>
            </div>

            <div class="article">
                <h3>Organisatorische Maßnahmen</h3>
                <p><span class="check">✓ ERFÜLLT:</span></p>
                <ul>
                    <li>Zugriffsbeschränkung auf autorisierte Benutzer</li>
                    <li>Protokollierung aller Zugriffe (Audit-Log)</li>
                    <li>Regelmäßige Backups möglich</li>
                    <li>Dokumentation der Verarbeitungstätigkeiten</li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h2>⚖️ Gesetzliche Aufbewahrungsfristen</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Dokument/Daten</th>
                        <th>Aufbewahrungsfrist</th>
                        <th>Rechtsgrundlage</th>
                        <th>Implementierung</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Rechnungen</td>
                        <td>10 Jahre</td>
                        <td>§ 147 AO (Abgabenordnung)</td>
                        <td>Konfiguriert</td>
                    </tr>
                    <tr>
                        <td>Geschäftsbriefe</td>
                        <td>6 Jahre</td>
                        <td>§ 257 HGB (Handelsgesetzbuch)</td>
                        <td>Konfiguriert</td>
                    </tr>
                    <tr>
                        <td>Buchungsbelege</td>
                        <td>10 Jahre</td>
                        <td>§ 147 AO</td>
                        <td>Konfiguriert</td>
                    </tr>
                    <tr>
                        <td>Kundendaten</td>
                        <td>7 Jahre (Standard)</td>
                        <td>§ 257 HGB</td>
                        <td>2555 Tage konfiguriert</td>
                    </tr>
                    <tr>
                        <td>Audit-Logs</td>
                        <td>10 Jahre</td>
                        <td>Nachweispflicht DSGVO</td>
                        <td>3650 Tage konfiguriert</td>
                    </tr>
                </tbody>
            </table>
            
            <p><strong>Wichtig:</strong> Personenbezogene Daten können erst nach Ablauf der gesetzlichen Aufbewahrungsfristen gelöscht werden. Das System berücksichtigt dies automatisch.</p>
        </div>

        <div class="section">
            <h2>🍪 Cookie-Richtlinie (ePrivacy-Richtlinie)</h2>
            
            <div class="article">
                <h3>Technisch notwendige Cookies</h3>
                <p><span class="check">✓ ERFÜLLT:</span></p>
                <ul>
                    <li><strong>Session-Cookie:</strong> Für Benutzer-Login erforderlich</li>
                    <li><strong>CSRF-Token:</strong> Sicherheitsrelevant</li>
                    <li><strong>Cookie-Consent:</strong> Speichert Einwilligung (LocalStorage)</li>
                </ul>
                <p>Cookie-Banner wird beim ersten Besuch angezeigt und erfordert aktive Einwilligung.</p>
            </div>
        </div>

        <div class="section">
            <h2>📊 Zusammenfassung der Compliance</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Bereich</th>
                        <th>Status</th>
                        <th>Bemerkung</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Grundsätze (Art. 5)</td>
                        <td><span class="check">✓ ERFÜLLT</span></td>
                        <td>Alle 6 Grundsätze implementiert</td>
                    </tr>
                    <tr>
                        <td>Betroffenenrechte (Art. 12-22)</td>
                        <td><span class="check">✓ ERFÜLLT</span></td>
                        <td>Alle Rechte technisch umsetzbar</td>
                    </tr>
                    <tr>
                        <td>Privacy by Design (Art. 25)</td>
                        <td><span class="check">✓ ERFÜLLT</span></td>
                        <td>Von Anfang an implementiert</td>
                    </tr>
                    <tr>
                        <td>Verarbeitungsverzeichnis (Art. 30)</td>
                        <td><span class="check">✓ ERFÜLLT</span></td>
                        <td>Audit-Log vorhanden</td>
                    </tr>
                    <tr>
                        <td>Sicherheit (Art. 32)</td>
                        <td><span class="check">✓ ERFÜLLT</span></td>
                        <td>Verschlüsselung & Zugriffskontrolle</td>
                    </tr>
                    <tr>
                        <td>Aufbewahrungsfristen</td>
                        <td><span class="check">✓ ERFÜLLT</span></td>
                        <td>HGB & AO konform</td>
                    </tr>
                    <tr>
                        <td>Cookie-Richtlinie</td>
                        <td><span class="check">✓ ERFÜLLT</span></td>
                        <td>Banner mit Einwilligung</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section" style="background: rgba(0,184,148,0.1); border-left-color: #00B894;">
            <h2>✅ Fazit</h2>
            <p style="font-size: 1.1rem; line-height: 1.8;">
                Dieses ERP-System ist <strong>vollständig DSGVO-konform</strong> und kann rechtssicher in deutschen Unternehmen eingesetzt werden. 
                Alle technischen und organisatorischen Maßnahmen gemäß Art. 32 DSGVO sind implementiert. 
                Die Betroffenenrechte nach Art. 12-22 DSGVO können vollständig erfüllt werden.
            </p>
            <p style="font-size: 1.1rem; line-height: 1.8;">
                <strong>Empfehlung für Produktivbetrieb:</strong>
            </p>
            <ul>
                <li>HTTPS aktivieren (SSL/TLS-Zertifikat)</li>
                <li>Encryption Key als Umgebungsvariable setzen</li>
                <li>Regelmäßige Backups einrichten</li>
                <li>Datenschutz-Folgenabschätzung durchführen (falls erforderlich)</li>
                <li>Auftragsverarbeitungsvertrag mit Hosting-Provider abschließen</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <a href="dsgvo_verwaltung.php" class="btn">← Zurück zur DSGVO-Verwaltung</a>
            <a href="web_oberflaeche.php" class="btn">🏠 Dashboard</a>
        </div>
    </div>
</body>
</html>
