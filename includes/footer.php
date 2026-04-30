    </div>
    
    <!-- Theme Script - Applies saved preference -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', nextTheme);
            localStorage.setItem('theme', nextTheme);
            const icon = document.getElementById('themeToggleIcon');
            if (icon) {
                icon.className = nextTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }

        function applyResponsiveTableLabels() {
            const tables = document.querySelectorAll('.modern-table .table, .table-responsive .table');
            tables.forEach((table) => {
                const headers = Array.from(table.querySelectorAll('thead th')).map((th) => th.textContent.replace(/\s+/g, ' ').trim());
                if (!headers.length) {
                    return;
                }

                table.classList.add('table-mobile-stack');
                table.querySelectorAll('tbody tr').forEach((row) => {
                    Array.from(row.children).forEach((cell, index) => {
                        if (!cell.hasAttribute('data-label')) {
                            cell.setAttribute('data-label', headers[index] || 'Wert');
                        }
                    });
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const icon = document.getElementById('themeToggleIcon');
            if (icon) {
                const activeTheme = document.documentElement.getAttribute('data-theme') || 'light';
                icon.className = activeTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }

            applyResponsiveTableLabels();
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
