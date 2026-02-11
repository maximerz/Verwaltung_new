<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['found' => false]);
    exit;
}

$code = $_GET['code'] ?? '';

if (empty($code)) {
    echo json_encode(['found' => false]);
    exit;
}

try {
    // Suche nach Barcode oder Modellnummer
    $stmt = $PDO->prepare("SELECT * FROM lagerartikel WHERE barcode = ? OR modellnummer = ? LIMIT 1");
    $stmt->execute([$code, $code]);
    $artikel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($artikel) {
        echo json_encode([
            'found' => true,
            'artikelnummer' => $artikel['artikelnummer'],
            'artikelname' => $artikel['artikelname'],
            'modellnummer' => $artikel['modellnummer'],
            'barcode' => $artikel['barcode'],
            'beschreibung' => $artikel['beschreibung'],
            'kategorie' => $artikel['kategorie'],
            'verkaufspreis' => $artikel['verkaufspreis'],
            'einkaufspreis' => $artikel['einkaufspreis']
        ]);
    } else {
        echo json_encode(['found' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['found' => false, 'error' => $e->getMessage()]);
}
?>
