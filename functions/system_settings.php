<?php
/**
 * System Settings Helper
 * Functions to get centralized system-wide settings
 */

/**
 * Get system-wide setting
 */
function get_system_setting($key, $default = '') {
    static $settings_cache = [];
    
    if (isset($settings_cache[$key])) {
        return $settings_cache[$key];
    }
    
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    
    $value = $result ? $result['setting_value'] : $default;
    $settings_cache[$key] = $value;
    
    return $value;
}

/**
 * Get Google OAuth config (from system settings or config.php fallback)
 */
function get_google_oauth_config() {
    $client_id = get_system_setting('google_client_id');
    $client_secret = get_system_setting('google_client_secret');
    $redirect_uri = get_system_setting('google_redirect_uri');
    
    // Fallback to config.php if system settings not configured
    if (empty($client_id) && defined('GOOGLE_CLIENT_ID')) {
        $client_id = GOOGLE_CLIENT_ID;
        $client_secret = GOOGLE_CLIENT_SECRET;
        $redirect_uri = GOOGLE_REDIRECT_URI;
    }
    
    return [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri
    ];
}

/**
 * Get Outlook OAuth config (from system settings or config.php fallback)
 */
function get_outlook_oauth_config() {
    $client_id = get_system_setting('outlook_client_id');
    $client_secret = get_system_setting('outlook_client_secret');
    $redirect_uri = get_system_setting('outlook_redirect_uri');
    
    // Fallback to config.php if system settings not configured
    if (empty($client_id) && defined('OUTLOOK_CLIENT_ID')) {
        $client_id = OUTLOOK_CLIENT_ID;
        $client_secret = OUTLOOK_CLIENT_SECRET;
        $redirect_uri = OUTLOOK_REDIRECT_URI;
    }
    
    return [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri
    ];
}

/**
 * Get Zoom OAuth config (from system settings or user settings fallback)
 */
function get_zoom_oauth_config($user_id) {
    $account_id = get_system_setting('zoom_account_id');
    $client_id = get_system_setting('zoom_client_id');
    $client_secret = get_system_setting('zoom_client_secret');
    
    // Fallback to user-specific settings if system settings not configured
    if (empty($account_id)) {
        $account_id = get_setting('zoom_account_id', $user_id);
        $client_id = get_setting('zoom_client_id', $user_id);
        $client_secret = get_setting('zoom_client_secret', $user_id);
    }
    
    return [
        'account_id' => $account_id,
        'client_id' => $client_id,
        'client_secret' => $client_secret
    ];
}
