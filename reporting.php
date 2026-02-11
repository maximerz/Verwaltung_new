<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Filter-Parameter
$zeitraum = $_GET['zeitraum'] ?? 'monat';
$jahr = $_GET['jahr'] ?? date('Y');
$monat = $_GET['monat'] ?? date('m');

// Zeitraum-Bedingungen
$where_clause = '';
$params = [];

switch ($zeitraum) {
    case 'heute':
        $where_clause = "AND DATE(COALESCE(b.erstellt_am, CURRENT_TIMESTAMP)) = DATE('now')";
        break;
    case 'woche':
        $where_clause = "AND DATE(COALESCE(b.erstellt_am, CURRENT_TIMESTAMP)) >= DATE('now', '-7 days')";
        break;
    case 'monat':
        $where_clause = "AND strftime('%Y-%m', COALESCE(b.erstellt_am, CURRENT_TIMESTAMP)) = ?";
        $params[] = "$jahr-" . str_pad($monat, 2, '0', STR_PAD_LEFT);
        break;
    case 'jahr':
        $where_clause = "AND strftime('%Y', COALESCE(b.erstellt_am, CURRENT_TIMESTAMP)) = ?";
        $params[] = $jahr;
        break;
}

// Dashboard KPIs
$stmt = $PDO->prepare("
    SELECT 
        SUM(gesamtpreis) as umsatz,
        COUNT(*) as bestellungen,
        AVG(gesamtpreis) as avg_order,
        COUNT(DISTINCT kundennummer) as unique_customers
    FROM bestellungen b 
    WHERE status = 'bestaetigt' $where_clause
");
$stmt->execute($params);
$kpis = $stmt->fetch();

// Top Kunden
$stmt = $PDO->prepare("
    SELECT 
        k.vorname, k.nachname, f.firmenname,
        SUM(b.gesamtpreis) as umsatz,
        COUNT(b.idbestellung) as bestellungen
    FROM bestellungen b 
    LEFT JOIN kundensystem k ON b.kundennummer = k.kundennummer 
    LEFT JOIN firma f ON k.firma_id = f.id 
    WHERE b.status = 'bestaetigt' $where_clause
    GROUP BY b.kundennummer 
    ORDER BY umsatz DESC 
    LIMIT 10
");
$stmt->execute($params);
$top_kunden = $stmt->fetchAll();

// Produktanalyse
$stmt = $PDO->prepare("
    SELECT 
        ap.artikel,
        SUM(ap.menge) as verkaufte_menge,
        SUM(ap.gesamtpreis) as umsatz
    FROM angebot_positionen ap
    JOIN bestellungen b ON ap.angebot_id = b.idbestellung
    WHERE b.status = 'bestaetigt' $where_clause
    GROUP BY ap.artikel
    ORDER BY umsatz DESC
    LIMIT 10
");
$stmt->execute($params);
$produkt_analyse = $stmt->fetchAll();

$page_title = 'Business Intelligence';
include 'includes/header.php';
include 'includes/table-style.php';
?>

<style>
.filter-bar { background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.filter-row { display: flex; gap: 1rem; flex-wrap: wrap; align-items: end; }
.filter-group { flex: 1; min-width: 150px; }
.filter-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--secondary); font-size: 0.875rem; }
.tabs { display: flex; gap: 0.5rem; margin-bottom: 2rem; flex-wrap: wrap; }
.tab { padding: 0.75rem 1.5rem; background: white; border: 2px solid var(--border); border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s; }
.tab.active { background: var(--primary); color: white; border-color: var(--primary); }
.tab-content { display: none; }
.tab-content.active { display: block; }
</style>

<div class="dashboard-card">
    <h2 class="section-title mb-4">
        <i class="fas fa-chart-bar me-2"></i>Business Intelligence Dashboard
    </h2>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" class="filter-row">
            <div class="filter-group">
                <label><i class="fas fa-calendar me-1"></i>Zeitraum</label>
                <select name="zeitraum" class="form-select" onchange="this.form.submit()">
                    <option value="heute" <?= $zeitraum === 'heute' ? 'selected' : '' ?>>Heute</option>
                    <option value="woche" <?= $zeitraum === 'woche' ? 'selected' : '' ?>>Diese Woche</option>
                    <option value="monat" <?= $zeitraum === 'monat' ? 'selected' : '' ?>>Dieser Monat</option>
                    <option value="jahr" <?= $zeitraum === 'jahr' ? 'selected' : '' ?>>Dieses Jahr</option>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-calendar-alt me-1"></i>Jahr</label>
                <select name="jahr" class="form-select" onchange="this.form.submit()">
                    <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                        <option value="<?= $y ?>" <?= $jahr == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-calendar-day me-1"></i>Monat</label>
                <select name="monat" class="form-select" onchange="this.form.submit()">
                    <?php 
                    $monate = ['01'=>'Januar', '02'=>'Februar', '03'=>'März', '04'=>'April', '05'=>'Mai', '06'=>'Juni',
                              '07'=>'Juli', '08'=>'August', '09'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Dezember'];
                    foreach($monate as $m => $name): ?>
                        <option value="<?= $m ?>" <?= $monat == $m ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-number"><?= number_format($kpis['umsatz'] ?? 0, 0, ',', '.') ?> €</div>
            <div class="stat-label"><i class="fas fa-euro-sign me-1"></i>Gesamtumsatz</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $kpis['bestellungen'] ?? 0 ?></div>
            <div class="stat-label"><i class="fas fa-shopping-cart me-1"></i>Bestellungen</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($kpis['avg_order'] ?? 0, 0, ',', '.') ?> €</div>
            <div class="stat-label"><i class="fas fa-chart-line me-1"></i>Ø Bestellwert</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $kpis['unique_customers'] ?? 0 ?></div>
            <div class="stat-label"><i class="fas fa-users me-1"></i>Aktive Kunden</div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab active" onclick="showTab('kunden')"><i class="fas fa-users me-1"></i>Top Kunden</button>
        <button class="tab" onclick="showTab('produkte')"><i class="fas fa-box me-1"></i>Top Produkte</button>
    </div>

    <!-- Top Kunden -->
    <div id="kunden" class="tab-content active">
        <h4 class="mb-3"><i class="fas fa-trophy me-2"></i>Top 10 Kunden</h4>
        <div class="modern-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rang</th>
                        <th>Kunde</th>
                        <th>Firma</th>
                        <th>Umsatz</th>
                        <th>Bestellungen</th>
                        <th>Ø Bestellung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_kunden as $index => $kunde): ?>
                    <tr>
                        <td><strong><?= $index + 1 ?></strong></td>
                        <td><?= htmlspecialchars($kunde['vorname'] . ' ' . $kunde['nachname']) ?></td>
                        <td><?= htmlspecialchars($kunde['firmenname'] ?? '-') ?></td>
                        <td><strong><?= number_format($kunde['umsatz'], 2, ',', '.') ?> €</strong></td>
                        <td><?= $kunde['bestellungen'] ?></td>
                        <td><?= number_format($kunde['umsatz'] / $kunde['bestellungen'], 2, ',', '.') ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Produkte -->
    <div id="produkte" class="tab-content">
        <h4 class="mb-3"><i class="fas fa-box me-2"></i>Top 10 Produkte</h4>
        <div class="modern-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rang</th>
                        <th>Artikel</th>
                        <th>Verkaufte Menge</th>
                        <th>Umsatz</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produkt_analyse as $index => $produkt): ?>
                    <tr>
                        <td><strong><?= $index + 1 ?></strong></td>
                        <td><?= htmlspecialchars($produkt['artikel']) ?></td>
                        <td><?= $produkt['verkaufte_menge'] ?></td>
                        <td><strong><?= number_format($produkt['umsatz'], 2, ',', '.') ?> €</strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>
