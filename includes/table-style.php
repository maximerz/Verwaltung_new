<style>
.modern-table {
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.modern-table .table {
    margin: 0;
}

.table tbody tr:hover {
    background: var(--bg-hover);
}

.table td .btn,
.table td .action-btn {
    margin-bottom: var(--spacing-xs);
    font-size: 12px;
    padding: var(--spacing-xs) var(--spacing-sm);
}

.table td a {
    font-weight: 500;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
}

.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    text-align: center;
    cursor: pointer;
    transition: all var(--transition);
    box-shadow: var(--shadow-sm);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: var(--color-primary);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: var(--spacing-sm);
}

.stat-label {
    color: var(--text-secondary);
    font-weight: 500;
}

.dashboard-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-xl);
    box-shadow: var(--shadow-sm);
}

.section-header {
    padding: var(--spacing-lg);
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--spacing-md);
}

.section-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.search-container {
    position: relative;
    min-width: 250px;
}

.search-input {
    padding-left: 2.5rem;
}

.search-container::before {
    content: '\f002';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    left: var(--spacing-sm);
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--radius-md);
    font-weight: 500;
    text-decoration: none;
    transition: all var(--transition);
    border: 1px solid transparent;
    cursor: pointer;
}

.btn-primary-modern {
    background: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
}

.btn-primary-modern:hover {
    background: var(--color-primary-hover);
    border-color: var(--color-primary-hover);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-success-modern {
    background: var(--color-success);
    color: white;
    border-color: var(--color-success);
}

.btn-success-modern:hover {
    background: #059669;
    border-color: #059669;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-warning-modern {
    background: var(--color-warning);
    color: white;
    border-color: var(--color-warning);
}

.btn-warning-modern:hover {
    background: #d97706;
    border-color: #d97706;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: var(--spacing-xs) var(--spacing-sm);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    border-radius: var(--radius-sm);
}

.badge-primary {
    background: var(--color-primary-light);
    color: var(--color-primary);
}

.badge-info {
    background: var(--color-info-light);
    color: var(--color-info);
}

.user-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-primary);
    color: white;
    border-radius: 50%;
    font-weight: 600;
}

.alert {
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
    border: 1px solid;
}

.alert-warning {
    background: var(--color-warning-light);
    border-color: var(--color-warning);
    color: #92400e;
}

.gap-3 {
    gap: var(--spacing-md);
}

/* Dark mode adjustments for table styles */
[data-theme="dark"] .table th {
    background: var(--bg-secondary);
    border-color: var(--border);
}

[data-theme="dark"] .table td {
    border-color: var(--border);
}

[data-theme="dark"] .dashboard-card,
[data-theme="dark"] .stat-card {
    background: var(--bg-card);
    border-color: var(--border);
}

[data-theme="dark"] .section-header {
    border-color: var(--border);
}

[data-theme="dark"] .badge-primary {
    background: rgba(37, 99, 235, 0.2);
    color: #60a5fa;
}

[data-theme="dark"] .badge-info {
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
}
</style>
