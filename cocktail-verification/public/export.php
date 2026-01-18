<?php
// Start output buffering to capture any errors
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/CocktailManager.php';

requireLogin();

$auth = new Auth();
$cocktailManager = new CocktailManager();
$expert = $auth->getCurrentExpert();
$pdo = getDB();

$message = '';
$messageType = '';
$exportFormat = $_GET['format'] ?? 'csv';
$exportType = $_GET['type'] ?? 'all';
$download = $_GET['download'] ?? false;

// Handle export
if ($download === 'true') {
    try {
        $data = [];
        $processedKeys = [];
        
        // Get verified tags
        if ($exportType === 'all' || $exportType === 'verified') {
            $verifiedStmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.strDrink,
                    c.strCategory,
                    c.strAlcoholic,
                    c.strGlass,
                    c.verification_status,
                    t.tag_name,
                    tc.name as tag_category,
                    ct.status,
                    ct.confidence_score,
                    e.full_name,
                    e.username,
                    e.expertise_level,
                    ct.verified_at,
                    ct.source,
                    ct.verification_notes
                FROM cocktails c
                INNER JOIN cocktail_tags ct ON c.id = ct.cocktail_id
                INNER JOIN tags t ON ct.tag_id = t.id
                LEFT JOIN tag_categories tc ON t.category_id = tc.id
                LEFT JOIN experts e ON ct.verified_by = e.id
                WHERE ct.status = 'verified'
                ORDER BY c.id, t.tag_name
            ");
            
            $verifiedStmt->execute();
            $verifiedTags = $verifiedStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($verifiedTags as $row) {
                $key = $row['id'] . '_' . $row['tag_name'];
                if (!isset($processedKeys[$key])) {
                    $data[] = [
                        'cocktail_id' => $row['id'] ?? '',
                        'cocktail_name' => $row['strDrink'] ?? '',
                        'category' => $row['strCategory'] ?? '',
                        'alcoholic' => $row['strAlcoholic'] ?? '',
                        'glass_type' => $row['strGlass'] ?? '',
                        'verification_status' => $row['verification_status'] ?? '',
                        'tag_name' => $row['tag_name'] ?? '',
                        'tag_category' => $row['tag_category'] ?? '',
                        'tag_status' => $row['status'] ?? '',
                        'confidence_score' => $row['confidence_score'] ?? '',
                        'verified_by' => $row['full_name'] ?? '',
                        'verified_username' => $row['username'] ?? '',
                        'expertise_level' => $row['expertise_level'] ?? '',
                        'verified_date' => $row['verified_at'] ?? '',
                        'source' => $row['source'] ?? '',
                        'notes' => $row['verification_notes'] ?? ''
                    ];
                    $processedKeys[$key] = true;
                }
            }
        }
        
        // Get pending tags
        if ($exportType === 'all' || $exportType === 'pending') {
            $pendingStmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.strDrink,
                    c.strCategory,
                    c.strAlcoholic,
                    c.strGlass,
                    c.verification_status,
                    t.tag_name,
                    tc.name as tag_category,
                    ct.status,
                    ct.confidence_score,
                    ct.source,
                    ct.created_at
                FROM cocktails c
                INNER JOIN cocktail_tags ct ON c.id = ct.cocktail_id
                INNER JOIN tags t ON ct.tag_id = t.id
                LEFT JOIN tag_categories tc ON t.category_id = tc.id
                WHERE ct.status = 'pending'
                ORDER BY c.id, t.tag_name
            ");
            
            $pendingStmt->execute();
            $pendingTags = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($pendingTags as $row) {
                $key = $row['id'] . '_' . $row['tag_name'];
                if (!isset($processedKeys[$key])) {
                    $data[] = [
                        'cocktail_id' => $row['id'] ?? '',
                        'cocktail_name' => $row['strDrink'] ?? '',
                        'category' => $row['strCategory'] ?? '',
                        'alcoholic' => $row['strAlcoholic'] ?? '',
                        'glass_type' => $row['strGlass'] ?? '',
                        'verification_status' => $row['verification_status'] ?? '',
                        'tag_name' => $row['tag_name'] ?? '',
                        'tag_category' => $row['tag_category'] ?? '',
                        'tag_status' => $row['status'] ?? 'pending',
                        'confidence_score' => $row['confidence_score'] ?? '',
                        'verified_by' => 'Pending',
                        'verified_username' => 'N/A',
                        'expertise_level' => 'N/A',
                        'verified_date' => 'N/A',
                        'source' => $row['source'] ?? '',
                        'notes' => 'Awaiting verification'
                    ];
                    $processedKeys[$key] = true;
                }
            }
        }
        
        // Get suggested tags (expert_added source)
        if ($exportType === 'all' || $exportType === 'suggested') {
            $suggestedStmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.strDrink,
                    c.strCategory,
                    c.strAlcoholic,
                    c.strGlass,
                    c.verification_status,
                    t.tag_name,
                    e.full_name,
                    e.username,
                    e.expertise_level,
                    ct.created_at,
                    ct.verified_at
                FROM cocktails c
                INNER JOIN cocktail_tags ct ON c.id = ct.cocktail_id
                INNER JOIN tags t ON ct.tag_id = t.id
                INNER JOIN experts e ON ct.verified_by = e.id
                WHERE ct.source = 'expert_added'
                ORDER BY c.id, t.tag_name
            ");
            
            $suggestedStmt->execute();
            $suggestedTags = $suggestedStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($suggestedTags as $row) {
                $key = $row['id'] . '_' . $row['tag_name'] . '_suggested';
                if (!isset($processedKeys[$key])) {
                    $data[] = [
                        'cocktail_id' => $row['id'] ?? '',
                        'cocktail_name' => $row['strDrink'] ?? '',
                        'category' => $row['strCategory'] ?? '',
                        'alcoholic' => $row['strAlcoholic'] ?? '',
                        'glass_type' => $row['strGlass'] ?? '',
                        'verification_status' => $row['verification_status'] ?? '',
                        'tag_name' => $row['tag_name'] ?? '',
                        'tag_category' => 'Suggested',
                        'tag_status' => 'suggested',
                        'confidence_score' => '90',
                        'verified_by' => $row['full_name'] ?? '',
                        'verified_username' => $row['username'] ?? '',
                        'expertise_level' => $row['expertise_level'] ?? '',
                        'verified_date' => $row['verified_at'] ?? $row['created_at'] ?? '',
                        'source' => 'expert_added',
                        'notes' => 'Expert suggested tag'
                    ];
                    $processedKeys[$key] = true;
                }
            }
        }
        
        // Check if we have data
        if (empty($data)) {
            throw new Exception("No data available for export with the selected criteria");
        }
        
        // Generate file based on format
        if ($exportFormat === 'csv') {
            generateCSV($data);
        } elseif ($exportFormat === 'json') {
            generateJSON($data);
        } else {
            throw new Exception("Invalid export format");
        }
        
    } catch (Exception $e) {
        // Clear any output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Set content type back to HTML for error display
        header('Content-Type: text/html; charset=utf-8');
        
        $message = "Export failed: " . $e->getMessage();
        $messageType = "danger";
        error_log("Export error: " . $e->getMessage());
    }
}

