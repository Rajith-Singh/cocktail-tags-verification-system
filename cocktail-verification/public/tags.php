<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/TagManager.php';

requireLogin();

$tagManager = new TagManager();
$auth = new Auth();
$expert = $auth->getCurrentExpert();
$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';

$tags = !empty($search) 
    ? $tagManager->searchTags($search, 100) 
    : $tagManager->getPopularTags(100);

$categories = $tagManager->getTagCategories();

$pageTitle = "Tag Library";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="glass-card p-4 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold mb-2">
                    <i class="fas fa-tags me-2"></i>Tag Library
                </h2>
                <p class="text-muted mb-0">Browse and manage all cocktail tags</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#suggestTagModal">
                    <i class="fas fa-lightbulb me-2"></i>Suggest New Tag
                </button>
            </div>
        </div>
    </div>
    
    <!-- Search & Filter -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" action="" class="d-flex gap-2">
                <input type="text" class="form-control" name="search" 
                       placeholder="Search tags..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        <div class="col-md-6">
            <select class="form-select" onchange="window.location='tags.php?category=' + this.value">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <!-- Tags List -->
    <div class="row">
        <?php if (empty($tags)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No tags found.
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($tags as $tag): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="glass-card p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($tag['tag_name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($tag['category_name'] ?? 'Uncategorized'); ?></small>
                        </div>
                        <span class="badge bg-primary"><?php echo $tag['usage_count']; ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-check-circle me-1" style="color: #10B981;"></i>
                            <?php echo $tag['usage_count']; ?> cocktails
                        </small>
                        <a href="cocktails.php?search=<?php echo urlencode($tag['tag_name']); ?>" 
                           class="btn btn-sm btn-outline-primary">
                            View
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Suggest Tag Modal -->
<div class="modal fade" id="suggestTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-lightbulb text-warning me-2"></i>Suggest New Tag
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="api/tags/suggest" id="suggestTagForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tag Name *</label>
                        <input type="text" class="form-control" name="tag_name" required 
                               placeholder="e.g., Citrusy, Tropical, Smoky">
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
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Describe what this tag means..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rationale *</label>
                        <textarea class="form-control" name="rationale" rows="3" required
                                  placeholder="Why should this tag be added to the system?"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Submit Suggestion
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
