<?php
/**
 * Header Template with Sidebar Navigation
 * Includes HTML head, Bootstrap CSS, TinyMCE, sidebar navigation
 */
$page_title = $page_title ?? 'HR Virtual Interview Portal';

// Get current user
$current_user = get_logged_in_user();
$user_id = $current_user['id'] ?? null;
$company_name = $current_user['company_name'] ?? 'Interview Portal';

// Get user-specific logo path
$logo_setting = get_setting('logo_path', $user_id);
if ($logo_setting && file_exists(__DIR__ . '/../' . $logo_setting)) {
    $logo_path = $logo_setting;
    $has_logo = true;
} else {
    $logo_path = 'assets/uploads/logo_user_' . $user_id . '.png';
    $has_logo = file_exists(__DIR__ . '/../' . $logo_path);
}

// Get theme colors from settings or use defaults
$primary_color = get_setting('theme_primary', $user_id) ?? '#0d6efd';
$secondary_color = get_setting('theme_secondary', $user_id) ?? '#6c757d';
$accent_color = get_setting('theme_accent', $user_id) ?? '#0dcaf0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($page_title); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- TinyMCE Rich Text Editor (Free Community Version) -->
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/style.css">
    
    <style>
        :root {
            --primary-color: <?php echo $primary_color; ?>;
            --secondary-color: <?php echo $secondary_color; ?>;
            --accent-color: <?php echo $accent_color; ?>;
        }
        
        /* Apply theme colors to sidebar */
        #sidebar-wrapper {
            background: var(--primary-color) !important;
        }
        
        .list-group-item-action.bg-primary {
            background: var(--primary-color) !important;
        }
        
        .list-group-item-action.bg-primary.active {
            background: var(--secondary-color) !important;
        }
        
        .btn-primary {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color) !important;
            border-color: var(--secondary-color) !important;
        }
        
        .btn-outline-primary {
            color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .bg-primary {
            background: var(--primary-color) !important;
        }
        
        .badge.bg-primary {
            background: var(--primary-color) !important;
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-primary" id="sidebar-wrapper">
            <div class="sidebar-heading text-white p-3 border-bottom text-center">
                <?php if ($has_logo): ?>
                    <img src="<?php echo sanitize($logo_path); ?>" alt="Company Logo" class="img-fluid mb-2" style="max-height: 50px;">
                <?php else: ?>
                    <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> <?php echo sanitize($company_name); ?></h5>
                <?php endif; ?>
                <small class="d-block text-white-50 mt-1"><?php echo sanitize($current_user['email']); ?></small>
            </div>
            <div class="list-group list-group-flush">
                <?php if (is_authenticated()): ?>
                    <a href="index.php?page=dashboard" class="list-group-item list-group-item-action bg-primary text-white border-0 <?php echo (!isset($_GET['page']) || $_GET['page'] == 'dashboard') ? 'active' : ''; ?>">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a href="index.php?page=jobs" class="list-group-item list-group-item-action bg-primary text-white border-0 <?php echo (isset($_GET['page']) && $_GET['page'] == 'jobs') ? 'active' : ''; ?>">
                        <i class="bi bi-briefcase me-2"></i> Jobs
                    </a>
                    <a href="index.php?page=candidates" class="list-group-item list-group-item-action bg-primary text-white border-0 <?php echo (isset($_GET['page']) && $_GET['page'] == 'candidates') ? 'active' : ''; ?>">
                        <i class="bi bi-people me-2"></i> Candidates
                    </a>
                    <a href="index.php?page=settings" class="list-group-item list-group-item-action bg-primary text-white border-0 <?php echo (isset($_GET['page']) && $_GET['page'] == 'settings') ? 'active' : ''; ?>">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                    <?php
                    // Show Super Admin link only for super admins
                    $stmt = $pdo->prepare("SELECT is_super_admin FROM users WHERE id = ?");
                    $stmt->execute([get_current_user_id()]);
                    $user_data = $stmt->fetch();
                    if ($user_data && $user_data['is_super_admin'] == 1):
                    ?>
                    <a href="index.php?page=super_admin" class="list-group-item list-group-item-action bg-danger text-white border-0 <?php echo (isset($_GET['page']) && $_GET['page'] == 'super_admin') ? 'active' : ''; ?>">
                        <i class="bi bi-shield-lock-fill me-2"></i> Super Admin
                    </a>
                    <?php endif; ?>
                    <div class="border-top border-white my-2"></div>
                    <a href="gui/logout.php" class="list-group-item list-group-item-action bg-primary text-white border-0">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Top Navigation Bar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="ms-auto d-flex align-items-center">
                        <span class="me-3">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo sanitize($_SESSION['user_email'] ?? 'Admin'); ?>
                        </span>
                    </div>
                </div>
            </nav>
            
            <main class="container-fluid p-4">
