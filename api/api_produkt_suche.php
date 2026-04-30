<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$suche = $_GET['q'] ?? '';

if (strlen($suche) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $PDO->prepare("SELECT id, name, beschreibung, preis, kategorie FROM produkte WHERE name LIKE ? OR beschreibung LIKE ? OR kategorie LIKE ? ORDER BY name LIMIT 10");
    $stmt->execute(["%$suche%", "%$suche%", "%$suche%"]);
    $produkte = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($produkte);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
