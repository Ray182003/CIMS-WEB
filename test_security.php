<?php
// Test script to verify security system is working
session_start();
require_once 'config/db.php';
require_once 'security_helper.php';

echo "<h1>Security System Test</h1>";

if ($security === null) {
    echo "<p style='color: red;'>Security system is not available</p>";
} else {
    echo "<p style='color: green;'>Security system is loaded successfully!</p>";
    
    // Test basic methods
    echo "<p>Testing CSRF generation...</p>";
    $csrfToken = $security->generateCSRF();
    echo "<p>CSRF Token: " . substr($csrfToken, 0, 10) . "...</p>";
    
    echo "<p>Testing input sanitization...</p>";
    $cleanInput = $security->sanitizeInput("<script>alert('xss')</script>");
    echo "<p>Sanitized input: " . htmlspecialchars($cleanInput) . "</p>";
}

echo "<p><a href='dashboard/'>Go to Dashboard</a></p>";
?>
