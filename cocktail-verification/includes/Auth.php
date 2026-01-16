<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $pdo;
    
    public function __construct() {
        try {
            $this->pdo = getDB();
            
            if ($this->pdo === null) {
                throw new Exception("Failed to initialize database connection");
            }
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        } catch (Exception $e) {
            error_log("Auth initialization error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Register a new expert
     */
    public function registerExpert($data) {
        try {
            // Validate input
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                throw new Exception("Missing required fields");
            }
            
            // Check if user exists
            $stmt = $this->pdo->prepare("SELECT id FROM experts WHERE username = ? OR email = ?");
            $stmt->execute([$data['username'], $data['email']]);
            
            if ($stmt->fetch()) {
                throw new Exception("Username or email already registered");
            }
            
            // Validate password strength
            if (!$this->isPasswordStrong($data['password'])) {
                throw new Exception("Password must contain uppercase, lowercase, numbers, and symbols");
            }
            
            // Hash password
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Insert expert
            $insertStmt = $this->pdo->prepare("
                INSERT INTO experts (
                    username, email, password_hash, full_name,
                    expertise_level, specialty_tags, is_verified,
                    is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $insertStmt->execute([
                $data['username'],
                $data['email'],
                $passwordHash,
                $data['full_name'],
                $data['expertise_level'] ?? 'bartender',
                $data['specialty_tags'] ?? '',
                0,
                1
            ]);
            
            return ['success' => true, 'message' => 'Registration successful. Please verify your email.'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Login expert
     */
    public function login($username, $password, $rememberMe = false) {
        if ($this->pdo === null) {
            throw new Exception("Database connection not available");
        }
        
        $stmt = $this->pdo->prepare("
            SELECT id, username, email, password_hash, full_name, expertise_level, 
                   is_active, is_verified, is_admin 
            FROM experts 
            WHERE (username = ? OR email = ?) AND is_active = 1
        ");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare login query: " . implode(", ", $this->pdo->errorInfo()));
        }
        
        $stmt->execute([$username, $username]);
        $expert = $stmt->fetch();
        
        if (!$expert) {
            throw new Exception("Account not found or inactive");
        }
        
        if (!password_verify($password, $expert['password_hash'])) {
            throw new Exception("Invalid password");
        }
        
        // Update last login
        $updateStmt = $this->pdo->prepare("UPDATE experts SET last_login = NOW() WHERE id = ?");
        if ($updateStmt) {
            $updateStmt->execute([$expert['id']]);
        }
        
        // Set session
        $_SESSION['expert_id'] = $expert['id'];
        $_SESSION['username'] = $expert['username'];
        $_SESSION['full_name'] = $expert['full_name'];
        $_SESSION['expertise_level'] = $expert['expertise_level'];
        $_SESSION['is_admin'] = $expert['is_admin'] == 1;
        $_SESSION['is_verified'] = $expert['is_verified'] == 1;
        
        // Set remember me cookie if requested
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $token);
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            
            setcookie('remember_token', $token, $expiry, '/', '', true, true);
            setcookie('expert_id', $expert['id'], $expiry, '/', '', true, true);
            
            // Store hashed token in database
            $tokenStmt = $this->pdo->prepare("
                UPDATE experts SET remember_token = ? WHERE id = ?
            ");
            if ($tokenStmt) {
                $tokenStmt->execute([$hashedToken, $expert['id']]);
            }
        }
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'expert' => $expert
        ];
    }
    
    /**
     * Logout
     */
    public function logout() {
        // Clear session
        $_SESSION = [];
        session_destroy();
        
        // Clear remember me cookies
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('expert_id', '', time() - 3600, '/');
        
        session_start();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (isset($_SESSION['expert_id'])) {
            return true;
        }
        
        // Check remember me cookie
        if (isset($_COOKIE['remember_token']) && isset($_COOKIE['expert_id'])) {
            return $this->verifyRememberToken($_COOKIE['expert_id'], $_COOKIE['remember_token']);
        }
        
        return false;
    }
    
    /**
     * Verify remember me token
     */
    private function verifyRememberToken($expertId, $token) {
        $hashedToken = hash('sha256', $token);
        
        $stmt = $this->pdo->prepare("
            SELECT id FROM experts 
            WHERE id = ? AND remember_token = ? AND is_active = 1
        ");
        
        $stmt->execute([$expertId, $hashedToken]);
        
        if ($expert = $stmt->fetch()) {
            // Restore session
            $_SESSION['expert_id'] = $expert['id'];
            
            $fullExpert = $this->getCurrentExpert();
            $_SESSION['username'] = $fullExpert['username'];
            $_SESSION['full_name'] = $fullExpert['full_name'];
            $_SESSION['expertise_level'] = $fullExpert['expertise_level'];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get current expert
     */
    public function getCurrentExpert() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        if ($this->pdo === null) {
            return null;
        }
        
        $stmt = $this->pdo->prepare("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM verification_logs WHERE expert_id = e.id) as total_verifications,
                   (SELECT COUNT(*) FROM cocktail_tags WHERE verified_by = e.id AND status = 'verified') as tags_verified,
                   (SELECT COUNT(*) FROM tag_suggestions WHERE suggested_by = e.id) as suggestions_made,
                   (SELECT COALESCE(AVG(confidence_score), 0) FROM cocktail_tags WHERE verified_by = e.id) as accuracy_score,
                   (SELECT 0) as streak_days
            FROM experts e 
            WHERE id = ?
        ");
        
        if (!$stmt) {
            error_log("getCurrentExpert prepare failed: " . implode(", ", $this->pdo->errorInfo()));
            return null;
        }
        
        $stmt->execute([$_SESSION['expert_id']]);
        return $stmt->fetch();
    }
    
    /**
     * Update expert profile
     */
    public function updateProfile($expertId, $data) {
        $allowedFields = ['full_name', 'bio', 'expertise_years', 'specialty_tags'];
        $updates = [];
        $values = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $expertId;
        $sql = "UPDATE experts SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Change password
     */
    public function changePassword($expertId, $currentPassword, $newPassword) {
        // Get current password hash
        $stmt = $this->pdo->prepare("SELECT password_hash FROM experts WHERE id = ?");
        $stmt->execute([$expertId]);
        $expert = $stmt->fetch();
        
        if (!$expert || !password_verify($currentPassword, $expert['password_hash'])) {
            throw new Exception("Current password is incorrect");
        }
        
        if (!$this->isPasswordStrong($newPassword)) {
            throw new Exception("New password must be stronger");
        }
        
        // Update password
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $updateStmt = $this->pdo->prepare("UPDATE experts SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        return $updateStmt->execute([$newHash, $expertId]);
    }
    
    /**
     * Check password strength
     */
    private function isPasswordStrong($password) {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
    }
    
    /**
     * Request password reset
     */
    public function requestPasswordReset($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM experts WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $expert = $stmt->fetch();
        
        if (!$expert) {
            throw new Exception("No account found with this email");
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);
        $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Store token in database
        $updateStmt = $this->pdo->prepare("
            UPDATE experts 
            SET reset_token = ?, reset_token_expires_at = ? 
            WHERE id = ?
        ");
        
        $updateStmt->execute([$hashedToken, $expiry, $expert['id']]);
        
        // Send reset email (implement email sending)
        // sendPasswordResetEmail($email, $token);
        
        return true;
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword($email, $token, $newPassword) {
        $hashedToken = hash('sha256', $token);
        
        $stmt = $this->pdo->prepare("
            SELECT id FROM experts 
            WHERE email = ? 
            AND reset_token = ? 
            AND reset_token_expires_at > NOW() 
            AND is_active = 1
        ");
        
        $stmt->execute([$email, $hashedToken]);
        $expert = $stmt->fetch();
        
        if (!$expert) {
            throw new Exception("Invalid or expired reset token");
        }
        
        if (!$this->isPasswordStrong($newPassword)) {
            throw new Exception("Password is not strong enough");
        }
        
        // Update password and clear reset token
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $updateStmt = $this->pdo->prepare("
            UPDATE experts 
            SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $updateStmt->execute([$newHash, $expert['id']]);
    }
}
?>