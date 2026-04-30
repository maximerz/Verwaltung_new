<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Kunden laden
$stmt = $PDO->prepare("
    SELECT k.*, f.firmenname, f.strasse, f.ort 
    FROM kundensystem k 
    LEFT JOIN firma f ON k.firma_id = f.id 
    ORDER BY k.nachname, k.vorname
");
$stmt->execute();
$kunden = $stmt->fetchAll();
?>
<?php $page_title = 'ERP System'; include 'includes/header.php'; ?><div class="container">
<?php include 'includes/table-style.php'; ?>
            <div class="dashboard-card animate-fade-in">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-users me-3"></i>Kundenverwaltung
                    </h1>
                    <p class="text-muted fs-5">Verwalten Sie alle Ihre Kunden an einem Ort</p>
                </div>

                <div class="nav-buttons">
                    <a href="web_oberflaeche.php" class="action-btn btn-primary-modern">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                    <a href="create_customer_form.php" class="action-btn btn-success-modern">
                        <i class="fas fa-plus me-2"></i>Neuer Kunde
                    </a>
                </div>

                <div class="search-container">
                    
                    <input type="text" id="kundenSuche" class="form-control search-input" placeholder="Kunden durchsuchen...">
                </div>

                <?php if ($kunden): ?>
                    <div class="modern-table">
                        <table class="table" id="kundenTabelle">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-2"></i>Kundennummer</th>
                                    <th><i class="fas fa-user me-2"></i>Name</th>
                                    <th><i class="fas fa-envelope me-2"></i>E-Mail</th>
                                    <th><i class="fas fa-building me-2"></i>Firma</th>
                                    <th><i class="fas fa-map-marker-alt me-2"></i>Ort</th>
                                    <th><i class="fas fa-cogs me-2"></i>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kunden as $kunde): ?>
                                <tr>
                                    <td>
                                        <span class="status-badge badge-primary">
                                            <?= htmlspecialchars($kunde['kundennummer']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3" style="width: 35px; height: 35px; font-size: 0.9rem;">
                                                <?= strtoupper(substr($kunde['vorname'] ?? '?', 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($kunde['vorname'] . ' ' . $kunde['nachname']) ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars($kunde['email']) ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($kunde['email']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="status-badge badge-info">
                                            <?= htmlspecialchars($kunde['firmenname'] ?? 'Privatkunde') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($kunde['ort'] ?? '-') ?></td>
                                    <td>
                                        <a href="kunde_details.php?id=<?= $kunde['id'] ?>" class="action-btn btn-primary-modern">
                                            <i class="fas fa-eye me-1"></i>Details
                                        </a>
                                        <a href="angebot_formular.php?kunde_id=<?= $kunde['id'] ?>" class="action-btn btn-success-modern">
                                            <i class="fas fa-file-invoice me-1"></i>Angebot
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-5x text-muted mb-4"></i>
                        <h3 class="text-muted">Keine Kunden vorhanden</h3>
                        <p class="text-muted mb-4">Erstellen Sie Ihren ersten Kunden</p>
                        <a href="create_customer_form.php" class="action-btn btn-success-modern">
                            <i class="fas fa-plus me-2"></i>Ersten Kunden anlegen
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function searchCustomers() {
            const searchTerm = document.getElementById('kundenSuche').value.toLowerCase();
            const rows = document.querySelectorAll('#kundenTabelle tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }
        
        document.getElementById('kundenSuche').addEventListener('keyup', searchCustomers);
    </script>
<?php include 'includes/footer.php'; ?>