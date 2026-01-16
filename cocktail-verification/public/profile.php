<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/Auth.php';

requireLogin();

$auth = new Auth();
$expert = $auth->getCurrentExpert();
$message = '';
$messageType = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!validateCSRF($csrf_token)) {
            $message = "Invalid request";
            $messageType = "danger";
        } else {
            try {
                $full_name = trim($_POST['full_name'] ?? '');
                $bio = trim($_POST['bio'] ?? '');
                $expertise_years = intval($_POST['expertise_years'] ?? 0);
                
                $result = $auth->updateProfile($expert['id'], [
                    'full_name' => $full_name,
                    'bio' => $bio,
                    'expertise_years' => $expertise_years
                ]);
                
                if ($result) {
                    $message = "Profile updated successfully!";
                    $messageType = "success";
                    $expert = $auth->getCurrentExpert();
                } else {
                    $message = "Failed to update profile";
                    $messageType = "danger";
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
                $messageType = "danger";
            }
        }
    } elseif ($action === 'change_password') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!validateCSRF($csrf_token)) {
            $message = "Invalid request";
            $messageType = "danger";
        } else {
            try {
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (strlen($new_password) < 8) {
                    throw new Exception("Password must be at least 8 characters");
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception("Passwords do not match");
                }
                
                $result = $auth->changePassword($expert['id'], $current_password, $new_password);
                
                if ($result) {
                    $message = "Password changed successfully!";
                    $messageType = "success";
                } else {
                    $message = "Failed to change password";
                    $messageType = "danger";
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
                $messageType = "danger";
            }
        }
    }
}

$pageTitle = "My Profile";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Profile Header -->
            <div class="glass-card p-5 mb-4">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="mb-3">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                                 style="width: 120px; height: 120px; background: linear-gradient(135deg, var(--primary), var(--secondary));">
                                <i class="fas fa-user fa-3x text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($expert['full_name']); ?></h2>
                        <p class="text-muted mb-3">
                            <i class="fas fa-at me-1"></i><?php echo htmlspecialchars($expert['username']); ?>
                        </p>
                        
                        <div class="d-flex flex-wrap gap-3">
                            <div class="expert-badge" 
                                 style="background: <?php echo $expertLevels[$expert['expertise_level']]['color']; ?>20; color: <?php echo $expertLevels[$expert['expertise_level']]['color']; ?>;">
                                <?php echo $expertLevels[$expert['expertise_level']]['icon']; ?>
                                <?php echo $expertLevels[$expert['expertise_level']]['name']; ?>
                            </div>
                            
                            <?php if ($expert['verification_badge'] !== 'none'): ?>
                            <span class="badge bg-warning" style="font-size: 12px; padding: 8px 12px;">
                                <i class="fas fa-star me-1"></i>
                                <?php echo ucfirst($expert['verification_badge']); ?> Badge
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Statistics -->
                <div class="col-md-6 mb-4">
                    <div class="glass-card p-4 h-100">
                        <h5 class="fw-bold mb-4">
                            <i class="fas fa-chart-bar me-2"></i>Statistics
                        </h5>
                        
                        <div class="row text-center mb-3">
                            <div class="col-6 mb-3">
                                <div class="rounded-circle p-3 mx-auto" 
                                     style="background: #10B98120; width: 70px; height: 70px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-check-circle fa-2x" style="color: #10B981;"></i>
                                </div>
                                <h4 class="fw-bold mt-2"><?php echo $expert['tags_verified']; ?></h4>
                                <small class="text-muted">Tags Verified</small>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="rounded-circle p-3 mx-auto" 
                                     style="background: #3B82F620; width: 70px; height: 70px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-plus-circle fa-2x" style="color: #3B82F6;"></i>
                                </div>
                                <h4 class="fw-bold mt-2"><?php echo $expert['tags_added']; ?></h4>
                                <small class="text-muted">Tags Added</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-muted">Accuracy Score</small>
                                <small class="fw-bold"><?php echo $expert['accuracy_score']; ?>%</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" 
                                     style="width: <?php echo $expert['accuracy_score']; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-muted">Current Streak</small>
                                <small class="fw-bold"><?php echo $expert['streak_days']; ?> days</small>
                            </div>
                            <small class="text-muted">Last activity: 
                                <?php if ($expert['last_activity']): ?>
                                    <?php echo timeago(new DateTime($expert['last_activity'])); ?>
                                <?php else: ?>
                                    Never
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Member since</small>
                            <h6 class="fw-bold"><?php echo date('F Y', strtotime($expert['created_at'])); ?></h6>
                        </div>
                    </div>
                </div>
                
                <!-- Update Profile Form -->
                <div class="col-md-6 mb-4">
                    <div class="glass-card p-4 h-100">
                        <h5 class="fw-bold mb-4">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </h5>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Full Name</label>
                                <input type="text" class="form-control" name="full_name" 
                                       value="<?php echo htmlspecialchars($expert['full_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Bio</label>
                                <textarea class="form-control" name="bio" rows="3" 
                                          placeholder="Tell us about yourself...">
<?php echo htmlspecialchars($expert['bio'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Years of Experience</label>
                                <input type="number" class="form-control" name="expertise_years" 
                                       value="<?php echo $expert['expertise_years'] ?? 0; ?>" min="0" max="50">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Change Password Form -->
            <div class="glass-card p-4 mb-4">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-lock me-2"></i>Change Password
                </h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">New Password</label>
                                <input type="password" class="form-control" name="new_password" required 
                                       placeholder="Minimum 8 characters">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-key me-2"></i>Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6 class="fw-bold mb-2">Password Requirements:</h6>
                            <ul class="mb-0 small">
                                <li>Minimum 8 characters</li>
                                <li>Contain uppercase letters</li>
                                <li>Contain lowercase letters</li>
                                <li>Contain numbers</li>
                                <li>Contain special characters</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Helper function for time ago
function timeago(datetime) {
    // Implementation for displaying relative time
    return "just now";
}
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
