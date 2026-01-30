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
$cocktailId = $_GET['cocktail'] ?? null;
$message = '';
$messageType = '';

// Get database connection
$pdo = getDB();

// Define suggested tags BEFORE header include
$suggestedTags = [
    'weather' => [
        ['name' => 'HotWeather', 'icon' => '☀️', 'color' => '#FF6B6B'],
        ['name' => 'ColdWeather', 'icon' => '❄️', 'color' => '#4ECDC4'],
        ['name' => 'RainyDay', 'icon' => '🌧️', 'color' => '#95A5A6'],
        ['name' => 'SunnyDay', 'icon' => '🌞', 'color' => '#FFD93D'],
    ],
    'time_of_day' => [
        ['name' => 'Morning', 'icon' => '🌅', 'color' => '#FFA500'],
        ['name' => 'Afternoon', 'icon' => '🏙️', 'color' => '#4169E1'],
        ['name' => 'Evening', 'icon' => '🌆', 'color' => '#9370DB'],
        ['name' => 'Night', 'icon' => '🌃', 'color' => '#191970'],
    ],
    'season' => [
        ['name' => 'Spring', 'icon' => '🌸', 'color' => '#FF69B4'],
        ['name' => 'Summer', 'icon' => '☀️', 'color' => '#FFA500'],
        ['name' => 'Autumn', 'icon' => '🍂', 'color' => '#FF8C00'],
        ['name' => 'Winter', 'icon' => '❄️', 'color' => '#87CEEB'],
    ],
    'mood' => [
        ['name' => 'Celebratory', 'icon' => '🎉', 'color' => '#FFD700'],
        ['name' => 'Energetic', 'icon' => '⚡', 'color' => '#FF6347'],
        ['name' => 'Social', 'icon' => '👥', 'color' => '#32CD32'],
        ['name' => 'Romantic', 'icon' => '💕', 'color' => '#FF1493'],
        ['name' => 'Relaxed', 'icon' => '😌', 'color' => '#90EE90'],
        ['name' => 'Mild', 'icon' => '🌤️', 'color' => '#87CEEB'],
        ['name' => 'Reflective', 'icon' => '🤔', 'color' => '#9370DB'],
        ['name' => 'Comforting', 'icon' => '🤗', 'color' => '#FFB6C1'],
        ['name' => 'Cozy', 'icon' => '🏠', 'color' => '#CD853F'],
        ['name' => 'Warm', 'icon' => '🔥', 'color' => '#FF7F50'],
        ['name' => 'Strong', 'icon' => '💪', 'color' => '#DC143C'],
        ['name' => 'Bold', 'icon' => '⚔️', 'color' => '#8B0000'],
        ['name' => 'Bitter', 'icon' => '😖', 'color' => '#8B4513'],
        ['name' => 'Playful', 'icon' => '🎭', 'color' => '#FF00FF'],
        ['name' => 'Unusual', 'icon' => '🎪', 'color' => '#FF69B4'],
        ['name' => 'Exciting', 'icon' => '🎊', 'color' => '#FF4500'],
        ['name' => 'Soothing', 'icon' => '🧘', 'color' => '#6A5ACD'],
        ['name' => 'Familiar', 'icon' => '😊', 'color' => '#FFD700'],
        ['name' => 'Clean', 'icon' => '✨', 'color' => '#00CED1'],
        ['name' => 'Crisp', 'icon' => '❄️', 'color' => '#1E90FF'],
        ['name' => 'Refreshing', 'icon' => '💧', 'color' => '#00BFFF'],
    ],
    'occasion' => [
        ['name' => 'DateNight', 'icon' => '💑', 'color' => '#FF69B4'],
        ['name' => 'Party', 'icon' => '🎉', 'color' => '#FFD700'],
        ['name' => 'Brunch', 'icon' => '🥂', 'color' => '#DEB887'],
        ['name' => 'AfterWork', 'icon' => '🏢', 'color' => '#4169E1'],
        ['name' => 'Holiday', 'icon' => '🎄', 'color' => '#228B22'],
    ],
    'group_size' => [
        ['name' => 'Solo', 'icon' => '👤', 'color' => '#A9A9A9'],
        ['name' => 'Couple', 'icon' => '👥', 'color' => '#FF69B4'],
        ['name' => 'Group', 'icon' => '👫👬👭', 'color' => '#32CD32'],
    ],
    'activity' => [
        ['name' => 'Relaxing', 'icon' => '😴', 'color' => '#90EE90'],
        ['name' => 'Socializing', 'icon' => '🗣️', 'color' => '#FFB6C1'],
        ['name' => 'Dining', 'icon' => '🍽️', 'color' => '#DEB887'],
        ['name' => 'Dancing', 'icon' => '💃', 'color' => '#FF1493'],
    ],
    'food_pairing' => [
        ['name' => 'PairsWithSeafood', 'icon' => '🦞', 'color' => '#00CED1'],
        ['name' => 'PairsWithDessert', 'icon' => '🍰', 'color' => '#FFB6C1'],
        ['name' => 'PairsWithSpicyFood', 'icon' => '🌶️', 'color' => '#FF4500'],
        ['name' => 'PairsWithCheese', 'icon' => '🧀', 'color' => '#FFD700'],
    ],
    'meal_timing' => [
        ['name' => 'Aperitif', 'icon' => '🍷', 'color' => '#8B0000'],
        ['name' => 'WithMeal', 'icon' => '🍽️', 'color' => '#DAA520'],
        ['name' => 'Digestif', 'icon' => '🥃', 'color' => '#8B4513'],
        ['name' => 'DessertDrink', 'icon' => '🍮', 'color' => '#DEB887'],
    ],
    'strength' => [
        ['name' => 'MildStrength', 'icon' => '🍃', 'color' => '#90EE90'],
        ['name' => 'ModerateStrength', 'icon' => '⚡', 'color' => '#FFD700'],
        ['name' => 'HighStrength', 'icon' => '🔥', 'color' => '#FF4500'],
        ['name' => 'VeryHighStrength', 'icon' => '💥', 'color' => '#DC143C'],
    ],
    'refreshment' => [
        ['name' => 'HighlyRefreshing', 'icon' => '💧', 'color' => '#00BFFF'],
        ['name' => 'ModeratelyRefreshing', 'icon' => '🌊', 'color' => '#87CEEB'],
        ['name' => 'LowlyRefreshing', 'icon' => '☔', 'color' => '#B0C4DE'],
    ],
    'complexity' => [
        ['name' => 'EasyToMake', 'icon' => '✓', 'color' => '#90EE90'],
        ['name' => 'ModerateDifficulty', 'icon' => '⚖️', 'color' => '#FFD700'],
        ['name' => 'ExpertOnly', 'icon' => '👨‍🍳', 'color' => '#DC143C'],
    ],
    'dietary' => [
        ['name' => 'Vegan', 'icon' => '🌱', 'color' => '#228B22'],
        ['name' => 'GlutenFree', 'icon' => '🌾', 'color' => '#D2B48C'],
        ['name' => 'DairyFree', 'icon' => '🚫', 'color' => '#FF6347'],
    ],
    'popularity' => [
        ['name' => 'Trending', 'icon' => '📈', 'color' => '#FF1493'],
        ['name' => 'Classic', 'icon' => '👑', 'color' => '#FFD700'],
        ['name' => 'Underrated', 'icon' => '💎', 'color' => '#4169E1'],
    ],
];

