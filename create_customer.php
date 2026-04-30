<?php
// Session nur prüfen wenn nicht übersprungen
if (!isset($_POST['skip_session'])) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

require_once 'db_connection.php';

$vorname = $_POST['vorname'] ?? '';
$nachname = $_POST['nachname'] ?? '';
$email = $_POST['email'] ?? '';
$firmenname = $_POST['firmenname'] ?? '';
$strasse = $_POST['strasse'] ?? '';
$ort = $_POST['ort'] ?? '';

if (empty($vorname) || empty($nachname) || empty($email) || empty($firmenname) || empty($strasse) || empty($ort)) {
    $redirect_to = $_POST['redirect_to'] ?? 'simple_web.php';
    header("Location: $redirect_to");
    exit;
}

try {
    // Zuerst Firma erstellen oder finden
    $stmt_check_firma = $PDO->prepare("SELECT id FROM firma WHERE firmenname = ? AND strasse = ? AND ort = ?");
    $stmt_check_firma->execute([$firmenname, $strasse, $ort]);
    $existing_firma = $stmt_check_firma->fetch();
    
    if ($existing_firma) {
        $firma_id = $existing_firma['id'];
    } else {
        // Neue Firma erstellen
        $stmt_create_firma = $PDO->prepare("INSERT INTO firma (firmenname, strasse, ort) VALUES (?, ?, ?)");
        $stmt_create_firma->execute([$firmenname, $strasse, $ort]);
        $firma_id = $PDO->lastInsertId();
    }
    
    // Kundennummer generieren
    $stmt_max_kunde = $PDO->prepare("SELECT MAX(kundennummer) as max_nr FROM kundensystem");
    $stmt_max_kunde->execute();
    $max_result = $stmt_max_kunde->fetch();
    $kundennummer = ($max_result['max_nr'] ?? 0) + 1;
    
    // Kunde erstellen
    $stmt_create_customer = $PDO->prepare("INSERT INTO kundensystem (kundennummer, vorname, nachname, email, firma_id) VALUES (?, ?, ?, ?, ?)");
    $stmt_create_customer->execute([$kundennummer, $vorname, $nachname, $email, $firma_id]);

    $redirect_to = $_POST['redirect_to'] ?? 'simple_web.php';
    header("Location: $redirect_to");
    exit;
} catch (Exception $e) {
    echo "Fehler beim Erstellen des Kunden: " . $e->getMessage();
}
?>