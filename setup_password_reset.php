<?php
/**
 * Setup Password Reset Functionality
 * Adds reset_token and reset_token_expiry columns to users table
 */

require_once __DIR__ . '/config/db.php';

echo "<h2>Setting up Password Reset Functionality</h2>";

try {
    // Check if columns already exist
    $checkQuery = "SHOW COLUMNS FROM users LIKE 'reset_token'";
    $stmt = $conn->query($checkQuery);
    
    if ($stmt->rowCount() > 0) {
        echo "<div class='alert alert-info'>✓ Password reset columns already exist</div>";
    } else {
        // Add reset_token column
        $alterQuery1 = "ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL";
        $conn->exec($alterQuery1);
        echo "<div class='alert alert-success'>✓ Added reset_token column</div>";
        
        // Add reset_token_expiry column
        $alterQuery2 = "ALTER TABLE users ADD COLUMN reset_token_expiry DATETIME NULL";
        $conn->exec($alterQuery2);
        echo "<div class='alert alert-success'>✓ Added reset_token_expiry column</div>";
        
        // Add password_changed_at column (if not exists)
        $checkPasswordChanged = "SHOW COLUMNS FROM users LIKE 'password_changed_at'";
        $stmt2 = $conn->query($checkPasswordChanged);
        
        if ($stmt2->rowCount() == 0) {
            $alterQuery3 = "ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL";
            $conn->exec($alterQuery3);
            echo "<div class='alert alert-success'>✓ Added password_changed_at column</div>";
        } else {
            echo "<div class='alert alert-info'>✓ password_changed_at column already exists</div>";
        }
    }
    
    echo "<h3>✅ Password Reset Setup Complete!</h3>";
    echo "<p>You can now use the forgot password functionality:</p>";
    echo "<ul>";
    echo "<li><a href='" . BASE_URL . "/auth/loging.php'>Login Page</a> - Click 'Forgot Password'</li>";
    echo "<li><a href='" . BASE_URL . "/auth/forgot_password.php'>Forgot Password Page</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>

<style>
.alert {
    padding: 15px;
    margin: 10px 0;
    border-radius: 5px;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
}
</style>
