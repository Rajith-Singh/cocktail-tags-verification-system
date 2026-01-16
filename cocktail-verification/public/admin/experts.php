<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Auth.php';

requireAdmin();

$auth = new Auth();
$pdo = getDB();
$message = '';
$messageType = '';

// Handle expert actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRF($csrf_token)) {
        $message = "Invalid request";
        $messageType = "danger";
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            if ($action === 'toggle_status') {
                $expert_id = $_POST['expert_id'] ?? null;
                $is_active = $_POST['is_active'] ?? 0;
                
                $stmt = $pdo->prepare("UPDATE experts SET is_active = ? WHERE id = ?");
                if ($stmt->execute([!$is_active, $expert_id])) {
                    $message = "Expert status updated successfully!";
                    $messageType = "success";
                }
            } elseif ($action === 'set_badge') {
                $expert_id = $_POST['expert_id'] ?? null;
                $badge = $_POST['badge'] ?? 'none';
                
                $stmt = $pdo->prepare("UPDATE experts SET verification_badge = ? WHERE id = ?");
                if ($stmt->execute([$badge, $expert_id])) {
                    $message = "Badge updated successfully!";
                    $messageType = "success";
                }
            } elseif ($action === 'reset_password') {
                $expert_id = $_POST['expert_id'] ?? null;
                $temp_password = bin2hex(random_bytes(4));
                $hashed = password_hash($temp_password, PASSWORD_BCRYPT);
                
                $stmt = $pdo->prepare("UPDATE experts SET password_hash = ? WHERE id = ?");
                if ($stmt->execute([$hashed, $expert_id])) {
                    $message = "Temporary password: <code>$temp_password</code>";
                    $messageType = "success";
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Get all experts
$experts_stmt = $pdo->query("
    SELECT 
        e.*,
        COUNT(DISTINCT vl.id) as total_verifications,
        COUNT(DISTINCT CASE WHEN vl.action_type = 'verify_tag' THEN vl.id END) as tags_verified,
        COUNT(DISTINCT ts.id) as suggestions_made
    FROM experts e
    LEFT JOIN verification_logs vl ON e.id = vl.expert_id
    LEFT JOIN tag_suggestions ts ON e.id = ts.suggested_by AND ts.status = 'pending'
    GROUP BY e.id
    ORDER BY e.created_at DESC
");
$experts = $experts_stmt->fetchAll();

$pageTitle = "Manage Experts";
include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Header -->
    <div class="glass-card p-4 mb-4">
        <h2 class="fw-bold mb-2">
            <i class="fas fa-users me-2"></i>Expert Management
        </h2>
        <p class="text-muted mb-0">Manage all registered experts and their permissions</p>
    </div>
    
    <!-- Experts Table -->
    <div class="glass-card p-4">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Level</th>
                        <th>Status</th>
                        <th>Verifications</th>
                        <th>Badge</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($experts as $expert): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($expert['full_name']); ?></strong>
                            <br>
                            <small class="text-muted">@<?php echo htmlspecialchars($expert['username']); ?></small>
                        </td>
                        <td>
                            <small><?php echo htmlspecialchars($expert['email']); ?></small>
                        </td>
                        <td>
                            <span class="badge" style="background: <?php echo $expertLevels[$expert['expertise_level']]['color']; ?>;">
                                <?php echo $expertLevels[$expert['expertise_level']]['name']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($expert['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo $expert['tags_verified']; ?></strong> verified
                            <br>
                            <small class="text-muted"><?php echo $expert['total_verifications']; ?> total</small>
                        </td>
                        <td>
                            <?php if ($expert['verification_badge'] !== 'none'): ?>
                            <span class="badge bg-warning">
                                <i class="fas fa-star me-1"></i><?php echo ucfirst($expert['verification_badge']); ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editExpertModal"
                                        data-expert-id="<?php echo $expert['id']; ?>"
                                        data-expert-name="<?php echo htmlspecialchars($expert['full_name']); ?>"
                                        data-expert-badge="<?php echo $expert['verification_badge']; ?>"
                                        data-expert-active="<?php echo $expert['is_active']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#resetPasswordModal"
                                        data-expert-id="<?php echo $expert['id']; ?>"
                                        data-expert-name="<?php echo htmlspecialchars($expert['full_name']); ?>">
                                    <i class="fas fa-key"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Expert Modal -->
<div class="modal fade" id="editExpertModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-edit me-2"></i>Edit Expert
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="expert_id" id="modalExpertId">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Expert</label>
                        <h5 id="modalExpertName" class="mb-0"></h5>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Verification Badge</label>
                        <select class="form-select" name="badge">
                            <option value="none">None</option>
                            <option value="bronze">Bronze</option>
                            <option value="silver">Silver</option>
                            <option value="gold">Gold</option>
                            <option value="platinum">Platinum</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="expertActive" value="1">
                        <label class="form-check-label" for="expertActive">
                            Keep expert active
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-key me-2"></i>Reset Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="expert_id" id="resetExpertId">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This will generate a temporary password for <strong id="resetExpertName"></strong>
                    </div>
                    
                    <p class="text-muted">
                        A temporary password will be generated. You can share it with the expert.
                    </p>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Generate New Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit Expert Modal
document.getElementById('editExpertModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    document.getElementById('modalExpertId').value = button.getAttribute('data-expert-id');
    document.getElementById('modalExpertName').textContent = button.getAttribute('data-expert-name');
    document.getElementById('expertActive').checked = button.getAttribute('data-expert-active') == 1;
});

// Reset Password Modal
document.getElementById('resetPasswordModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    document.getElementById('resetExpertId').value = button.getAttribute('data-expert-id');
    document.getElementById('resetExpertName').textContent = button.getAttribute('data-expert-name');
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
