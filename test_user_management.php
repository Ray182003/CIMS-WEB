<?php
/**
 * Test User Management Functionality
 */

// Include required files
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/security_helper.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Error: User not logged in. Please login first.\n";
    echo "Login URL: " . BASE_URL . "/auth/loging.php\n";
    exit;
}

echo "=== User Management Test ===\n\n";

// Check current user info
echo "Current User:\n";
echo "- User ID: " . $_SESSION['user_id'] . "\n";
echo "- Username: " . $_SESSION['username'] . "\n";
echo "- Role: " . $_SESSION['role'] . "\n";
echo "- Full Name: " . $_SESSION['full_name'] . "\n\n";

// Check security system
global $security;
if ($security) {
    echo "✓ Security system is loaded\n";
    
    // Test permissions
    $permissions = ['users_view', 'users_add', 'users_edit', 'users_delete'];
    foreach ($permissions as $perm) {
        $hasPerm = $security->hasPermission($_SESSION['user_id'], $perm);
        echo "- $perm: " . ($hasPerm ? "✓" : "✗") . "\n";
    }
} else {
    echo "⚠ Security system not available (using fallback)\n";
    echo "- Admin fallback: " . (($_SESSION['role'] === 'admin') ? "✓" : "✗") . "\n";
}

// Test database connection
echo "\n=== Database Test ===\n";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Database connection OK\n";
    echo "Total users: " . $result['total'] . "\n";
    
    // Check if user can create new users
    if ($_SESSION['role'] === 'admin' || ($security && $security->hasPermission($_SESSION['user_id'], 'users_add'))) {
        echo "✓ User can create new accounts\n";
        
        // Show available roles
        echo "\nAvailable Roles:\n";
        echo "- admin (Full access)\n";
        echo "- staff (Limited access)\n";
        echo "- viewer (Read-only access)\n";
        
        echo "\nTo create a new user:\n";
        echo "1. Go to: " . BASE_URL . "/users/\n";
        echo "2. Click 'Add User' button\n";
        echo "3. Fill in user details\n";
        echo "4. Select role (admin or staff)\n";
        echo "5. Click 'Add User'\n";
    } else {
        echo "✗ User cannot create new accounts\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
