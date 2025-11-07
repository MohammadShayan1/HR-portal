<?php
/**
 * Test Google Calendar Sync Status
 * Check if Google Calendar integration is properly configured
 */

session_start();
require_once __DIR__ . '/functions/db.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/system_settings.php';
require_once __DIR__ . '/functions/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$user_id = $_SESSION['user_id'];

echo "<h1>Google Calendar Sync Status</h1>";
echo "<hr>";

// 1. Check if sync is enabled
$google_sync_enabled = get_setting('google_calendar_sync', $user_id);
echo "<h3>1. Google Calendar Sync Setting</h3>";
echo "<p><strong>Status:</strong> " . ($google_sync_enabled === '1' ? '✅ ENABLED' : '❌ DISABLED') . "</p>";
if ($google_sync_enabled !== '1') {
    echo "<p style='color: red;'>Go to Settings and enable Google Calendar Sync</p>";
}

// 2. Check if token exists
$encrypted_token = get_setting('google_calendar_token', $user_id);
echo "<h3>2. Google Access Token</h3>";
echo "<p><strong>Token Exists:</strong> " . (!empty($encrypted_token) ? '✅ YES' : '❌ NO') . "</p>";
if (empty($encrypted_token)) {
    echo "<p style='color: red;'>No access token found. Please connect your Google Calendar in Settings.</p>";
} else {
    // Try to decrypt
    $token = decrypt_data($encrypted_token);
    if ($token) {
        echo "<p><strong>Token Valid:</strong> ✅ YES (Encrypted properly)</p>";
        echo "<p><strong>Token Preview:</strong> " . substr($token, 0, 20) . "...</p>";
    } else {
        echo "<p style='color: red;'><strong>Token Valid:</strong> ❌ NO (Decryption failed)</p>";
    }
}

// 3. Check token expiry
$token_expiry = get_setting('google_calendar_token_expiry', $user_id);
echo "<h3>3. Token Expiry</h3>";
if ($token_expiry) {
    $is_expired = time() > $token_expiry;
    echo "<p><strong>Expiry Time:</strong> " . date('Y-m-d H:i:s', $token_expiry) . "</p>";
    echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>Status:</strong> " . ($is_expired ? '❌ EXPIRED' : '✅ VALID') . "</p>";
    
    if ($is_expired) {
        echo "<p style='color: orange;'>Token expired but should auto-refresh on next use</p>";
    }
} else {
    echo "<p style='color: red;'>No expiry time set</p>";
}

// 4. Check refresh token
$encrypted_refresh_token = get_setting('google_calendar_refresh_token', $user_id);
echo "<h3>4. Refresh Token</h3>";
echo "<p><strong>Exists:</strong> " . (!empty($encrypted_refresh_token) ? '✅ YES' : '❌ NO') . "</p>";
if (empty($encrypted_refresh_token)) {
    echo "<p style='color: red;'>No refresh token found. You'll need to reconnect Google Calendar.</p>";
}

// 5. Check Google OAuth configuration
require_once __DIR__ . '/functions/system_settings.php';
$google_config = get_google_oauth_config();
echo "<h3>5. Google OAuth Configuration</h3>";
echo "<p><strong>Client ID:</strong> " . (!empty($google_config['client_id']) ? '✅ SET' : '❌ NOT SET') . "</p>";
echo "<p><strong>Client Secret:</strong> " . (!empty($google_config['client_secret']) ? '✅ SET' : '❌ NOT SET') . "</p>";

// 6. Test API call
echo "<h3>6. Test API Connection</h3>";
if ($google_sync_enabled === '1' && !empty($encrypted_token)) {
    require_once __DIR__ . '/functions/calendar_sync.php';
    
    echo "<p>Attempting to fetch calendar events...</p>";
    $test_result = fetch_google_calendar_events($user_id);
    
    if (isset($test_result['error'])) {
        echo "<p style='color: red;'><strong>❌ API ERROR:</strong> " . htmlspecialchars($test_result['error']) . "</p>";
    } elseif (isset($test_result['items'])) {
        echo "<p style='color: green;'><strong>✅ API SUCCESS!</strong> Retrieved " . count($test_result['items']) . " events</p>";
    } else {
        echo "<p style='color: orange;'><strong>⚠️ UNKNOWN RESPONSE:</strong></p>";
        echo "<pre>" . htmlspecialchars(print_r($test_result, true)) . "</pre>";
    }
} else {
    echo "<p style='color: gray;'>Skipped (sync disabled or no token)</p>";
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
if ($google_sync_enabled !== '1') {
    echo "<li>Go to <a href='index.php?page=settings'>Settings</a> and enable Google Calendar Sync</li>";
}
if (empty($encrypted_token) || empty($encrypted_refresh_token)) {
    echo "<li>Connect your Google Calendar account in <a href='index.php?page=settings'>Settings</a></li>";
}
if (!empty($google_config['client_id']) === false) {
    echo "<li>Configure Google OAuth credentials in Super Admin settings</li>";
}
echo "<li>After fixing issues above, book a test interview slot and check if it appears in Google Calendar</li>";
echo "<li>Check <code>logs/calendar_sync.log</code> for detailed sync results</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='index.php?page=settings'>← Go to Settings</a> | <a href='index.php'>← Dashboard</a></p>";
?>
