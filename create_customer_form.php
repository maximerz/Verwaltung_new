<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Formular verarbeiten
if ($_POST) {
    $vorname = $_POST['vorname'] ?? '';
    $nachname = $_POST['nachname'] ?? '';
    $email = $_POST['email'] ?? '';
    $firmenname = $_POST['firmenname'] ?? '';
    $strasse = $_POST['strasse'] ?? '';
    $ort = $_POST['ort'] ?? '';
    $hinweise = $_POST['hinweise'] ?? '';
    $konditionen = $_POST['konditionen'] ?? '';

    if (!empty($vorname) && !empty($nachname) && !empty($email) && !empty($firmenname) && !empty($strasse) && !empty($ort)) {
        try {
            // Firma erstellen oder finden
            $stmt_check_firma = $PDO->prepare("SELECT id FROM firma WHERE firmenname = ? AND strasse = ? AND ort = ?");
            $stmt_check_firma->execute([$firmenname, $strasse, $ort]);
            $existing_firma = $stmt_check_firma->fetch();
            
            if ($existing_firma) {
                $firma_id = $existing_firma['id'];
            } else {
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
            $stmt_create_customer = $PDO->prepare("INSERT INTO kundensystem (kundennummer, vorname, nachname, email, firma_id, hinweise, konditionen) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_create_customer->execute([$kundennummer, $vorname, $nachname, $email, $firma_id, $hinweise, $konditionen]);

            $success = "Kunde erfolgreich erstellt!";
        } catch (Exception $e) {
            $error = "Fehler beim Erstellen des Kunden: " . $e->getMessage();
        }
    } else {
        $error = "Bitte alle Felder ausfüllen!";
    }
}
?>
<?php $page_title = 'Neuen Kunden erstellen'; include 'includes/header.php'; ?>
<?php include 'includes/table-style.php'; ?>

<div class="dashboard-card" style="max-width: 700px; margin: 0 auto;">
    <h2 class="section-title mb-4">
        <i class="fas fa-user-plus me-2"></i>Neuen Kunden erstellen
    </h2>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label"><i class="fas fa-user me-1"></i>Vorname</label>
                <input type="text" class="form-control" name="vorname" placeholder="z.B. Max" required>
            </div>
            <div class="col-md-6">
                <label class="form-label"><i class="fas fa-user me-1"></i>Nachname</label>
                <input type="text" class="form-control" name="nachname" placeholder="z.B. Mustermann" required>
            </div>
            <div class="col-12">
                <label class="form-label"><i class="fas fa-envelope me-1"></i>E-Mail</label>
                <input type="email" class="form-control" name="email" placeholder="z.B. max@example.com" required>
            </div>
        </div>
        
        <hr class="my-4">
        <h5 class="mb-3"><i class="fas fa-building me-2"></i>Firmendaten</h5>
        
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label"><i class="fas fa-building me-1"></i>Firmenname</label>
                <input type="text" class="form-control" name="firmenname" placeholder="z.B. Mustermann GmbH" required>
            </div>
            <div class="col-12">
                <label class="form-label"><i class="fas fa-road me-1"></i>Straße</label>
                <input type="text" class="form-control" name="strasse" placeholder="z.B. Musterstraße 123" required>
            </div>
            <div class="col-12">
                <label class="form-label"><i class="fas fa-map-marker-alt me-1"></i>Ort</label>
                <input type="text" class="form-control" name="ort" placeholder="z.B. 12345 Musterstadt" required>
            </div>
        </div>

        <hr class="my-4">
        <h5 class="mb-3"><i class="fas fa-sticky-note me-2"></i>Hinweise</h5>
        
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Hinweise zum Kunden</label>
                <textarea class="form-control" name="hinweise" rows="3" placeholder="Optionale Hinweise..."></textarea>
            </div>
        </div>
        
        <hr class="my-4">
        <h5 class="mb-3"><i class="fas fa-handshake me-2"></i>Konditionen</h5>
        
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Kundenspezifische Konditionen</label>
                <textarea class="form-control" name="konditionen" rows="3" placeholder="z.B. ServicePlus, Event-Rabatt..."></textarea>
            </div>
        </div>
        
        <div class="d-grid gap-2 mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-check me-2"></i>Kunde erstellen
            </button>
            <a href="web_oberflaeche.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Zurück
            </a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
