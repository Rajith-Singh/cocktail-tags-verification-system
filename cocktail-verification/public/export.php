<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/ExportManager.php';

requireLogin();

$auth = new Auth();
$exportManager = new ExportManager();
$expert = $auth->getCurrentExpert();

$message = '';
$messageType = '';

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validateCSRF($csrf_token)) {
        $message = "Invalid request. Please try again.";
        $messageType = "danger";
    } else {
        $format = $_POST['format'] ?? 'csv';
        $includeUnverified = isset($_POST['include_unverified']);
        $includeExperts = isset($_POST['include_experts']);
        
        try {
            $filename = $exportManager->generateExport($format, $includeUnverified, $includeExperts);
            
            if ($filename) {
                // Offer download
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                header('Content-Length: ' . filesize($filename));
                readfile($filename);
                exit();
            } else {
                throw new Exception("Failed to generate export file");
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

$pageTitle = "Export Verified Data";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="glass-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold" style="color: var(--primary);">
                        <i class="fas fa-download me-2"></i>Export Verified Dataset
                    </h2>
                    <p class="text-muted">Download the complete verified cocktail dataset for your research</p>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Export Options -->
                    <div class="col-md-6">
                        <div class="glass-card p-4 h-100">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-cog me-2"></i>Export Options
                            </h5>
                            
                            <form method="POST" action="" id="exportForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Export Format</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-check card-option">
                                                <input class="form-check-input" type="radio" name="format" 
                                                       id="formatCsv" value="csv" checked>
                                                <label class="form-check-label w-100" for="formatCsv">
                                                    <div class="text-center p-3 border rounded">
                                                        <i class="fas fa-file-csv fa-3x mb-2 text-success"></i>
                                                        <h6>CSV</h6>
                                                        <small class="text-muted">Excel compatible</small>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-check card-option">
                                                <input class="form-check-input" type="radio" name="format" 
                                                       id="formatJson" value="json">
                                                <label class="form-check-label w-100" for="formatJson">
                                                    <div class="text-center p-3 border rounded">
                                                        <i class="fas fa-code fa-3x mb-2 text-primary"></i>
                                                        <h6>JSON</h6>
                                                        <small class="text-muted">API ready</small>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Data Options</label>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="include_unverified" 
                                               id="includeUnverified">
                                        <label class="form-check-label" for="includeUnverified">
                                            Include unverified cocktails and tags
                                        </label>
                                        <small class="form-text text-muted d-block">
                                            Includes pending and rejected tags for completeness
                                        </small>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="include_experts" 
                                               id="includeExperts" checked>
                                        <label class="form-check-label" for="includeExperts">
                                            Include expert verification information
                                        </label>
                                        <small class="form-text text-muted d-block">
                                            Shows which expert verified each tag
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-download me-2"></i>Generate Export
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Export Preview -->
                    <div class="col-md-6">
                        <div class="glass-card p-4 h-100">
                            <h5 class="fw-bold mb-3">
                                <i class="fas fa-eye me-2"></i>Export Preview
                            </h5>
                            
                            <div class="mb-4">
                                <h6>What's included:</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item px-0">
                                        <i class="fas fa-check text-success me-2"></i>
                                        All cocktail details (50+ fields)
                                    </li>
                                    <li class="list-group-item px-0">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Verified tags with categories
                                    </li>
                                    <li class="list-group-item px-0">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Expert verification timestamps
                                    </li>
                                    <li class="list-group-item px-0">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Confidence scores for tags
                                    </li>
                                    <li class="list-group-item px-0">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Original tags from CSV import
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="mb-4">
                                <h6>Sample CSV Structure:</h6>
                                <div class="bg-light p-3 rounded" style="font-family: monospace; font-size: 12px;">
                                    idDrink,strDrink,strCategory,...,verified_tags,verification_status<br>
                                    11000,Mojito,Cocktail,...,"Mint,Refreshing,Rum",fully_verified<br>
                                    11001,Martini,Cocktail,...,"Gin,Dry,Classic",partially_verified
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Data Privacy:</strong> Expert emails are never exported. Only usernames and expertise levels are included.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-option .form-check-input {
    position: absolute;
    opacity: 0;
}

.card-option .form-check-label {
    cursor: pointer;
    transition: var(--transition);
}

.card-option .form-check-input:checked + .form-check-label > div {
    border-color: var(--primary);
    background: rgba(139, 92, 246, 0.05);
    box-shadow: 0 5px 15px rgba(139, 92, 246, 0.1);
}

.card-option .form-check-label:hover > div {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}
</style>

<script>
document.getElementById('exportForm').addEventListener('submit', function(e) {
    const button = this.querySelector('button[type="submit"]');
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating Export...';
    
    // Show loading overlay
    const overlay = document.createElement('div');
    overlay.className = 'export-loading-overlay';
    overlay.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h4 class="mt-3">Preparing your export...</h4>
            <p class="text-muted">This may take a moment for large datasets</p>
        </div>
    `;
    
    document.body.appendChild(overlay);
});

// Remove overlay when page unloads (in case of download)
window.addEventListener('beforeunload', function() {
    const overlay = document.querySelector('.export-loading-overlay');
    if (overlay) overlay.remove();
});
</script>

<style>
.export-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(5px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}
</style>

<?php include __DIR__ . '/../templates/footer.php'; ?>