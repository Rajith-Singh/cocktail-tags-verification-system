<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();

// Redirect to dashboard if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $agreeTerms = isset($_POST['agree_terms']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
        $message = "All fields are required";
        $messageType = "danger";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters";
        $messageType = "danger";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match";
        $messageType = "danger";
    } elseif (!$agreeTerms) {
        $message = "You must agree to the Privacy Policy and Terms of Service";
        $messageType = "danger";
    } else {
        try {
            $result = $auth->registerExpert([
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'full_name' => $fullName
            ]);
            
            if ($result['success']) {
                $message = "Registration successful! Redirecting to login...";
                $messageType = "success";
                // Redirect after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                $message = $result['message'];
                $messageType = "danger";
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = "danger";
        }
    }
}

$pageTitle = "Register - Expert Verification";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <div class="row min-vh-100 align-items-center justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="glass-card p-5 rounded-lg">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <i class="fas fa-user-plus fa-4x" style="color: var(--primary);"></i>
                    </div>
                    <h2 class="fw-bold mb-2">Create Account</h2>
                    <p class="text-muted">Join our expert verification community</p>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4">
                    <i class="fas fa-<?php echo $messageType === 'danger' ? 'exclamation-circle' : 'check-circle'; ?> me-2"></i>
                    <?php echo h($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" class="form-control form-control-lg" name="full_name" 
                               value="<?php echo h($_POST['full_name'] ?? ''); ?>" 
                               required placeholder="Enter your full name">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <input type="text" class="form-control form-control-lg" name="username" 
                               value="<?php echo h($_POST['username'] ?? ''); ?>" 
                               required placeholder="Choose a unique username">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address</label>
                        <input type="email" class="form-control form-control-lg" name="email" 
                               value="<?php echo h($_POST['email'] ?? ''); ?>" 
                               required placeholder="Enter your email">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" class="form-control form-control-lg" name="password" 
                               required placeholder="Create a strong password">
                        <small class="text-muted d-block mt-1">
                            Minimum 8 characters with uppercase, lowercase, numbers, and symbols
                        </small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Confirm Password</label>
                        <input type="password" class="form-control form-control-lg" name="confirm_password" 
                               required placeholder="Re-enter your password">
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="agree_terms" 
                               id="agreeTerms" required>
                        <label class="form-check-label" for="agreeTerms">
                            I agree to the 
                            <a href="#" class="fw-bold text-primary" data-bs-toggle="modal" 
                               data-bs-target="#privacyPolicyModal" onclick="return false;">
                                Privacy Policy
                            </a> 
                            and 
                            <a href="#" class="fw-bold text-primary" data-bs-toggle="modal" 
                               data-bs-target="#termsOfServiceModal" onclick="return false;">
                                Terms of Service
                            </a>
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-check me-2"></i>Create Account
                        </button>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p class="text-muted">Already have an account? 
                            <a href="login.php" class="fw-bold">Login here</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyPolicyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-shield-alt me-2"></i>Privacy Policy
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold mb-3">1. Information We Collect</h6>
                <p class="text-muted mb-4">
                    We collect information you provide directly, such as when you create an account. This includes:
                </p>
                <ul class="text-muted mb-4">
                    <li>Full name, email address, and username</li>
                    <li>Password and authentication information</li>
                    <li>Verification activities and tag verification history</li>
                    <li>User profile information and preferences</li>
                </ul>
                
                <h6 class="fw-bold mb-3">2. How We Use Your Information</h6>
                <p class="text-muted mb-4">
                    We use the information we collect to:
                </p>
                <ul class="text-muted mb-4">
                    <li>Provide, maintain, and improve our services</li>
                    <li>Create and manage your account</li>
                    <li>Track your verification activities and statistics</li>
                    <li>Send you service-related notifications</li>
                    <li>Prevent fraud and ensure security</li>
                </ul>
                
                <h6 class="fw-bold mb-3">3. Data Security</h6>
                <p class="text-muted mb-4">
                    We implement appropriate security measures to protect your personal information from unauthorized access, alteration, disclosure, or destruction. 
                    All passwords are encrypted using bcrypt hashing.
                </p>
                
                <h6 class="fw-bold mb-3">4. Data Sharing</h6>
                <p class="text-muted mb-4">
                    We do not sell, trade, or rent your personal information to third parties. Your data is only used for the purposes outlined in this policy.
                </p>
                
                <h6 class="fw-bold mb-3">5. Cookies</h6>
                <p class="text-muted mb-4">
                    We use cookies and similar technologies to enhance your browsing experience and remember your preferences. 
                    You can control cookie settings through your browser.
                </p>
                
                <h6 class="fw-bold mb-3">6. Your Rights</h6>
                <p class="text-muted mb-4">
                    You have the right to access, update, or delete your personal information at any time. 
                    Contact us for assistance with these requests.
                </p>
                
                <h6 class="fw-bold mb-3">7. Policy Changes</h6>
                <p class="text-muted mb-0">
                    We may update this Privacy Policy periodically. We will notify you of any significant changes by updating the "Last Updated" date.
                </p>
                
                <div class="alert alert-info mt-4">
                    <small><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></small>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<!-- Terms of Service Modal -->
<div class="modal fade" id="termsOfServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-file-contract me-2"></i>Terms of Service
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold mb-3">1. Acceptance of Terms</h6>
                <p class="text-muted mb-4">
                    By accessing and using this Cocktail Verification System, you accept and agree to be bound by the terms and provision of this agreement.
                </p>
                
                <h6 class="fw-bold mb-3">2. User Responsibilities</h6>
                <p class="text-muted mb-4">
                    You are responsible for maintaining the confidentiality of your account and password and for restricting access to your computer. 
                    You agree to accept responsibility for all activities that occur under your account.
                </p>
                
                <h6 class="fw-bold mb-3">3. User Conduct</h6>
                <p class="text-muted mb-4">
                    You agree not to:
                </p>
                <ul class="text-muted mb-4">
                    <li>Harass or cause distress to other users</li>
                    <li>Submit false or misleading verification data</li>
                    <li>Attempt to gain unauthorized access to the system</li>
                    <li>Disrupt the normal flow of dialogue or data transmission</li>
                    <li>Violate any applicable laws or regulations</li>
                </ul>
                
                <h6 class="fw-bold mb-3">4. Intellectual Property Rights</h6>
                <p class="text-muted mb-4">
                    The content, features, and functionality of the Cocktail Verification System are owned by us, our licensors, 
                    or other providers of such material and are protected by copyright laws.
                </p>
                
                <h6 class="fw-bold mb-3">5. Disclaimer of Warranties</h6>
                <p class="text-muted mb-4">
                    The Cocktail Verification System is provided on an "AS-IS" basis. We make no warranties, expressed or implied, 
                    regarding its operation or the information, content, or materials included on the system.
                </p>
                
                <h6 class="fw-bold mb-3">6. Limitation of Liability</h6>
                <p class="text-muted mb-4">
                    In no event shall we be liable for any direct, indirect, incidental, special, consequential or punitive damages 
                    arising out of or relating to your use of the system.
                </p>
                
                <h6 class="fw-bold mb-3">7. Termination</h6>
                <p class="text-muted mb-4">
                    We reserve the right to terminate your account and access to the system if you violate these terms of service 
                    or engage in conduct that we deem harmful to other users or the integrity of the system.
                </p>
                
                <h6 class="fw-bold mb-3">8. Governing Law</h6>
                <p class="text-muted mb-0">
                    These terms and conditions are governed by and construed in accordance with applicable laws, 
                    and you irrevocably submit to the exclusive jurisdiction of the courts in that location.
                </p>
                
                <div class="alert alert-info mt-4">
                    <small><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></small>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Accept</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>