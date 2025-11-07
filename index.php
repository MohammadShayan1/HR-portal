<?php
/**
 * Main Router / Controller
 * Entry point - redirects to home page or admin panel
 */

session_start();
require_once __DIR__ . '/functions/db.php';
require_once __DIR__ . '/functions/auth.php';

// Check if user is trying to access admin panel
// If authenticated, show admin panel
// If not authenticated, redirect to home page
if (is_authenticated()) {
    require_once __DIR__ . '/functions/core.php';
    
    // Get requested page
    $page = $_GET['page'] ?? 'dashboard';
    
    // Whitelist of allowed pages
    $allowed_pages = ['dashboard', 'jobs', 'candidates', 'report', 'settings', 'super_admin'];
    
    // Validate page
    if (!in_array($page, $allowed_pages)) {
        $page = 'dashboard';
    }
    
    // Build page file path
    $page_file = __DIR__ . '/gui/' . $page . '.php';
    
    // Check if page file exists
    if (!file_exists($page_file)) {
        $page = 'dashboard';
        $page_file = __DIR__ . '/gui/dashboard.php';
    }
    
    // Include header
    include __DIR__ . '/gui/header.php';
    
    // Include requested page
    include $page_file;
    
    // Include footer
    include __DIR__ . '/gui/footer.php';
} else {
    // Not authenticated - redirect to home page
    header('Location: home.php');
    exit;
}
