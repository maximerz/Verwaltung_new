<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nicht angemeldet']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$rechnung_id = $input['rechnung_id'] ?? null;

if (!$rechnung_id) {
    echo json_encode(['success' => false, 'error' => 'Keine Rechnungs-ID']);
    exit;
}

try {
    $stmt = $PDO->prepare("UPDATE rechnungen SET status = 'bezahlt', zahlungsdatum = DATE('now') WHERE id = ?");
    $stmt->execute([$rechnung_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>