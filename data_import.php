<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';
$import_log = [];

// Import verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    $import_type = $_POST['import_type'] ?? '';
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_content = file_get_contents($file['tmp_name']);
        $data = json_decode($file_content, true);
        
        if ($data === null) {
            $error = 'Ungültiges JSON-Format';
        } else {
            try {
                $PDO->beginTransaction();
                
                switch ($import_type) {
                    case 'kunden':
                        foreach ($data as $kunde) {
                            // Firma erstellen falls vorhanden
                            $firma_id = null;
                            if (!empty($kunde['firmenname'])) {
                                $stmt = $PDO->prepare("INSERT OR IGNORE INTO firma (firmenname, strasse, ort) VALUES (?, ?, ?)");
                                $stmt->execute([
                                    $kunde['firmenname'] ?? '',
                                    $kunde['strasse'] ?? '',
                                    $kunde['ort'] ?? ''
                                ]);
                                $firma_id = $PDO->lastInsertId() ?: null;
                            }
                            
                            // Kunde erstellen
                            $stmt = $PDO->prepare("INSERT INTO kundensystem (kundennummer, vorname, nachname, email, firma_id) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $kunde['kundennummer'] ?? rand(100000, 999999),
                                $kunde['vorname'] ?? '',
                                $kunde['nachname'] ?? '',
                                $kunde['email'] ?? '',
                                $firma_id
                            ]);
                            $import_log[] = "Kunde: " . ($kunde['vorname'] ?? '') . " " . ($kunde['nachname'] ?? '');
                        }
                        break;
                        
                    case 'artikel':
                        foreach ($data as $artikel) {
                            $stmt = $PDO->prepare("INSERT INTO produkte (name, beschreibung, preis, kategorie) VALUES (?, ?, ?, ?)");
                            $stmt->execute([
                                $artikel['name'] ?? $artikel['artikelname'] ?? '',
                                $artikel['beschreibung'] ?? '',
                                $artikel['preis'] ?? 0,
                                $artikel['kategorie'] ?? 'Allgemein'
                            ]);
                            $import_log[] = "Artikel: " . ($artikel['name'] ?? $artikel['artikelname'] ?? '');
                        }
                        break;
                        
                    case 'bestellungen':
                        foreach ($data as $bestellung) {
                            $stmt = $PDO->prepare("INSERT INTO bestellungen (kundennummer, bestellungsnummer, bestellungsname, angebotsnummer, status, gesamtpreis) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $bestellung['kundennummer'] ?? '',
                                $bestellung['bestellungsnummer'] ?? 'B' . rand(100000, 999999),
                                $bestellung['bestellungsname'] ?? '',
                                $bestellung['angebotsnummer'] ?? '',
                                $bestellung['status'] ?? 'neu',
                                $bestellung['gesamtpreis'] ?? 0
                            ]);
                            $import_log[] = "Bestellung: " . ($bestellung['bestellungsnummer'] ?? '');
                        }
                        break;
                        
                    default:
                        throw new Exception('Unbekannter Import-Typ');
                }
                
                $PDO->commit();
                $success = count($import_log) . " Datensätze erfolgreich importiert!";
            } catch (Exception $e) {
                $PDO->rollBack();
                $error = "Import fehlgeschlagen: " . $e->getMessage();
            }
        }
    } else {
        $error = "Fehler beim Hochladen der Datei";
    }
}

$page_title = 'GSOffice Import';
include 'includes/header.php';
?>

<style>
    .import-container {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 2rem;
        background: rgba(255,255,255,0.98);
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(20,184,166,0.15);
        border: 2px solid rgba(20,184,166,0.1);
    }
    .import-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 4px solid #14b8a6;
    }
    .drop-zone {
        border: 3px dashed #14b8a6;
        border-radius: 15px;
        padding: 40px;
        text-align: center;
        background: rgba(20,184,166,0.05);
        cursor: pointer;
        transition: all 0.3s;
    }
    .drop-zone:hover {
        background: rgba(20,184,166,0.1);
        border-color: #0d9488;
    }
    .drop-zone.dragover {
        background: rgba(20,184,166,0.15);
        border-color: #0d9488;
    }
    .log-item {
        padding: 8px 12px;
        background: white;
        border-left: 3px solid #10b981;
        margin-bottom: 5px;
        border-radius: 5px;
    }
