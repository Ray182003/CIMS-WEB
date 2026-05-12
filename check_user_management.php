<!DOCTYPE html>
<html>
<head>
    <title>User Management Check</title>
    <link href="<?php echo BASE_URL; ?>/public/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>User Management Status Check</h2>
        
        <?php
        require_once __DIR__ . '/config/db.php';
        require_once __DIR__ . '/security_helper.php';
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            echo '<div class="alert alert-warning">';
            echo '<h5>Not Logged In</h5>';
            echo '<p>Please <a href="' . BASE_URL . '/auth/loging.php">login here</a> first.</p>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-success">';
            echo '<h5>✓ Logged In</h5>';
            echo '<ul>';
            echo '<li><strong>User ID:</strong> ' . $_SESSION['user_id'] . '</li>';
            echo '<li><strong>Username:</strong> ' . $_SESSION['username'] . '</li>';
            echo '<li><strong>Role:</strong> ' . $_SESSION['role'] . '</li>';
            echo '<li><strong>Full Name:</strong> ' . $_SESSION['full_name'] . '</li>';
            echo '</ul>';
            echo '</div>';
            
            // Check permissions
            global $security;
            if ($security) {
                echo '<div class="alert alert-info">';
                echo '<h5>Security System Status: Active</h5>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-warning">';
                echo '<h5>Security System Status: Using Admin Fallback</h5>';
                echo '</div>';
            }
            
            // Check if user can create accounts
            if ($_SESSION['role'] === 'admin') {
                echo '<div class="alert alert-success">';
                echo '<h5>✓ Can Create User Accounts</h5>';
                echo '<p>As an admin, you can create new admin and staff accounts.</p>';
                echo '<a href="' . BASE_URL . '/users/" class="btn btn-primary">Go to User Management</a>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-danger">';
                echo '<h5>✗ Cannot Create User Accounts</h5>';
                echo '<p>Only admin users can create new accounts.</p>';
                echo '</div>';
            }
        }
        
        // Check database
        try {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<div class="alert alert-info">';
            echo '<h5>Database Status: OK</h5>';
            echo '<p>Total users in database: ' . $result['total'] . '</p>';
            echo '</div>';
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">';
            echo '<h5>Database Error</h5>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '</div>';
        }
        ?>
        
        <div class="mt-4">
            <h5>How to Create Admin & Staff Accounts:</h5>
            <ol>
                <li>Login as admin user</li>
                <li>Go to <a href="<?php echo BASE_URL; ?>/users/">User Management</a></li>
                <li>Click "Add User" button</li>
                <li>Fill in user details</li>
                <li>Select role: "Admin" or "Staff"</li>
                <li>Click "Add User"</li>
            </ol>
        </div>
    </div>
</body>
</html>
