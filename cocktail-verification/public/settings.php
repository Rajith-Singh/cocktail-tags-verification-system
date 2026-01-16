<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

requireLogin();

$auth = new Auth();
$expert = $auth->getCurrentExpert();
$pdo = getDB();
$message = '';
$messageType = '';

// Handle setting updates (admin only)
if (isAdmin() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRF($csrf_token)) {
        $message = "Invalid request";
        $messageType = "danger";
    } else {
        $setting_key = $_POST['setting_key'] ?? '';
        $setting_value = $_POST['setting_value'] ?? '';
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, updated_by) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    updated_by = VALUES(updated_by),
                    updated_at = NOW()
            ");
            
            if ($stmt->execute([$setting_key, $setting_value, $expert['id']])) {
                $message = "Setting saved successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to save setting";
                $messageType = "danger";
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Get all settings
$settings_stmt = $pdo->query("SELECT * FROM system_settings ORDER BY category, setting_key");
$settings = $settings_stmt->fetchAll();

// Group settings by category
$grouped_settings = [];
foreach ($settings as $setting) {
    $category = $setting['category'] ?? 'general';
    if (!isset($grouped_settings[$category])) {
        $grouped_settings[$category] = [];
    }
    $grouped_settings[$category][] = $setting;
}

$pageTitle = "Settings";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Settings Navigation -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <?php $first = true; ?>
                <?php foreach ($grouped_settings as $category => $category_settings): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $first ? 'active' : ''; ?>" 
                            id="<?php echo $category; ?>-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#<?php echo $category; ?>" 
                            type="button">
                        <?php echo ucfirst($category); ?>
                    </button>
                </li>
                <?php $first = false; endforeach; ?>
            </ul>
            
            <!-- Settings Tabs -->
            <div class="tab-content">
                <?php $first = true; ?>
                <?php foreach ($grouped_settings as $category => $category_settings): ?>
                <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" 
                     id="<?php echo $category; ?>" 
                     role="tabpanel">
                    <div class="glass-card p-4">
                        <h5 class="fw-bold mb-4">
                            <i class="fas fa-cog me-2"></i><?php echo ucfirst($category); ?> Settings
                        </h5>
                        
                        <?php foreach ($category_settings as $setting): ?>
                        <div class="mb-4 pb-4 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold"><?php echo htmlspecialchars($setting['setting_key']); ?></h6>
                                    <?php if ($setting['description']): ?>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($setting['description']); ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (isAdmin()): ?>
                                <form method="POST" action="" class="ms-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                                    <input type="hidden" name="setting_key" value="<?php echo htmlspecialchars($setting['setting_key']); ?>">
                                    
                                    <div class="input-group">
                                        <?php if ($setting['setting_type'] === 'boolean'): ?>
                                        <select class="form-select form-select-sm" name="setting_value" onchange="this.form.submit()">
                                            <option value="0" <?php echo $setting['setting_value'] === '0' ? 'selected' : ''; ?>>Disabled</option>
                                            <option value="1" <?php echo $setting['setting_value'] === '1' ? 'selected' : ''; ?>>Enabled</option>
                                        </select>
                                        <?php elseif ($setting['setting_type'] === 'integer'): ?>
                                        <input type="number" class="form-control form-control-sm" 
                                               name="setting_value" 
                                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                               onchange="this.form.submit()">
                                        <?php else: ?>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="setting_value" 
                                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                        <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                                <?php else: ?>
                                <span class="badge bg-secondary">
                                    <?php 
                                    if ($setting['setting_type'] === 'boolean') {
                                        echo $setting['setting_value'] === '1' ? 'Enabled' : 'Disabled';
                                    } else {
                                        echo htmlspecialchars($setting['setting_value']);
                                    }
                                    ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php $first = false; endforeach; ?>
            </div>
            
            <!-- Info Card -->
            <div class="glass-card p-4 mt-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-info-circle me-2"></i>System Information
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">Database Version</small>
                        <p class="fw-bold">1.0.0</p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Last Updated</small>
                        <p class="fw-bold"><?php echo date('F d, Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
