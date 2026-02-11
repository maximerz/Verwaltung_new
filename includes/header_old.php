<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ERP System' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css?v=2">
    <style>
    </style>
    <style>
        :root {
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --light: #f8fafc;
            --border: #e2e8f0;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #F9FAFB;
            min-height: 100vh;
        }
        
        .navbar {
            background: white; /* light mode */
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary) !important;
        }
        
        .nav-link {
            color: var(--secondary) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .nav-link:hover {
            background: var(--light); /* light mode */
            color: var(--primary) !important;
        }
        
        .container-main {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .dashboard-card {
            background: white; /* light mode */
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white; /* light mode */
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--secondary);
            font-weight: 500;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }
        
        .search-container {
            position: relative;
            min-width: 300px;
        }
        
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            pointer-events: none;
            z-index: 10;
        }
        
        .search-input {
            padding-left: 3rem;
            border-radius: 10px;
            border: 2px solid var(--border);
        }
        
        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .modern-table {
            overflow-x: auto;
        }
        
        .table {
            margin: 0;
        }
        
        .table thead th {
            background: var(--light); /* light mode */
            font-weight: 600;
            border: none;
            padding: 1rem;
            white-space: nowrap;
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border);
        }
        
        .table tbody tr:hover {
            background: var(--light); /* light mode */
        }
        
        
        .action-btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            margin: 0.25rem;
        }
        
        .btn-primary-modern {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary-modern:hover {
            color: white;
        }
        
        .btn-success-modern {
            background: var(--success);
            color: white;
        }
        
        .btn-success-modern:hover {
            background: #059669;
            color: white;
        }
        
        .btn-warning-modern {
            background: var(--warning);
            color: white;
        }
        
        .btn-warning-modern:hover {
            background: #d97706;
            color: white;
        }
        
        .btn-danger-modern {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger-modern:hover {
            background: #dc2626;
            color: white;
        }
        
        .btn-info {
            background: var(--info);
            color: white;
        }
        
        .btn-info:hover {
            background: #0891b2;
            color: white;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .badge-primary {
            background: #dbeafe;
            color: var(--primary);
        }
        
        .badge-success {
            background: #d1fae5;
            color: var(--success);
        }
        
        .badge-warning {
            background: #fef3c7;
            color: var(--warning);
        }
        
        .badge-info {
            background: #cffafe;
            color: var(--info);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .form-control, .form-select {
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 0.625rem 0.875rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn {
            border-radius: 8px;
            padding: 0.625rem 1.25rem;
            font-weight: 500;
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
        }
        
        .btn-primary:hover {
        }
        
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="web_oberflaeche.php">
                <i class="fas fa-chart-line me-2"></i>ERP System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="web_oberflaeche.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kunden_verwaltung.php">
                            <i class="fas fa-users me-1"></i>Kunden
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="produktkatalog.php">
                            <i class="fas fa-box me-1"></i>Produkte
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lagerverwaltung.php">
                            <i class="fas fa-warehouse me-1"></i>Lager
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lieferanten.php">
                            <i class="fas fa-truck me-1"></i>Lieferanten
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="finanzbuchhaltung.php">
                            <i class="fas fa-euro-sign me-1"></i>Finanzen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reporting.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="user_management.php">
                            <i class="fas fa-users-cog me-1"></i>Admin
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?= $_SESSION['username'] ?? 'User' ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <!-- <li><a class="dropdown-item" href="user_settings.php"><i class="fas fa-cog me-2"></i>Einstellungen</a></li>
                            <li><hr class="dropdown-divider"></li> -->
                            <li><a class="dropdown-item" href="impressum.php"><i class="fas fa-info-circle me-2"></i>Impressum</a></li>
                            <li><a class="dropdown-item" href="datenschutz.php"><i class="fas fa-shield-alt me-2"></i>Datenschutz</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-main">
