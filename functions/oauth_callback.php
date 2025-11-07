<?php
/**
 * OAuth Callback Handler for Google Calendar and Microsoft Outlook
 * 
 * Security Features:
 * - State parameter verification (CSRF protection)
 * - Secure token storage with encryption
 * - Session-based user tracking
 * - HTTPS enforcement
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/system_settings.php';

// Load config (for fallback)
if (!file_exists(__DIR__ . '/../config.php')) {
    die('Error: config.php not found. Please copy config.example.php to config.php');
}
$config = require(__DIR__ . '/../config.php');

// Start session BEFORE loading security.php (which tries to configure session)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Now load security functions
require_once __DIR__ . '/security.php';

// Force HTTPS for OAuth
if (($config['force_https'] ?? false)) {
    if (function_exists('force_https')) {
        force_https();
    }
}

// Add security headers
if (function_exists('add_security_headers')) {
    add_security_headers();
}

$provider = $_GET['provider'] ?? '';
$action = $_GET['action'] ?? '';
$code = $_GET['code'] ?? '';

// Skip origin verification for OAuth callbacks (they come from external providers)
// Only verify origin for initial connect requests
if ($action === 'connect' && function_exists('verify_origin') && !verify_origin()) {
    if (function_exists('log_security_event')) {
        log_security_event('oauth_invalid_origin', ['provider' => $provider]);
    }
    die('Invalid request origin');
}

// Redirect URI (must match the one registered in Google/Microsoft)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
$redirect_uri = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/functions/oauth_callback.php';

/**
 * Google Calendar OAuth
 */
if ($provider === 'google') {
    if ($action === 'connect') {
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            die('Please log in first');
        }
        
        // Step 1: Redirect to Google OAuth consent screen
        $google_config = get_google_oauth_config();
        $client_id = $google_config['client_id'];
        
        if (empty($client_id) || $client_id === 'YOUR_GOOGLE_CLIENT_ID') {
            die('Google OAuth not configured. Please ask your administrator to configure it in the Super Admin panel.');
        }
        
        $scope = 'https://www.googleapis.com/auth/calendar';
        
        $_SESSION['oauth_state'] = generate_secure_token(16);
        $_SESSION['oauth_user_id'] = $user_id;
        $_SESSION['oauth_timestamp'] = time();
        
        if (function_exists('log_security_event')) {
            log_security_event('oauth_initiated', ['provider' => 'google'], $user_id);
        }
        
        $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $client_id,
            'redirect_uri' => $google_config['redirect_uri'],
            'response_type' => 'code',
            'scope' => $scope,
            'access_type' => 'offline',
            'state' => $_SESSION['oauth_state']
        ]);
        
        header('Location: ' . $auth_url);
        exit;
    } 
    elseif (!empty($code)) {
        // Step 2: Exchange authorization code for access token
        $state = $_GET['state'] ?? '';
        
        // Verify state parameter (CSRF protection)
        if ($state !== ($_SESSION['oauth_state'] ?? '')) {
            if (function_exists('log_security_event')) {
                log_security_event('oauth_invalid_state', ['provider' => 'google']);
            }
            die('Invalid state parameter. Possible CSRF attack.');
        }
        
        // Verify timestamp (prevent replay attacks)
        if (!isset($_SESSION['oauth_timestamp']) || time() - $_SESSION['oauth_timestamp'] > 600) {
            die('OAuth request expired. Please try again.');
        }
        
        $google_config = get_google_oauth_config();
        $client_id = $google_config['client_id'];
        $client_secret = $google_config['client_secret'];
        
        if (empty($client_secret)) {
            die('Google OAuth not configured properly.');
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'code' => $code,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $google_config['redirect_uri'],
            'grant_type' => 'authorization_code'
        ]));
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (isset($result['access_token'])) {
            $user_id = $_SESSION['oauth_user_id'];
            
            // Encrypt tokens before storing (SECURITY ENHANCEMENT)
            $encrypted_token = encrypt_data($result['access_token']);
            set_setting('google_calendar_token', $encrypted_token, $user_id);
            
            if (isset($result['refresh_token'])) {
                $encrypted_refresh = encrypt_data($result['refresh_token']);
                set_setting('google_calendar_refresh_token', $encrypted_refresh, $user_id);
            }
            
            if (function_exists('log_security_event')) {
                log_security_event('oauth_success', ['provider' => 'google'], $user_id);
            }
            
            // Clear OAuth session data
            unset($_SESSION['oauth_state']);
            unset($_SESSION['oauth_user_id']);
            unset($_SESSION['oauth_timestamp']);
            
            header('Location: ../index.php?page=settings&google_connected=1');
            exit;
        } else {
            if (function_exists('log_security_event')) {
                log_security_event('oauth_failed', [
                    'provider' => 'google',
                    'error' => $result['error'] ?? 'unknown'
                ], $_SESSION['oauth_user_id'] ?? null);
            }
            die('Failed to get access token: ' . ($result['error_description'] ?? 'Unknown error'));
        }
    }
}

/**
 * Microsoft Outlook OAuth
 */
elseif ($provider === 'outlook') {
    if ($action === 'connect') {
        // Step 1: Redirect to Microsoft OAuth consent screen
        $outlook_config = get_outlook_oauth_config();
        $client_id = $outlook_config['client_id'];
        
        if (empty($client_id)) {
            die('Outlook OAuth not configured. Please ask your administrator to configure it in the Super Admin panel.');
        }
        
        $scope = 'Calendars.ReadWrite offline_access';
        
        $_SESSION['oauth_state'] = bin2hex(random_bytes(16));
        $_SESSION['oauth_user_id'] = get_current_user_id();
        
        $auth_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . http_build_query([
            'client_id' => $client_id,
            'response_type' => 'code',
            'redirect_uri' => $outlook_config['redirect_uri'],
            'response_mode' => 'query',
            'scope' => $scope,
            'state' => $_SESSION['oauth_state']
        ]);
        
        header('Location: ' . $auth_url);
        exit;
    }
    elseif (!empty($code)) {
        // Step 2: Exchange authorization code for access token
        $state = $_GET['state'] ?? '';
        
        if ($state !== $_SESSION['oauth_state']) {
            die('Invalid state parameter');
        }
        
        $outlook_config = get_outlook_oauth_config();
        $client_id = $outlook_config['client_id'];
        $client_secret = $outlook_config['client_secret'];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://login.microsoftonline.com/common/oauth2/v2.0/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $client_id,
            'scope' => 'Calendars.ReadWrite offline_access',
            'code' => $code,
            'redirect_uri' => $outlook_config['redirect_uri'],
            'grant_type' => 'authorization_code',
            'client_secret' => $client_secret
        ]));
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (isset($result['access_token'])) {
            $user_id = $_SESSION['oauth_user_id'];
            set_setting('outlook_calendar_token', $result['access_token'], $user_id);
            
            if (isset($result['refresh_token'])) {
                set_setting('outlook_calendar_refresh_token', $result['refresh_token'], $user_id);
            }
            
            header('Location: ../index.php?page=settings&outlook_connected=1');
            exit;
        } else {
            die('Failed to get access token: ' . ($result['error_description'] ?? 'Unknown error'));
        }
    }
}

die('Invalid OAuth request');
