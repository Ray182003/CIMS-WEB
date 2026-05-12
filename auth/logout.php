<?php
session_start();
require "../config/db.php";
require_once __DIR__ . "/../security_helper.php";

// Initialize security helper for audit logging
global $security;
$security = new AaaSecurity($conn);

// Log logout before destroying session
if (isset($_SESSION['user_id'])) {
    logActivity('LOGOUT', 'auth', $_SESSION['user_id'], null, [
        'username' => $_SESSION['username'] ?? 'unknown',
        'role' => $_SESSION['role'] ?? 'unknown',
        'logout_time' => date('Y-m-d H:i:s'),
        'session_duration' => isset($_SESSION['login_time']) ? time() - $_SESSION['login_time'] : null
    ]);
}

session_unset();
session_destroy();
echo "<script>window.location.href = '" . BASE_URL . "/auth/loging.php';</script>";
exit();
