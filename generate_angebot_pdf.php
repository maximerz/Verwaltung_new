<?php
session_start();
require_once 'db_connection.php';

$angebotsnummer = $_GET['angebot'] ?? $_GET['order_id'] ?? null;
$order = null;

if ($angebotsnummer) {
    if (is_numeric($angebotsnummer)) {
        // Suche nach order_id
        $stmt = $PDO->prepare("SELECT * FROM bestellungen WHERE idbestellung = ?");
        $stmt->execute([$angebotsnummer]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Suche nach Angebotsnummer
        $stmt = $PDO->prepare("SELECT * FROM bestellungen WHERE angebotsnummer = ?");
        $stmt->execute([$angebotsnummer]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$order) {
    die('Angebot nicht gefunden');
}

// Kundendaten laden
$kunde = null;
if ($order['kundennummer']) {
    $stmt = $PDO->prepare("
        SELECT k.*, f.firmenname, f.strasse, f.ort 
        FROM kundensystem k 
        LEFT JOIN firma f ON k.firma_id = f.id 
        WHERE k.kundennummer = ?
    ");
    $stmt->execute([$order['kundennummer']]);
    $kunde = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Positionen laden
$stmt = $PDO->prepare("SELECT * FROM angebot_positionen WHERE angebot_id = ?");
$stmt->execute([$order['idbestellung']]);
$positionen = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angebot - <?php echo htmlspecialchars($order['angebotsnummer']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
        }
        .angebot-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #C9A227;
            padding-bottom: 20px;
        }
        .kunde-info, .angebot-details {
            margin-bottom: 30px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .total {
            text-align: right;
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 20px;
        }
        .print-buttons {
            text-align: center;
            margin: 20px 0;
        }
        .btn {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #C9A227;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        @media print {
            .print-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="angebot-container">
        <div class="header">
            <h1>ANGEBOT</h1>
            <h2><?php echo htmlspecialchars($order['angebotsnummer']); ?></h2>
            <p>Datum: <?php echo date('d.m.Y'); ?></p>
        </div>

        <?php if ($kunde): ?>
        <div class="kunde-info">
            <h3>Kunde</h3>
            <p><strong><?php echo htmlspecialchars($kunde['vorname'] . ' ' . $kunde['nachname']); ?></strong></p>
            <p><?php echo htmlspecialchars($kunde['firmenname']); ?></p>
            <p><?php echo htmlspecialchars($kunde['strasse']); ?></p>
            <p><?php echo htmlspecialchars($kunde['ort']); ?></p>
            <p>Email: <?php echo htmlspecialchars($kunde['email']); ?></p>
        </div>
        <?php endif; ?>

        <div class="angebot-details">
            <h3>Angebots-Details</h3>
            <p><strong>Bestellungsname:</strong> <?php echo htmlspecialchars($order['bestellungsname']); ?></p>
            <p><strong>Lieferzeit:</strong> <?php echo htmlspecialchars($order['lieferzeit']); ?> Tage</p>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Pos.</th>
                    <th>Artikel</th>
                    <th>Beschreibung</th>
                    <th>Menge</th>
                    <th>Einzelpreis</th>
                    <th>Gesamtpreis</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $pos_nr = 1;
                $gesamtsumme = 0;
                foreach ($positionen as $pos): 
                    $gesamtsumme += $pos['gesamtpreis'];
                ?>
                <tr>
                    <td><?php echo $pos_nr++; ?></td>
                    <td><?php echo htmlspecialchars($pos['artikel']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($pos['beschreibung'])); ?></td>
                    <td><?php echo htmlspecialchars($pos['menge']); ?></td>
                    <td><?php echo number_format($pos['einzelpreis'], 2, ',', '.'); ?> €</td>
                    <td><?php echo number_format($pos['gesamtpreis'], 2, ',', '.'); ?> €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total">
            <p>Gesamtsumme (netto): <?php echo number_format($gesamtsumme, 2, ',', '.'); ?> €</p>
            <p>MwSt. (19%): <?php echo number_format($gesamtsumme * 0.19, 2, ',', '.'); ?> €</p>
            <p><strong>Gesamtsumme (brutto): <?php echo number_format($gesamtsumme * 1.19, 2, ',', '.'); ?> €</strong></p>
        </div>

        <div class="print-buttons">
            <button onclick="window.print()" class="btn btn-primary">Drucken</button>
            <a href="confirm_angebot.php?order_id=<?php echo $order['idbestellung']; ?>" class="btn btn-success">Angebot bestätigen</a>
            <a href="web_oberflaeche.php" class="btn">Zurück</a>
        </div>
    </div>
</body>
</html>