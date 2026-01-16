<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();

// Redirect to dashboard if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "Cocktail Verification System";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <!-- Hero Section -->
    <div class="row min-vh-100 align-items-center">
        <div class="col-lg-6 mb-5 mb-lg-0">
            <h1 class="display-4 fw-bold mb-4" style="color: white;">
                <i class="fas fa-glass-martini me-3"></i>
                Cocktail Tag Verification
            </h1>
            <p class="lead mb-4" style="color: rgba(255,255,255,0.9);">
                Join our community of expert bartenders and mixologists to verify and curate the world's most comprehensive cocktail database. Your expertise matters!
            </p>
            
            <div class="d-flex gap-3 flex-wrap mb-5">
                <a href="register.php" class="btn btn-light btn-lg px-5">
                    <i class="fas fa-user-plus me-2"></i>Register Now
                </a>
                <a href="login.php" class="btn btn-outline-light btn-lg px-5">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
            </div>
            
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="text-white">
                        <h4 class="fw-bold">500+</h4>
                        <p class="text-muted">Cocktails to Verify</p>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="text-white">
                        <h4 class="fw-bold">50+</h4>
                        <p class="text-muted">Expert Contributors</p>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="text-white">
                        <h4 class="fw-bold">2000+</h4>
                        <p class="text-muted">Tags Verified</p>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="text-white">
                        <h4 class="fw-bold">98%</h4>
                        <p class="text-muted">Avg Accuracy</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="glass-card p-5">
                <div class="text-center mb-4">
                    <i class="fas fa-flask-vial fa-5x mb-3" style="color: var(--primary);"></i>
                    <h2 class="fw-bold">Why Contribute?</h2>
                </div>
                
                <div class="mb-4">
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle p-2 me-3" style="background: var(--primary); width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-crown text-white fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-1">Earn Recognition</h5>
                            <p class="text-muted mb-0">Get badges and achievements as you verify more cocktails</p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle p-2 me-3" style="background: var(--secondary); width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-users text-white fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-1">Join the Community</h5>
                            <p class="text-muted mb-0">Connect with fellow bartenders and mixologists worldwide</p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle p-2 me-3" style="background: var(--accent); width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-chart-line text-white fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-1">Track Progress</h5>
                            <p class="text-muted mb-0">Monitor your verification statistics and improvement over time</p>
                        </div>
                    </div>
                    
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle p-2 me-3" style="background: #6366F1; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-database text-white fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-1">Help Research</h5>
                            <p class="text-muted mb-0">Contribute to comprehensive AI training datasets</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Features Section -->
    <div class="row my-5 py-5">
        <div class="col-12 text-center mb-5">
            <h2 class="fw-bold" style="color: white;">
                <i class="fas fa-sparkles me-2"></i>Powerful Features
            </h2>
            <p class="lead" style="color: rgba(255,255,255,0.8);">Everything you need to verify cocktails with confidence</p>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="mb-3">
                    <i class="fas fa-check-double fa-3x mb-3" style="color: var(--primary);"></i>
                </div>
                <h5 class="fw-bold mb-2">Smart Verification</h5>
                <p class="text-muted mb-0">Verify tags with confidence scores, detailed notes, and category classification for every cocktail.</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="mb-3">
                    <i class="fas fa-image fa-3x mb-3" style="color: var(--secondary);"></i>
                </div>
                <h5 class="fw-bold mb-2">Rich Media</h5>
                <p class="text-muted mb-0">View cocktail images, instructional videos, and detailed ingredient lists while verifying tags.</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="mb-3">
                    <i class="fas fa-download fa-3x mb-3" style="color: var(--accent);"></i>
                </div>
                <h5 class="fw-bold mb-2">Export Data</h5>
                <p class="text-muted mb-0">Download verified datasets in CSV or JSON formats with complete verification history.</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="mb-3">
                    <i class="fas fa-chart-bar fa-3x mb-3" style="color: #6366F1;"></i>
                </div>
                <h5 class="fw-bold mb-2">Analytics Dashboard</h5>
                <p class="text-muted mb-0">Track your performance metrics, accuracy scores, and contribution statistics in real-time.</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="mb-3">
                    <i class="fas fa-tag fa-3x mb-3" style="color: #EC4899;"></i>
                </div>
                <h5 class="fw-bold mb-2">Tag Management</h5>
                <p class="text-muted mb-0">Add new tags, manage categories, and suggest improvements to the tag library.</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="mb-3">
                    <i class="fas fa-shield-alt fa-3x mb-3" style="color: #06B6D4;"></i>
                </div>
                <h5 class="fw-bold mb-2">Secure & Private</h5>
                <p class="text-muted mb-0">Your data is encrypted and protected. We never share personal information with third parties.</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
