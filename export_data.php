<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$export_type = $_GET['export'] ?? '';
$zeitraum = $_GET['zeitraum'] ?? 'monat';
$jahr = $_GET['jahr'] ?? date('Y');
$monat = $_GET['monat'] ?? date('m');
$kunde_filter = $_GET['kunde_filter'] ?? '';
$produkt_filter = $_GET['produkt_filter'] ?? '';

// Zeitraum-Bedingungen
$where_clause = '';
$params = [];

switch ($zeitraum) {
    case 'heute':
        $where_clause = "AND DATE(b.erstellt_am) = DATE('now')";
        break;
    case 'woche':
        $where_clause = "AND DATE(b.erstellt_am) >= DATE('now', '-7 days')";
        break;
    case 'monat':
        $where_clause = "AND strftime('%Y-%m', b.erstellt_am) = ?";
        $params[] = "$jahr-" . str_pad($monat, 2, '0', STR_PAD_LEFT);
        break;
    case 'quartal':
        $q = ceil($monat / 3);
        $start_month = ($q - 1) * 3 + 1;
        $end_month = $q * 3;
        $where_clause = "AND strftime('%Y', b.erstellt_am) = ? AND CAST(strftime('%m', b.erstellt_am) AS INTEGER) BETWEEN ? AND ?";
        $params = [$jahr, $start_month, $end_month];
        break;
    case 'jahr':
        $where_clause = "AND strftime('%Y', b.erstellt_am) = ?";
        $params[] = $jahr;
        break;
}

// Filter hinzufügen
if ($kunde_filter) {
    $where_clause .= " AND (k.vorname LIKE ? OR k.nachname LIKE ? OR f.firmenname LIKE ?)";
    $params[] = "%$kunde_filter%";
    $params[] = "%$kunde_filter%";
    $params[] = "%$kunde_filter%";
}

if ($produkt_filter) {
    $where_clause .= " AND ap.artikel LIKE ?";
    $params[] = "%$produkt_filter%";
}

function outputCSV($filename, $data, $headers) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers, ';');
    
    foreach ($data as $row) {
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit;
}

try {
    switch ($export_type) {
        case 'kunden':
            $stmt = $PDO->prepare("
                SELECT 
                    k.vorname, k.nachname, k.email, f.firmenname, f.strasse, f.ort,
                    SUM(b.gesamtpreis) as umsatz,
                    COUNT(b.idbestellung) as bestellungen,
                    AVG(b.gesamtpreis) as avg_bestellung,
                    MIN(b.erstellt_am) as erste_bestellung,
                    MAX(b.erstellt_am) as letzte_bestellung
                FROM kundensystem k 
                LEFT JOIN firma f ON k.firma_id = f.id 
                LEFT JOIN bestellungen b ON k.kundennummer = b.kundennummer
                WHERE (b.status = 'bestaetigt' OR b.status IS NULL) $where_clause
                GROUP BY k.kundennummer 
                ORDER BY umsatz DESC
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_NUM);
            
            outputCSV(
                'kunden_export_' . date('Y-m-d') . '.csv',
                $data,
                ['Vorname', 'Nachname', 'E-Mail', 'Firma', 'Straße', 'Ort', 'Umsatz', 'Bestellungen', 'Ø Bestellung', 'Erste Bestellung', 'Letzte Bestellung']
            );
            break;
            
        case 'produkte':
            $stmt = $PDO->prepare("
                SELECT 
                    ap.artikel,
                    SUM(ap.menge) as verkaufte_menge,
                    SUM(ap.gesamtpreis) as umsatz,
                    AVG(ap.einzelpreis) as avg_preis,
                    COUNT(DISTINCT b.kundennummer) as anzahl_kunden,
                    COUNT(DISTINCT b.idbestellung) as anzahl_bestellungen,
                    MIN(ap.einzelpreis) as min_preis,
                    MAX(ap.einzelpreis) as max_preis
                FROM angebot_positionen ap
                JOIN bestellungen b ON ap.angebot_id = b.idbestellung
                LEFT JOIN kundensystem k ON b.kundennummer = k.kundennummer 
                LEFT JOIN firma f ON k.firma_id = f.id 
                WHERE b.status = 'bestaetigt' $where_clause
                GROUP BY ap.artikel
                ORDER BY umsatz DESC
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_NUM);
            
            outputCSV(
                'produkte_export_' . date('Y-m-d') . '.csv',
                $data,
                ['Artikel', 'Verkaufte Menge', 'Umsatz', 'Ø Preis', 'Anzahl Kunden', 'Anzahl Bestellungen', 'Min Preis', 'Max Preis']
            );
            break;
            
        case 'bestellungen':
            $stmt = $PDO->prepare("
                SELECT 
                    b.idbestellung, b.bestellungsnummer, b.bestellungsname, b.auftragsnummer,
                    b.gesamtpreis, b.status, b.erstellt_am, b.lieferzeit,
                    k.vorname, k.nachname, k.email, f.firmenname
                FROM bestellungen b 
                LEFT JOIN kundensystem k ON b.kundennummer = k.kundennummer 
                LEFT JOIN firma f ON k.firma_id = f.id 
                WHERE 1=1 $where_clause
                ORDER BY b.erstellt_am DESC
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_NUM);
            
            outputCSV(
                'bestellungen_export_' . date('Y-m-d') . '.csv',
                $data,
                ['ID', 'Bestellnummer', 'Bestellname', 'Auftragsnummer', 'Gesamtpreis', 'Status', 'Erstellt am', 'Lieferzeit', 'Vorname', 'Nachname', 'E-Mail', 'Firma']
            );
            break;
            
        case 'finanzen':
            $stmt = $PDO->prepare("
                SELECT 
                    r.rechnungsnummer, r.rechnungsdatum, r.faelligkeitsdatum, r.zahlungsdatum,
                    r.nettobetrag, r.mwst_betrag, r.bruttobetrag, r.status,
                    k.vorname, k.nachname, f.firmenname
                FROM rechnungen r
                LEFT JOIN kundensystem k ON r.kunde_id = k.kundennummer
                LEFT JOIN firma f ON k.firma_id = f.id
                ORDER BY r.rechnungsdatum DESC
            ");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_NUM);
            
            outputCSV(
                'finanzen_export_' . date('Y-m-d') . '.csv',
                $data,
                ['Rechnungsnummer', 'Rechnungsdatum', 'Fälligkeitsdatum', 'Zahlungsdatum', 'Nettobetrag', 'MwSt', 'Bruttobetrag', 'Status', 'Vorname', 'Nachname', 'Firma']
            );
            break;
            
        default:
            header('Location: reporting.php');
            exit;
    }
    
} catch (Exception $e) {
    echo "Fehler beim Export: " . $e->getMessage();
}
?>