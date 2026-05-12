<?php
/**
 * AAA Security System
 * Provides comprehensive security features for the CIMS application
 */

class AaaSecurity {
    private $conn;
    private $sessionId;
    private $userId;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->sessionId = session_id();
        $this->userId = $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Validate user session
     */
    public function validateSession() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
            return false;
        }
        
        // Check if session is still valid in database
        try {
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE id = ? AND status = 'active'");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Session validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log security events
     */
    public function logEvent($event_type, $description, $severity = 'info') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO security_logs (user_id, session_id, event_type, description, severity, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $stmt->execute([
                $this->userId,
                $this->sessionId,
                $event_type,
                $description,
                $severity,
                $ip_address,
                $user_agent
            ]);
            
        } catch (PDOException $e) {
            error_log("Security log error: " . $e->getMessage());
        }
    }
    
    /**
     * Check user permissions
     */
    public function checkPermission($permission) {
        if (!$this->validateSession()) {
            return false;
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT p.permission_name 
                FROM permissions p
                JOIN user_permissions up ON p.id = up.permission_id
                WHERE up.user_id = ? AND p.permission_name = ?
            ");
            $stmt->execute([$this->userId, $permission]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Permission check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log audit activity
     */
    public function logAudit($userId, $action, $module, $recordId = null, $oldValues = null, $newValues = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO audit_logs (user_id, action, module, record_id, old_values, new_values, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $oldJson = $oldValues ? json_encode($oldValues) : null;
            $newJson = $newValues ? json_encode($newValues) : null;
            
            $stmt->execute([
                $userId,
                $action,
                $module,
                $recordId,
                $oldJson,
                $newJson,
                $ip_address,
                $user_agent
            ]);
            
        } catch (PDOException $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user has specific permission (alias for checkPermission)
     */
    public function hasPermission($userId, $permission) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.permission_name 
                FROM permissions p
                JOIN user_permissions up ON p.id = up.permission_id
                WHERE up.user_id = ? AND p.permission_name = ?
            ");
            $stmt->execute([$userId, $permission]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Permission check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRF($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Rate limiting check
     */
    public function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
        $key = $action . '_' . $this->sessionId;
        
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as attempts 
                FROM rate_limits 
                WHERE session_id = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$this->sessionId, $action, $timeWindow]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['attempts'] < $maxAttempts;
        } catch (PDOException $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return true; // Allow on error
        }
    }
    
    /**
     * Log rate limit attempt
     */
    public function logRateLimit($action) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO rate_limits (session_id, action, created_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$this->sessionId, $action]);
        } catch (PDOException $e) {
            error_log("Rate limit log error: " . $e->getMessage());
        }
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (!$this->validateSession()) {
            return null;
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT id, username, email, full_name, role, created_at
                FROM users 
                WHERE id = ? AND status = 'active'
            ");
            $stmt->execute([$this->userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get current user error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Destroy session securely
     */
    public function destroySession() {
        $this->logEvent('logout', 'User logged out', 'info');
        
        // Clear session data
        $_SESSION = [];
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
}
?>