/**
 * Generate CSV file with proper fputcsv parameters
 */
function generateCSV($data) {
    if (empty($data)) {
        throw new Exception("No data to export");
    }
    
    $filename = 'cocktail-verification-' . date('Y-m-d-His') . '.csv';
    
    // Clear ALL output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Set headers for file download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    if ($output === false) {
        throw new Exception("Failed to open output stream");
    }
    
    // Write UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    // Write header row with explicit parameters for PHP 8.1+
    if (!empty($data)) {
        // Use proper fputcsv parameters: (resource, fields, delimiter, enclosure, escape)
        fputcsv($output, array_keys($data[0]), ',', '"', '\\');
        
        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, $row, ',', '"', '\\');
        }
    }
    
    fclose($output);
    exit(0);
}

/**
 * Generate JSON file
 */
function generateJSON($data) {
    if (empty($data)) {
        throw new Exception("No data to export");
    }
    
    $filename = 'cocktail-verification-' . date('Y-m-d-His') . '.json';
    
    // Clear ALL output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Prepare JSON data
    $jsonData = json_encode([
        'export_date' => date('Y-m-d H:i:s'),
        'export_type' => $_GET['type'] ?? 'all',
        'total_records' => count($data),
        'data' => $data
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    if ($jsonData === false) {
        throw new Exception("Failed to encode JSON: " . json_last_error_msg());
    }
    
    // Set headers for file download
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($jsonData));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Write UTF-8 BOM
    echo chr(0xEF) . chr(0xBB) . chr(0xBF);
    
    // Output JSON
    echo $jsonData;
    exit(0);
}

// Clear the output buffer started at the beginning
ob_end_clean();

$pageTitle = "Export Data";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="glass-card p-4 mb-4">
        <h2 class="fw-bold mb-2">
            <i class="fas fa-download me-2"></i>Export Verified Data
        </h2>
        <p class="text-muted mb-0">Download comprehensive cocktail verification data with all tags and details</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4">
        <i class="fas fa-<?php echo $messageType === 'danger' ? 'exclamation-circle' : 'check-circle'; ?> me-2"></i>
        <?php echo h($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Export Options Card -->
        <div class="col-lg-8 mb-4">
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-cog me-2"></i>Export Options
                </h5>

                <form method="GET" action="" id="exportForm">
                    <input type="hidden" name="download" value="true">
                    
                    <!-- Data Type Selection -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">
                            <span class="badge bg-primary me-2">1</span>Select Data Type
                        </h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded export-option" style="cursor: pointer;">
                                    <input class="form-check-input" type="radio" name="type" 
                                           value="all" id="typeAll" <?php echo ($exportType === 'all') ? 'checked' : ''; ?>>
                                    <label class="form-check-label w-100" for="typeAll">
                                        <strong>All Data</strong>
                                        <small class="d-block text-muted">Verified, pending, and suggested tags</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded export-option" style="cursor: pointer;">
                                    <input class="form-check-input" type="radio" name="type" 
                                           value="verified" id="typeVerified" <?php echo ($exportType === 'verified') ? 'checked' : ''; ?>>
                                    <label class="form-check-label w-100" for="typeVerified">
                                        <strong>Verified Tags Only</strong>
                                        <small class="d-block text-muted">Confirmed and verified tags</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded export-option" style="cursor: pointer;">
                                    <input class="form-check-input" type="radio" name="type" 
                                           value="pending" id="typePending" <?php echo ($exportType === 'pending') ? 'checked' : ''; ?>>
                                    <label class="form-check-label w-100" for="typePending">
                                        <strong>Pending Tags Only</strong>
                                        <small class="d-block text-muted">Awaiting verification</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded export-option" style="cursor: pointer;">
                                    <input class="form-check-input" type="radio" name="type" 
                                           value="suggested" id="typeSuggested" <?php echo ($exportType === 'suggested') ? 'checked' : ''; ?>>
                                    <label class="form-check-label w-100" for="typeSuggested">
                                        <strong>Expert Suggestions</strong>
                                        <small class="d-block text-muted">Tags suggested by experts</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Format Selection -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">
                            <span class="badge bg-primary me-2">2</span>Select File Format
                        </h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded export-option" style="cursor: pointer;">
                                    <input class="form-check-input" type="radio" name="format" 
                                           value="csv" id="formatCSV" <?php echo ($exportFormat === 'csv') ? 'checked' : ''; ?>>
                                    <label class="form-check-label w-100" for="formatCSV">
                                        <i class="fas fa-file-csv me-2" style="color: #10B981;"></i>
                                        <strong>CSV Format</strong>
                                        <small class="d-block text-muted">Excel compatible spreadsheet</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded export-option" style="cursor: pointer;">
                                    <input class="form-check-input" type="radio" name="format" 
                                           value="json" id="formatJSON" <?php echo ($exportFormat === 'json') ? 'checked' : ''; ?>>
                                    <label class="form-check-label w-100" for="formatJSON">
                                        <i class="fas fa-file-code me-2" style="color: #F59E0B;"></i>
                                        <strong>JSON Format</strong>
                                        <small class="d-block text-muted">Structured data format</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Export Statistics -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">
                            <span class="badge bg-primary me-2">3</span>Export Statistics
                        </h6>
                        <div class="row g-3">
                            <?php
                            try {
                                $verifiedStmt = $pdo->query("SELECT COUNT(*) as count FROM cocktail_tags WHERE status = 'verified'");
                                $verifiedCount = $verifiedStmt->fetch()['count'] ?? 0;
                                
                                $pendingStmt = $pdo->query("SELECT COUNT(*) as count FROM cocktail_tags WHERE status = 'pending'");
                                $pendingCount = $pendingStmt->fetch()['count'] ?? 0;
                                
                                $suggestedStmt = $pdo->query("SELECT COUNT(*) as count FROM cocktail_tags WHERE source = 'expert_added'");
                                $suggestedCount = $suggestedStmt->fetch()['count'] ?? 0;
                                
                                $totalStmt = $pdo->query("SELECT COUNT(*) as count FROM cocktail_tags");
                                $totalTags = $totalStmt->fetch()['count'] ?? 0;
                                
                            } catch (Exception $e) {
                                error_log("Statistics error: " . $e->getMessage());
                                $verifiedCount = 0;
                                $pendingCount = 0;
                                $suggestedCount = 0;
                                $totalTags = 0;
                            }
                            ?>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h4 class="fw-bold text-success"><?php echo $verifiedCount; ?></h4>
                                    <small class="text-muted">Verified Tags</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h4 class="fw-bold text-warning"><?php echo $pendingCount; ?></h4>
                                    <small class="text-muted">Pending Tags</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h4 class="fw-bold text-info"><?php echo $suggestedCount; ?></h4>
                                    <small class="text-muted">Suggested Tags</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h4 class="fw-bold text-primary"><?php echo $totalTags; ?></h4>
                                    <small class="text-muted">Total Tags</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Export Button -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-download me-2"></i>Download Export
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Panel -->
        <div class="col-lg-4 mb-4">
            <!-- Data Included -->
            <div class="glass-card p-4 mb-4">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-info-circle me-2"></i>Data Included
                </h6>
                <ul class="list-unstyled small text-muted">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Cocktail ID & Name</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Category & Glass Type</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>All Tag Names</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Tag Status</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Confidence Scores</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Verified By</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Expertise Level</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Verification Date</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Notes</li>
                </ul>
            </div>

            <div class="glass-card p-4">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-lightbulb me-2"></i>Export Tips
                </h6>
                <ul class="list-unstyled small text-muted">
                    <li class="mb-2">✓ CSV opens in Excel/Sheets</li>
                    <li class="mb-2">✓ JSON for APIs & scripts</li>
                    <li class="mb-2">✓ All exports UTF-8 encoded</li>
                    <li class="mb-2">✓ No duplicates included</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.export-option {
    transition: all 0.3s ease;
}

.export-option:hover {
    background-color: rgba(139, 92, 246, 0.05);
    border-color: var(--primary) !important;
}

.export-option input[type="radio"]:checked ~ label {
    color: var(--primary);
}

.export-option input[type="radio"]:checked + label strong {
    color: var(--primary);
}
</style>

<script>
document.querySelectorAll('.export-option').forEach(option => {
    option.addEventListener('click', function() {
        this.querySelector('input[type="radio"]').checked = true;
    });
});

document.getElementById('exportForm').addEventListener('submit', function(e) {
    const button = this.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Preparing download...';
    button.disabled = true;
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 5000);
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>