<!-- Cookie Consent Banner - DSGVO konform -->
<div id="cookie-banner">
    <div class="cookie-banner-content">
        <h3>🍪 Wir schätzen Ihre Privatsphäre</h3>
        <p>
            Wir nutzen Cookies für die grundlegende Funktionalität und Analysepurposen. 
            Sie können Ihre Einstellungen anpassen oder alle akzeptieren. 
            Lesen Sie unsere <a href="datenschutz.php">Datenschutzerklärung</a>.
        </p>
    </div>
    <div class="cookie-banner-buttons">
        <button class="cookie-btn cookie-btn-reject" id="cookie-reject-all">Ablehnen</button>
        <button class="cookie-btn cookie-btn-settings" id="cookie-settings-btn">Einstellungen</button>
        <button class="cookie-btn cookie-btn-accept" id="cookie-accept-all">Alle akzeptieren</button>
    </div>
</div>

<!-- Cookie Settings Panel -->
<div id="cookie-settings-panel">
    <div class="cookie-settings-content">
        <button class="cookie-settings-close" id="cookie-settings-close">✕</button>
        <h2>Cookie-Einstellungen</h2>
        <p>Bestimmen Sie, welche Cookies Sie akzeptieren möchten. Einige Cookies sind erforderlich und können nicht deaktiviert werden.</p>

        <!-- Essential Cookies -->
        <div class="cookie-category">
            <div class="cookie-category-header">
                <div>
                    <div class="cookie-category-title">Notwendige Cookies</div>
                    <div class="cookie-category-description">Erforderlich für die grundlegende Funktionalität der Website</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="cookie-essential" checked disabled>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <p class="cookie-small-text">
                • Session-Verwaltung<br>
                • Authentifizierung<br>
                • CSRF-Schutz<br>
                • Spracheinstellungen
            </p>
        </div>

        <!-- Functional Cookies -->
        <div class="cookie-category">
            <div class="cookie-category-header">
                <div>
                    <div class="cookie-category-title">Funktionale Cookies</div>
                    <div class="cookie-category-description">Verbessern das Benutzererlebnis</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="cookie-functional">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <p class="cookie-small-text">
                • Theme-Präferenzen<br>
                • Benutzereinstellungen<br>
                • Formularerinnerungen
            </p>
        </div>

        <!-- Analytics Cookies -->
        <div class="cookie-category">
            <div class="cookie-category-header">
                <div>
                    <div class="cookie-category-title">Analyse-Cookies</div>
                    <div class="cookie-category-description">Helfen uns, die Website zu verbessern</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="cookie-analytics">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <p class="cookie-small-text">
                • Anonyme Nutzungsstatistiken<br>
                • Seitenzugriffsdaten<br>
                • Performance-Metriken
            </p>
        </div>

        <!-- Marketing Cookies -->
        <div class="cookie-category">
            <div class="cookie-category-header">
                <div>
                    <div class="cookie-category-title">Marketing-Cookies</div>
                    <div class="cookie-category-description">Für Werbung und Retargeting</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="cookie-marketing">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <p class="cookie-small-text">
                • Retargeting-Pixel<br>
                • Social Media Integration<br>
                • Werbepräferenzen
            </p>
        </div>

        <div class="cookie-settings-buttons">
            <button class="cookie-btn cookie-btn-reject" id="cookie-settings-reject-all">Alle ablehnen</button>
            <button class="cookie-btn cookie-btn-accept" id="cookie-save-settings">Einstellungen speichern</button>
        </div>

        <p class="cookie-small-text" style="margin-top: 1.5rem; margin-bottom: 0;">
            Weitere Informationen zu Cookies finden Sie in unserer <a href="datenschutz.php" style="color: #10b981;">Datenschutzerklärung</a>.
        </p>
    </div>
</div>

<!-- Script -->
<link rel="stylesheet" href="assets/css/cookie-banner.css">
<script src="assets/js/cookie-consent.js"></script>

<script>
    // Zusätzliche Einstellungs-Panel Events
    document.addEventListener('DOMContentLoaded', () => {
        const rejectAllInSettings = document.getElementById('cookie-settings-reject-all');
        if (rejectAllInSettings && window.cookieConsent) {
            rejectAllInSettings.addEventListener('click', () => {
                window.cookieConsent.setConsent({
                    essential: true,
                    functional: false,
                    analytics: false,
                    marketing: false,
                    timestamp: new Date().toISOString()
                });
                window.cookieConsent.hideSettingsPanel();
            });
        }
    });
</script>