</style>

<div class="import-container">
    <h1><i class="fas fa-file-import"></i> GSOffice Daten Import</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $success ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
        </div>
    <?php endif; ?>
    
    <div class="import-card">
        <h3><i class="fas fa-info-circle"></i> Anleitung</h3>
        <ol>
            <li>Wählen Sie den Datentyp aus, den Sie importieren möchten</li>
            <li>Laden Sie die JSON-Datei aus GSOffice hoch</li>
            <li>Klicken Sie auf "Importieren"</li>
        </ol>
        <p><strong>Unterstützte Formate:</strong> JSON (.json)</p>
    </div>
    
    <form method="POST" enctype="multipart/form-data" id="importForm">
        <div class="import-card">
            <h3><i class="fas fa-database"></i> Import-Typ auswählen</h3>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="import_type" value="kunden" id="type_kunden" required>
                        <label class="form-check-label" for="type_kunden">
                            <i class="fas fa-users"></i> Kunden
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="import_type" value="artikel" id="type_artikel">
                        <label class="form-check-label" for="type_artikel">
                            <i class="fas fa-box"></i> Artikel
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="import_type" value="bestellungen" id="type_bestellungen">
                        <label class="form-check-label" for="type_bestellungen">
                            <i class="fas fa-shopping-cart"></i> Bestellungen
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="import-card">
            <h3><i class="fas fa-upload"></i> Datei hochladen</h3>
            <div class="drop-zone" id="dropZone">
                <i class="fas fa-cloud-upload-alt fa-3x mb-3" style="color: #14b8a6;"></i>
                <p class="mb-2"><strong>Datei hier ablegen oder klicken zum Auswählen</strong></p>
                <p class="text-muted">JSON-Dateien werden unterstützt</p>
                <input type="file" name="import_file" id="fileInput" accept=".json" style="display: none;" required>
                <div id="fileName" class="mt-3"></div>
            </div>
        </div>
        
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-file-import"></i> Jetzt importieren
            </button>
            <a href="web_oberflaeche.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Zurück
            </a>
        </div>
    </form>
    
    <?php if (!empty($import_log)): ?>
        <div class="import-card mt-4">
            <h3><i class="fas fa-list"></i> Import-Protokoll</h3>
            <div class="log-container">
                <?php foreach ($import_log as $log): ?>
                    <div class="log-item">
                        <i class="fas fa-check text-success"></i> <?= htmlspecialchars($log) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="import-card mt-4">
        <h3><i class="fas fa-file-code"></i> Beispiel JSON-Format</h3>
        <p><strong>Kunden:</strong></p>
        <pre><code>[
  {
    "kundennummer": 123456,
    "vorname": "Max",
    "nachname": "Mustermann",
    "email": "max@example.com",
    "firmenname": "Musterfirma GmbH",
    "strasse": "Musterstraße 1",
    "ort": "12345 Musterstadt"
  }
]</code></pre>
        
        <p><strong>Artikel:</strong></p>
        <pre><code>[
  {
    "name": "Produkt A",
    "beschreibung": "Beschreibung",
    "preis": 99.99,
    "kategorie": "Kategorie A"
  }
]</code></pre>
    </div>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileName = document.getElementById('fileName');

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        updateFileName();
    }
});

fileInput.addEventListener('change', updateFileName);

function updateFileName() {
    if (fileInput.files.length > 0) {
        fileName.innerHTML = `<div class="alert alert-info mb-0">
            <i class="fas fa-file"></i> ${fileInput.files[0].name}
        </div>`;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
