<?php

/**
 * Fix Password Reset Database Columns
 */

require_once __DIR__ . '/config/db.php';

echo "<h2>Fixing Password Reset Database...</h2>";

try {
    // Check if reset_token column exists
    $checkToken = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
    if ($checkToken->rowCount() == 0) {
        echo "<p>Adding reset_token column...</p>";
        $conn->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL");
        echo "<p>✅ reset_token column added</p>";
    } else {
        echo "<p>✅ reset_token column already exists</p>";
    }

    // Check if reset_token_expiry column exists
    $checkExpiry = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token_expiry'");
    if ($checkExpiry->rowCount() == 0) {
        echo "<p>Adding reset_token_expiry column...</p>";
        $conn->exec("ALTER TABLE users ADD COLUMN reset_token_expiry DATETIME NULL");
        echo "<p>✅ reset_token_expiry column added</p>";
    } else {
        echo "<p>✅ reset_token_expiry column already exists</p>";
    }

    // Check if password_changed_at column exists
    $checkChanged = $conn->query("SHOW COLUMNS FROM users LIKE 'password_changed_at'");
    if ($checkChanged->rowCount() == 0) {
        echo "<p>Adding password_changed_at column...</p>";
        $conn->exec("ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL");
        echo "<p>✅ password_changed_at column added</p>";
    } else {
        echo "<p>✅ password_changed_at column already exists</p>";
    }

    echo "<h3>✅ Password reset database is ready!</h3>";
    echo "<p><a href='auth/forgot_password_debug.php'>Test Forgot Password</a></p>";
} catch (PDOException $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
