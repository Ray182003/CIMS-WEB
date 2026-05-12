<?php
/**
 * Security Helper - Authorization and Access Control
 * Include this file in pages that require authentication and authorization
 */

if (!function_exists('ensureSessionStarted')) {
    /**
     * Safely start the session when headers are still available.
     */
    function ensureSessionStarted(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        if (headers_sent($sentFile, $sentLine)) {
            error_log("Unable to start session because headers were already sent in {$sentFile}:{$sentLine}");
            return false;
        }

        return session_start();
    }
}

ensureSessionStarted();

// Include database configuration
require_once __DIR__ . '/config/db.php';

// Initialize security system
$security = null;
try {
    require_once __DIR__ . '/auth/AaaSecurity.php';
    if (isset($conn) && $conn instanceof PDO) {
        $security = new AaaSecurity($conn);
    }
} catch (Exception $e) {
    error_log("Security system unavailable: " . $e->getMessage());
    $security = null;
}

/**
 * Check if user is authenticated and has valid session
 */
function requireAuth() {
    ensureSessionStarted();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/auth/loging.php');
        exit();
    }
    return true;
}

/**
 * Check if user has specific permission
 */
function requirePermission($permission) {
    // First ensure user is authenticated
    requireAuth();
    
    // If security system is not available, use fallback for admin users
    global $security;
    if (!$security) {
        // Fallback: allow access if user is admin
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            return true;
        }
        // For non-admin users, deny access to protected areas
        header('Location: ' . BASE_URL . '/dashboard/');
        exit();
    }
    
    // Check permission using security system
    if (!$security->checkPermission($permission)) {
        // If permission check fails, check if user is admin
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            return true; // Admins have access to everything
        }
        header('Location: ' . BASE_URL . '/dashboard/');
        exit();
    }
    return true;
}

/**
 * Get current user information
 */
function getCurrentUser() {
    global $conn;
    
    ensureSessionStarted();
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $query = "SELECT id, username FROM users WHERE id = :user_id LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Check if current user is admin
 */
function isAdmin() {
    ensureSessionStarted();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if current user is staff or higher
 */
function isStaff() {
    ensureSessionStarted();
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'staff']);
}

/**
 * Get user's full name for display
 */
function getUserDisplayName() {
    ensureSessionStarted();
    return isset($_SESSION['full_name']) ? $_SESSION['full_name'] : $_SESSION['username'];
}

/**
 * Get user's role for display
 */
function getUserRole() {
    ensureSessionStarted();
    return isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Unknown';
}

/**
 * Log user activity (convenience function)
 */
function logActivity($action, $module, $recordId = null, $oldValues = null, $newValues = null) {
    global $security;
    ensureSessionStarted();

    if ($security && ($action === 'LOGIN_FAILED' || isset($_SESSION['user_id']))) {
        try {
            // For failed login, use null user_id, otherwise use session user_id
            $userId = ($action === 'LOGIN_FAILED') ? null : $_SESSION['user_id'];
            $security->logAudit($userId, $action, $module, $recordId, $oldValues, $newValues);
        } catch (Exception $e) {
            // Fallback: log to error log if audit system fails
            $userId = $_SESSION['user_id'] ?? 'unknown';
            error_log("Activity log failed: $action on $module by user $userId - " . $e->getMessage());
        }
    } else {
        // Fallback: log to error log if security system not available
        $userId = $_SESSION['user_id'] ?? 'unknown';
        error_log("Activity: $action on $module by user $userId");
    }
}
?>
