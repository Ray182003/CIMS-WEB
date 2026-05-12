<?php
$title = "User Management";
require_once "../security_helper.php";

// Require admin permission
requirePermission('users_view');

function columnExists(PDO $connection, string $table, string $column): bool
{
    try {
        $stmt = $connection->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return $stmt && $stmt->rowCount() > 0;
    } catch (\Throwable $e) {
        return false;
    }
}

if (!columnExists($conn, 'users', 'status')) {
    $conn->exec("ALTER TABLE users ADD COLUMN status ENUM('active','inactive') DEFAULT 'active'");
}

// Handle AJAX request to get user data
if (isset($_GET['get_user']) && is_numeric($_GET['get_user'])) {
    $userId = (int)$_GET['get_user'];
    
    $stmt = $conn->prepare("SELECT id, username, role, status FROM users WHERE id = :user_id LIMIT 1");
    $stmt->bindParam(":user_id", $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($user) {
        header('Content-Type: application/json');
        echo json_encode([
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role ?? 'staff',
            'status' => $user->status ?? 'active'
        ]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'User not found']);
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_user' && (($security && $security->checkPermission('users_add')) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'))) {
        $username = trim($_POST['username']);
        $role = trim($_POST['role']);
        $status = $_POST['status'] ?? 'active';
        $status = in_array($status, ['active', 'inactive'], true) ? $status : 'active';
        $password = $_POST['password'];
        
        // Validate input
        if (empty($username) || empty($role) || empty($password)) {
            $error = "Username, role, password, and status are required.";
        } else {
            // Check if username exists
            $checkQuery = "SELECT id FROM users WHERE username = :username LIMIT 1";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bindParam(":username", $username);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                $error = "Username already exists.";
            } else {
                // Create user
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $insertQuery = "INSERT INTO users (username, password, role, status) 
                               VALUES (:username, :password, :role, :status)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bindParam(":username", $username);
                $stmt->bindParam(":password", $passwordHash);
                $stmt->bindParam(":role", $role);
                $stmt->bindParam(":status", $status);
                
                if ($stmt->execute()) {
                    logActivity('CREATE', 'users', $conn->lastInsertId(), null, [
                        'username' => $username,
                        'role' => $role,
                        'status' => $status
                    ]);
                    $success = "User created successfully.";
                } else {
                    $error = "Failed to create user.";
                }
            }
        }
    } elseif ($action == 'reset_password' && ($security && $security->hasPermission($_SESSION['user_id'], 'users_edit') || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'))) {
        $userId = $_POST['user_id'];
        $newPassword = $_POST['new_password'];
        
        if (empty($newPassword)) {
            $error = "Password is required.";
        } else {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE users SET password = :password WHERE id = :user_id";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bindParam(":password", $passwordHash);
            $stmt->bindParam(":user_id", $userId);
            
            if ($stmt->execute()) {
                logActivity('PASSWORD_RESET', 'users', $userId, null, ['password_changed' => true]);
                $success = "Password reset successfully.";
            } else {
                $error = "Failed to reset password.";
            }
        }
    } elseif ($action == 'edit_user' && (($security && $security->checkPermission('users_edit')) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'))) {
        $userId = (int)$_POST['user_id'];
        $username = trim($_POST['username']);
        $role = trim($_POST['role']);
        $status = $_POST['status'] ?? 'active';
        $status = in_array($status, ['active', 'inactive'], true) ? $status : 'active';
        
        if (empty($username) || empty($role)) {
            $error = "Username, role, and status are required.";
        } else {
            // Check if username exists (excluding current user)
            $checkQuery = "SELECT id FROM users WHERE username = :username AND id != :user_id LIMIT 1";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                $error = "Username already exists.";
            } else {
                // Update user
                $updateQuery = "UPDATE users SET username = :username, role = :role, status = :status WHERE id = :user_id";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bindParam(":username", $username);
                $stmt->bindParam(":role", $role);
                $stmt->bindParam(":user_id", $userId);
                $stmt->bindParam(":status", $status);
                
                if ($stmt->execute()) {
                    logActivity('UPDATE', 'users', $userId, null, [
                        'username' => $username,
                        'role' => $role,
                        'status' => $status
                    ]);
                    $success = "User updated successfully.";
                } else {
                    $error = "Failed to update user.";
                }
            }
        }
    } elseif ($action == 'toggle_status' && (($security && $security->checkPermission('users_edit')) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'))) {
        $userId = (int)$_POST['user_id'];
        $newStatus = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

        if ($userId == $_SESSION['user_id'] && $newStatus === 'inactive') {
            $error = "You cannot deactivate your own account.";
        } else {
            $updateQuery = "UPDATE users SET status = :status WHERE id = :user_id";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bindParam(":status", $newStatus);
            $stmt->bindParam(":user_id", $userId);

            if ($stmt->execute()) {
                logActivity('STATUS_CHANGE', 'users', $userId, null, ['status' => $newStatus]);
                $success = "User status updated to " . ucfirst($newStatus) . ".";
            } else {
                $error = "Failed to update user status.";
            }
        }
    } elseif ($action == 'delete_user' && (($security && $security->checkPermission('users_delete')) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'))) {
        $userId = $_POST['user_id'];
        
        // Prevent self-deletion
        if ($userId == $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            // Get user info for audit
            $userQuery = "SELECT username FROM users WHERE id = :user_id LIMIT 1";
            $stmt = $conn->prepare($userQuery);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_OBJ);
            
            // Delete user
            $deleteQuery = "DELETE FROM users WHERE id = :user_id";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bindParam(":user_id", $userId);
            
            if ($stmt->execute()) {
                logActivity('DELETE', 'users', $userId, (array)$user, null);
                $success = "User deleted successfully.";
            } else {
                $error = "Failed to delete user.";
            }
        }
    }
}

// Get all users
$createdByExists = columnExists($conn, 'users', 'created_by');

if ($createdByExists) {
    $usersQuery = "SELECT u.id, u.username, u.role, u.status,
                   COALESCE(creator.username, 'System') as created_by_name
                   FROM users u
                   LEFT JOIN users creator ON u.created_by = creator.id
                   ORDER BY u.id DESC";
} else {
    $usersQuery = "SELECT u.id, u.username, u.role, u.status,
                   'System' as created_by_name
                   FROM users u
                   ORDER BY u.id DESC";
}

$users = $conn->query($usersQuery)->fetchAll(PDO::FETCH_OBJ);

include "../partials/html.head.php";
?>

<body class="sb-nav-fixed gradient-page">
    <?php include_once("../partials/navbar.php"); ?>
    <style>
        .user-mgmt-page {
            min-height: 100vh;
        }

        .user-mgmt-header h1 {
            font-weight: 700;
            letter-spacing: 0.03em;
            background: linear-gradient(120deg, #064e3b, #047857, #22c55e);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .user-mgmt-header .btn-primary {
            border-radius: 999px;
            padding-inline: 1.25rem;
            box-shadow: 0 10px 22px rgba(22, 163, 74, 0.35);
            background: linear-gradient(120deg, #16a34a, #22c55e);
            border: none;
        }

        .user-mgmt-header .btn-primary:hover {
            background: linear-gradient(120deg, #15803d, #16a34a);
            box-shadow: 0 14px 28px rgba(22, 163, 74, 0.45);
        }

        .user-mgmt-card {
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
        }

        .user-mgmt-card-header {
            background: transparent;
            border-bottom: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-mgmt-card-body {
            padding: 1.5rem 1.75rem;
        }

        .user-mgmt-table thead th {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom-width: 1px;
        }

        .user-mgmt-table tbody tr {
            transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }

        .user-mgmt-table tbody tr:hover {
            background-color: rgba(22, 163, 74, 0.06);
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.12);
            transform: translateY(-2px);
        }

        .user-mgmt-card .badge {
            border-radius: 999px;
            padding: 0.4rem 0.75rem;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
        }

        .user-mgmt-card .btn-group.btn-group-sm .btn {
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            padding: 0;
        }
    </style>
    <div id="layoutSidenav">
        <?php include_once("../partials/sidebar.php"); ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 py-4 user-mgmt-page">
                    <div class="d-flex justify-content-between align-items-center mb-4 user-mgmt-header">
                        <h1 class="mt-4">User Management</h1>
                        <?php if ($security && $security->hasPermission($_SESSION['user_id'], 'users_add')): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card user-mgmt-card">
                        <div class="card-header user-mgmt-card-header">
                            <i class="fas fa-users me-1"></i> System Users
                        </div>
                        <div class="card-body user-mgmt-card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover user-mgmt-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user->username); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user->role === 'admin' ? 'danger' : 'primary'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($user->role ?? 'staff')); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php $userStatus = $user->status ?? 'active'; ?>
                                                <span class="badge bg-<?php echo $userStatus === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($userStatus)); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($user->created_by_name); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if (($security && $security->checkPermission('users_edit')) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')): ?>
                                                    <button class="btn btn-outline-primary btn-sm" 
                                                            onclick="editUser(<?php echo $user->id; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-outline-warning btn-sm" 
                                                            onclick="resetPassword(<?php echo $user->id; ?>, '<?php echo htmlspecialchars($user->username); ?>')">
                                                        <i class="fas fa-key"></i>
                                                    </button>

                                                    <?php if (($security && $security->checkPermission('users_edit')) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')): ?>
                                                    <?php if ($userStatus === 'active'): ?>
                                                    <button class="btn btn-outline-secondary btn-sm" 
                                                            onclick="toggleStatus(<?php echo $user->id; ?>, '<?php echo htmlspecialchars($user->username); ?>', 'inactive')">
                                                        <i class="fas fa-user-slash"></i>
                                                    </button>
                                                    <?php else: ?>
                                                    <button class="btn btn-outline-success btn-sm" 
                                                            onclick="toggleStatus(<?php echo $user->id; ?>, '<?php echo htmlspecialchars($user->username); ?>', 'active')">
                                                        <i class="fas fa-user-check"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ((($security && $security->checkPermission('users_delete')) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')) && $user->id != $_SESSION['user_id']): ?>
                                                    <button class="btn btn-outline-danger btn-sm" 
                                                            onclick="deleteUser(<?php echo $user->id; ?>, '<?php echo htmlspecialchars($user->username); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <style>
        .user-modal-top {
            margin-top: 3.5rem;
        }
    </style>

    <!-- Add User Modal -->
    <?php if (($security && $security->hasPermission($_SESSION['user_id'], 'users_add')) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')): ?>
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog user-modal-top">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_user">
                        
                        <div class="mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="edit_role">
                                <option value="admin">Admin</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog user-modal-top">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" id="reset_user_id">
                        
                        <p>Reset password for: <strong id="reset_username"></strong></p>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password *</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editUser(userId) {
            // Fetch user data via AJAX; build a clean URL without existing query params
            const url = new URL(window.location.href);
            url.search = '';
            url.searchParams.set('get_user', userId);

            fetch(url.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    
                    document.getElementById('edit_user_id').value = data.id;
                    document.getElementById('edit_username').value = data.username;
                    document.getElementById('edit_role').value = data.role || 'staff';
                    document.getElementById('edit_status').value = data.status || 'active';
                    
                    new bootstrap.Modal(document.getElementById('editUserModal')).show();
                })
                .catch(error => {
                    console.error('Error fetching user data:', error);
                    alert('Error loading user data');
                });
        }
        
        function resetPassword(userId, username) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_username').textContent = username;
            
            new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
        }
        
        function toggleStatus(userId, username, newStatus) {
            const actionLabel = newStatus === 'active' ? 'activate' : 'deactivate';
            if (confirm(`Are you sure you want to ${actionLabel} user "${username}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteUser(userId, username) {
            if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

    <?php include_once("../partials/html.footer.php"); ?>
</body>
</html>
