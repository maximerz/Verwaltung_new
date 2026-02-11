<?php
session_start();
if (!isset($_POST['skip_session']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connection.php';

$kundennummer = $_POST['kundennummer'] ?? '';
$bestellungsname = $_POST['bestellungsnamen'] ?? '';

if (empty($kundennummer) || empty($bestellungsname)) {
    header('Location: web_oberflaeche.php');
    exit;
}

try {
    // Automatische Bestellungsnummer generieren
    $stmt_max = $PDO->prepare("SELECT MAX(idbestellung) as max_id FROM bestellungen");
    $stmt_max->execute();
    $max_result = $stmt_max->fetch();
    $bestellungsnummer = 'B' . str_pad(($max_result['max_id'] ?? 0) + 1, 6, '0', STR_PAD_LEFT);
    
    $stmt_create_order = $PDO->prepare("INSERT INTO bestellungen (Kundennummer, Bestellungsnummer, Bestellungsname, status) VALUES (?, ?, ?, 'neu')");
    $stmt_create_order->execute([$kundennummer, $bestellungsnummer, $bestellungsname]);

    header('Location: web_oberflaeche.php');
    exit;
} catch (Exception $e) {
    echo "Fehler beim Erstellen der Bestellung: " . $e->getMessage();
}