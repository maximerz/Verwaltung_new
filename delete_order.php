<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['order_id'] ?? '';
$redirect = $_GET['redirect'] ?? 'web_oberflaeche.php';

if ($order_id) {
    try {
        // Positionen löschen
        $stmt = $PDO->prepare("DELETE FROM angebot_positionen WHERE angebot_id = ?");
        $stmt->execute([$order_id]);
        
        // Bestellung löschen
        $stmt = $PDO->prepare("DELETE FROM bestellungen WHERE idbestellung = ?");
        $stmt->execute([$order_id]);
        
    } catch (Exception $e) {
        // Fehler ignorieren
    }
}

header("Location: $redirect");
exit;
?>