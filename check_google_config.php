<?php
require_once 'functions/db.php';
require_once 'functions/system_settings.php';

echo "Checking Google OAuth Configuration...\n";
echo str_repeat("=", 50) . "\n\n";

// Check system_settings table
$pdo = get_db();
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE '%google%'");
$system_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if (empty($system_settings)) {
    echo "❌ No Google OAuth settings found in system_settings table\n\n";
} else {
    echo "✅ Google OAuth settings in system_settings:\n";
    foreach ($system_settings as $key => $value) {
        $preview = substr($value, 0, 20) . (strlen($value) > 20 ? '...' : '');
        echo "   - $key: $preview\n";
    }
    echo "\n";
}

// Check using get_google_oauth_config()
echo "Config from get_google_oauth_config():\n";
$config = get_google_oauth_config();
foreach ($config as $key => $value) {
    $preview = substr($value, 0, 30) . (strlen($value) > 30 ? '...' : '');
    $is_placeholder = (strpos($value, 'YOUR_') === 0);
    $icon = $is_placeholder ? '❌' : '✅';
    echo "   $icon $key: $preview\n";
}
echo "\n";

if ($config['client_id'] === 'YOUR_GOOGLE_CLIENT_ID') {
    echo "⚠️  WARNING: Google OAuth is NOT configured!\n";
    echo "   Please go to Super Admin panel and configure Google OAuth credentials.\n";
    echo "   URL: http://localhost/HR-portal/index.php?page=super_admin\n";
} else {
    echo "✅ Google OAuth appears to be configured\n";
    echo "   Redirect URI: " . $config['redirect_uri'] . "\n";
}
