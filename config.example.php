<?php
/**
 * Secure Configuration File
 * 
 * IMPORTANT: 
 * 1. Copy this file to config.php (git ignored)
 * 2. Fill in your actual credentials
 * 3. Never commit config.php to version control
 * 4. Keep this file outside web root in production
 */

return [
    // Application Secret Key (used for encryption)
    // Generate with: php -r "echo bin2hex(random_bytes(32));"
    'app_secret_key' => 'CHANGE_THIS_TO_RANDOM_64_CHAR_HEX_STRING',
    
    // Force HTTPS (set to true in production)
    'force_https' => false,
    
    // Google OAuth Credentials
    'google_client_id' => 'YOUR_GOOGLE_CLIENT_ID',
    'google_client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',
    
    // Microsoft OAuth Credentials
    'microsoft_client_id' => 'YOUR_MICROSOFT_CLIENT_ID',
    'microsoft_client_secret' => 'YOUR_MICROSOFT_CLIENT_SECRET',
    
    // Security Settings
    'session_timeout' => 7200, // 2 hours in seconds
    'session_regenerate_interval' => 1800, // 30 minutes
    'max_login_attempts' => 5,
    'login_lockout_duration' => 900, // 15 minutes in seconds
    
    // Password Requirements
    'password_min_length' => 12,
    'password_require_uppercase' => true,
    'password_require_lowercase' => true,
    'password_require_number' => true,
    'password_require_special' => true,
    
    // File Upload Settings
    'max_file_size' => 5242880, // 5MB in bytes
    'allowed_file_types' => ['application/pdf'],
    'upload_dir' => __DIR__ . '/../assets/uploads',
    
    // Rate Limiting
    'rate_limit_enabled' => true,
    'rate_limit_max_attempts' => 5,
    'rate_limit_time_window' => 900, // 15 minutes
    
    // Logging
    'enable_security_logging' => true,
    'log_failed_logins' => true,
    'log_api_calls' => false,
    
    // Development Mode (NEVER enable in production)
    'debug_mode' => false,
    'display_errors' => false,
];
