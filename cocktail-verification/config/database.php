<?php
// Database configuration - WITH DEBUG MODE
// define('DB_HOST', getenv('DB_HOST') ?: '185.187.241.1');
// define('DB_USER', getenv('DB_USER') ?: 'u974801020_sys_testing');
// define('DB_PASS', getenv('DB_PASS') ?: 'ig!T^P?G7');
// define('DB_NAME', getenv('DB_NAME') ?: 'u974801020_testing');

define('DB_HOST', '185.187.241.1');
define('DB_NAME', 'u974801020_cocktail_ai');
define('DB_USER', 'u974801020_system_admin');
define('DB_PASS', 'error404@PHP');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_DEBUG', true);

// Connection pooling configuration
define('DB_PERSISTENT', true); // Enable persistent connections
define('DB_POOL_SIZE', 1); // Use single persistent connection per process
define('DB_TIMEOUT', 10);

// Application configuration
define('APP_NAME', 'Cocktail Tags Verification System');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', __DIR__ . '/../');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost:8001/');
define('ASSETS_PATH', SITE_URL . 'assets/');

// Error reporting based on environment
if (DB_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_name('cocktail_verifier');
    session_start();
}

// Create cache directory if it doesn't exist
$cachePath = __DIR__ . '/../storage/cache/';
if (!file_exists($cachePath)) {
    mkdir($cachePath, 0755, true);
}

/**
 * Get database connection with connection pooling
 * Uses persistent connections to reduce connection creation overhead
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            if (DB_DEBUG) {
                error_log("Creating NEW database connection...");
            }
            
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => DB_TIMEOUT,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, SESSION sql_mode='STRICT_TRANS_TABLES'",
                PDO::ATTR_PERSISTENT => DB_PERSISTENT, // Set persistent here
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Set connection timeouts - ONLY ONCE when connection is created
            $pdo->exec("SET SESSION wait_timeout=300, interactive_timeout=300"); // 5 minutes idle timeout
            
            if (DB_DEBUG) {
                error_log("Database connection established successfully!");
            }
            
        } catch (PDOException $e) {
            // ... error handling ...
        }
    } else {
        if (DB_DEBUG) {
            error_log("Reusing existing database connection...");
        }
    }
    
    return $pdo;
}

/**
 * Test database connection
 */
function testDatabaseConnection() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        return !empty($result['test']);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['expert_id']) && $_SESSION['expert_id'] > 0;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . 'login.php');
        exit();
    }
}

/**
 * Require admin access
 */
function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . 'login.php');
        exit();
    }
    
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . 'dashboard.php');
        exit();
    }
}

/**
 * Generate CSRF token
 */
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * HTML escape function with null handling
 */
function h($string) {
    if ($string === null || $string === '') {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Get YouTube ID from URL
 */
function getYouTubeId($url) {
    if (empty($url)) {
        return null;
    }
    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/)([^"&?\s]{11})|youtu\.be/([^"&?\s]{11}))%i', $url, $match);
    return $match[1] ?? $match[2] ?? null;
}

/**
 * Get expert levels
 */
function getExpertLevels() {
    return [
        'trainee' => ['name' => 'Trainee', 'color' => '#6B7280', 'icon' => '👶'],
        'junior' => ['name' => 'Junior', 'color' => '#3B82F6', 'icon' => '👨‍🎓'],
        'bartender' => ['name' => 'Bartender', 'color' => '#10B981', 'icon' => '🍸'],
        'senior' => ['name' => 'Senior', 'color' => '#F59E0B', 'icon' => '👨‍💼'],
        'head_bartender' => ['name' => 'Head Bartender', 'color' => '#8B5CF6', 'icon' => '👑'],
        'mixologist' => ['name' => 'Mixologist', 'color' => '#EF4444', 'icon' => '🎩']
    ];
}

/**
 * Get tag colors
 */
function getTagColors() {
    return [
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
}

// Make available globally
$GLOBALS['expertLevels'] = getExpertLevels();
$GLOBALS['tagColors'] = getTagColors();

// Register shutdown function to close connections properly
register_shutdown_function(function() {
    if (DB_DEBUG) {
        error_log("Script execution completed. Connection will be reused.");
    }
});

?>