<?php

function nextcloud_config(): array {
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    $cfg = require __DIR__ . '/../config/nextcloud_config.php';
    if (!is_array($cfg)) {
        $cfg = [];
    }
    return $cfg;
}

function nextcloud_webdav_is_configured(): bool {
    $cfg = nextcloud_config();
    return !empty($cfg['NEXTCLOUD_WEBDAV_BASE_URL'])
        && !empty($cfg['NEXTCLOUD_USERNAME'])
        && !empty($cfg['NEXTCLOUD_APP_PASSWORD']);
}


function nextcloud_webdav_normalize_remote_dir(string $dir): string {
    // WebDAV braucht: /ERP/123/Rechnungen/ (ohne doppelte //)
    $dir = trim($dir);
    $dir = str_replace('\\', '/', $dir);
    $dir = preg_replace('#/+#', '/', $dir);
    if ($dir === '') {
        return '';
    }
    // führender / erwünscht
    if ($dir[0] !== '/') {
        $dir = '/' . $dir;
    }
    // trailing / behalten
    return rtrim($dir, '/');
}

function nextcloud_webdav_build_url(string $remotePath): string {
    $cfg = nextcloud_config();
    $base = rtrim($cfg['NEXTCLOUD_WEBDAV_BASE_URL'], '/');

    $remotePath = str_replace('\\', '/', $remotePath);
    $remotePath = ltrim($remotePath, '/');

    return $base . '/' . $remotePath;
}

function nextcloud_webdav_auth_headers(): array {
    $cfg = nextcloud_config();
    $user = (string)$cfg['NEXTCLOUD_USERNAME'];
    $pass = (string)$cfg['NEXTCLOUD_APP_PASSWORD'];
    $token = base64_encode($user . ':' . $pass);
    return [
        'Authorization: Basic ' . $token,
    ];
}

function nextcloud_webdav_mkcol(string $remoteDir, array &$lastInfo = null): bool {
    // MKCOL: remoteDir muss ohne trailing filename sein
    $remoteDir = trim($remoteDir);
    if ($remoteDir === '') {
        return true;
    }

    $cfg = nextcloud_config();

    // RemotePath Stück für Stück anlegen
    $remoteDir = str_replace('\\', '/', $remoteDir);
    $remoteDir = trim($remoteDir, '/');
    if ($remoteDir === '') return true;

    $parts = explode('/', $remoteDir);
    $pathSoFar = '';

    foreach ($parts as $part) {
        if ($part === '') continue;
        $pathSoFar = $pathSoFar === '' ? $part : ($pathSoFar . '/' . $part);
        $url = nextcloud_webdav_build_url($pathSoFar);

        $headers = nextcloud_webdav_auth_headers();
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'MKCOL',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int)($cfg['NEXTCLOUD_UPLOAD_TIMEOUT_SEC'] ?? 30),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => array_merge($headers, ['Content-Length: 0']),
        ]);
        $resp = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        // 201 Created, 405 Method Not Allowed (existiert evtl.)
        if ($http === 201 || $http === 405) {
            $lastInfo = ['http' => $http, 'url' => $url, 'resp' => $resp, 'err' => $err];
            continue;
        }

        // 409 Conflict -> existiert/anderes Problem
        if ($http === 409) {
            $lastInfo = ['http' => $http, 'url' => $url, 'resp' => $resp, 'err' => $err];
            continue;
        }

        $lastInfo = ['http' => $http, 'url' => $url, 'resp' => $resp, 'err' => $err];
        return false;
    }

    return true;
}

function nextcloud_webdav_put(string $remoteFilePath, string $data, string $contentType = 'application/pdf', array &$lastInfo = null): bool {
    $cfg = nextcloud_config();

    $remoteFilePath = str_replace('\\', '/', $remoteFilePath);
    $remoteFilePath = ltrim($remoteFilePath, '/');

    $url = nextcloud_webdav_build_url($remoteFilePath);

    $headers = nextcloud_webdav_auth_headers();
    $headers[] = 'Content-Type: ' . $contentType;
    $headers[] = 'Content-Length: ' . strlen($data);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => (int)($cfg['NEXTCLOUD_UPLOAD_TIMEOUT_SEC'] ?? 30),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $data,
    ]);

    $resp = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $lastInfo = ['http' => $http, 'url' => $url, 'resp' => $resp, 'err' => $err];

    // WebDAV PUT: 201 Created oder 204 No Content
    return ($http === 201 || $http === 204);
}

function nextcloud_webdav_upload_pdf_for_order(int $kundennummerOrId, string $pdfBytes, string $filename, string $docKind = 'Dokumente', array &$lastInfo = null): bool {
    if (!nextcloud_webdav_is_configured()) {
        $lastInfo = ['error' => 'Nextcloud config missing'];
        return false;
    }

    $cfg = nextcloud_config();

    $kundenNummerSafe = preg_replace('/[^0-9A-Za-z_\-\. ]/', '_', (string)$kundennummerOrId);
    $docKindSafe = preg_replace('/[^0-9A-Za-z_\-\. ]/', '_', $docKind);
    $filenameSafe = preg_replace('/[^0-9A-Za-z_\-\. ]/', '_', $filename);

    // remote Dir: ERP/<kunde>/<docKind>/
    $remoteDir = $cfg['NEXTCLOUD_ROOT_FOLDER'] . '/' . $kundenNummerSafe . '/' . $docKindSafe;
    if (!nextcloud_webdav_mkcol($remoteDir, $lastInfo)) {
        return false;
    }

    $remoteFile = $remoteDir . '/' . $filenameSafe;
    return nextcloud_webdav_put($remoteFile, $pdfBytes, 'application/pdf', $lastInfo);
}

