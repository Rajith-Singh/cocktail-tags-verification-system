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

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $expertiseLevel = $_POST['expertise_level'] ?? 'bartender';
    $specialty = trim($_POST['specialty'] ?? '');
    
    // Validation
    $errors = [];
    
    if (strlen($username) < 3) $errors[] = "Username must be at least 3 characters";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (strlen($fullName) < 3) $errors[] = "Full name is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $passwordConfirm) $errors[] = "Passwords do not match";
    
    if (empty($errors)) {
        try {
            $result = $auth->registerExpert([
                'username' => $username,
                'email' => $email,
                'full_name' => $fullName,
                'password' => $password,
                'expertise_level' => $expertiseLevel,
                'specialty_tags' => $specialty
            ]);
            
            if ($result['success']) {
                $message = "Registration successful! Please log in.";
                $messageType = "success";
                // Redirect after 2 seconds
                header('refresh:2;url=login.php');
            } else {
                $message = $result['message'] ?? "Registration failed";
                $messageType = "danger";
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = "danger";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "danger";
    }
}

$pageTitle = "Register - Domain Expert";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <div class="row min-vh-100 align-items-center justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="glass-card p-5 rounded-lg">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <i class="fas fa-flask-vial fa-4x" style="color: var(--primary);"></i>
                    </div>
                    <h2 class="fw-bold mb-2">Register as Expert</h2>
                    <p class="text-muted">Join our cocktail verification community</p>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <input type="text" class="form-control" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               required placeholder="Your unique username">
                        <small class="text-muted">3-50 characters, letters and numbers only</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               required placeholder="your.email@example.com">
                        <small class="text-muted">We'll never share your email</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" class="form-control" name="full_name" 
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                               required placeholder="Your full name">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Expertise Level</label>
                                <select class="form-select" name="expertise_level">
                                    <option value="trainee">Trainee Bartender</option>
                                    <option value="bartender" selected>Bartender</option>
                                    <option value="senior">Senior Bartender</option>
                                    <option value="head_bartender">Head Bartender</option>
                                    <option value="mixologist">Mixologist</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Specialty</label>
                                <input type="text" class="form-control" name="specialty" 
                                       value="<?php echo htmlspecialchars($_POST['specialty'] ?? ''); ?>" 
                                       placeholder="e.g., Gin cocktails, Spirits">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" class="form-control" name="password" 
                               required placeholder="Minimum 8 characters">
                        <small class="text-muted">Must include uppercase, lowercase, number, and symbol</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Confirm Password</label>
                        <input type="password" class="form-control" name="password_confirm" 
                               required placeholder="Repeat your password">
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                        <label class="form-check-label" for="agreeTerms">
                            I agree to the Terms of Service and Privacy Policy
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p class="text-muted">Already have an account? 
                            <a href="login.php" class="fw-bold">Sign in here</a>
                        </p>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Why Register?</strong>
                    <ul class="mb-0 mt-2">
                        <li>Track your verification contributions</li>
                        <li>Earn expert badges and achievements</li>
                        <li>Access exclusive analytics</li>
                        <li>Join the bartender community</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>