<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/CocktailManager.php';
require_once __DIR__ . '/../includes/TagManager.php';

requireLogin();

$auth = new Auth();
$cocktailManager = new CocktailManager();
$tagManager = new TagManager();

$expert = $auth->getCurrentExpert();
$cocktailId = $_GET['cocktail'] ?? null;
$message = '';
$messageType = '';

// Handle tag actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRF($csrf_token)) {
        $message = "Invalid request. Please try again.";
        $messageType = "danger";
    } else {
        $action = $_POST['action'] ?? '';
        $cocktailTagId = $_POST['cocktail_tag_id'] ?? null;
        $newTag = trim($_POST['new_tag'] ?? '');
        $categoryId = $_POST['category_id'] ?? null;
        $confidence = intval($_POST['confidence'] ?? 100);
        $reason = $_POST['reason'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        
        try {
            switch ($action) {
                case 'verify':
                    $result = $tagManager->verifyTag($cocktailTagId, $expert['id'], $confidence, $notes);
                    $message = $result['message'];
                    $messageType = 'success';
                    break;
                    
                case 'reject':
                    $result = $tagManager->rejectTag($cocktailTagId, $expert['id'], $reason, $_POST['custom_reason'] ?? '', $notes);
                    $message = $result['message'];
                    $messageType = 'warning';
                    break;
                    
                case 'add':
                    if (empty($newTag)) {
                        throw new Exception("Tag name is required");
                    }
                    $result = $tagManager->addTagToCocktail($cocktailId, $newTag, $expert['id'], $categoryId, $confidence);
                    $message = $result['message'];
                    $messageType = 'success';
                    break;
                    
                case 'remove':
                    $result = $tagManager->removeTagFromCocktail($cocktailTagId, $expert['id'], $notes);
                    $message = $result['message'];
                    $messageType = 'warning';
                    break;
                    
                default:
                    throw new Exception("Invalid action");
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get cocktail to verify
if ($cocktailId) {
    $cocktail = $cocktailManager->getCocktail($cocktailId);
} else {
    // Get random cocktail
    $cocktail = $cocktailManager->getRandomCocktailForVerification($expert['id']);
    if ($cocktail) {
        header("Location: verify.php?cocktail=" . $cocktail['id']);
        exit();
    }
}

if (!$cocktail) {
    $message = "All cocktails are verified! Great work!";
    $messageType = "success";
}

$pageTitle = "Verify Tags";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <!-- Action Messages -->
    <?php if (isset($message) && !empty($message)): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (empty($cocktail)): ?>
    <!-- No cocktails to verify -->
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="glass-card p-5 text-center mt-5">
                <div class="mb-4">
                    <i class="fas fa-check-circle fa-5x text-success"></i>
                </div>
                <h2 class="fw-bold mb-3">All Caught Up! 🎉</h2>
                <p class="text-muted mb-4">All cocktails have been verified. You're amazing!</p>
                <div class="d-grid gap-2 col-md-8 mx-auto">
                    <a href="cocktails.php" class="btn btn-primary">
                        <i class="fas fa-glass-martini me-2"></i>Browse All Cocktails
                    </a>
                    <a href="export.php" class="btn btn-outline-primary">
                        <i class="fas fa-download me-2"></i>Export Verified Data
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Cocktail Verification Interface -->
    <div class="row">
        <!-- Left Column: Cocktail Details -->
        <div class="col-lg-8 mb-4">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <div class="d-flex align-items-center mb-2">
                            <h1 class="fw-bold mb-0 me-3"><?php echo h($cocktail['strDrink']); ?></h1>
                            <span class="verification-badge" style="background: 
                                <?php echo $cocktail['verification_status'] === 'fully_verified' ? '#10B98120' : 
                                        ($cocktail['verification_status'] === 'partially_verified' ? '#F59E0B20' : '#EF444420'); ?>; 
                                color: <?php echo $cocktail['verification_status'] === 'fully_verified' ? '#10B981' : 
                                        ($cocktail['verification_status'] === 'partially_verified' ? '#F59E0B' : '#EF4444'); ?>;">
                                <i class="fas fa-<?php echo $cocktail['verification_status'] === 'fully_verified' ? 'check-circle' : 
                                                  ($cocktail['verification_status'] === 'partially_verified' ? 'exclamation-circle' : 'clock'); ?> me-1"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $cocktail['verification_status'])); ?>
                            </span>
                        </div>
                        <div class="d-flex flex-wrap gap-3">
                            <?php if (!empty($cocktail['strCategory'])): ?>
                            <span class="text-muted">
                                <i class="fas fa-tag me-1"></i><?php echo h($cocktail['strCategory']); ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($cocktail['strAlcoholic'])): ?>
                            <span class="text-muted">
                                <i class="fas fa-wine-bottle me-1"></i><?php echo h($cocktail['strAlcoholic']); ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($cocktail['strGlass'])): ?>
                            <span class="text-muted">
                                <i class="fas fa-glass me-1"></i><?php echo h($cocktail['strGlass']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <a href="verify.php" class="btn btn-outline-primary">
                            <i class="fas fa-dice me-2"></i>Random
                        </a>
                    </div>
                </div>
                
                <!-- Cocktail Image & Video -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="cocktail-card">
                            <?php if (!empty($cocktail['strDrinkThumb'])): ?>
                            <img src="<?php echo h($cocktail['strDrinkThumb']); ?>" 
                                 class="cocktail-image rounded" 
                                 alt="<?php echo h($cocktail['strDrink']); ?>"
                                 onerror="this.src='https://via.placeholder.com/600x400/667eea/ffffff?text=Cocktail'"
                                 style="width: 100%; height: 400px; object-fit: cover;">
                            <?php else: ?>
                            <div class="cocktail-image d-flex align-items-center justify-content-center rounded" 
                                 style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 400px;">
                                <i class="fas fa-glass-martini-alt fa-5x text-white"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <?php if (!empty($cocktail['strVideo'])): ?>
                        <div class="mb-3">
                            <h5 class="fw-bold mb-2">
                                <i class="fas fa-video me-2"></i>Video Tutorial
                            </h5>
                            <div class="ratio ratio-16x9">
                                <iframe src="https://www.youtube.com/embed/<?php echo getYouTubeId($cocktail['strVideo']); ?>" 
                                        title="Cocktail Video" 
                                        allowfullscreen></iframe>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Ingredients -->
                        <div class="mb-3">
                            <h5 class="fw-bold mb-2">
                                <i class="fas fa-list-ul me-2"></i>Ingredients
                            </h5>
                            <div class="ingredients-list">
                                <?php foreach ($cocktail['ingredients'] as $ingredient): ?>
                                <div class="ingredient-chip">
                                    <span class="fw-bold"><?php echo h($ingredient['measure']); ?></span>
                                    <span class="ms-2"><?php echo h($ingredient['ingredient']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <?php if (!empty($cocktail['strInstructions'])): ?>
                <div class="mb-4">
                    <h5 class="fw-bold mb-2">
                        <i class="fas fa-list-ol me-2"></i>Instructions
                    </h5>
                    <div class="glass-card p-3" style="background: #F9FAFB;">
                        <p class="mb-0"><?php echo nl2br(h($cocktail['strInstructions'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Column: Tag Verification -->
        <div class="col-lg-4 mb-4">
            <!-- Original Tags -->
            <div class="glass-card p-4 mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-history me-2"></i>Original Tags
                </h5>
                <div class="mb-3">
                    <div class="glass-card p-3" style="background: #F9FAFB;">
                        <p class="mb-0 text-muted small"><?php echo h($cocktail['original_tags'] ?? 'No tags'); ?></p>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Parsed from CSV file</small>
                    <button class="btn btn-sm btn-outline-secondary" 
                            onclick="copyToClipboard('<?php echo h($cocktail['original_tags'] ?? ''); ?>')">
                        <i class="fas fa-copy me-1"></i>Copy
                    </button>
                </div>
            </div>
            
            <!-- Current Tags -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-tags me-2"></i>Current Tags
                    </h5>
                    <span class="badge bg-primary"><?php echo count($cocktail['all_tags'] ?? []); ?></span>
                </div>
                
                <!-- Verified Tags -->
                <?php if (!empty($cocktail['verified_tags'])): ?>
                <h6 class="fw-bold text-success mb-2">
                    <i class="fas fa-check-circle me-1"></i>Verified (<?php echo count($cocktail['verified_tags']); ?>)
                </h6>
                <div class="mb-3" style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($cocktail['verified_tags'] as $tag): ?>
                    <div class="tag-badge mb-2" 
                         style="background: <?php echo $tag['color_code'] ?? '#10B981'; ?>20; 
                                color: <?php echo $tag['color_code'] ?? '#10B981'; ?>; 
                                border: 1px solid <?php echo $tag['color_code'] ?? '#10B981'; ?>30;">
                        <div class="flex-grow-1">
                            <span><?php echo h($tag['tag_name']); ?></span>
                            <small class="d-block text-muted"><?php echo h($tag['category'] ?? 'Uncategorized'); ?></small>
                        </div>
                        <div class="d-flex align-items-center ms-2">
                            <small class="me-2"><?php echo $tag['confidence_score']; ?>%</small>
                            <button class="btn btn-sm btn-outline-danger p-0" 
                                    style="width: 20px; height: 20px;"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#removeTagModal"
                                    data-tag-id="<?php echo $tag['id']; ?>"
                                    data-tag-name="<?php echo h($tag['tag_name']); ?>">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Pending Tags -->
                <?php if (!empty($cocktail['pending_tags'])): ?>
                <h6 class="fw-bold text-warning mb-2">
                    <i class="fas fa-hourglass-half me-1"></i>Pending (<?php echo count($cocktail['pending_tags']); ?>)
                </h6>
                <div class="mb-3" style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($cocktail['pending_tags'] as $tag): ?>
                    <div class="tag-badge mb-2" 
                         style="background: <?php echo $tag['color_code'] ?? '#F59E0B'; ?>20; 
                                color: <?php echo $tag['color_code'] ?? '#F59E0B'; ?>; 
                                border: 1px solid <?php echo $tag['color_code'] ?? '#F59E0B'; ?>30;">
                        <div class="flex-grow-1">
                            <span><?php echo h($tag['tag_name']); ?></span>
                            <small class="d-block text-muted"><?php echo h($tag['category'] ?? 'Uncategorized'); ?></small>
                        </div>
                        <div class="d-flex align-items-center ms-2">
                            <button class="btn btn-sm btn-outline-success p-0 me-1" 
                                    style="width: 20px; height: 20px;"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#verifyTagModal"
                                    data-tag-id="<?php echo $tag['id']; ?>"
                                    data-tag-name="<?php echo h($tag['tag_name']); ?>">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger p-0" 
                                    style="width: 20px; height: 20px;"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#rejectTagModal"
                                    data-tag-id="<?php echo $tag['id']; ?>"
                                    data-tag-name="<?php echo h($tag['tag_name']); ?>">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Add New Tag Button -->
                <button class="btn btn-outline-primary w-100" 
                        data-bs-toggle="modal" 
                        data-bs-target="#addTagModal">
                    <i class="fas fa-plus me-2"></i>Add New Tag
                </button>
            </div>
            
            <!-- Quick Stats -->
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-chart-bar me-2"></i>Progress
                </h5>
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="rounded-circle p-3 mx-auto" style="background: #10B98120; width: 70px; height: 70px;">
                            <i class="fas fa-check-circle fa-2x" style="color: #10B981;"></i>
                        </div>
                        <h4 class="fw-bold mt-2"><?php echo count($cocktail['verified_tags'] ?? []); ?></h4>
                        <small class="text-muted">Verified</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="rounded-circle p-3 mx-auto" style="background: #F59E0B20; width: 70px; height: 70px;">
                            <i class="fas fa-clock fa-2x" style="color: #F59E0B;"></i>
                        </div>
                        <h4 class="fw-bold mt-2"><?php echo count($cocktail['pending_tags'] ?? []); ?></h4>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modals for Tag Actions -->
<?php include __DIR__ . '/../templates/modal.php'; ?>

<script>
// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
}

// Show toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    const container = document.getElementById('toastContainer') || createToastContainer();
    container.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    setTimeout(() => toast.remove(), 3000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(container);
    return container;
}

// Handle modal data
document.addEventListener('DOMContentLoaded', function() {
    const verifyTagModal = document.getElementById('verifyTagModal');
    if (verifyTagModal) {
        verifyTagModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const tagId = button.getAttribute('data-tag-id');
            const tagName = button.getAttribute('data-tag-name');
            const modal = this;
            modal.querySelector('#verifyTagId').value = tagId;
            modal.querySelector('#verifyTagName').textContent = tagName;
        });
    }
    
    const rejectTagModal = document.getElementById('rejectTagModal');
    if (rejectTagModal) {
        rejectTagModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const tagId = button.getAttribute('data-tag-id');
            const tagName = button.getAttribute('data-tag-name');
            const modal = this;
            modal.querySelector('#rejectTagId').value = tagId;
            modal.querySelector('#rejectTagName').textContent = tagName;
        });
    }
    
    const removeTagModal = document.getElementById('removeTagModal');
    if (removeTagModal) {
        removeTagModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const tagId = button.getAttribute('data-tag-id');
            const tagName = button.getAttribute('data-tag-name');
            const modal = this;
            modal.querySelector('#removeTagId').value = tagId;
            modal.querySelector('#removeTagName').textContent = tagName;
        });
    }
});

function getYouTubeId(url) {
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? match[2] : null;
}
</script>

<style>
.toast-container {
    z-index: 9999;
}

.ingredients-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.ingredient-chip {
    background: var(--light);
    border-radius: 20px;
    padding: 8px 15px;
    border: 1px solid #E5E7EB;
    font-size: 14px;
}

.tag-badge {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 8px;
    transition: var(--transition);
}

.tag-badge:hover {
    transform: translateY(-2px);
}
</style>

<?php include __DIR__ . '/../templates/footer.php'; ?>