<?php
session_start();
require_once 'db_connection.php';
require_once 'includes/audit_log.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['order_id'] ?? '';
$redirect = $_GET['redirect'] ?? 'web_oberflaeche.php';

if ($order_id) {
    try {
        $stmt = $PDO->prepare("SELECT * FROM bestellungen WHERE idbestellung = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        $stmt = $PDO->prepare("DELETE FROM angebot_positionen WHERE angebot_id = ?");
        $stmt->execute([$order_id]);
        
        $stmt = $PDO->prepare("DELETE FROM bestellungen WHERE idbestellung = ?");
        $stmt->execute([$order_id]);
        
        audit_log($PDO, 'DELETE', 'bestellungen', $order_id, $order, null);
        
    } catch (Exception $e) {
        // Fehler ignorieren
    }
}

header("Location: $redirect");
exit;
?>