<?php
// Nextcloud WebDAV Konfiguration (Phase 1: Upload von TCPDF-PDFs)
// Hinweis: App-Passwort statt normales Passwort verwenden.

return [
    // Basis-URL für WebDAV Files (Endet idealerweise mit /USERNAME/)
    // Beispiel: https://cloud.example.com/remote.php/dav/files/admin/
    'NEXTCLOUD_WEBDAV_BASE_URL' => 'https://cloud.merz-edv.eu/remote.php/dav/files/USERNAME/',

    // App-Passwort
    'NEXTCLOUD_USERNAME' => 'USERNAME',
    'NEXTCLOUD_APP_PASSWORD' => 'APP_PASSWORD',

    // Optionaler Root-Ordner innerhalb Nextcloud
    // Pfad wird relativ zu NEXTCLOUD_WEBDAV_BASE_URL aufgebaut, z.B. ERP/<kundennummer>/...
    'NEXTCLOUD_ROOT_FOLDER' => 'ERP',

    // Nur für Debug-Zwecke (keinen Secret loggen)
    'NEXTCLOUD_UPLOAD_TIMEOUT_SEC' => 30,
];

