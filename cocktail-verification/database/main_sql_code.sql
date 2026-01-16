-- ============================================
-- DATABASE: cocktail_verification_db
-- VERSION: 1.0.0
-- CREATED: 2024
-- PURPOSE: Cocktail tag verification system for AI research
-- ============================================

-- ============================================
-- 1. MAIN COCKTAIL TABLE (Preserves ALL original data)
-- ============================================
CREATE TABLE cocktails (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Original dataset columns (50+ columns preserved exactly)
    idDrink VARCHAR(50) UNIQUE NOT NULL,
    strDrink VARCHAR(255) NOT NULL,
    strDrinkAlternate VARCHAR(255),
    strVideo TEXT,
    strCategory VARCHAR(100),
    strIBA VARCHAR(100),
    strAlcoholic VARCHAR(50),
    strGlass VARCHAR(100),
    strInstructions TEXT,
    strInstructionsES TEXT,
    strInstructionsDE TEXT,
    strInstructionsFR TEXT,
    strInstructionsIT TEXT,
    strInstructionsZH_HANS TEXT,
    strInstructionsZH_HANT TEXT,
    strDrinkThumb TEXT,
    strIngredient1 VARCHAR(100),
    strIngredient2 VARCHAR(100),
    strIngredient3 VARCHAR(100),
    strIngredient4 VARCHAR(100),
    strIngredient5 VARCHAR(100),
    strIngredient6 VARCHAR(100),
    strIngredient7 VARCHAR(100),
    strIngredient8 VARCHAR(100),
    strIngredient9 VARCHAR(100),
    strIngredient10 VARCHAR(100),
    strIngredient11 VARCHAR(100),
    strIngredient12 VARCHAR(100),
    strIngredient13 VARCHAR(100),
    strIngredient14 VARCHAR(100),
    strIngredient15 VARCHAR(100),
    strMeasure1 VARCHAR(100),
    strMeasure2 VARCHAR(100),
    strMeasure3 VARCHAR(100),
    strMeasure4 VARCHAR(100),
    strMeasure5 VARCHAR(100),
    strMeasure6 VARCHAR(100),
    strMeasure7 VARCHAR(100),
    strMeasure8 VARCHAR(100),
    strMeasure9 VARCHAR(100),
    strMeasure10 VARCHAR(100),
    strMeasure11 VARCHAR(100),
    strMeasure12 VARCHAR(100),
    strMeasure13 VARCHAR(100),
    strMeasure14 VARCHAR(100),
    strMeasure15 VARCHAR(100),
    strImageSource TEXT,
    strImageAttribution TEXT,
    strCreativeCommonsConfirmed VARCHAR(10),
    dateModified DATETIME,
    
    -- System fields
    verification_status ENUM('pending', 'partially_verified', 'fully_verified', 'disputed') DEFAULT 'pending',
    total_tags INT DEFAULT 0,
    verified_tags INT DEFAULT 0,
    last_verified_by INT,
    last_verified_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_idDrink (idDrink),
    INDEX idx_verification_status (verification_status),
    INDEX idx_category (strCategory),
    INDEX idx_alcoholic (strAlcoholic),
    INDEX idx_glass (strGlass),
    INDEX idx_created_at (created_at),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. ORIGINAL TAGS ARCHIVE
-- ============================================
CREATE TABLE cocktail_original_tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cocktail_id INT NOT NULL,
    original_tags TEXT NOT NULL,
    import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (cocktail_id) REFERENCES cocktails(id) ON DELETE CASCADE,
    INDEX idx_cocktail_id (cocktail_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. TAG CATEGORIES
-- ============================================
CREATE TABLE tag_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    color_code VARCHAR(7) DEFAULT '#6B7280',
    icon_class VARCHAR(50),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO tag_categories (name, description, color_code, display_order) VALUES
('Flavor', 'Taste characteristics like sweet, sour, bitter', '#EF4444', 10),
('Occasion', 'Suitable events or situations', '#3B82F6', 20),
('Season', 'Seasonal appropriateness', '#10B981', 30),
('Type', 'Cocktail classification', '#8B5CF6', 40),
('Ingredient', 'Main ingredients present', '#F59E0B', 50),
('Texture', 'Mouthfeel and consistency', '#EC4899', 60),
('Strength', 'Alcohol content level', '#6366F1', 70),
('Temperature', 'Serving temperature', '#06B6D4', 80),
('Complexity', 'Preparation difficulty', '#84CC16', 90);

-- ============================================
-- 4. TAGS MASTER TABLE
-- ============================================
CREATE TABLE tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tag_name VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(120) UNIQUE NOT NULL,
    category_id INT,
    description TEXT,
    
    -- Verification status
    is_verified BOOLEAN DEFAULT FALSE,
    verification_score INT DEFAULT 0,
    
    -- Usage statistics
    usage_count INT DEFAULT 0,
    verified_usage_count INT DEFAULT 0,
    
    -- System fields
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (category_id) REFERENCES tag_categories(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_tag_name (tag_name),
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_is_verified (is_verified),
    INDEX idx_usage_count (usage_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. EXPERTS/VERIFIERS TABLE
-- ============================================
CREATE TABLE experts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    
    -- Personal info
    full_name VARCHAR(255) NOT NULL,
    avatar_url TEXT,
    bio TEXT,
    expertise_years INT DEFAULT 0,
    
    -- Expertise levels
    expertise_level ENUM('trainee', 'junior', 'bartender', 'senior', 'head_bartender', 'mixologist') DEFAULT 'bartender',
    specialty_tags TEXT,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_badge ENUM('none', 'bronze', 'silver', 'gold', 'platinum') DEFAULT 'none',
    
    -- Statistics
    total_verifications INT DEFAULT 0,
    tags_verified INT DEFAULT 0,
    tags_added INT DEFAULT 0,
    accuracy_score DECIMAL(5,2) DEFAULT 100.00,
    streak_days INT DEFAULT 0,
    
    -- Activity tracking
    last_login DATETIME,
    last_activity DATETIME,
    
    -- System fields
    email_verified_at DATETIME,
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_expertise_level (expertise_level),
    INDEX idx_is_active (is_active),
    INDEX idx_accuracy_score (accuracy_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. COCKTAIL-TAG RELATIONSHIP TABLE (CORE)
-- ============================================
CREATE TABLE cocktail_tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cocktail_id INT NOT NULL,
    tag_id INT NOT NULL,
    
    -- Status tracking
    status ENUM('pending', 'verified', 'rejected', 'disputed') DEFAULT 'pending',
    
    -- Source tracking
    source ENUM('original_dataset', 'expert_added', 'ai_suggested', 'user_suggested') DEFAULT 'original_dataset',
    
    -- Verification info
    verified_by INT,
    verified_at DATETIME,
    verification_notes TEXT,
    
    -- Confidence metrics
    confidence_score DECIMAL(5,2) DEFAULT 100.00,
    verification_count INT DEFAULT 0,
    
    -- Weight for recommendation system
    relevance_weight DECIMAL(5,2) DEFAULT 1.00,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (cocktail_id) REFERENCES cocktails(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES experts(id) ON DELETE SET NULL,
    
    -- Constraints
    UNIQUE KEY unique_cocktail_tag (cocktail_id, tag_id),
    
    -- Indexes
    INDEX idx_cocktail_status (cocktail_id, status),
    INDEX idx_tag_status (tag_id, status),
    INDEX idx_source (source),
    INDEX idx_verified_by (verified_by),
    INDEX idx_confidence (confidence_score),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. VERIFICATION LOGS TABLE
-- ============================================
CREATE TABLE verification_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expert_id INT NOT NULL,
    cocktail_id INT NOT NULL,
    
    -- Action details
    action_type ENUM('add_tag', 'remove_tag', 'verify_tag', 'reject_tag', 'dispute_tag', 'verify_cocktail', 'dispute_cocktail', 'add_comment', 'edit_tag_info') NOT NULL,
    
    tag_id INT,
    cocktail_tag_id INT,
    
    -- Value tracking
    old_value TEXT,
    new_value TEXT,
    notes TEXT,
    
    -- Context
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    -- Timestamp
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (expert_id) REFERENCES experts(id),
    FOREIGN KEY (cocktail_id) REFERENCES cocktails(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE SET NULL,
    FOREIGN KEY (cocktail_tag_id) REFERENCES cocktail_tags(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_expert_action (expert_id, action_type),
    INDEX idx_cocktail_action (cocktail_id, action_type),
    INDEX idx_tag_action (tag_id, action_type),
    INDEX idx_performed_at (performed_at),
    INDEX idx_action_type (action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. TAG SUGGESTIONS POOL
-- ============================================
CREATE TABLE tag_suggestions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tag_name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    category_id INT,
    description TEXT,
    rationale TEXT,
    
    -- Status
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'duplicate') DEFAULT 'pending',
    
    -- Suggestion info
    suggested_by INT NOT NULL,
    suggested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Review info
    reviewed_by INT,
    reviewed_at DATETIME,
    review_notes TEXT,
    
    -- If approved
    approved_tag_id INT,
    
    -- Foreign keys
    FOREIGN KEY (category_id) REFERENCES tag_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (suggested_by) REFERENCES experts(id),
    FOREIGN KEY (reviewed_by) REFERENCES experts(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_tag_id) REFERENCES tags(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_status (status),
    INDEX idx_suggested_by (suggested_by),
    INDEX idx_reviewed_by (reviewed_by),
    INDEX idx_suggested_at (suggested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. VERIFICATION QUEUE
-- ============================================
CREATE TABLE verification_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cocktail_tag_id INT NOT NULL,
    
    -- Assignment
    assigned_to INT,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'assigned', 'in_progress', 'completed', 'skipped') DEFAULT 'pending',
    
    -- Timing
    assigned_at DATETIME,
    started_at DATETIME,
    completed_at DATETIME,
    time_spent_seconds INT DEFAULT 0,
    
    -- Stats
    attempts INT DEFAULT 0,
    last_error TEXT,
    
    -- Foreign keys
    FOREIGN KEY (cocktail_tag_id) REFERENCES cocktail_tags(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES experts(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_assigned_status (assigned_to, status),
    INDEX idx_priority_status (priority, status),
    INDEX idx_status (status),
    UNIQUE KEY unique_cocktail_tag_queue (cocktail_tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. TAG SYNONYMS
-- ============================================
CREATE TABLE tag_synonyms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tag_id INT NOT NULL,
    synonym VARCHAR(100) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    language_code VARCHAR(10) DEFAULT 'en',
    
    -- Verification
    verified_by INT,
    verified_at DATETIME,
    
    -- System
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES experts(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES experts(id) ON DELETE SET NULL,
    
    -- Constraints
    UNIQUE KEY unique_synonym_language (synonym, language_code),
    
    -- Indexes
    INDEX idx_tag_id (tag_id),
    INDEX idx_synonym (synonym),
    INDEX idx_language (language_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. EXPERT SESSIONS
-- ============================================
CREATE TABLE expert_sessions (
    id VARCHAR(128) PRIMARY KEY,
    expert_id INT NOT NULL,
    
    -- Session data
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT,
    
    -- Activity tracking
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key
    FOREIGN KEY (expert_id) REFERENCES experts(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_expert_id (expert_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. COCKTAIL COMMENTS
-- ============================================
CREATE TABLE cocktail_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cocktail_id INT NOT NULL,
    expert_id INT NOT NULL,
    parent_id INT NULL,
    
    -- Comment content
    content TEXT NOT NULL,
    comment_type ENUM('general', 'tag_discussion', 'verification', 'question') DEFAULT 'general',
    
    -- Status
    is_resolved BOOLEAN DEFAULT FALSE,
    is_pinned BOOLEAN DEFAULT FALSE,
    
    -- Stats
    upvotes INT DEFAULT 0,
    downvotes INT DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (cocktail_id) REFERENCES cocktails(id) ON DELETE CASCADE,
    FOREIGN KEY (expert_id) REFERENCES experts(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES cocktail_comments(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_cocktail_id (cocktail_id),
    INDEX idx_expert_id (expert_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_resolved (is_resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. TAG VERIFICATION HISTORY
-- ============================================
CREATE TABLE tag_verification_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cocktail_tag_id INT NOT NULL,
    expert_id INT NOT NULL,
    
    -- Action details
    action ENUM('verify', 'reject', 'dispute', 'update_confidence', 'add_note', 'change_status') NOT NULL,
    previous_status ENUM('pending', 'verified', 'rejected', 'disputed') DEFAULT 'pending',
    new_status ENUM('pending', 'verified', 'rejected', 'disputed') DEFAULT 'pending',
    
    -- Changes
    previous_confidence_score DECIMAL(5,2),
    new_confidence_score DECIMAL(5,2),
    notes TEXT,
    
    -- Context
    reason ENUM('incorrect', 'duplicate', 'irrelevant', 'ambiguous', 'correct', 'improved', 'other') DEFAULT 'other',
    custom_reason TEXT,
    
    -- Timestamp
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (cocktail_tag_id) REFERENCES cocktail_tags(id) ON DELETE CASCADE,
    FOREIGN KEY (expert_id) REFERENCES experts(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_cocktail_tag_id (cocktail_tag_id),
    INDEX idx_expert_id (expert_id),
    INDEX idx_action (action),
    INDEX idx_performed_at (performed_at),
    INDEX idx_new_status (new_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 14. STATISTICS TRACKING
-- ============================================
CREATE TABLE verification_statistics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE UNIQUE NOT NULL,
    
    -- Daily counts
    experts_active INT DEFAULT 0,
    cocktails_verified INT DEFAULT 0,
    tags_verified INT DEFAULT 0,
    tags_added INT DEFAULT 0,
    suggestions_made INT DEFAULT 0,
    
    -- Averages
    avg_verification_time_seconds DECIMAL(10,2) DEFAULT 0,
    avg_expert_score DECIMAL(5,2) DEFAULT 0,
    
    -- System metrics
    total_cocktails INT DEFAULT 0,
    total_tags INT DEFAULT 0,
    total_experts INT DEFAULT 0,
    
    -- Timestamp
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 15. NOTIFICATIONS
-- ============================================
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expert_id INT NOT NULL,
    
    -- Notification content
    type ENUM('tag_assigned', 'verification_completed', 'comment_reply', 'system_announcement', 'achievement_unlocked') NOT NULL,
    
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data TEXT,
    
    -- Status
    is_read BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME,
    
    -- Foreign key
    FOREIGN KEY (expert_id) REFERENCES experts(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_expert_read (expert_id, is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 16. ACHIEVEMENTS
-- ============================================
CREATE TABLE achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon_url TEXT,
    criteria_type ENUM('verifications_count', 'tags_added', 'streak_days', 'accuracy_score', 'special_verification') NOT NULL,
    criteria_value INT NOT NULL,
    badge_tier ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    points_awarded INT DEFAULT 10,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_criteria_type (criteria_type),
    INDEX idx_badge_tier (badge_tier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 17. EXPERT ACHIEVEMENTS
-- ============================================
CREATE TABLE expert_achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expert_id INT NOT NULL,
    achievement_id INT NOT NULL,
    
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress_percent INT DEFAULT 100,
    
    FOREIGN KEY (expert_id) REFERENCES experts(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_expert_achievement (expert_id, achievement_id),
    INDEX idx_expert_id (expert_id),
    INDEX idx_unlocked_at (unlocked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 18. IMPORT BATCHES (Track data imports)
-- ============================================
CREATE TABLE import_batches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_name VARCHAR(100) NOT NULL,
    file_name VARCHAR(255),
    total_records INT DEFAULT 0,
    imported_records INT DEFAULT 0,
    failed_records INT DEFAULT 0,
    
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    started_at DATETIME,
    completed_at DATETIME,
    
    notes TEXT,
    created_by INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES experts(id) ON DELETE SET NULL,
    
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 19. TAG CATEGORY MAPPING (For tag categorization)
-- ============================================
CREATE TABLE tag_category_mapping (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tag_id INT NOT NULL,
    category_id INT NOT NULL,
    confidence DECIMAL(5,2) DEFAULT 100.00,
    mapped_by INT,
    mapped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES tag_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (mapped_by) REFERENCES experts(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_tag_category (tag_id, category_id),
    INDEX idx_tag_id (tag_id),
    INDEX idx_category_id (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 20. SYSTEM SETTINGS
-- ============================================
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json', 'array') DEFAULT 'string',
    category VARCHAR(50) DEFAULT 'general',
    description TEXT,
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    
    FOREIGN KEY (updated_by) REFERENCES experts(id) ON DELETE SET NULL,
    
    INDEX idx_setting_key (setting_key),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description) VALUES
('verification_threshold', '2', 'integer', 'verification', 'Minimum verifications required for a tag'),
('auto_assign_tasks', 'true', 'boolean', 'workflow', 'Automatically assign verification tasks'),
('max_daily_verifications', '50', 'integer', 'limits', 'Maximum verifications per expert per day'),
('default_confidence_score', '85', 'integer', 'scoring', 'Default confidence score for new tags'),
('require_expert_verification', 'true', 'boolean', 'security', 'Require expert verification for account'),
('tag_suggestion_approval', 'true', 'boolean', 'workflow', 'Require approval for new tag suggestions'),
('maintenance_mode', 'false', 'boolean', 'system', 'Maintenance mode status');

-- ============================================
-- TRIGGERS FOR DATA INTEGRITY
-- ============================================

-- Trigger 1: Update cocktail verification status
DELIMITER //
CREATE TRIGGER after_cocktail_tag_update
AFTER UPDATE ON cocktail_tags
FOR EACH ROW
BEGIN
    DECLARE total_tags_count INT;
    DECLARE verified_tags_count INT;
    
    -- Get counts for this cocktail
    SELECT COUNT(*), SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END)
    INTO total_tags_count, verified_tags_count
    FROM cocktail_tags
    WHERE cocktail_id = NEW.cocktail_id;
    
    -- Determine verification status
    SET @verification_status = CASE 
        WHEN verified_tags_count = 0 THEN 'pending'
        WHEN verified_tags_count = total_tags_count THEN 'fully_verified'
        ELSE 'partially_verified'
    END;
    
    -- Update cocktail table
    UPDATE cocktails 
    SET 
        total_tags = total_tags_count,
        verified_tags = verified_tags_count,
        verification_status = @verification_status,
        last_verified_by = NEW.verified_by,
        last_verified_at = NEW.verified_at,
        updated_at = NOW()
    WHERE id = NEW.cocktail_id;
END //
DELIMITER ;

-- Trigger 2: Update tag usage counts
DELIMITER //
CREATE TRIGGER after_cocktail_tag_insert
AFTER INSERT ON cocktail_tags
FOR EACH ROW
BEGIN
    UPDATE tags 
    SET 
        usage_count = usage_count + 1,
        verified_usage_count = verified_usage_count + (CASE WHEN NEW.status = 'verified' THEN 1 ELSE 0 END),
        updated_at = NOW()
    WHERE id = NEW.tag_id;
END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER after_cocktail_tag_delete
AFTER DELETE ON cocktail_tags
FOR EACH ROW
BEGIN
    UPDATE tags 
    SET 
        usage_count = GREATEST(0, usage_count - 1),
        verified_usage_count = GREATEST(0, verified_usage_count - (CASE WHEN OLD.status = 'verified' THEN 1 ELSE 0 END)),
        updated_at = NOW()
    WHERE id = OLD.tag_id;
END //
DELIMITER ;

-- Trigger 3: Update expert statistics
DELIMITER //
CREATE TRIGGER after_verification_log_insert
AFTER INSERT ON verification_logs
FOR EACH ROW
BEGIN
    -- Update expert stats based on action type
    IF NEW.action_type IN ('verify_tag', 'verify_cocktail') THEN
        UPDATE experts 
        SET 
            total_verifications = total_verifications + 1,
            tags_verified = tags_verified + (CASE WHEN NEW.tag_id IS NOT NULL THEN 1 ELSE 0 END),
            streak_days = CASE 
                WHEN DATE(last_activity) = DATE(NOW() - INTERVAL 1 DAY) THEN streak_days + 1
                WHEN DATE(last_activity) < DATE(NOW() - INTERVAL 1 DAY) THEN 1
                ELSE streak_days
            END,
            last_activity = NOW(),
            updated_at = NOW()
        WHERE id = NEW.expert_id;
        
    ELSEIF NEW.action_type = 'add_tag' THEN
        UPDATE experts 
        SET 
            tags_added = tags_added + 1,
            last_activity = NOW(),
            updated_at = NOW()
        WHERE id = NEW.expert_id;
    END IF;
END //
DELIMITER ;

-- Trigger 4: Log tag verification history
DELIMITER //
CREATE TRIGGER before_cocktail_tag_update
BEFORE UPDATE ON cocktail_tags
FOR EACH ROW
BEGIN
    -- Only log if status or confidence changed
    IF OLD.status != NEW.status OR OLD.confidence_score != NEW.confidence_score THEN
        INSERT INTO tag_verification_history (
            cocktail_tag_id,
            expert_id,
            action,
            previous_status,
            new_status,
            previous_confidence_score,
            new_confidence_score,
            notes
        ) VALUES (
            NEW.id,
            NEW.verified_by,
            CASE 
                WHEN NEW.status = 'verified' THEN 'verify'
                WHEN NEW.status = 'rejected' THEN 'reject'
                WHEN NEW.status = 'disputed' THEN 'dispute'
                ELSE 'change_status'
            END,
            OLD.status,
            NEW.status,
            OLD.confidence_score,
            NEW.confidence_score,
            NEW.verification_notes
        );
    END IF;
END //
DELIMITER ;

-- ============================================
-- STORED PROCEDURES
-- ============================================

-- Procedure 1: Add tag to cocktail
DELIMITER //
CREATE PROCEDURE sp_add_cocktail_tag(
    IN p_cocktail_id INT,
    IN p_tag_name VARCHAR(100),
    IN p_expert_id INT,
    IN p_source VARCHAR(20),
    IN p_confidence DECIMAL(5,2),
    IN p_notes TEXT
)
BEGIN
    DECLARE v_tag_id INT;
    DECLARE v_existing_id INT;
    DECLARE v_slug VARCHAR(120);
    
    -- Generate slug
    SET v_slug = LOWER(REPLACE(REPLACE(p_tag_name, ' ', '-'), ',', ''));
    
    -- Check if tag exists
    SELECT id INTO v_tag_id FROM tags WHERE tag_name = p_tag_name OR slug = v_slug;
    
    -- Create tag if it doesn't exist
    IF v_tag_id IS NULL THEN
        INSERT INTO tags (tag_name, slug, created_by) 
        VALUES (p_tag_name, v_slug, p_expert_id);
        SET v_tag_id = LAST_INSERT_ID();
    END IF;
    
    -- Check if cocktail already has this tag
    SELECT id INTO v_existing_id 
    FROM cocktail_tags 
    WHERE cocktail_id = p_cocktail_id AND tag_id = v_tag_id;
    
    IF v_existing_id IS NULL THEN
        -- Add new tag relationship
        INSERT INTO cocktail_tags (
            cocktail_id, 
            tag_id, 
            source, 
            verified_by, 
            verified_at,
            confidence_score,
            verification_notes,
            status
        ) VALUES (
            p_cocktail_id,
            v_tag_id,
            p_source,
            p_expert_id,
            NOW(),
            p_confidence,
            p_notes,
            'verified'
        );
        
        -- Log the action
        INSERT INTO verification_logs (
            expert_id,
            cocktail_id,
            action_type,
            tag_id,
            new_value
        ) VALUES (
            p_expert_id,
            p_cocktail_id,
            'add_tag',
            v_tag_id,
            p_tag_name
        );
        
        SELECT 'Tag added successfully' as message, LAST_INSERT_ID() as cocktail_tag_id;
    ELSE
        -- Update existing tag
        UPDATE cocktail_tags 
        SET 
            status = 'verified',
            verified_by = p_expert_id,
            verified_at = NOW(),
            verification_count = verification_count + 1,
            source = p_source,
            confidence_score = p_confidence,
            verification_notes = p_notes
        WHERE id = v_existing_id;
        
        SELECT 'Tag updated successfully' as message, v_existing_id as cocktail_tag_id;
    END IF;
END //
DELIMITER ;

-- Procedure 2: Verify a tag
DELIMITER //
CREATE PROCEDURE sp_verify_cocktail_tag(
    IN p_cocktail_tag_id INT,
    IN p_expert_id INT,
    IN p_confidence DECIMAL(5,2),
    IN p_notes TEXT
)
BEGIN
    DECLARE v_cocktail_id INT;
    DECLARE v_tag_id INT;
    DECLARE v_tag_name VARCHAR(100);
    
    -- Get current values
    SELECT ct.cocktail_id, ct.tag_id, t.tag_name 
    INTO v_cocktail_id, v_tag_id, v_tag_name
    FROM cocktail_tags ct
    JOIN tags t ON ct.tag_id = t.id
    WHERE ct.id = p_cocktail_tag_id;
    
    IF v_cocktail_id IS NOT NULL THEN
        -- Update tag status
        UPDATE cocktail_tags 
        SET 
            status = 'verified',
            verified_by = p_expert_id,
            verified_at = NOW(),
            verification_count = verification_count + 1,
            confidence_score = p_confidence,
            verification_notes = CONCAT_WS('\n', verification_notes, p_notes)
        WHERE id = p_cocktail_tag_id;
        
        -- Log the action
        INSERT INTO verification_logs (
            expert_id,
            cocktail_id,
            action_type,
            tag_id,
            new_value,
            notes
        ) VALUES (
            p_expert_id,
            v_cocktail_id,
            'verify_tag',
            v_tag_id,
            'verified',
            p_notes
        );
        
        SELECT 'Tag verified successfully' as message;
    ELSE
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Cocktail tag not found';
    END IF;
END //
DELIMITER ;

-- Procedure 3: Reject a tag
DELIMITER //
CREATE PROCEDURE sp_reject_cocktail_tag(
    IN p_cocktail_tag_id INT,
    IN p_expert_id INT,
    IN p_reason ENUM('incorrect', 'duplicate', 'irrelevant', 'ambiguous', 'other'),
    IN p_custom_reason TEXT,
    IN p_notes TEXT
)
BEGIN
    DECLARE v_cocktail_id INT;
    DECLARE v_tag_id INT;
    
    -- Get current values
    SELECT cocktail_id, tag_id 
    INTO v_cocktail_id, v_tag_id
    FROM cocktail_tags 
    WHERE id = p_cocktail_tag_id;
    
    IF v_cocktail_id IS NOT NULL THEN
        -- Update tag status
        UPDATE cocktail_tags 
        SET 
            status = 'rejected',
            verified_by = p_expert_id,
            verified_at = NOW(),
            verification_notes = CONCAT_WS('\n', verification_notes, p_notes)
        WHERE id = p_cocktail_tag_id;
        
        -- Log the action
        INSERT INTO verification_logs (
            expert_id,
            cocktail_id,
            action_type,
            tag_id,
            new_value,
            notes
        ) VALUES (
            p_expert_id,
            v_cocktail_id,
            'reject_tag',
            v_tag_id,
            'rejected',
            CONCAT_WS(' - ', p_reason, p_custom_reason, p_notes)
        );
        
        SELECT 'Tag rejected successfully' as message;
    ELSE
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Cocktail tag not found';
    END IF;
END //
DELIMITER ;

-- Procedure 4: Get cocktails needing verification
DELIMITER //
CREATE PROCEDURE sp_get_cocktails_needing_verification(
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    SELECT 
        c.id,
        c.idDrink,
        c.strDrink,
        c.strDrinkThumb,
        c.strCategory,
        c.strAlcoholic,
        c.strGlass,
        c.verification_status,
        c.total_tags,
        c.verified_tags,
        COUNT(ct.id) as pending_tags_count,
        GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name) as pending_tag_names
    FROM cocktails c
    LEFT JOIN cocktail_tags ct ON c.id = ct.cocktail_id AND ct.status = 'pending'
    LEFT JOIN tags t ON ct.tag_id = t.id
    WHERE c.verification_status IN ('pending', 'partially_verified')
    GROUP BY c.id
    ORDER BY c.updated_at ASC, c.total_tags DESC
    LIMIT p_limit OFFSET p_offset;
END //
DELIMITER ;

-- ============================================
-- VIEWS FOR REPORTING
-- ============================================

-- View 1: Cocktails needing verification
CREATE VIEW v_cocktails_needing_verification AS
SELECT 
    c.id,
    c.idDrink,
    c.strDrink,
    c.strDrinkThumb,
    c.strCategory,
    c.strAlcoholic,
    c.strGlass,
    c.verification_status,
    c.total_tags,
    c.verified_tags,
    (c.total_tags - c.verified_tags) as pending_tags,
    c.last_verified_at,
    c.updated_at
FROM cocktails c
WHERE c.verification_status IN ('pending', 'partially_verified')
ORDER BY c.updated_at ASC;

-- View 2: Expert performance dashboard
CREATE VIEW v_expert_performance AS
SELECT 
    e.id,
    e.username,
    e.full_name,
    e.expertise_level,
    e.accuracy_score,
    e.total_verifications,
    e.tags_verified,
    e.tags_added,
    e.streak_days,
    COUNT(DISTINCT vq.id) as assigned_tasks,
    COUNT(DISTINCT ts.id) as pending_suggestions,
    e.last_activity,
    DATEDIFF(NOW(), e.created_at) as days_since_join
FROM experts e
LEFT JOIN verification_queue vq ON e.id = vq.assigned_to AND vq.status IN ('assigned', 'in_progress')
LEFT JOIN tag_suggestions ts ON e.id = ts.suggested_by AND ts.status = 'pending'
WHERE e.is_active = TRUE
GROUP BY e.id
ORDER BY e.total_verifications DESC;

-- View 3: Tag statistics
CREATE VIEW v_tag_statistics AS
SELECT 
    t.id,
    t.tag_name,
    tc.name as category,
    t.usage_count,
    t.verified_usage_count,
    t.verification_score,
    COUNT(DISTINCT ct.cocktail_id) as cocktail_count,
    COUNT(DISTINCT ct.verified_by) as verifier_count,
    AVG(ct.confidence_score) as avg_confidence,
    MAX(ct.verified_at) as last_verified
FROM tags t
LEFT JOIN tag_categories tc ON t.category_id = tc.id
LEFT JOIN cocktail_tags ct ON t.id = ct.tag_id
GROUP BY t.id
ORDER BY t.usage_count DESC;

-- View 4: Daily verification summary
CREATE VIEW v_daily_verification_summary AS
SELECT 
    DATE(vl.performed_at) as verification_date,
    COUNT(DISTINCT vl.expert_id) as active_experts,
    COUNT(CASE WHEN vl.action_type = 'verify_tag' THEN 1 END) as tags_verified,
    COUNT(CASE WHEN vl.action_type = 'add_tag' THEN 1 END) as tags_added,
    COUNT(DISTINCT vl.cocktail_id) as cocktails_touched,
    AVG(TIME_TO_SEC(TIMEDIFF(vq.completed_at, vq.started_at))) as avg_verification_time
FROM verification_logs vl
LEFT JOIN verification_queue vq ON vl.cocktail_tag_id = vq.cocktail_tag_id
WHERE DATE(vl.performed_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(vl.performed_at)
ORDER BY verification_date DESC;

-- ============================================
-- INITIAL DATA
-- ============================================

-- Insert sample achievements
INSERT INTO achievements (name, description, criteria_type, criteria_value, badge_tier, points_awarded) VALUES
('First Step', 'Complete your first tag verification', 'verifications_count', 1, 'bronze', 10),
('Consistent Contributor', 'Verify 50 tags', 'verifications_count', 50, 'silver', 50),
('Tag Master', 'Verify 200 tags', 'verifications_count', 200, 'gold', 150),
('Perfect Week', 'Maintain a 7-day verification streak', 'streak_days', 7, 'silver', 100),
('Accuracy Pro', 'Maintain 95%+ accuracy score', 'accuracy_score', 95, 'platinum', 250),
('Pioneer', 'Add 20 new tags to the system', 'tags_added', 20, 'gold', 200);

COMMIT;

-- ============================================
-- DATABASE USER AND PERMISSIONS (Run separately)
-- ============================================
/*
-- Create application user
CREATE USER 'cocktail_app'@'localhost' IDENTIFIED BY 'YourSecurePassword123!';
GRANT SELECT, INSERT, UPDATE, DELETE ON cocktail_verification_db.* TO 'cocktail_app'@'localhost';

-- Create admin user
CREATE USER 'cocktail_admin'@'localhost' IDENTIFIED BY 'AdminSecurePassword456!';
GRANT ALL PRIVILEGES ON cocktail_verification_db.* TO 'cocktail_admin'@'localhost';

-- Create read-only user for reports
CREATE USER 'cocktail_report'@'localhost' IDENTIFIED BY 'ReportPassword789!';
GRANT SELECT ON cocktail_verification_db.* TO 'cocktail_report'@'localhost';

FLUSH PRIVILEGES;
*/

-- ============================================
-- INDEX OPTIMIZATION
-- ============================================
-- Additional indexes for performance
CREATE INDEX idx_cocktails_verification ON cocktails(verification_status, updated_at);
CREATE INDEX idx_cocktail_tags_composite ON cocktail_tags(cocktail_id, status, confidence_score);
CREATE INDEX idx_tags_usage ON tags(usage_count DESC, is_verified);
CREATE INDEX idx_experts_activity ON experts(is_active, last_activity DESC);
CREATE INDEX idx_verification_logs_composite ON verification_logs(performed_at DESC, expert_id, action_type);

-- ============================================
-- EVENT SCHEDULER (For maintenance)
-- ============================================

-- Enable event scheduler if not already enabled (This should be run separately in MySQL)
-- SET GLOBAL event_scheduler = ON;  -- Remove this line from SQL file, run manually

-- Daily statistics calculation
DELIMITER //
CREATE EVENT IF NOT EXISTS e_daily_statistics
ON SCHEDULE EVERY 1 DAY
STARTS CONCAT(CURDATE() + INTERVAL 1 DAY, ' 23:59:59')
DO
BEGIN
    -- Calculate daily statistics
    INSERT INTO verification_statistics (
        date,
        experts_active,
        cocktails_verified,
        tags_verified,
        tags_added,
        suggestions_made,
        avg_verification_time_seconds,
        avg_expert_score,
        total_cocktails,
        total_tags,
        total_experts,
        calculated_at
    )
    SELECT 
        CURDATE() - INTERVAL 1 DAY as date,
        COUNT(DISTINCT expert_id) as experts_active,
        COUNT(DISTINCT CASE WHEN action_type = 'verify_cocktail' THEN cocktail_id END) as cocktails_verified,
        COUNT(CASE WHEN action_type = 'verify_tag' THEN 1 END) as tags_verified,
        COUNT(CASE WHEN action_type = 'add_tag' THEN 1 END) as tags_added,
        COUNT(DISTINCT CASE WHEN action_type = 'add_comment' THEN 1 END) as suggestions_made,
        COALESCE(AVG(TIME_TO_SEC(TIMEDIFF(vq.completed_at, vq.started_at))), 0) as avg_verification_time_seconds,
        COALESCE(AVG(e.accuracy_score), 0) as avg_expert_score,
        (SELECT COUNT(*) FROM cocktails) as total_cocktails,
        (SELECT COUNT(*) FROM tags) as total_tags,
        (SELECT COUNT(*) FROM experts WHERE is_active = TRUE) as total_experts,
        NOW() as calculated_at
    FROM verification_logs vl
    LEFT JOIN verification_queue vq ON vl.cocktail_tag_id = vq.cocktail_tag_id
    LEFT JOIN experts e ON vl.expert_id = e.id
    WHERE DATE(vl.performed_at) = CURDATE() - INTERVAL 1 DAY;
END //
DELIMITER ;

-- Cleanup old sessions (older than 24 hours)
DELIMITER //
CREATE EVENT IF NOT EXISTS e_cleanup_sessions
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    DELETE FROM expert_sessions 
    WHERE last_activity < NOW() - INTERVAL 24 HOUR;
END //
DELIMITER ;

-- Cleanup expired verification queue items (older than 7 days)
DELIMITER //
CREATE EVENT IF NOT EXISTS e_cleanup_verification_queue
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    DELETE FROM verification_queue 
    WHERE 
        (status = 'completed' AND completed_at < NOW() - INTERVAL 7 DAY)
        OR (status = 'skipped' AND completed_at < NOW() - INTERVAL 3 DAY);
END //
DELIMITER ;

-- Update expert streak days
DELIMITER //
CREATE EVENT IF NOT EXISTS e_update_expert_streaks
ON SCHEDULE EVERY 1 DAY
STARTS CONCAT(CURDATE() + INTERVAL 1 DAY, ' 02:00:00')
DO
BEGIN
    -- Reset streak for experts inactive for more than 1 day
    UPDATE experts e
    LEFT JOIN (
        SELECT expert_id, MAX(performed_at) as last_verification
        FROM verification_logs 
        WHERE DATE(performed_at) >= CURDATE() - INTERVAL 2 DAY
        GROUP BY expert_id
    ) vl ON e.id = vl.expert_id
    SET e.streak_days = 
        CASE 
            WHEN vl.last_verification >= CURDATE() - INTERVAL 1 DAY THEN 
                -- Active yesterday, increment streak
                e.streak_days + 1
            WHEN vl.last_verification >= CURDATE() - INTERVAL 2 DAY AND vl.last_verification < CURDATE() - INTERVAL 1 DAY THEN 
                -- Active day before yesterday but not yesterday, reset to 1
                1
            ELSE 
                -- Inactive for 2+ days, reset to 0
                0
        END
    WHERE e.is_active = TRUE;
END //
DELIMITER ;

-- Update tag confidence scores based on verification count
DELIMITER //
CREATE EVENT IF NOT EXISTS e_update_tag_confidence
ON SCHEDULE EVERY 1 DAY
STARTS CONCAT(CURDATE() + INTERVAL 1 DAY, ' 03:00:00')
DO
BEGIN
    -- Update tag verification scores based on verification count and agreement
    UPDATE tags t
    JOIN (
        SELECT 
            tag_id,
            COUNT(DISTINCT verified_by) as total_verifiers,
            COUNT(*) as total_verifications,
            AVG(confidence_score) as avg_confidence,
            CASE 
                WHEN COUNT(DISTINCT verified_by) >= 3 THEN 100
                WHEN COUNT(DISTINCT verified_by) = 2 THEN 85
                ELSE 70
            END as calculated_score
        FROM cocktail_tags 
        WHERE status = 'verified' AND verified_by IS NOT NULL
        GROUP BY tag_id
    ) ct ON t.id = ct.tag_id
    SET 
        t.verification_score = ct.calculated_score,
        t.is_verified = (ct.calculated_score >= 80),
        t.updated_at = NOW()
    WHERE t.verification_score != ct.calculated_score;
END //
DELIMITER ;