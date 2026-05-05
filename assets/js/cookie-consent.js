/**
 * Cookie Consent Management
 * Konform mit DSGVO und ePrivacy Verordnung
 */

class CookieConsent {
    constructor() {
        this.storageKey = 'cookie_consent_choice';
        this.storageKeyDate = 'cookie_consent_date';
        this.consentExpireDays = 365;
        this.banner = null;
        this.settingsPanel = null;
        this.init();
    }

    init() {
        // Nur initialisieren, wenn keine Einwilligung vorhanden
        if (!this.hasConsent()) {
            this.showBanner();
        }
    }

    hasConsent() {
        const consent = localStorage.getItem(this.storageKey);
        if (!consent) return false;
        
        const date = localStorage.getItem(this.storageKeyDate);
        if (date) {
            const consentDate = new Date(date);
            const expiryDate = new Date(consentDate.getTime() + this.consentExpireDays * 24 * 60 * 60 * 1000);
            if (new Date() > expiryDate) {
                this.clearConsent();
                return false;
            }
        }
        return true;
    }

    getConsent() {
        const consent = localStorage.getItem(this.storageKey);
        return consent ? JSON.parse(consent) : null;
    }

    setConsent(consent) {
        localStorage.setItem(this.storageKey, JSON.stringify(consent));
        localStorage.setItem(this.storageKeyDate, new Date().toISOString());
        this.hideBanner();
        this.triggerConsent(consent);
    }

    clearConsent() {
        localStorage.removeItem(this.storageKey);
        localStorage.removeItem(this.storageKeyDate);
    }

    showBanner() {
        this.banner = document.getElementById('cookie-banner');
        this.settingsPanel = document.getElementById('cookie-settings-panel');
        
        if (this.banner) {
            this.banner.style.display = 'flex';
            this.attachEventListeners();
        }
    }

    hideBanner() {
        if (this.banner) {
            this.banner.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                this.banner.style.display = 'none';
            }, 300);
        }
    }

    showSettingsPanel() {
        if (this.settingsPanel) {
            this.settingsPanel.style.display = 'block';
            this.settingsPanel.scrollIntoView({ behavior: 'smooth' });
        }
    }

    hideSettingsPanel() {
        if (this.settingsPanel) {
            this.settingsPanel.style.display = 'none';
        }
    }

    attachEventListeners() {
        // Accept All
        const acceptAllBtn = document.getElementById('cookie-accept-all');
        if (acceptAllBtn) {
            acceptAllBtn.addEventListener('click', () => {
                this.setConsent({
                    essential: true,
                    functional: true,
                    analytics: true,
                    marketing: true,
                    timestamp: new Date().toISOString()
                });
            });
        }

        // Reject All
        const rejectAllBtn = document.getElementById('cookie-reject-all');
        if (rejectAllBtn) {
            rejectAllBtn.addEventListener('click', () => {
                this.setConsent({
                    essential: true,
                    functional: false,
                    analytics: false,
                    marketing: false,
                    timestamp: new Date().toISOString()
                });
            });
        }

        // Settings Toggle
        const settingsBtn = document.getElementById('cookie-settings-btn');
        if (settingsBtn) {
            settingsBtn.addEventListener('click', () => {
                this.showSettingsPanel();
            });
        }

        // Close Settings
        const closeSettingsBtn = document.getElementById('cookie-settings-close');
        if (closeSettingsBtn) {
            closeSettingsBtn.addEventListener('click', () => {
                this.hideSettingsPanel();
            });
        }

        // Save Custom Settings
        const saveCookiesBtn = document.getElementById('cookie-save-settings');
        if (saveCookiesBtn) {
            saveCookiesBtn.addEventListener('click', () => {
                this.saveCustomSettings();
            });
        }
    }

    saveCustomSettings() {
        const consent = {
            essential: true, // Immer true, da erforderlich
            functional: document.getElementById('cookie-functional')?.checked ?? false,
            analytics: document.getElementById('cookie-analytics')?.checked ?? false,
            marketing: document.getElementById('cookie-marketing')?.checked ?? false,
            timestamp: new Date().toISOString()
        };
        this.setConsent(consent);
        this.hideSettingsPanel();
    }

    triggerConsent(consent) {
        // Events für externe Skripte
        document.dispatchEvent(new CustomEvent('cookieConsent', { detail: consent }));

        // Google Analytics aktivieren, wenn akzeptiert
        if (consent.analytics && window.gtag) {
            gtag('consent', 'update', {
                'analytics_storage': 'granted'
            });
        }

        // Marketing Cookies aktivieren
        if (consent.marketing) {
            document.dispatchEvent(new CustomEvent('cookieConsentMarketing', { detail: consent }));
        }
    }

    resetCookies() {
        this.clearConsent();
        location.reload();
    }
}

// Initialisiere Cookie Consent beim Laden
document.addEventListener('DOMContentLoaded', () => {
    window.cookieConsent = new CookieConsent();
});
