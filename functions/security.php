<?php
/**
 * Security Configuration and Helper Functions
 * 
 * This file contains security-related configurations and helper functions
 * to protect against common vulnerabilities.
 */

/**
 * Initialize secure session settings
 */
function init_secure_session() {
    // Set secure session parameters before starting session
    ini_set('session.cookie_httponly', 1);  // Prevent JavaScript access to session cookie
    ini_set('session.use_only_cookies', 1); // Only use cookies for session ID
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Require HTTPS for cookies (if available)
    ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
    ini_set('session.use_strict_mode', 1); // Prevent session fixation
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
    
    // Session timeout (2 hours of inactivity)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token input field HTML
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Encrypt sensitive data before storing
 */
function encrypt_data($data, $key = null) {
    if (empty($data)) {
        return '';
    }
    
    // Use application secret key or generate one
    $key = $key ?? getenv('APP_SECRET_KEY') ?? 'CHANGE_THIS_SECRET_KEY_IN_PRODUCTION';
    
    // Generate initialization vector
    $iv_length = openssl_cipher_iv_length('AES-256-CBC');
    $iv = openssl_random_pseudo_bytes($iv_length);
    
    // Encrypt the data
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', hash('sha256', $key), 0, $iv);
    
    // Combine IV and encrypted data
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt sensitive data
 */
function decrypt_data($encrypted_data, $key = null) {
    if (empty($encrypted_data)) {
        return '';
    }
    
    $key = $key ?? getenv('APP_SECRET_KEY') ?? 'CHANGE_THIS_SECRET_KEY_IN_PRODUCTION';
    
    // Decode the data
    $data = base64_decode($encrypted_data);
    
    // Extract IV
    $iv_length = openssl_cipher_iv_length('AES-256-CBC');
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    
    // Decrypt
    return openssl_decrypt($encrypted, 'AES-256-CBC', hash('sha256', $key), 0, $iv);
}

/**
 * Rate limiting for login attempts
 */
function check_rate_limit($identifier, $max_attempts = 5, $time_window = 900) {
    $pdo = get_db();
    
    // Create rate_limits table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        identifier TEXT NOT NULL,
        attempts INTEGER DEFAULT 0,
        last_attempt INTEGER NOT NULL,
        blocked_until INTEGER DEFAULT 0
    )");
    
    // Check if identifier is blocked
    $stmt = $pdo->prepare("SELECT * FROM rate_limits WHERE identifier = ?");
    $stmt->execute([$identifier]);
    $record = $stmt->fetch();
    
    if ($record) {
        // Check if still blocked
        if ($record['blocked_until'] > time()) {
            return [
                'allowed' => false,
                'reason' => 'Too many attempts. Try again in ' . ceil(($record['blocked_until'] - time()) / 60) . ' minutes.'
            ];
        }
        
        // Reset if time window passed
        if (time() - $record['last_attempt'] > $time_window) {
            $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = 1, last_attempt = ? WHERE identifier = ?");
            $stmt->execute([time(), $identifier]);
            return ['allowed' => true];
        }
        
        // Increment attempts
        $new_attempts = $record['attempts'] + 1;
        
        if ($new_attempts >= $max_attempts) {
            // Block for 15 minutes
            $blocked_until = time() + 900;
            $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = ?, last_attempt = ?, blocked_until = ? WHERE identifier = ?");
            $stmt->execute([$new_attempts, time(), $blocked_until, $identifier]);
            return [
                'allowed' => false,
                'reason' => 'Too many failed attempts. Account temporarily locked for 15 minutes.'
            ];
        }
        
        $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = ?, last_attempt = ? WHERE identifier = ?");
        $stmt->execute([$new_attempts, time(), $identifier]);
        return ['allowed' => true, 'attempts' => $new_attempts];
    } else {
        // First attempt
        $stmt = $pdo->prepare("INSERT INTO rate_limits (identifier, attempts, last_attempt) VALUES (?, 1, ?)");
        $stmt->execute([$identifier, time()]);
        return ['allowed' => true, 'attempts' => 1];
    }
}

/**
 * Reset rate limit on successful login
 */
function reset_rate_limit($identifier) {
    $pdo = get_db();
    $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE identifier = ?");
    $stmt->execute([$identifier]);
}

/**
 * Add security headers
 */
function add_security_headers() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Prevent MIME sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://generativelanguage.googleapis.com;");
    
    // HSTS (HTTP Strict Transport Security) - only if HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    // Permissions Policy (formerly Feature Policy)
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

/**
 * Force HTTPS redirect
 */
function force_https() {
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        if (php_sapi_name() !== 'cli') { // Don't redirect in CLI mode
            $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirect_url, true, 301);
            exit;
        }
    }
}

/**
 * Validate password strength
 */
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 12) {
        $errors[] = 'Password must be at least 12 characters long';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Sanitize filename for upload
 */
function sanitize_filename($filename) {
    // Remove any path information
    $filename = basename($filename);
    
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Prevent double extensions
    $filename = preg_replace('/\.{2,}/', '.', $filename);
    
    return $filename;
}

/**
 * Generate secure random token
 */
function generate_secure_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Log security events
 */
function log_security_event($event_type, $details = [], $user_id = null) {
    $pdo = get_db();
    
    // Create security_logs table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS security_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_type TEXT NOT NULL,
        user_id INTEGER,
        ip_address TEXT,
        user_agent TEXT,
        details TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    
    $stmt = $pdo->prepare("
        INSERT INTO security_logs (event_type, user_id, ip_address, user_agent, details)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $event_type,
        $user_id,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        json_encode($details)
    ]);
}

/**
 * Check if request is from trusted domain (CSRF protection)
 */
function verify_origin() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    
    if (empty($origin) && empty($referer)) {
        return true; // Allow same-origin requests without origin header
    }
    
    $trusted_domains = [$host, 'https://' . $host, 'http://' . $host];
    
    foreach ($trusted_domains as $domain) {
        if (strpos($origin, $domain) === 0 || strpos($referer, $domain) === 0) {
            return true;
        }
    }
    
    return false;
}
