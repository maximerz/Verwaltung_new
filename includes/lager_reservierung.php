<?php
// Automatische Lagerreservierung für Angebote
require_once 'db_connection.php';

function reserviereLagerbestand($angebot_id, $PDO) {
    try {
        // Angebot laden
        $stmt = $PDO->prepare("SELECT * FROM bestellungen WHERE idbestellung = ?");
        $stmt->execute([$angebot_id]);
        $angebot = $stmt->fetch();
        
        if (!$angebot) {
            return ['success' => false, 'message' => 'Angebot nicht gefunden'];
        }
        
        // Positionen laden
        $stmt = $PDO->prepare("SELECT * FROM angebot_positionen WHERE angebot_id = ?");
        $stmt->execute([$angebot_id]);
        $positionen = $stmt->fetchAll();
        
        $reserviert = 0;
        $fehler = [];
        
        foreach ($positionen as $position) {
            // Prüfe ob Artikel im Lager existiert
            $stmt = $PDO->prepare("SELECT * FROM lagerartikel WHERE artikelname = ? OR artikelnummer = ?");
            $stmt->execute([$position['artikel'], $position['artikel']]);
            $lagerartikel = $stmt->fetch();
            
            if ($lagerartikel) {
                $verfuegbar = $lagerartikel['bestand'] - $lagerartikel['reserviert'];
                
                if ($verfuegbar >= $position['menge']) {
                    // Reservierung erstellen
                    $stmt = $PDO->prepare("INSERT INTO lager_reservierungen (artikel_id, menge, reserviert_fuer, angebot_id, status) VALUES (?, ?, ?, ?, 'reserviert')");
                    $stmt->execute([
                        $lagerartikel['id'],
                        $position['menge'],
                        'Angebot ' . $angebot['angebotsnummer'],
                        $angebot_id
                    ]);
                    
                    // Reservierten Bestand aktualisieren
                    $stmt = $PDO->prepare("UPDATE lagerartikel SET reserviert = reserviert + ? WHERE id = ?");
                    $stmt->execute([$position['menge'], $lagerartikel['id']]);
                    
                    $reserviert++;
                } else {
                    $fehler[] = $position['artikel'] . ': Nur ' . $verfuegbar . ' verfügbar (benötigt: ' . $position['menge'] . ')';
                }
            } else {
                $fehler[] = $position['artikel'] . ': Nicht im Lager gefunden';
            }
        }
        
        if (count($fehler) > 0) {
            return [
                'success' => false,
                'message' => 'Teilweise reserviert (' . $reserviert . ' von ' . count($positionen) . ')',
                'fehler' => $fehler
            ];
        }
        
        return [
            'success' => true,
            'message' => $reserviert . ' Artikel erfolgreich reserviert'
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Fehler: ' . $e->getMessage()];
    }
}

function entferneReservierung($angebot_id, $PDO) {
    try {
        // Reservierungen laden
        $stmt = $PDO->prepare("SELECT * FROM lager_reservierungen WHERE angebot_id = ? AND status = 'reserviert'");
        $stmt->execute([$angebot_id]);
        $reservierungen = $stmt->fetchAll();
        
        foreach ($reservierungen as $res) {
            // Reservierten Bestand reduzieren
            $stmt = $PDO->prepare("UPDATE lagerartikel SET reserviert = reserviert - ? WHERE id = ?");
            $stmt->execute([$res['menge'], $res['artikel_id']]);
            
            // Reservierung löschen
            $stmt = $PDO->prepare("DELETE FROM lager_reservierungen WHERE id = ?");
            $stmt->execute([$res['id']]);
        }
        
        return ['success' => true, 'message' => 'Reservierungen entfernt'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Fehler: ' . $e->getMessage()];
    }
}

function bestaetigenReservierung($angebot_id, $PDO) {
    try {
        // Reservierungen in Bestand umwandeln
        $stmt = $PDO->prepare("SELECT * FROM lager_reservierungen WHERE angebot_id = ? AND status = 'reserviert'");
        $stmt->execute([$angebot_id]);
        $reservierungen = $stmt->fetchAll();
        
        foreach ($reservierungen as $res) {
            // Bestand reduzieren und Reservierung entfernen
            $stmt = $PDO->prepare("UPDATE lagerartikel SET bestand = bestand - ?, reserviert = reserviert - ? WHERE id = ?");
            $stmt->execute([$res['menge'], $res['menge'], $res['artikel_id']]);
            
            // Reservierung als ausgeliefert markieren
            $stmt = $PDO->prepare("UPDATE lager_reservierungen SET status = 'ausgeliefert' WHERE id = ?");
            $stmt->execute([$res['id']]);
        }
        
        return ['success' => true, 'message' => 'Bestand aktualisiert'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Fehler: ' . $e->getMessage()];
    }
}
?>
