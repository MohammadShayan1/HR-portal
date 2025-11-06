<?php
/**
 * Authentication Functions
 * Handles user registration, login, logout, and session management
 */

require_once __DIR__ . '/db.php';

/**
 * Register a new user
 * @param string $email
 * @param string $password
 * @param string $company_name
 * @return bool|string True on success, error message on failure
 */
function register_user($email, $password, $company_name = '') {
    // Validate inputs
    if (empty($email) || empty($password)) {
        return "Email and password are required.";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format.";
    }
    
    if (strlen($password) < 6) {
        return "Password must be at least 6 characters.";
    }
    
    // Allow multiple registrations - multi-tenant system
    $pdo = get_db();
    
    try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password, company_name, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $hashed_password, $company_name, date('Y-m-d H:i:s')]);
        return true;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
            return "Email already exists.";
        }
        return "Registration failed: " . $e->getMessage();
    }
}

/**
 * Login a user
 * @param string $email
 * @param string $password
 * @return bool|string True on success, error message on failure
 */
function login_user($email, $password) {
    if (empty($email) || empty($password)) {
        return "Email and password are required.";
    }
    
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return "Invalid email or password.";
    }
    
    if (!password_verify($password, $user['password'])) {
        return "Invalid email or password.";
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    
    return true;
}

/**
 * Logout the current user
 */
function logout_user() {
    session_unset();
    session_destroy();
}

/**
 * Check if user is authenticated
 * @return bool
 */
function is_authenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Check authentication and redirect if not logged in
 * @param string $redirect_to URL to redirect to if not authenticated
 */
function check_auth($redirect_to = 'gui/login.php') {
    if (!is_authenticated()) {
        header("Location: " . $redirect_to);
        exit;
    }
}

/**
 * Check if any admin user exists
 * @return bool
 */
function admin_exists() {
    $pdo = get_db();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    return $result['count'] > 0;
}

/**
 * Get current user ID
 * @return int|null
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 * @return array|null
 */
function get_logged_in_user() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return null;
    }
    
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT id, email, company_name, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}
