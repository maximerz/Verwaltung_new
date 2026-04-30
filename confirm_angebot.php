<?php
session_start();
require_once 'db_connection.php';
require_once 'includes/lager_reservierung.php';

$order_id = $_GET['order_id'] ?? null;

if ($order_id) {
    try {
        // Auftragsnummer generieren
        $auftragsnummer = 'A' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        // Bestellnummer generieren
        $bestellungsnummer = 'B' . date('Ymd') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        
        // Status auf bestätigt ändern und Nummern setzen
        $stmt = $PDO->prepare("
            UPDATE bestellungen 
            SET status = 'bestaetigt', auftragsnummer = ?, bestellungsnummer = ? 
            WHERE idbestellung = ?
        ");
        $stmt->execute([$auftragsnummer, $bestellungsnummer, $order_id]);
        
        // Lagerreservierung in tatsächlichen Bestand umwandeln
        $result = bestaetigenReservierung($order_id, $PDO);
        
        if ($result['success']) {
            header("Location: web_oberflaeche.php?confirmed=1&lager=ok");
        } else {
            header("Location: web_oberflaeche.php?confirmed=1&lager=warning");
        }
        exit;
    } catch (Exception $e) {
        header("Location: web_oberflaeche.php?error=confirm_failed");
        exit;
    }
} else {
    header("Location: web_oberflaeche.php");
    exit;
}
?>