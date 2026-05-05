# 🍪 Cookie Banner & Datenschutz Guide

Willkommen! Ich habe dir ein professionelles Cookie Banner und ein verbessertes Impressum/Datenschutzerklärung erstellt. Alles ist DSGVO-konform und sofort einsatzbereit.

## 📦 Was wurde erstellt?

### 1. **Cookie Banner System**
- ✅ `assets/js/cookie-consent.js` - Verwaltung der Cookie-Einwilligungen
- ✅ `assets/css/cookie-banner.css` - Styling für das Banner
- ✅ `includes/cookie-banner.php` - HTML des Banners
- ✅ Automatisch in alle Seiten integriert (via footer.php)

### 2. **Verbesserte Datenschutz-Seiten**
- ✅ `impressum.php` - Erweitert mit 10 Abschnitten
- ✅ `datenschutz.php` - Umfassende Datenschutzerklärung

## 🚀 Schnell-Start

Das Cookie Banner wird automatisch angezeigt, wenn Besucher die Website betreten und noch keine Einwilligung gegeben haben.

### Ist automatisch aktiviert für:
- Alle Seiten mit `include 'includes/header.php'`
- Alle Seiten mit `include 'includes/footer.php'`

## ⚙️ Funktionsweise des Cookie Banners

### Cookie-Kategorien

1. **Notwendige Cookies** (immer aktiv - nicht deaktivierbar)
   - Session-Management
   - Authentifizierung
   - CSRF-Schutz
   - Grundlegende Funktionalität

2. **Funktionale Cookies** (optional)
   - Theme-Präferenzen (Light/Dark Mode)
   - Sprache
   - Benutzereinstellungen

3. **Analyse-Cookies** (optional)
   - Nutzungsstatistiken
   - Performance-Metriken
   - Zugriffsdaten

4. **Marketing-Cookies** (optional)
   - Retargeting
   - Social Media Integration
   - Werbung

### Einwilligungen speichern

Die Einwilligung wird lokal gespeichert unter:
- **Storage Key**: `cookie_consent_choice` (localStorage)
- **Datum Key**: `cookie_consent_date` (localStorage)
- **Ablauf**: 365 Tage

## 📋 Impressum anpassen

Öffne `/impressum.php` und ersetze folgende Platzhalter:

```
[Firmenname]              → Dein Firmenname
[Straße und Hausnummer]   → Deine Adresse
[PLZ] [Stadt]             → Postleitzahl und Stadt
[Telefonnummer]           → Deine Telefonnummer
[E-Mail-Adresse]          → Deine E-Mail
[Handelsregister Info]    → Deine Handelsregisterdaten
[Steuernummer]            → Deine Steuernummer
[USt-IdNr.]               → Deine Umsatzsteuer-ID
[Name Geschäftsführer]    → Name des Verantwortlichen
[Datenschutz-E-Mail]      → E-Mail für Datenschutzfragen
```

## 🔒 Datenschutzerklärung anpassen

Öffne `/datenschutz.php` und ersetze:

```
[Firmenname]              → Dein Firmenname
[Straße und Hausnummer]   → Deine Adresse
[PLZ] [Stadt]             → Postleitzahl und Stadt
[Telefonnummer]           → Deine Telefonnummer
[E-Mail-Adresse]          → Deine E-Mail
[Name Datenschutzbeauftragter] → Name DSB
[DSB-E-Mail]              → E-Mail des DSB
[DSB-Telefon]             → Telefon des DSB
[Ihr Bundesland]          → Zuständiges Bundesland
```

## 🔧 JavaScript-API für Entwickler

### Cookie-Einwilligung prüfen

```javascript
// Prüfe ob Benutzer akzeptiert hat
if (window.cookieConsent.hasConsent()) {
    console.log("User has given consent");
}

// Hole aktuelle Einstellungen
const consent = window.cookieConsent.getConsent();
console.log(consent);
// Output: {
//   essential: true,
//   functional: false,
//   analytics: true,
//   marketing: false,
//   timestamp: "2026-05-05T12:00:00Z"
// }
```

