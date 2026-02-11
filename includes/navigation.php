<?php
// Session optional starten falls noch nicht gestartet
if (session_status() === PHP_SESSION_NONE) {
    try {
        session_start();
    } catch (Exception $e) {
        // Session-Fehler ignorieren
    }
}

$logged_in = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'Gast';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="top-nav">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="nav-brand">
                <i class="fas fa-chart-line me-2"></i>ERP Dashboard
            </div>
            
            <ul class="nav nav-pills flex-nowrap">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'web_oberflaeche.php' ? 'active' : '' ?>" href="web_oberflaeche.php">
                        <i class="fas fa-home me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'kunden_verwaltung.php' ? 'active' : '' ?>" href="kunden_verwaltung.php">
                        <i class="fas fa-users me-1"></i>Kunden
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'produktkatalog.php' ? 'active' : '' ?>" href="produktkatalog.php">
                        <i class="fas fa-box me-1"></i>Produkte
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'lagerverwaltung.php' ? 'active' : '' ?>" href="lagerverwaltung.php">
                        <i class="fas fa-warehouse me-1"></i>Lager
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'lieferanten.php' ? 'active' : '' ?>" href="lieferanten.php">
                        <i class="fas fa-truck me-1"></i>Lieferanten
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'finanzbuchhaltung.php' ? 'active' : '' ?>" href="finanzbuchhaltung.php">
                        <i class="fas fa-calculator me-1"></i>Finanzen
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'reporting.php' ? 'active' : '' ?>" href="reporting.php">
                        <i class="fas fa-chart-bar me-1"></i>Reports
                    </a>
                </li>
                <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'user_management.php' ? 'active' : '' ?>" href="user_management.php">
                        <i class="fas fa-user-cog me-1"></i>Admin
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="d-flex align-items-center gap-3">
                <div class="user-avatar">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>
                <div class="dropdown">
                        <?= htmlspecialchars($username) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="user_settings.php"><i class="fas fa-cog me-2"></i>Einstellungen</a></li>
                        <li><a class="dropdown-item" href="datenschutz.php"><i class="fas fa-shield-alt me-2"></i>Datenschutz</a></li>
                        <li><a class="dropdown-item" href="impressum.php"><i class="fas fa-info-circle me-2"></i>Impressum</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ($logged_in): ?>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="login.php"><i class="fas fa-sign-in-alt me-2"></i>Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
