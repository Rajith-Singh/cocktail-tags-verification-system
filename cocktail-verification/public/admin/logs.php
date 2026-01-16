<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Auth.php';

requireAdmin();

$auth = new Auth();
$expert = $auth->getCurrentExpert();
$pdo = getDB();

$page = intval($_GET['page'] ?? 1);
$action_type = $_GET['action_type'] ?? '';
$expert_filter = $_GET['expert'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$limit = 50;
$offset = ($page - 1) * $limit;

// Build query
$sql = "
    SELECT 
        vl.*,
        e.username as expert_username,
        e.full_name as expert_name,
        c.strDrink as cocktail_name,
        t.tag_name
    FROM verification_logs vl
    LEFT JOIN experts e ON vl.expert_id = e.id
    LEFT JOIN cocktails c ON vl.cocktail_id = c.id
    LEFT JOIN tags t ON vl.tag_id = t.id
    WHERE 1=1
";

$params = [];

if (!empty($action_type)) {
    $sql .= " AND vl.action_type = ?";
    $params[] = $action_type;
}

if (!empty($expert_filter)) {
    $sql .= " AND vl.expert_id = ?";
    $params[] = $expert_filter;
}

if (!empty($date_from)) {
    $sql .= " AND DATE(vl.performed_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND DATE(vl.performed_at) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY vl.performed_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM verification_logs vl WHERE 1=1";
if (!empty($action_type)) $count_sql .= " AND vl.action_type = ?";
if (!empty($expert_filter)) $count_sql .= " AND vl.expert_id = ?";
if (!empty($date_from)) $count_sql .= " AND DATE(vl.performed_at) >= ?";
if (!empty($date_to)) $count_sql .= " AND DATE(vl.performed_at) <= ?";

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute(array_slice($params, 0, -2));
$total = $count_stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// Get unique experts for filter
$experts_stmt = $pdo->query("SELECT id, username, full_name FROM experts ORDER BY username");
$experts = $experts_stmt->fetchAll();

$pageTitle = "Verification Logs";
include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <div class="glass-card p-4 mb-4">
        <h2 class="fw-bold mb-3">
            <i class="fas fa-history me-2"></i>Verification Logs
        </h2>
        
        <!-- Filters -->
        <form method="GET" action="" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label fw-bold">Action Type</label>
                <select class="form-select" name="action_type">
                    <option value="">All Actions</option>
                    <option value="verify_tag" <?php echo $action_type === 'verify_tag' ? 'selected' : ''; ?>>Verify Tag</option>
                    <option value="reject_tag" <?php echo $action_type === 'reject_tag' ? 'selected' : ''; ?>>Reject Tag</option>
                    <option value="add_tag" <?php echo $action_type === 'add_tag' ? 'selected' : ''; ?>>Add Tag</option>
                    <option value="remove_tag" <?php echo $action_type === 'remove_tag' ? 'selected' : ''; ?>>Remove Tag</option>
                    <option value="dispute_tag" <?php echo $action_type === 'dispute_tag' ? 'selected' : ''; ?>>Dispute Tag</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label fw-bold">Expert</label>
                <select class="form-select" name="expert">
                    <option value="">All Experts</option>
                    <?php foreach ($experts as $exp): ?>
                    <option value="<?php echo $exp['id']; ?>" <?php echo $expert_filter == $exp['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($exp['full_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-bold">From</label>
                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-bold">To</label>
                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
            </div>
        </form>
        
        <!-- Results Summary -->
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>
            Showing <strong><?php echo count($logs); ?></strong> of <strong><?php echo $total; ?></strong> logs
        </div>
        
        <!-- Logs Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Expert</th>
                        <th>Action</th>
                        <th>Cocktail</th>
                        <th>Tag</th>
                        <th>Details</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                            No logs found
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <small class="text-muted">
                                    <?php echo date('M d, Y H:i', strtotime($log['performed_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['expert_name']); ?></strong>
                                <br>
                                <small class="text-muted">@<?php echo htmlspecialchars($log['expert_username']); ?></small>
                            </td>
                            <td>
                                <?php
                                $badge_class = match($log['action_type']) {
                                    'verify_tag' => 'success',
                                    'reject_tag' => 'danger',
                                    'add_tag' => 'info',
                                    'remove_tag' => 'warning',
                                    'dispute_tag' => 'secondary',
                                    default => 'light'
                                };
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $log['action_type'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($log['cocktail_name']): ?>
                                <a href="<?php echo SITE_URL; ?>verify.php?cocktail=<?php echo $log['cocktail_id']; ?>" 
                                   class="text-decoration-none" target="_blank">
                                    <?php echo htmlspecialchars($log['cocktail_name']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['tag_name']): ?>
                                <code><?php echo htmlspecialchars($log['tag_name']); ?></code>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small>
                                    <?php if (!empty($log['new_value'])): ?>
                                    New: <code><?php echo htmlspecialchars(substr($log['new_value'], 0, 30)); ?></code>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($log['notes']): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                        data-bs-toggle="tooltip" 
                                        title="<?php echo htmlspecialchars($log['notes']); ?>">
                                    <i class="fas fa-sticky-note"></i>
                                </button>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&action_type=<?php echo urlencode($action_type); ?>&expert=<?php echo urlencode($expert_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
