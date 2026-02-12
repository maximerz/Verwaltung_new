<?php
session_start();
require_once 'db_connection.php';
require_once 'includes/audit_log.php';

// Audit-Log: Logout
if (isset($_SESSION['user_id'])) {
    audit_log($PDO, 'LOGOUT', 'users', $_SESSION['user_id'], null, ['username' => $_SESSION['username'] ?? 'unknown']);
}

session_destroy();
header("Location: login.php");
exit();
?>