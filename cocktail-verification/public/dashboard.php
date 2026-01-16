<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/CocktailManager.php';
require_once __DIR__ . '/../includes/TagManager.php';

requireLogin();

$auth = new Auth();
$cocktailManager = new CocktailManager();
$tagManager = new TagManager();

$expert = $auth->getCurrentExpert();
$stats = $cocktailManager->getCocktailStats();
$popularTags = $tagManager->getPopularTags(10);
$recentCocktails = $cocktailManager->getCocktailsByStatus(COCKTAIL_PENDING, 6, 0);

$pageTitle = "Dashboard";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <!-- Welcome Banner -->
    <div class="glass-card p-4 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold mb-2">
                    Welcome back, <?php echo htmlspecialchars($expert['full_name']); ?>!
                    <span class="expert-badge ms-2" style="background: <?php echo $expertLevels[$expert['expertise_level']]['color']; ?>20; color: <?php echo $expertLevels[$expert['expertise_level']]['color']; ?>;">
                        <?php echo $expertLevels[$expert['expertise_level']]['icon']; ?>
                        <?php echo $expertLevels[$expert['expertise_level']]['name']; ?>
                    </span>
                </h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-calendar-alt me-1"></i> 
                    Member since <?php echo date('F Y', strtotime($expert['created_at'])); ?> 
                    • 
                    <i class="fas fa-check-circle me-1 ms-2"></i> 
                    <?php echo number_format($expert['tags_verified']); ?> tags verified
                </p>
            </div>
            <div class="col-md-4 text-end">
                <a href="verify.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-play-circle me-2"></i>Start Verifying
                </a>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="glass-card p-4 text-center">
                <div class="d-flex justify-content-center mb-3">
                    <div class="rounded-circle p-3" style="background: rgba(139, 92, 246, 0.1);">
                        <i class="fas fa-glass-martini-alt fa-2x" style="color: var(--primary);"></i>
                    </div>
                </div>
                <h3 class="fw-bold"><?php echo number_format($stats['total']); ?></h3>
                <p class="text-muted mb-0">Total Cocktails</p>
                <div class="mt-2">
                    <span class="badge bg-success"><?php echo $stats['fully_verified']; ?> verified</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="glass-card p-4 text-center">
                <div class="d-flex justify-content-center mb-3">
                    <div class="rounded-circle p-3" style="background: rgba(16, 185, 129, 0.1);">
                        <i class="fas fa-tags fa-2x" style="color: var(--secondary);"></i>
                    </div>
                </div>
                <h3 class="fw-bold"><?php echo number_format($stats['total_tags']); ?></h3>
                <p class="text-muted mb-0">Total Tags</p>
                <div class="mt-2">
                    <span class="badge bg-success"><?php echo $stats['verified_tags']; ?> verified</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="glass-card p-4 text-center">
                <div class="d-flex justify-content-center mb-3">
                    <div class="rounded-circle p-3" style="background: rgba(245, 158, 11, 0.1);">
                        <i class="fas fa-user-check fa-2x" style="color: var(--accent);"></i>
                    </div>
                </div>
                <h3 class="fw-bold"><?php echo number_format($expert['total_verifications']); ?></h3>
                <p class="text-muted mb-0">Your Verifications</p>
                <div class="mt-2">
                    <span class="badge bg-primary">Rank: Top 10%</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="glass-card p-4 text-center">
                <div class="d-flex justify-content-center mb-3">
                    <div class="rounded-circle p-3" style="background: rgba(239, 68, 68, 0.1);">
                        <i class="fas fa-hourglass-half fa-2x" style="color: var(--danger);"></i>
                    </div>
                </div>
                <h3 class="fw-bold"><?php echo number_format($stats['pending'] + $stats['partially_verified']); ?></h3>
                <p class="text-muted mb-0">Need Verification</p>
                <div class="mt-2">
                    <a href="verify.php" class="btn btn-sm btn-danger">Verify Now</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Cocktails -->
        <div class="col-lg-8 mb-4">
            <div class="glass-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0">
                        <i class="fas fa-hourglass-start me-2"></i>Cocktails Needing Verification
                    </h4>
                    <a href="cocktails.php?status=pending" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                
                <div class="row">
                    <?php if (empty($recentCocktails)): ?>
                    <div class="col-12">
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>All cocktails are verified! Great work!
                        </div>
                    </div>
                    <?php else: ?>
                        <?php foreach ($recentCocktails as $cocktail): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <a href="verify.php?cocktail=<?php echo $cocktail['id']; ?>" class="text-decoration-none">
                                <div class="cocktail-card glass-card">
                                    <div class="position-relative">
                                        <?php if ($cocktail['strDrinkThumb']): ?>
                                        <img src="<?php echo htmlspecialchars($cocktail['strDrinkThumb']); ?>" 
                                             class="cocktail-image" 
                                             alt="<?php echo htmlspecialchars($cocktail['strDrink']); ?>"
                                             onerror="this.src='https://via.placeholder.com/300x200/667eea/ffffff?text=Cocktail'">
                                        <?php else: ?>
                                        <div class="cocktail-image d-flex align-items-center justify-content-center" 
                                             style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                            <i class="fas fa-glass-martini-alt fa-3x text-white"></i>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <span class="verification-badge bg-warning text-dark">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo $cocktail['verified_tags']; ?>/<?php echo $cocktail['total_tags']; ?>
                                        </span>
                                    </div>
                                    <div class="p-3">
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($cocktail['strDrink']); ?></h6>
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
        </div>
        
        <!-- Quick Actions & Stats -->
        <div class="col-lg-4 mb-4">
            <!-- Quick Actions -->
            <div class="glass-card p-4 mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
                <div class="d-grid gap-2">
                    <a href="verify.php" class="btn btn-primary">
                        <i class="fas fa-play-circle me-2"></i>Verify Random Cocktail
                    </a>
                    <a href="tags.php" class="btn btn-outline-primary">
                        <i class="fas fa-tags me-2"></i>Browse Tag Library
                    </a>
                    <a href="export.php" class="btn btn-outline-secondary">
                        <i class="fas fa-download me-2"></i>Export Verified Data
                    </a>
                </div>
            </div>
            
            <!-- Popular Tags -->
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-fire me-2"></i>Popular Tags
                </h5>
                <div class="tag-cloud">
                    <?php foreach ($popularTags as $tag): ?>
                    <span class="tag-badge" 
                          style="background: <?php echo $tag['color_code'] ?? '#6B7280'; ?>20; 
                                 color: <?php echo $tag['color_code'] ?? '#6B7280'; ?>; 
                                 border: 1px solid <?php echo $tag['color_code'] ?? '#6B7280'; ?>30;">
                        <?php echo htmlspecialchars($tag['tag_name']); ?>
                        <span class="badge bg-light text-dark ms-1"><?php echo $tag['usage_count']; ?></span>
                    </span>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3 text-center">
                    <a href="tags.php" class="btn btn-sm btn-outline-primary">View All Tags</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Progress Ring -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="glass-card p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="fw-bold mb-2">Verification Progress</h4>
                        <p class="text-muted">Overall database verification status</p>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle p-2 me-3" style="background: #10B98120;">
                                        <i class="fas fa-check-circle fa-lg" style="color: #10B981;"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0"><?php echo $stats['fully_verified']; ?></h5>
                                        <small class="text-muted">Fully Verified</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle p-2 me-3" style="background: #F59E0B20;">
                                        <i class="fas fa-exclamation-circle fa-lg" style="color: #F59E0B;"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0"><?php echo $stats['partially_verified']; ?></h5>
                                        <small class="text-muted">Partial</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle p-2 me-3" style="background: #EF444420;">
                                        <i class="fas fa-clock fa-lg" style="color: #EF4444;"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0"><?php echo $stats['pending']; ?></h5>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="progress-ring mx-auto">
                            <svg width="120" height="120" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="54" fill="none" stroke="#E5E7EB" stroke-width="10"/>
                                <circle class="progress-ring__circle" cx="60" cy="60" r="54" fill="none" 
                                        stroke="url(#gradient)" stroke-width="10" stroke-linecap="round"
                                        stroke-dasharray="339" 
                                        stroke-dashoffset="<?php echo 339 - (($stats['fully_verified'] / $stats['total']) * 339); ?>"/>
                                <defs>
                                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#8B5CF6"/>
                                        <stop offset="100%" stop-color="#10B981"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                            <div class="position-absolute top-50 start-50 translate-middle">
                                <h3 class="fw-bold mb-0">
                                    <?php echo $stats['total'] > 0 ? round(($stats['fully_verified'] / $stats['total']) * 100) : 0; ?>%
                                </h3>
                                <small class="text-muted">Complete</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Button -->
<a href="verify.php" class="floating-action-btn">
    <i class="fas fa-check-circle"></i>
</a>

<?php include __DIR__ . '/../templates/footer.php'; ?>