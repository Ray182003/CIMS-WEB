<?php
require_once __DIR__ . '/config/db.php';

echo "Adding password reset columns to users table...\n";

try {
    // Check if reset_token column exists
    $check = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
    if ($check->rowCount() == 0) {
        // Add reset_token column
        $conn->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL");
        echo "✓ Added reset_token column\n";
    } else {
        echo "✓ reset_token column already exists\n";
    }
    
    // Check if reset_token_expiry column exists
    $check = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token_expiry'");
    if ($check->rowCount() == 0) {
        // Add reset_token_expiry column
        $conn->exec("ALTER TABLE users ADD COLUMN reset_token_expiry DATETIME NULL");
        echo "✓ Added reset_token_expiry column\n";
    } else {
        echo "✓ reset_token_expiry column already exists\n";
    }
    
    // Check if password_changed_at column exists
    $check = $conn->query("SHOW COLUMNS FROM users LIKE 'password_changed_at'");
    if ($check->rowCount() == 0) {
        // Add password_changed_at column
        $conn->exec("ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL");
        echo "✓ Added password_changed_at column\n";
    } else {
        echo "✓ password_changed_at column already exists\n";
    }
    
    echo "\n✅ Password reset columns setup complete!\n";
    echo "You can now use the forgot password functionality.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
