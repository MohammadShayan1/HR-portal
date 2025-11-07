<?php
/**
 * Fix Google Calendar Connection
 * This page helps reconnect an expired Google Calendar connection
 */

session_start();
require_once __DIR__ . '/functions/db.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/system_settings.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle disconnect action
if (isset($_GET['disconnect'])) {
    require_once __DIR__ . '/functions/db.php';
    delete_setting('google_calendar_token', $user_id);
    delete_setting('google_calendar_token_expiry', $user_id);
    delete_setting('google_calendar_refresh_token', $user_id);
    delete_setting('google_calendar_sync', $user_id);
    
    $message = 'Google Calendar disconnected successfully. You can now reconnect.';
    $message_type = 'success';
}

// Check current status
$is_connected = !empty(get_setting('google_calendar_token', $user_id));
$sync_enabled = get_setting('google_calendar_sync', $user_id) === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Google Calendar - HR Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Fix Google Calendar Connection</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-danger">
                            <h5><i class="bi bi-x-circle"></i> Authentication Error</h5>
                            <p>Your Google Calendar access token has <strong>expired</strong> or is <strong>invalid</strong>.</p>
                            <p class="mb-0">This happens when:</p>
                            <ul class="mt-2">
                                <li>The token has been unused for 6 months</li>
                                <li>You revoked access from your Google account</li>
                                <li>The OAuth credentials changed</li>
                            </ul>
                        </div>

                        <h5 class="mt-4">Current Status:</h5>
                        <ul class="list-group mb-4">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Google Calendar Connected
                                <span class="badge bg-<?php echo $is_connected ? 'success' : 'danger'; ?>">
                                    <?php echo $is_connected ? 'YES' : 'NO'; ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Calendar Sync Enabled
                                <span class="badge bg-<?php echo $sync_enabled ? 'success' : 'secondary'; ?>">
                                    <?php echo $sync_enabled ? 'YES' : 'NO'; ?>
                                </span>
                            </li>
                        </ul>

                        <h5 class="mt-4">How to Fix:</h5>
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title"><strong>Step 1: Disconnect Current Connection</strong></h6>
                                <p class="card-text">Clear the invalid tokens first</p>
                                <a href="?disconnect=1" class="btn btn-warning">
                                    <i class="bi bi-unlink"></i> Disconnect Google Calendar
                                </a>
                            </div>
                        </div>

                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title"><strong>Step 2: Reconnect to Google</strong></h6>
                                <p class="card-text">Go to Settings and authenticate again</p>
                                <a href="index.php?page=settings#calendar-integration" class="btn btn-primary">
                                    <i class="bi bi-arrow-right-circle"></i> Go to Settings
                                </a>
                            </div>
                        </div>

                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><strong>Step 3: Enable Calendar Sync</strong></h6>
                                <p class="card-text mb-0">In Settings:</p>
                                <ol class="mt-2 mb-0">
                                    <li>Scroll to <strong>Calendar Integration</strong> section</li>
                                    <li>Click <strong>"Connect Google Calendar"</strong> button</li>
                                    <li>Sign in and grant permissions</li>
                                    <li>Enable <strong>"Sync to Google Calendar"</strong> checkbox</li>
                                    <li>Save settings</li>
                                </ol>
                            </div>
                        </div>

                        <div class="alert alert-info mt-4">
                            <h6><i class="bi bi-info-circle"></i> After Reconnecting:</h6>
                            <ul class="mb-0">
                                <li>New interview bookings will automatically sync to Google Calendar</li>
                                <li>You'll see Google Calendar events in your HR Portal dashboard</li>
                                <li>Events will include candidate details and Zoom links</li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-house"></i> Back to Dashboard
                        </a>
                        <a href="test_google_sync.php" class="btn btn-info">
                            <i class="bi bi-gear"></i> Test Connection
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
