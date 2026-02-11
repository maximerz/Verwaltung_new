<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'ERP System' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #0ea5e9;
            --bg-main: #f8fafc;
            --bg-card: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.08);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        /* Navbar */
        .navbar {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav-link {
            color: var(--text-secondary) !important;
            font-weight: 500;
            padding: 0.6rem 1rem !important;
            border-radius: 10px;
            transition: all 0.2s;
            margin: 0 0.2rem;
        }
        
        .nav-link:hover {
            background: linear-gradient(135deg, rgba(99,102,241,0.1) 0%, rgba(139,92,246,0.1) 100%);
            color: var(--primary) !important;
            transform: translateY(-1px);
        }
        
        /* Container */
        .container-main {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        
        /* Cards */
        .dashboard-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            transition: all 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-lg);
            cursor: pointer;
            transition: all 0.3s;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transition: all 0.5s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(37,99,235,0.3);
        }
        
        .stat-card:hover::before {
            top: -30%;
            right: -30%;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
        }
        
        .stat-label {
            font-weight: 500;
            opacity: 0.95;
            font-size: 1.1rem;
            position: relative;
        }
        
        /* Section Header */
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
            background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Search */
        .search-container {
            width: 350px;
        }
        
        .search-input {
            width: 100%;
            padding: 0.65rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            background: white;
            transition: all 0.2s;
        }
        
        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.08);
            outline: none;
        }
        
        /* Tables */
        .modern-table {
            overflow-x: auto;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            margin: 1rem 0;
        }
        
        .table {
            margin: 0;
            background: white;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        .table thead th {
            background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1.2rem 1rem;
            white-space: nowrap;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        
        .table thead th:first-child {
            border-top-left-radius: 20px;
        }
        
        .table thead th:last-child {
            border-top-right-radius: 20px;
        }
        
        .table tbody td {
            padding: 1.1rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            background: white;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 20px;
        }
        
        .table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 20px;
        }
        
        .table tbody tr:hover {
            background: linear-gradient(to right, rgba(99,102,241,0.05) 0%, rgba(139,92,246,0.05) 100%);
            transition: all 0.3s;
        }
        
        .table tbody tr:hover td {
            background: transparent;
        }
        
        /* Buttons */
        .action-btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            margin: 0.25rem;
        }
        
        .btn-primary-modern {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
        }
        
        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99,102,241,0.4);
            color: white;
        }
        
        .btn-success-modern {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
        }
        
        .btn-success-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16,185,129,0.4);
            color: white;
        }
        
        .btn-warning-modern {
            background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(245,158,11,0.3);
        }
        
        .btn-warning-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(245,158,11,0.4);
            color: white;
        }
        
        .btn-danger-modern {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(239,68,68,0.3);
        }
        
        .btn-danger-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239,68,68,0.4);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(135deg, var(--info) 0%, #0891b2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(6,182,212,0.3);
        }
        
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(6,182,212,0.4);
            color: white;
        }
        
        /* Badges */
        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.9rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-primary {
            background: linear-gradient(135deg, rgba(99,102,241,0.15) 0%, rgba(139,92,246,0.15) 100%);
            color: var(--primary);
        }
        
        .badge-success {
            background: rgba(16,185,129,0.15);
            color: var(--success);
        }
        
        .badge-warning {
            background: rgba(245,158,11,0.15);
            color: var(--warning);
        }
        
        .badge-info {
            background: rgba(6,182,212,0.15);
            color: var(--info);
        }
        
        /* Avatar */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Forms */
        .form-control, .form-select {
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: 0.7rem 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99,102,241,0.1);
            outline: none;
        }
        
        .btn {
            border-radius: 10px;
            padding: 0.7rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99,102,241,0.4);
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
                        <a class="nav-link" href="admin_dashboard.php">
                            <i class="fas fa-shield-alt me-1"></i>Admin
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?= $_SESSION['username'] ?? 'User' ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
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
