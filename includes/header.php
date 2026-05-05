<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = $page_title ?? 'ERP System';
$username = $_SESSION['username'] ?? 'Gast';
$user_role = $_SESSION['role'] ?? 'user';
$logged_in = isset($_SESSION['user_id']);

$navigation_items = [
    ['file' => 'web_oberflaeche.php', 'label' => 'Dashboard', 'icon' => 'fa-compass'],
    ['file' => 'kunden_verwaltung.php', 'label' => 'Kunden', 'icon' => 'fa-users'],
    ['file' => 'produktkatalog.php', 'label' => 'Produkte', 'icon' => 'fa-box-open'],
    ['file' => 'lagerverwaltung.php', 'label' => 'Lager', 'icon' => 'fa-warehouse'],
    ['file' => 'lieferanten.php', 'label' => 'Lieferanten', 'icon' => 'fa-truck'],
    ['file' => 'finanzbuchhaltung.php', 'label' => 'Finanzen', 'icon' => 'fa-chart-pie'],
    ['file' => 'reporting.php', 'label' => 'Reports', 'icon' => 'fa-chart-column'],
];

if ($user_role === 'admin' || !empty($_SESSION['can_manage_users'])) {
    $navigation_items[] = ['file' => 'user_management.php', 'label' => 'Admin', 'icon' => 'fa-shield-halved'];
}
if ($user_role === 'admin' || !empty($_SESSION['can_manage_users']) || $user_role === 'template_editor') {
    $navigation_items[] = ['file' => 'document_templates.php', 'label' => 'Vorlagen', 'icon' => 'fa-file-signature'];
}

$kicker = $user_role === 'admin' ? 'Administrationsbereich' : 'Operatives System';
$aside_label = $logged_in ? 'Aktive Sitzung' : 'Zugang';
$aside_value = $logged_in ? $username : 'Nicht angemeldet';
?>
<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#14b8a6">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/assets/images/logo.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="includes/navigation.css">
    <style>
        [data-theme="light"] .app-brand-mark {
            background: linear-gradient(135deg, #14b8a6, #0d9488) !important;
        }
        [data-theme="light"] svg, [data-theme="light"] img {
            filter: none !important;
        }
    </style>
    <script>
        document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
    </script>
</head>
<body>
    <div class="app-nav-shell">
        <nav class="app-nav navbar navbar-expand-lg">
            <div class="container-fluid px-0">
                <a class="app-brand navbar-brand" href="web_oberflaeche.php">
                    <span class="app-brand-mark"><i class="fas fa-layer-group"></i></span>
                    <span class="app-brand-copy">
                        <strong>Projekt1 ERP</strong>
                        <span>Operations Console</span>
                    </span>
                </a>

                <button class="app-nav-toggle navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primaryNav" aria-controls="primaryNav" aria-expanded="false" aria-label="Navigation umschalten">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="primaryNav">
                    <div class="app-nav-links navbar-nav mx-auto mt-3 mt-lg-0">
                        <?php foreach ($navigation_items as $item): ?>
                            <a class="nav-link <?= $current_page === $item['file'] ? 'active' : '' ?>" href="<?= htmlspecialchars($item['file']) ?>">
                                <i class="fas <?= htmlspecialchars($item['icon']) ?>"></i>
                                <span><?= htmlspecialchars($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="app-toolbar">
                        <button type="button" class="theme-toggle" onclick="toggleTheme()">
                            <i class="fas fa-moon" id="themeToggleIcon"></i>
                            <span>Theme</span>
                        </button>

                        <?php if ($logged_in): ?>
                            <div class="dropdown">
                                <button class="app-user-chip dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="user-avatar"><?= strtoupper(substr($username, 0, 1)) ?></span>
                                    <span><?= htmlspecialchars($username) ?></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="user_settings.php"><i class="fas fa-sliders me-2"></i>Einstellungen</a></li>
                                    <li><a class="dropdown-item" href="impressum.php"><i class="fas fa-circle-info me-2"></i>Impressum</a></li>
                                    <li><a class="dropdown-item" href="datenschutz.php"><i class="fas fa-user-shield me-2"></i>Datenschutz</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-right-from-bracket me-2"></i>Logout</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a class="quick-link-chip" href="login.php">
                                <i class="fas fa-right-to-bracket"></i>
                                <span>Login</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <div class="container-main fade-in">
        <section class="page-hero">
            <div class="page-hero-main">
                <span class="page-kicker">
                    <i class="fas fa-bolt"></i>
                    <?= htmlspecialchars($kicker) ?>
                </span>
                <h1><?= htmlspecialchars($page_title) ?></h1>
                <p>Überarbeitete Oberfläche mit klarerer Navigation, stärkerem Kontrast und einem konsistenten Komponenten-Design für das gesamte Projekt.</p>
            </div>
            <aside class="page-hero-aside">
                <div>
                    <span class="page-hero-aside-label"><?= htmlspecialchars($aside_label) ?></span>
                    <strong><?= htmlspecialchars($aside_value) ?></strong>
                </div>
                <div class="page-hero-meta">
                    <a class="quick-link-chip" href="web_oberflaeche.php">
                        <i class="fas fa-house"></i>
                        <span>Start</span>
                    </a>
                    <?php if ($logged_in): ?>
                        <a class="quick-link-chip" href="user_settings.php">
                            <i class="fas fa-gear"></i>
                            <span>Profil</span>
                        </a>
                    <?php else: ?>
                        <a class="quick-link-chip" href="register.php">
                            <i class="fas fa-user-plus"></i>
                            <span>Registrieren</span>
                        </a>
                    <?php endif; ?>
                </div>
            </aside>
        </section>
<?php /* footer.php closes the remaining wrappers and loads scripts */ ?>
