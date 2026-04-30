<?php
header('Content-Type: application/json');
require_once '../db_connection.php';

function validate_api_key($key) {
    global $PDO;
    try {
        $PDO->exec("CREATE TABLE IF NOT EXISTS api_keys (id INTEGER PRIMARY KEY AUTOINCREMENT, api_key TEXT UNIQUE, name TEXT, active INTEGER DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $stmt = $PDO->prepare("SELECT * FROM api_keys WHERE api_key = ? AND active = 1");
        $stmt->execute([$key]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

$api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;

if (!$api_key || !validate_api_key($api_key)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

try {
    if (preg_match('#/api/kunden$#', $path) && $method === 'GET') {
        $stmt = $PDO->query("SELECT k.*, f.firmenname FROM kundensystem k LEFT JOIN firma f ON k.firma_id = f.id");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    elseif (preg_match('#/api/bestellungen$#', $path) && $method === 'GET') {
        $stmt = $PDO->query("SELECT * FROM bestellungen");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    elseif (preg_match('#/api/lager$#', $path) && $method === 'GET') {
        $stmt = $PDO->query("SELECT *, (bestand - reserviert) as verfuegbar FROM lagerartikel");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
    elseif (preg_match('#/api/stats$#', $path) && $method === 'GET') {
        $stats = [];
        $stmt = $PDO->query("SELECT COUNT(*) as anzahl FROM kundensystem");
        $stats['kunden'] = $stmt->fetch()['anzahl'];
        $stmt = $PDO->query("SELECT COUNT(*) as anzahl FROM bestellungen WHERE status = 'angebot'");
        $stats['angebote'] = $stmt->fetch()['anzahl'];
        echo json_encode(['success' => true, 'data' => $stats]);
    }
    else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint nicht gefunden']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