// Handle tag actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRF($csrf_token)) {
        $message = "Invalid request. Please try again.";
        $messageType = "danger";
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'verify_pending':
                    // Bulk verify selected pending tags
                    $cocktail_tags_to_verify = isset($_POST['pending_tags']) && is_array($_POST['pending_tags']) ? $_POST['pending_tags'] : [];
                    $tags_verified = 0;
                    $tags_failed = [];
                    
                    if (empty($cocktail_tags_to_verify)) {
                        throw new Exception("No tags selected for verification");
                    }
                    
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    try {
                        foreach ($cocktail_tags_to_verify as $cocktail_tag_id) {
                            try {
                                // Sanitize cocktail_tag_id (this is cocktail_tags.id)
                                $cocktail_tag_id = (int)$cocktail_tag_id;
                                
                                if ($cocktail_tag_id <= 0) {
                                    $tags_failed[] = "Invalid tag ID: $cocktail_tag_id";
                                    continue;
                                }
                                
                                // Get tag details before verifying - use cocktail_tags.id
                                $tagStmt = $pdo->prepare("
                                    SELECT ct.id, ct.tag_id, t.tag_name, ct.cocktail_id, ct.status
                                    FROM cocktail_tags ct
                                    JOIN tags t ON ct.tag_id = t.id
                                    WHERE ct.id = ? AND ct.cocktail_id = ?
                                ");
                                $tagStmt->execute([$cocktail_tag_id, $cocktailId]);
                                $tagData = $tagStmt->fetch(PDO::FETCH_ASSOC);
                                
                                if (!$tagData) {
                                    $tags_failed[] = "Tag not found: ID $cocktail_tag_id";
                                    continue;
                                }
                                
                                // Only verify if currently pending
                                if ($tagData['status'] !== 'pending') {
                                    $tags_failed[] = "{$tagData['tag_name']} is already " . $tagData['status'];
                                    continue;
                                }
                                
                                // Update cocktail_tags status to verified using ct.id
                                $updateStmt = $pdo->prepare("
                                    UPDATE cocktail_tags 
                                    SET 
                                        status = 'verified',
                                        verified_by = ?,
                                        verified_at = NOW(),
                                        confidence_score = 100,
                                        verification_notes = ?
                                    WHERE id = ?
                                ");
                                
                                $notes = "Bulk verified by " . $expert['full_name'];
                                $updateStmt->execute([
                                    $expert['id'],
                                    $notes,
                                    $cocktail_tag_id
                                ]);
                                
                                // Log the verification
                                $logStmt = $pdo->prepare("
                                    INSERT INTO verification_logs (
                                        expert_id,
                                        cocktail_id,
                                        action_type,
                                        tag_id,
                                        cocktail_tag_id,
                                        new_value,
                                        notes
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                                ");
                                
                                $logStmt->execute([
                                    $expert['id'],
                                    $cocktailId,
                                    'verify_tag',
                                    $tagData['tag_id'],
                                    $cocktail_tag_id,
                                    'verified',
                                    $notes
                                ]);
                                
                                $tags_verified++;
                                
                            } catch (Exception $e) {
                                error_log("Error verifying tag $cocktail_tag_id: " . $e->getMessage());
                                $tags_failed[] = "Failed to verify tag ID $cocktail_tag_id: " . $e->getMessage();
                            }
                        }
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        // Update cocktail verification status
                        $cocktailManager->updateVerificationStatus($cocktailId);
                        
                        if ($tags_verified > 0) {
                            $message = "Successfully verified $tags_verified tag(s)";
                            if (!empty($tags_failed)) {
                                $message .= ". Issues: " . implode(", ", array_slice($tags_failed, 0, 3));
                            }
                            $messageType = "success";
                        } else {
                            throw new Exception("Failed to verify any tags: " . implode(", ", $tags_failed));
                        }
                        
                        // Refresh cocktail data
                        $cocktail = $cocktailManager->getCocktail($cocktailId);
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                    break;
                    
                case 'add_suggested_tags':
                    // Bulk add suggested tags with validation
                    $selected_tags = isset($_POST['selected_tags']) && is_array($_POST['selected_tags']) ? $_POST['selected_tags'] : [];
                    $tags_added = 0;
                    $tags_failed = [];
                    
                    if (empty($selected_tags)) {
                        throw new Exception("No tags selected");
                    }
                    
                    // Validate required tags
                    $weather_count = 0;
                    $time_count = 0;
                    $mood_count = 0;
                    
                    foreach ($selected_tags as $tag_name) {
                        foreach ($suggestedTags['weather'] as $t) {
                            if ($t['name'] === $tag_name) $weather_count++;
                        }
                        foreach ($suggestedTags['time_of_day'] as $t) {
                            if ($t['name'] === $tag_name) $time_count++;
                        }
                        foreach ($suggestedTags['mood'] as $t) {
                            if ($t['name'] === $tag_name) $mood_count++;
                        }
                    }
                    
                    if ($weather_count === 0 || $time_count === 0 || $mood_count === 0) {
                        throw new Exception("You must select at least one Weather, one Time of Day, and one Mood tag");
                    }
                    
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    try {
                        foreach ($selected_tags as $tag_name) {
                            try {
                                $tag_name = trim($tag_name);
                                
                                if (empty($tag_name)) {
                                    $tags_failed[] = "Empty tag name";
                                    continue;
                                }
                                
                                // Check if tag already exists for this cocktail
                                $checkStmt = $pdo->prepare("
                                    SELECT ct.id FROM cocktail_tags ct
                                    JOIN tags t ON ct.tag_id = t.id
                                    WHERE ct.cocktail_id = ? AND t.tag_name = ?
                                ");
                                $checkStmt->execute([$cocktailId, $tag_name]);
                                
                                if ($checkStmt->fetch()) {
                                    $tags_failed[] = "$tag_name already exists";
                                    continue;
                                }
                                
                                // Find or create tag
                                $tagStmt = $pdo->prepare("SELECT id FROM tags WHERE tag_name = ?");
                                $tagStmt->execute([$tag_name]);
                                $tagRow = $tagStmt->fetch(PDO::FETCH_ASSOC);
                                
                                $tag_id = null;
                                if ($tagRow) {
                                    $tag_id = $tagRow['id'];
                                } else {
                                    // Create new tag
                                    $insertTagStmt = $pdo->prepare("
                                        INSERT INTO tags (tag_name, slug, created_by)
                                        VALUES (?, ?, ?)
                                    ");
                                    $slug = strtolower(str_replace([' ', '_'], '-', $tag_name));
                                    $insertTagStmt->execute([$tag_name, $slug, $expert['id']]);
                                    $tag_id = $pdo->lastInsertId();
                                }
                                
                                // Add tag to cocktail
                                $addStmt = $pdo->prepare("
                                    INSERT INTO cocktail_tags (
                                        cocktail_id,
                                        tag_id,
                                        status,
                                        source,
                                        verified_by,
                                        verified_at,
                                        confidence_score,
                                        verification_notes
                                    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
                                ");
                                
                                $addStmt->execute([
                                    $cocktailId,
                                    $tag_id,
                                    'verified',
                                    'expert_added',
                                    $expert['id'],
                                    100,
                                    "Added by " . $expert['full_name']
                                ]);
                                
                                // Log the action
                                $logStmt = $pdo->prepare("
                                    INSERT INTO verification_logs (
                                        expert_id,
                                        cocktail_id,
                                        action_type,
                                        tag_id,
                                        new_value,
                                        notes
                                    ) VALUES (?, ?, ?, ?, ?, ?)
                                ");
                                
                                $logStmt->execute([
                                    $expert['id'],
                                    $cocktailId,
                                    'add_tag',
                                    $tag_id,
                                    $tag_name,
                                    "Suggested tag added by " . $expert['full_name']
                                ]);
                                
                                $tags_added++;
                                
                            } catch (Exception $e) {
                                error_log("Error adding tag '$tag_name': " . $e->getMessage());
                                $tags_failed[] = "Failed to add $tag_name";
                            }
                        }
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        // Update cocktail verification status
                        $cocktailManager->updateVerificationStatus($cocktailId);
                        
                        $message = "Successfully added $tags_added suggested tag(s)";
                        if (!empty($tags_failed)) {
                            $message .= ". Issues: " . implode(", ", array_slice($tags_failed, 0, 3));
                        }
                        $messageType = "success";
                        
                        // Refresh cocktail data
                        $cocktail = $cocktailManager->getCocktail($cocktailId);
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                    break;
                    
                default:
                    throw new Exception("Invalid action");
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = "danger";
            error_log("Verification action error: " . $e->getMessage());
        }
    }
}

// Get cocktail to verify
if ($cocktailId) {
    $cocktail = $cocktailManager->getCocktail($cocktailId);
} else {
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
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo h($message); ?>
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
        <div class="col-lg-7 mb-4">
            <!-- Cocktail Info Card -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h2 class="fw-bold mb-2"><?php echo h($cocktail['strDrink']); ?></h2>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if (!empty($cocktail['strCategory'])): ?>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-tag me-1"></i><?php echo h($cocktail['strCategory']); ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($cocktail['strAlcoholic'])): ?>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-wine-bottle me-1"></i><?php echo h($cocktail['strAlcoholic']); ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($cocktail['strGlass'])): ?>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-glass me-1"></i><?php echo h($cocktail['strGlass']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="verify.php" class="btn btn-outline-primary">
                        <i class="fas fa-dice me-2"></i>Random
                    </a>
                </div>
            </div>

            <!-- Cocktail Image -->
            <div class="glass-card p-3 mb-4">
                <?php if (!empty($cocktail['strDrinkThumb'])): ?>
                <img src="<?php echo h($cocktail['strDrinkThumb']); ?>" 
                     class="cocktail-image rounded" 
                     alt="<?php echo h($cocktail['strDrink']); ?>"
                     onerror="this.src='https://via.placeholder.com/600x400/667eea/ffffff?text=Cocktail'"
                     style="width: 100%; height: 350px; object-fit: cover;">
                <?php else: ?>
                <div class="cocktail-image d-flex align-items-center justify-content-center rounded" 
                     style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 350px;">
                    <i class="fas fa-glass-martini-alt fa-5x text-white"></i>
                </div>
                <?php endif; ?>
            </div>

            <!-- Instructions & Ingredients -->
            <div class="glass-card p-4">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="ingredientsTab" data-bs-toggle="tab" 
                                data-bs-target="#ingredientsPanel" type="button">
                            <i class="fas fa-list me-1"></i>Ingredients
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="instructionsTab" data-bs-toggle="tab" 
                                data-bs-target="#instructionsPanel" type="button">
                            <i class="fas fa-list-ol me-1"></i>Instructions
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="ingredientsPanel">
                        <div class="ingredients-list">
                            <?php foreach ($cocktail['ingredients'] as $ingredient): ?>
                            <div class="ingredient-chip mb-2">
                                <span class="fw-bold"><?php echo h($ingredient['measure']); ?></span>
                                <span class="ms-2"><?php echo h($ingredient['ingredient']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="instructionsPanel">
                        <p><?php echo nl2br(h($cocktail['strInstructions'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Tag Verification & Suggestions -->
        <div class="col-lg-5 mb-4">
            <!-- Current Tags Section -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-check-circle me-2" style="color: #10B981;"></i>Current Tags
                    </h5>
                    <span class="badge bg-success"><?php echo count($cocktail['verified_tags'] ?? []); ?></span>
                </div>

                <?php if (!empty($cocktail['verified_tags'])): ?>
                <div class="tags-container mb-3">
                    <?php foreach ($cocktail['verified_tags'] as $tag): ?>
                    <span class="tag-pill bg-success">
                        <i class="fas fa-check-circle me-1"></i><?php echo h($tag['tag_name']); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0">No verified tags yet</p>
                <?php endif; ?>
            </div>

            <!-- Pending Tags Section - Bulk Verify -->
            <?php if (!empty($cocktail['pending_tags'])): ?>
            <div class="glass-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-hourglass-half me-2" style="color: #F59E0B;"></i>Pending Tags
                    </h5>
                    <span class="badge bg-warning text-dark"><?php echo count($cocktail['pending_tags']); ?></span>
                </div>

                <form method="POST" action="" id="pendingTagsForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <input type="hidden" name="action" value="verify_pending">
                    
                    <div class="alert alert-info alert-sm mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Select only the tags you want to verify. Unselected tags will remain pending for review.</small>
                    </div>
                    
                    <div class="tags-container mb-3" id="pendingTagsContainer">
                        <?php foreach ($cocktail['pending_tags'] as $tag): ?>
                        <label class="tag-checkbox pending-tag-item">
                            <input type="checkbox" name="pending_tags[]" value="<?php echo $tag['id']; ?>" 
                                   class="pending-tag-checkbox" data-tag-name="<?php echo h($tag['tag_name']); ?>">
                            <span class="tag-pill" style="cursor: pointer;">
                                <i class="fas fa-circle-notch me-1"></i><?php echo h($tag['tag_name']); ?>
                                <i class="fas fa-times-circle ms-2" style="font-size: 0.8em;"></i>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" 
                                onclick="togglePendingTags()" id="toggleBtn">
                            <i class="fas fa-check-double me-1"></i>Select All
                        </button>
                        <button type="submit" class="btn btn-success btn-sm w-100" 
                                id="verifyAllBtn" disabled>
                            <i class="fas fa-check-circle me-1"></i>Verify Selected (<span id="pendingCount">0</span>)
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Suggested Tags Section - Enhanced UI -->
            <div class="glass-card p-4 mb-4">
                <div class="suggested-tags-header mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">
                                <i class="fas fa-magic me-2" style="color: #8B5CF6;"></i>Suggest Tags
                            </h5>
                            <small class="text-muted">Help complete this cocktail's profile</small>
                        </div>
                        <div class="suggested-counter">
                            <span class="badge bg-primary">
                                <span id="selectedCount">0</span>/Required
                            </span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="" id="suggestedTagsForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <input type="hidden" name="action" value="add_suggested_tags">

                    <!-- Weather Tags (Required) -->
                    <div class="suggested-section mb-4">
                        <div class="section-header">
                            <div class="section-title">
                                <div class="section-icon">☀️</div>
                                <div>
                                    <h6 class="fw-bold mb-0">Weather & Climate</h6>
                                    <small class="text-muted">How does this cocktail suit different weather?</small>
                                </div>
                            </div>
                            <span class="badge bg-danger">Required</span>
                        </div>
                        <div class="suggested-tags-grid">
                            <?php if (isset($suggestedTags['weather']) && is_array($suggestedTags['weather'])): ?>
                                <?php foreach ($suggestedTags['weather'] as $tag): ?>
                                <label class="suggested-tag-label">
                                    <input type="checkbox" name="selected_tags[]" 
                                           value="<?php echo h($tag['name']); ?>" class="suggested-checkbox weather-check"
                                           data-category="weather">
                                    <span class="suggested-tag-btn" style="--tag-color: <?php echo h($tag['color']); ?>">
                                        <span class="tag-icon"><?php echo $tag['icon']; ?></span>
                                        <span class="tag-text"><?php echo h($tag['name']); ?></span>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Time of Day Tags (Required) -->
                    <div class="suggested-section mb-4">
                        <div class="section-header">
                            <div class="section-title">
                                <div class="section-icon">🕒</div>
                                <div>
                                    <h6 class="fw-bold mb-0">Time of Day</h6>
                                    <small class="text-muted">When is this cocktail best enjoyed?</small>
                                </div>
                            </div>
                            <span class="badge bg-danger">Required</span>
                        </div>
                        <div class="suggested-tags-grid">
                            <?php if (isset($suggestedTags['time_of_day']) && is_array($suggestedTags['time_of_day'])): ?>
                                <?php foreach ($suggestedTags['time_of_day'] as $tag): ?>
                                <label class="suggested-tag-label">
                                    <input type="checkbox" name="selected_tags[]" 
                                           value="<?php echo h($tag['name']); ?>" class="suggested-checkbox time-check"
                                           data-category="time_of_day">
                                    <span class="suggested-tag-btn" style="--tag-color: <?php echo h($tag['color']); ?>">
                                        <span class="tag-icon"><?php echo $tag['icon']; ?></span>
                                        <span class="tag-text"><?php echo h($tag['name']); ?></span>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Mood Tags (Required) -->
                    <div class="suggested-section mb-4">
                        <div class="section-header">
                            <div class="section-title">
                                <div class="section-icon">😊</div>
                                <div>
                                    <h6 class="fw-bold mb-0">Mood & Atmosphere</h6>
                                    <small class="text-muted">What emotions does this cocktail evoke?</small>
                                </div>
                            </div>
                            <span class="badge bg-danger">Required</span>
                        </div>
                        <div class="suggested-tags-grid mood-grid">
                            <?php if (isset($suggestedTags['mood']) && is_array($suggestedTags['mood'])): ?>
                                <?php foreach ($suggestedTags['mood'] as $tag): ?>
                                <label class="suggested-tag-label">
                                    <input type="checkbox" name="selected_tags[]" 
                                           value="<?php echo h($tag['name']); ?>" class="suggested-checkbox mood-check"
                                           data-category="mood">
                                    <span class="suggested-tag-btn" style="--tag-color: <?php echo h($tag['color']); ?>">
                                        <span class="tag-icon"><?php echo $tag['icon']; ?></span>
                                        <span class="tag-text"><?php echo h($tag['name']); ?></span>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Optional Sections Container -->
                    <div class="optional-sections">
                        <!-- Activity Tags -->
                        <details class="optional-section">
                            <summary class="optional-summary">
                                <div class="summary-content">
                                    <i class="fas fa-chevron-right chevron-icon"></i>
                                    <div class="summary-text">
                                        <div class="summary-title">🎯 User Activity</div>
                                        <div class="summary-subtitle">What activities suit this cocktail</div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary">Optional</span>
                            </summary>
                            <div class="optional-content">
                                <div class="suggested-tags-grid">
                                    <?php if (isset($suggestedTags['activity']) && is_array($suggestedTags['activity'])): ?>
                                        <?php foreach ($suggestedTags['activity'] as $tag): ?>
                                        <label class="suggested-tag-label">
                                            <input type="checkbox" name="selected_tags[]" 
                                                   value="<?php echo h($tag['name']); ?>" class="suggested-checkbox"
                                                   data-category="activity">
                                            <span class="suggested-tag-btn-secondary" style="--tag-color: <?php echo h($tag['color']); ?>">
                                                <span class="tag-icon"><?php echo $tag['icon']; ?></span>
                                                <span class="tag-text"><?php echo h($tag['name']); ?></span>
                                            </span>
                                        </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </details>

                        <!-- Food Pairing Tags -->
                        <details class="optional-section">
                            <summary class="optional-summary">
                                <div class="summary-content">
                                    <i class="fas fa-chevron-right chevron-icon"></i>
                                    <div class="summary-text">
                                        <div class="summary-title">🍽️ Food Pairings</div>
                                        <div class="summary-subtitle">Best food combinations</div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary">Optional</span>
                            </summary>
                            <div class="optional-content">
                                <div class="suggested-tags-grid">
                                    <?php if (isset($suggestedTags['food_pairing']) && is_array($suggestedTags['food_pairing'])): ?>
                                        <?php foreach ($suggestedTags['food_pairing'] as $tag): ?>
                                        <label class="suggested-tag-label">
                                            <input type="checkbox" name="selected_tags[]" 
                                                   value="<?php echo h($tag['name']); ?>" class="suggested-checkbox"
                                                   data-category="food_pairing">
                                            <span class="suggested-tag-btn-secondary" style="--tag-color: <?php echo h($tag['color']); ?>">
                                                <span class="tag-icon"><?php echo $tag['icon']; ?></span>
                                                <span class="tag-text"><?php echo h($tag['name']); ?></span>
                                            </span>
                                        </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </details>

                        <!-- Meal Timing Tags -->
                        <details class="optional-section">
                            <summary class="optional-summary">
                                <div class="summary-content">
                                    <i class="fas fa-chevron-right chevron-icon"></i>
                                    <div class="summary-text">
                                        <div class="summary-title">🕐 Meal Timing</div>
                                        <div class="summary-subtitle">When to serve this cocktail</div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary">Optional</span>
                            </summary>
                            <div class="optional-content">
                                <div class="suggested-tags-grid">
                                    <?php if (isset($suggestedTags['meal_timing']) && is_array($suggestedTags['meal_timing'])): ?>
                                        <?php foreach ($suggestedTags['meal_timing'] as $tag): ?>
                                        <label class="suggested-tag-label">
                                            <input type="checkbox" name="selected_tags[]" 
                                                   value="<?php echo h($tag['name']); ?>" class="suggested-checkbox"
                                                   data-category="meal_timing">
                                            <span class="suggested-tag-btn-secondary" style="--tag-color: <?php echo h($tag['color']); ?>">
                                                <span class="tag-icon"><?php echo $tag['icon']; ?></span>
                                                <span class="tag-text"><?php echo h($tag['name']); ?></span>
                                            </span>
                                        </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </details>

                        <!-- Alcohol Strength Tags -->
                        <details class="optional-section">
                            <summary class="optional-summary">
                                <div class="summary-content">
                                    <i class="fas fa-chevron-right chevron-icon"></i>
                                    <div class="summary-text">
                                        <div class="summary-title">🥃 Alcohol Intensity</div>
                                        <div class="summary-subtitle">Strength and potency level</div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary">Optional</span>
                            </summary>
                            <div class="optional-content">
                                <div class="suggested-tags-grid">
                                    <?php if (isset($suggestedTags['strength']) && is_array($suggestedTags['strength'])): ?>
                                        <?php foreach ($suggestedTags['strength'] as $tag): ?>
                                        <label class="suggested-tag-label">
                                            <input type="checkbox" name="selected_tags[]" 
                                                   value="<?php echo h($tag['name']); ?>" class="suggested-checkbox"
                                                   data-category="strength">
                                            <span class="suggested-tag-btn-secondary" style="--tag-color: <?php echo h($tag['color']); ?>">
                                                <span class="tag-icon"><?php echo $tag['icon']; ?></span>
                                                <span class="tag-text"><?php echo h($tag['name']); ?></span>
                                            </span>
                                        </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </details>

                        <!-- Refreshment Score Tags -->
                        <details class="optional-section">
                            <summary class="optional-summary">
                                <div class="summary-content">
                                    <i class="fas fa-chevron-right chevron-icon"></i>
                                    <div class="summary-text">
                                        <div class="summary-title">💧 Refreshment Level</div>
                                        <div class="summary-subtitle">How refreshing is this cocktail</div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary">Optional</span>
                            </summary>
                            <div class="optional-content">
                                <div class="suggested-tags-grid">
                                    <?php if (isset($suggestedTags['refreshment']) && is_array($suggestedTags['refreshment'])): ?>
                                        <?php foreach ($suggestedTags['refreshment'] as $tag): ?>
                                        <label class="suggested-tag-label">
                                            <input type="checkbox" name="selected_tags[]" 
                                                   value="<?php echo h($tag['name']); ?>" class="suggested-checkbox"
                                                   data-category="refreshment">
                                            <span class="suggested-tag-btn-secondary" style="--tag-color: <?php echo h($tag['color']); ?>">
                                                <span class="tag-icon"><?php echo $tag['icon']; ?></span>
                                                <span class="tag-text"><?php echo h($tag['name']); ?></span>
                                            </span>
                                        </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </details>

                        <!-- Complexity Level Tags -->
                        <details class="optional-section">
                            <summary class="optional-summary">
                                <div class="summary-content">
                                    <i class="fas fa-chevron-right chevron-icon"></i>
                                    <div class="summary-text">
                                        <div class="summary-title">🎓 Complexity Level</div>
                                        <div class="summary-subtitle">Difficulty to prepare</div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary">Optional</span>
                            </summary>
                            <div class="optional-content">
                                <div class="suggested-tags-grid">
                                    <?php if (isset($suggestedTags['complexity']) && is_array($suggestedTags['complexity'])): ?>
                                        <?php foreach ($suggestedTags['complexity'] as $tag): ?>
                                        <label class="suggested-tag-label">
                                            <input type="checkbox" name="selected_tags[]" 
                                                   value="<?php echo h($tag['name']); ?>" class="suggested-checkbox"
                                                   data-category="complexity">
                                            <span class="suggested-tag-btn-secondary" style="--tag-color: <?php echo h($tag['color']); ?>">
                                                <span class="tag-icon"><?php echo $tag['icon']; ?></span>
                                                <span class="tag-text"><?php echo h($tag['name']); ?></span>
                                            </span>
                                        </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </details>

                        <!-- Dietary Tags -->
                        <details class="optional-section">
                            <summary class="optional-summary">
                                <div class="summary-content">
                                    <i class="fas fa-chevron-right chevron-icon"></i>
                                    <div class="summary-text">
                                        <div class="summary-title">🌱 Dietary Options</div>
                                        <div class="summary-subtitle">Special dietary requirements</div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary">Optional</span>
                            </summary>
                            <div class="optional-content">
                                <div class="suggested-tags-grid">
                                    <?php if (isset($suggestedTags['dietary']) && is_array($suggestedTags['dietary'])): ?>
                                        <?php foreach ($suggestedTags['dietary'] as $tag): ?>
                                        <label class="suggested-tag-label">
                                            <input type="checkbox" name="selected_tags[]" 
                                                   value="<?php echo h($tag['name']); ?>" class="suggested-checkbox"
                                                   data-category="dietary">
                                            <span class="suggested-tag-btn-secondary" style="--tag-color: <?php echo h($tag['color']); ?>">
                                                <span class="tag-icon"><?php echo $tag['icon']; ?></span>
                                                <span class="tag-text"><?php echo h($tag['name']); ?></span>
                                            </span>
                                        </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </details>

                        <!-- Popularity & Trend Tags -->
                        <details class="optional-section">
                            <summary class="optional-summary">
                                <div class="summary-content">
                                    <i class="fas fa-chevron-right chevron-icon"></i>
                                    <div class="summary-text">
                                        <div class="summary-title">⭐ Popularity & Trends</div>
                                        <div class="summary-subtitle">Market status and trends</div>
                                    </div>
                                </div>
                                <span class="badge bg-secondary">Optional</span>
                            </summary>
                            <div class="optional-content">
                                <div class="suggested-tags-grid">
                                    <?php if (isset($suggestedTags['popularity']) && is_array($suggestedTags['popularity'])): ?>
                                        <?php foreach ($suggestedTags['popularity'] as $tag): ?>
                                        <label class="suggested-tag-label">
                                            <input type="checkbox" name="selected_tags[]" 
                                                   value="<?php echo h($tag['name']); ?>" class="suggested-checkbox"
                                                   data-category="popularity">
                                            <span class="suggested-tag-btn-secondary" style="--tag-color: <?php echo h($tag['color']); ?>">
                                                <span class="tag-icon"><?php echo $tag['icon']; ?></span>
                                                <span class="tag-text"><?php echo h($tag['name']); ?></span>
                                            </span>
                                        </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </details>
                    </div>

                    <!-- Validation Message -->
                    <div class="validation-message mb-3" id="validationMessage" style="display: none;">
                        <div class="alert alert-info alert-sm">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="validationText"></span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="addSuggestedBtn" disabled>
                            <i class="fas fa-plus-circle me-2"></i>Add <span id="tagCountText">0</span> Selected Tags
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modals for individual tag actions -->
<?php include __DIR__ . '/../templates/modal.php'; ?>

<style>
.tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tag-pill {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    background: #10B981;
    color: white;
    white-space: nowrap;
    transition: all 0.3s ease;
}

.pending-tag-item:hover .tag-pill {
    background: #059669;
    transform: translateY(-2px);
}

.tag-checkbox {
    display: contents;
}

.tag-checkbox input[type="checkbox"] {
    display: none;
}

.tag-checkbox input[type="checkbox"]:checked + .tag-pill {
    background: #0D9488;
    box-shadow: 0 0 10px rgba(13, 148, 136, 0.3);
    transform: scale(1.05);
}

.suggested-tags-header {
    padding-bottom: 1rem;
    border-bottom: 2px solid rgba(139, 92, 246, 0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16px;
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.05) 0%, rgba(99, 102, 241, 0.05) 100%);
    border-radius: 12px;
    margin-bottom: 16px;
    border-left: 4px solid var(--primary);
}

.section-title {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.section-icon {
    font-size: 28px;
    min-width: 40px;
    text-align: center;
}

.section-title h6 {
    font-size: 15px;
    margin: 0;
}

.section-title small {
    font-size: 12px;
}

.suggested-tags-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

.mood-grid {
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
}

.suggested-tag-label {
    cursor: pointer;
    display: block;
}

.suggested-tag-label input[type="checkbox"] {
    display: none;
}

.suggested-tag-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 14px 12px;
    border-radius: 14px;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    border: 2px solid transparent;
    cursor: pointer;
    background: linear-gradient(135deg, var(--tag-color) 0%, var(--tag-color) 100%);
    color: white;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.suggested-tag-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.2);
    transition: left 0.3s ease;
}

.suggested-tag-btn:hover::before {
    left: 100%;
}

.suggested-tag-btn:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
}

.tag-icon {
    font-size: 24px;
    margin-bottom: 4px;
    display: block;
}

.tag-text {
    display: block;
    font-size: 12px;
    line-height: 1.2;
}

.suggested-tag-label input[type="checkbox"]:checked + .suggested-tag-btn {
    background: linear-gradient(135deg, var(--tag-color) 0%, var(--tag-color) 100%);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2), 0 8px 20px rgba(0, 0, 0, 0.25);
    transform: scale(1.05) translateY(-2px);
}

.suggested-tag-btn-secondary {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 12px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 2px solid rgba(139, 92, 246, 0.2);
    cursor: pointer;
    background: white;
    color: var(--dark);
    text-align: center;
}

.suggested-tag-btn-secondary:hover {
    background: rgba(139, 92, 246, 0.05);
    border-color: var(--tag-color);
    transform: translateY(-2px);
}

.suggested-tag-label input[type="checkbox"]:checked + .suggested-tag-btn-secondary {
    background: var(--tag-color);
    color: white;
    border-color: var(--tag-color);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    transform: scale(1.05);
}

.optional-section {
    border: 1px solid rgba(139, 92, 246, 0.15);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    background: white;
    transition: all 0.3s ease;
}

.optional-section:hover {
    border-color: rgba(139, 92, 246, 0.3);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.1);
}

.optional-summary {
    list-style: none;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    user-select: none;
    padding: 0;
}

.summary-content {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.chevron-icon {
    font-size: 12px;
    transition: transform 0.3s ease;
    color: var(--primary);
}

.optional-section[open] .chevron-icon {
    transform: rotate(90deg);
}

.summary-text {
    flex: 1;
}

.summary-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
}

.summary-subtitle {
    font-size: 12px;
    color: #999;
}

.optional-content {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid rgba(139, 92, 246, 0.1);
}

.validation-message {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-sm {
    padding: 8px 12px;
    font-size: 13px;
}

.optional-sections {
    margin: 24px 0;
    padding: 20px;
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.02) 0%, rgba(99, 102, 241, 0.02) 100%);
    border-radius: 12px;
    border: 1px dashed rgba(139, 92, 246, 0.2);
}
</style>

<script>
// Pending tags functions
function togglePendingTags() {
    const checkboxes = document.querySelectorAll('.pending-tag-checkbox');
    const btn = document.getElementById('toggleBtn');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(cb => cb.checked = !allChecked);
    
    btn.innerHTML = allChecked ? 
        '<i class="fas fa-check-double me-1"></i>Select All' : 
        '<i class="fas fa-times me-1"></i>Deselect All';
    
    updatePendingCount();
}

function updatePendingCount() {
    const checkedBoxes = document.querySelectorAll('.pending-tag-checkbox:checked');
    const btn = document.getElementById('verifyAllBtn');
    const countSpan = document.getElementById('pendingCount');
    
    const count = checkedBoxes.length;
    countSpan.textContent = count;
    btn.disabled = count === 0;
    
    if (count > 0) {
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-success');
    } else {
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-success');
    }
}

// Event listeners for pending tags
document.querySelectorAll('.pending-tag-checkbox').forEach(cb => {
    cb.addEventListener('change', updatePendingCount);
});

// Suggested tags validation
function updateAddSuggestedButton() {
    const requiredWeather = document.querySelectorAll('.weather-check:checked').length;
    const requiredTime = document.querySelectorAll('.time-check:checked').length;
    const requiredMood = document.querySelectorAll('.mood-check:checked').length;
    const totalSelected = document.querySelectorAll('.suggested-checkbox:checked').length;
    const btn = document.getElementById('addSuggestedBtn');
    const validationMessage = document.getElementById('validationMessage');
    const validationText = document.getElementById('validationText');
    const tagCountText = document.getElementById('tagCountText');
    const selectedCount = document.getElementById('selectedCount');
    
    tagCountText.textContent = totalSelected;
    selectedCount.textContent = totalSelected;
    
    const isValid = requiredWeather > 0 && requiredTime > 0 && requiredMood > 0;
    
    if (!isValid && totalSelected > 0) {
        validationMessage.style.display = 'block';
        validationText.textContent = `Select at least one Weather, one Time of Day, and one Mood tag (Weather: ${requiredWeather}/1, Time: ${requiredTime}/1, Mood: ${requiredMood}/1)`;
    } else {
        validationMessage.style.display = 'none';
    }
    
    btn.disabled = !isValid;
    btn.style.opacity = isValid ? '1' : '0.5';
}

// Event listeners for suggested tags
document.querySelectorAll('.suggested-checkbox').forEach(cb => {
    cb.addEventListener('change', updateAddSuggestedButton);
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePendingCount();
    updateAddSuggestedButton();
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>