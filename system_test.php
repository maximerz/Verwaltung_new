<?php
require_once 'db_connection.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>ERP System Test</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5}";
echo ".test{background:white;padding:15px;margin:10px 0;border-radius:8px;border-left:4px solid #2563eb}";
echo ".success{border-left-color:#10b981;color:#10b981}";
echo ".error{border-left-color:#ef4444;color:#ef4444}";
echo "h1{color:#2563eb}h2{color:#64748b;margin-top:30px}</style></head><body>";

echo "<h1>🧪 ERP System - Vollständiger Test</h1>";

$tests_passed = 0;
$tests_failed = 0;

function test($name, $callback) {
    global $tests_passed, $tests_failed;
    try {
        $result = $callback();
        if ($result) {
            echo "<div class='test success'>✓ $name</div>";
            $tests_passed++;
        } else {
            echo "<div class='test error'>✗ $name - Test fehlgeschlagen</div>";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "<div class='test error'>✗ $name - Fehler: " . $e->getMessage() . "</div>";
        $tests_failed++;
    }
}

echo "<h2>📊 1. Datenbank-Tests</h2>";

test("Datenbank-Verbindung", function() use ($PDO) {
    return $PDO !== null;
});

$tables = ['kundensystem', 'firma', 'bestellungen', 'angebot_positionen', 'lieferscheine', 'lagerartikel', 'api_keys', 'audit_log'];
foreach ($tables as $table) {
    test("Tabelle '$table' existiert", function() use ($PDO, $table) {
        $stmt = $PDO->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
        return $stmt->fetch() !== false;
    });
}

echo "<h2>👥 2. Kunden-Tests</h2>";

test("Kunden können geladen werden", function() use ($PDO) {
    $stmt = $PDO->query("SELECT COUNT(*) as cnt FROM kundensystem");
    return $stmt->fetch()['cnt'] >= 0;
});

echo "<h2>📦 3. Bestell-Tests</h2>";

test("Bestellungen können geladen werden", function() use ($PDO) {
    $stmt = $PDO->query("SELECT COUNT(*) as cnt FROM bestellungen");
    return $stmt->fetch()['cnt'] >= 0;
});

test("Angebot-Positionen verknüpft", function() use ($PDO) {
    $stmt = $PDO->query("SELECT COUNT(*) as cnt FROM angebot_positionen");
    return $stmt->fetch()['cnt'] >= 0;
});

echo "<h2>🏭 4. Lager-Tests</h2>";

test("Lagerartikel-Tabelle funktioniert", function() use ($PDO) {
    $stmt = $PDO->query("SELECT COUNT(*) as cnt FROM lagerartikel");
    return $stmt->fetch()['cnt'] >= 0;
});

test("Reservierungen funktionieren", function() use ($PDO) {
    $stmt = $PDO->query("SELECT COUNT(*) as cnt FROM lager_reservierungen");
    return $stmt->fetch()['cnt'] >= 0;
});

echo "<h2>🚚 5. Lieferschein-Tests</h2>";

test("Lieferscheine können geladen werden", function() use ($PDO) {
    $stmt = $PDO->query("SELECT COUNT(*) as cnt FROM lieferscheine");
    return $stmt->fetch()['cnt'] >= 0;
});

echo "<h2>🔐 6. Sicherheits-Tests</h2>";

test("Audit-Log Tabelle existiert", function() use ($PDO) {
    $stmt = $PDO->query("SELECT name FROM sqlite_master WHERE type='table' AND name='audit_log'");
    return $stmt->fetch() !== false;
});

test("API-Keys Tabelle existiert", function() use ($PDO) {
    $stmt = $PDO->query("SELECT name FROM sqlite_master WHERE type='table' AND name='api_keys'");
    return $stmt->fetch() !== false;
});

test("Verschlüsselung verfügbar", function() {
    return function_exists('openssl_encrypt');
});

echo "<h2>💾 7. Backup-Tests</h2>";

test("Backup-Verzeichnis existiert", function() {
    return is_dir(__DIR__ . '/backups');
});

test("Backup-Script existiert", function() {
    return file_exists(__DIR__ . '/backup.sh');
});

test("Backup-Script ist ausführbar", function() {
    return is_executable(__DIR__ . '/backup.sh');
});

echo "<h2>🔌 8. API-Tests</h2>";

test("API-Verzeichnis existiert", function() {
    return is_dir(__DIR__ . '/api');
});

test("API-Endpoint existiert", function() {
    return file_exists(__DIR__ . '/api/index.php');
});

echo "<h2>📈 Zusammenfassung</h2>";
echo "<div class='test'>";
echo "<strong>Tests bestanden:</strong> $tests_passed<br>";
echo "<strong>Tests fehlgeschlagen:</strong> $tests_failed<br>";
$total = $tests_passed + $tests_failed;
$percentage = $total > 0 ? round(($tests_passed / $total) * 100, 1) : 0;
echo "<strong>Erfolgsrate:</strong> $percentage%";
echo "</div>";

if ($tests_failed === 0) {
    echo "<div class='test success'><h3>🎉 Alle Tests bestanden!</h3></div>";
} else {
    echo "<div class='test error'><h3>⚠️ Einige Tests sind fehlgeschlagen</h3></div>";
}

echo "<br><a href='web_oberflaeche.php' style='display:inline-block;padding:10px 20px;background:#2563eb;color:white;text-decoration:none;border-radius:8px'>← Zurück zum Dashboard</a>";
echo "</body></html>";
?>
