<?php
session_start();
require_once 'db_connection.php';

$error = '';
$success = '';

if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Benutzername und Passwort sind erforderlich.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwörter stimmen nicht überein.';
    } elseif (strlen($password) < 4) {
        $error = 'Passwort muss mindestens 4 Zeichen lang sein.';
    } else {
        try {
            // Prüfen ob Benutzer bereits existiert
            $stmt = $PDO->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $error = 'Benutzername bereits vergeben.';
            } else {
                // Registrierungsanfrage erstellen
                $stmt = $PDO->prepare("INSERT INTO user_requests (username, password) VALUES (?, ?)");
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                
                $success = 'Registrierungsanfrage gesendet! Warten Sie auf die Freigabe durch den Administrator.';
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $error = 'Benutzername bereits angefragt oder vorhanden.';
            } else {
                $error = 'Fehler beim Senden der Anfrage: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzer Registrierung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #8b1538 0%, #a91b47 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            max-width: 450px;
            width: 100%;
            margin: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #8b1538 0%, #a91b47 100%);
            color: white;
            text-align: center;
            padding: 40px 30px;
            margin: 0;
        }
        .register-header h2 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 700;
        }
        .register-body {
            padding: 40px 30px;
        }
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 1rem;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #8b1538;
            box-shadow: 0 0 0 0.2rem rgba(139, 21, 56, 0.25);
        }
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #8b1538;
            z-index: 10;
        }
        .input-group .form-control {
            padding-left: 50px;
        }
        .btn-register {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #8b1538 0%, #a91b47 100%);
            color: white;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 21, 56, 0.4);
        }
        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 20px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="d-flex align-items-center justify-content-center mb-3">
                <div>
                    <h2><i class="fas fa-user-plus"></i> Registrierung</h2>
                </div>
            </div>
            <div class="subtitle">Neuen Benutzer erstellen</div>
        </div>
        
        <div class="register-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" class="form-control" name="username" placeholder="Benutzername eingeben" required>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" name="password" placeholder="Passwort eingeben" required>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Passwort bestätigen" required>
                </div>
                
                <button type="submit" class="btn btn-register">
                    <i class="fas fa-user-plus"></i> Registrieren
                </button>
            </form>
            
            <div class="login-link">
                <a href="login.php" class="text-decoration-none" style="color: #8b1538;">
                    <i class="fas fa-arrow-left"></i> Zurück zum Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>