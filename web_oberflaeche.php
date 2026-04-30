<?php 
session_start();
require_once 'db_connection.php';

$page_title = 'ERP Dashboard';

// Lager-Warnungen anzeigen
$lager_warnungen = $_SESSION['lager_warnungen'] ?? [];
$angebot_id = $_SESSION['angebot_id'] ?? null;
if (!empty($lager_warnungen)) {
    unset($_SESSION['lager_warnungen']);
    unset($_SESSION['angebot_id']);
}

//Abfrage der Firmen aus der Datenbank
$stmt_companies = $PDO->prepare("SELECT * FROM firma");
$stmt_companies->execute();
$companies = $stmt_companies->fetchAll(PDO::FETCH_ASSOC);

//Abfrage der Kunden mit Firmen-Informationen aus der Datenbank
try {
    $stmt_customers = $PDO->prepare("
        SELECT k.*, f.firmenname, f.strasse, f.ort 
        FROM kundensystem k 
        LEFT JOIN firma f ON k.firma_id = f.id
    ");
    $stmt_customers->execute();
    $customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback: Kunden ohne Firmen-Join laden
    $stmt_customers = $PDO->prepare("SELECT * FROM kundensystem");
    $stmt_customers->execute();
    $customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);
}

//Abfrage der Angebote aus der Datenbank
$stmt_angebote = $PDO->prepare("
    SELECT b.*, k.vorname, k.nachname, k.email, f.firmenname 
    FROM bestellungen b 
    LEFT JOIN kundensystem k ON b.kundennummer = k.kundennummer 
    LEFT JOIN firma f ON k.firma_id = f.id 
    WHERE b.status = 'angebot'
");
$stmt_angebote->execute();
$angebote = $stmt_angebote->fetchAll(PDO::FETCH_ASSOC);

//Abfrage der Bestellungen aus der Datenbank
$stmt_orders = $PDO->prepare("
    SELECT b.*, k.vorname, k.nachname, k.email, f.firmenname 
    FROM bestellungen b 
    LEFT JOIN kundensystem k ON b.kundennummer = k.kundennummer 
    LEFT JOIN firma f ON k.firma_id = f.id 
    WHERE b.status = 'bestaetigt'
");
$stmt_orders->execute();
$orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
include 'includes/table-style.php';
?>

<?php if (!empty($lager_warnungen)): ?>
    <div class="alert alert-warning mb-4">
        <h5><i class="fas fa-exclamation-triangle me-2"></i>Lager-Hinweise für Angebot #<?= $angebot_id ?></h5>
        <p>Das Angebot wurde erstellt, aber folgende Artikel müssen nachbestellt werden:</p>
        <ul class="mb-3">
            <?php foreach ($lager_warnungen as $warnung): ?>
                <li><?= $warnung ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="lagerverwaltung.php" class="action-btn btn-warning-modern">
            <i class="fas fa-warehouse me-1"></i>Zur Lagerverwaltung
        </a>
    </div>
<?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card" onclick="document.getElementById('kunden').scrollIntoView({behavior: 'smooth'});">
                        <div class="stat-number"><?= count($customers) ?></div>
                        <div class="stat-label"><i class="fas fa-users me-1"></i>Kunden</div>
                    </div>
                    <div class="stat-card" onclick="document.getElementById('angebote').scrollIntoView({behavior: 'smooth'});">
                        <div class="stat-number"><?= count($angebote) ?></div>
                        <div class="stat-label"><i class="fas fa-file-invoice me-1"></i>Angebote</div>
                    </div>
                    <div class="stat-card" onclick="document.getElementById('bestellungen').scrollIntoView({behavior: 'smooth'});">
                        <div class="stat-number"><?= count($orders) ?></div>
                        <div class="stat-label"><i class="fas fa-shopping-cart me-1"></i>Bestellungen</div>
                    </div>
                </div>

                <!-- Customers Section -->
                <div class="dashboard-card" id="kunden">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-users"></i>Kunden
                        </h2>
                        <div class="d-flex gap-3 align-items-center">
                            <a href="create_customer_form.php" class="action-btn btn-success-modern">
                                <i class="fas fa-plus me-1"></i>Neuer Kunde
                            </a>
                            <div class="search-container">
                                
                                <input type="text" id="kundenSuche" class="form-control search-input" placeholder="Kunden durchsuchen...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modern-table">
                        <table class="table" id="kundenTabelle">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                    <th><i class="fas fa-user me-1"></i>Name</th>
                                    <th><i class="fas fa-envelope me-1"></i>E-Mail</th>
                                    <th><i class="fas fa-building me-1"></i>Firma</th>
                                    <th><i class="fas fa-road me-1"></i>Straße</th>
                                    <th><i class="fas fa-map-marker-alt me-1"></i>Ort</th>
                                    <th><i class="fas fa-cogs me-1"></i>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (count($customers) > 0) {
                                    foreach($customers as $customer) {
                                        echo '<tr>
                                            <td><span class="status-badge badge-primary">'.htmlspecialchars($customer["kundennummer"] ?? "N/A").'</span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                        '.strtoupper(substr($customer["vorname"] ?? "?", 0, 1)).'
                                                    </div>
                                                    <strong>'.htmlspecialchars(($customer["vorname"] ?? "")." ".($customer["nachname"] ?? "")).'</strong>
                                                </div>
                                            </td>
                                            <td><a href="mailto:'.htmlspecialchars($customer["email"] ?? "").'" class="text-decoration-none">
                                                '.htmlspecialchars($customer["email"] ?? "N/A").'
                                            </a></td>
                                            <td><span class="status-badge badge-info">'.htmlspecialchars($customer["firmenname"] ?? "Privat").'</span></td>
                                            <td>'.htmlspecialchars($customer["strasse"] ?? "-").'</td>
                                            <td>'.htmlspecialchars($customer["ort"] ?? "-").'</td>
                                            <td>
                                                <a href="kunde_details.php?id='.$customer["id"].'" class="action-btn btn-primary-modern">
                                                    <i class="fas fa-eye me-1"></i>Details
                                                </a>
                                                <a href="angebot_formular.php?kunde_id='.$customer["id"].'" class="action-btn btn-success-modern">
                                                    <i class="fas fa-file-invoice me-1"></i>Angebot
                                                </a>
                                            </td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Keine Kunden vorhanden</p>
                                    </td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quotes Section -->
                <div class="dashboard-card" id="angebote">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-file-invoice"></i>Angebote
                        </h2>
                        <div class="search-container">
                            
                            <input type="text" id="angeboteSuche" class="form-control search-input" placeholder="Angebote durchsuchen...">
                        </div>
                    </div>
                    
                    <div class="modern-table">
                        <table class="table" id="angeboteTabelle">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-1"></i>Nummer</th>
                                    <th><i class="fas fa-user me-1"></i>Kunde</th>
                                    <th><i class="fas fa-tag me-1"></i>Bezeichnung</th>
                                    <th><i class="fas fa-euro-sign me-1"></i>Wert</th>
                                    <th><i class="fas fa-cogs me-1"></i>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (count($angebote) > 0) {
                                    foreach($angebote as $angebot) {
                                        echo '<tr>
                                            <td><span class="status-badge badge-warning">'.htmlspecialchars($angebot["angebotsnummer"] ?? "N/A").'</span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                        '.strtoupper(substr($angebot["vorname"] ?? "?", 0, 1)).'
                                                    </div>
                                                    '.htmlspecialchars(($angebot["vorname"] ?? "")." ".($angebot["nachname"] ?? "")).'
                                                </div>
                                            </td>
                                            <td><strong>'.htmlspecialchars($angebot["bestellunsname"] ?? $angebot["bestellungsname"] ?? "N/A").'</strong></td>
                                            <td><span class="fw-bold text-success">'.number_format($angebot["gesamtpreis"] ?? 0, 2, ',', '.').' €</span></td>
                                            <td>
                                                <a href="generate_angebot_pdf.php?order_id='.$angebot["idbestellung"].'" class="action-btn btn-primary-modern" target="_blank">
                                                    <i class="fas fa-file-pdf me-1"></i>PDF
                                                </a>
                                                <a href="send_email.php?order_id='.$angebot["idbestellung"].'&type=angebot" class="action-btn btn-info" style="background: linear-gradient(135deg, #42A5F5 0%, #1E88E5 100%);">
                                                    <i class="fas fa-envelope me-1"></i>E-Mail
                                                </a>
                                                <a href="angebot_formular.php?angebot_id='.$angebot["idbestellung"].'" class="action-btn btn-warning-modern">
                                                    <i class="fas fa-edit me-1"></i>Bearbeiten
                                                </a>
                                                <a href="confirm_angebot.php?order_id='.$angebot["idbestellung"].'" class="action-btn btn-success-modern">
                                                    <i class="fas fa-check me-1"></i>Bestellen
                                                </a>
                                                <a href="delete_order.php?order_id='.$angebot["idbestellung"].'" class="action-btn btn-danger-modern" onclick="return confirm(\'Angebot wirklich löschen?\')">
                                                    <i class="fas fa-trash me-1"></i>Löschen
                                                </a>
                                            </td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center py-5">
                                        <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Keine Angebote vorhanden</p>
                                    </td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Orders Section -->
                <div class="dashboard-card" id="bestellungen">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-shopping-cart"></i>Bestellungen
                        </h2>
                        <div class="search-container">
                            
                            <input type="text" id="bestellungenSuche" class="form-control search-input" placeholder="Bestellungen durchsuchen...">
                        </div>
                    </div>
                    
                    <div class="modern-table">
                        <table class="table" id="bestellungenTabelle">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-1"></i>Nummer</th>
                                    <th><i class="fas fa-user me-1"></i>Kunde</th>
                                    <th><i class="fas fa-tag me-1"></i>Bezeichnung</th>
                                    <th><i class="fas fa-file me-1"></i>Angebot</th>
                                    <th><i class="fas fa-euro-sign me-1"></i>Wert</th>
                                    <th><i class="fas fa-cogs me-1"></i>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (count($orders) > 0) {
                                    foreach($orders as $order) {
                                        echo '<tr>
                                            <td><span class="status-badge badge-success">'.htmlspecialchars($order["bestellungsnummer"] ?? "N/A").'</span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                        '.strtoupper(substr($order["vorname"] ?? "?", 0, 1)).'
                                                    </div>
                                                    '.htmlspecialchars(($order["vorname"] ?? "")." ".($order["nachname"] ?? "")).'
                                                </div>
                                            </td>
                                            <td><strong>'.htmlspecialchars($order["bestellunsname"] ?? $order["bestellungsname"] ?? "N/A").'</strong></td>
                                            <td>'.htmlspecialchars($order["angebotsnummer"] ?? "N/A").'</td>
                                            <td><span class="fw-bold text-success">'.number_format($order["gesamtpreis"] ?? 0, 2, ',', '.').' €</span></td>
                                            <td>
                                                <a href="generate_pdf.php?order_id='.$order["idbestellung"].'" class="action-btn btn-primary-modern" target="_blank">
                                                    <i class="fas fa-file-pdf me-1"></i>Bestätigung
                                                </a>
                                                <a href="send_email.php?order_id='.$order["idbestellung"].'&type=bestellung" class="action-btn btn-info" style="background: linear-gradient(135deg, #42A5F5 0%, #1E88E5 100%);">
                                                    <i class="fas fa-envelope me-1"></i>E-Mail
                                                </a>
                                                <a href="lieferschein_editor.php?bestellung_id='.$order["idbestellung"].'" class="action-btn btn-success-modern">
                                                    <i class="fas fa-truck me-1"></i>Lieferschein
                                                </a>
                                                <span class="status-badge badge-success">
                                                    <i class="fas fa-check-circle me-1"></i>Abgeschlossen
                                                </span>
                                            </td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center py-5">
                                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Keine Bestellungen vorhanden</p>
                                    </td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

<script>
// Kunden-Suchfunktion
document.getElementById('kundenSuche').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#kundenTabelle tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Angebote-Suchfunktion
document.getElementById('angeboteSuche').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#angeboteTabelle tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Bestellungen-Suchfunktion
document.getElementById('bestellungenSuche').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#bestellungenTabelle tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>
<?php include 'includes/footer.php'; ?>