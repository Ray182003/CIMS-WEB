<?php
/**
 * Debug Version of Reset Password
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../security_helper.php';

$error = '';
$success = '';
$token_valid = false;
$debug_info = [];

function columnExists(PDO $connection, string $table, string $column): bool
{
    try {
        $stmt = $connection->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

// Check if token is provided
if (!isset($_GET['token'])) {
    $error = "Invalid reset link.";
    $debug_info[] = "No token provided in URL";
} else {
    $token = $_GET['token'];
    $debug_info[] = "Token provided: " . substr($token, 0, 8) . "...";
    
    // Try to validate token
    try {
        $query = "SELECT id, username, full_name FROM users 
                  WHERE reset_token = :token 
                  AND reset_token_expiry > NOW() 
                  AND status = 'active' 
                  LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_OBJ);
        
        $debug_info[] = "Token validation query executed";
        $debug_info[] = "User found: " . ($user ? "YES (ID: " . $user->id . ", Username: " . $user->username . ")" : "NO");
        
        if ($user) {
            $token_valid = true;
            $debug_info[] = "Token is valid";
            
            // Handle form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = trim($_POST['password']);
                $confirm_password = trim($_POST['confirm_password']);
                
                $debug_info[] = "Password reset form submitted";
                $debug_info[] = "Password length: " . strlen($password);
                $debug_info[] = "Passwords match: " . ($password === $confirm_password ? "YES" : "NO");
                
                if (empty($password) || empty($confirm_password)) {
                    $error = "Both password fields are required.";
                } elseif ($password !== $confirm_password) {
                    $error = "Passwords do not match.";
                } elseif (strlen($password) < 6) {
                    $error = "Password must be at least 6 characters long.";
                } else {
                    // Update password and clear reset token
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $debug_info[] = "Password hashed successfully";

                    $hasPasswordHashColumn = columnExists($conn, 'users', 'password_hash');
                    $hasPasswordColumn = columnExists($conn, 'users', 'password');
                    $hasResetTokenColumn = columnExists($conn, 'users', 'reset_token');
                    $hasResetExpiryColumn = columnExists($conn, 'users', 'reset_token_expiry');
                    $hasPasswordChangedAt = columnExists($conn, 'users', 'password_changed_at');

                    $debug_info[] = "password_hash column: " . ($hasPasswordHashColumn ? 'YES' : 'NO');
                    $debug_info[] = "password column: " . ($hasPasswordColumn ? 'YES' : 'NO');

                    try {
                        $setParts = [];
                        if ($hasPasswordHashColumn) {
                            $setParts[] = "password_hash = :password_hash";
                        }
                        if ($hasPasswordColumn) {
                            $setParts[] = "password = :password_hash";
                        }
                        if ($hasResetTokenColumn) {
                            $setParts[] = "reset_token = NULL";
                        }
                        if ($hasResetExpiryColumn) {
                            $setParts[] = "reset_token_expiry = NULL";
                        }
                        if ($hasPasswordChangedAt) {
                            $setParts[] = "password_changed_at = NOW()";
                        }

                        if (empty($setParts)) {
                            throw new RuntimeException('No password column available for update.');
                        }

                        $updateQuery = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = :user_id";
                        $stmt = $conn->prepare($updateQuery);

                        if ($hasPasswordHashColumn || $hasPasswordColumn) {
                            $stmt->bindParam(":password_hash", $password_hash);
                        }
                        $stmt->bindParam(":user_id", $user->id, PDO::PARAM_INT);

                        $debug_info[] = "Attempting to update password...";

                        if ($stmt->execute()) {
                            $debug_info[] = "Password updated successfully";
                            $success = "Password reset successfully! You can now login with your new password.";

                            logActivity('PASSWORD_RESET', 'users', $user->id, null, ['password_changed' => true]);

                            header("refresh:3;url=" . BASE_URL . "/auth/loging.php");
                        } else {
                            $debug_info[] = "Password update failed";
                            $error = "Failed to reset password. Please try again.";
                        }
                    } catch (Throwable $e) {
                        $debug_info[] = "Password update error: " . $e->getMessage();
                        $error = "Database error: " . $e->getMessage();
                    }
                }
            }
        } else {
            $debug_info[] = "Token is invalid or expired";
            $error = "Invalid or expired reset link. Please request a new one.";
        }
    } catch (PDOException $e) {
        $debug_info[] = "Token validation error: " . $e->getMessage();
        
        if (strpos($e->getMessage(), 'reset_token') !== false) {
            $error = "Password reset functionality is not properly set up. Please contact administrator to set up the database.";
        } else {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password (Debug) - Parish Information Management System</title>
    <link href="<?php echo BASE_URL; ?>/public/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="<?php echo BASE_URL; ?>/auth/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-key fa-2x" style="color: #ffc107;"></i>
            <h4>Reset Password (Debug)</h4>
            <p class="text-muted">Set your new password</p>
        </div>
        
        <?php if ($token_valid): ?>
            <?php if ($user): ?>
                <div class="alert alert-info">
                    <strong>Account:</strong> <?php echo htmlspecialchars($user->username); ?>
                    <br>
                    <strong>Name:</strong> <?php echo htmlspecialchars($user->full_name); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <p class="text-muted">Redirecting to login page...</p>
            <?php else: ?>
                <form method="POST" class="form">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <input type="password" class="form-control" name="password" placeholder="Enter new password" required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Confirm new password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fas fa-save"></i> Reset Password
                    </button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($debug_info)): ?>
            <div class="mt-4">
                <h6><i class="fas fa-bug"></i> Debug Information:</h6>
                <div class="alert alert-info" style="font-size: 12px; font-family: monospace;">
                    <?php foreach ($debug_info as $info): ?>
                        <div><?php echo htmlspecialchars($info); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-3 text-center">
            <a href="<?php echo BASE_URL; ?>/auth/loging.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html>
