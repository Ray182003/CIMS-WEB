<?php
/**
 * Reset Password Page
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../security_helper.php';

$error = '';
$success = '';
$token_valid = false;

// Check if token is provided
if (!isset($_GET['token'])) {
    $error = "Invalid reset link.";
} else {
    $token = $_GET['token'];
    
    // Try to validate token - handle missing columns gracefully
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
        
        if ($user) {
            $token_valid = true;
            
            // Handle form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = trim($_POST['password']);
                $confirm_password = trim($_POST['confirm_password']);
                
                if (empty($password) || empty($confirm_password)) {
                    $error = "Both password fields are required.";
                } elseif ($password !== $confirm_password) {
                    $error = "Passwords do not match.";
                } elseif (strlen($password) < 6) {
                    $error = "Password must be at least 6 characters long.";
                } else {
                    // Update password and clear reset token
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    try {
                        $updateQuery = "UPDATE users 
                                        SET password_hash = :password_hash, 
                                            reset_token = NULL, 
                                            reset_token_expiry = NULL,
                                            password_changed_at = NOW()
                                        WHERE id = :user_id";
                        $stmt = $conn->prepare($updateQuery);
                        $stmt->bindParam(":password_hash", $password_hash);
                        $stmt->bindParam(":user_id", $user->id);
                        
                        if ($stmt->execute()) {
                            $success = "Password reset successfully! You can now login with your new password.";
                            
                            // Log the password reset
                            logActivity('PASSWORD_RESET', 'users', $user->id, null, ['password_changed' => true]);
                            
                            // Redirect to login after 3 seconds
                            header("refresh:3;url=" . BASE_URL . "/auth/loging.php");
                        } else {
                            $error = "Failed to reset password. Please try again.";
                        }
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'reset_token') !== false) {
                            // Try without reset_token columns
                            $updateQuery = "UPDATE users SET password_hash = :password_hash WHERE id = :user_id";
                            $stmt = $conn->prepare($updateQuery);
                            $stmt->bindParam(":password_hash", $password_hash);
                            $stmt->bindParam(":user_id", $user->id);
                            
                            if ($stmt->execute()) {
                                $success = "Password reset successfully! You can now login with your new password.";
                                logActivity('PASSWORD_RESET', 'users', $user->id, null, ['password_changed' => true]);
                                header("refresh:3;url=" . BASE_URL . "/auth/loging.php");
                            } else {
                                $error = "Failed to reset password. Please try again.";
                            }
                        } else {
                            $error = "Database error: " . $e->getMessage();
                        }
                    }
                }
            }
        } else {
            $error = "Invalid or expired reset link. Please request a new one.";
        }
    } catch (PDOException $e) {
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
    <title>Reset Password - Parish Information Management System</title>
    <link href="<?php echo BASE_URL; ?>/public/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="<?php echo BASE_URL; ?>/auth/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-key fa-2x" style="color: #28a745;"></i>
            <h4>Reset Your Password</h4>
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
                    
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-save"></i> Reset Password
                    </button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="mt-3 text-center">
            <a href="<?php echo BASE_URL; ?>/auth/loging.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html>
