<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/TagManager.php';

requireAdmin();

$auth = new Auth();
$tagManager = new TagManager();
$pdo = getDB();
$message = '';
$messageType = '';

// Handle tag actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRF($csrf_token)) {
        $message = "Invalid request";
        $messageType = "danger";
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            if ($action === 'approve') {
                $suggestion_id = $_POST['suggestion_id'] ?? null;
                $category_id = $_POST['category_id'] ?? null;
                $current_expert = $auth->getCurrentExpert();
                
                // Get suggestion
                $stmt = $pdo->prepare("SELECT * FROM tag_suggestions WHERE id = ?");
                $stmt->execute([$suggestion_id]);
                $suggestion = $stmt->fetch();
                
                // Create tag
                $slug = strtolower(str_replace(' ', '-', $suggestion['tag_name']));
                $insert_stmt = $pdo->prepare("
                    INSERT INTO tags (tag_name, slug, category_id, description, created_by) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $insert_stmt->execute([
                    $suggestion['tag_name'],
                    $slug,
                    $category_id,
                    $suggestion['description'],
                    $current_expert['id']
                ]);
                
                $tag_id = $pdo->lastInsertId();
                
                // Update suggestion
                $update_stmt = $pdo->prepare("
                    UPDATE tag_suggestions 
                    SET status = 'approved', approved_tag_id = ?, reviewed_by = ?, reviewed_at = NOW()
                    WHERE id = ?
                ");
                
                $update_stmt->execute([$tag_id, $current_expert['id'], $suggestion_id]);
                
                $message = "Tag suggestion approved and created successfully!";
                $messageType = "success";
                
            } elseif ($action === 'reject') {
                $suggestion_id = $_POST['suggestion_id'] ?? null;
                $rejection_reason = $_POST['rejection_reason'] ?? '';
                $current_expert = $auth->getCurrentExpert();
                
                $stmt = $pdo->prepare("
                    UPDATE tag_suggestions 
                    SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_notes = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([$current_expert['id'], $rejection_reason, $suggestion_id]);
                
                $message = "Tag suggestion rejected";
                $messageType = "warning";
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Get pending suggestions
$suggestions_stmt = $pdo->query("
    SELECT 
        ts.*,
        e.full_name as suggested_by_name,
        e.username as suggested_by_username,
        tc.name as category_name,
        ra.full_name as reviewed_by_name
    FROM tag_suggestions ts
    LEFT JOIN experts e ON ts.suggested_by = e.id
    LEFT JOIN tag_categories tc ON ts.category_id = tc.id
    LEFT JOIN experts ra ON ts.reviewed_by = ra.id
    ORDER BY 
        CASE WHEN ts.status = 'pending' THEN 0 ELSE 1 END,
        ts.suggested_at DESC
");
$suggestions = $suggestions_stmt->fetchAll();

// Get categories
$categories = $tagManager->getTagCategories();

$pageTitle = "Tag Suggestions";
include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid">
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-2"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Header -->
    <div class="glass-card p-4 mb-4">
        <h2 class="fw-bold mb-2">
            <i class="fas fa-lightbulb me-2"></i>Tag Suggestions
        </h2>
        <p class="text-muted mb-0">Review and manage tag suggestions from experts</p>
    </div>
    
    <!-- Suggestions -->
    <div class="row">
        <?php foreach ($suggestions as $suggestion): ?>
        <div class="col-md-6 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="flex-grow-1">
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($suggestion['tag_name']); ?></h5>
                        <small class="text-muted">by <?php echo htmlspecialchars($suggestion['suggested_by_name']); ?></small>
                    </div>
                    
                    <?php if ($suggestion['status'] === 'pending'): ?>
                    <span class="badge bg-warning">Pending</span>
                    <?php elseif ($suggestion['status'] === 'approved'): ?>
                    <span class="badge bg-success">Approved</span>
                    <?php elseif ($suggestion['status'] === 'rejected'): ?>
                    <span class="badge bg-danger">Rejected</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <p class="mb-2"><strong>Category:</strong> <?php echo htmlspecialchars($suggestion['category_name'] ?? 'Uncategorized'); ?></p>
                    
                    <?php if ($suggestion['description']): ?>
                    <p class="mb-2">
                        <strong>Description:</strong>
                        <br>
                        <small><?php echo nl2br(htmlspecialchars($suggestion['description'])); ?></small>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($suggestion['rationale']): ?>
                    <p class="mb-0">
                        <strong>Rationale:</strong>
                        <br>
                        <small><?php echo nl2br(htmlspecialchars($suggestion['rationale'])); ?></small>
                    </p>
                    <?php endif; ?>
                </div>
                
                <?php if ($suggestion['status'] === 'pending'): ?>
                <div class="btn-group w-100">
                    <button type="button" class="btn btn-outline-success" 
                            data-bs-toggle="modal" 
                            data-bs-target="#approveSuggestionModal"
                            data-suggestion-id="<?php echo $suggestion['id']; ?>"
                            data-suggestion-name="<?php echo htmlspecialchars($suggestion['tag_name']); ?>"
                            data-category-id="<?php echo $suggestion['category_id']; ?>">
                        <i class="fas fa-check me-1"></i>Approve
                    </button>
                    <button type="button" class="btn btn-outline-danger" 
                            data-bs-toggle="modal" 
                            data-bs-target="#rejectSuggestionModal"
                            data-suggestion-id="<?php echo $suggestion['id']; ?>"
                            data-suggestion-name="<?php echo htmlspecialchars($suggestion['tag_name']); ?>">
                        <i class="fas fa-times me-1"></i>Reject
                    </button>
                </div>
                <?php elseif ($suggestion['status'] === 'rejected' && $suggestion['review_notes']): ?>
                <div class="alert alert-danger small mb-0">
                    <strong>Rejection Reason:</strong>
                    <br>
                    <?php echo htmlspecialchars($suggestion['review_notes']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Approve Suggestion Modal -->
<div class="modal fade" id="approveSuggestionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-check-circle text-success me-2"></i>Approve Suggestion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="suggestion_id" id="approveSuggestionId">
                    
                    <div class="mb-3">
                        <p class="text-muted mb-2">Tag Name:</p>
                        <h5 class="fw-bold" id="approveSuggestionName"></h5>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category *</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Select a category...</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Suggestion Modal -->
<div class="modal fade" id="rejectSuggestionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-times-circle text-danger me-2"></i>Reject Suggestion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="suggestion_id" id="rejectSuggestionId">
                    
                    <div class="mb-3">
                        <p class="text-muted mb-2">Tag Name:</p>
                        <h5 class="fw-bold" id="rejectSuggestionName"></h5>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rejection Reason</label>
                        <textarea class="form-control" name="rejection_reason" rows="3" required
                                  placeholder="Explain why you're rejecting this suggestion..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('approveSuggestionModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    document.getElementById('approveSuggestionId').value = button.getAttribute('data-suggestion-id');
    document.getElementById('approveSuggestionName').textContent = button.getAttribute('data-suggestion-name');
});

document.getElementById('rejectSuggestionModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    document.getElementById('rejectSuggestionId').value = button.getAttribute('data-suggestion-id');
    document.getElementById('rejectSuggestionName').textContent = button.getAttribute('data-suggestion-name');
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
