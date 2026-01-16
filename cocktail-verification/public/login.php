<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$message = '';
$messageType = '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember']);
    
    try {
        $result = $auth->login($username, $password, $rememberMe);
        
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $message = $result['message'] ?? "Login failed";
            $messageType = "danger";
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "danger";
    }
}

$pageTitle = "Login - Expert Verification";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <div class="row min-vh-100 align-items-center justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="glass-card p-5 rounded-lg">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <i class="fas fa-martini-glass-citrus fa-4x" style="color: var(--primary);"></i>
                    </div>
                    <h2 class="fw-bold mb-2">Expert Login</h2>
                    <p class="text-muted">Cocktail Tag Verification System</p>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4">
                    <i class="fas fa-<?php echo $messageType === 'danger' ? 'exclamation-circle' : 'check-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username or Email</label>
                        <input type="text" class="form-control form-control-lg" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               required autofocus placeholder="Enter your username or email">
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0">Password</label>
                            <a href="forgot-password.php" class="small">Forgot password?</a>
                        </div>
                        <input type="password" class="form-control form-control-lg" name="password" 
                               required placeholder="Enter your password">
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Remember me for 30 days
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p class="text-muted">Don't have an account? 
                            <a href="register.php" class="fw-bold">Register now</a>
                        </p>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <small class="text-muted d-block mb-3">Demo Credentials:</small>
                    <code class="bg-light p-2 rounded d-block mb-2">bartender / demo123</code>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
