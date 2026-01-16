<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/CocktailManager.php';

requireLogin();

$cocktailManager = new CocktailManager();
$auth = new Auth();
$expert = $auth->getCurrentExpert();

$page = intval($_GET['page'] ?? 1);
$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$limit = 12;
$offset = ($page - 1) * $limit;

// Get cocktails based on filters
if (!empty($search)) {
    $cocktails = $cocktailManager->searchCocktails($search, $limit);
} elseif (!empty($status)) {
    $cocktails = $cocktailManager->getCocktailsByStatus($status, $limit, $offset);
} else {
    $cocktails = $cocktailManager->getCocktailsByStatus(COCKTAIL_PENDING, $limit, $offset);
}

$stats = $cocktailManager->getCocktailStats();
$pageTitle = "All Cocktails";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="glass-card p-4 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold mb-2">
                    <i class="fas fa-glass-martini me-2"></i>Cocktail Library
                </h2>
                <p class="text-muted mb-0">Browse and verify tags for all cocktails</p>
            </div>
            <div class="col-md-4 text-end">
                <form method="GET" action="" class="d-flex gap-2">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search cocktails..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <div class="glass-card p-3 mb-4">
        <ul class="nav nav-pills" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo empty($status) ? 'active' : ''; ?>" 
                   href="cocktails.php">
                    <i class="fas fa-list me-1"></i>All (<?php echo $stats['total']; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $status === 'pending' ? 'active' : ''; ?>" 
                   href="cocktails.php?status=pending">
                    <i class="fas fa-clock me-1"></i>Pending (<?php echo $stats['pending']; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $status === 'partially_verified' ? 'active' : ''; ?>" 
                   href="cocktails.php?status=partially_verified">
                    <i class="fas fa-hourglass-half me-1"></i>Partial (<?php echo $stats['partially_verified']; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $status === 'fully_verified' ? 'active' : ''; ?>" 
                   href="cocktails.php?status=fully_verified">
                    <i class="fas fa-check-circle me-1"></i>Verified (<?php echo $stats['fully_verified']; ?>)
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Cocktails Grid -->
    <div class="row mb-4">
        <?php if (empty($cocktails)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No cocktails found matching your criteria.
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($cocktails as $cocktail): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <a href="verify.php?cocktail=<?php echo $cocktail['id']; ?>" class="text-decoration-none">
                    <div class="cocktail-card glass-card h-100">
                        <div class="position-relative">
                            <?php if ($cocktail['strDrinkThumb']): ?>
                            <img src="<?php echo htmlspecialchars($cocktail['strDrinkThumb']); ?>" 
                                 class="cocktail-image" 
                                 alt="<?php echo htmlspecialchars($cocktail['strDrink']); ?>"
                                 onerror="this.src='https://via.placeholder.com/300x200/667eea/ffffff?text=Cocktail'">
                            <?php else: ?>
                            <div class="cocktail-image d-flex align-items-center justify-content-center" 
                                 style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="fas fa-glass-martini-alt fa-5x text-white"></i>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Verification Badge -->
                            <span class="verification-badge" 
                                  style="background: <?php echo $cocktail['verification_status'] === 'fully_verified' ? '#10B98120' : ($cocktail['verification_status'] === 'partially_verified' ? '#F59E0B20' : '#EF444420'); ?>; color: <?php echo $cocktail['verification_status'] === 'fully_verified' ? '#10B981' : ($cocktail['verification_status'] === 'partially_verified' ? '#F59E0B' : '#EF4444'); ?>;">
                                <i class="fas fa-<?php echo $cocktail['verification_status'] === 'fully_verified' ? 'check-circle' : ($cocktail['verification_status'] === 'partially_verified' ? 'exclamation-circle' : 'clock'); ?> me-1"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $cocktail['verification_status'])); ?>
                            </span>
                        </div>
                        
                        <div class="p-3">
                            <h6 class="fw-bold mb-2 text-dark"><?php echo htmlspecialchars($cocktail['strDrink']); ?></h6>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">Verification Progress</small>
                                    <small class="fw-bold"><?php echo $cocktail['verified_tags']; ?>/<?php echo $cocktail['total_tags']; ?></small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" 
                                         style="width: <?php echo ($cocktail['total_tags'] > 0) ? ($cocktail['verified_tags'] / $cocktail['total_tags'] * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">
                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($cocktail['strCategory']); ?>
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-glass me-1"></i><?php echo htmlspecialchars($cocktail['strGlass']); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
