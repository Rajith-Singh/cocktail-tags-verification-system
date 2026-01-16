<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Auth.php';

requireAdmin();

$auth = new Auth();
$expert = $auth->getCurrentExpert();
$pdo = getDB();

// Get system stats
$stats_stmt = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM cocktails) as total_cocktails,
        (SELECT COUNT(*) FROM experts) as total_experts,
        (SELECT COUNT(*) FROM tags) as total_tags,
        (SELECT COUNT(*) FROM cocktail_tags WHERE status = 'verified') as verified_tags,
        (SELECT COUNT(*) FROM verification_logs WHERE DATE(performed_at) = CURDATE()) as today_verifications,
        (SELECT COUNT(*) FROM tag_suggestions WHERE status = 'pending') as pending_suggestions,
        (SELECT COUNT(*) FROM experts WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as active_experts_week
");
$stats = $stats_stmt->fetch();

// Get recent activity
$activity_stmt = $pdo->query("
    SELECT 
        vl.*,
        e.username,
        e.full_name,
        c.strDrink,
        t.tag_name
    FROM verification_logs vl
    LEFT JOIN experts e ON vl.expert_id = e.id
    LEFT JOIN cocktails c ON vl.cocktail_id = c.id
    LEFT JOIN tags t ON vl.tag_id = t.id
    ORDER BY vl.performed_at DESC
    LIMIT 10
");
$recent_activity = $activity_stmt->fetchAll();

// Get top contributors
$contributors_stmt = $pdo->query("
    SELECT 
        e.id,
        e.username,
        e.full_name,
        e.expertise_level,
        COUNT(DISTINCT vl.id) as total_actions,
        COUNT(DISTINCT CASE WHEN vl.action_type IN ('verify_tag', 'verify_cocktail') THEN vl.id END) as verifications,
        AVG(e.accuracy_score) as avg_accuracy
    FROM experts e
    LEFT JOIN verification_logs vl ON e.id = vl.expert_id
    WHERE e.is_active = 1
    GROUP BY e.id
    ORDER BY total_actions DESC
    LIMIT 5
");
$top_contributors = $contributors_stmt->fetchAll();

$pageTitle = "Admin Dashboard";
include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <!-- Welcome Banner -->
    <div class="glass-card p-4 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold mb-2">
                    <i class="fas fa-shield-alt me-2"></i>Admin Dashboard
                </h2>
                <p class="text-muted mb-0">System overview and management</p>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-success me-2">
                    <i class="fas fa-circle me-1"></i>System Online
                </span>
                <span class="text-muted small">Last updated: <?php echo date('H:i:s'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Key Stats -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="glass-card p-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-glass-martini-alt fa-3x" style="color: var(--primary);"></i>
                </div>
                <h3 class="fw-bold"><?php echo number_format($stats['total_cocktails']); ?></h3>
                <p class="text-muted mb-0">Total Cocktails</p>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="glass-card p-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-tags fa-3x" style="color: var(--secondary);"></i>
                </div>
                <h3 class="fw-bold"><?php echo number_format($stats['verified_tags']); ?></h3>
                <p class="text-muted mb-0">Verified Tags</p>
                <small class="text-muted">of <?php echo number_format($stats['total_tags']); ?> total</small>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="glass-card p-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-users fa-3x" style="color: var(--accent);"></i>
                </div>
                <h3 class="fw-bold"><?php echo $stats['total_experts']; ?></h3>
                <p class="text-muted mb-0">Expert Users</p>
                <small class="text-muted"><?php echo $stats['active_experts_week']; ?> active this week</small>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="glass-card p-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-check-circle fa-3x" style="color: #10B981;"></i>
                </div>
                <h3 class="fw-bold"><?php echo $stats['today_verifications']; ?></h3>
                <p class="text-muted mb-0">Today's Verifications</p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Activity -->
        <div class="col-lg-8 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-history me-2"></i>Recent Activity
                    </h5>
                    <a href="logs.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Expert</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activity as $log): ?>
                            <tr>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('H:i', strtotime($log['performed_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['full_name']); ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = match($log['action_type']) {
                                        'verify_tag' => 'success',
                                        'reject_tag' => 'danger',
                                        'add_tag' => 'info',
                                        'remove_tag' => 'warning',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?> small">
                                        <?php echo ucfirst(str_replace('_', ' ', $log['action_type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        <?php echo htmlspecialchars($log['tag_name']); ?> 
                                        • 
                                        <?php echo htmlspecialchars(substr($log['strDrink'], 0, 20)); ?>
                                    </small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Top Contributors -->
        <div class="col-lg-4 mb-4">
            <div class="glass-card p-4 h-100">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-crown me-2"></i>Top Contributors
                </h5>
                
                <div class="list-group list-group-flush">
                    <?php foreach ($top_contributors as $contributor): ?>
                    <div class="list-group-item px-0 py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($contributor['full_name']); ?>
                                </h6>
                                <small class="text-muted">
                                    @<?php echo htmlspecialchars($contributor['username']); ?>
                                </small>
                            </div>
                            <span class="badge bg-primary">
                                <?php echo $contributor['total_actions']; ?> actions
                            </span>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <?php echo $contributor['verifications']; ?> verifications • 
                                <?php echo round($contributor['avg_accuracy'], 1); ?>% accuracy
                            </small>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar bg-success" 
                                     style="width: <?php echo round($contributor['avg_accuracy'], 1); ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-toolbox me-2"></i>Quick Actions
                </h5>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="experts.php" class="text-decoration-none">
                            <div class="glass-card p-4 text-center hover-scale">
                                <i class="fas fa-users fa-2x mb-3" style="color: var(--primary);"></i>
                                <h6 class="fw-bold">Manage Experts</h6>
                                <small class="text-muted">Add, edit, or remove experts</small>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <a href="tags.php" class="text-decoration-none">
                            <div class="glass-card p-4 text-center hover-scale">
                                <i class="fas fa-tags fa-2x mb-3" style="color: var(--secondary);"></i>
                                <h6 class="fw-bold">Manage Tags</h6>
                                <small class="text-muted">Review tag suggestions</small>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <a href="logs.php" class="text-decoration-none">
                            <div class="glass-card p-4 text-center hover-scale">
                                <i class="fas fa-history fa-2x mb-3" style="color: var(--accent);"></i>
                                <h6 class="fw-bold">View Logs</h6>
                                <small class="text-muted">Verification activity logs</small>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo SITE_URL; ?>settings.php" class="text-decoration-none">
                            <div class="glass-card p-4 text-center hover-scale">
                                <i class="fas fa-cog fa-2x mb-3" style="color: #6366F1;"></i>
                                <h6 class="fw-bold">Settings</h6>
                                <small class="text-muted">System configuration</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-scale {
    transition: var(--transition);
    cursor: pointer;
}

.hover-scale:hover {
    transform: translateY(-5px);
}
</style>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
