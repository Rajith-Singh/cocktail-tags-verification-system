<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/CocktailManager.php';

// Get expert levels
$expertLevels = getExpertLevels();

$currentPage = basename($_SERVER['PHP_SELF']);
$expert = isLoggedIn() ? (new Auth())->getCurrentExpert() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/style.css">
    
    <style>
        :root {
            --primary: #8B5CF6;
            --primary-dark: #7C3AED;
            --secondary: #10B981;
            --accent: #F59E0B;
            --danger: #EF4444;
            --light: #F9FAFB;
            --dark: #1F2937;
            --card-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
        }
        
        /* Sidebar positioning */
        .sidebar {
            position: fixed;
            left: 0;
            top: 56px;
            width: var(--sidebar-width);
            height: calc(100vh - 56px);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(0, 0, 0, 0.1);
            padding: 0;
            z-index: 100;
            overflow-y: auto;
            box-shadow: var(--card-shadow);
        }
        
        /* Main content offset when sidebar is visible */
        .main-content {
            margin-left: var(--sidebar-width);
            padding-top: 0;
            min-height: calc(100vh - 56px);
        }
        
        /* Full width when no sidebar */
        .main-content.full-width {
            margin-left: 0;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3);
        }
        
        .tag-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin: 2px;
            transition: var(--transition);
        }
        
        .tag-badge:hover {
            transform: scale(1.05);
        }
        
        .cocktail-card {
            border-radius: 15px;
            overflow: hidden;
            transition: var(--transition);
            border: none;
        }
        
        .cocktail-card:hover {
            transform: translateY(-5px);
        }
        
        .cocktail-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
        .verification-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .expert-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 5px;
        }
        
        .nav-link {
            color: var(--dark);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 10px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            border-left: 4px solid transparent;
        }
        
        .nav-link:hover {
            background: rgba(139, 92, 246, 0.1);
            border-left-color: var(--primary);
            color: var(--primary);
        }
        
        .nav-link.active {
            background: var(--primary);
            color: white;
            border-left-color: var(--primary-dark);
        }
        
        .nav-link i {
            width: 25px;
            margin-right: 10px;
        }
        
        .progress-ring {
            position: relative;
            width: 80px;
            height: 80px;
        }
        
        .progress-ring__circle {
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
        
        .ingredient-chip {
            background: var(--light);
            border-radius: 20px;
            padding: 8px 15px;
            margin: 5px;
            display: inline-flex;
            align-items: center;
            border: 1px solid #E5E7EB;
        }
        
        .floating-action-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.4);
            z-index: 1000;
            transition: var(--transition);
            text-decoration: none;
        }
        
        .floating-action-btn:hover {
            transform: scale(1.1);
            color: white;
            text-decoration: none;
        }
        
        /* Footer adjustment for sidebar */
        @media (max-width: 768px) {
            footer {
                margin-left: 0 !important;
            }
        }
        
        /* Ensure footer stays full width */
        footer {
            transition: margin-left var(--transition);
        }
        
        /* Modal styling */
        .modal-content {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
        }
        
        .modal-header {
            background: rgba(139, 92, 246, 0.05);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.show {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px); z-index: 1000;">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>dashboard.php">
                <i class="fas fa-cocktail me-2"></i>
                <span style="font-family: 'Playfair Display', serif; font-weight: 600;"><?php echo APP_NAME; ?></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isLoggedIn()): ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="me-2">
                                <div class="expert-badge" style="background: <?php echo $expertLevels[$expert['expertise_level'] ?? 'bartender']['color']; ?>20; color: <?php echo $expertLevels[$expert['expertise_level'] ?? 'bartender']['color']; ?>;">
                                    <?php echo $expertLevels[$expert['expertise_level'] ?? 'bartender']['icon']; ?>
                                    <?php echo $expertLevels[$expert['expertise_level'] ?? 'bartender']['name']; ?>
                                </div>
                            </div>
                            <strong><?php echo h($expert['full_name'] ?? 'User'); ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>dashboard.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
                <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>register.php">Register</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light" href="<?php echo SITE_URL; ?>login.php">Login</a>
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Layout wrapper with sidebar and content -->
    <?php if (isLoggedIn() && $currentPage !== 'index.php' && $currentPage !== 'login.php' && $currentPage !== 'register.php'): ?>
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column px-2">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'cocktails.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>cocktails.php">
                            <i class="fas fa-glass-martini"></i> All Cocktails
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'verify.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>verify.php">
                            <i class="fas fa-check-circle"></i> Verify Tags
                            <?php 
                            try {
                                $pdo = getDB();
                                $stmt = $pdo->query("
                                    SELECT COUNT(*) as pending_count 
                                    FROM cocktail_tags 
                                    WHERE status = 'pending'
                                ");
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $pendingCount = $result['pending_count'] ?? 0;
                                
                                if ($pendingCount > 0): ?>
                                <span class="badge bg-danger ms-auto"><?php echo $pendingCount; ?></span>
                                <?php endif;
                            } catch (Exception $e) {
                                error_log("Error getting pending tags count: " . $e->getMessage());
                            }
                            ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'tags.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>tags.php">
                            <i class="fas fa-tags"></i> Tag Library
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'export.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>export.php">
                            <i class="fas fa-download"></i> Export Data
                        </a>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                    <li class="nav-divider my-3 mx-3" style="border-top: 1px solid #E5E7EB;"></li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>admin/" style="color: var(--primary);">
                            <i class="fas fa-shield-alt"></i> Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-divider my-3 mx-3" style="border-top: 1px solid #E5E7EB;"></li>
                    <li class="nav-item px-3 py-2">
                        <small class="text-muted fw-bold">Verification Stats</small>
                        <div class="mt-2">
                            <?php
                            try {
                                $cocktailManager = new CocktailManager();
                                $stats = $cocktailManager->getCocktailStats();
                            } catch (Exception $e) {
                                error_log("Error getting stats: " . $e->getMessage());
                                $stats = ['total' => 0, 'fully_verified' => 0];
                            }
                            ?>
                            <div class="d-flex justify-content-between mb-2">
                                <small>Completed</small>
                                <small><?php echo $stats['fully_verified'] ?? 0; ?>/<?php echo $stats['total'] ?? 0; ?></small>
                            </div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-success" style="width: <?php echo ($stats['total'] > 0) ? (($stats['fully_verified'] / $stats['total']) * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content p-4" style="margin-top: 56px;">
    <?php else: ?>
        <!-- Full width for non-logged in pages -->
        <div class="main-content full-width p-4" style="margin-top: 56px;">
    <?php endif; ?>