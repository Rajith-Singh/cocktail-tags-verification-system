<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

requireLogin();

$auth = new Auth();
$expert = $auth->getCurrentExpert();
$pdo = getDB();

$page = intval($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Mark notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'mark_read') {
    $notification_id = $_POST['notification_id'] ?? null;
    
    if ($notification_id) {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE, read_at = NOW() 
            WHERE id = ? AND expert_id = ?
        ");
        $stmt->execute([$notification_id, $expert['id']]);
    }
}

// Get notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE expert_id = ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$expert['id'], $limit, $offset]);
$notifications = $stmt->fetchAll();

// Get unread count
$unread_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE expert_id = ? AND is_read = FALSE");
$unread_stmt->execute([$expert['id']]);
$unread_count = $unread_stmt->fetch()['count'];

// Get total count
$total_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE expert_id = ?");
$total_stmt->execute([$expert['id']]);
$total = $total_stmt->fetch()['count'];
$total_pages = ceil($total / $limit);

$pageTitle = "Notifications";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="glass-card p-4 mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h2 class="fw-bold mb-0">
                            <i class="fas fa-bell me-2"></i>Notifications
                        </h2>
                    </div>
                    <div class="col-auto">
                        <?php if ($unread_count > 0): ?>
                        <span class="badge bg-danger"><?php echo $unread_count; ?> Unread</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Notifications List -->
            <div class="notification-list">
                <?php if (empty($notifications)): ?>
                <div class="glass-card p-5 text-center">
                    <i class="fas fa-inbox fa-5x text-muted mb-3"></i>
                    <h4>No Notifications</h4>
                    <p class="text-muted">You're all caught up!</p>
                </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                    <div class="glass-card p-4 mb-3 <?php echo !$notification['is_read'] ? 'border-primary border-left' : ''; ?>" 
                         style="<?php echo !$notification['is_read'] ? 'border-left: 4px solid var(--primary);' : ''; ?>">
                        <div class="row align-items-start">
                            <div class="col">
                                <div class="d-flex align-items-center mb-2">
                                    <?php
                                    $icon = match($notification['type']) {
                                        'tag_assigned' => 'fa-tasks',
                                        'verification_completed' => 'fa-check-circle',
                                        'comment_reply' => 'fa-comment',
                                        'system_announcement' => 'fa-megaphone',
                                        'achievement_unlocked' => 'fa-star',
                                        default => 'fa-bell'
                                    };
                                    ?>
                                    <i class="fas <?php echo $icon; ?> me-2" style="color: var(--primary);"></i>
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                    <?php if (!$notification['is_read']): ?>
                                    <span class="badge bg-primary ms-auto">New</span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="text-muted mb-2">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </p>
                                
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo timeAgo($notification['created_at']); ?>
                                </small>
                            </div>
                            <div class="col-auto">
                                <?php if (!$notification['is_read']): ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-check"></i> Mark Read
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
