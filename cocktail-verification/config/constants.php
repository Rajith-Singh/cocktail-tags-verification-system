<?php
// User Roles
define('ROLE_TRAINEE', 'trainee');
define('ROLE_JUNIOR', 'junior');
define('ROLE_BARTENDER', 'bartender');
define('ROLE_SENIOR', 'senior');
define('ROLE_HEAD_BARTENDER', 'head_bartender');
define('ROLE_MIXOLOGIST', 'mixologist');

// Tag Status
define('TAG_PENDING', 'pending');
define('TAG_VERIFIED', 'verified');
define('TAG_REJECTED', 'rejected');
define('TAG_DISPUTED', 'disputed');

// Cocktail Status
define('COCKTAIL_PENDING', 'pending');
define('COCKTAIL_PARTIALLY_VERIFIED', 'partially_verified');
define('COCKTAIL_FULLY_VERIFIED', 'fully_verified');

// Verification Actions
define('ACTION_VERIFY', 'verify_tag');
define('ACTION_REJECT', 'reject_tag');
define('ACTION_ADD', 'add_tag');
define('ACTION_REMOVE', 'remove_tag');
define('ACTION_DISPUTE', 'dispute_tag');

// Colors for tags
$GLOBALS['tagColors'] = [
    'Flavor' => '#EF4444',
    'Occasion' => '#3B82F6',
    'Season' => '#10B981',
    'Type' => '#8B5CF6',
    'Ingredient' => '#F59E0B',
    'Texture' => '#EC4899',
    'Strength' => '#6366F1',
    'Temperature' => '#06B6D4',
    'Complexity' => '#84CC16'
];

// Expert levels with colors and icons
$GLOBALS['expertLevels'] = [
    'trainee' => ['name' => 'Trainee', 'color' => '#6B7280', 'icon' => '<i class="fas fa-user"></i>'],
    'junior' => ['name' => 'Junior', 'color' => '#3B82F6', 'icon' => '<i class="fas fa-user-graduate"></i>'],
    'bartender' => ['name' => 'Bartender', 'color' => '#10B981', 'icon' => '<i class="fas fa-glass-martini-alt"></i>'],
    'senior' => ['name' => 'Senior', 'color' => '#F59E0B', 'icon' => '<i class="fas fa-user-tie"></i>'],
    'head_bartender' => ['name' => 'Head Bartender', 'color' => '#8B5CF6', 'icon' => '<i class="fas fa-crown"></i>'],
    'mixologist' => ['name' => 'Mixologist', 'color' => '#EF4444', 'icon' => '<i class="fas fa-flask-vial"></i>']
];

// Pagination
define('ITEMS_PER_PAGE', 20);
define('MAX_EXPORT_SIZE', 10000); // Maximum records per export

// Session
define('SESSION_TIMEOUT', 3600); // 1 hour
define('REMEMBER_DURATION', 2592000); // 30 days

// File uploads
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['csv', 'json', 'xlsx']);

// Security
define('MIN_PASSWORD_LENGTH', 8);
define('PASSWORD_REQUIREMENTS', [
    'uppercase' => true,
    'lowercase' => true,
    'numbers' => true,
    'special' => true
]);

// Email
define('MAIL_FROM', 'noreply@cocktail-verification.com');
define('MAIL_FROM_NAME', 'Cocktail Verification System');

// API
define('API_RATE_LIMIT', 100); // requests per minute
define('API_TIMEOUT', 30); // seconds

// Database
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// Features
define('ENABLE_REGISTRATION', true);
define('REQUIRE_EMAIL_VERIFICATION', false);
define('AUTO_ASSIGN_BADGES', true);
define('MAINTENANCE_MODE', false);
?>