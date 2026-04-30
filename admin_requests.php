<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? 'user') !== 'admin' && !($_SESSION['can_manage_users'] ?? 0))) {
    header("Location: login.php");
    exit();
}

if ($_POST) {
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($request_id && $action) {
        if ($action === 'approve') {
            $stmt = $PDO->prepare("SELECT username, password FROM user_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();

            if ($request) {
                $stmt = $PDO->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
                $stmt->execute([$request['username'], $request['password']]);

                $stmt = $PDO->prepare("DELETE FROM user_requests WHERE id = ?");
                $stmt->execute([$request_id]);

                $message = "Benutzer erfolgreich genehmigt.";
            }
        } elseif ($action === 'reject') {
            $stmt = $PDO->prepare("DELETE FROM user_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $message = "Anfrage abgelehnt.";
        }
    }
}

$stmt = $PDO->prepare("SELECT * FROM user_requests WHERE status = 'pending' ORDER BY created_at DESC");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Benutzeranfragen - Projekt1 ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <script>
        document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
    </script>
</head>
<body class="admin-standalone">
    <div class="admin-shell">
        <div class="surface-card p-4 p-md-5 slide-up">
            <div class="admin-topbar">
                <div>
                    <span class="page-kicker"><i class="fas fa-user-cog"></i>Admin Inbox</span>
                    <h1 class="mt-3 mb-2">Benutzeranfragen</h1>
                    <p>Offene Registrierungsanfragen zentral prüfen, genehmigen oder ablehnen.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="web_oberflaeche.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i>Zurück</a>
                    <button type="button" class="theme-toggle" onclick="toggleAdminTheme()">
                        <i class="fas fa-moon" id="adminThemeIcon"></i>
                        <span>Theme</span>
                    </button>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-success mb-4"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="modern-table">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user me-2"></i>Benutzername</th>
                            <th><i class="fas fa-calendar me-2"></i>Angefragt am</th>
                            <th><i class="fas fa-sliders me-2"></i>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($requests) > 0): ?>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="user-avatar"><?= strtoupper(substr($request['username'], 0, 1)) ?></span>
                                            <strong><?= htmlspecialchars($request['username']) ?></strong>
                                        </div>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($request['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" class="d-flex gap-2 flex-wrap">
                                            <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm">
                                                <i class="fas fa-check"></i>Genehmigen
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Anfrage wirklich ablehnen?')">
                                                <i class="fas fa-xmark"></i>Ablehnen
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p class="mb-0">Keine offenen Anfragen vorhanden.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleAdminTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', nextTheme);
            localStorage.setItem('theme', nextTheme);
            syncThemeIcon();
        }

        function syncThemeIcon() {
            const icon = document.getElementById('adminThemeIcon');
            if (icon) {
                icon.className = document.documentElement.getAttribute('data-theme') === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }

        document.addEventListener('DOMContentLoaded', syncThemeIcon);
    </script>
</body>
</html>
