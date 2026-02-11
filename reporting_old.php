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
$kunde_filter = $_GET['kunde_filter'] ?? '';
$produkt_filter = $_GET['produkt_filter'] ?? '';

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
    case 'quartal':
        $q = ceil($monat / 3);
        $start_month = ($q - 1) * 3 + 1;
        $end_month = $q * 3;
        $where_clause = "AND strftime('%Y', COALESCE(b.erstellt_am, CURRENT_TIMESTAMP)) = ? AND CAST(strftime('%m', COALESCE(b.erstellt_am, CURRENT_TIMESTAMP)) AS INTEGER) BETWEEN ? AND ?";
        $params = [$jahr, $start_month, $end_month];
        break;
    case 'jahr':
        $where_clause = "AND strftime('%Y', COALESCE(b.erstellt_am, CURRENT_TIMESTAMP)) = ?";
        $params[] = $jahr;
        break;
}

// Kunden-Filter
if ($kunde_filter) {
    $where_clause .= " AND (k.vorname LIKE ? OR k.nachname LIKE ? OR f.firmenname LIKE ?)";
    $params[] = "%$kunde_filter%";
    $params[] = "%$kunde_filter%";
    $params[] = "%$kunde_filter%";
}

// Produkt-Filter
if ($produkt_filter) {
    $where_clause .= " AND ap.artikel LIKE ?";
    $params[] = "%$produkt_filter%";
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

// Umsatzentwicklung (täglich für aktuellen Monat)
$stmt = $PDO->prepare("
    SELECT 
        DATE(COALESCE(erstellt_am, CURRENT_TIMESTAMP)) as tag,
        SUM(gesamtpreis) as tagesumsatz,
        COUNT(*) as tagesbestellungen
    FROM bestellungen 
    WHERE status = 'bestaetigt' 
    AND strftime('%Y-%m', COALESCE(erstellt_am, CURRENT_TIMESTAMP)) = ?
    GROUP BY DATE(COALESCE(erstellt_am, CURRENT_TIMESTAMP))
    ORDER BY tag
");
$stmt->execute(["$jahr-" . str_pad($monat, 2, '0', STR_PAD_LEFT)]);
$tagesumsatz = $stmt->fetchAll();

// Top Kunden mit detaillierter Analyse
$stmt = $PDO->prepare("
    SELECT 
        k.vorname, k.nachname, f.firmenname,
        SUM(b.gesamtpreis) as umsatz,
        COUNT(b.idbestellung) as bestellungen,
        AVG(b.gesamtpreis) as avg_bestellung,
        MIN(COALESCE(b.erstellt_am, CURRENT_TIMESTAMP)) as erste_bestellung,
        MAX(COALESCE(b.erstellt_am, CURRENT_TIMESTAMP)) as letzte_bestellung,
        COUNT(DISTINCT ap.artikel) as verschiedene_produkte
    FROM bestellungen b 
    LEFT JOIN kundensystem k ON b.kundennummer = k.kundennummer 
    LEFT JOIN firma f ON k.firma_id = f.id 
    LEFT JOIN angebot_positionen ap ON b.idbestellung = ap.angebot_id
    WHERE b.status = 'bestaetigt' $where_clause
    GROUP BY b.kundennummer 
    ORDER BY umsatz DESC 
    LIMIT 20
");
$stmt->execute($params);
$top_kunden = $stmt->fetchAll();

// Produktanalyse mit Rentabilität
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
    LIMIT 20
");
$stmt->execute($params);
$produkt_analyse = $stmt->fetchAll();

// Monatlicher Vergleich (12 Monate)
$stmt = $PDO->prepare("
    SELECT 
        strftime('%Y-%m', COALESCE(erstellt_am, CURRENT_TIMESTAMP)) as monat,
        SUM(gesamtpreis) as umsatz,
        COUNT(*) as bestellungen,
        COUNT(DISTINCT kundennummer) as kunden,
        AVG(gesamtpreis) as avg_bestellung
    FROM bestellungen 
    WHERE status = 'bestaetigt' 
    AND COALESCE(erstellt_am, CURRENT_TIMESTAMP) >= date('now', '-12 months')
    GROUP BY strftime('%Y-%m', COALESCE(erstellt_am, CURRENT_TIMESTAMP))
    ORDER BY monat DESC
");
$stmt->execute();
$monatsvergleich = $stmt->fetchAll();

// Zahlungsanalyse
$stmt = $PDO->prepare("
    SELECT 
        COUNT(*) as gesamt_rechnungen,
        SUM(CASE WHEN status = 'bezahlt' THEN 1 ELSE 0 END) as bezahlte_rechnungen,
        SUM(CASE WHEN status = 'offen' THEN 1 ELSE 0 END) as offene_rechnungen,
        SUM(CASE WHEN status = 'offen' AND faelligkeitsdatum < DATE('now') THEN 1 ELSE 0 END) as ueberfaellige_rechnungen,
        SUM(bruttobetrag) as gesamt_forderungen,
        SUM(CASE WHEN status = 'offen' THEN bruttobetrag ELSE 0 END) as offene_forderungen,
        AVG(CASE WHEN status = 'bezahlt' THEN julianday(zahlungsdatum) - julianday(rechnungsdatum) END) as avg_zahlungsdauer
    FROM rechnungen
");
$stmt->execute();
$zahlungsanalyse = $stmt->fetch();

// Conversion Funnel
$stmt = $PDO->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'angebot' THEN 1 END) as angebote,
        COUNT(CASE WHEN status = 'bestaetigt' THEN 1 END) as bestellungen,
        AVG(CASE WHEN status = 'angebot' THEN gesamtpreis END) as avg_angebot,
        AVG(CASE WHEN status = 'bestaetigt' THEN gesamtpreis END) as avg_bestellung
    FROM bestellungen b
    WHERE 1=1 $where_clause
");
$stmt->execute($params);
$conversion = $stmt->fetch();

$conversion_rate = ($conversion['angebote'] + $conversion['bestellungen']) > 0 ? 
    ($conversion['bestellungen'] / ($conversion['angebote'] + $conversion['bestellungen'])) * 100 : 0;

// Alle Kunden für Filter-Dropdown
$stmt = $PDO->prepare("SELECT DISTINCT k.vorname, k.nachname, f.firmenname FROM kundensystem k LEFT JOIN firma f ON k.firma_id = f.id ORDER BY k.nachname");
$stmt->execute();
$alle_kunden = $stmt->fetchAll();

// Alle Produkte für Filter-Dropdown
$stmt = $PDO->prepare("SELECT DISTINCT artikel FROM angebot_positionen ORDER BY artikel");
$stmt->execute();
$alle_produkte = $stmt->fetchAll();
?>
<?php $page_title = 'ERP System'; include 'includes/header.php'; ?><div class="container">
        <div class="header">
            <h1>📊 Business Intelligence Dashboard</h1>
            <p style="color: #6c757d; margin: 0;">Erweiterte Geschäftsanalysen & Reporting</p>
        </div>

        <div class="nav-buttons">
            <a href="web_oberflaeche.php" class="btn">🏠 Hauptmenü</a>
            <a href="finanzbuchhaltung.php" class="btn">💰 Finanzbuchhaltung</a>
        </div>

        <!-- Advanced Filter Bar -->
        <div class="filter-bar">
            <form method="GET" class="filter-row">
                <div class="filter-group">
                    <label>📅 Zeitraum</label>
                    <select name="zeitraum" onchange="this.form.submit()">
                        <option value="heute" <?= $zeitraum === 'heute' ? 'selected' : '' ?>>Heute</option>
                        <option value="woche" <?= $zeitraum === 'woche' ? 'selected' : '' ?>>Diese Woche</option>
                        <option value="monat" <?= $zeitraum === 'monat' ? 'selected' : '' ?>>Dieser Monat</option>
                        <option value="quartal" <?= $zeitraum === 'quartal' ? 'selected' : '' ?>>Dieses Quartal</option>
                        <option value="jahr" <?= $zeitraum === 'jahr' ? 'selected' : '' ?>>Dieses Jahr</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>📅 Jahr</label>
                    <select name="jahr" onchange="this.form.submit()">
                        <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                            <option value="<?= $y ?>" <?= $jahr == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>📅 Monat</label>
                    <select name="monat" onchange="this.form.submit()">
                        <?php 
                        $monate = ['01'=>'Januar', '02'=>'Februar', '03'=>'März', '04'=>'April', '05'=>'Mai', '06'=>'Juni',
                                  '07'=>'Juli', '08'=>'August', '09'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Dezember'];
                        foreach($monate as $m => $name): ?>
                            <option value="<?= $m ?>" <?= $monat == $m ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>👤 Kunde</label>
                    <input type="text" name="kunde_filter" value="<?= htmlspecialchars($kunde_filter) ?>" placeholder="Kunde suchen...">
                </div>
                
                <div class="filter-group">
                    <label>📦 Produkt</label>
                    <input type="text" name="produkt_filter" value="<?= htmlspecialchars($produkt_filter) ?>" placeholder="Produkt suchen...">
                </div>
                
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="filter-btn">🔍 Filtern</button>
                </div>
            </form>
        </div>

        <!-- KPI Dashboard -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-value"><?= number_format($kpis['umsatz'] ?? 0, 0, ',', '.') ?> €</div>
                <div class="kpi-label">💰 Gesamtumsatz</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value"><?= $kpis['bestellungen'] ?? 0 ?></div>
                <div class="kpi-label">📦 Bestellungen</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value"><?= number_format($kpis['avg_order'] ?? 0, 0, ',', '.') ?> €</div>
                <div class="kpi-label">📊 Ø Bestellwert</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value"><?= $kpis['unique_customers'] ?? 0 ?></div>
                <div class="kpi-label">👥 Aktive Kunden</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value"><?= number_format($conversion_rate, 1) ?>%</div>
                <div class="kpi-label">📈 Conversion Rate</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value"><?= number_format($zahlungsanalyse['avg_zahlungsdauer'] ?? 0, 1) ?></div>
                <div class="kpi-label">⏱️ Ø Zahlungsziel (Tage)</div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('overview')">📊 Übersicht</button>
            <button class="tab" onclick="showTab('kunden')">👥 Kundenanalyse</button>
            <button class="tab" onclick="showTab('produkte')">📦 Produktanalyse</button>
            <button class="tab" onclick="showTab('finanzen')">💰 Finanzanalyse</button>
            <button class="tab" onclick="showTab('trends')">📈 Trends</button>
            <button class="tab" onclick="showTab('export')">📤 Export</button>
        </div>

        <!-- Overview Tab -->
        <div id="overview" class="tab-content active">
            <div class="dashboard-grid">
                <div class="chart-card">
                    <h3>📈 Tagesumsätze (Aktueller Monat)</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Datum</th>
                                <th>Umsatz</th>
                                <th>Bestellungen</th>
                                <th>Ø Bestellwert</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($tagesumsatz, -10) as $tag): ?>
                            <tr>
                                <td><?= date('d.m.Y', strtotime($tag['tag'])) ?></td>
                                <td><?= number_format($tag['tagesumsatz'], 2, ',', '.') ?> €</td>
                                <td><?= $tag['tagesbestellungen'] ?></td>
                                <td><?= number_format($tag['tagesbestellungen'] > 0 ? $tag['tagesumsatz'] / $tag['tagesbestellungen'] : 0, 2, ',', '.') ?> €</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="chart-card">
                    <h3>🎯 Conversion Funnel</h3>
                    <div style="padding: 20px;">
                        <div style="background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 8px;">
                            <strong>📋 Angebote erstellt:</strong> <?= $conversion['angebote'] ?><br>
                            <small>Ø Angebotswert: <?= number_format($conversion['avg_angebot'] ?? 0, 2, ',', '.') ?> €</small>
                        </div>
                        <div style="text-align: center; margin: 15px 0;">⬇️</div>
                        <div style="background: #e8f5e8; padding: 15px; margin: 10px 0; border-radius: 8px;">
                            <strong>✅ Bestellungen bestätigt:</strong> <?= $conversion['bestellungen'] ?><br>
                            <small>Ø Bestellwert: <?= number_format($conversion['avg_bestellung'] ?? 0, 2, ',', '.') ?> €</small>
                        </div>
                        <div style="text-align: center; margin: 20px 0; font-size: 1.2em; font-weight: bold; color: #8b1538;">
                            Conversion Rate: <?= number_format($conversion_rate, 1) ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kundenanalyse Tab -->
        <div id="kunden" class="tab-content">
            <div class="chart-card">
                <h3>🏆 Top 20 Kunden (ABC-Analyse)</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rang</th>
                            <th>Kunde</th>
                            <th>Firma</th>
                            <th>Umsatz</th>
                            <th>Bestellungen</th>
                            <th>Ø Bestellung</th>
                            <th>Produkte</th>
                            <th>Erste Bestellung</th>
                            <th>Letzte Bestellung</th>
                            <th>Kategorie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_umsatz = array_sum(array_column($top_kunden, 'umsatz'));
                        $cumulative = 0;
                        foreach ($top_kunden as $index => $kunde): 
                            $cumulative += $kunde['umsatz'];
                            $percentage = $total_umsatz > 0 ? ($cumulative / $total_umsatz) * 100 : 0;
                            $abc_class = $percentage <= 80 ? 'A' : ($percentage <= 95 ? 'B' : 'C');
                            $abc_style = $abc_class === 'A' ? 'abc-a' : ($abc_class === 'B' ? 'abc-b' : 'abc-c');
                        ?>
                        <tr class="<?= $abc_style ?>">
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($kunde['vorname'] . ' ' . $kunde['nachname']) ?></td>
                            <td><?= htmlspecialchars($kunde['firmenname'] ?? '-') ?></td>
                            <td><?= number_format($kunde['umsatz'], 2, ',', '.') ?> €</td>
                            <td><?= $kunde['bestellungen'] ?></td>
                            <td><?= number_format($kunde['avg_bestellung'], 2, ',', '.') ?> €</td>
                            <td><?= $kunde['verschiedene_produkte'] ?></td>
                            <td><?= date('d.m.Y', strtotime($kunde['erste_bestellung'])) ?></td>
                            <td><?= date('d.m.Y', strtotime($kunde['letzte_bestellung'])) ?></td>
                            <td><span style="font-weight: bold; color: #8b1538;"><?= $abc_class ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Produktanalyse Tab -->
        <div id="produkte" class="tab-content">
            <div class="chart-card">
                <h3>📦 Top 20 Produkte nach Umsatz</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rang</th>
                            <th>Artikel</th>
                            <th>Verkaufte Menge</th>
                            <th>Umsatz</th>
                            <th>Ø Preis</th>
                            <th>Min/Max Preis</th>
                            <th>Anzahl Kunden</th>
                            <th>Anzahl Bestellungen</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produkt_analyse as $index => $produkt): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($produkt['artikel']) ?></td>
                            <td><?= $produkt['verkaufte_menge'] ?></td>
                            <td><?= number_format($produkt['umsatz'], 2, ',', '.') ?> €</td>
                            <td><?= number_format($produkt['avg_preis'], 2, ',', '.') ?> €</td>
                            <td><?= number_format($produkt['min_preis'], 2, ',', '.') ?> € - <?= number_format($produkt['max_preis'], 2, ',', '.') ?> €</td>
                            <td><?= $produkt['anzahl_kunden'] ?></td>
                            <td><?= $produkt['anzahl_bestellungen'] ?></td>
                            <td>
                                <?php 
                                $performance = $produkt['umsatz'] / max($produkt['anzahl_bestellungen'], 1);
                                if ($performance > 1000) echo '<span class="trend-up">🔥 Top</span>';
                                elseif ($performance > 500) echo '<span class="trend-neutral">📈 Gut</span>';
                                else echo '<span class="trend-down">📉 Schwach</span>';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Finanzanalyse Tab -->
        <div id="finanzen" class="tab-content">
            <div class="dashboard-grid">
                <div class="chart-card">
                    <h3>💰 Zahlungsanalyse</h3>
                    <div style="padding: 20px;">
                        <div class="kpi-grid" style="grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0;">
                            <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 1.5em; font-weight: bold; color: #28a745;"><?= $zahlungsanalyse['bezahlte_rechnungen'] ?? 0 ?></div>
                                <div>✅ Bezahlte Rechnungen</div>
                            </div>
                            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 1.5em; font-weight: bold; color: #856404;"><?= $zahlungsanalyse['offene_rechnungen'] ?? 0 ?></div>
                                <div>⏳ Offene Rechnungen</div>
                            </div>
                            <div style="background: #f8d7da; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 1.5em; font-weight: bold; color: #721c24;"><?= $zahlungsanalyse['ueberfaellige_rechnungen'] ?? 0 ?></div>
                                <div>⚠️ Überfällige Rechnungen</div>
                            </div>
                            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 1.5em; font-weight: bold; color: #1565c0;"><?= number_format($zahlungsanalyse['avg_zahlungsdauer'] ?? 0, 1) ?></div>
                                <div>📅 Ø Zahlungsdauer (Tage)</div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 30px;">
                            <h4>💶 Forderungsübersicht</h4>
                            <p><strong>Gesamte Forderungen:</strong> <?= number_format($zahlungsanalyse['gesamt_forderungen'] ?? 0, 2, ',', '.') ?> €</p>
                            <p><strong>Offene Forderungen:</strong> <?= number_format($zahlungsanalyse['offene_forderungen'] ?? 0, 2, ',', '.') ?> €</p>
                            <p><strong>Zahlungsquote:</strong> <?= $zahlungsanalyse['gesamt_rechnungen'] > 0 ? number_format(($zahlungsanalyse['bezahlte_rechnungen'] / $zahlungsanalyse['gesamt_rechnungen']) * 100, 1) : 0 ?>%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trends Tab -->
        <div id="trends" class="tab-content">
            <div class="chart-card">
                <h3>📈 Monatlicher Vergleich (12 Monate)</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Monat</th>
                            <th>Umsatz</th>
                            <th>Bestellungen</th>
                            <th>Kunden</th>
                            <th>Ø Bestellwert</th>
                            <th>Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $prev_umsatz = 0;
                        foreach ($monatsvergleich as $index => $monat): 
                            $trend = '';
                            if ($prev_umsatz > 0) {
                                $change = (($monat['umsatz'] - $prev_umsatz) / $prev_umsatz) * 100;
                                if ($change > 5) $trend = '<span class="trend-up">📈 +' . number_format($change, 1) . '%</span>';
                                elseif ($change < -5) $trend = '<span class="trend-down">📉 ' . number_format($change, 1) . '%</span>';
                                else $trend = '<span class="trend-neutral">➡️ ' . number_format($change, 1) . '%</span>';
                            }
                            $prev_umsatz = $monat['umsatz'];
                        ?>
                        <tr>
                            <td><?= date('m/Y', strtotime($monat['monat'] . '-01')) ?></td>
                            <td><?= number_format($monat['umsatz'], 2, ',', '.') ?> €</td>
                            <td><?= $monat['bestellungen'] ?></td>
                            <td><?= $monat['kunden'] ?></td>
                            <td><?= number_format($monat['avg_bestellung'], 2, ',', '.') ?> €</td>
                            <td><?= $trend ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Export Tab -->
        <div id="export" class="tab-content">
            <div class="export-section">
                <h3>📤 Daten Export</h3>
                <p>Exportieren Sie Ihre Geschäftsdaten in verschiedenen Formaten für weitere Analysen.</p>
                
                <div class="export-buttons">
                    <button class="btn" onclick="exportData('kunden')">
                        👥 Kundendaten<br><small>CSV Export</small>
                    </button>
                    <button class="btn" onclick="exportData('produkte')">
                        📦 Produktdaten<br><small>CSV Export</small>
                    </button>
                    <button class="btn" onclick="exportData('bestellungen')">
                        📋 Bestellungen<br><small>CSV Export</small>
                    </button>
                    <button class="btn" onclick="exportData('finanzen')">
                        💰 Finanzdaten<br><small>CSV Export</small>
                    </button>
                    <button class="btn" onclick="window.print()">
                        🖨️ Dashboard<br><small>PDF Druck</small>
                    </button>
                </div>
                
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <h4>📊 Aktueller Filter:</h4>
                    <p><strong>Zeitraum:</strong> <?= ucfirst($zeitraum) ?> (<?= $jahr ?><?= $zeitraum === 'monat' ? '/' . $monat : '' ?>)</p>
                    <?php if ($kunde_filter): ?><p><strong>Kunde:</strong> <?= htmlspecialchars($kunde_filter) ?></p><?php endif; ?>
                    <?php if ($produkt_filter): ?><p><strong>Produkt:</strong> <?= htmlspecialchars($produkt_filter) ?></p><?php endif; ?>
                </div>
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
        
        function exportData(type) {
            const params = new URLSearchParams(window.location.search);
            params.set('export', type);
            window.open('export_data.php?' + params.toString(), '_blank');
        }
    </script>
<?php include 'includes/footer.php'; ?>