<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/Auth.php';

requireLogin();

$auth = new Auth();
$expert = $auth->getCurrentExpert();
$pdo = getDB();

$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Get verification stats by date
$stmt = $pdo->prepare("
    SELECT 
        DATE(vl.performed_at) as date,
        COUNT(*) as total_actions,
        COUNT(CASE WHEN vl.action_type = 'verify_tag' THEN 1 END) as tags_verified,
        COUNT(CASE WHEN vl.action_type = 'add_tag' THEN 1 END) as tags_added,
        COUNT(CASE WHEN vl.action_type = 'reject_tag' THEN 1 END) as tags_rejected
    FROM verification_logs vl
    WHERE vl.expert_id = ? AND DATE(vl.performed_at) BETWEEN ? AND ?
    GROUP BY DATE(vl.performed_at)
    ORDER BY date ASC
");
$stmt->execute([$expert['id'], $date_from, $date_to]);
$daily_stats = $stmt->fetchAll();

// Get top verified tags
$top_tags_stmt = $pdo->prepare("
    SELECT 
        t.tag_name,
        tc.name as category,
        COUNT(*) as verification_count,
        AVG(ct.confidence_score) as avg_confidence
    FROM verification_logs vl
    JOIN tags t ON vl.tag_id = t.id
    LEFT JOIN tag_categories tc ON t.category_id = tc.id
    WHERE vl.expert_id = ? AND vl.action_type = 'verify_tag' 
    AND DATE(vl.performed_at) BETWEEN ? AND ?
    GROUP BY vl.tag_id
    ORDER BY verification_count DESC
    LIMIT 10
");
$top_tags_stmt->execute([$expert['id'], $date_from, $date_to]);
$top_tags = $top_tags_stmt->fetchAll();

// Get action breakdown
$breakdown_stmt = $pdo->prepare("
    SELECT 
        action_type,
        COUNT(*) as count
    FROM verification_logs
    WHERE expert_id = ? AND DATE(performed_at) BETWEEN ? AND ?
    GROUP BY action_type
");
$breakdown_stmt->execute([$expert['id'], $date_from, $date_to]);
$action_breakdown = $breakdown_stmt->fetchAll();

$pageTitle = "Analytics";
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <!-- Date Range Filter -->
    <div class="glass-card p-4 mb-4">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">From</label>
                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">To</label>
                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
            </div>
        </form>
    </div>
    
    <div class="row">
        <!-- Summary Stats -->
        <div class="col-md-3 mb-4">
            <div class="glass-card p-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-check-circle fa-2x" style="color: var(--primary);"></i>
                </div>
                <h4 class="fw-bold">
                    <?php echo array_reduce($daily_stats, function($carry, $item) {
                        return $carry + $item['tags_verified'];
                    }, 0); ?>
                </h4>
                <small class="text-muted">Tags Verified</small>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="glass-card p-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-plus-circle fa-2x" style="color: var(--secondary);"></i>
                </div>
                <h4 class="fw-bold">
                    <?php echo array_reduce($daily_stats, function($carry, $item) {
                        return $carry + $item['tags_added'];
                    }, 0); ?>
                </h4>
                <small class="text-muted">Tags Added</small>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="glass-card p-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-times-circle fa-2x" style="color: var(--danger);"></i>
                </div>
                <h4 class="fw-bold">
                    <?php echo array_reduce($daily_stats, function($carry, $item) {
                        return $carry + $item['tags_rejected'];
                    }, 0); ?>
                </h4>
                <small class="text-muted">Tags Rejected</small>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="glass-card p-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-chart-line fa-2x" style="color: var(--accent);"></i>
                </div>
                <h4 class="fw-bold">
                    <?php echo count($daily_stats); ?>
                </h4>
                <small class="text-muted">Active Days</small>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Activity Chart -->
        <div class="col-lg-8 mb-4">
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-chart-line me-2"></i>Daily Activity
                </h5>
                
                <canvas id="activityChart"></canvas>
            </div>
        </div>
        
        <!-- Action Breakdown -->
        <div class="col-lg-4 mb-4">
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-pie-chart me-2"></i>Actions Breakdown
                </h5>
                
                <canvas id="breakdownChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top Verified Tags -->
    <div class="glass-card p-4">
        <h5 class="fw-bold mb-4">
            <i class="fas fa-fire me-2"></i>Most Verified Tags
        </h5>
        
        <div class="table-responsive">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>Tag Name</th>
                        <th>Category</th>
                        <th>Verifications</th>
                        <th>Avg Confidence</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_tags as $tag): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($tag['tag_name']); ?></code></td>
                        <td><?php echo htmlspecialchars($tag['category'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge bg-primary"><?php echo $tag['verification_count']; ?></span>
                        </td>
                        <td>
                            <span class="badge bg-success"><?php echo round($tag['avg_confidence'], 2); ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Activity Chart Data
const activityData = {
    labels: [<?php echo implode(',', array_map(function($stat) { 
        return "'" . date('M d', strtotime($stat['date'])) . "'"; 
    }, $daily_stats)); ?>],
    datasets: [
        {
            label: 'Tags Verified',
            data: [<?php echo implode(',', array_map(function($stat) { 
                return $stat['tags_verified']; 
            }, $daily_stats)); ?>],
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4
        },
        {
            label: 'Tags Added',
            data: [<?php echo implode(',', array_map(function($stat) { 
                return $stat['tags_added']; 
            }, $daily_stats)); ?>],
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4
        }
    ]
};

const activityCtx = document.getElementById('activityChart').getContext('2d');
new Chart(activityCtx, {
    type: 'line',
    data: activityData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Breakdown Chart
const breakdownData = {
    labels: [<?php echo implode(',', array_map(function($item) { 
        return "'" . ucfirst(str_replace('_', ' ', $item['action_type'])) . "'"; 
    }, $action_breakdown)); ?>],
    datasets: [{
        data: [<?php echo implode(',', array_map(function($item) { 
            return $item['count']; 
        }, $action_breakdown)); ?>],
        backgroundColor: [
            '#10B981',
            '#3B82F6',
            '#EF4444',
            '#F59E0B',
            '#8B5CF6'
        ]
    }]
};

const breakdownCtx = document.getElementById('breakdownChart').getContext('2d');
new Chart(breakdownCtx, {
    type: 'doughnut',
    data: breakdownData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'bottom'
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
