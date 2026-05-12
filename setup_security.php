<?php
/**
 * Setup Security Tables Script
 * Run this to create the required security tables
 */

require_once 'config/db.php';

echo "<h1>Security Tables Setup</h1>";

// SQL queries to create tables
$sql_queries = [
    // Create permissions table
    "CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        permission_name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Create user_permissions table
    "CREATE TABLE IF NOT EXISTS user_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        permission_id INT NOT NULL,
        granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        granted_by INT DEFAULT NULL,
        UNIQUE KEY unique_user_permission (user_id, permission_id),
        INDEX idx_user_id (user_id),
        INDEX idx_permission_id (permission_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

// Default permissions
$default_permissions = [
    'users_view' => 'View users list',
    'users_add' => 'Add new users',
    'users_edit' => 'Edit existing users',
    'users_delete' => 'Delete users',
    'audit_view' => 'View audit logs',
    'archive_view' => 'View archive',
    'backup_create' => 'Create database backups',
    'backup_restore' => 'Restore database backups'
];

try {
    // Create tables
    foreach ($sql_queries as $sql) {
        $conn->exec($sql);
        echo "<p style='color: green;'>✅ Table created successfully</p>";
    }
    
    // Insert default permissions
    foreach ($default_permissions as $name => $description) {
        $stmt = $conn->prepare("INSERT IGNORE INTO permissions (permission_name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
    }
    echo "<p style='color: green;'>✅ Default permissions added</p>";
    
    // Grant all permissions to admin user (id = 1)
    $admin_id = 1;
    $stmt = $conn->prepare("SELECT id FROM permissions");
    $stmt->execute();
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($permissions as $permission) {
        $stmt = $conn->prepare("INSERT IGNORE INTO user_permissions (user_id, permission_id, granted_by) VALUES (?, ?, ?)");
        $stmt->execute([$admin_id, $permission['id'], $admin_id]);
    }
    echo "<p style='color: green;'>✅ Admin user granted all permissions</p>";
    
    echo "<h2 style='color: green;'>✅ Security setup completed successfully!</h2>";
    echo "<p><a href='dashboard/'>Go to Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
