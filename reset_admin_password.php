<?php
/**
 * Reset Admin Password Script
 * Use this to reset the admin password if needed
 * Run: http://localhost/cims-app/reset_admin_password.php
 */

require_once 'config/db.php';

// Default admin credentials
$username = 'admin';
$newPassword = 'admin123'; // Change this to your desired password

// Hash the new password
$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    // Check if admin user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Update existing admin password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $user['id']]);
        echo "<h2>✅ Admin password updated successfully!</h2>";
        echo "<p>Username: <strong>$username</strong></p>";
        echo "<p>New Password: <strong>$newPassword</strong></p>";
        echo "<p><a href='auth/loging.php'>Go to Login</a></p>";
    } else {
        // Create new admin user
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $passwordHash]);
        echo "<h2>✅ Admin user created successfully!</h2>";
        echo "<p>Username: <strong>$username</strong></p>";
        echo "<p>Password: <strong>$newPassword</strong></p>";
        echo "<p><a href='auth/loging.php'>Go to Login</a></p>";
    }
} catch (PDOException $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
