<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$kunde_id = $_GET['id'] ?? '';
if (!$kunde_id) {
    header('Location: kunden_verwaltung.php');
    exit;
}

// Kundendaten laden
$stmt = $PDO->prepare("
    SELECT k.*, f.firmenname, f.strasse, f.ort 
    FROM kundensystem k 
    LEFT JOIN firma f ON k.firma_id = f.id 
    WHERE k.id = ?
");
$stmt->execute([$kunde_id]);
$kunde = $stmt->fetch();

if (!$kunde) {
    header('Location: kunden_verwaltung.php');
    exit;
}

// Konditionen-Vorlagen laden
$stmt = $PDO->prepare("SELECT * FROM konditionen_vorlagen WHERE aktiv = 1 ORDER BY name");
$stmt->execute();
$konditionen_vorlagen = $stmt->fetchAll();

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
    
    try {
        // Firma aktualisieren oder erstellen
        if ($kunde['firma_id']) {
            $stmt = $PDO->prepare("UPDATE firma SET firmenname = ?, strasse = ?, ort = ? WHERE id = ?");
            $stmt->execute([$firmenname, $strasse, $ort, $kunde['firma_id']]);
            $firma_id = $kunde['firma_id'];
        } else {
            $stmt = $PDO->prepare("INSERT INTO firma (firmenname, strasse, ort) VALUES (?, ?, ?)");
            $stmt->execute([$firmenname, $strasse, $ort]);
            $firma_id = $PDO->lastInsertId();
        }
        
        // Kunde aktualisieren
        $stmt = $PDO->prepare("UPDATE kundensystem SET vorname = ?, nachname = ?, email = ?, firma_id = ?, hinweise = ?, konditionen = ? WHERE id = ?");
        $stmt->execute([$vorname, $nachname, $email, $firma_id, $hinweise, $konditionen, $kunde_id]);
        
        $success = "Kundendaten erfolgreich aktualisiert!";
        
        // Daten neu laden
        $stmt = $PDO->prepare("
            SELECT k.*, f.firmenname, f.strasse, f.ort 
            FROM kundensystem k 
            LEFT JOIN firma f ON k.firma_id = f.id 
            WHERE k.id = ?
        ");
        $stmt->execute([$kunde_id]);
        $kunde = $stmt->fetch();
    } catch (Exception $e) {
        $error = "Fehler beim Aktualisieren: " . $e->getMessage();
    }
}
?>
<?php $page_title = 'Kundendaten bearbeiten'; include 'includes/header.php'; ?>
<?php include 'includes/table-style.php'; ?>

<div class="dashboard-card">
    <h2 class="section-title mb-4">
        <i class="fas fa-user-edit me-2"></i>Kundendaten bearbeiten
    </h2>

    <div class="mb-4">
        <a href="kunde_details.php?id=<?= $kunde_id ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Zurück zu Kundendetails</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success mb-4"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger mb-4"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label"><i class="fas fa-user me-1"></i>Vorname</label>
                <input type="text" class="form-control" name="vorname" value="<?= htmlspecialchars($kunde['vorname'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label"><i class="fas fa-user me-1"></i>Nachname</label>
                <input type="text" class="form-control" name="nachname" value="<?= htmlspecialchars($kunde['nachname'] ?? '') ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label"><i class="fas fa-envelope me-1"></i>E-Mail</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($kunde['email'] ?? '') ?>" required>
            </div>
        </div>
        
        <hr class="my-4">
        <h5 class="mb-3"><i class="fas fa-building me-2"></i>Firmendaten</h5>
        
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label"><i class="fas fa-building me-1"></i>Firmenname</label>
                <input type="text" class="form-control" name="firmenname" value="<?= htmlspecialchars($kunde['firmenname'] ?? '') ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label"><i class="fas fa-road me-1"></i>Straße</label>
                <input type="text" class="form-control" name="strasse" value="<?= htmlspecialchars($kunde['strasse'] ?? '') ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label"><i class="fas fa-map-marker-alt me-1"></i>Ort</label>
                <input type="text" class="form-control" name="ort" value="<?= htmlspecialchars($kunde['ort'] ?? '') ?>" required>
            </div>
        </div>
        
        <hr class="my-4">
        <h5 class="mb-3"><i class="fas fa-sticky-note me-2"></i>Hinweise</h5>
        
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Hinweise zum Kunden</label>
                <textarea class="form-control" name="hinweise" rows="4" placeholder="z.B. Wichtige Informationen, Präferenzen, Ansprechpartner..."><?= htmlspecialchars($kunde['hinweise'] ?? '') ?></textarea>
            </div>
        </div>
        
        <hr class="my-4">
        <h5 class="mb-3"><i class="fas fa-handshake me-2"></i>Konditionen</h5>
        
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Kundenspezifische Konditionen</label>
                <textarea class="form-control" name="konditionen" rows="4" placeholder="z.B. ServicePlus, Event-Rabatt, Sonderkonditionen..."><?= htmlspecialchars($kunde['konditionen'] ?? '') ?></textarea>
                <small class="text-muted">Diese Konditionen werden automatisch bei neuen Angeboten vorgeschlagen.</small>
            </div>
        </div>
        
        <div class="d-grid gap-2 mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-check me-2"></i>Änderungen speichern
            </button>
            <a href="kunde_details.php?id=<?= $kunde_id ?>" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i>Abbrechen
            </a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

