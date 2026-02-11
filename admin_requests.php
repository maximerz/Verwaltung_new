<?php
session_start();
require_once 'db_connection.php';

// Prüfen ob Admin eingeloggt ist
if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? 'user') !== 'admin' && !($_SESSION['can_manage_users'] ?? 0))) {
    header("Location: login.php");
    exit();
}

// Anfrage bearbeiten
if ($_POST) {
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    if ($request_id && $action) {
        if ($action === 'approve') {
            // Benutzer aus Anfrage erstellen
            $stmt = $PDO->prepare("SELECT username, password FROM user_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();
            
            if ($request) {
                // Benutzer erstellen
                $stmt = $PDO->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
                $stmt->execute([$request['username'], $request['password']]);
                
                // Anfrage löschen
                $stmt = $PDO->prepare("DELETE FROM user_requests WHERE id = ?");
                $stmt->execute([$request_id]);
                
                $message = "Benutzer erfolgreich genehmigt!";
            }
        } elseif ($action === 'reject') {
            // Anfrage ablehnen
            $stmt = $PDO->prepare("DELETE FROM user_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            
            $message = "Anfrage abgelehnt!";
        }
    }
}

// Alle offenen Anfragen laden
$stmt = $PDO->prepare("SELECT * FROM user_requests WHERE status = 'pending' ORDER BY created_at DESC");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzeranfragen - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #8b1538 0%, #a91b47 100%);
            min-height: 100vh;
            padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container-main {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .header-title {
            background: linear-gradient(135deg, #8b1538 0%, #a91b47 100%);
            color: white;
            padding: 40px;
            border-radius: 15px 15px 0 0;
            margin: -30px -30px 30px -30px;
        }
        .table thead {
            background: linear-gradient(135deg, #8b1538 0%, #a91b47 100%);
            color: white;
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #e91e63 100%);
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="container-main">
            <div class="header-title">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-users-cog"></i> Benutzeranfragen</h1>
                        <p class="mb-0">Verwaltung von Registrierungsanfragen</p>
                    </div>
                    <div>
                        <a href="web_oberflaeche.php" class="btn btn-light">
                            <i class="fas fa-arrow-left"></i> Zurück
                        </a>
                    </div>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Benutzername</th>
                            <th><i class="fas fa-calendar"></i> Angefragt am</th>
                            <th><i class="fas fa-tools"></i> Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($requests) > 0): ?>
                            <?php foreach($requests as $request): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($request['username']); ?></strong></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm me-2">
                                                <i class="fas fa-check"></i> Genehmigen
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Anfrage wirklich ablehnen?')">
                                                <i class="fas fa-times"></i> Ablehnen
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox"></i> Keine offenen Anfragen
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>