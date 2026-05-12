<?php
session_start();
require_once 'security_helper.php';

echo "<h1>Authentication Test</h1>";

if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✓ User is logged in</p>";
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Username: " . ($_SESSION['username'] ?? 'Not set') . "</p>";
    echo "<p>Full Name: " . ($_SESSION['full_name'] ?? 'Not set') . "</p>";
    echo "<p>Role: " . ($_SESSION['role'] ?? 'Not set') . "</p>";
    echo "<p>Logged In: " . ($_SESSION['logged_in'] ?? 'false') . "</p>";
    
    echo "<h3>Security System Status:</h3>";
    if ($security === null) {
        echo "<p style='color: orange;'>⚠ Security system not available</p>";
    } else {
        echo "<p style='color: green;'>✓ Security system active</p>";
    }
    
    echo "<p><a href='dashboard/'>Go to Dashboard</a></p>";
    echo "<p><a href='auth/logout.php'>Logout</a></p>";
} else {
    echo "<p style='color: red;'>✗ User not logged in</p>";
    echo "<p><a href='auth/loging.php'>Go to Login</a></p>";
}

echo "<h3>Session Status:</h3>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";
?>
