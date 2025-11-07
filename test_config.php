<?php
/**
 * Configuration Test Page
 * 
 * This page checks if config.php exists and validates all settings.
 * Access: http://yourdomain.com/test_config.php
 * 
 * SECURITY: Delete this file after testing in production!
 */

// Prevent direct access in production
if (!isset($_GET['test']) || $_GET['test'] !== 'run') {
    die('Access denied. Add ?test=run to URL to run tests.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .test-section {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .test-header {
            background: #f5f5f5;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 16px;
            border-bottom: 1px solid #e0e0e0;
        }
        .test-body {
            padding: 20px;
        }
        .test-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .test-item:last-child {
            border-bottom: none;
        }
        .status {
            width: 80px;
            text-align: center;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 4px;
            margin-right: 15px;
            font-size: 12px;
        }
        .status.pass {
            background: #4caf50;
            color: white;
        }
        .status.fail {
            background: #f44336;
            color: white;
        }
        .status.warning {
            background: #ff9800;
            color: white;
        }
        .status.info {
            background: #2196f3;
            color: white;
        }
        .test-label {
            flex: 1;
            color: #333;
        }
        .test-value {
            color: #666;
            font-family: monospace;
            font-size: 13px;
            background: #f5f5f5;
            padding: 3px 8px;
            border-radius: 3px;
        }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        .summary h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .summary-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 15px;
        }
        .stat {
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
        }
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .alert.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        .alert.warning {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffcc80;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #999;
            font-size: 13px;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            color: #e91e63;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Configuration Test</h1>
            <p>HR Portal Configuration Validator</p>
        </div>
        <div class="content">
            <?php
            $passed = 0;
            $failed = 0;
            $warnings = 0;
            $tests = [];

            // Test 1: Check if config.php exists
            $configExists = file_exists(__DIR__ . '/config.php');
            $tests[] = [
                'status' => $configExists ? 'pass' : 'fail',
                'label' => 'config.php file exists',
                'value' => $configExists ? 'Found' : 'Missing'
            ];
            $configExists ? $passed++ : $failed++;

            if (!$configExists) {
                echo '<div class="alert error">';
                echo '<strong>❌ Critical Error:</strong> config.php not found!<br>';
                echo 'Please copy <code>config.example.php</code> to <code>config.php</code> and configure it.';
                echo '</div>';
            } else {
                // Test 2: Load config
                try {
                    $config = require __DIR__ . '/config.php';
                    $tests[] = [
                        'status' => 'pass',
                        'label' => 'Config file loads successfully',
                        'value' => 'Valid PHP'
                    ];
                    $passed++;

                    // Test 3: Check secret key
                    $secretKeySet = isset($config['app_secret_key']) && 
                                   $config['app_secret_key'] !== 'CHANGE_THIS_TO_RANDOM_64_CHAR_HEX_STRING' &&
                                   strlen($config['app_secret_key']) === 64;
                    $tests[] = [
                        'status' => $secretKeySet ? 'pass' : 'fail',
                        'label' => 'Secret key configured (64 chars)',
                        'value' => $secretKeySet ? 'Configured' : 'Using default/invalid'
                    ];
                    $secretKeySet ? $passed++ : $failed++;

                    // Test 4: HTTPS enforcement
                    $httpsEnabled = isset($config['force_https']) && $config['force_https'] === true;
                    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                               $_SERVER['SERVER_PORT'] == 443;
                    
                    if ($httpsEnabled && !$isHttps) {
                        $tests[] = [
                            'status' => 'warning',
                            'label' => 'HTTPS enforcement',
                            'value' => 'Enabled but not using HTTPS'
                        ];
                        $warnings++;
                    } else {
                        $tests[] = [
                            'status' => $httpsEnabled ? 'pass' : 'warning',
                            'label' => 'HTTPS enforcement',
                            'value' => $httpsEnabled ? 'Enabled' : 'Disabled (dev only)'
                        ];
                        $httpsEnabled ? $passed++ : $warnings++;
                    }

                    // Test 5: Google OAuth
                    $googleConfigured = isset($config['google_client_id']) && 
                                       isset($config['google_client_secret']) &&
                                       strpos($config['google_client_id'], 'YOUR_') === false;
                    $tests[] = [
                        'status' => $googleConfigured ? 'pass' : 'info',
                        'label' => 'Google Calendar OAuth configured',
                        'value' => $googleConfigured ? 'Configured' : 'Not configured'
                    ];
                    $googleConfigured ? $passed++ : null;

                    // Test 6: Microsoft OAuth
                    $microsoftConfigured = isset($config['microsoft_client_id']) && 
                                          isset($config['microsoft_client_secret']) &&
                                          strpos($config['microsoft_client_id'], 'YOUR_') === false;
                    $tests[] = [
                        'status' => $microsoftConfigured ? 'pass' : 'info',
                        'label' => 'Outlook Calendar OAuth configured',
                        'value' => $microsoftConfigured ? 'Configured' : 'Not configured'
                    ];
                    $microsoftConfigured ? $passed++ : null;

                    // Test 7: Session timeout
                    $sessionTimeout = isset($config['session_timeout']) ? $config['session_timeout'] : 0;
                    $tests[] = [
                        'status' => $sessionTimeout > 0 ? 'pass' : 'fail',
                        'label' => 'Session timeout configured',
                        'value' => $sessionTimeout > 0 ? ($sessionTimeout / 60) . ' minutes' : 'Not set'
                    ];
                    $sessionTimeout > 0 ? $passed++ : $failed++;

                    // Test 8: Rate limiting
                    $rateLimitEnabled = isset($config['rate_limit_enabled']) && $config['rate_limit_enabled'] === true;
                    $tests[] = [
                        'status' => $rateLimitEnabled ? 'pass' : 'warning',
                        'label' => 'Rate limiting enabled',
                        'value' => $rateLimitEnabled ? 'Enabled' : 'Disabled'
                    ];
                    $rateLimitEnabled ? $passed++ : $warnings++;

                    // Test 9: Password requirements
                    $passwordLength = isset($config['password_min_length']) ? $config['password_min_length'] : 0;
                    $tests[] = [
                        'status' => $passwordLength >= 12 ? 'pass' : 'warning',
                        'label' => 'Strong password policy (≥12 chars)',
                        'value' => $passwordLength . ' characters minimum'
                    ];
                    $passwordLength >= 12 ? $passed++ : $warnings++;

                    // Test 10: Security logging
                    $loggingEnabled = isset($config['enable_security_logging']) && $config['enable_security_logging'] === true;
                    $tests[] = [
                        'status' => $loggingEnabled ? 'pass' : 'warning',
                        'label' => 'Security logging enabled',
                        'value' => $loggingEnabled ? 'Enabled' : 'Disabled'
                    ];
                    $loggingEnabled ? $passed++ : $warnings++;

                    // Test 11: Debug mode (should be off in production)
                    $debugMode = isset($config['debug_mode']) && $config['debug_mode'] === true;
                    $tests[] = [
                        'status' => !$debugMode ? 'pass' : 'warning',
                        'label' => 'Debug mode (should be OFF)',
                        'value' => $debugMode ? 'ON (insecure!)' : 'OFF'
                    ];
                    !$debugMode ? $passed++ : $warnings++;

                    // Test 12: Database connection
                    try {
                        require_once __DIR__ . '/functions/db.php';
                        $db = getDBConnection();
                        $tests[] = [
                            'status' => 'pass',
                            'label' => 'Database connection',
                            'value' => 'Connected'
                        ];
                        $passed++;
                    } catch (Exception $e) {
                        $tests[] = [
                            'status' => 'fail',
                            'label' => 'Database connection',
                            'value' => 'Failed: ' . $e->getMessage()
                        ];
                        $failed++;
                    }

                    // Test 13: Security functions available
                    $securityFileExists = file_exists(__DIR__ . '/functions/security.php');
                    $tests[] = [
                        'status' => $securityFileExists ? 'pass' : 'fail',
                        'label' => 'Security functions available',
                        'value' => $securityFileExists ? 'security.php found' : 'Missing'
                    ];
                    $securityFileExists ? $passed++ : $failed++;

                    // Test 14: Upload directory writable
                    $uploadDir = isset($config['upload_dir']) ? $config['upload_dir'] : __DIR__ . '/assets/uploads';
                    $uploadWritable = is_dir($uploadDir) && is_writable($uploadDir);
                    $tests[] = [
                        'status' => $uploadWritable ? 'pass' : 'fail',
                        'label' => 'Upload directory writable',
                        'value' => $uploadWritable ? 'Writable' : 'Not writable'
                    ];
                    $uploadWritable ? $passed++ : $failed++;

                } catch (Exception $e) {
                    echo '<div class="alert error">';
                    echo '<strong>❌ Error loading config:</strong> ' . htmlspecialchars($e->getMessage());
                    echo '</div>';
                    $failed++;
                }
            }

            // Calculate score
            $total = $passed + $failed + $warnings;
            $score = $total > 0 ? round(($passed / $total) * 100) : 0;

            // Display summary
            echo '<div class="summary">';
            echo '<h2>Configuration Score: ' . $score . '%</h2>';
            if ($score >= 80) {
                echo '<p>✅ Your configuration is production-ready!</p>';
            } elseif ($score >= 60) {
                echo '<p>⚠️ Configuration needs improvements for production use</p>';
            } else {
                echo '<p>❌ Configuration has critical issues that must be fixed</p>';
            }
            echo '<div class="summary-stats">';
            echo '<div class="stat"><div class="stat-value" style="color: #4caf50;">' . $passed . '</div><div class="stat-label">Passed</div></div>';
            echo '<div class="stat"><div class="stat-value" style="color: #ff9800;">' . $warnings . '</div><div class="stat-label">Warnings</div></div>';
            echo '<div class="stat"><div class="stat-value" style="color: #f44336;">' . $failed . '</div><div class="stat-label">Failed</div></div>';
            echo '</div>';
            echo '</div>';

            // Display test results
            echo '<div class="test-section">';
            echo '<div class="test-header">📋 Configuration Tests</div>';
            echo '<div class="test-body">';
            foreach ($tests as $test) {
                echo '<div class="test-item">';
                echo '<span class="status ' . $test['status'] . '">' . strtoupper($test['status']) . '</span>';
                echo '<span class="test-label">' . htmlspecialchars($test['label']) . '</span>';
                echo '<span class="test-value">' . htmlspecialchars($test['value']) . '</span>';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';

            // Recommendations
            if ($failed > 0 || $warnings > 0) {
                echo '<div class="test-section">';
                echo '<div class="test-header">💡 Recommendations</div>';
                echo '<div class="test-body">';
                
                if ($failed > 0) {
                    echo '<div class="alert error">';
                    echo '<strong>Critical Issues:</strong><br>';
                    echo '<ul style="margin: 10px 0 0 20px;">';
                    if (!$configExists) {
                        echo '<li>Copy <code>config.example.php</code> to <code>config.php</code></li>';
                    }
                    foreach ($tests as $test) {
                        if ($test['status'] === 'fail') {
                            echo '<li>' . htmlspecialchars($test['label']) . ' - ' . htmlspecialchars($test['value']) . '</li>';
                        }
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                
                if ($warnings > 0) {
                    echo '<div class="alert warning">';
                    echo '<strong>Warnings:</strong><br>';
                    echo '<ul style="margin: 10px 0 0 20px;">';
                    foreach ($tests as $test) {
                        if ($test['status'] === 'warning') {
                            echo '<li>' . htmlspecialchars($test['label']) . ' - ' . htmlspecialchars($test['value']) . '</li>';
                        }
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                
                echo '</div>';
                echo '</div>';
            }

            // Next steps
            echo '<div class="test-section">';
            echo '<div class="test-header">🚀 Next Steps</div>';
            echo '<div class="test-body">';
            if ($score >= 80) {
                echo '<p style="color: #4caf50; font-weight: 600; margin-bottom: 10px;">✅ Configuration is ready for use!</p>';
                echo '<ol style="margin-left: 20px; line-height: 1.8;">';
                echo '<li>Go to <a href="gui/settings.php" style="color: #667eea;">Settings</a> to configure integrations</li>';
                echo '<li>Add your Gemini API key for AI features</li>';
                echo '<li>Set up Zoom, Google Calendar, or Outlook (optional)</li>';
                echo '<li><strong style="color: #f44336;">Delete this test_config.php file in production!</strong></li>';
                echo '</ol>';
            } else {
                echo '<ol style="margin-left: 20px; line-height: 1.8;">';
                echo '<li>Fix all <span style="color: #f44336; font-weight: 600;">FAIL</span> items above</li>';
                echo '<li>Review and address <span style="color: #ff9800; font-weight: 600;">WARNING</span> items</li>';
                echo '<li>Run this test again: <code>' . htmlspecialchars($_SERVER['REQUEST_URI']) . '</code></li>';
                echo '<li>Once score is ≥80%, proceed to Settings page</li>';
                echo '</ol>';
            }
            echo '</div>';
            echo '</div>';
            ?>
        </div>
        <div class="footer">
            <p><strong>⚠️ Security Note:</strong> Delete this file (<code>test_config.php</code>) after testing in production!</p>
            <p style="margin-top: 10px;">HR Portal v2.0 | Configuration Validator</p>
        </div>
    </div>
</body>
</html>