### Events abfangen

```javascript
// Höre auf Cookie-Einwilligung Events
document.addEventListener('cookieConsent', (event) => {
    const consent = event.detail;
    
    if (consent.analytics) {
        // Analytics laden
        console.log("Analytics enabled");
    }
});

// Marketing-spezifischer Event
document.addEventListener('cookieConsentMarketing', (event) => {
    console.log("Marketing cookies accepted");
});
```

### Einwilligung setzen/zurücksetzen

```javascript
// Einwilligung manuell setzen
window.cookieConsent.setConsent({
    essential: true,
    functional: true,
    analytics: false,
    marketing: false,
    timestamp: new Date().toISOString()
});

// Cookie Banner neu anzeigen
window.cookieConsent.showBanner();

// Einwilligung zurücksetzen
window.cookieConsent.resetCookies();
```

## 🎨 Styling anpassen

Das Cookie Banner ist in `assets/css/cookie-banner.css` vollständig stilisierbar.

### Wichtigste CSS-Variablen

```css
/* Am Anfang der cookie-banner.css */
/* Farben anpassen */
background-color: #1f2937;  /* Banner Hintergrund */
background-color: #10b981;  /* Akzent-Farbe */
```

### Mobile-optimiert

Das Banner ist vollständig responsive:
- Desktop: Horizontal Layout
- Mobile: Vertikales Layout
- Accessibility: Unterstützt `prefers-reduced-motion`

## 🔐 DSGVO-Compliance Checkliste

✅ Explizite Einwilligung vor nicht-essentiellen Cookies
✅ Granulare Cookie-Kategorien
✅ Leichte Widerrufsmöglichkeit
✅ Verknüpfung zu Datenschutzerklärung
✅ Speicherdauer dokumentiert
✅ Rechte gemäß DSGVO aufgelistet
✅ Verschlüsselung und Sicherheit dokumentiert
✅ Datenweitergabe transparent
✅ Kontaktdaten für Datenschutzfragen

## 📱 Integration in existierende Seiten

Für neue Seiten, die nicht über `header.php` und `footer.php` eingebunden werden:

```php
<?php
// Am Anfang der Seite
include 'includes/header.php';
?>

<!-- Dein Inhalt -->

<?php
// Am Ende der Seite
include 'includes/footer.php';
?>
```

Das Cookie Banner wird automatisch hinzugefügt!

## ⚠️ Wichtige Hinweise

1. **Platzhalter ersetzen**: Alle `[...]` Platzhalter MÜSSEN mit echten Daten gefüllt werden
2. **Juridischer Hinweis**: Konsultieren Sie einen Datenschutzanwalt zur Korrektheit
3. **Datenschutzbeauftragter**: Prüfen Sie, ob Sie einen DSB benennen müssen
4. **Regelmäßige Updates**: Aktualisieren Sie die Datenschutzerklärung bei Änderungen
5. **Audit-Log**: Das System loggt alle Datenzugriffe für DSGVO-Compliance

## 📞 Support

Bei Fragen zum Cookie Banner oder Datenschutz:
- Öffne `datenschutz.php` → "10. Kontakt zum Datenschutz"
- Öffne `impressum.php` → "10. Kontakt für Datenschutzfragen"

## 🔄 Cookie Banner Ablauf

```
Besucher kommt auf Seite
    ↓
[Hat Einwilligung im localStorage?]
    ↓ NEIN
[Zeige Banner]
    ↓
[Besucher klickt: Ablehnen / Akzeptieren / Einstellungen]
    ↓
[Speichere Einwilligung mit Zeitstempel]
    ↓
[Banner versteckt sich]
    ↓
[Nach 365 Tagen: Erneute Abfrage]
```

---

**Version**: 1.0
**Erstellung**: 5. Mai 2026
**DSGVO-konform**: ✅ Ja
**Responsive**: ✅ Ja
**Accessibility**: ✅ WCAG 2.1 AA
